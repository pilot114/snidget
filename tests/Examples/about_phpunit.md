Заметки для доклада по phpunit

Юнит - это черная коробка
Вход и выход - это "чистые" зависимости
Побочные эффекты - мок-зависимости

### CLI

--coverage-html tests/output/coverage
--coverage-filter src
    Отчет по покрытию кода. Должен быть установлен xdebug и переменная окружения XDEBUG_MODE=coverage
--process-isolation
    Для лучшей изоляции тестов
--order-by random
    Указать порядок тестов, например рандомный
-v
    Больше информации, например показывает почему пропущены тесты
--bootstrap
    Скрипт, подключаемый перед запуском тестов
--testdox
    Более читабельный вывод, основанный на названиях классов и методов
--repeat
    Повторить N раз
--printer
    Переопределить класс для вывода
--configuration
    Если нужно вынести конфиги phpUnit в файл

Для разработчиков:
--filter TestNamespace
--filter TestCaseClass
--filter testMethod
--filter 'testMethod#2-4'
--filter 'testMethod@my.*data'

--group
    Фильтрация по аннотациям @author @ticket @group

### Базовые требования к тестам

- Должны наследоваться от PHPUnit\Framework\TestCase
- Тест для класса FooBar должен называться FooBarTest
- Методы должны начинаться с префикса test

### depends / data-providers

@depends (clone/shallowClone) testName
    явная зависимость тестов. Если передается объект, можно включить клонирование

@dataProvider providerName
    провайдер должен вернуть массив массивов или Iterator массивов. Выполняется до любых других вызовов

Можно комбинировать depends и dataProvider, аргументы от depends будут добавляться в конце (статично)

### Ошибки

expectException
expectExceptionCode
expectExceptionMessage
expectExceptionMessageMatches
    Если ожидаем Exception
expectDeprecation
expectNotice
expectWarning
expectError
    Если ожидаем прочие ошибки

В тестах оправдано использовать подавление ошибок через @, т.к. все ошибки преобразуются в Exceptions

### Вывод

expectOutputString
expectOutputRegex
setOutputCallback + getActualOutput
    Для проверки вывода

### Фикстуры
### Организация тестов
### Risky
### Incompleted / Skipped
### Stubs / Mocks
### Покрытие кода