## Update Repomanager

!!! warning "Upgrade path"
    To avoid any issues, you must avoid skipping versions when updating Repomanager.

    For example, if you are currently running version ``4.16.0``, you must update to ``4.16.1`` then ``4.16.2``, etc, until you reach the latest version.

    This is to ensure that all database migrations are applied correctly. See the [releases page](https://github.com/lbr38/repomanager/releases) for all available versions.

When a new version of Repomanager is released, you can update your installation by following these steps:

Stop and delete the current container:

```
docker stop repomanager
docker rm -f repomanager
```

Clean up:

```
docker system prune -a -f
```

Pull and run the new Docker image (specify the **target version** or ``latest`` if you were on the latest version before). You will need to pass the following environment variables to the container:

- ``FQDN`` - Fully Qualified Domain Name of the Repomanager server.
- ``MAX_UPLOAD_SIZE`` - Maximum upload size in MB (default 32). Increase this value if you want to upload large packages to your repositories.
- ``NGINX_LISTEN_PORT`` - (Optional) Port to bind the web interface (default 8080). This variable is only used in ``host`` network mode.

??? "Additional environment variables"

    - ``WEBSOCKET_LISTEN_PORT`` - (Optional) Port to bind the WebSocket server (default 8081). This can be useful to change if you are running multiple Repomanager instances on the same host in ``host`` network mode (instances would conflict otherwise).

    - ``PUID`` - (Optional) UID for the main user inside the container (default 33). This can be useful to avoid permission issues between the host and the container when using persistent volumes.

    - ``PGID`` - (Optional) GID for the main group inside the container (default 33). This can be useful to avoid permission issues between the host and the container when using persistent volumes.

Choose the appropriate **network mode** for your Docker container, then pull and run the container with the environment variables and the persistent volumes:


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
        lbr38/repomanager:<VERSION>
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
        lbr38/repomanager:<VERSION>
    ```
</div>
