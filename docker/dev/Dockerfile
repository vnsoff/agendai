# Development image: PHP 8.4 CLI + Composer + Node, running as the host user
# so files created inside the container stay owned by you.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql intl zip pcntl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

ARG UID=1000
ARG GID=1000
RUN groupadd --gid ${GID} dev && useradd --uid ${UID} --gid ${GID} --create-home dev

USER dev
WORKDIR /app
