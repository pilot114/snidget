FROM php:8.6-fpm-alpine3.24 as build

### deps

RUN set -ex && apk --no-cache add $PHPIZE_DEPS sudo unzip git linux-headers zlib-dev oniguruma-dev

### php modules

# в образах 8.6 нет mbstring
RUN docker-php-ext-install pcntl mbstring

### php dev-modules

# в образах 8.6 нет pecl, а стабильный xdebug 3.5 не собирается под 8.6
RUN git clone https://github.com/xdebug/xdebug.git \
    --depth 1 --single-branch --branch 3.6.0alpha1 /usr/src/php/ext/xdebug && \
  docker-php-ext-install -j$(nproc) xdebug

# shortcut: spx пока не собирается под 8.6 ("supported: 5.4 to 8.5") — вернуть сборку и spx.ini, когда поддержат

# only builded files

FROM php:8.6-fpm-alpine3.24 as dev
COPY --from=build /usr/bin/sudo /usr/bin/sudo
COPY --from=build /usr/lib/sudo /usr/lib/sudo
COPY --from=build /usr/local /usr/local

# colored pager instead busybox-less + ssh; git и unzip нужны composer, oniguruma — mbstring
RUN apk --no-cache add less openssh git unzip oniguruma

### custom user ###

ARG PUID=1000
ARG PGID=1000
ENV PUID ${PUID}
ENV PGID ${PGID}

RUN adduser -G wheel -u ${PUID} workspace -D
RUN echo workspace:workspace | chpasswd
RUN adduser workspace wheel
RUN echo "%wheel ALL=(ALL:ALL) NOPASSWD: ALL" >> /etc/sudoers

USER workspace

### user-level deps

RUN sh -c "$(wget -O- https://github.com/deluan/zsh-in-docker/releases/download/v1.2.1/zsh-in-docker.sh)" -- \
    -t robbyrussell \
    -p git

COPY --from=composer/composer:2.8-bin /composer /usr/bin/composer

ADD xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
ADD .zshrc /home/workspace
ADD completion.zsh /home/workspace
