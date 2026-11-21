### События ядра

Все системные события отправляет `Kernel`, их список - перечисление `Snidget\Kernel\PSR\Event\KernelEvent`.

- `START` - создание ядра, еще до регистрации обработчиков ошибок. Данных нет
- `REQUEST` - запрос получен, роутинг еще не выполнялся. Данные - `Request`
- `RESPONSE` - ответ готов и сейчас будет отправлен. Данные - `Response`
- `ERROR` - необработанное исключение (ответ 500), warning или notice PHP, исключение вне обработки запроса.
Данные - `Throwable`
- `FINISH` - завершение скрипта (shutdown), как можно позже. Данных нет

Порядок для обычного запроса: START → REQUEST → RESPONSE → FINISH, при ошибке между REQUEST и RESPONSE
добавляется ERROR. Ответы 404 и 405 ошибкой не считаются и ERROR не вызывают.

В асинхронном режиме (модуль Async) START и FINISH случаются один раз на весь процесс сервера,
а REQUEST и RESPONSE - на каждый запрос.

Консольные команды запускаются без ядра, поэтому в CLI события ядра не отправляются.

### Подписка

Слушатель - метод любого класса из _App_ (или из ядра фреймворка) с атрибутом `Listen`:

    class RequestLogger
    {
        #[Listen(KernelEvent::REQUEST)]
        public function onRequest(Request $data, Logger $logger): void
        {
            $logger->info("{$data->method} /{$data->uri}");
        }

        #[Listen(KernelEvent::START)]
        #[Listen(KernelEvent::FINISH)]
        public function onBoundary(): void { ... }
    }

- данные события приходят в параметр с именем **`$data`** - имя важно, тип можно указывать любой подходящий
- остальные параметры, как обычно, заполняет контейнер
- атрибут повторяемый: один метод может слушать несколько событий
- директория не важна: слушатели ищутся во всем _App_, включая модули

Пример из самого фреймворка - `Snidget\Kernel\ErrorHandler`: по событию ERROR он сразу пишет ошибку в лог PHP
(в консоли - в stderr). Фатальные ошибки до обработчиков не доходят, их пишет в лог сам PHP.

### Свои события

Событием может быть любое перечисление (enum). Отправляется оно через `EventManager`:

    enum OrderEvent
    {
        case CREATED;
        case PAID;
    }

    $eventManager->emit(OrderEvent::CREATED, $order);

`EventManager` реализует PSR-14, и его `dispatch()` принимает в том числе объекты-события
(с поддержкой `StoppableEventInterface`), но подписаться на них через `#[Listen]` пока нельзя - атрибут
принимает только варианты перечислений.
