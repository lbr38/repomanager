## What should be backed up

### Application data

Repomanager application data is stored in a dedicated Docker volume named `repomanager-data` which is located in `/var/lib/docker/volumes/repomanager-data` on the host (unless you have changed the default location). 

This volume contains **databases, logs and configuration files of the application**.

You should back up this volume to ensure you can restore your data in case of a failure. **This can be done by copying the content of the volume to another location with rsync or cp**. As the database is SQLite, no dump is needed.

The `repomanager-data` volume also contains the `version` file which contains the version of the application. This can be useful to know which version of the application was running when the backup was made and to restore the same version when restoring the backup.

!!! warning

    Make sure the container is stopped before backing up `repomanager-data` volume to avoid database write while the backup is being made. Otherwise you could end up with a corrupted backup and will not be able to restore your data correctly.

    ```bash
    docker stop repomanager
    ```

### Repositories

Repositories are stored in a dedicated Docker volume named `repomanager-repo` which is located in `/var/lib/docker/volumes/repomanager-repo` on the host (unless you have changed the default location). 

This volume contains the packages and metadata of the repositories.

You should back up this volume to ensure you can restore your data in case of a failure. **This can be done by copying the content of the volume to another location with rsync or cp**.

### Mirrors

[Mirrors](/usage/exact-mirrors/) are stored in a dedicated Docker volume named `repomanager-mirrors` which is located in `/var/lib/docker/volumes/repomanager-mirrors` on the host (unless you have changed the default location).

This volume contains the snapshots of the mirrors. As mirrors are copies of upstream repositories, they can also be synced again from scratch instead of being backed up.

!!! info
    Snapshots of a mirror share their unchanged files through hard links. Use `rsync -H` (or `cp -a`) to preserve hard links, otherwise each snapshot is copied in full in the backup.

### Summary

In summary, you should back up the `repomanager-data`, `repomanager-repo` and `repomanager-mirrors` volume directories which are located:

| Volume name | Default location on host |
|-------------|------------------|
| `repomanager-data` | `/var/lib/docker/volumes/repomanager-data` |
| `repomanager-repo` | `/var/lib/docker/volumes/repomanager-repo` |
| `repomanager-mirrors` | `/var/lib/docker/volumes/repomanager-mirrors` |


## Restoring from backup

First stop the container:

```bash
docker stop repomanager
```

Restore the backup by copying the content of your backup to the volumes:

```bash
rsync -aP --delete my_backup/repomanager-data/ /var/lib/docker/volumes/repomanager-data/
rsync -aP --delete my_backup/repomanager-repo/ /var/lib/docker/volumes/repomanager-repo/
rsync -aHP --delete my_backup/repomanager-mirrors/ /var/lib/docker/volumes/repomanager-mirrors/
```

Make sure the permissions are correct:

```bash
chown -R 33 /var/lib/docker/volumes/repomanager-data
chown -R 33 /var/lib/docker/volumes/repomanager-repo
chown -R 33 /var/lib/docker/volumes/repomanager-mirrors
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

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
