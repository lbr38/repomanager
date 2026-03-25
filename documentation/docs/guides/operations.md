## :material-plus-circle: Create a mirror repository

From the **REPOSITORIES** tab:

1. You must first add one or more source repos to mirror from. See `Add a new source repository <https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#add-a-new-source-repository>`_.
2. Click the ``Create a new repo`` button to show the **CREATE A NEW REPO** panel.
3. Select ``mirror`` as the repo type then complete all the required fields.
4. Confirm and execute.

// TO UPDATE
[![Create a mirror repository](https://github.com/lbr38/repomanager/assets/54670129/3e25ff10-0135-43b8-9acf-a7d4db73e1d3)](https://github.com/lbr38/repomanager/assets/54670129/3e25ff10-0135-43b8-9acf-a7d4db73e1d3)

!!! info "Notes"
    **Custom repo name** field is optionnal, if none is specified then the mirror repository will have the same name as the source repository (e.g source repo is named `nginx` then the mirror repository will also be named `nginx`).


## :material-plus-circle: Create a local repository

From the **REPOSITORIES** tab:

1. Click the ``Create a new repo`` button to show the **CREATE A NEW REPO** panel.
2. Select ``local`` as the repo type then complete all the required fields.
3. Confirm and execute.

// TO UPDATE
[![Create a local repository](https://github.com/lbr38/repomanager/assets/54670129/39ed988f-e24a-4493-a5c8-9947d25bbb16)](https://github.com/lbr38/repomanager/assets/54670129/39ed988f-e24a-4493-a5c8-9947d25bbb16)

!!! info "Notes"
    Local repositories are always empty when created, you must then add you own `rpm` or `deb` packages by uploading them. See [Upload package(s) in a repository](#upload-packages-in-a-repository).


## :material-autorenew: Update an existing mirror repository

From the **REPOSITORIES** tab:

1. Select the repository snapshot from which you want to update the mirror.
2. Click the ``Update`` button.
3. Specify mirroring parameters.
4. Confirm and execute.

// TO UPDATE
[![Update a mirror repository](https://github.com/lbr38/repomanager/assets/54670129/0dce8a57-a5fc-47c0-afba-6145f3c5f648)](https://github.com/lbr38/repomanager/assets/54670129/0dce8a57-a5fc-47c0-afba-6145f3c5f648)

!!! info "Notes"
    Only mirror repository can be updated.


## :material-arrow-left-top: Point an environment to a repository

From the **REPOSITORIES** tab:

1. Select the repository snapshot you want to point to an environment.
2. Click the ``Point an environment`` button.
3. Specify the environment that will point to the snapshot.
4. Confirm and execute.

// TO UPDATE
[![Point an environment to a repository](https://github.com/lbr38/repomanager/assets/54670129/02a38d03-9678-4fe8-af4d-88c8d323067b)](https://github.com/lbr38/repomanager/assets/54670129/02a38d03-9678-4fe8-af4d-88c8d323067b)


## :material-delete: Remove an environment from a repository

From the **REPOSITORIES** tab:

1. Click the Delete icon next to the environment you want to remove.
2. Confirm by clicking the ``Delete`` button.

// TO UPDATE
[![Remove an environment from a repository](https://github.com/lbr38/repomanager/assets/54670129/afe0f861-ca94-451a-acdc-a24d995905cb)](https://github.com/lbr38/repomanager/assets/54670129/afe0f861-ca94-451a-acdc-a24d995905cb)


## :octicons-copy-16: Duplicate a repository

From the **REPOSITORIES** tab:

1. Select the repository snapshot you want to duplicate.
2. Click the ``Duplicate`` button.
3. Specify the name of the new repository that will be created from the selected repository.
4. Confirm and execute.

// TO UPDATE
[![Duplicate a repository](https://github.com/lbr38/repomanager/assets/54670129/94eef3af-93b5-4832-9015-a22671b35483)](https://github.com/lbr38/repomanager/assets/54670129/94eef3af-93b5-4832-9015-a22671b35483)


## :material-delete: Delete a repository snapshot

From the **REPOSITORIES** tab:

1. Select the repository snapshot you want to delete.
2. Choose **Delete**
3. Confirm and execute

// TO UPDATE
[![Delete a repository snapshot](https://raw.githubusercontent.com/lbr38/resources/main/screenshots/repomanager/documentation/delete-snapshot/delete-snapshot.gif)](https://raw.githubusercontent.com/lbr38/resources/main/screenshots/repomanager/documentation/delete-snapshot/delete-snapshot.gif)


## :octicons-browser-16: Browse repository content

From the **REPOSITORIES** tab:

1. Locate the repository snapshot you want to explore. It can be either a local or a mirror repository.
2. Click on the ``date`` of the snapshot to browse its content.

// TO UPDATE
[![Browse repository content](https://github.com/lbr38/repomanager/assets/54670129/32f7f799-5be0-48b7-bace-231e9d9c01aa)](https://github.com/lbr38/repomanager/assets/54670129/32f7f799-5be0-48b7-bace-231e9d9c01aa)


## :material-file-upload: Upload package(s) in a repository

From the **REPOSITORIES** tab:

1. Locate the repo snapshot date in which you want to upload package(s). It can be either a local or a mirror repository.
2. Click on the ``date`` of the snapshot to browse its content.
3. Use the **UPLOAD PACKAGES** right panel to select and upload packages from your local PC.
   Valid packages MIME types are: '**application/x-rpm**' (.rpm file) and '**application/vnd.debian.binary-package**' (.deb file).
4. To take the new package(s) into account and make them visible from clients, use the **REBUILD REPO** right panel to rebuild the repository's metadata.

// TO UPDATE
[![Upload packages in a repository](https://github.com/user-attachments/assets/da48e118-e8c3-449e-90d7-86b2b6b4f0b1)](https://github.com/user-attachments/assets/da48e118-e8c3-449e-90d7-86b2b6b4f0b1)

!!! info "Notes"
    - If you need to increase the maximum upload size, consider increasing the MAX_UPLOAD_SIZE value when running the docker container (see installation process).
    - `.deb` package must contain the architecture in its name (e.g. `nginx_1.23.3-1_amd64.deb`) otherwise it will not be recognized as a valid package. Valid architectures are: `amd64`, `arm64`, `armel`, `armhf`, `i386`, `mips`, `mips64el`, `mipsel`, `ppc64el`, `s390x` and `all`.


## :material-delete: Delete package(s) from a repository

From the **REPOSITORIES** tab:

1. Locate the repo snapshot in which you want to delete package(s). It can be either a local or a mirror repository.
2. Click on the ``date`` of the snapshot to browse its content.
3. Use the package dropdown list to search for the package(s) you need to delete, then use the checkbox to select them.
4. Use the **Delete** button to delete packages.
5. To take the changes into account, use the **REBUILD REPO** right panel to rebuild the repository's metadata.

// TO UPDATE
[![Delete packages from a repository](https://github.com/user-attachments/assets/30d8f658-6c1f-4219-86fa-2ef09d61b0c0)](https://github.com/user-attachments/assets/30d8f658-6c1f-4219-86fa-2ef09d61b0c0)


## :material-autorenew: Rebuild repository metadata

From the **REPOSITORIES** tab:

1. Select the repository snapshot you wish to rebuild. It can be either a local or a mirror repository.
2. Click the ``Rebuild`` button.
3. Confirm and execute.

// TO UPDATE
[![Rebuild repository metadata](https://github.com/lbr38/repomanager/assets/54670129/dfa511a8-307b-41e0-8ddb-e87baa8fce10)](https://github.com/lbr38/repomanager/assets/54670129/dfa511a8-307b-41e0-8ddb-e87baa8fce10)


## :material-download: Install a repository on a client

Once a repository has been created, you can install it on a client to make it available for package installation.

From the **REPOSITORIES** tab:

1. Select the repository snapshot you wish to install.
2. Click the **Install** button. 
3. Select the environment.
4. Copy the command lines and paste them in your client terminal console.
