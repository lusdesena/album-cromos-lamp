ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-apache AS builder
RUN docker-php-ext-install mysqli
WORKDIR /src
COPY . .
RUN find . -name '*.php' -print0 | xargs -0 -n1 php -l

FROM php:${PHP_VERSION}-apache AS runtime
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini /usr/local/etc/php/conf.d/
WORKDIR /var/www/html
COPY --from=builder /src/ .

RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
  && a2enconf servername
RUN cp config.example.php config.php \
  && mkdir -p uploads \
  && chown -R www-data:www-data uploads