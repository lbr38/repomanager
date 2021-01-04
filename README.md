<h1>Alpha version</h1>

Compatible avec les sytèmes Redhat/CentOS et Debian/Ubuntu.

Testé sur : 
- Debian 10
- CentOS 8
- Linux Mint 19.3 (Ubuntu bionic)

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


<h2>Installation</h2>

<b>Serveur web</b>

Repomanager s'administre depuis une interface web. Il faut donc installer un service web+php et configurer un vhost dédié.

Dans sa version alpha, repomanager n'a été testé qu'avec nginx+php-fpm. Une compatibilité avec apache n'est pas exclue puisque le vhost à mettre en place n'a rien d'extraordinaire.

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
    listen SERVER-IP:443 default_server;
    server_name SERVERNAME.MYDOMAIN.COM;
    #rewrite ^/(.*)/$ /$1 permanent;
    ssl on;
    ssl_certificate      PATH-TO-CERTIFICATE.crt;
    ssl_certificate_key  PATH-TO-PRIVATE-KEY.key;
    access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_access.log;
    error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_error.log;

	location / {
			root WWW_DIR; # default is /var/www/repomanager
	        try_files $uri $uri/ =404;
	        index index.php;
	}


	location ~ [^/]\.php(/|$) {
	        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
	        if (!-f $document_root$fastcgi_script_name) {
	                return 404;
	        }
	        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock; 
	        fastcgi_index index.php;
	        include fastcgi_params;
	}

	location /repo {
	        root REPOS_DIR; # default is /home/repo
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
