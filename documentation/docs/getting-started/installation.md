## Requirements

- docker (service must be up and running)
- If you want to access the web interface through a secure connection (``https://``), you will need a reverse proxy (nginx for example), a fully qualified domain name (FQDN) and a valid SSL certificate for this FQDN
- A least a SPF record configured for your FQDN, to be able to send emails from Repomanager


## Standard installation

**The standard installation** is done by simply:
- pulling the docker image from Docker Hub
- creating a reverse proxy to access the web interface (optional but recommended)

This is the most common way to install a production-ready Repomanager instance on a host with docker.

### Pull and run the docker image

1. You will have to pass the following environment variables to the container:

- ``FQDN`` Fully Qualified Domain Name of the Repomanager server.
- ``MAX_UPLOAD_SIZE`` Max upload size in MB (default 32). Increase this value if you want to upload large packages to your repos.
- ``NGINX_LISTEN_PORT`` (Optional) Port to bind the web interface (default 8080). This variable is only used in ``host`` network mode.

<details>
<summary>Additonal environment variables</summary>

- ``WEBSOCKET_LISTEN_PORT`` (Optional) Port to bind the websocket server (default 8081). This can be useful to change it if you are running multiple Repomanager instances on the same host in ``host`` network mode (instances would conflict otherwise).

- ``PUID`` (Optional) UID for the main user inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.
- ``PGID`` (Optional) GID for the main group inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.

</details>

2. Choose the proper docker **network mode** for your use case:

- ``bridge`` This is docker default network mode, but not necessarily the best choice. Be aware that in this mode, docker will bypass the firewall rules of your host by creating its own iptables rules, leading to a potential world access to the web interface. This mode is not recommanded for public hosts (for example VPS or dedicated servers).
- ``host`` Docker will bind port to the host. This mode is recommanded for public hosts (for example VPS or dedicated servers) as it will use the host firewall rules.

3. Pull and run the container with the environment variables and the persistent volumes:

<div align="center">
<table>
<tr>
<td>
    
``bridge`` network mode
    
</td>
<td>

``host`` network mode

</td>
</tr>
<tr>
<td>

```bash
docker run -d --restart unless-stopped --name repomanager \
    -e FQDN=repomanager.example.com \
    -e MAX_UPLOAD_SIZE=32M \
    -p 8080:8080 \
    -v /etc/localtime:/etc/localtime:ro \
    -v /var/lib/docker/volumes/repomanager-data:/var/lib/repomanager \
    -v /var/lib/docker/volumes/repomanager-repo:/home/repo \
    lbr38/repomanager:latest
```

</td>
<td>

```bash
docker run -d --restart unless-stopped --name repomanager --network=host \
    -e FQDN=repomanager.example.com \
    -e MAX_UPLOAD_SIZE=32M \
    -e NGINX_LISTEN_PORT=8080 \
    -v /etc/localtime:/etc/localtime:ro \
    -v /var/lib/docker/volumes/repomanager-data:/var/lib/repomanager \
    -v /var/lib/docker/volumes/repomanager-repo:/home/repo \
    lbr38/repomanager:latest
```

</td>
</tr>
</table>
</div>

Two persistent volumes will be created on your local host:

- ``repomanager-data`` (default path: ``/var/lib/docker/volumes/repomanager-data/``): contains database and log files
- ``repomanager-repo`` (default path: ``/var/lib/docker/volumes/repomanager-repo/``): contains repositories packages (deb/rpm), this directory might grow large depending on your usage

4. Check that the container is running:

```
docker ps
```

```
CONTAINER ID   IMAGE                      COMMAND                CREATED          STATUS          PORTS                    NAMES
61088656e1bd   lbr38/repomanager:latest   "/tmp/entrypoint.sh"   12 seconds ago   Up 10 seconds   0.0.0.0:8080->8080/tcp   repomanager
```

5. Once the container is up and running, Repomanager will be accessible through a web browser on ``http://localhost:8080``. It is recommended to configure a reverse proxy to access the web interface through a dedicated FQDN and port ``443`` (you will need to have a valid SSL certificate). See an example below.

Default credentials to access the web interface:

- **Username:** admin
- **Password:** repomanager


### Reverse proxy

Here is an example of a nginx reverse proxy.

1. Create a new vhost and replace the following values:

- ``<SERVER-IP>``
- ``<FQDN>``
- ``<PATH_TO_CERTIFICATE>``
- ``<PATH_TO_PRIVATE_KEY>``

```
upstream repomanager_docker {
    server 127.0.0.1:8080;
}

# Disable some logging
map $request_uri $loggable {
    /ajax/controller.php 0;
    default 1;
}

server {
    listen <SERVER-IP>:80;
    server_name <FQDN>;

    access_log /var/log/nginx/<FQDN>_access.log combined if=$loggable;
    error_log /var/log/nginx/<FQDN>_error.log;

    return 301 https://$server_name$request_uri;
}
 
server {
    listen <SERVER-IP>:443 ssl;
    server_name <FQDN>;

    # Path to SSL certificate/key files
    ssl_certificate <PATH_TO_CERTIFICATE>;
    ssl_certificate_key <PATH_TO_PRIVATE_KEY>;

    # Path to log files
    access_log /var/log/nginx/<FQDN>_ssl_access.log combined if=$loggable;
    error_log /var/log/nginx/<FQDN>_ssl_error.log;

    # Max upload size
    client_max_body_size 32M;
 
    # Security headers
    add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;" always;
    add_header Referrer-Policy "no-referrer" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;

    # Remove X-Powered-By, which is an information leak
    fastcgi_hide_header X-Powered-By;
 
    location / {
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Port 443;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_read_timeout 86400;
        proxy_pass http://repomanager_docker;
    }
}
```

2. Reload nginx to apply.

3. Open your web browser and connect to ``http://<FQDN>``.


## Alternative installation methods

### With Ansible <img src="https://github.com/user-attachments/assets/81ab3096-d13c-47a6-92c5-aa4a389b65b4" width="18" />

You can find an Ansible role to install and update Repomanager [here](https://github.com/lbr38/repomanager/tree/devel/ansible)

This role pulls the latest image and creates a reverse proxy vhost for nginx.

Replace the variables in ``roles/repomanager/vars/repomanager.yml``, add the role inside your ansible playbook and run it!

/!\ The role does not install the basic requirements (docker and nginx). You will have to install them before running the role.

### With Kubernetes <img src="https://github.com/user-attachments/assets/4e4c55cd-0018-4408-ac07-6b0840974e54" width="18" />

Some users managed to install Repomanager inside a Kubernetes cluster but this is not officially documented yet.


## Update repomanager

> [!IMPORTANT]  
> **Upgrade path**: to avoid any issues, you must avoid to skip versions when updating Repomanager.
>
> For example, if you are currently running version ``4.16.0``, you must update to ``4.16.1`` then ``4.16.2``, etc, until you reach the latest version.
>
> This is to ensure that all database migrations are applied correctly. See the releases page for all available versions: https://github.com/lbr38/repomanager/releases

When a new version of Repomanager is released, you can update your installation by following these steps:

1. Stop and delete the current container:

```
docker stop repomanager
docker rm -f repomanager
```

2. Clean up:

```
docker system prune -a -f
```

3. Pull and run the new docker image (specify the target version or ``latest`` if you were on the latest version before). You will have to pass the following environment variables to the container:

- ``FQDN`` Fully Qualified Domain Name of the Repomanager server.
- ``MAX_UPLOAD_SIZE`` Max upload size in MB (default 32). Increase this value if you want to upload large packages to your repos.
- ``NGINX_LISTEN_PORT`` (Optional) Port to bind the web interface (default 8080). This variable is only used in ``host`` network mode.

4. Choose the proper network mode for your docker container then pull and run the container with the environment variables and the persistent volumes:

<div align="center">
<table>
<tr>
<td>
    
``bridge`` network mode
    
</td>
<td>

``host`` network mode

</td>
</tr>
<tr>
<td>

```bash
docker run -d --restart unless-stopped --name repomanager \
    -e FQDN=repomanager.example.com \
    -e MAX_UPLOAD_SIZE=32M \
    -p 8080:8080 \
    -v /etc/localtime:/etc/localtime:ro \
    -v /var/lib/docker/volumes/repomanager-data:/var/lib/repomanager \
    -v /var/lib/docker/volumes/repomanager-repo:/home/repo \
    lbr38/repomanager:<VERSION>
```

</td>
<td>

```bash
docker run -d --restart unless-stopped --name repomanager --network=host \
    -e FQDN=repomanager.example.com \
    -e MAX_UPLOAD_SIZE=32M \
    -e NGINX_LISTEN_PORT=8080 \
    -v /etc/localtime:/etc/localtime:ro \
    -v /var/lib/docker/volumes/repomanager-data:/var/lib/repomanager \
    -v /var/lib/docker/volumes/repomanager-repo:/home/repo \
    lbr38/repomanager:<VERSION>
```

</td>
</tr>
</table>
</div>


## Backup and restore

See https://github.com/lbr38/repomanager/wiki/13.-Backup-and-restore