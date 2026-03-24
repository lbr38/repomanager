## Standard installation

**The standard installation** is done by simply:

- Pulling the docker image from Docker Hub
- Creating a reverse proxy to access the web interface (optional but recommended)

This is the most common way to install a production-ready Repomanager instance on a host with docker.

### Pull and run the docker image

You will have to pass the following environment variables to the container:

- ``FQDN`` Fully Qualified Domain Name of the Repomanager server.
- ``MAX_UPLOAD_SIZE`` Max upload size in MB (default 32). Increase this value if you want to upload large packages to your repos.
- ``NGINX_LISTEN_PORT`` (Optional) Port to bind the web interface (default 8080). This variable is only used in ``host`` network mode.

<details>
<summary>Additonal environment variables</summary>

- ``WEBSOCKET_LISTEN_PORT`` (Optional) Port to bind the websocket server (default 8081). This can be useful to change it if you are running multiple Repomanager instances on the same host in ``host`` network mode (instances would conflict otherwise).

- ``PUID`` (Optional) UID for the main user inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.
- ``PGID`` (Optional) GID for the main group inside the container (default 33). This can be useful to avoid permissions issues between the host and the container when using persistent volumes.

</details>

Choose the proper docker **network mode** for your use case:

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
