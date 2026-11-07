Operations are made from the **REPOSITORIES** tab. Filtered repositories, local repositories and mirrors are listed together, see [Repository types](/usage/repository-types/) for the differences between them.

!!! info "Operations available for mirrors"
    Mirrors are copies of upstream repositories, their snapshots are read-only. The following operations are **not available** for mirrors: `Rename`, `Duplicate`, `Rebuild`, packages upload and deletion. Mirrors are synced with the `Sync` button instead of `Update`.

## :material-plus-circle: Create a filtered repository

From the **REPOSITORIES** tab:

**Step 1:** You must first add one or more source repositories to get packages from. See [Source repositories](/configuration/source-repositories/).

**Step 2:** Click the ``Create a new repository`` button to show the **NEW REPOSITORY** panel.

[![Create a filtered repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)

**Step 3:** Select ``Filtered`` as the repository type then complete all the required fields.

[![Create a filtered repository](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-repository.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-repository.png)

**Step 4:** Click the `Execute now` button to launch the sync task.

!!! info
    The `REPOSITORY NAME` field is optional. If none is specified, the repository will have the same name as the source repository (e.g., if the source repository is named `nginx`, then the filtered repository will also be named `nginx`).


## :material-plus-circle: Create a local repository

From the **REPOSITORIES** tab:

**Step 1:** Click the ``Create a new repository`` button to show the **NEW REPOSITORY** panel.

[![Create a local repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)

**Step 2:** Select ``Local`` as the repository type then complete all the required fields.

[![Create a local repository](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-local-repository.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-local-repository.png)

**Step 3:** Click the `Execute now` button to launch the repository creation task.

!!! info
    Local repositories are always empty when created. You must then add your own `rpm` or `deb` packages by uploading them. See [Upload package(s) in a repository](#upload-packages-in-a-repository).


## :material-plus-circle: Create a mirror

From the **REPOSITORIES** tab:

**Step 1:** Add the source repository to mirror from, if not already done. See [Source repositories](/configuration/source-repositories/). Import its GPG key if you want the upstream signature to be checked during the sync.

**Step 2:** Click the ``Create a new repository`` button, select ``Mirror`` as the repository type (Arch Linux repositories can only be mirrored, selecting the `arch` package type opens the mirror form directly) and fill in the form:

- **Name**: deb, rpm and arch mirrors are separated, mirrors of different package types can have the same name.
- **Source repository**.
- deb: the **distribution(s)** and the **components** to mirror. All selected distributions are mirrored together in the same tree and share the same `pool`, as upstream: clients select the distribution in their `sources.list` (see [Install a repository on a client](#install-a-repository-on-a-client)). The selected components are mirrored for each distribution. To manage distributions separately (different environments or sync schedules), create one mirror per distribution.
- rpm: the **release version**, which replaces `$releasever` in the source repository URL. Several mirrors can have the same name with different release versions (e.g. `nginx` for release versions `9` and `10`), they are published under `https://<FQDN>/mirror/rpm/<name>/<release version>/`.
- arch: the **repositories** to mirror (e.g. `core`, `extra`), which replace `$repo` in the source repository URL. All selected repositories are mirrored together, each one under `<repository>/os/<architecture>/`, as on Arch Linux mirrors.
- **Architecture(s)** to mirror.
- **Check GPG signatures**: check the upstream signature with the GPG keys imported in Repomanager (recommended).
- **Environment(s)**: optional, environments make the snapshot available to clients.
- **Description**, **tags**, **snapshots retention** and **group**: optional, as for repositories. See [Edit repository properties](#edit-repository-properties).

[![Create a mirror](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-mirror.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/create-new-mirror.png)

**Step 3:** Click the `Execute now` button to launch the sync task.

??? note "How architectures are handled"
    - **deb**: only the indices and packages of the selected architectures are retrieved. `all` packages are always included. Select `src` to also mirror source packages.
    - **arch**: each selected architecture replaces `$arch` in the source repository URL. `any` packages are included in each architecture repository, as upstream.
    - **rpm**: if the source URL contains `$basearch`, one repository per selected architecture is mirrored under `<snapshot>/<arch>/` (`src` is mirrored under `SRPMS/`). There is no `noarch` repository: `noarch` packages are included in each architecture repository. If the source URL does not contain `$basearch`, the repository is mirrored as a whole, whatever the selected architectures.


## :material-autorenew: Update an existing filtered repository

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot from which you want to update the filtered repository.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Update`` button.

**Step 3:** Specify the sync parameters.

[![Update repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/update-repository-2.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/update-repository-2.png)

**Step 4:** Click the `Execute now` button to launch the sync task.

!!! info
    A filtered repository has only one snapshot per day: updating a snapshot created the same day replaces it instead of creating a new one. Environments pointing to it keep pointing to it.<br>A snapshot used by a protected environment cannot be replaced: the update is refused on the day it was created.


## :material-autorenew: Sync a mirror

From the **REPOSITORIES** tab:

**Step 1:** Click a snapshot of each mirror to sync to select it. Several mirrors can be synced at once, selecting several snapshots of the same mirror syncs it only once.

[![Select a mirror snapshot](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/select-mirror-snapshot.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/select-mirror-snapshot.png)

**Step 2:** Click the `Sync` button.

[![Mirror actions](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/mirror-actions.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/mirror-actions.png)

Optionally select the environment(s) to switch to the new snapshot once it is complete.

[![Sync a mirror](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/sync-mirror.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/sync-mirror.png)

**Step 3:** Click the `Execute now` button to launch the sync task.

Each sync creates a new snapshot:

- Unchanged files are **hard linked** from the latest snapshot, only new files are downloaded. A new snapshot of an unchanged mirror takes almost no disk space.
- Every downloaded file is checked against the checksums of the upstream metadata.
- If the upstream repository is updated during the sync, the sync is started again (up to 3 times) so that the snapshot is always consistent.
- Files that are no longer referenced upstream are not kept in the new snapshot.
- If a sync fails, the next one resumes from the files already retrieved.

Only one sync can run at a time on a mirror.

To keep a mirror up to date, schedule a recurring sync (hourly, daily, cron...) from the same panel. See [Scheduling tasks](/usage/scheduling/).


## :material-cog: Edit repository properties

From the **REPOSITORIES** tab, click the `Edit` button on the repository or mirror card to open the **EDIT REPOSITORY PROPERTIES** (or **EDIT MIRROR PROPERTIES**) panel:

[![Edit button](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository-btn.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository-btn.png)

- **Source repository** (filtered repositories only): the source repository used by the next updates.
- **Snapshots retention**: see [Snapshots retention](#snapshots-retention).

[![Edit repository properties](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository.png)

The **description** and **tags** are edited from the card itself: use the `+ Description` and `+ Tags` buttons, or double-click on the existing value. The repository can be moved to another group from the `Groups` panel.

### Snapshots retention

After each task, snapshots that are not used by any environment are deleted, except the most recent ones:

- By default, the number of unused snapshots to keep is the global `RETENTION` setting (see [Settings](/configuration/settings/#global-settings)).
- The **Snapshots retention** field overrides it for a repository or a mirror. Leave it empty to use the global setting.
- **Keep all snapshots** disables the deletion of unused snapshots for this repository or mirror. Disk usage will grow with each update.

The retention can be set when creating the repository or mirror, or later from the `Edit` button.

For mirrors, **at least one unused snapshot is always kept**, even if the retention is `0`: it is the snapshot an environment has just been switched from, clients may still be reading it.


## :material-rename: Rename a repository

Not available for mirrors.

From the **REPOSITORIES** tab:

**Step 1:** Click the `Rename` button on the repository card.

[![Rename button](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository-btn.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/edit-repository-btn.png)

**Step 2:** Specify the new name of the repository. Only the name changes: the release version (rpm), distribution and component (deb) are kept.

[![Rename a repository](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/rename-repository.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/rename-repository.png)

**Step 3:** Click the `Execute now` button to launch the renaming task, or schedule it.

!!! info
    - The whole repository is renamed, with all its snapshots.
    - The new name must not be used by another repository with the same release version (rpm) or the same distribution and component (deb).
    - As the name is part of the repository URL, update the configuration of the clients after renaming. See [Install a repository on a client](#install-a-repository-on-a-client).


## :material-arrow-left-top: Point an environment to a snapshot

From the **REPOSITORIES** tab:

**Step 1:** Select the repository or mirror snapshot you want to point an environment to.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Point an environment`` button.

**Step 3:** Specify the environment that will point to the snapshot.

[![Point environment](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/point-environment.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/point-environment.png)

**Step 4:** Click the `Execute now` button to launch the task.

!!! info
    - A protected environment cannot be pointed to a repository snapshot.
    - Snapshots of several repositories or mirrors can be selected at once. For mirrors, select only one snapshot per mirror, as an environment points to one snapshot per mirror.
    - Mirrors environments are switched atomically: clients never get an error during the switch.


## :material-delete: Remove an environment

From the **REPOSITORIES** tab:

**Step 1:** Select the environment(s) you want to remove from a repository or mirror snapshot. Several environments, of one or several repositories or mirrors, can be selected.

[![Remove environment](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/remove-environment.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/remove-environment.png)

**Step 2:** Click the ``Remove`` button. The environment URL is no longer available to clients.

!!! info
    A protected environment cannot be removed from a repository snapshot.


## :octicons-copy-16: Duplicate a repository

Not available for mirrors.

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot you want to duplicate.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Duplicate`` button.

**Step 3:** Specify the name of the new repository that will be created from the selected repository snapshot.

[![Duplicate a repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/duplicate-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/duplicate-repository.png)

**Step 4:** Click the `Execute now` button to launch the duplication task.


## :material-delete: Delete a repository snapshot

From the **REPOSITORIES** tab:

**Step 1:** Select the repository or mirror snapshot(s) you want to delete.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Delete`` button.

**Step 3:** Click the `Execute now` button to launch the deletion task.

!!! info
    - A snapshot having a protected environment cannot be deleted.
    - Mirrors: environments pointing to a deleted snapshot are removed with it, their URL is no longer available to clients. The mirror is deleted with its last snapshot, with its scheduled tasks. For rpm, only the selected release version is deleted.


## :octicons-browser-16: Browse repository content

From the **REPOSITORIES** tab:

**Step 1:** Locate the repository or mirror snapshot you want to explore.

**Step 2:** Click on the snapshot date to browse its content: tree structure, files search, text files (e.g. `InRelease`, `repomd.xml`) preview and files download.

[![Browse a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)

!!! info
    Mirror snapshots are read-only: packages cannot be uploaded or deleted, and metadata cannot be rebuilt.


## :material-file-upload: Upload package(s) in a repository

Not available for mirrors.

From the **REPOSITORIES** tab:

**Step 1:** Locate the repository snapshot in which you want to upload package(s).

**Step 2:** Click on the snapshot date to browse its content.

[![Browse a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)

**Step 3:** Click on the `Upload packages` button to open the **UPLOAD PACKAGES** panel.

[![Upload packages button](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/upload-packages-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/upload-packages-btn.png)

**Step 4:** Select the packages from your local computer and click the `Upload package(s)` button to upload.

[![Upload packages](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/upload-packages.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/upload-packages.png)

**Step 5:** To take the new package(s) into account and make them visible from clients, click on the `Rebuild metadata` button to open the **REBUILD METADATA** panel.

[![Rebuild repository metadata button](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata-btn.png)

**Step 6:** Choose whether to sign the repository metadata if required, then click the `Execute` button to launch the rebuild process.

[![Rebuild repository metadata](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata.png)

!!! info
    - If you need to increase the maximum upload size, consider increasing the `MAX_UPLOAD_SIZE` value when pulling the docker image (see [Prepare the environment variables](/getting-started/installation/#prepare-the-environment-variables)).
    - A `.deb` package must contain the architecture in its name (e.g. `nginx_1.23.3-1_amd64.deb`) otherwise it will not be recognized as a valid package.<br>Valid architectures are: `amd64`, `arm64`, `armel`, `armhf`, `i386`, `mips`, `mips64el`, `mipsel`, `ppc64el`, `s390x` and `all`.
    - A snapshot having a protected environment cannot be modified.


## :material-delete: Delete package(s) from a repository

Not available for mirrors.

From the **REPOSITORIES** tab:

**Step 1:** Locate the repository snapshot in which you want to delete package(s).

**Step 2:** Click on the snapshot date to browse its content.

[![Browse a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)

**Step 3:** Use the package dropdown list to search for the package(s) you need to delete, then use the checkbox to select them.

[![Delete packages](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/delete-packages.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/delete-packages.png)


**Step 4:** Click the `Delete` button to delete the selected packages.

**Step 5:** To take the changes into account, click on the `Rebuild metadata` button to open the **REBUILD METADATA** panel.

[![Rebuild repository metadata button](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata-btn.png)

**Step 6:** Choose whether to sign the repository metadata if required, then click the `Execute` button to launch the rebuild process.

[![Rebuild repository metadata](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository-metadata.png)

!!! info
    A snapshot having a protected environment cannot be modified.


## :material-autorenew: Rebuild repository metadata

Not available for mirrors.

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot you wish to rebuild.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the `Rebuild` button.

**Step 3:** Choose whether to sign the repository metadata if required. Click the `Execute now` button to launch the metadata rebuilding task.

[![Rebuild repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/rebuild-repository.png)


!!! info
    A snapshot having a protected environment cannot be rebuilt.


## :material-download: Install a repository on a client

Once a repository has been created, you can install it on a client to make it available for package installation.

From the **REPOSITORIES** tab:

**Step 1:** Select the repository or mirror snapshot(s) you wish to install.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the `Install` button. 

**Step 3:** Specify an environment to generate the installation command lines for.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/install-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/install-repository.png)

**Step 4:** Copy the command lines and paste them into your client terminal console.

### Mirrors

Clients use the mirror URL of an environment, and the **upstream GPG key** (not the Repomanager signing key). The `Install` panel also lists the GPG key fingerprints declared in the source repository, the key must be trusted on the host.

[![Install a mirror](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/install-mirror.png)](https://assets.repomanager.net/repomanager/6.2.0/usage/operations/install-mirror.png)

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

Mirrors cannot be added to [host profiles](/usage/hosts/#profiles), see [Mirrors limitations](/usage/repository-types/#mirrors-limitations).

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
