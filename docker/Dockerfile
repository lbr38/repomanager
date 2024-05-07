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
ARG env
ARG fqdn
ARG max_upload_size

# PACKAGES INSTALL

# Install dependencies
RUN apt-get update -y
RUN apt-get install findutils iputils-ping git gnupg2 rpm librpmsign9 createrepo-c apt-utils curl ca-certificates apt-transport-https dnsutils xz-utils bzip2 vim -y

# Install postfix
RUN apt-get install postfix -y

# Add nginx and PHP 8.1 repositories
RUN curl -sS https://packages.bespin.ovh/repo/gpgkeys/packages.bespin.ovh.pub | gpg --dearmor > /etc/apt/trusted.gpg.d/packages.bespin.ovh.gpg
RUN echo "deb https://packages.bespin.ovh/repo/repomanager-nginx/bookworm/nginx_prod bookworm nginx" > /etc/apt/sources.list.d/nginx.list
RUN echo "deb https://packages.bespin.ovh/repo/repomanager-php/bookworm/main_prod bookworm main" > /etc/apt/sources.list.d/php.list
RUN apt-get update -y

# Install nginx and PHP 8.1
RUN apt-get install nginx php8.1-fpm php8.1-cli php8.1-sqlite3 php8.1-xml php8.1-curl sqlite3 -y

# Clone project in the container
RUN git clone https://github.com/lbr38/repomanager.git /tmp/repomanager
# For tests only
RUN if [ "$env" = "devel" ]; then \
       cd /tmp/repomanager && git checkout devel; \
    fi

# SERVICES CONFIG

# Configure Nginx
RUN mkdir -p /etc/nginx/sites-enabled
RUN cp /tmp/repomanager/docker/config/nginx/nginx.conf /etc/nginx/nginx.conf
RUN cp /tmp/repomanager/docker/config/nginx/repomanager.conf /etc/nginx/sites-enabled/repomanager.conf
RUN sed -i "s/__MAX_UPLOAD_SIZE__/$max_upload_size/g" /etc/nginx/sites-enabled/repomanager.conf
RUN rm -rf /etc/nginx/sites-enabled/default /var/www/html

# Configure PHP
RUN cp /tmp/repomanager/docker/config/php/www.conf /etc/php/8.1/fpm/pool.d/www.conf
RUN sed -i "s/^upload_max_filesize.*$/upload_max_filesize = $max_upload_size/g" /etc/php/8.1/fpm/php.ini
RUN sed -i "s/^post_max_size.*$/post_max_size = $max_upload_size/g" /etc/php/8.1/fpm/php.ini

# Configure SQLite
RUN echo ".headers on" > /root/.sqliterc
RUN echo ".mode column" >> /root/.sqliterc

# Configure Postfix
RUN \cp /tmp/repomanager/docker/config/postfix/main.cf /etc/postfix/main.cf
RUN sed -i "s/__FQDN__/$fqdn/g" /etc/postfix/main.cf
RUN echo $fqdn > /etc/mailname

# Copy repomanager files
RUN mkdir -p $WWW_DIR $DATA_DIR $REPOS_DIR
RUN cp -r /tmp/repomanager/www/* $WWW_DIR/
RUN touch $WWW_DIR/.docker
RUN echo "$fqdn" > $WWW_DIR/.fqdn

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
RUN cp /tmp/repomanager/docker/entrypoint.sh /entrypoint.sh
RUN chmod 700 /entrypoint.sh

# Clean
RUN rm -rf /tmp/repomanager

# Expose port 8080
EXPOSE 8080

# Set working dir
WORKDIR ${DATA_DIR}

ENTRYPOINT ["/entrypoint.sh"]