FROM syncxplus/php:7.2.13-cli-stretch

WORKDIR /data/

COPY . ./

RUN composer install --prefer-dist --optimize-autoloader && composer clear-cache

ENTRYPOINT ["docker-php-entrypoint"]

CMD ["php", "index.php"]
