# Dockerfile (beta) for Repomanager

# Base image
FROM debian:11

# Metadata
LABEL version="1.0-beta" maintainer="lbr38 <repomanager@protonmail.com>"

# Variables
ARG WWW_DIR="/var/www/repomanager"
ARG DATA_DIR="/var/lib/repomanager"
ARG REPOS_DIR="/home/repo"

# PACKAGES INSTALL

# Install dependencies
RUN apt-get update -y
RUN apt-get install findutils git gnupg2 rpm librpmsign9 createrepo-c reprepro -y
RUN apt-get install apt-utils curl wget vim ca-certificates apt-transport-https software-properties-common -y

# Add PHP 8.1 repository
RUN curl -fsSL  https://packages.sury.org/php/apt.gpg| gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-keyring.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/sury-php.list
RUN apt-get update -y

# Webserver
RUN apt-get install nginx php8.1-fpm php8.1-cli php8.1-sqlite3 php8.1-xml php8.1-curl sqlite3 -y

# SERVICES CONFIG

# Configure Nginx
COPY config/nginx/repomanager.conf /etc/nginx/sites-enabled/repomanager.conf
RUN rm -rf /etc/nginx/sites-enabled/default /var/www/html

# Configure PHP
COPY config/php/www.conf /etc/php/8.1/fpm/pool.d/www.conf

# Clone project in the container
RUN git clone https://github.com/lbr38/repomanager.git /tmp/repomanager
# (temp) for tests
# RUN cd /tmp/repomanager && git checkout devel

# Copy repomanager files
RUN mkdir -p $WWW_DIR $DATA_DIR $REPOS_DIR
RUN cp -r /tmp/repomanager/www/* $WWW_DIR/
RUN touch $WWW_DIR/.docker

# Set basic permissions
RUN chown -R www-data:www-data $WWW_DIR $DATA_DIR $REPOS_DIR

# Initialize and update database (if needed)
RUN /bin/su -s /bin/bash -c "php $WWW_DIR/tools/initialize-database.php" www-data
RUN /bin/su -s /bin/bash -c "php $WWW_DIR/tools/update-database.php" www-data

# Expose port 8080
EXPOSE 8080

# Set working dir
WORKDIR ${DATA_DIR}

# Start services on container start
ENTRYPOINT service php8.1-fpm start && service nginx start && bash /var/www/repomanager/bin/service/repomanager-service && /bin/bash