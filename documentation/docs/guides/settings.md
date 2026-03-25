## MAIN CONFIGURATION

### SYSTEM

General information about the system related to the Docker container.

### GLOBAL SETTINGS

| Parameter | Description |
|---|---|
| `Hostname` | Repomanager FQDN, defined when pulling the docker image. |
| `Timezone` | Specify your timezone. This is especially useful to ensure that scheduled tasks run at the specified time. |
| `Default contact` | Default contact for receiving emails. Currently, only scheduled tasks and their reminders are sending emails. You can specify multiple recipients. |
| `Use proxy` | If your Repomanager instance runs behind a proxy, you can specify the proxy URL to use to access the internet (optionnal). Example: https://myproxy.com:8080. |
| `Task queuing` | Enable or disable the task queuing. |
| `Maximum number of simultaneous tasks` | Maximum number of tasks that can run simultaneously. The other tasks will be queued. |
| `Task execution memory limit (in MB)` | Set PHP memory limit for task execution. It is recommended to set this value to a higher value when mirroring large repositories (lot of packages may require more memory to be synced as package list is loaded in memory). |


## REPOSITORIES

### GLOBAL SETTINGS

| Parameter | Description |
|---|---|
| `Repos URL` | Root URL for accessing repositories. This URL is not browseable for security reasons. To explore the content of a repository snapshot, use the snapshot browsing system, see [Browse repository content](https://github.com/lbr38/repomanager/wiki/02.-Operations-on-repositories#browse-repository-content). If you still want to enable the web browser directory listing, see: [Enable repository web browsing](https://github.com/lbr38/repomanager/wiki/12.-Miscellaneous-and-tips#enable-repository-web-browsing) |
| `Retention` | Maximum number of unused snapshots to keep per repository. Set to 0 to disable retention. |
| `Repository configuration file name prefix` | Prefix added to repository configuration files when installing on client hosts (e.g. `<myprefix>-debian.list` or `<myprefix>-nginx.repo`). Leave empty if you want no prefix. |


### GLOBAL MIRRORING SETTINGS

| Parameter | Description |
|---|---|
| `Package download timeout (in seconds)` | Maximum time allowed to download a package during a mirroring process. |

### RPM

| Parameter | Description |
|---|---|
| `Enable RPM repositories` | Enable RPM package repositories. |
| `Sign packages with GPG` | Enable the signing of RPM packages when creating a RPM package repository (mirror or local repository). Packages will be signed using the GPG signing key specified by the `GPG key Id` parameter. |
| `Default release version` | Default release version to use when creating RPM repositories. |
| `Default package architecture` | Default package architecture to use when creating RPM repositories. |

**RPM MIRRORING SETTINGS**

| Parameter | Description |
|---|---|
| `When package signature is missing` | Package retrieved from a remote repository may not be signed at all (for example, the publisher released the package forgetting to sign it). This parameter allows you to choose what to do in this case. |
| `When package signature is invalid` | Package retrieved from a remote repository may have invalid signature (because the GPG key used to sign the package was not imported, or because the publisher signed the package with a different GPG key, or because the package's signature is corrupted or somehow broken). This parameter allows you to choose what to do in this case. |

### DEB

| Parameter | Description |
|---|---|
| `Enable DEB repositories` | Enable DEB package repositories. |
| `Sign repositories with GPG` | Enable the signing of DEB repositories when creating a DEB package repository (mirror or local repository). The repository metadata will be signed using the GPG signing key specified by the `GPG key Id` parameter. |
| `Default package architecture` | Default package architecture to use when creating DEB repositories. |

**DEB MIRRORING SETTINGS**

| Parameter | Description |
|---|---|
| `When Release file signature is invalid` | `InRelease` / `Release` file retrieved from a remote repository may have invalid signature (because the GPG key used to sign the file was not imported, or because the publisher signed the file with a different GPG key, or because the file's signature is corrupted or somehow broken). This parameter allows you to choose what to do in this case. |

### GPG SIGNING KEY

| Parameter | Description |
|---|---|
| `GPG key Id` | GPG key for signing packages and repositories, identified by its email address. This key is randomly generated upon Repomanager's first startup (4096 bits RSA key). |

It is currently not possible to modify the key Id or the passphrase on the fly from the web interface. To modify the key Id, you must do it manually with the following steps:

1. Set a new key Id from the web interface respecting the format ``keyname@fqdn`` and **Save**.

// TO UPDATE
[![GPG key Id](https://github.com/lbr38/repomanager/assets/54670129/c5411e04-feb3-45b5-bfb2-ad78c62baabf)](https://github.com/lbr38/repomanager/assets/54670129/c5411e04-feb3-45b5-bfb2-ad78c62baabf)

2. Enter the container:

```
docker exec -it repomanager /bin/bash
```

3. Delete pubring, macros file and the public key:

```
rm /var/lib/repomanager/.gnupg/pubring.* /var/lib/repomanager/.rpm/.mcs /home/repo/gpgkeys/* -f
```

4. Refresh Repomanager (F5) and test.

5. Beware that the packages and repositories signed with the old key will no longer be valid. You must re-sign them with the new key (rebuild repositories metadata).


### ENVIRONMENTS

See `Manage repositories environments <https://github.com/lbr38/repomanager/wiki/04.-Manage-repositories-environments>`_


### STATISTICS

| Parameter | Description |
|---|---|
| `Enable repositories statistics` | Enable logging and statistics on repositories access, repositories size and repositories packages count. |


## SCHEDULED TASKS

| Parameter | Description |
|---|---|
| `Enable scheduled tasks reminders` | Enable reminders for scheduled tasks. Reminders are sent via email to the recipients defined when adding a new scheduled task. |


## HOSTS

| Parameter | Description |
|---|---|
| `Manage hosts` | Enable the management of client hosts. These hosts can register to Repomanager using linupdate. See [Manage hosts](https://github.com/lbr38/repomanager/wiki/09.-Manage-hosts-and-profiles#manage-hosts) |


## CVE

| Parameter | Description |
|---|---|
| `Import CVEs (beta)` | Enable the import of CVEs into Repomanager. The import uses feeds from https://nvd.nist.gov/. Eventually, the CVEs tab should be able to list client hosts imported into Repomanager that have vulnerable packages. |
| `Import scheduled time` | Every day time at which the import of CVEs runs. |


## SSO

SSO was introduced in version [`4.19.0`](https://github.com/lbr38/repomanager/pull/241).

SSO was tested against the following Identity Providers (IdP):

- Okta (Workforce Identity Cloud, Developer Edition)
- Authentik (Open Source)
- Microsoft EntraID / Azure AD

SSO can be configured:

- with an administrator account in the web-UI (settings page)
- with the ``app.yaml`` file located in the root of the ``repomanager-data`` volume of the container

!!! info
    The ``app.yaml`` content will override the web-UI settings if both are set.

| Parameter | Description |
|---|---|
| `Enable SSO` | Enable SSO login via OpenID Connect. |
| `SSO only` | Only allow login via OpenID Connect. This will disable local login. |
| `Provider URL` | Provider URL, used for Autodiscovery. |
| `Authorization endpoint` | Override Authorization Endpoint (leave empty for Autodiscovery). |
| `Token endpoint` | Override Token Endpoint (leave empty for Autodiscovery). |
| `Userinfo endpoint` | Override Userinfo Endpoint (leave empty for Autodiscovery). |
| `Scopes` | Additional OIDC Scopes (openid already present). |
| `Client ID` | Client ID. |
| `Client secret` | Client secret. |
| `Username claim` | OIDC Claim for username. |
| `First name` | OIDC Claim for First Name. |
| `Last name` | OIDC Claim for Last Name. |
| `Email` | OIDC Claim for Email. |
| `Groups` | OIDC Claim for Groups / Roles. |
| `Group administrator` | Groups value for Administrator. |

**Example with Okta**

| Setting | Value |
|---|---|
| provider_url | (Example: https://dev-0000000.okta.com/) |
| client_id | (client id) |
| client_secret | (client secret) |

**Example with Authentik**

| Setting | Value |
|---|---|
| provider_url | (Example: http://server:9000/application/o/repomanager/) |
| authorization_endpoint | (Example: http://localhost:9000/application/o/authorize/) |
| client_id | (client id) |
| client_secret | (client secret) |

## USERS

### Create an user

From the ``SETTINGS`` tab:

1. Use the ``USERS`` right panel to create a new user.
2. Specify its username and its role.
3. A new random password is generated and the user is ready to use.

// TO UPDATE
[![Create an user](https://github.com/lbr38/repomanager/assets/54670129/905ad750-8086-4421-8a18-3acda1e526ae)](https://github.com/lbr38/repomanager/assets/54670129/905ad750-8086-4421-8a18-3acda1e526ae)

!!! info "Notes"
    - `administrator` role has full permissions. It can create or delete any data. It cannot delete other administrators.
    - `standard` role has limited permissions which can be configured by administrators.


### Delete an user

From the ``SETTINGS`` tab:

1. Use the ``USERS`` right panel to create a new user.
2. Use the Delete button to delete an user.

// TO UPDATE
[![Delete an user](https://github.com/lbr38/repomanager/assets/54670129/d57e4866-6f81-4776-a4fd-5452b8c6eb50)](https://github.com/lbr38/repomanager/assets/54670129/d57e4866-6f81-4776-a4fd-5452b8c6eb50)


### Reset user password

You must be logged in as an administrator account to reset another user password.

From the ``SETTINGS`` tab:

1. Use the Update button to reset user password.
2. A new random password will be generated.

// TO UPDATE
[![Reset user password](https://github.com/lbr38/repomanager/assets/54670129/a7107cb4-877a-42c3-bbcf-8b75613de505)](https://github.com/lbr38/repomanager/assets/54670129/a7107cb4-877a-42c3-bbcf-8b75613de505)
