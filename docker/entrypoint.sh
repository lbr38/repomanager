#!/bin/bash

WWW_DIR="/var/www/repomanager"
DATA_DIR="/var/lib/repomanager"

/bin/bash $WWW_DIR/bin/repomanager -p &

# Docker run options
# when
# -e FQDN=server.example.com
# -e MAX_UPLOAD_SIZE=xxM
# are set, the following settings are changed:
if [ ! -z "$FQDN" ];then
    # Postfix/mail configuration
    postconf -e "myhostname = $FQDN"
    echo $FQDN > /etc/mailname

    # Repomanager configuration
    echo $FQDN > "$WWW_DIR/.fqdn"
fi

if [ ! -z "$MAX_UPLOAD_SIZE" ];then
    # Nginx configuration
    sed -i "s/client_max_body_size.*$/client_max_body_size ${MAX_UPLOAD_SIZE};/g" /etc/nginx/sites-enabled/repomanager.conf
    # PHP configuration
    sed -i "s/^upload_max_filesize.*$/upload_max_filesize = ${MAX_UPLOAD_SIZE}/g" /etc/php/8.1/fpm/php.ini
    sed -i "s/^post_max_size.*$/post_max_size = ${MAX_UPLOAD_SIZE}/g" /etc/php/8.1/fpm/php.ini
fi

# Start services
if [ -f "/etc/init.d/syslog-ng" ];then
    /usr/sbin/service syslog-ng start
fi
/usr/sbin/service php8.1-fpm start
/usr/sbin/service nginx start
/usr/sbin/service postfix start

# Initialize and update database (if needed)
/bin/su -s /bin/bash -c "/usr/bin/php $WWW_DIR/tools/initialize-database.php" www-data
/bin/su -s /bin/bash -c "/usr/bin/php $WWW_DIR/tools/update-database.php" www-data

# Clear repos list cache
if [ -d "$DATA_DIR/cache/" ]; then
    /bin/rm "$DATA_DIR/cache/"* -f
fi

# Start repomanager service
/bin/su -s /bin/bash -c "/usr/bin/php $WWW_DIR/tools/service.php" www-data

/bin/bash