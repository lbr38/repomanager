## Enable repository web browsing

For security reasons, browsing a repository through the web server directory listing is disabled by default, as it allows anyone to browse the content of repositories without authentication.

If you really want to enable web server directory listing, follow the steps below. 

!!! warning "Important"
    This change is not persistent and will be lost at the next Repomanager upgrade. You will need to repeat these steps after each upgrade if you want to keep this feature enabled.

**Step 1:** Enter the container:

```bash
docker exec -it repomanager /bin/bash
```

**Step 2:** Edit the `repomanager` vhost configuration file:

```bash
vim /etc/nginx/sites-enabled/repomanager.conf
```

**Step 3:** Go to the bottom of the file and uncomment the `autoindex` directives in the `location /repo` block:

```nginx
location /repo {
    alias $REPOS_DIR;
    autoindex on;
    autoindex_exact_size off;
    autoindex_localtime on;
    autoindex_format html;
}
```

**Step 4:** Save the file and reload nginx:

```bash
service nginx reload
```

**Step 5:** Exit the container:

```bash
exit
```

You can now browse the repositories through your web browser at: `https://<FQDN>/repo/`

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
