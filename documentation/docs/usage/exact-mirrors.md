## What is a mirror?

Since version `6.2.0`, Repomanager can create **mirrors**: copies of upstream deb or rpm repositories **as is**. Mirrors are listed with the other repositories in the **REPOSITORIES** tab, and can be added to groups like any repository. They are identified by the `mirror` label on their card (curated repositories have the `curated` label).

Unlike [curated repositories](/usage/operations/#create-a-curated-repository), nothing is rebuilt:

| | Curated repository | Mirror |
|---|---|---|
| Metadata (`Release`, `Packages`, `repomd.xml`...) | Rebuilt by Repomanager | Upstream files, byte for byte |
| GPG signature checked by clients | Repomanager signing key | **Upstream key** (e.g. Debian, Rocky Linux, nginx...) |
| Content | One distribution + one component (deb), one release version (rpm) | **One or several distributions** with all their selected components, sharing the same pool as upstream (deb), one release version with all its architectures (rpm) |
| Package filtering (include, exclude, keep latest versions) | Yes | No, all packages are mirrored |
| Published under | `https://<FQDN>/repo/` | `https://<FQDN>/mirrors/deb/<name>/<environment>/`<br>`https://<FQDN>/mirrors/rpm/<name>/<release version>/<environment>/` |

Mirrors are meant to provide **public or internal mirrors** that clients use exactly as they would use the upstream repository, with the upstream GPG key. They keep Repomanager snapshots and environments.

!!! info "Storage"
    Mirrors are stored in the `repomanager-mirrors` volume (`/home/mirrors` inside the container). See [Standard installation](/getting-started/installation/) and, when upgrading from a previous version, [Update Repomanager](/administration/update/).


## :material-plus-circle: Create a mirror

From the **REPOSITORIES** tab:

**Step 1:** Add the source repository to mirror from, if not already done. See [Source repositories](/configuration/source-repositories/). Import its GPG key if you want the upstream signature to be checked during the sync.

**Step 2:** Click the `Create a new repository` button, select the `Mirror` repository type and fill in the form:

- **Name**: deb and rpm mirrors are separated, a deb mirror and a rpm mirror can have the same name.
- **Source repository**.
- deb: the **distribution(s)** and the **components** to mirror. All selected distributions are mirrored together in the same tree and share the same `pool`, as upstream: clients select the distribution in their `sources.list` (see [Configure clients](#configure-clients)). The selected components are mirrored for each distribution. To manage distributions separately (different environments or sync schedules), create one mirror per distribution.
- rpm: the **release version**, which replaces `$releasever` in the source repository URL. Several mirrors can have the same name with different release versions (e.g. `nginx` for release versions `9` and `10`), they are published under `https://<FQDN>/mirrors/rpm/<name>/<release version>/`.
- **Architecture(s)** to mirror.
- **Check GPG signatures**: check the upstream signature with the GPG keys imported in Repomanager (recommended).
- **Environment(s)**: optional, environments make the snapshot available to clients.
- **Description**, **tags** and **group**: optional, as for repositories. They can also be edited later from the mirror card (`+ Description`, `+ Tags` buttons, or double-click on the existing value), and the mirror can be moved to another group from the `Groups` panel.

**Step 3:** Click the `Execute now` button to launch the sync task.

??? note "How architectures are handled"
    - **deb**: only the indices and packages of the selected architectures are retrieved. `all` packages are always included. Select `src` to also mirror source packages. Installer files (`debian-installer`, udebs) are not mirrored.
    - **rpm**: if the source URL contains `$basearch`, one repository per selected architecture is mirrored under `<snapshot>/<arch>/` (`src` is mirrored under `SRPMS/`). There is no `noarch` repository: `noarch` packages are included in each architecture repository. If the source URL does not contain `$basearch`, the repository is mirrored as a whole, whatever the selected architectures.


## :material-autorenew: Sync a mirror

From the **REPOSITORIES** tab, hover the mirror and click the `Sync` button. Optionally select the environment(s) to switch to the new snapshot once it is complete.

Each sync creates a new snapshot:

- Unchanged files are **hard linked** from the latest snapshot, only new files are downloaded. A new snapshot of an unchanged mirror takes almost no disk space.
- Every downloaded file is checked against the checksums of the upstream metadata.
- If the upstream repository is updated during the sync, the sync is started again (up to 3 times) so that the snapshot is always consistent.
- Files that are no longer referenced upstream are not kept in the new snapshot.
- If a sync fails, the next one resumes from the files already retrieved.

Only one sync can run at a time on a mirror.

To keep a mirror up to date, schedule a recurring sync (hourly, daily, cron...) from the same panel. See [Scheduling tasks](/usage/scheduling/).

### Snapshots retention

After each sync, snapshots that are not used by any environment are deleted, except the most recent ones, according to the `RETENTION` setting (see [Settings](/configuration/settings/#global-settings)).

For mirrors, **at least one unused snapshot is always kept**, even if `RETENTION` is `0`: it is the snapshot an environment has just been switched from, clients may still be reading it.


## :material-arrow-left-top: Point an environment to a snapshot

From the **REPOSITORIES** tab:

**Step 1:** Click the snapshot(s) to select them. Snapshots of several mirrors can be selected at once, but only one snapshot per mirror, as an environment points to one snapshot per mirror.

**Step 2:** Click the `Point an environment` button, select the environment(s) for each snapshot and click `Execute now`.

The environment is switched atomically: clients never get an error during the switch.

Protected environments cannot be moved from a snapshot to another.


## :material-delete: Remove an environment

Click the environment tag(s) to select them (several environments, of one or several mirrors, can be selected), then click `Remove`. The environment URL is no longer available to clients.

Protected environments cannot be removed.


## :material-delete: Delete a snapshot or a mirror

- **Snapshot(s)**: click the snapshot(s) to select them, then click `Delete`. A snapshot used by an environment cannot be deleted: remove or move the environment first.
- **Whole mirror**: hover the mirror and click the `Delete` button. All its snapshots, environments and scheduled tasks are deleted. For rpm, only the selected release version is deleted.


## :material-monitor: Configure clients

Clients use the mirror URL of an environment, and the **upstream GPG key** (not the Repomanager signing key). The URL of each environment is printed at the bottom of the mirror card in the **REPOSITORIES** tab.

=== "deb"

    Example for a `debian` mirror of the `bookworm`, `bookworm-updates`, `trixie` and `trixie-updates` distributions, `main` component, on the `prod` environment. All distributions are available under the same URL, as upstream:

    ```
    # Debian 12 hosts
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirrors/deb/debian/prod bookworm main
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirrors/deb/debian/prod bookworm-updates main

    # Debian 13 hosts
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirrors/deb/debian/prod trixie main
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirrors/deb/debian/prod trixie-updates main
    ```

    The distributions and components are the same as upstream.

=== "rpm"

    Example for `rocky-baseos` mirrors of release versions `9` and `10`, whose source URL contains `$basearch`, on the `prod` environment. As the release version is part of the URL, the same file can be used on all hosts, `dnf` replaces `$releasever` with the version of the host:

    ```ini
    [rocky-baseos-mirror]
    name=Rocky Linux BaseOS (mirror)
    baseurl=https://<FQDN>/mirrors/rpm/rocky-baseos/$releasever/prod/$basearch
    gpgcheck=1
    gpgkey=file:///etc/pki/rpm-gpg/RPM-GPG-KEY-Rocky-$releasever
    enabled=1
    ```

    If the source URL does not contain `$basearch`, remove `/$basearch` from `baseurl`.


## :material-account-lock: Permissions

Mirrors use the repositories permissions (see [Users](/configuration/settings/#users)):

- **Actions** (create, update, delete, point or remove an environment, edit) are the same as for repositories.
- **Viewing**: a user can view a mirror if they can view all repositories, if they can view the group the mirror is member of (or the `Default` group if it is not member of any group), or if the mirror is granted individually (`Mirrors` section of the permissions selector).


## Limitations

- deb: the `$component` variable is not supported in the source repository URL, and the `$dist` variable requires a single distribution.
- rpm: only `baseurl` source repositories are supported (no `metalink` or `mirrorlist`).
- rpm: many repositories do not sign `repomd.xml`. In this case the sync prints a warning and packages signatures are checked by clients (`gpgcheck=1`).
- rpm: packages declared with a `xml:base` attribute in the upstream metadata are downloaded by clients from that URL, not from the mirror. The sync prints a warning in this case.
- Directory listing of `/mirrors/` is disabled by default, see [Enable repository web browsing](/reference/miscellaneous/#enable-repository-web-browsing).

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
