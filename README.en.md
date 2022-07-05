**ENGLISH VERSION**

:warning: **Warning : there is no english version for the web UI which is actually in french.** :warning:

:warning: **Code is actually in english (functions, class names, variables...) but the comments remains in french.** :warning:

:warning: **Help for traduction is much appreciated!** :warning:


<h1>REPOMANAGER</h1>

Repomanager is a web mirroring tool for .rpm or .deb packages repository, based on reposync (Redhat/CentOS) & debmirror (Debian).

Designed for an enterprise usage and to help deployment of packages updates on large Linux servers farms, it can create mirrors of public repos (eg. Debian or CentOS official repos or third-party editors) and manage several versions and environments.

<b>Main features:</b>

- Create mirrors, update them, duplicate them
- Sign packages/mirror with GPG
- Create environments (eg. preprod, prod...) and make your mirrors availables only for specific envs.
- Automatic tasks plans

![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-4.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-5.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-3.png?raw=true)

<b>Features</b>

| **Functions** ||
|----------|---------------|
| Create mirrors from public repos | ✅ |
| Create local repos | ✅ |
| Sign repos or packages with GPG key | ✅ |
| Archive / restore mirrors | ✅ |
| Load custom packages into repos (eg: patch zero-day) | ✅ |
| **Automatisation** ||
| Create automatic tasks on mirrors (update mirror...) | ✅ |
| Send automatic task reminder (mail) | ✅ |
| **Stats** ||
| Visualize graphs on repos' evolution and utilisation | ✅ |
| **Hosts management** ||
| Analyze et manage installed packages on clients hosts (linupdate agent needed) | ✅ |
| **General** ||
| Create users (ro-user or admin) | ✅ |
| See history of actions taken by users | ✅ |
| Automatic or manual update of repomanager | ✅ |


<h2>Requirements</h2>

Runs on following Redhat/CentOS or Debian/Ubuntu systems:
- Debian 9,10, Ubuntu bionic
- RHEL 7/8, CentOS 7/8, Fedora 33
Minimal recommanded configuration: Debian 10 or RHEL/CentOS 8.

Repomanager only needs a web service + PHP (7.x or 8.x) and SQLite.

CPU and RAM are mostly sollicited during mirror creation if GPG signature is enabled.
Disk space required depends on the size of the repos you need to clone.

<b>Dependencies</b>

Repomanager requires packages commonly found on every Linux distributions such as:
```
rsync, curl, wget, gnupg2
```

And specific packages needed to build mirrors such as:
```
yum-utils and createrepo (CentOS/Redhat)
rpmresign (perl RPM4 module) to sign repos (CentOS/Redhat)
debmirror (Debian)
```

Repomanager will automatically install those dependencies if there are not present. Please check that the server has at least access to its OS base repositories to be able to install those deps.


<h2>Installation</h2>

<b>Web service + PHP</b>

You must install a web service + PHP then configure a dedicated vhost.

Repomanager has been only tested with nginx+php-fpm (PHP 7.x) but an apache/httpd compatibility is not excluded.

Note for Redhat/CentOS systems: you may adapt SELinux configuration to make sure it will not prevent PHP execution.

```
# Redhat / CentOS
yum install nginx php-fpm php-cli php-pdo php-json sqlite # PHP 7.4
yum install nginx php-fpm php-cli php-pdo sqlite # PHP 8.1

# Debian
apt update && apt install nginx php-fpm php-cli php7.4-json php7.4-sqlite3 sqlite3 # PHP 7.4
apt update && apt install nginx php-fpm php-cli php8.1-sqlite3 sqlite3 # PHP 8.1
```

<b>SQLite</b>

Be sure that sqlite module for php is enabled:

```
# Debian
vim /etc/php/7.4/mods-available/sqlite3.ini

# Redhat/CentOS
vim /etc/php.d/20-sqlite3.ini

extension=sqlite3.so
```

<b>Vhost</b>

eg. vhost for nginx below.

Adapt the following values:
 - path to php's unix socket
 - $WWW_DIR and $REPOS_DIR variables
 - server_name, access_log, error_log, ssl_certificate and ssl_certificate_key directives

```
#### Repomanager vhost ####

# Disable some logging
map $request_uri $loggable {
        /run.php?reload 0;
        default 1;
}

# Path to unix socket
upstream php-handler {
        server unix:/var/run/php-fpm/php-fpm.sock;
}

server {
        listen SERVER-IP:80 default_server;
        server_name SERVERNAME.MYDOMAIN.COM;

        # Path to log files
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_access.log combined if=$loggable;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_error.log;

        # Redirect to https
        return 301 https://$server_name$request_uri;
}

server {
        # Set repomanager base directories variables
        set $WWW_DIR '/var/www/repomanager'; # default is /var/www/repomanager
        set $REPOS_DIR '/home/repo';         # default is /home/repo

        listen SERVER-IP:443 default_server ssl;
        server_name SERVERNAME.MYDOMAIN.COM;

        # Path to log files
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_access.log combined if=$loggable;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_error.log;

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
        location ~ ^/api/hosts$ {
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME $WWW_DIR/public/api/hosts/index.php;
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


<b>Repomanager</b>

The program will need two directories chosen by the user during installation:
```
Main installation directory (default is /var/www/repomanager/)
Repos directory (default is /home/repo/)
```

Installation script must be executed by root or sudo user to make sure that correct permissions are applied on the directories used by repomanager.

Download last available release (.tar.gz) (all releases are visible here: https://github.com/lbr38/repomanager/releases):

```
RELEASE="v3.0.2-stable" # release choice
cd /tmp
wget https://github.com/lbr38/repomanager/releases/download/$RELEASE/repomanager_$RELEASE.tar.gz
tar xzf repomanager_$RELEASE.tar.gz
cd /tmp/repomanager/
```

Proceed the installation:
```
chmod 700 repomanager
sudo ./repomanager --install
```