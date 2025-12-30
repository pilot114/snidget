# Отчёт по проблемным местам фреймворка Snidget

## Общая информация

- **Версия PHP**: 8.4+
- **Размер ядра**: ~500 LLOC, 37 PHP файлов
- **Назначение**: Образовательный/pet-project фреймворк

---

## 1. Проблемы безопасности

### 1.1 SQL Injection в Table.php

**Файл**: `src/Database/SQLite/Table.php:17-19, 26-28, 45, 58-62`

**Критичность**: ВЫСОКАЯ

Используется прямая интерполяция переменных в SQL-запросы:

```php
// Строка 18
$sql = "select name from sqlite_master where type='table' and name = '{$this->name}'";

// Строка 26
$sql = "create table {$this->name} ($definition)";

// Строка 28
$sql = "create table {$this->name} select * from $from";

// Строка 45-49 - частично защищено через prepared statement, но $field не защищён
return $this->db->query(
    "select * from {$this->name} where $field like lower(:q)",
    ['q' => '%' . mb_strtolower($q) . '%']
);

// Строка 60 - $field не защищён
"select * from {$this->name} where $field = :id limit 1"
```

**Рекомендация**: Использовать prepared statements для всех параметров или валидировать имена таблиц/полей через белый список.

---

### 1.2 Command Injection в Server.php

**Файл**: `App/Module/Async/Server.php:82`

**Критичность**: ВЫСОКАЯ

```php
$responseString = shell_exec('php ' . $request->uri);
```

URI из запроса напрямую передаётся в `shell_exec`, что позволяет выполнить произвольные команды.

**Рекомендация**: Использовать `escapeshellarg()` или полностью избежать вызова shell.

---

### 1.3 Отсутствие валидации входных данных в Request.php

**Файл**: `src/HTTP/Request.php:15-17`

**Критичность**: СРЕДНЯЯ

```php
$this->uri = trim(parse_url($_SERVER['REQUEST_URI'])['path'] ?? '', '/');
$this->payload = json_decode(file_get_contents('php://input') ?: '', true);
```

- Нет проверки на размер payload
- Нет лимита на длину URI
- Отсутствует санитизация

---

### 1.4 die() в middleware вместо исключений

**Файл**: `App/Module/Core/HTTP/Middleware/BuiltIn.php:28-29`

**Критичность**: СРЕДНЯЯ

```php
if ($messages) {
    dump($messages);
    die();
}
```

Использование `die()` прерывает выполнение без возможности корректной обработки ошибки.

---

## 2. Архитектурные проблемы

### 2.1 Жёстко закодированные пути

**Файл**: `src/Kernel/Kernel.php:22`

**Критичность**: ВЫСОКАЯ

```php
$appPath = '/app/App';
```

Путь к приложению жёстко закодирован и работает только в Docker-контейнере.

**Рекомендация**: Использовать конфигурацию или environment variables.

---

### 2.2 Нарушение PSR-11 в Container

**Файл**: `src/Kernel/PSR/Container.php:66`

**Критичность**: СРЕДНЯЯ

```php
public function get(string $id, array $params = [])
```

PSR-11 `ContainerInterface::get()` не принимает второй параметр. Это нарушает контракт интерфейса.

---

### 2.3 Неполная реализация PSR-14 EventDispatcher

**Файл**: `src/Kernel/PSR/Event/EventManager.php:41-45`

**Критичность**: НИЗКАЯ

```php
public function dispatch(object $event)
{
    // TODO
    return $event;
}
```

Метод `dispatch()` не реализован, хотя класс имплементирует `EventDispatcherInterface`.

---

### 2.4 Статические свойства в Server.php

**Файл**: `App/Module/Async/Server.php:13-16`

**Критичность**: СРЕДНЯЯ

```php
public static \Closure $kernelHandler;
public static Request $request;
protected static array $serveFiles = [];
```

Использование static делает код сложным для тестирования и потенциально создаёт проблемы с состоянием в долгоживущих процессах.

---

### 2.5 Публичные свойства в SplQueue

**Файл**: `App/Module/Async/Scheduler.php:10`

**Критичность**: НИЗКАЯ

```php
public SplQueue $fibers;
```

Публичное свойство позволяет внешнему коду модифицировать внутреннее состояние.

---

## 3. Проблемы с типизацией

### 3.1 Слабая типизация в Container

**Файл**: `src/Kernel/PSR/Container.php:19-20`

```php
protected array $pool = [];
protected array $map = [];
```

Отсутствуют PHPDoc аннотации для типов массивов.

---

### 3.2 Отсутствие declare(strict_types=1)

**Файлы**: Большинство файлов ядра

Только `src/Kernel/Schema/Type.php` содержит `declare(strict_types=1)`. Рекомендуется добавить во все файлы.

---

### 3.3 Unchecked null returns

**Файл**: `src/Kernel/PSR/Container.php:120-121`

```php
$type = $param->getType();
$typeName = is_null($type) ? 'mixed' : $type->getName();
```

`getType()` может вернуть `ReflectionUnionType` или `ReflectionIntersectionType`, у которых нет метода `getName()`.

---

## 4. Проблемы производительности

### 4.1 Повторное создание Reflection объектов

**Файл**: `src/Kernel/AttributeLoader.php`, `src/Kernel/Schema/Type.php`

В каждом вызове создаётся новый `Reflection` объект:

```php
$ref = new Reflection($className);
```

**Рекомендация**: Кэшировать Reflection объекты (упоминается в TODO в Container.php:14).

---

### 4.2 Неэффективная проверка JSON в Response

**Файл**: `src/HTTP/Response.php:23-27`

```php
protected function isJson(string $data): bool
{
    json_decode($data);
    return json_last_error() === JSON_ERROR_NONE;
}
```

Полное декодирование JSON только для проверки валидности неэффективно.

**Рекомендация**: Проверять первый символ (`{` или `[`) или использовать Content-Type заголовок.

---

### 4.3 usleep(0) в Scheduler

**Файл**: `App/Module/Async/Scheduler.php:41`

```php
usleep(0);
```

Согласно комментарию в Server.php:22-23, это снижает RPS с ~12600 до ~1650.

---

## 5. Проблемы с покрытием тестами

### 5.1 Минимальное покрытие

Существует только 2 реальных теста:
- `ContainerTest.php` - тестирование DI контейнера
- `InMemoryCacheTest.php` - тестирование кэша

**Не покрыты тестами**:
- Router
- Request/Response
- CommandHandler
- MiddlewareManager
- EventManager
- AttributeLoader
- Все компоненты Database
- Все Async компоненты

---

## 6. Незавершённая функциональность (TODO)

| Файл | Строка | Описание |
|------|--------|----------|
| `Container.php` | 14 | WeakMap для кэширования |
| `functions.php` | 24 | Вывод в асинхронном режиме не работает |
| `Server.php` | 81 | Обработка output и terminate в текущем процессе |
| `CommandHandler.php` | 37 | Вынести banner/help в отдельную команду |
| `AttributeLoader.php` | 131 | CLI info |
| `EventManager.php` | 43 | dispatch() не реализован |
| `raw.php` | 6-7 | Заменить format на длину в битах |
| `raw.php` | 202 | Response common format |

---

## 7. Проблемы кодирования

### 7.1 Смешение языков в сообщениях об ошибках

**Файлы**: Множество файлов

Сообщения об ошибках на русском языке:
```php
throw new SnidgetException("Невозможно инcтанцировать абстрактный класс $id");
throw new SnidgetException("Не найден роут для URI: '$request->uri'");
```

**Рекомендация**: Стандартизировать на одном языке (английский для лучшей совместимости).

---

### 7.2 Неиспользуемые переменные

**Файл**: `src/HTTP/Router.php:10`

```php
protected array $route = [];
```

Свойство `$route` присваивается в `match()`, но никогда не читается.

---

### 7.3 Публичные свойства без readonly

**Файл**: `src/HTTP/Request.php:7-11`

```php
public string $uri;
public string $method = 'GET';
public array $headers = [];
public mixed $payload;
public float $requestTimeMs;
```

Публичные свойства позволяют внешнему коду модифицировать объект.

**Рекомендация**: Использовать `readonly` для иммутабельности.

---

### 7.4 Логическая ошибка в Type.php

**Файл**: `src/Kernel/Schema/Type.php:101`

```php
if (!in_array($property->getName(), array_flip($this->useFields))) {
```

`array_flip($this->useFields)` вернёт массив с ключами = значениям `useFields`, что не соответствует логике проверки.

**Должно быть**: `in_array($property->getName(), $this->useFields)` или `isset(array_flip($this->useFields)[$property->getName()])`.

---

## 8. Документация

### 8.1 Пустые файлы документации

- `utils/docs/md/http.md` - пусто
- `utils/docs/md/cli.md` - пусто

### 8.2 Отсутствие PHPDoc

Многие публичные методы не имеют PHPDoc комментариев.

---

## 9. Файл raw.php

**Файл**: `src/raw.php`

**Критичность**: НИЗКАЯ (обучающий код)

Файл содержит сетевой код (ICMP, IP пакеты) который:
- Требует root-прав
- Не интегрирован в фреймворк
- Содержит закомментированный код

**Рекомендация**: Вынести в отдельный пример или удалить из src.

---

## Резюме

### Критические проблемы (требуют исправления):
1. SQL Injection в Table.php
2. Command Injection в Server.php
3. Жёстко закодированные пути

### Важные улучшения:
1. Увеличить покрытие тестами
2. Добавить declare(strict_types=1) во все файлы
3. Реализовать кэширование Reflection
4. Исправить нарушения PSR

### Рекомендации:
1. Стандартизировать язык сообщений
2. Завершить TODO
3. Добавить PHPDoc
4. Использовать readonly свойства
