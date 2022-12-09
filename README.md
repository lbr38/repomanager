<h1>REPOMANAGER</h1>

**Repomanager** is a web mirroring tool for RPM or DEB packages repositories.

Designed for an enterprise usage and to help deployment of packages updates on large Linux servers farms, it can create mirrors of public repositories (eg. Debian or CentOS official repos or third-party editors) and manage several snapshots versions and environments.

<h2>Main features</h2>

- Create deb or rpm mirror repositories
- Sign repo with GPG
- Upload packages into repositories
- Create environments (eg. preprod, prod...) and make mirrors available only for specific envs.
- Manage hosts packages updates
- Plan tasks
- ...

![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/demo-1.gif?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-4.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-5.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-3.png?raw=true)

<h2>Requirements</h2>

<h3>OS</h3>

Repomanager runs on following systems:
- Debian 9,10
- RHEL 7/8, CentOS 7/8

**Recommended system:** Debian 10 or RHEL/CentOS 8.

<h3>Hardware</h3>

- CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled.
- Disk space depends on the size of the repos you need to clone.

<h3>Software</h3>

- Common packages (curl, gnupg2...). Repomanager will automatically install them during the installation process.
- A web service + PHP and SQLite.

**Recommended:** nginx + PHP-FPM (PHP 8.1)

<h2>Prepare installation</h2>

<h3>Nginx + PHP</h3>

You must install a web service + PHP and then configure a dedicated vhost.

**Note for Redhat/CentOS systems:** you may adapt **SELinux** configuration (or disable SELinux) to make sure it will not prevent PHP scripts execution.

**Installation on a Redhat/CentOS system** (you will need to have access to a repository providing PHP8.1 packages):

```
yum install nginx php-fpm php-cli php-pdo php-xml sqlite
```

**Installation on a Debian system** (you will need to have access to a repository providing PHP8.1 packages):

```
apt update && apt install nginx php-fpm php-cli php8.1-sqlite3 php8.1-xml php8.1-curl sqlite3
```

<h3>SQLite</h3>

Be sure that sqlite module for php is enabled.

**On a Redhat/CentOS system:**

```
vim /etc/php.d/20-sqlite3.ini

extension=sqlite3.so
```

**On a Debian system:**

```
vim /etc/php/8.1/mods-available/sqlite3.ini

extension=sqlite3.so
```

<h3>Vhost</h3>

Set up a new <b>dedicated vhost</b> for repomanager.

E.g. nginx vhost:

Adapt the following values:
 - path to php's unix socket
 - $WWW_DIR and $REPOS_DIR variables
 - server_name directive
 - ssl_certificate and ssl_certificate_key paths

```
#### Repomanager vhost ####

# Disable some logging
map $request_uri $loggable {
        /run?reload 0;
        default 1;
}

# Path to unix socket
upstream php-handler {
        server unix:/var/run/php-fpm/php-fpm.sock;
}

server {
        listen SERVER-IP:80 default_server;
        server_name repomanager.mydomain.com;

        # Path to log files
        access_log /var/log/nginx/repomanager_access.log combined if=$loggable;
        error_log /var/log/nginx/repomanager_error.log;

        # Redirect to https
        return 301 https://$server_name$request_uri;
}

server {
        # Set repomanager base directories variables
        set $WWW_DIR '/var/www/repomanager'; # default is /var/www/repomanager
        set $REPOS_DIR '/home/repo';         # default is /home/repo

        listen SERVER-IP:443 default_server ssl;
        server_name repomanager.mydomain.com;

        # Path to log files
        access_log /var/log/nginx/repomanager_ssl_access.log combined if=$loggable;
        error_log /var/log/nginx/repomanager_ssl_error.log;

        # Path to SSL certificate/key files
        ssl_certificate PATH-TO-CERTIFICATE.crt;
        ssl_certificate_key PATH-TO-PRIVATE-KEY.key;

        # Security headers
        add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;" always;
        add_header Referrer-Policy "no-referrer" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-Download-Options "noopen" always;
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Permitted-Cross-Domain-Policies "none" always;
        add_header X-Robots-Tag "none" always;
        add_header X-XSS-Protection "1; mode=block" always;

        # Remove X-Powered-By, which is an information leak
        fastcgi_hide_header X-Powered-By;

        # Set a sufficient value if you intend to upload packages into repositories from the UI
        # php.ini also needs some limits to be increased
        client_max_body_size 32M;

        # Path to repomanager root directory
        root $WWW_DIR/public;

        # Custom error pages
        error_page 404 /custom_404.html;
        error_page 500 502 503 504 /custom_50x.html;

        location = /custom_404.html {
                root $WWW_DIR/public/custom_errors;
                internal;
        }

        location = /custom_50x.html {
                root $WWW_DIR/public/custom_errors;
                internal;
        }

        location = /robots.txt {
                deny all;
                log_not_found off;
                access_log off;
        }

        # Enable gzip but do not remove ETag headers
        gzip on;
        gzip_vary on;
        gzip_comp_level 4;
        gzip_min_length 256;
        gzip_proxied expired no-cache no-store private no_last_modified no_etag auth;
        gzip_types application/atom+xml application/javascript application/json application/ld+json application/manifest+json application/rss+xml application/vnd.geo+json application/vnd.ms-fontobject application/x-font-ttf application/x-web-app-manifest+json application/xhtml+xml application/xml font/opentype image/bmp image/svg+xml image/x-icon text/cache-manifest text/css text/plain text/vcard text/vnd.rim.location.xloc text/vtt text/x-component text/x-cross-domain-policy;

        location / {
                rewrite ^ /index.php;
        }

        # API
        location /api/v2/ {
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME $WWW_DIR/public/api/v2/index.php;
                fastcgi_param HTTPS on;
                # Avoid sending the security headers twice
                fastcgi_param modHeadersAvailable true;
                fastcgi_pass php-handler;
                fastcgi_intercept_errors on;
                fastcgi_request_buffering off;
        }

        location ~ \.php$ {
                root $WWW_DIR/public;
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME $request_filename;
                #include fastcgi.conf;
                fastcgi_param HTTPS on;
                # Avoid sending the security headers twice
                fastcgi_param modHeadersAvailable true;
                fastcgi_pass php-handler;
                fastcgi_intercept_errors on;
                fastcgi_request_buffering off;
        }

        location ~ \.(?:css|js|woff2?|svg|gif|map)$ {
                try_files $uri $uri/ =404;
                add_header Cache-Control "public, max-age=15778463";
                add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;" always;
                add_header Referrer-Policy "no-referrer" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header X-Download-Options "noopen" always;
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Permitted-Cross-Domain-Policies "none" always;
                add_header X-Robots-Tag "none" always;
                add_header X-XSS-Protection "1; mode=block" always;
                access_log off;
        }

        location ~ \.(?:png|html|ttf|ico|jpg|jpeg|bcmap)$ {
                access_log off;
        }

        location /repo {
                alias $REPOS_DIR;
        }
}
```

Check configuration and reload nginx:

```
nginx -t
systemctl reload nginx
```

<h2>Installation</h2>

The **installation wizard** will need **two** paths to be specified by the user during installation:

- Main installation directory (default is /var/www/repomanager/)
- Repos directory (default is /home/repo/)

Installation script must be executed by **root** or **sudo** user to make sure that correct permissions are applied on the directories used by repomanager.

Clone the project:

```
git clone https://github.com/lbr38/repomanager.git /tmp/repomanager
```

Execute the installation wizard:

```
sudo /tmp/repomanager/repomanager --install
```

<h2>Documentation</h2>

Official documentation is available <a href="https://github.com/lbr38/repomanager/wiki/Documentation">here</a>.

It should help you starting using Repomanager.
