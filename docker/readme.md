https://www.youtube.com/watch?v=6ZwLi3vKbcw

# Dockerfile

Указывать максимально точную версию образа, но без patch версии в semver

    FROM php:7.4-cli-alpine3.13

Слои должны идти в порядке от редких к частым изменениям

Установка через пакетные менеджеры должна выполняться раньше, чем монтирование
файлов приложения, т.к. иначе при пересборке будет лишнее подтягивание зависимостей.

Всегда нужно создавать не root пользователя (например uid:gid 10001:10001).
Прокидывать рекомендуется из основной системы: $(id -r) $(id -g)

Можно использовать multi stage, чтобы подтянуть только нужное из других образов (COPY --from)
(например, go бинарник или собрать статику и положить в nginx образ)
Можно так вытаскивать файлы из уже готовых образов!

COPY --link позволяет избежать ненужной инвалидации кэша слоев
https://docs.docker.com/engine/reference/builder/#copy---link

target позволяет из одного dockerfile билдить разные образы (наследование + композиция)

    docker build --target=app_php .
    docker build --target=app_php_dev .

dive - утилита для просмотра образа

RUN --mount=type=secret     позволяет монтировать файлы как "секреты" (нет в ФС и истории)
RUN --mount=type=cache      кэширование, например, для пакетных менеджеров
RUN --mount=type=ssh        удобное и безопасное монтирование ssh ключей

Очистка от лишних файлов работает не всегда очевидно. Надо перепроверять через dive

По entrypoint/cmd:
Образ должен быть health сразу после старта, поэтому все по возможности выносим в build

https://github.com/dunglas/symfony-docker/blob/main/Dockerfile
https://github.com/tarampampam/laravel-roadrunner-in-docker/blob/master/Dockerfile

# docker-compose

в .env можно вынести некоторые настройки docker-compose:
    
    # мерж и подключеник нескольких docker-compose
    COMPOSE_FILE = a.yaml:b.yaml
    # префикс имен контейнеров
    COMPOSE_PROJECT_NAME = app

Чтобы одни контейнеры дожидались других на основе healthcheck:

    depends_on:
        postgres: {condition: service_healthy}
        redis: {condition: service_healthy}

docker-compose config позволяет продебажить итоговый конфиг

В портах можно указать "-" чтобы задать значение по умолчанию, если не указан env
и "?" чтобы его затребовать, если он не указан

В .env можно выносить практически все, вплоть до имен образов и тегов. Сами .env тоже можно
разделять на отдельные файлы и подключать через env_file

XDEBUG_MODE - env для xdebug

# other

.dockerignore - чтобы не копировать лишний контекст
логи - надо писать в stderr
mailhog - фейковый smtp сервер для тестирования отправки писем
генерация pdf - https://github.com/gotenberg/gotenberg
mkcert - сертификаты
hadolint,dockle,trivy - linters fro Dockerfile
traefik - решает проблему с запуском нескольких сервисов на одинаковых портах (альтенативы?)
Makefile - отличный инструмент
