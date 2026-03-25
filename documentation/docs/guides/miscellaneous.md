## Enable repository web browsing

For security reasons, browsing a repository through the web server directory listing is disabled by default as it allows anybody to browse the content of the repositories without authentication.

If you really want to enable web server directory listing, follow the steps below. Note that this change is not persistent and will be lost at the next Repomanager upgrade. You will need to repeat these steps after each upgrade if you want to keep this feature enabled.

Enter the container:

```
docker exec -it repomanager /bin/bash
```

Edit ``repomanager`` vhost configuration file:

```
vim /etc/nginx/sites-enabled/repomanager.conf
```

Go to the very bottom file and uncomment the ``autoindex`` directives in the ``location /repo`` block:

```
location /repo {
    alias $REPOS_DIR;
    autoindex on;
    autoindex_exact_size off;
    autoindex_localtime on;
    autoindex_format html;
}
```

Save the file and reload nginx:

```
service nginx reload
```

Exit the container:

```
exit
```
You can now browse the repositories through your web browser: ``https://<FQDN>/repo/``
