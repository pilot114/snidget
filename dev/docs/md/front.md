### Где живет представление

В Snidget нет шаблонизатора и нет сборки фронтенда - и это осознанное решение. Уровень представления
(см. "Основы") отделен от фреймворка и реализуется на клиенте, а бэкенд отдает данные: обычно JSON,
который получается сам, если вернуть из действия массив или DTO.

Получается привычная схема: статическая страница + API.

### Статика

Всё, что лежит в _public_, nginx отдает напрямую, минуя PHP. Остальные запросы уходят в _public/index.php_.

    public
     ┣━━ index.php       - точка входа, трогать не нужно
     ┗━━ test
          ┗━━ index.html - доступна по адресу /test/index.html

Индексный файл директории для nginx - только _index.php_, поэтому HTML страницы открываются по полному имени.
Если страница и API отдаются с одного адреса, никаких настроек CORS не требуется.

### Страница без сборки

Современному браузеру не нужны ни сборщик, ни фреймворк: `fetch`, `async/await` и DOM API работают из коробки.
Минимальная страница, которая берет данные из API, может выглядеть так:

    <p id="total"></p>
    <ul id="items"></ul>

    <script type="module">
        const { total, items } = await (await fetch('/api/v1/post')).json()
        document.querySelector('#total').textContent = `Всего: ${total}`
        for (const item of items) {
            document.querySelector('#items').append(Object.assign(document.createElement('li'), {
                textContent: `${item.name} (${item.age})`,
            }))
        }
    </script>

`textContent` вставляет данные как текст, а не как разметку, поэтому экранировать их вручную не нужно.

Так же сделан и этот сайт документации: без внешних библиотек, только HTML, немного CSS и несколько строк JS.
`make docs` собирает страницы из markdown файлов в _dev/docs/md_ в один _dev/docs/index.html_.

### HTML с сервера

Если нужна страница, сформированная на сервере, действие может вернуть строку с HTML - так устроена
админка (см. "Admin"). Для пары служебных страниц этого достаточно. Данные пользователя перед вставкой
в разметку нужно экранировать через `htmlspecialchars()`, а для чего-то большего стоит
подключить шаблонизатор - например, Twig, через composer и контейнер.

Полезные ссылки:
- [JavaScript модули](https://developer.mozilla.org/ru/docs/Web/JavaScript/Guide/Modules)
- [Fetch API](https://developer.mozilla.org/ru/docs/Web/API/Fetch_API)
