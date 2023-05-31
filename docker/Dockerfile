# Dockerfile for Repomanager

# Base image
FROM debian:11

# Metadata
LABEL version="1.2" maintainer="lbr38 <repomanager@protonmail.com>"

# Variables
ARG WWW_DIR="/var/www/repomanager"
ARG DATA_DIR="/var/lib/repomanager"
ARG REPOS_DIR="/home/repo"
ARG DEBIAN_FRONTEND=noninteractive
ARG fqdn
ARG max_upload_size

# PACKAGES INSTALL

# Install dependencies
RUN apt-get update -y
RUN apt-get install findutils git gnupg2 rpm librpmsign9 createrepo-c reprepro apt-utils curl ca-certificates apt-transport-https dnsutils xz-utils -y

# Install postfix
RUN apt-get install postfix -y

# Add PHP 8.1 repository
RUN curl -fsSL  https://packages.sury.org/php/apt.gpg| gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-keyring.gpg
RUN echo "deb https://packages.sury.org/php/ bullseye main" | tee /etc/apt/sources.list.d/sury-php.list
RUN apt-get update -y

# Install nginx and PHP 8.1
RUN apt-get install nginx php8.1-fpm php8.1-cli php8.1-sqlite3 php8.1-xml php8.1-curl sqlite3 -y

# Clone project in the container
RUN git clone https://github.com/lbr38/repomanager.git /tmp/repomanager
# For tests only
# RUN cd /tmp/repomanager && git checkout devel

# SERVICES CONFIG

# Configure Nginx
RUN cp /tmp/repomanager/docker/config/nginx/nginx.conf /etc/nginx/nginx.conf
RUN cp /tmp/repomanager/docker/config/nginx/repomanager.conf /etc/nginx/sites-enabled/repomanager.conf
RUN sed -i "s/__MAX_UPLOAD_SIZE__/$max_upload_size/g" /etc/nginx/sites-enabled/repomanager.conf
RUN rm -rf /etc/nginx/sites-enabled/default /var/www/html

# Configure PHP
RUN cp /tmp/repomanager/docker/config/php/www.conf /etc/php/8.1/fpm/pool.d/www.conf
RUN sed -i "s/^upload_max_filesize.*$/upload_max_filesize = $max_upload_size/g" /etc/php/8.1/fpm/php.ini

# Configure SQLite
RUN echo -e ".headers on\n.mode column" > /root/.sqliterc

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

# Copy entrypoint script
RUN cp /tmp/repomanager/docker/entrypoint.sh /tmp/entrypoint.sh
RUN chmod 700 /tmp/entrypoint.sh

# Clean
RUN rm -rf /tmp/repomanager

# Expose port 8080
EXPOSE 8080

# Set working dir
WORKDIR ${DATA_DIR}

ENTRYPOINT ["/tmp/entrypoint.sh"]