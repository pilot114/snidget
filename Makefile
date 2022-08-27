start:
	@docker-compose up
stop:
	@docker-compose stop
enter:
	@docker exec -it -u workspace wshell_workspace_1 zsh