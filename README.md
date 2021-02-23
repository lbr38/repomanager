<h1>REPOMANAGER</h1>

Repomanager est un gestionnaire de repos de paquets.

Conçu pour un usage en entreprise et pour faciliter le déploiement de mises à jours sur d'importants parcs de serveurs Linux, il permet de créer facilement des miroirs de repos publics (ex: repos Debian, CentOS, ou autres éditeurs tiers) et d'en gérer plusieurs versions par environnements.

<b>Principales fonctionnalités :</b>

- Créer des miroirs de repos, les mettre à jour, les dupliquer, les déployer sur les serveurs clients.
- Signer ses repos de paquets avec GPG.
- Système d'environnements (ex preprod, prod) permettant de rendre accessible les miroirs d'abord par les serveurs preprod, puis par les serveurs prod (nb d'environnements illimité).
- Planifications automatiques permettant d'exécuter les actions ci-dessus à n'importe quelle date/heure.

(voir tableau ci-dessous pour la liste complète)

![alt text](https://github.com/lbr38/repomanager/blob/beta/repomanager.png?raw=true)

<b>Ressources :</b>

Repomanager est très léger car il repose sur Bash et se pilote depuis une web-UI (user interface).
Il nécessite seulement un service web + PHP sans module complémentaire. Aucun système de gestion de base de données n'est nécessaire.

Le CPU et la RAM sont essentiellement sollicités lors de la création de miroirs et selon le nombre de paquets à copier et signer.
L'espace disque est à adapter en fonction du nombre de miroirs créés / nombre de paquets qu'ils contiennent.


<h1>Beta version</h1>

Compatible avec les systèmes Redhat/CentOS et Debian/Ubuntu.

Testé sur : 
- Debian 10
- CentOS 7, 8
- Fedora 33
- Ubuntu bionic

<p>Fonctionnalités actuelles et futures de la version Beta</p>

| **Fonctions basiques et avancées** | **Disponible en version Beta** |
|----------|---------------|
| Créer un miroir à partir d'un repo public | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Mettre à jour un miroir précédemment créé (récupérer les dernières versions de paquets) | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Signer ses repos avec GPG | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes (repos rpm seulement pour le moment) |
| Dupliquer un repo | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Renommer un repo | ![#f03c15](https://placehold.it/15/f03c15/000000?text=+) No (à venir) |
| Archiver un repo | ![#00aa00](https://placehold.it/e repos à partir repos à partir/00aa00/000000?text=+) Yes |
| Restaurer un repo archivé | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Créer des groupes de repos | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Planifier la mise à jour d'un repo | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Planifier la bascule d'environnement | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Rappels de planifications (mail) | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Nettoyage automatique des repos archivés | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Mise à jour automatique de repomanager | ![#00aa00](https://placehold.it/15/00aa00/000000?text=+) Yes |
| Créer des patchs zero-day (uploader un ou plusieurs paquet(s) patché dans ses repos) | ![#f03c15](https://placehold.it/15/f03c15/000000?text=+) No (à venir) |


<b>Dépendances</b>

Pour fonctionner repomanager requiert la présence de certains logiciels couramment installés sur les distributions Linux, tels que :
<pre>
rsync, curl, wget, mutt, at, gnupg2
</pre>

Ainsi que certains logiciels spécifiques nécessaires pour créer des miroirs de repo tels que :
<pre>
yum-utils et createrepo (CentOS/Redhat)
rpmresign (module perl RPM4) pour la signature des repos (CentOS/Redhat)
debmirror (Debian)
</pre>

Repomanager installera lui même ces dépendances s'il détecte qu'elles ne sont pas présentes sur le système. Veillez donc à ce que le serveur ait au moins accès aux repositorys de base de son OS.

Note pour les systèmes Redhat : Il faut désactiver SELinux ou faire en sorte qu'il ne n'empêche pas la bonne exécution de PHP.


<h2>Installation</h2>

<b>Serveur web</b>

Repomanager s'administre depuis une interface web. Il faut donc installer un service web+php et configurer un vhost dédié.

Dans sa version beta, repomanager n'a été testé qu'avec nginx+php-fpm. Une compatibilité avec apache n'est pas exclue puisque le vhost à mettre en place n'a rien d'extraordinaire.

<pre>
yum install nginx php-fpm
apt update && apt install nginx php-fpm
</pre>

<b>Vhost</b>

<pre>
#### Repomanager vhost ####

server {
        listen SERVER-IP:80 default_server;
        server_name SERVERNAME.MYDOMAIN.COM;
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_access.log;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_error.log;
        return 301 https://$server_name$request_uri;
}

server {
        listen SERVER-IP:443 default_server ssl;
        server_name SERVERNAME.MYDOMAIN.COM;
        #rewrite ^/(.*)/$ /$1 permanent;

        # SSL certificate files
        ssl_certificate      PATH-TO-CERTIFICATE.crt;
        ssl_certificate_key  PATH-TO-PRIVATE-KEY.key;

	# Log files
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_access.log;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_error.log;

        # Security headers
        add_header Strict-Transport-Security "max-age=15552000; includeSubDomains";
        add_header X-Content-Type-Options nosniff;
        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-XSS-Protection "1; mode=block";
        add_header X-Robots-Tag none;
        add_header X-Download-Options noopen;
        add_header X-Permitted-Cross-Domain-Policies none;

	# Custom error pages
        error_page 404 /custom_404.html;
        error_page 500 502 503 504 /custom_50x.html;
        location = /custom_404.html {
                root WWW_DIR/www/custom_errors;
                internal;
        }
        location = /custom_50x.html {
                root WWW_DIR/www/custom_errors;
                internal;
        }

        # Enable gzip but do not remove ETag headers
        gzip on;
        gzip_vary on;
        gzip_comp_level 4;
        gzip_min_length 256;
        gzip_proxied expired no-cache no-store private no_last_modified no_etag auth;
        gzip_types application/atom+xml application/javascript application/json application/ld+json application/manifest+json application/rss+xml application/vnd.geo+json application/vnd.ms-fontobject application/x-font-ttf application/x-web-app-manifest+json application/xhtml+xml application/xml font/opentype image/bmp image/svg+xml image/x-icon text/cache-manifest text/css text/plain text/vcard text/vnd.rim.location.xloc text/vtt text/x-component text/x-cross-domain-policy;


	location / {
		root WWW_DIR; # default is /var/www/repomanager
	        try_files $actual_uri $actual_uri/ =404;
	        index index.php;
	}


	location ~ [^/]\.php(/|$) {
		root WWW_DIR; # default is /var/www/repomanager
	        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
	        if (!-f $document_root$fastcgi_script_name) {
	                return 404;
	        }
	        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock; 
	        fastcgi_index index.php;
	        include fastcgi_params;
	}

	location /repo {
	        root REPOS_DIR; # default is /home
	        autoindex off;
	        allow all;
	}
}
</pre>


<b>repomanager</b>

Le programme s'installe dans 3 répertoires différents choisis par l'utilisateur au moment de l'installation :
<pre>
Répertoire du programme bash (par défaut /home/repomanager/)
Répertoire des fichiers web (par défaut /var/www/repomanager/)
Répertoire de stockage des miroirs de repos (par défaut /home/repo/)
</pre>

Ainsi que le répertoire des fichiers de configuration et variables (non modifiable) :
<pre>
/etc/repomanager/
</pre>

Il est préférable de procéder à l'installation en tant que root ou sudo afin que l'utilisateur puisse correctement mettre en place les bonnes permissions sur tous les répertoires utilisés par repomanager.

En tant que root, télécharger la dernière release disponible de repomanager au format .tar.gz. Toutes les releases sont visibles ici : https://github.com/lbr38/repomanager/releases

<pre>
su -
cd /tmp
wget https://github.com/lbr38/repomanager/releases/download/RELEASE/repomanager_RELEASE.tar.gz
tar xzf repomanager_RELEASE.tar.gz
cd /tmp/repomanager
</pre>

Utilisez le script first-install qui se chargera de vous demander les chemins des 3 répertoires d'installation et d'y copier les bons fichiers.
<pre>
chmod 700 first-install
./first-install
</pre>

Enfin, répondre aux questions posées par repomanager afin de mettre en place sa configuration. Il est possible d'interrompre la configuration à tout moment par Ctrl+C.
