build:
	export PUID=$(id -u $USER)
	export PGID=$(id -g $USER)
	docker-compose -f default.yml build --build-arg $PUID --build-arg $PGID
start:
	docker-compose -f default.yml up -d
stop:
	docker-compose -f default.yml stop
enter:
	docker exec -it snidget-php-1 zsh
