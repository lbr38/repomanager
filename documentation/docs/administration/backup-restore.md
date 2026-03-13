## What should be backed up

### Application data

Repomanager application data is stored in a dedicated Docker volume named `repomanager-data` which is located in `/var/lib/docker/volumes/repomanager-data` on the host (unless you have changed the default location). 

This volume contains **databases, logs and configuration files of the application**.

You should back up this volume to ensure you can restore your data in case of a failure. **This can be done by copying the content of the volume to another location with rsync or cp**. As the database is SQLite, no dump is needed.

The `repomanager-data` volume also contains the `version` file which contains the version of the application. This can be useful to know which version of the application was running when the backup was made and to restore the same version when restoring the backup.

### Repositories

Repositories are stored in a dedicated Docker volume named `repomanager-repo` which is located in `/var/lib/docker/volumes/repomanager-repo` on the host (unless you have changed the default location). 

This volume contains the packages and metadata of the repositories.

You should back up this volume to ensure you can restore your data in case of a failure. **This can be done by copying the content of the volume to another location with rsync or cp**.

### Summary

In summary, you should back up both `repomanager-data` and `repomanager-repo` volume directories which are located:

| Volume name | Default location on host |
|-------------|------------------|
| `repomanager-data` | `/var/lib/docker/volumes/repomanager-data` |
| `repomanager-repo` | `/var/lib/docker/volumes/repomanager-repo` |


## Restoring from backup

First stop the container:

```bash
docker stop repomanager
```

Restore the backup by copying the content of your backup to the volumes:

```bash
rsync -aP --delete my_backup/repomanager-data/ /var/lib/docker/volumes/repomanager-data/
rsync -aP --delete my_backup/repomanager-repo/ /var/lib/docker/volumes/repomanager-repo/
```

Make sure the permissions are correct:

```bash
chown -R 33 /var/lib/docker/volumes/repomanager-data
chown -R 33 /var/lib/docker/volumes/repomanager-repo
```

If you are restoring a previous version of the application, retrieve the version from the `version` file in the backup:

```bash
cat /var/lib/docker/volumes/repomanager-data/version
```

If you are restoring a previous version of the application, delete the current image and pull the image of the version you are restoring:

```bash
docker rm -f repomanager
docker system prune -a -f
docker pull lbr38/repomanager:<version>
```

Start the container:

```bash
docker start repomanager
```
