# Dockerfile for Repomanager

# Base image
FROM debian:12-slim AS base

# Metadata
LABEL version="1.2" maintainer="lbr38 <repomanager@protonmail.com>"

# Variables
ARG WWW_DIR="/var/www/repomanager"
ARG DATA_DIR="/var/lib/repomanager"
ARG REPOS_DIR="/home/repo"
ARG PHP_VERSION="8.3"
ARG DEBIAN_FRONTEND=noninteractive
ARG branch=main

# Export branch as environment variable for /init script
ENV BRANCH=${branch}

# PACKAGES INSTALL
# Add nginx and PHP 8.3 repositories
ADD https://packages.repomanager.net/repo/gpgkeys/packages.repomanager.net.pub /tmp/packages.repomanager.net.gpg
RUN apt-get update -y -qq && apt-get install -y -qq gnupg2 ca-certificates && rm -rf /var/lib/apt/lists/* && \
    cat /tmp/packages.repomanager.net.gpg | gpg --dearmor > /etc/apt/trusted.gpg.d/packages.repomanager.net.gpg && rm -f /tmp/packages.repomanager.net.gpg
RUN echo "deb https://packages.repomanager.net/repo/deb/repomanager-nginx/bookworm/nginx/prod bookworm nginx" > /etc/apt/sources.list.d/nginx.list && \
    echo "deb https://packages.repomanager.net/repo/deb/repomanager-php/bookworm/main/prod bookworm main" > /etc/apt/sources.list.d/php.list

# Install dependencies
RUN apt-get update -y -qq && \
    apt-get install -y -qq findutils iputils-ping git rpm librpmsign9 createrepo-c apt-utils curl apt-transport-https dnsutils xz-utils bzip2 zstd vim python3-psutil \
    # Install postfix
    postfix \
    # Install nginx and PHP 8.3
    nginx php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-yaml php${PHP_VERSION}-opcache sqlite3 \
    # Install xdebug if branch is devel
    $(if [ "$branch" = "devel" ]; then echo "php${PHP_VERSION}-xdebug"; fi) && \
    apt-get -qq autoremove -y && rm -rf /var/lib/apt/lists/*

FROM base AS run

ARG WWW_DIR="/var/www/repomanager"
ARG DATA_DIR="/var/lib/repomanager"
ARG REPOS_DIR="/home/repo"
ARG branch=main

# SERVICES CONFIG

# Configure Nginx
RUN rm -rf /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf /var/www/html
COPY docker/config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/config/nginx/repomanager.conf /etc/nginx/sites-enabled/repomanager.conf

# Configure PHP
COPY docker/config/php/php.ini /etc/php/${PHP_VERSION}/fpm/php.ini
COPY docker/config/php/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
COPY docker/config/php/opcache.ini /etc/php/${PHP_VERSION}/mods-available/opcache.ini
COPY docker/config/php/xdebug.ini /tmp/xdebug.ini
RUN if [ "$branch" = "devel" ]; then cp /tmp/xdebug.ini /etc/php/${PHP_VERSION}/mods-available/xdebug.ini; else rm /tmp/xdebug.ini; fi

# Configure SQLite
RUN echo ".headers on" > /root/.sqliterc && \
    echo ".mode column" >> /root/.sqliterc

# Configure Postfix
COPY docker/config/postfix/main.cf /etc/postfix/main.cf
# Copy master.cf with custom listening port 2525 (to avoid conflict with other mail services on the host) (when networking=host)
COPY docker/config/postfix/master.cf /etc/postfix/master.cf

RUN mkdir -p $WWW_DIR $DATA_DIR $REPOS_DIR && \
    # Create repomanager group and set repomanager as default group for www-data user
    groupadd repomanager && \
    usermod -G repomanager -a www-data

# Copy repomanager files
COPY www/ $WWW_DIR/

# Some basic configurations
RUN sed -i 's/# alias ll=/alias ll=/g' /root/.bashrc && \
    echo "alias repomanager-execute=\"su -s /bin/bash -c '/usr/bin/php /var/www/repomanager/tasks/execute.php' www-data\"" >> /root/.bashrc && \
    echo "set ic" > /root/.vimrc && \
    echo "set mouse-=a" >> /root/.vimrc && \
    echo "syntax on" >> /root/.vimrc && \
    echo "set background=dark" >> /root/.vimrc

# Copy entrypoint script
COPY docker/init /init
RUN chmod 700 /init

# Expose port 8080
EXPOSE 8080

# Set working dir
WORKDIR ${DATA_DIR}

ENTRYPOINT ["/init"]
