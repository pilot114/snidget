FROM php:8.2-fpm-alpine3.16 as build

### deps

RUN set -ex && apk --no-cache add $PHPIZE_DEPS sudo unzip git linux-headers zlib-dev

### php modules

RUN docker-php-ext-install pcntl && docker-php-ext-enable pcntl

### php dev-modules

RUN pecl install xdebug-3.2.2 && docker-php-ext-enable xdebug

RUN git clone https://github.com/NoiseByNorthwest/php-spx.git  \
    --depth 1 --single-branch --branch master /usr/src/php/ext/spx && \
  docker-php-ext-install -j$(nproc) spx

# only builded files

FROM php:8.2-fpm-alpine3.16 as dev
COPY --from=build /usr/bin/sudo /usr/bin/sudo
COPY --from=build /usr/lib/sudo /usr/lib/sudo
COPY --from=build /usr/local /usr/local

# colored pager instead busybox-less + ssh
RUN apk --no-cache add less openssh

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

RUN sh -c "$(wget -O- https://github.com/deluan/zsh-in-docker/releases/download/v1.1.5/zsh-in-docker.sh)" -- \
    -t robbyrussell \
    -p git

COPY --from=composer/composer:2.5-bin /composer /usr/bin/composer

ADD xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
ADD spx.ini /usr/local/etc/php/conf.d/docker-php-ext-spx.ini
ADD .zshrc /home/workspace
ADD completion.zsh /home/workspace
