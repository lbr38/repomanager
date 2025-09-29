# Dockerfile for Repomanager

# Base image
FROM debian:12-slim

# Metadata
LABEL version="1.2" maintainer="lbr38 <repomanager@protonmail.com>"

# Variables
ARG WWW_DIR="/var/www/repomanager"
ARG DATA_DIR="/var/lib/repomanager"
ARG REPOS_DIR="/home/repo"
ARG DEBIAN_FRONTEND=noninteractive
ARG branch=main

# PACKAGES INSTALL

# Install dependencies
RUN apt-get update -y
RUN apt-get install findutils iputils-ping git gnupg2 rpm librpmsign9 createrepo-c apt-utils curl ca-certificates apt-transport-https dnsutils xz-utils bzip2 zstd vim python3-psutil -y

# Install postfix
RUN apt-get install postfix -y

# Add nginx and PHP 8.3 repositories
RUN curl -sS https://packages.repomanager.net/repo/gpgkeys/packages.repomanager.net.pub | gpg --dearmor > /etc/apt/trusted.gpg.d/packages.repomanager.net.gpg
RUN echo "deb https://packages.repomanager.net/repo/deb/repomanager-nginx/bookworm/nginx/prod bookworm nginx" > /etc/apt/sources.list.d/nginx.list
RUN echo "deb https://packages.repomanager.net/repo/deb/repomanager-php/bookworm/main/prod bookworm main" > /etc/apt/sources.list.d/php.list
RUN apt-get update -y

# Install nginx and PHP 8.3
RUN apt-get install nginx php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-xml php8.3-curl php8.3-yaml php8.3-opcache sqlite3 -y

# Install xdebug if branch is devel
RUN if [ "$branch" = "devel" ]; then apt-get install php8.3-xdebug -y; fi

# Clone project in the container
RUN git clone https://github.com/lbr38/repomanager.git /tmp/repomanager
RUN cd /tmp/repomanager && git checkout $branch

# SERVICES CONFIG

# Configure Nginx
RUN mkdir -p /etc/nginx/sites-enabled
RUN cp /tmp/repomanager/docker/config/nginx/nginx.conf /etc/nginx/nginx.conf
RUN cp /tmp/repomanager/docker/config/nginx/repomanager.conf /etc/nginx/sites-enabled/repomanager.conf
RUN rm -rf /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf /var/www/html

# Configure PHP
RUN cp /tmp/repomanager/docker/config/php/php.ini /etc/php/8.3/fpm/php.ini
RUN cp /tmp/repomanager/docker/config/php/www.conf /etc/php/8.3/fpm/pool.d/www.conf
RUN cp /tmp/repomanager/docker/config/php/opcache.ini /etc/php/8.3/mods-available/opcache.ini
RUN if [ "$branch" = "devel" ]; then cp /tmp/repomanager/docker/config/php/xdebug.ini /etc/php/8.3/mods-available/xdebug.ini; fi

# Configure SQLite
RUN echo ".headers on" > /root/.sqliterc
RUN echo ".mode column" >> /root/.sqliterc

# Configure Postfix
RUN \cp /tmp/repomanager/docker/config/postfix/main.cf /etc/postfix/main.cf
# Copy master.cf with custom listening port 2525 (to avoid conflict with other mail services on the host) (when networking=host)
RUN \cp /tmp/repomanager/docker/config/postfix/master.cf /etc/postfix/master.cf

# Copy repomanager files
RUN mkdir -p $WWW_DIR $DATA_DIR $REPOS_DIR
RUN cp -r /tmp/repomanager/www/* $WWW_DIR/

# Create repomanager group and set basic permissions
RUN groupadd repomanager
RUN usermod -a -G repomanager www-data
RUN chown -R www-data:repomanager $WWW_DIR $DATA_DIR $REPOS_DIR

# Some basic configurations
RUN sed -i 's/# alias ll=/alias ll=/g' /root/.bashrc
RUN echo "set ic" > /root/.vimrc
RUN echo "set mouse-=a" >> /root/.vimrc
RUN echo "syntax on" >> /root/.vimrc
RUN echo "set background=dark" >> /root/.vimrc

# Copy entrypoint script
RUN cp /tmp/repomanager/docker/init /init
RUN chmod 700 /init

# Clean
RUN rm -rf /tmp/repomanager

# Expose port 8080
EXPOSE 8080

# Set working dir
WORKDIR ${DATA_DIR}

ENTRYPOINT ["/init"]