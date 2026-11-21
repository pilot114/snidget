### Подключение

Из коробки поддерживается SQLite - база, которой не нужен отдельный сервер, а значит и лишний контейнер.
Работа с ней построена на PDO и состоит из трех частей:

    ConnectConfig - параметры подключения (dsn, user, password)
    Driver        - соединение и выполнение запросов
    Table         - операции с одной таблицей, схема которой описана DTO

По умолчанию `ConnectConfig` указывает на базу в памяти (`sqlite::memory:`): такая база живет ровно один
HTTP запрос или один запуск команды - удобно для тестов и экспериментов, но не для хранения данных.
Для файловой базы драйвер создается явно, а файлы удобно хранить в директории _data_ (она не попадает в git):

    $driver = new Driver(new ConnectConfig(dsn: 'sqlite:/app/data/app.db'));

Если файла еще нет, драйвер создаст его сам.

Контейнер хранит созданные объекты, поэтому достаточно один раз положить в него драйвер с нужной базой -
дальше автовайринг будет отдавать именно его (подробнее в "Расширении функционала"):

    $container->get(Driver::class, ['config' => new ConnectConfig(dsn: 'sqlite:/app/data/app.db')]);

### Запросы

`Driver` - тонкая обертка над PDO: только подготовленные запросы, исключения при ошибках,
строки в виде ассоциативных массивов.

    $driver->execute('UPDATE people SET age = :age WHERE id = :id', ['age' => 30, 'id' => 1]); // bool
    $rows = $driver->query('SELECT * FROM people WHERE age > :age', ['age' => 18]);         // list<array>
    $total = $driver->count('SELECT count(*) FROM people');                                  // int

Транзакции - через замыкание: при любом исключении изменения откатываются, а наружу выбрасывается
`SnidgetException` с исходной ошибкой внутри.

    $driver->transaction(function (Driver $db) {
        $db->execute(...);
        $db->execute(...);
    });

Для ручного управления есть `beginTransaction()`, `commit()` и `rollback()`.

### Схема таблицы

Схема описывается тем же DTO, которым данные передаются по приложению. Колонки размечаются атрибутом `Column`,
DTO для базы принято складывать в _Schema/Database_:

    class People extends Type
    {
        #[Column(name: 'id', type: Type::INTEGER, autoincrement: true)]
        public ?int $id = null;

        #[Column(name: 'name')]
        public ?string $name = 'test';

        #[Column(name: 'age', isNull: true, default: 18)]
        public int $age = 18;
    }

Здесь два разных `Type`: базовый класс DTO (`Snidget\Kernel\Schema\Type`) и перечисление типов SQLite
(`Snidget\Database\SQLite\Type`: `NULL`, `INTEGER`, `REAL`, `TEXT`, `BLOB`). Тип колонки берется только
из атрибута, тип PHP свойства на него не влияет: по умолчанию колонка - `TEXT`, поэтому `age` из примера
вернется из базы строкой `"18"`. Для чисел тип нужно указать явно.

Параметры `Column`: `name`, `type`, `isNull` (по умолчанию колонка `NOT NULL`), `autoincrement`
(делает колонку `PRIMARY KEY AUTOINCREMENT`), `default`.

### Работа с таблицей

`Table` связывает драйвер, имя таблицы и DTO-схему:

    $table = $container->get(Table::class, [
        'name' => 'people',
        'type' => $container->get(People::class),
    ]);

    if (!$table->exist()) {
        $table->create();              // CREATE TABLE по атрибутам Column
    }

    $table->insert(new People(['name' => 'Bob', 'age' => 25]));
    $table->count();                   // int
    $table->find();                    // все строки
    $table->read(1);                   // строка по id (или по другому полю: read(25, 'age')), [] если не найдена
    $table->like('bo', 'name');        // поиск подстроки без учета регистра (только латиница - так устроен LIKE в SQLite)

`insert()` пропускает свойства со значением `null` - так автоинкрементный `id` заполняет сама база.
`create('other')` создает таблицу копированием другой (`CREATE TABLE ... AS SELECT`).

Имена таблиц и полей подставляются в SQL напрямую, поэтому `Table` проверяет их и выбрасывает исключение
на всё, что не похоже на идентификатор - имя поля из запроса клиента не превратится в SQL-инъекцию.
Значения же всегда передаются через параметры.

Пример работы с таблицей целиком - контроллер `App\HTTP\Controller\Api` (_/api/v1/post_).

### Структура базы

`Meta` разбирает схему существующей базы: таблицы, колонки и внешние ключи. Посмотреть на нее можно командой:

    bin/app Database:run

Полезные ссылки:
- [Типы данных SQLite](https://www.sqlite.org/datatype3.html)
- [PDO](https://www.php.net/manual/ru/book.pdo.php)
