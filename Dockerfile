ARG PHP_VERSION=8.5

# builder and runtime start from the same image, so the multi-stage build does
# not reduce the size (the php:*-apache toolchain is in both). It is kept to
# separate the syntax gate (php -l) from the final image and to be able to change
# the runtime base later without rewriting the Dockerfile. Both stages must use
# the same PHP_VERSION: the extensions directory carries the API number.
FROM php:${PHP_VERSION}-apache AS builder
RUN docker-php-ext-install mysqli
WORKDIR /src
COPY . .
RUN find . -name '*.php' -print0 | xargs -0 -n1 php -l

FROM php:${PHP_VERSION}-apache AS runtime
LABEL org.opencontainers.image.source="https://github.com/lusdesena/album-cromos-lamp"
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini /usr/local/etc/php/conf.d/
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
  && a2enconf servername

WORKDIR /var/www/html
COPY --from=builder /src/ .

RUN cp config.example.php config.php \
  && mkdir -p uploads \
  && chown -R www-data:www-data uploads