build:
	export PUID=$(id -u $USER)
	export PGID=$(id -g $USER)
	docker-compose -f serve.yml build --build-arg $PUID --build-arg $PGID
start:
	docker-compose -f serve.yml up -d
stop:
	docker-compose -f serve.yml stop
enter:
	docker exec -it snidget-php-1 zsh
log:
	docker-compose -f serve.yml logs -f
