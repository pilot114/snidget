FROM php:8.2-fpm-alpine3.16
RUN docker-php-ext-install pcntl
RUN set -ex && apk --no-cache add sudo

# set custom user

ARG PUID=1000
ARG PGID=1000
ENV PUID ${PUID}
ENV PGID ${PGID}

RUN adduser -G wheel -u ${PUID} workspace -D
RUN echo workspace:workspace | chpasswd
RUN adduser workspace wheel
RUN echo "%wheel ALL=(ALL:ALL) NOPASSWD: ALL" >> /etc/sudoers

USER workspace

# dev

RUN sh -c "$(wget -O- https://github.com/deluan/zsh-in-docker/releases/download/v1.1.5/zsh-in-docker.sh)" -- \
    -t robbyrussell \
    -p git

RUN echo 'eval "$(/app/console/index.php completion zsh)"' >> ~/.zshrc
