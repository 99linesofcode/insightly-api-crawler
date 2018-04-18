include .env

clean:
	rm -rf data/db/mysql/*
	rm -rf back/vendor
	rm -rf back/composer.lock

docker-start:
	docker-compose up -d

docker-stop:
	docker-compose down -v
	@make clean

composer-update:
	docker run --rm -v $(shell pwd)/back/app:/app composer update

composer-dump:
	docker run --rm -v $(shell pwd)/back/app:/app composer dump-autoload

test:
	docker-compose exec -T php ./app/vendor/bin/phpunit \
		--verbose \
		--colors=always \
		--bootstrap ./app/vendor/autoload.php \
		./app/tests/
