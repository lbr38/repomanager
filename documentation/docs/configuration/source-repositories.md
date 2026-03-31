A **source repository** is a remote repository of packages (``deb`` or ``rpm``) from a third-party publisher (e.g. **Debian official repos**).

Typically, this is the repository from which you will create a mirror.


## Add a source repository manually

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the `Manually add` button to add a new source repository.

[![Add source repository manually](https://assets.repomanager.net/repomanager/configuration/source-repositories/add-source-repository-1.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/add-source-repository-1.png)

**Step 3:** Select the type of repository then define a name to identify the source repository and specify its root URL.

[![Add source repository manually](https://assets.repomanager.net/repomanager/configuration/source-repositories/add-source-repository-2.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/add-source-repository-2.png)

**Step 4:** Click the `Add` button to add the source repository to the list of available source repositories.

**Step 5:** You should now edit the source repository to add more information like distributions and components or release version and import its GPG signing key if any. It is recommended to add as much information as you can as this is used for suggestions when creating a mirror. See [Edit a source repository](#edit-a-source-repository).

!!! info "Notes"

    **Supported RPM repository URLs**

      - Please only provide **direct** URLs to the source repository. Mirrorlist URLs are **not supported**.
      - ``$releasever`` and ``$basearch`` yum variables inside URLs **are supported**. They will be automatically replaced during the repo mirroring process by the value you have defined for **Release version** and **Architecture** (when creating or updating a repo).
      - Examples of supported URLs:

        `https://dl.fedoraproject.org/pub/epel/$releasever/Everything/$basearch`
        
        `http://repo.mysql.com/yum/mysql-5.7-community/el/7/$basearch`


## Import source repositories from predefined or custom lists

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the `Import` button.

[![Import source repositories](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-source-repository-1.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-source-repository-1.png)

**Step 3:** Select the source repositories list you want to import (e.g. **Alma Linux official repositories**).

Predefined lists content is public and can be seen [here](https://github.com/lbr38/repomanager/tree/main/www/templates/source-repositories). More lists can be added in the future. You can make your own custom list and import it, see [Create custom source repositories list](#create-custom-source-repositories-list).

[![Import source repositories](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-source-repository-2.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-source-repository-2.png)

**Step 4:** Click the `Import` button to import the selected source repositories.

This will create new source repositories in the list and import their GPG signing keys if any. If the import fails, it may be due to a temporary issue with the keyserver (try again later).

!!! info "Notes"

    Importing source repositories will overwrite existing source repositories with the same name.


## Create custom source repositories list

You can create your own custom list with all predefined information and import it through the Repomanager web interface.

Custom lists are made for private purposes, they will not be shared with the community. If you want to share your list, you can create a pull request to add it to the predefined lists.

**Step 1:** Source repositories lists are defined in YAML format and must be named with `.yml` extension. You can start from templates here [deb template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/deb/deb-repository.yml.template) / [rpm template](https://github.com/lbr38/repomanager/blob/main/www/templates/source-repositories/rpm/rpm-repository.yml.template) or take example from existing lists here [predefined lists](https://github.com/lbr38/repomanager/tree/main/www/templates/source-repositories) to build your own file.

**Step 2:** Once you have created your custom file, you will have to copy it inside the container in a specific directory of the `repomanager-data` volume. You can also import it through the API, for automation purposes, see [Import source repositories lists from API](#import-source-repositories-lists-from-api).

Here is an example with a custom **deb** list. From the Docker host, it must be copied into the ``deb`` directory under ``/var/lib/docker/volumes/repomanager-data/templates/source-repositories/``:

```
# Copy
cp my-custom-deb-list.yml /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml

# Set permissions
chown 33:33 /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml
chmod 640 /var/lib/docker/volumes/repomanager-data/templates/source-repositories/deb/my-custom-deb-list.yml
```

**Step 3:** Your custom list is now ready for import through the web interface: 

[![Custom source repositories list ready for import](https://github.com/user-attachments/assets/842aa0d4-1e08-4acb-871f-4b0380345e13)](https://github.com/user-attachments/assets/842aa0d4-1e08-4acb-871f-4b0380345e13)

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

[![Source repositories button](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click a source repository to edit it.

[![Edit a source repository](https://assets.repomanager.net/repomanager/configuration/source-repositories/edit-source-repository-1.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/edit-source-repository-1.png)

**Step 3:** Update the source repository information, like distributions and components or release version.

Import their GPG signing key if any.

It is recommended to add as much information as you can as this is used for suggestions when creating a mirror.

[![Edit a source repository](https://assets.repomanager.net/repomanager/configuration/source-repositories/edit-source-repository-2.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/edit-source-repository-2.png)

!!! info "Notes"

    You can provide an **SSL certificate and private key** to authenticate to the source repository. This is useful if the source repository access is protected (for example, Red Hat official repositories ``cdn.redhat.com`` require a private key/certificate to access them).


## Delete a source repository

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Select the source repository you want to delete and click the `Delete` button.

[![Delete a source repository](https://assets.repomanager.net/repomanager/configuration/source-repositories/delete-source-repository.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/delete-source-repository.png)

!!! info "Notes"

    GPG signing key(s) related to the source repository will not be deleted.


## Import a source repository GPG signing key

From the **REPOSITORIES** tab:

**Step 1:** Use the `Source repositories` button to show the **SOURCE REPOSITORIES** panel.

[![Source repositories button](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/source-repositories-btn.png)

**Step 2:** Click the source repository to edit it.

- If it is a `deb` repository, click the distribution you want to import the GPG signing key for.
- If it is a `rpm` repository, click the release version you want to import the GPG key for.

**Step 3:** Scroll down to **IMPORT GPG KEY** then paste the GPG key **URL**, **fingerprint** or **plain text**.

[![Import a GPG key](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-gpg-key.png)](https://assets.repomanager.net/repomanager/configuration/source-repositories/import-gpg-key.png)

**Step 4:** Click the `Import` button to import the GPG key.


## Non-compliant deb source repositories

Some ``.deb`` repositories do not follow the standard layout (``<root_url>/dists/<distribution>/...``) and causes issues when trying to sync them with Repomanager. For example, repositories with packages and metadata stored directly under the root URL (``<root_url>/xxx.deb``) will fail to sync.

If you encounter such a repository, you can try to enable the ``NON-COMPLIANT REPOSITORY`` parameter when editing the source repository. Repomanager will try to sync from the root url without adding the ``/dists/<distribution>/`` part.

[![Non-compliant repository parameter](https://github.com/user-attachments/assets/c046b1c9-993b-483f-b496-0c7ec51cec24)](https://github.com/user-attachments/assets/c046b1c9-993b-483f-b496-0c7ec51cec24)

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

You can now import the certificate, private key and CA certificate content into Repomanager (see the **Notes** under [Edit a source repository](#edit-a-source-repository))

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
