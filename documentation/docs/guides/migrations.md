## 5.0.0

``5.0.0`` is a major upgrade including breaking changes.

There should be no reason this upgrade fails, but here is a list of what to check and do before & after upgrading.

### What to check/do before upgrading

1. Make sure to be on the latest ``4.24.1`` version before upgrading to ``5.0.0``
2. Make sure to do a backup of your Repomanager data before upgrading. This includes volumes ``repomanager-data`` and ``repomanager-repo``. See [How to backup - What should be backed up](https://github.com/lbr38/repomanager/wiki/13.-Backup-and-restore#what-should-be-backed-up)
3. If possible, test the upgrade on a staging environment before doing it on production.

### What to check/do after upgrading

Once upgraded, check the docker container output with:

```bash
docker logs -f repomanager
```

Check for the yellow ``* migration started`` messages and wait until ``5.0.0 migration completed. Please check that everything is fine with your repositories.`` is displayed. Check that there is no red error message in between.

```bash
 ______  ____ ______   ____   _____ _____   ____  _____    ____   ___________
 \_  _ \/ __ \\____ \ /  _ \ /     \\__  \ /     \\__  \  / ___\_/ __ \_  __ \
 |  | \|  ___/|  |_> )  (_) )  Y Y  \/ __ \|   |  \/ __ \/ /_/  >  ___/|  | \/
 |__|   \___  >   __/ \____/|__|_|  (____  /___|  (____  |___  / \___  >__|
            \/|__|                \/     \/     \/     \/_____/      \/
           

[Tue Sep 02 08:58:15] Setting permissions... 
.
[Tue Sep 2 08:59:38] Databases check and initialization successful
[Tue Sep 2 08:59:38] Enabling maintenance page
[Tue Sep 2 08:59:38] Updating database
[Tue Sep 2 08:59:38] 5.0.0 stats database migration started
[Tue Sep 2 08:59:38] 5.0.0 stats database migration completed.
[Tue Sep 2 08:59:38] 5.0.0 repositories migration started
[Tue Sep 2 08:59:39] 
[Tue Sep 2 08:59:39] Migrating DEB repository: debian > bookworm > contrib
[Tue Sep 2 08:59:39]  - Snapshot: 31-08-2025
[Tue Sep 2 08:59:39]  - Environment: preprod
[Tue Sep 2 08:59:39]  -> Moving snapshot to /home/repo/deb/debian/bookworm/contrib/2025-08-31
[Tue Sep 2 08:59:39]  -> Recreating environment symlink: preprod
[Tue Sep 2 08:59:39]  -> Removing old environment symlink: /home/repo/debian/bookworm/contrib_preprod
[Tue Sep 2 08:59:39] 
[Tue Sep 2 08:59:39] Migrating DEB repository: debian > bookworm > main
[Tue Sep 2 08:59:39]  - Snapshot: 29-08-2025
[Tue Sep 2 08:59:39]  - Environment: preprod
[Tue Sep 2 08:59:39]  -> Moving snapshot to /home/repo/deb/debian/bookworm/main/2025-08-29
[Tue Sep 2 08:59:39]  -> Recreating environment symlink: preprod
[Tue Sep 2 08:59:39]  -> Removing old environment symlink: /home/repo/debian/bookworm/main_preprod
[Tue Sep 2 08:59:39] 
[Tue Sep 2 08:59:39] Migrating DEB repository: debian > bookworm > non-free
[Tue Sep 2 08:59:39]  - Snapshot: 29-08-2025
[Tue Sep 2 08:59:39]  - Environment: preprod
[Tue Sep 2 08:59:39]  -> Moving snapshot to /home/repo/deb/debian/bookworm/non-free/2025-08-29
[Tue Sep 2 08:59:39]  -> Recreating environment symlink: preprod
[Tue Sep 2 08:59:39]  -> Removing old environment symlink: /home/repo/debian/bookworm/non-free_preprod

[...]

[Tue Sep 2 08:59:39] 5.0.0 migration completed. Please check that everything is fine with your repositories.
[Tue Sep 2 08:59:39] Disabling maintenance page
```

If an error occurs during the migration process, please open a Github issue and provide the logs or reach me on Discord. Then restore a backup if needed, see [Restoring from backup](https://github.com/lbr38/repomanager/wiki/13.-Backup-and-restore#restoring-from-backup)

Login to Repomanager web interface and check that everything is fine with your repositories.

If possible, try to do some snapshot updates and to make sure everything is working fine.

Your repositories URLs have changed, you need to update the repository URLs on all your clients (select snapshots > ``Install``). See [Install a repository on a client](https://github.com/lbr38/repomanager/wiki/02.-Operations-on-repositories#install-a-repository-on-a-client)

