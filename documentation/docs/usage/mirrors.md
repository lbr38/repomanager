## What is a mirror?

Since version `6.2.0`, Repomanager can create **mirrors**: copies of upstream deb, rpm or Arch Linux (pacman) repositories **as is**. Mirrors are listed with the other repositories in the **REPOSITORIES** tab, and can be added to groups like any repository. They are identified by the `mirror` label on their card (filtered repositories have the `filtered` label).

Unlike [filtered repositories](/usage/operations/#create-a-filtered-repository), nothing is rebuilt:

| | Filtered repository | Mirror |
|---|---|---|
| Metadata (`Release`, `Packages`, `repomd.xml`, `core.db`...) | Rebuilt by Repomanager | Upstream files, byte for byte |
| GPG signature checked by clients | Repomanager signing key | **Upstream key** (e.g. Debian, Rocky Linux, nginx...) |
| Content | One distribution + one component (deb), one release version (rpm) | **One or several distributions** with all their selected components, sharing the same pool as upstream (deb), one release version with all its architectures (rpm), one or several repositories such as `core` and `extra` (arch) |
| Package filtering (include, exclude, keep latest versions) | Yes | No, all packages are mirrored |
| Published under | `https://<FQDN>/repo/` | `https://<FQDN>/mirror/deb/<name>/<environment>/`<br>`https://<FQDN>/mirror/rpm/<name>/<release version>/<environment>/`<br>`https://<FQDN>/mirror/arch/<name>/<environment>/` |

Mirrors are meant to provide **public or internal mirrors** that clients use exactly as they would use the upstream repository, with the upstream GPG key. They keep Repomanager snapshots and environments.

!!! info "Storage"
    Mirrors are stored in the `repomanager-mirror` volume (`/home/mirror` inside the container). See [Standard installation](/getting-started/installation/) and, when upgrading from a previous version, [Update Repomanager](/administration/update/).


## :material-plus-circle: Create a mirror

From the **REPOSITORIES** tab:

**Step 1:** Add the source repository to mirror from, if not already done. See [Source repositories](/configuration/source-repositories/). Import its GPG key if you want the upstream signature to be checked during the sync.

**Step 2:** Click the `Create a new repository` button, select the `Mirror` repository type (Arch Linux repositories can only be mirrored, selecting the `arch` package type opens the mirror form directly) and fill in the form:

- **Name**: deb, rpm and arch mirrors are separated, mirrors of different package types can have the same name.
- **Source repository**.
- deb: the **distribution(s)** and the **components** to mirror. All selected distributions are mirrored together in the same tree and share the same `pool`, as upstream: clients select the distribution in their `sources.list` (see [Configure clients](#configure-clients)). The selected components are mirrored for each distribution. To manage distributions separately (different environments or sync schedules), create one mirror per distribution.
- rpm: the **release version**, which replaces `$releasever` in the source repository URL. Several mirrors can have the same name with different release versions (e.g. `nginx` for release versions `9` and `10`), they are published under `https://<FQDN>/mirror/rpm/<name>/<release version>/`.
- arch: the **repositories** to mirror (e.g. `core`, `extra`), which replace `$repo` in the source repository URL. All selected repositories are mirrored together, each one under `<repository>/os/<architecture>/`, as on Arch Linux mirrors.
- **Architecture(s)** to mirror.
- **Check GPG signatures**: check the upstream signature with the GPG keys imported in Repomanager (recommended).
- **Environment(s)**: optional, environments make the snapshot available to clients.
- **Description**, **tags** and **group**: optional, as for repositories. They can also be edited later from the mirror card (`+ Description`, `+ Tags` buttons, or double-click on the existing value), and the mirror can be moved to another group from the `Groups` panel.

**Step 3:** Click the `Execute now` button to launch the sync task.

??? note "How architectures are handled"
    - **deb**: only the indices and packages of the selected architectures are retrieved. `all` packages are always included. Select `src` to also mirror source packages. Installer files (`debian-installer`, udebs) are not mirrored.
    - **arch**: each selected architecture replaces `$arch` in the source repository URL. `any` packages are included in each architecture repository, as upstream.
    - **rpm**: if the source URL contains `$basearch`, one repository per selected architecture is mirrored under `<snapshot>/<arch>/` (`src` is mirrored under `SRPMS/`). There is no `noarch` repository: `noarch` packages are included in each architecture repository. If the source URL does not contain `$basearch`, the repository is mirrored as a whole, whatever the selected architectures.


## :material-autorenew: Sync a mirror

From the **REPOSITORIES** tab, click a snapshot of each mirror to sync to select it, then click the `Sync` button. Several mirrors can be synced at once, selecting several snapshots of the same mirror syncs it only once. Optionally select the environment(s) to switch to the new snapshot once it is complete.

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


## :material-folder-search: Browse a snapshot

Click the date of a snapshot to browse its content, as for repositories: tree structure, files search, text files (e.g. `InRelease`, `repomd.xml`) preview and files download. Mirror snapshots are read-only: packages cannot be uploaded or deleted, and metadata cannot be rebuilt.


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
- **Whole mirror**: delete all its snapshots, the mirror is deleted with its last snapshot, with its scheduled tasks. For rpm, only the selected release version is deleted.


## :material-monitor: Configure clients

Clients use the mirror URL of an environment, and the **upstream GPG key** (not the Repomanager signing key).

As for repositories, select one or more snapshots and click the `Install` button to get the configuration commands of the selected mirrors for an environment. The panel also lists the GPG key fingerprints declared in the source repository, the key must be trusted on the host.

=== "deb"

    Example for a `debian` mirror of the `bookworm`, `bookworm-updates`, `trixie` and `trixie-updates` distributions, `main` component, on the `prod` environment. All distributions are available under the same URL, as upstream:

    ```
    # Debian 12 hosts
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirror/deb/debian/prod bookworm main
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirror/deb/debian/prod bookworm-updates main

    # Debian 13 hosts
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirror/deb/debian/prod trixie main
    deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://<FQDN>/mirror/deb/debian/prod trixie-updates main
    ```

    The distributions and components are the same as upstream.

=== "rpm"

    Example for `rocky-baseos` mirrors of release versions `9` and `10`, whose source URL contains `$basearch`, on the `prod` environment. As the release version is part of the URL, the same file can be used on all hosts, `dnf` replaces `$releasever` with the version of the host:

    ```ini
    [rocky-baseos-mirror]
    name=Rocky Linux BaseOS (mirror)
    baseurl=https://<FQDN>/mirror/rpm/rocky-baseos/$releasever/prod/$basearch
    gpgcheck=1
    gpgkey=file:///etc/pki/rpm-gpg/RPM-GPG-KEY-Rocky-$releasever
    enabled=1
    ```

    If the source URL does not contain `$basearch`, remove `/$basearch` from `baseurl`.

=== "arch"

    Example for an `archlinux` mirror of the `core`, `extra` and `multilib` repositories, `x86_64` architecture, on the `prod` environment. Whatever the layout of the source repository URL, the mirror always has the layout of Arch Linux mirrors, so the same `Server` line is used for all repositories, in `/etc/pacman.d/mirrorlist`:

    ```
    Server = https://<FQDN>/mirror/arch/archlinux/prod/$repo/os/$arch
    ```

    Or directly in `/etc/pacman.conf` for third-party repositories:

    ```ini
    [chaotic-aur]
    Server = https://<FQDN>/mirror/arch/chaotic-aur/prod/$repo/os/$arch
    ```

    Packages are checked by pacman with the upstream keyring, as usual (e.g. `archlinux-keyring`, `chaotic-keyring`).


## :material-chart-line: Statistics

Click the name of a mirror to open its statistics page, as for repositories: snapshots timeline and size, accesses over time by environment, access requests and top IP addresses. Statistics must be enabled (`STATS_ENABLED` setting).

Accesses of apt, dnf/yum and pacman clients to the environments of the mirror are counted. As mirrors may be public and heavily used, keep in mind that each access request is stored in the statistics database (requests older than one year are deleted).


## :material-api: API

Mirrors can be listed, synced and their environments pointed through the API, see [API](/reference/api/#mirrors).


## :material-account-lock: Permissions

Mirrors use the repositories permissions (see [Users](/configuration/settings/#users)):

- **Actions** (create, update, delete, point or remove an environment, edit) are the same as for repositories.
- **Viewing**: a user can view a mirror if they can view all repositories, if they can view the group the mirror is member of (or the `Default` group if it is not member of any group), or if the mirror is granted individually (`Mirrors` section of the permissions selector).


## Limitations

- deb: the `$component` variable is not supported in the source repository URL, and the `$dist` variable requires a single distribution.
- rpm: only `baseurl` source repositories are supported (no `metalink` or `mirrorlist`).
- rpm: many repositories do not sign `repomd.xml`. In this case the sync prints a warning and packages signatures are checked by clients (`gpgcheck=1`).
- rpm: packages declared with a `xml:base` attribute in the upstream metadata are downloaded by clients from that URL, not from the mirror. The sync prints a warning in this case.
- arch: most repositories, including Arch Linux official ones, do not sign their database (`<repo>.db.sig`). In this case the sync prints a note, the snapshot is labelled `Unsigned database`, and packages signatures (`.sig` files, mirrored with the packages) are checked by pacman on clients.
- arch: the `<repo>.db.tar.gz` and `<repo>.files.tar.gz` aliases of upstream mirrors are not created, pacman only uses `<repo>.db` and `<repo>.files`.
- Directory listing of `/mirror/` is disabled by default, see [Enable repository web browsing](/reference/miscellaneous/#enable-repository-web-browsing).

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
