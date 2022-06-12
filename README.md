[[English version]](README.en.md)

<h1>REPOMANAGER</h1>

Repomanager est un gestionnaire de repos de paquets.

Conçu pour un usage en entreprise et pour faciliter le déploiement de mises à jours sur d'importants parcs de serveurs Linux, il permet de créer facilement des miroirs de repos publics (ex: repos Debian, CentOS, ou autres éditeurs tiers) et d'en gérer plusieurs versions par environnements.

<b>Principales fonctionnalités :</b>

- Créer des miroirs de repos, les mettre à jour, les dupliquer
- Signer ses repos de paquets avec GPG
- Système d'environnements (ex: preprod, prod...) permettant de rendre accessible les miroirs à des environnements de serveurs particuliers
- Planifications automatiques permettant d'exécuter les actions ci-dessus à une date/heure souhaitée.

![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-4.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-5.png?raw=true)
![alt text](https://github.com/lbr38/repomanager-docs/blob/main/screenshots/repomanager-3.png?raw=true)


<b>Fonctionnalités</b>

| **Fonctions** | **Stable** |
|----------|---------------|
| Créer des miroirs de repos publics | ✅ |
| Créer des repos locaux | ✅ |
| Signer les repos ou les paquets avec GPG | ✅ |
| Archiver / restaurer des miroirs | ✅ |
| Charger des paquets dans des repos (ex: patchs zero-day) | ✅ |
| **Automatisation** | **Stable** |
| Créer des tâches planifiées sur les miroirs (mise à jour...) | ✅ |
| Rappels de planifications (mail) | ✅ |
| **Statistiques** | **Stable** |
| Graphiques sur l'utilisation et l'évolution des repos | ✅ |
| **Gestion du parc** | **Stable** |
| Analyser et gérer les paquets installés sur un parc de serveurs "clients" | ✅ |
| **Général** | **Stable** |
| Création d'utilisateurs (administrateurs ou "lecture-seule") | ✅ |
| Historique des actions effectuées par utilisateur | ✅ |
| Mise à jour automatique ou manuelle de repomanager | ✅ |


<h2>Ressources</h2>

Installation compatible sur les systèmes Redhat/CentOS et Debian/Ubuntu :
- Debian 9,10, Ubuntu bionic
- RHEL 7/8, CentOS 7/8, CentOS Stream, Rocky Linux, Fedora 33
Configuration minimale recommandé : Debian 10 ou RHEL/CentOS 8.

Repomanager ne nécessite qu'un service web + PHP (7 minimum) et SQLite.

Le CPU et la RAM sont essentiellement sollicités lors de la création de miroirs et si la signature avec GPG est activée.
L'espace disque est à adapter en fonction de la taille des repos distants à cloner.


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

Repomanager installera lui même ces dépendances si il détecte qu'elles ne sont pas présentes sur le système. Veillez donc à ce que le serveur ait au moins accès aux dépôts de base de son OS.


<h2>Installation</h2>

<b>Serveur web + PHP</b>

Repomanager s'administre depuis une interface web. Il faut donc installer un service web+php et configurer un vhost dédié.

Repomanager n'est testé qu'avec nginx+php-fpm (PHP 7.x) mais une compatibilité avec apache n'est pas exclue.

Note pour les systèmes Redhat/CentOS : adapter la configuration de SELinux et faire en sorte qu'il n'empêche pas la bonne exécution de PHP.

<pre>
# Redhat / CentOS
yum install nginx php-fpm php-cli php-pdo php-json sqlite

# Debian
apt update && apt install nginx php-fpm php-cli php7.4-json php7.4-sqlite3 sqlite3
</pre>

<b>SQLite</b>

S'assurer que le module sqlite pour php est activée :

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
RELEASE="v3.0.2-stable" # choix de la release
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

<h1>Linupdate et Repomanager</h1>

<b>linupdate</b> est un utilitaire de mise à jour de paquets systèmes pour les hôtes basés sur Debian ou Redhat/CentOS, et qui possède un module capable de communiquer avec Repomanager au travers de son api.

Sur Repomanager, l'onglet 'Gestion des hôtes' et 'Gestion des profils' permet de gérer et de configurer des serveurs/hôtes Linux exécutant linupdate et d'avoir une visualisation globale de l'état de leurs paquets systèmes.

Voir le projet [[linupdate]](https://github.com/lbr38/linupdate) pour plus d'informations.