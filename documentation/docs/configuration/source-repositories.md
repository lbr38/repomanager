A **source repository** is a remote repository of packages (``deb``, ``rpm``, ``arch``, ``alpine`` or ``openbsd``) from a third-party publisher (e.g. **Debian official repos**). ``arch`` (Arch Linux / pacman), ``alpine`` (Alpine Linux / apk) and ``openbsd`` (OpenBSD) source repositories can only be used by [mirrors](/usage/repository-types/#mirrors).

Typically, this is the repository from which you will create a curated repository or a mirror.


## Add a source repository manually

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the `Manually add` button to add a new source repository.

[![Add source repository manually](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/add-source-repository-1.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/add-source-repository-1.png)

**Step 3:** Select the type of repository then define a name to identify the source repository and specify its root URL.

[![Add source repository manually](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/add-source-repository-2.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/add-source-repository-2.png)

**Step 4:** Click the `Add` button to add the source repository to the list of available source repositories.

**Step 5:** You should now edit the source repository to add more information like distributions and components or release version and import its signing keys if any (GPG keys, RSA keys for an alpine source repository, signify keys for an openbsd one). It is recommended to add as much information as you can as this is used for suggestions when creating a curated repository or a mirror. See [Edit a source repository](#edit-a-source-repository).

!!! info "RPM repositories URLs information"

      - Please only provide **direct** URLs to the source repository. Mirrorlist URLs are **not supported**.
      - ``$releasever`` and ``$basearch`` variables inside URLs **are supported**. They will be automatically replaced during the sync process by the value you have defined for **Release version** and **Architecture** (when creating or updating a repo).
      - Examples of supported URLs:

        `https://dl.fedoraproject.org/pub/epel/$releasever/Everything/$basearch`
        
        `http://repo.mysql.com/yum/mysql-5.7-community/el/7/$basearch`

!!! info "DEB repositories URLs information"

    - ``$dist`` and ``$component`` variables inside URLs **are supported**. They will be automatically replaced during the sync process by the value you have defined for **Distribution** and **Component** (when creating or updating a repo).


!!! info "ARCH repositories URLs information"

    - Use the ``Server`` URL of a pacman mirrorlist. ``$repo`` and ``$arch`` variables inside URLs **are supported**, they are replaced during the sync by each selected **Repository** (e.g. ``core``, ``extra``) and **Architecture**.
    - Examples of supported URLs:

        `https://geo.mirror.pkgbuild.com/$repo/os/$arch`

        `http://mirror.archlinuxarm.org/$arch/$repo`

    - Edit the source repository to define its repositories and architectures, they are used for suggestions when creating a mirror.
    - Arch Linux official databases are not signed (packages are signed and checked by pacman on clients). If the source repository signs its databases, import its GPG keys when editing it to verify them during the sync, see [Import a source repository GPG signing key](#import-a-source-repository-gpg-signing-key).


!!! info "ALPINE repositories URLs information"

    - Use the repository URL of `/etc/apk/repositories`. ``$branch`` and ``$repo`` variables inside URLs **are supported**, they are replaced during the sync by the **Branch** of the mirror (e.g. ``v3.24``, ``edge``) and each selected **Repository** (e.g. ``main``, ``community``). As apk does, the **Architecture** is appended to the URL: the URL must contain the ``<arch>/APKINDEX.tar.gz`` index.
    - Examples of supported URLs:

        `https://dl-cdn.alpinelinux.org/alpine/$branch/$repo`

    - A third-party repository with a single branch or a single repository can omit the variables (e.g. `https://apk.corretto.aws`): the branch or the repositories are then not needed, a repository without branch is published under `https://<FQDN>/mirror/alpine/<name>/`, and without repositories it is mirrored as a single repository.
    - Edit the source repository to define its branches, repositories and architectures, they are used for suggestions when creating a mirror.
    - Alpine Linux indexes are signed with RSA keys, not GPG keys. Import the public keys of the source repository from their URL or their content when editing it (see [Import an alpine or openbsd source repository key](#import-an-alpine-or-openbsd-source-repository-key)), or with the ``keys`` field of the lists to import (see the `alpine-repository.yml.template` template). The name of a key must be the name of the key file on clients (``/etc/apk/keys/<name>``), as the signature references it: it is the file name of the URL by default. Only the keys of the source repository are used to verify its indexes.


!!! info "OPENBSD repositories URLs information"

    - Use the root of an OpenBSD mirror, as in `/etc/installurl`: the URL must contain the `<version>/`, `<version>/packages/<arch>/` and `syspatch/<version>/` directories. Variables are not used.
    - Examples of supported URLs:

        `https://cdn.openbsd.org/pub/OpenBSD`

    - Edit the source repository to define its versions (e.g. ``8.0``) and architectures (platforms, as named by OpenBSD, e.g. ``amd64``, ``arm64``), they are used for suggestions when creating a mirror. Snapshots are not supported.
    - OpenBSD releases, packages and binary patches are signed with signify keys, not GPG keys: each directory contains the list of its files with their checksum, signed in `SHA256.sig`. Import the public keys of the source repository from their URL or their content when editing it (see [Import an alpine or openbsd source repository key](#import-an-alpine-or-openbsd-source-repository-key)), or with the ``keys`` field of the lists to import (see the `openbsd.yml` list): the base, pkg and syspatch keys of each version (e.g. ``openbsd-80-pkg.pub``), as installed in `/etc/signify/` on clients. Only the keys of the source repository are used to verify its signatures.


## Import source repositories from predefined or custom lists

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the `Import` button.

[![Import source repositories](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-source-repository-1.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-source-repository-1.png)

**Step 3:** Select the source repositories list you want to import (e.g. **Alma Linux official repositories**).

Predefined lists content is public and can be seen [here](https://github.com/lbr38/repomanager/tree/main/www/templates/source-repositories). More lists can be added in the future. You can make your own custom list and import it, see [Create custom source repositories list](#create-custom-source-repositories-list).

[![Import source repositories](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-source-repository-2.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-source-repository-2.png)

**Step 4:** Click the `Import` button to import the selected source repositories.

This will create new source repositories in the list and import their GPG signing keys if any. If the import fails, it may be due to a temporary issue with the keyserver (try again later).

!!! info "Notes"

    Importing source repositories will overwrite existing source repositories with the same name.


## Create custom source repositories list

You can create your own custom list with all predefined information and import it through the Repomanager web interface.

Custom lists are made for private purposes, they will not be shared with the community. If you want to share your list, you can create a pull request to add it to the predefined lists.

**Step 1:** Source repositories lists are defined in YAML format and must be named with `.yml` extension. You can start from templates here [deb template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/deb/deb-repository.yml.template) / [rpm template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/rpm/rpm-repository.yml.template) / [arch template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/arch/arch-repository.yml.template) or take example from existing lists here [predefined lists](https://github.com/lbr38/repomanager/tree/main/www/templates/source-repositories) to build your own file.

**Step 2:** Once you have created your custom file, you will have to copy it inside the container in a specific directory of the `repomanager-data` volume. You can also import it through the API, for automation purposes, see [Import source repositories lists from API](#import-source-repositories-lists-from-api).

Here is an example with a custom **deb** list. From the Docker host, it must be copied into the ``deb`` directory (``rpm`` or ``arch`` for other types) under ``/var/lib/docker/volumes/repomanager-data/templates/source-repositories/``:

```
# Copy
cp my-custom-deb-list.yml /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml

# Set permissions
chown 33:33 /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml
chmod 640 /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml
```

**Step 3:** Your custom list is now ready for import through the web interface:

[![Custom source repositories list ready for import](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-custom-source-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-custom-source-repository.png)

**Step 4:** Click the `Import` button to import the source repositories from your custom list.

## Import source repositories lists from API

You can import source repositories lists through the API, for automation purposes. This can be helpful for repositories that require frequent updates, like SSL authentication certificates updates.

**Step 1:** First create or update your custom list in YAML format, see [Create custom source repositories list](#create-custom-source-repositories-list).

**Step 2:** Generate an API token from an admin account, if not already done, see [API key](/reference/api/#api-key).

**Step 3:** Use the following API endpoint to import the list:

```
curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <API_KEY>" -F "template=@<PATH_TO_CUSTOM_LIST_FILE>" https://<REPOMANAGER_FQDN>/api/v2/source/<TYPE>/import/
```

Replace the following placeholders:

| Placeholder | Description |
|-------------|-------------|
| ``<API_KEY>`` | Your API key |
| ``<PATH_TO_CUSTOM_LIST_FILE>`` | The path to your custom list `.yml` file |
| ``<REPOMANAGER_FQDN>`` | Repomanager URL |
| ``<TYPE>`` | The type of source repositories list you want to import (`deb` or `rpm`) |

The API will return a JSON response with ``201`` status code if the import was successful. Otherwise it will return an error message with ``400`` status code:

```
{"return":201,"results":"Source repositories imported successfully"}
```

## Edit a source repository

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click a source repository to edit it.

Source repositories are listed by type, one tab per type (``RPM``, ``DEB``, ``Arch``, ``Alpine``, ``OpenBSD``) with its number of source repositories. Use the search field to find a source repository by name, URL or description: the tabs then show the number of matching source repositories.

[![Edit a source repository](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/edit-source-repository-1.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/edit-source-repository-1.png)

**Step 3:** Update the source repository information, like distributions and components or release version.

Import their signing keys if any, see [Import a source repository GPG signing key](#import-a-source-repository-gpg-signing-key) and [Import an alpine or openbsd source repository key](#import-an-alpine-or-openbsd-source-repository-key).

It is recommended to add as much information as you can as this is used for suggestions when creating a curated repository or a mirror.

[![Edit a source repository](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/edit-source-repository-2.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/edit-source-repository-2.png)

!!! info "Notes"

    You can provide an **SSL certificate and private key** to authenticate to the source repository. This is useful if the source repository access is protected (for example, Red Hat official repositories ``cdn.redhat.com`` require a private key/certificate to access them).

    You can also provide a **username and password** if the source repository is protected by HTTP basic authentication. Credentials are sent to the source repository host only (not to other hosts in case of redirection). Empty the username to remove the credentials. Basic authentication can also be defined in custom lists with the ``basic-authentication`` section (``username`` and ``password``). Prefer this over embedding credentials in the URL.


## Delete a source repository

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Select the source repository you want to delete and click the `Delete` button.

[![Delete a source repository](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/delete-source-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/delete-source-repository.png)

!!! info "Notes"

    GPG signing key(s) related to the source repository will not be deleted: they stay in Repomanager keyring, see [Manage signing keys](#manage-signing-keys) to delete them. The RSA and signify keys of an alpine or openbsd source repository are deleted with it.


## Import a source repository GPG signing key

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the source repository to edit it.

- If it is a `deb` repository, click the distribution you want to import the GPG signing key for.
- If it is a `rpm` repository, click the release version you want to import the GPG key for.
- If it is an `arch` repository, the GPG keys are in the source repository form.

**Step 3:** Scroll down to **IMPORT GPG KEY** then paste the GPG key **URL**, **fingerprint** or **plain text**.

[![Import a GPG key](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-gpg-key.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/import-gpg-key.png)

**Step 4:** Click the `Import` button to import the GPG key.

!!! info "Notes"

    GPG keys are imported in Repomanager keyring, which is shared by all the deb, rpm and arch source repositories: a key verifies the signature of any of them. Removing a GPG key from a distribution, a release version or an arch source repository keeps it in the keyring, see [Manage signing keys](#manage-signing-keys) to delete it.


## Import an alpine or openbsd source repository key

Alpine Linux indexes are signed with RSA keys and OpenBSD files lists with signify keys. These keys are stored in each source repository: only its own keys verify its signatures.

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the alpine or openbsd source repository to edit it.

**Step 3:** Scroll down to **IMPORT RSA KEY** (alpine) or **IMPORT SIGNIFY KEY** (openbsd) then paste the key **URL** or its content (**plain text**).

The **KEY NAME** is the name of the key file on clients (e.g. ``alpine-devel@lists.alpinelinux.org-6165ee59.rsa.pub`` in ``/etc/apk/keys/``, ``openbsd-80-pkg.pub`` in ``/etc/signify/``). It is required when importing from plain text, and it is the name of the downloaded file by default when importing from URL.

**Step 4:** Click the `Import` button to import the key.


## Manage signing keys

The **SIGNING KEYS** panel lists all the public keys used to verify the signature of the source repositories.

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the `Manage keys` button, at the bottom of the panel, to show the **SIGNING KEYS** panel.

**Step 3:** Select the type of keys, then click a key to show its details:

- **GPG**: the keys of Repomanager keyring, with their fingerprint, user IDs, status, algorithm, creation and expiration dates, subkeys and the source repositories referencing them (deb distributions, rpm release versions and arch source repositories). Expired or revoked keys and keys expiring within 90 days are labeled. A key that no source repository references is labeled `Not referenced`: it is still trusted to verify signatures.
- **RSA** (alpine) and **Signify** (openbsd): the keys of each source repository, with their size and SHA256 fingerprint (RSA) or their key number and comment (signify), their link and their content.

Use the search field to find keys (by name, user ID, fingerprint, link or source repository). GPG keys can also be filtered by status.

**Step 4:** To delete keys, select them then click the `Delete` button (GPG keys, deleted from Repomanager keyring) or the `Remove` button (RSA and signify keys, removed from their source repository).

!!! warning

    A deleted key can no longer verify signatures: syncing a mirror whose source repository is signed with this key fails if the signature check is enabled. The details of a GPG key list the source repositories referencing it, and the confirmation message gives how many of the selected keys are referenced.


## Non-compliant deb source repositories

Some ``.deb`` repositories do not follow the standard layout (``<root_url>/dists/<distribution>/...``) and causes issues when trying to sync them with Repomanager. For example, repositories with packages and metadata stored directly under the root URL (``<root_url>/xxx.deb``) will fail to sync.

If you encounter such a repository, you can try to enable the ``NON-COMPLIANT REPOSITORY`` parameter when editing the source repository. Repomanager will try to sync from the root url without adding the ``/dists/<distribution>/`` part.

[![Non-compliant repository parameter](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/non-compliant-repository.png)](https://assets.repomanager.net/repomanager/6.0.0/configuration/source-repositories/non-compliant-repository.png)

If it still does not work, please open an issue on GitHub with the repository details so it can be investigated and fixed if possible.


## Use case: Redhat repositories

Related issue: [https://github.com/lbr38/repomanager/issues/169](https://github.com/lbr38/repomanager/issues/169)

To be able to sync Red Hat repositories (under ``cdn.redhat.com``), you will need a **Red Hat 8** or **Red Hat 9** host/server with a valid subscription (see [https://access.redhat.com/solutions/253273](https://access.redhat.com/solutions/253273))

Once your host is registered, you should have access to Red Hat rpm repositories (you can try with ``dnf update``).

Just for you to understand: Red Hat repositories are private and only accessible with a certificate and private key (this is what the subscription provides). Now that you are registered, you can retrieve the certificate and private key content and import them into Repomanager to be able to sync Red Hat repositories, see below.

From your registered host, retrieve the certificate, private key and Red Hat CA certificate content. You can find their location in ``/etc/yum.repos.d/redhat.repo``:

[![Redhat certificate and private key location](https://github.com/lbr38/repomanager/assets/54670129/f342159a-b878-4754-b35b-92437f382976)](https://github.com/lbr38/repomanager/assets/54670129/f342159a-b878-4754-b35b-92437f382976)

Simply ``cat`` the content of each file and copy/paste it to a text editor (lines from ``-----BEGIN PGP PUBLIC KEY BLOCK-----`` to ``-----END PGP PUBLIC KEY BLOCK-----``).

```
cat /etc/rhsm/ca/redhat-uep.pem
cat /etc/pki/entitlement/733946906105629479-key.pem
cat /etc/pki/entitlement/733946906105629479.pem
```

You can now manually import the certificate, private key and CA certificate content into Repomanager (see the **Notes** under [Edit a source repository](#edit-a-source-repository)) or automate the import with the API, see [Import source repository through the API](#import-source-repository-through-the-api).


## Import source repository through the API

You can use the following Python script to import source repository through the Repomanager API. This can be useful to automate the import or the update of source repositories.

This can be useful for repositories that frequently update their authentication certificates, like Red Hat official repositories. You can schedule this script to run periodically (e.g. with a cron job) to keep the source repository up to date with the latest certificates and private keys, which is required to be able to sync repositories that require SSL authentication.

**Step 1:** Install python3 dependencies on the host where you will run the script.

```bash
apt install python3-yaml python3-requests
```

**Step 2:** Download the script.

```bash
wget https://raw.githubusercontent.com/lbr38/repomanager/main/www/bin/api-import-from-template.py
```

**Step 3:** Prepare your own YAML template or use an existing one (for example [Redhat template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/rpm/redhat.yml)).

If you intend to update the SSL certificate and private key:
- Make sure the ``ssl-authentication`` section exists and is uncommented in the template, otherwise certificates and private key will not be updated.
- Prepare the certificate and private key files on your host.

**Step 4:** Run the script with the required arguments. You will find below the usage and some examples.

```
Usage: api-import-from-template.py [OPTIONS]
Options:

  Repomanager:
  --url                          URL to Repomanager. e.g. https://repomanager.example.com
  --api-token                    API token to authenticate to Repomanager

  Template:
  --template-path                Path to the source repository template to import
  --repository-type              (optional) Type of repository included in the template (rpm, deb or arch), checked against the type of the template

  Certificates and private key paths. You can provide direct paths to the files, specify a directory and patterns to match the files or a combination of both
  --certs-dir                    Directory where the certificates are stored
  --certificate-pattern          Pattern to match the certificate in the certs directory. Pattern can be a regular expression (e.g. "*.crt$")
  --private-key-pattern          Pattern to match the private key in the certs directory. Pattern can be a regular expression (e.g. "*.key$")
  --ca-certificate-pattern       Pattern to match the CA certificate in the certs directory. Pattern can be a regular expression (e.g. "*.crt$")
  --certificate-path             Direct path to the certificate
  --private-key-path             Direct path to the private key
  --ca-certificate-path          Direct path to the CA certificate
```

**Example 1:** Update the ubuntu source repositories by using a template file:

```bash
python3 api-import-from-template.py --url https://repomanager.example.com --api-token ak_xxxxxxxxxxxxxxxxx --template-path /tmp/templates/ubuntu.yml
```

**Example 2:** Update the redhat source repositories by using a custom template file and providing a directory with the new certificate and private key, using patterns to match them:

```bash
python3 api-import-from-template.py --url https://repomanager.example.com --api-token ak_xxxxxxxxxxxxxxxxx --template-path /tmp/templates/redhat.yml --certs-dir /tmp/certs/ --certificate-pattern ".crt$" --private-key-pattern ".key$" --ca-certificate-pattern ".ca$"
```

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
