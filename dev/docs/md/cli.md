### Запуск команд

Точка входа консоли - _bin/app_. Команда задается в формате `Класс:метод`:

    bin/app Test:run -a hello -a world -b=test -de one --gamma test2 two three1 three2

Запуск без аргументов выводит список всех команд с описаниями:

    $ bin/app
    AutoComplete
        run	auto-complete for commands and options
    Database
        run	Test database
    Test
        run	Creates a new user

Команды запускаются внутри docker-контейнера: `make enter` открывает в нем shell (zsh) в директории _/app_.

### Команды

Команда - публичный метод с атрибутом `Command` в классе из директории _Command_ (приложения или модуля).
Имя команды - короткое имя класса и имя метода, описание берется из атрибута:

    class Test
    {
        #[Command('Creates a new user')]
        public function run(TestInput $data, Logger $logger): void
        {
            echo "success!\n";
            dump($data);
        }
    }

Параметры метода заполняет контейнер зависимостей, как и в HTTP контроллерах. Если среди параметров
есть DTO (наследник `Snidget\Kernel\Schema\Type`), он будет заполнен аргументами командной строки.

Модульный префикс у команд пока не поддерживается, поэтому имена классов команд должны быть уникальны
во всем приложении.

### Опции и аргументы

Описание входных данных команды - это DTO, публичные свойства которого размечены атрибутом `Arg`:

    class TestInput extends Type
    {
        /** @var list<string> */
        #[Arg('alpha option', short: 'a')]
        public array $alpha = [];

        #[Arg('beta option', short: 'b')]
        public ?string $beta = null;

        #[Arg('delta option', short: 'd')]
        public bool $delta = false;

        #[Arg('first arg', isOption: false)]
        public string $first = '';

        /** @var list<string> */
        #[Arg('third arg', isOption: false)]
        public array $third = [];
    }

По умолчанию `Arg` описывает **опцию**, с `isOption: false` - позиционный **аргумент**.

Опции:

- длинное имя совпадает с именем свойства: `--beta=test` или `--beta test`
- короткое задается через `short`: `-b test`, `-btest` или `-b=test`
- `bool` - флаг без значения, короткие флаги можно склеивать: `-de` = `-d -e`
- `array` - опцию можно повторять, значения накапливаются: `-a hello -a world`
- остальные опции необязательны по своей природе, поэтому их тип должен допускать `null` (`?string`),
иначе команда завершится ошибкой
- `int` и `float` значения приводятся к нужному типу

Аргументы заполняются по порядку объявления свойств. Аргумент-массив забирает все оставшиеся значения,
поэтому он должен быть последним. Всё, что идет после `--`, считается аргументами, даже если начинается с `-`.

Неизвестная опция или опция без значения - это исключение с понятным сообщением:

    Fatal error: Uncaught Snidget\Kernel\SnidgetException: Не указано значение опции --beta

### Автодополнение

Для zsh в контейнере подключается функция автодополнения команд и их опций (_dev/docker/php/completion.zsh_).
Под капотом оно вызывает обычную команду `AutoComplete:run` модуля Core, которая читает те же атрибуты
`Command` и `Arg` - так что новые команды и опции появляются в подсказках сами.

### Вывод

Для отладочного вывода есть глобальная функция `dump()` - в консоли она печатает `print_r` значения,
в браузере оборачивает его в `<pre>`. Для цветного вывода заготовлены перечисления `CLIColor` и `CLIStyle` (функции, которая их использует, пока нет).

Полезные ссылки:
- [Соглашения об аргументах командной строки (POSIX)](https://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap12.html)
