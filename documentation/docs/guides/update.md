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