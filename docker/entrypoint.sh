#!/bin/bash

WWW_DIR="/var/www/repomanager"
DATA_DIR="/var/lib/repomanager"

/bin/bash $WWW_DIR/bin/repomanager -p &

# Start services
/usr/sbin/service php8.1-fpm start
/usr/sbin/service nginx start
/usr/sbin/service postfix start

# Initialize and update database (if needed)
/bin/su -s /bin/bash -c "php $WWW_DIR/tools/initialize-database.php" www-data
/bin/su -s /bin/bash -c "php $WWW_DIR/tools/update-database.php" www-data

# Clear repos list cache
if [ -d "$DATA_DIR/cache/" ]; then
    /bin/rm "$DATA_DIR/cache/"* -f
fi

# Start repomanager service
/bin/su -s /bin/bash -c "php $WWW_DIR/tools/service.php" www-data

/bin/bash