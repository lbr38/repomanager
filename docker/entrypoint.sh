#!/bin/bash

WWW_DIR="/var/www/repomanager"

# Start services
service php8.1-fpm start
service nginx start

# Initialize and update database (if needed)
/bin/su -s /bin/bash -c "php $WWW_DIR/tools/initialize-database.php" www-data
/bin/su -s /bin/bash -c "php $WWW_DIR/tools/update-database.php" www-data

# Start repomanager service
bash $WWW_DIR/bin/service/repomanager-service

# /bin/bash