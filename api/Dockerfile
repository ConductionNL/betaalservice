# the different stages of this Dockerfile are meant to be built into separate images

#############################
# 		"php" stage	 		#
#############################
# The base stage for all our stages

FROM conduction/pc-php:prod AS api_platform_php


# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
	composer global require "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
	composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/api

# build for production
ARG APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock symfony.lock ./

RUN set -eux; \
	composer install --prefer-dist --no-dev --no-scripts --no-progress --no-suggest; \
	composer clear-cache

# copy only specifically what we need
COPY .env ./
COPY helm helm/
COPY bin bin/
COPY config config/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.2 \
    && docker-php-ext-enable xdebug;\
    echo -e "xdebug.overload_var_dump=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync

VOLUME /srv/api/var

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]



#############################
# 		NLX Outway	 		#
#############################
# does not depend on any of the above stages, but placed here to keep everything in one Dockerfile
#FROM nlxio/outway AS api-nlx-outway
#
#COPY nlx-setup /certs/

#############################
# 		NLX Inway	 		#
#############################
# depends on the "php" stage above
#FROM nlxio/inway AS api-nlx-inway
#
#COPY nlx-setup /certs/
#COPY nlx-setup/service-config.toml.template /service-config.toml.template

# Lets install envsubst
#RUN apk --no-cache add gettext

# Lets parse the toml file
# CMD envsubst < /service-config.toml.template > /service-config.toml
