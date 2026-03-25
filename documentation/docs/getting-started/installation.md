**The standard installation** is done by simply:

- Pulling the docker image from Docker Hub
- Creating a reverse proxy to access the web interface (optional but recommended)

This is the most common way to install a production-ready Repomanager instance on a host with docker.

## Prepare the environment variables

You will have to pass the following environment variables to the container:

- ``FQDN`` Fully Qualified Domain Name of the Repomanager server.
- ``MAX_UPLOAD_SIZE`` Max upload size in MB (default 32). Increase this value if you want to upload large packages to your repos.
- ``NGINX_LISTEN_PORT`` (Optional) Port to bind the web interface (default 8080). This variable is only used in ``host`` network mode.

??? "Additonal environment variables"

    - ``WEBSOCKET_LISTEN_PORT`` (Optional) Port to bind the websocket server (default 8081). This can be useful to change it if you are running multiple Repomanager instances on the same host in ``host`` network mode (instances would conflict otherwise).

    - ``PUID`` (Optional) UID for the main user inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.

    - ``PGID`` (Optional) GID for the main group inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.

## Choose the network mode

Choose the proper docker **network mode** for your use case:

- ``bridge`` This is docker default network mode, but not necessarily the best choice. Be aware that in this mode, docker will bypass the firewall rules of your host by creating its own iptables rules, leading to a potential world access to the web interface. This mode is not recommanded for public hosts (for example VPS or dedicated servers).
- ``host`` Docker will bind port to the host. This mode is recommanded for public hosts (for example VPS or dedicated servers) as it will use the host firewall rules.

## Pull and run the docker image

Once everything is ready, pull and run the docker image with the environment variables and the persistent volumes:

<div class="grid cards" markdown>

-   :material-network: ``bridge`` network mode

    ---

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

-   :material-server-network-outline: ``host`` network mode

    ---

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
</div>

Two persistent volumes will be created on your local host:

- ``repomanager-data`` (default path: ``/var/lib/docker/volumes/repomanager-data/``): contains database and log files
- ``repomanager-repo`` (default path: ``/var/lib/docker/volumes/repomanager-repo/``): contains repositories packages (deb/rpm), this directory might grow large depending on your usage

## Check that the container is running

```
docker ps
```

```
CONTAINER ID   IMAGE                      COMMAND                CREATED          STATUS          PORTS                    NAMES
61088656e1bd   lbr38/repomanager:latest   "/tmp/entrypoint.sh"   12 seconds ago   Up 10 seconds   0.0.0.0:8080->8080/tcp   repomanager
```

Eventually check that there is no error in the container logs:

```
docker logs repomanager
```

```
 ______  ____ ______   ____   _____ _____   ____  _____    ____   ___________
 \_  _ \/ __ \\____ \ /  _ \ /     \\__  \ /     \\__  \  / ___\_/ __ \_  __ \
 |  | \|  ___/|  |_> )  (_) )  Y Y  \/ __ \|   |  \/ __ \/ /_/  >  ___/|  | \/
 |__|   \___  >   __/ \____/|__|_|  (____  /___|  (____  |___  / \___  >__|
            \/|__|                \/     \/     \/     \/_____/      \/
           

[Wed Mar 25 09:04:24][INF] Setting permissions... 
[Wed Mar 25 09:04:24][INF] Starting php-fpm...
[Wed Mar 25 09:04:24][INF] Starting nginx...
[Wed Mar 25 09:04:24][INF] Starting postfix...
[Wed Mar 25 09:04:26][INF] Databases check and initialization successful
[Wed Mar 25 09:04:26][INF] Enabling maintenance page
[Wed Mar 25 09:04:26][INF] Updating database
[Wed Mar 25 09:04:26][INF] Disabling maintenance page
[Wed Mar 25 09:04:26][INF] Starting repomanager service...
```

## First login

Once the container is up and running, Repomanager will be accessible through a web browser on ``http://localhost:8080``.

It is recommended to configure a reverse proxy to access the web interface through a dedicated FQDN and port ``443`` (you will need to have a valid SSL certificate). See [this section](reverseproxy.md) for an example of nginx reverse proxy configuration.

Default credentials to access the web interface:

- **Username:** admin
- **Password:** repomanager
