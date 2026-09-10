## :material-plus-circle: Create a mirror repository

From the **REPOSITORIES** tab:

**Step 1:** You must first add one or more source repositories to mirror from. See [Source repositories](/configuration/source-repositories/).

**Step 2:** Click the ``Create a new repository`` button to show the **NEW REPOSITORY** panel.

[![Create a mirror repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)

**Step 3:** Select ``mirror`` as the repository type then complete all the required fields.

[![Create a mirror repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository.png)

**Step 4:** Click the `Execute now` button to launch the mirroring task.

!!! info
    The `REPOSITORY NAME` field is optional. If none is specified, the repository will have the same name as the source repository (e.g., if the source repository is named `nginx`, then the mirror repository will also be named `nginx`).


## :material-plus-circle: Create a local repository

From the **REPOSITORIES** tab:

**Step 1:** Click the ``Create a new repository`` button to show the **NEW REPOSITORY** panel.

[![Create a local repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-repository-btn.png)

**Step 2:** Select ``local`` as the repository type then complete all the required fields.

[![Create a local repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-local-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/create-new-local-repository.png)

**Step 3:** Click the `Execute now` button to launch the repository creation task.

!!! info
    Local repositories are always empty when created. You must then add your own `rpm` or `deb` packages by uploading them. See [Upload package(s) in a repository](#upload-packages-in-a-repository).


## :material-autorenew: Update an existing mirror repository

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot from which you want to update the mirror.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Update`` button.

**Step 3:** Specify the mirroring parameters.

[![Update repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/update-repository-2.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/update-repository-2.png)

**Step 4:** Click the `Execute now` button to launch the mirroring task.


## :material-arrow-left-top: Point an environment to a repository

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot you want to point an environment to.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Point an environment`` button.

**Step 3:** Specify the environment that will point to the snapshot.

[![Point environment](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/point-environment.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/point-environment.png)

**Step 4:** Click the `Execute now` button to launch the task.

!!! info
    A protected environment cannot be pointed to a repository snapshot.


## :material-delete: Remove an environment from a repository

From the **REPOSITORIES** tab:

**Step 1:** Select the environment you want to remove from a repository snapshot.

[![Remove environment](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/remove-environment.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/remove-environment.png)

**Step 2:** Click the ``Remove`` button.

!!! info
    A protected environment cannot be removed from a repository snapshot.


## :octicons-copy-16: Duplicate a repository

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot you want to duplicate.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Duplicate`` button.

**Step 3:** Specify the name of the new repository that will be created from the selected repository snapshot.

[![Duplicate a repository](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/duplicate-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/duplicate-repository.png)

**Step 4:** Click the `Execute now` button to launch the duplication task.


## :material-delete: Delete a repository snapshot

From the **REPOSITORIES** tab:

**Step 1:** Select the repository snapshot you want to delete.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the ``Delete`` button.

**Step 3:** Click the `Execute now` button to launch the deletion task.

!!! info
    A snapshot having a protected environment cannot be deleted.


## :octicons-browser-16: Browse repository content

From the **REPOSITORIES** tab:

**Step 1:** Locate the repository snapshot you want to explore.

**Step 2:** Click on the snapshot date to browse its content.

[![Browse a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/browse-snapshot.png)


## :material-file-upload: Upload package(s) in a repository

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

**Step 1:** Select the repository snapshot you wish to install.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/select-snapshot.png)

**Step 2:** Click the `Install` button. 

**Step 3:** Specify an environment to generate the installation command lines for.

[![Select a snapshot](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/install-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/usage/operations/install-repository.png)

**Step 4:** Copy the command lines and paste them into your client terminal console.

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
