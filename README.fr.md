[[English version]](README.en.md)

<h1>REPOMANAGER</h1>

Repomanager est un gestionnaire de repos de paquets.

Conçu pour un usage en entreprise et pour faciliter le déploiement de mises à jours sur d'importants parcs de serveurs Linux, il permet de créer facilement des miroirs de repos publics (ex: repos Debian, CentOS, ou autres éditeurs tiers) et d'en gérer plusieurs versions par environnements.

<b>Principales fonctionnalités :</b>

- Créer des miroirs de repos, les mettre à jour, les dupliquer
- Signer ses repos de paquets avec GPG
- Système d'environnements (ex: preprod, prod...) permettant de rendre accessible les miroirs à des environnements de serveurs particuliers
- Planifications automatiques permettant d'exécuter les actions ci-dessus à une date/heure souhaitée.

![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-4.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-5.png?raw=true)
![alt text](https://github.com/lbr38/resources/blob/main/screenshots/repomanager/repomanager-3.png?raw=true)


<b>Fonctionnalités</b>

| **Fonctionnalités** ||
|----------|---------------|
| Créer des miroirs de repos publics | ✅ |
| Créer des repos locaux | ✅ |
| Signer les repos / les paquets avec GPG | ✅ |
| Charger des paquets dans des repos (ex: patchs zero-day) | ✅ |
| **Automatisation** ||
| Créer des tâches planifiées pour mettre à jour les miroirs | ✅ |
| Rappels de planifications (mail) | ✅ |
| **Statistiques** ||
| Métriques sur l'utilisation et l'évolution des repos | ✅ |
| **Gestion du parc** | |
| Analyser et gérer les paquets installés sur un parc de serveurs "clients" | ✅ |
| **Général** ||
| Création d'utilisateurs (administrateurs ou "lecture-seule") | ✅ |
| Historique des actions effectuées par utilisateur | ✅ |
| Mise à jour automatique ou manuelle de repomanager | ✅ |


<h2>Ressources</h2>

Installation compatible sur les systèmes Redhat/CentOS et Debian/Ubuntu :
- Debian 9,10, Ubuntu bionic
- RHEL 7/8, CentOS 7/8, CentOS Stream, Rocky Linux, Fedora 33
Configuration minimale recommandé : Debian 10 ou RHEL/CentOS 8.

Repomanager ne nécessite qu'un service web + PHP (7.x ou 8.x) et SQLite.

Le CPU et la RAM sont essentiellement sollicités lors de la création de miroirs et si la signature avec GPG est activée.
L'espace disque est à adapter en fonction de la taille des repos distants à cloner.


<b>Dépendances</b>

Pour fonctionner repomanager requiert la présence de certains logiciels couramment installés sur les distributions Linux, tels que :
```
curl, mlocate, wget, gnupg2
```

Ainsi que certains logiciels spécifiques nécessaires pour créer des miroirs de repo tels que :
```
- yum-utils et createrepo (RPM)
- rpmresign (module perl RPM4) pour la signature des repos (RPM)
- debmirror (DEB)
```

Repomanager installera automatiquement ces dépendances. Veillez donc à ce que le serveur ait au moins accès aux dépôts de base de son OS.


<h2>Installation</h2>

<b>Serveur web + PHP</b>

Repomanager s'administre depuis une interface web. Il faut donc installer un service web+php et configurer un vhost dédié.

Repomanager n'est testé qu'avec nginx+php-fpm (PHP 7.x/8.x) mais une compatibilité avec apache n'est pas exclue.

Note pour les systèmes Redhat/CentOS : adapter la configuration de SELinux et faire en sorte qu'il n'empêche pas la bonne exécution de PHP.

```
# Redhat / CentOS
yum install nginx php-fpm php-cli php-pdo php-json sqlite # PHP 7.4
yum install nginx php-fpm php-cli php-pdo sqlite # PHP 8.1

# Debian
apt update && apt install nginx php-fpm php-cli php7.4-json php7.4-sqlite3 sqlite3 # PHP 7.4
apt update && apt install nginx php-fpm php-cli php8.1-sqlite3 sqlite3 # PHP 8.1
```

<b>SQLite</b>

S'assurer que le module sqlite pour php est activée :

```
# Debian
vim /etc/php/7.4/mods-available/sqlite3.ini

# Redhat/CentOS
vim /etc/php.d/20-sqlite3.ini

extension=sqlite3.so
```

<b>Vhost</b>

Exemple de vhost pour nginx.

Adapter les valeurs :
 - du chemin vers le socket unix php
 - des deux variables $WWW_DIR et $REPOS_DIR
 - des directives server_name, access_log, error_log, ssl_certificate, ssl_certificate_key

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

Le programme nécessite 2 répertoires choisis par l'utilisateur au moment de l'installation :
```
Répertoire d'installation (par défaut /var/www/repomanager/)
Répertoire de stockage des miroirs de repos (par défaut /home/repo/)
```

L'installation doit s'effectuer en tant que root ou sudo afin que les bonnes permissions soient correctement établies sur les répertoires utilisés par repomanager.

Cloner le projet :

```
cd /tmp
git clone https://github.com/lbr38/repomanager.git
cd /tmp/repomanager/
```

Lancer l'installation de repomanager :
```
sudo ./repomanager --install
```

<h1>Linupdate et Repomanager</h1>

<b>linupdate</b> est un utilitaire de mise à jour de paquets systèmes pour les hôtes basés sur Debian ou Redhat/CentOS, et qui possède un module capable de communiquer avec Repomanager au travers de son api.

Sur Repomanager, l'onglet 'Gestion des hôtes' et 'Gestion des profils' permet de gérer et de configurer des serveurs/hôtes Linux exécutant linupdate et d'avoir une visualisation globale de l'état de leurs paquets systèmes.

Voir le projet [[linupdate]](https://github.com/lbr38/linupdate) pour plus d'informations.