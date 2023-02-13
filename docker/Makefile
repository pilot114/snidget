serve:
	export PUID=$(id -u $USER)
	export PGID=$(id -g $USER)
	docker-compose -f serve.yml build --build-arg $PUID --build-arg $PGID && docker-compose -f serve.yml up
aserve:
	export PUID=$(id -u $USER)
	export PGID=$(id -g $USER)
	docker-compose -f aserve.yml build --build-arg $PUID --build-arg $PGID && docker-compose -f aserve.yml up
enter:
	docker exec -it snidget-php-1 zsh