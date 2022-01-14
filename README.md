<h1>REPOMANAGER</h1>

Repomanager est un gestionnaire de repos de paquets.

Conçu pour un usage en entreprise et pour faciliter le déploiement de mises à jours sur d'importants parcs de serveurs Linux, il permet de créer facilement des miroirs de repos publics (ex: repos Debian, CentOS, ou autres éditeurs tiers) et d'en gérer plusieurs versions par environnements.

<b>Principales fonctionnalités :</b>

- Créer des miroirs de repos, les mettre à jour, les dupliquer, les rendre accessibles aux serveurs clients.
- Signer ses repos de paquets avec GPG.
- Système d'environnements (ex: preprod, prod...) permettant de rendre accessible les miroirs à des environnements de serveurs particuliers
- Planifications automatiques permettant d'exécuter les actions ci-dessus à une date/heure souhaitée.


![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager.png?raw=true)
![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager-3.png?raw=true)

<b>Ressources :</b>

Repomanager ne nécessite qu'un service web + PHP (7 minimum) et sqlite.

Le CPU et la RAM sont essentiellement sollicités pendant la création de miroirs et selon le nombre de paquets à copier et signer.
L'espace disque est à adapter en fonction du nombre de miroirs créés / nombre de paquets qu'ils contiennent.


<h1>Version beta</h1>

Installation compatible sur les systèmes Redhat/CentOS et Debian/Ubuntu :
- Debian 10, Ubuntu bionic
- CentOS 7, 8, Fedora 33

<p>Fonctionnalités actuelles de la version Beta</p>

| **Fonctions** | **Beta** |
|----------|---------------|
| Créer des miroirs à partir de repos publics | ✅ |
| Créer des repos locaux | ✅ |
| Mettre à jour des miroirs | ✅ |
| Signer ses repos avec GPG | ✅ |
| Archiver / restaurer des repos | ✅ |
| Charger des patchs zero-day | ✅ |
| **Automatisation** | **Beta** |
| Planifier la mise à jour de miroirs | ✅ |
| Rappels de planifications (mail) | ✅ |
| **Statistiques** | **Beta** |
| Graphiques sur l'utilisation et l'évolution des repos | ✅ |


<b>Dépendances</b>

Pour fonctionner repomanager requiert la présence de certains logiciels couramment installés sur les distributions Linux, tels que :
<pre>
rsync, curl, wget, gnupg2
</pre>

Ainsi que certains logiciels spécifiques nécessaires pour créer des miroirs de repo tels que :
<pre>
yum-utils et createrepo (CentOS/Redhat)
rpmresign (module perl RPM4) pour la signature des repos (CentOS/Redhat)
debmirror (Debian)
</pre>

Repomanager installera lui même ces dépendances s'il détecte qu'elles ne sont pas présentes sur le système. Veillez donc à ce que le serveur ait au moins accès aux repositorys de base de son OS.

Note pour les systèmes Redhat/CentOS : adapter la configuration de SELinux et faire en sorte qu'il n'empêche pas la bonne exécution de PHP.


<h2>Installation</h2>

<b>Serveur web + PHP</b>

Repomanager s'administre depuis une interface web. Il faut donc installer un service web+php et configurer un vhost dédié.

Repomanager n'est testé qu'avec nginx+php-fpm (PHP 7.x) mais une compatibilité avec apache n'est pas exclue.

<pre>
# Redhat / CentOS
yum install nginx php-fpm php-cli php-pdo sqlite

# Debian
apt update && apt install nginx php-fpm php-cli php7.4-sqlite3 sqlite3
</pre>

<b>SQLite</b>

S'assurer que l'extension sqlite pour php est activée (généralement dans /etc/php.d/) :

<pre>
# Debian
vim /etc/php/7.4/mods-available/sqlite3.ini

# Redhat/CentOS
vim /etc/php.d/20-sqlite3.ini

extension=sqlite3.so
</pre>

<b>Vhost</b>

Exemple de vhost pour nginx.

Adapter les valeurs :
 - du chemin vers le socket unix php
 - des deux variables $WWW_DIR et $REPOS_DIR
 - des directives server_name, access_log, error_log, ssl_certificate, ssl_certificate_key

<pre>
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
        root $WWW_DIR;

        # Custom error pages
        error_page 404 /custom_404.html;
        error_page 500 502 503 504 /custom_50x.html;

        location = /custom_404.html {
                root $WWW_DIR/custom_errors;
                internal;
        }

        location = /custom_50x.html {
                root $WWW_DIR/custom_errors;
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
                try_files $uri $uri/ =404;
                index index.php;
        }

        location ~ \.php$ {
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
                try_files $uri /index.php$request_uri;
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

        location = /main.conf {
                root $REPOS_DIR/profiles/_reposerver;
                allow all;
        }

        location /repo {
                alias $REPOS_DIR;
        }

        location /profiles {
                root $REPOS_DIR;
                allow all;
                autoindex on;
        }
}
</pre>


<b>Repomanager</b>

Le programme nécessite 2 répertoires choisis par l'utilisateur au moment de l'installation :
<pre>
Répertoire d'installation (par défaut /var/www/repomanager/)
Répertoire de stockage des miroirs de repos (par défaut /home/repo/)
</pre>

L'installation doit s'effectuer en tant que root ou sudo afin que les bonnes permissions soient correctement établies sur les répertoires utilisés par repomanager.

Télécharger la dernière release disponible au format .tar.gz. Toutes les releases sont visibles ici : https://github.com/lbr38/repomanager/releases

<pre>
RELEASE="v2.5.1-beta" # choix de la release
cd /tmp
wget https://github.com/lbr38/repomanager/releases/download/$RELEASE/repomanager_$RELEASE.tar.gz
tar xzf repomanager_$RELEASE.tar.gz
cd /tmp/repomanager/
</pre>

Lancer l'installation de repomanager :
<pre>
chmod 700 repomanager
sudo ./repomanager --install
</pre>