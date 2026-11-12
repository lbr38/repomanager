!!! info "API root URL"
    ``https://<FQDN>/api/v3``

Repomanager exposes an API to manage repositories, mirrors, snapshots, environments, tasks and hosts.

:material-server: From a client host (linupdate agent):

- Register or unregister a host into Repomanager
- Send general host information and packages information to Repomanager
- Retrieve the configuration of a host profile

:octicons-device-desktop-16: From a desktop or a CI pipeline:

- Create, edit and delete curated repositories, local repositories and mirrors
- Sync repositories, point environments to snapshots, rebuild metadata
- Upload packages to a repository
- List hosts, their packages and their compliance
- ...

!!! note
    API v2 is still available for compatibility, see [API v2 (deprecated)](/reference/api-v2/).


## API key

An API key must be retrieved from userspace.

Once generated, copy the key and keep it safe. This key is used to authenticate with the API and to perform certain actions when there is no host Id+token pair available.

[![Generate API key](https://assets.repomanager.net/repomanager/6.0.0/reference/api/generate-api-key.png)](https://assets.repomanager.net/repomanager/6.0.0/reference/api/generate-api-key.png)

!!! info
    If a new API key is generated, the old key becomes invalid and unusable.


## Authentication

Every request must be authenticated with the ``Authorization`` header:

- ``Authorization: Bearer <APIKEY>``: API key of a user. The [permissions](/configuration/settings/#edit-user-permissions) of the user apply: repositories and mirrors the user is not allowed to view are not listed and return a ``404`` error, actions the user is not allowed to perform return a ``403`` error.
- ``Authorization: Host <HOST_ID>:<HOST_TOKEN>``: Id and token of a host, returned when the host registers. Only the routes used by the linupdate agent accept it.


## Conventions

### Responses

Successful responses are JSON objects with lowercase keys:

```json
{
  "data": { ... },
  "meta": {
    "message": "Curated repository #12 has been edited"
  }
}
```

- ``data``: the requested resource or list of resources, ``null`` when there is none (e.g. an action on a task).
- ``meta``: optional information, ``message`` (information message) and ``pagination`` (see [Pagination](#pagination)).

| Status code | Meaning |
|---|---|
| ``200`` | Success |
| ``201`` | Resource created (host registration), the ``Location`` header is the URL of the resource |
| ``202`` | Task started or scheduled, the task is returned and the ``Location`` header is its URL |
| ``400`` | Invalid request or parameters, or operation refused (e.g. protected environment) |
| ``401`` | Missing or invalid authentication |
| ``403`` | The user is not allowed to perform this action |
| ``404`` | Route, repository, snapshot or other resource not found |
| ``405`` | Method not allowed on this route, the ``Allow`` header lists the allowed methods |
| ``500`` | Internal error, details are in the Repomanager logs |
| ``503`` | Repomanager is being updated or is under maintenance |

### Errors

Errors are returned as Problem Details objects ([RFC 9457](https://www.rfc-editor.org/rfc/rfc9457)), with the ``application/problem+json`` content type:

```json
{
  "type": "https://docs.repomanager.net/reference/api/#invalid-parameter",
  "title": "Invalid parameter",
  "status": 400,
  "detail": "Unknown parameter(s): descripton. Valid parameters are: description, tags, retention, retention_keep_all",
  "code": "invalid_parameter",
  "errors": [
    { "detail": "Unknown parameter", "pointer": "#/descripton" }
  ]
}
```

- ``type``, ``title``, ``status``, ``detail``: standard members, ``detail`` is the explanation of the error.
- ``code``: stable error code, scripts should rely on it rather than on ``detail``.
- ``errors``: parameters in error, when they are known. ``pointer`` is the JSON Pointer ([RFC 6901](https://www.rfc-editor.org/rfc/rfc6901)) of a body parameter, ``parameter`` is the name of a query parameter.

| Code | Status | Meaning |
|---|---|---|
| <span id="invalid-request"></span>``invalid_request`` | ``400`` | Invalid request (e.g. nothing to edit, no file uploaded) |
| <span id="invalid-parameter"></span>``invalid_parameter`` | ``400`` | A parameter has an invalid value or type, or is unknown |
| <span id="missing-parameter"></span>``missing_parameter`` | ``400`` | A required parameter is missing |
| <span id="operation-refused"></span>``operation_refused`` | ``400`` | The operation is refused by Repomanager (e.g. protected environment, existing repository, invalid repository name) |
| <span id="unauthorized"></span>``unauthorized`` | ``401`` | Missing or invalid authentication |
| <span id="forbidden"></span>``forbidden`` | ``403`` | The user is not allowed to perform this action |
| <span id="not-found"></span>``not_found`` | ``404`` | The route or the resource does not exist |
| <span id="method-not-allowed"></span>``method_not_allowed`` | ``405`` | The method is not allowed on this route |
| <span id="internal-error"></span>``internal_error`` | ``500`` | Internal error, details are in the Repomanager logs |
| <span id="unavailable"></span>``unavailable`` | ``503`` | Repomanager is being updated or is under maintenance |

### Parameters

- Parameters are sent as a JSON body (``Content-Type: application/json``), except for files uploads which are multipart requests (``-F``).
- Keys are lowercase (``package_type``, ``gpg_sign``...), booleans are JSON booleans (``true`` / ``false``).
- Parameters accepting a list also accept a single value (``"environments": "prod"`` is the same as ``"environments": ["prod"]``).
- Unknown parameters are refused with an ``invalid_parameter`` error, to report misspelled parameters.

### Pagination

The lists of tasks, hosts and hosts packages are paginated with the ``limit`` (1 to 1000, default 100) and ``offset`` (default 0) query parameters:

```bash
curl --fail-with-body -L -s -H "Authorization: Bearer <APIKEY>" "https://repomanager.mydomain.net/api/v3/tasks?limit=50&offset=100"
```

The pagination is returned in ``meta.pagination`` (``total``, ``limit``, ``offset``), and the ``Link`` header ([RFC 8288](https://www.rfc-editor.org/rfc/rfc8288)) contains the URL of the next and previous pages:

```
Link: </api/v3/tasks?limit=50&offset=150>; rel="next", </api/v3/tasks?limit=50&offset=50>; rel="prev"
```

### Tasks

Operations that modify the content of repositories (creation, sync, environment pointing, deletion...) are executed in background by a task. They return a ``202`` response with the task, and its URL in the ``Location`` header:

```
HTTP/1.1 202 Accepted
Location: /api/v3/tasks/42
```

```json
{
  "data": {
    "id": 42,
    "action": "update",
    "status": "queued",
    ...
  },
  "meta": {
    "message": "Sync of mirror #3 started"
  }
}
```

The task can be followed with ``GET /tasks/<TASK_ID>``. When an operation creates several tasks (e.g. deleting a repository with several snapshots), they are grouped under a parent task, which is returned.

### Scheduling

Operations executed by a task accept an optional ``schedule`` parameter to execute them later instead of immediately. The schedule types allowed depend on the operation: for example, a sync or a metadata rebuild can be recurring, a repository creation cannot.

```json
"schedule": {
  "type": "unique",
  "date": "2026-12-31",
  "time": "22:00",
  "notify_error": true,
  "notify_success": false,
  "reminders": [1, 3],
  "recipients": ["admin@example.com"]
}
```

| Parameter | Description |
|---|---|
| ``type`` | ``unique`` or ``recurring`` (required) |
| ``date`` | Execution date ``YYYY-MM-DD`` (``unique``) |
| ``time`` | Execution time ``HH:MM`` (``unique``, and ``daily``, ``weekly``, ``monthly`` frequencies) |
| ``frequency`` | ``hourly``, ``daily``, ``weekly``, ``monthly`` or ``cron`` (``recurring``) |
| ``days`` | List of days for the ``weekly`` frequency: ``monday``, ``tuesday``... |
| ``monthly_day``, ``monthly_day_position`` | Day of the month for the ``monthly`` frequency, e.g. ``monday`` and ``first`` (``first``, ``second``, ``third``, ``last``) |
| ``cron`` | Cron expression for the ``cron`` frequency |
| ``notify_error``, ``notify_success`` | Send an email when the task fails or succeeds (default ``false``) |
| ``reminders`` | Send a reminder email the specified number of days before the execution |
| ``recipients`` | Emails of the notifications and reminders recipients |


## Repositories

Repositories are addressed by their type and their Id: ``/repositories/<TYPE>/<ID>``.

- ``<TYPE>`` is ``curated``, ``local`` or ``mirror``.
- ``<ID>`` is the Id of the repository, returned when listing repositories. Curated and local repositories share the same Ids, mirrors have their own.
- Snapshots are addressed within their repository: ``/repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>``.

The type is checked: requesting a local repository with the ``curated`` type returns a ``404`` error. Routes that only apply to some types (packages, metadata rebuild...) return a ``404`` error for the other types.

| Endpoint | Description |
|---|---|
| ``GET /repositories`` | List all repositories (curated, local and mirrors). Optional filters: ``?package_type=deb``, ``?tag=<TAG>``, ``?group=<GROUP>`` |
| ``GET /repositories/<TYPE>`` | List the repositories of a type, same filters |
| ``POST /repositories/<TYPE>`` | Create a repository (task) |
| ``GET /repositories/<TYPE>/<ID>`` | Repository details, with its snapshots |
| ``PATCH /repositories/<TYPE>/<ID>`` | Edit the repository properties |
| ``DELETE /repositories/<TYPE>/<ID>`` | Delete the repository with all its snapshots (task) |
| ``POST /repositories/<TYPE>/<ID>/rename`` | Rename the repository (task, curated and local repositories). Parameter: ``name`` |

### Create a curated repository

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"package_type": "deb", "source": "debian", "dist": "trixie", "section": "main", "arch": ["amd64"], "environments": ["preprod"]}' \
  https://repomanager.mydomain.net/api/v3/repositories/curated
```

| Parameter | Description |
|---|---|
| ``package_type`` | ``rpm`` or ``deb`` (required) |
| ``source`` | Source repository name (required) |
| ``name`` | Repository name, the source repository name by default |
| ``releasever`` | Release version (``rpm``, required). A list creates a repository for each release version |
| ``dist``, ``section`` | Distribution and component (``deb``, required). Lists create a repository for each distribution and component |
| ``arch`` | Architectures, the default architectures of the settings by default |
| ``gpg_check`` | Check the GPG signatures of the source repository (default ``true``) |
| ``gpg_sign`` | Sign the repository with GPG, as defined in the settings by default |
| ``environments`` | Environments to point to the first snapshot |
| ``description``, ``tags``, ``group`` | Description, list of tags and group name |
| ``retention``, ``retention_keep_all`` | Number of unused snapshots to keep (``null``: global setting), or keep all of them |
| ``advanced_params`` | Packages to sync and metadata, see below |
| ``schedule`` | See [Scheduling](#scheduling) |

``advanced_params``:

```json
"advanced_params": {
  "packages": { "include": ["nginx*"], "exclude": [], "keep_latest": 3 },
  "metadata_sync": { "comps": true, "modules": true, "updateinfo": true },
  "metadata_custom_fields": { "origin": "", "label": "", "description": "" }
}
```

``metadata_sync`` only applies to ``rpm`` repositories, ``metadata_custom_fields`` to ``deb`` repositories.

### Create a local repository

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"package_type": "rpm", "name": "my-apps", "releasever": "9", "environments": ["preprod"]}' \
  https://repomanager.mydomain.net/api/v3/repositories/local
```

Same parameters as a curated repository, ``name`` is required. ``source``, ``gpg_check`` and ``advanced_params`` do not apply.

### Create a mirror

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"package_type": "deb", "source": "debian", "dists": ["trixie", "trixie-updates"], "components": ["main"], "arch": ["amd64"]}' \
  https://repomanager.mydomain.net/api/v3/repositories/mirror
```

| Parameter | Description |
|---|---|
| ``package_type`` | ``rpm``, ``deb`` or ``arch`` (required) |
| ``source`` | Source repository name (required) |
| ``arch`` | Architectures (required) |
| ``name`` | Mirror name, the source repository name by default |
| ``releasever`` | Release version (``rpm``, required) |
| ``dists``, ``components`` | Distributions and components (``deb``, not needed for flat repositories) |
| ``repos`` | Repositories (``arch``, e.g. ``core``, ``extra``) |
| ``gpg_check`` | Check the GPG signatures of the source repository (default ``true``) |
| ``environments``, ``description``, ``tags``, ``group``, ``retention``, ``retention_keep_all``, ``schedule`` | Same as curated repositories |

### Edit a repository

```bash
curl --fail-with-body -L -s -X PATCH -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"description": "Debian main repository", "tags": ["debian", "prod"], "retention": 5}' \
  https://repomanager.mydomain.net/api/v3/repositories/curated/<ID>
```

Only the specified properties are edited: ``description``, ``tags``, ``retention``, ``retention_keep_all``, and ``source`` for curated repositories. The edited repository is returned.


## Snapshots

| Endpoint | Description |
|---|---|
| ``GET /repositories/<TYPE>/<ID>/snapshots`` | List the snapshots, most recent first |
| ``POST /repositories/<TYPE>/<ID>/snapshots`` | Create a new snapshot (task): sync from the source repository (curated repositories and mirrors), or copy of the latest snapshot to add packages to (local repositories) |
| ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>`` | Snapshot details, with the environments pointing to it and their URL |
| ``DELETE /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>`` | Delete the snapshot (task). A curated or local repository is deleted with its last snapshot |
| ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/rebuild`` | Rebuild the snapshot metadata (task, curated and local repositories). Parameter: ``gpg_sign`` (current signature by default) |
| ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/duplicate`` | Duplicate the snapshot into a new repository (task, curated and local repositories). Parameters: ``name`` (required), ``environments``, ``description``, ``tags``, ``group`` |
| ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/diff/<OTHER_SNAPSHOT_ID>`` | Packages added and removed between two snapshots of the repository (curated and local repositories). Add ``?other_repository=<TYPE>/<ID>`` to compare with a snapshot of another repository |

Parameters of a new snapshot (all optional):

- curated repositories: ``arch``, ``gpg_check``, ``gpg_sign``, ``advanced_params``, ``environments``, ``schedule``. Unspecified values are the settings of the latest snapshot.
- local repositories: ``arch``, ``gpg_sign``, ``environments``, ``schedule``.
- mirrors: ``environments``, ``schedule``.

```bash
# Sync a mirror and point the preprod environment to the new snapshot
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"environments": ["preprod"]}' \
  https://repomanager.mydomain.net/api/v3/repositories/mirror/<ID>/snapshots

# Sync a curated repository every night
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"schedule": {"type": "recurring", "frequency": "daily", "time": "02:00"}}' \
  https://repomanager.mydomain.net/api/v3/repositories/curated/<ID>/snapshots
```


## Environments

| Endpoint | Description |
|---|---|
| ``GET /environments`` | List the environments. A protected environment cannot be pointed nor removed |
| ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/environments`` | Point environment(s) to the snapshot (task). Parameters: ``environments`` (required), ``schedule`` |
| ``DELETE /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/environments/<ENV>`` | Remove an environment from the snapshot (task) |

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"environments": ["prod"]}' \
  https://repomanager.mydomain.net/api/v3/repositories/local/<ID>/snapshots/<SNAPSHOT_ID>/environments
```


## Packages

Curated and local repositories only.

| Endpoint | Description |
|---|---|
| ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` | List the packages of the snapshot |
| ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` | Upload packages (multipart request). Optional fields: ``overwrite``, ``ignore_if_exists`` (``true`` / ``false``) |
| ``DELETE /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` | Delete packages by file name. Parameter: ``packages`` (required) |

The snapshot metadata must then be rebuilt to make the changes available.

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" \
  -F "packages[]=@my-app_1.0.0_amd64.deb" -F "packages[]=@my-lib_1.0.0_amd64.deb" \
  https://repomanager.mydomain.net/api/v3/repositories/local/<ID>/snapshots/<SNAPSHOT_ID>/packages

curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" \
  -d '{"gpg_sign": true}' \
  https://repomanager.mydomain.net/api/v3/repositories/local/<ID>/snapshots/<SNAPSHOT_ID>/rebuild
```


## Source repositories

These endpoints require the permission to edit source repositories, as their definition can contain credentials.

| Endpoint | Description |
|---|---|
| ``GET /sources`` | List the source repositories. Optional filter: ``?package_type=deb`` (``deb``, ``rpm`` or ``arch``) |
| ``GET /sources/<PACKAGE_TYPE>/<NAME>`` | Source repository details |
| ``POST /sources/import`` | Import source repositories from a template file (multipart request, file field ``template``) |

```bash
curl --fail-with-body -L -s -X POST -H "Authorization: Bearer <APIKEY>" -F "template=@/path/to/template.yml" \
  https://repomanager.mydomain.net/api/v3/sources/import
```


## Tasks

| Endpoint | Description |
|---|---|
| ``GET /tasks`` | List the tasks. Optional filter: ``?status=running`` (``running``, ``queued``, ``scheduled`` or ``done``) |
| ``GET /tasks/<TASK_ID>`` | Task details: status, targeted repository, schedule... |
| ``POST /tasks/<TASK_ID>/enable`` | Enable a scheduled task |
| ``POST /tasks/<TASK_ID>/disable`` | Disable a scheduled task |
| ``POST /tasks/<TASK_ID>/stop`` | Stop a running task |
| ``POST /tasks/<TASK_ID>/relaunch`` | Relaunch a task, returns the Id of the new task |
| ``DELETE /tasks/<TASK_ID>`` | Delete a task |


## Hosts

| Endpoint | Authentication | Description |
|---|---|---|
| ``GET /hosts`` | API key | List the hosts, see filters below |
| ``GET /hosts/<HOST_ID>`` | API key or host | Host details (a host can only access its own data) |
| ``GET /hosts/<HOST_ID>/packages/installed`` | API key or host | Installed packages of a host (paginated) |
| ``GET /hosts/<HOST_ID>/packages/available`` | API key or host | Available updates of a host (paginated) |
| ``DELETE /hosts/<HOST_ID>`` | API key | Delete a host |

Filters of ``GET /hosts``, they can be combined:

- properties: ``?os=ubuntu``, ``?os_version=24.04``, ``?os_family=Debian``, ``?kernel=<KERNEL>``, ``?arch=x86_64``, ``?type=<VIRTUALIZATION>``, ``?profile=<PROFILE>``, ``?env=prod``
- status: ``?status=uptodate``, ``outdated``, ``compliant`` or ``non-compliant``. Add ``&packages`` to include the list of available updates
- package: ``?package=nginx``, with optional ``&version=<VERSION>``, ``&strict_name``, ``&strict_version`` (or ``&strict`` for both), ``&absent`` to list the hosts on which the package is not installed

```bash
curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" \
  "https://repomanager.mydomain.net/api/v3/hosts?os=debian&status=outdated&packages"
```

### linupdate agent

| Endpoint | Authentication | Description |
|---|---|---|
| ``POST /hosts`` | API key | Register a host. Parameters: ``ip``, ``hostname``. Returns the ``id`` and ``token`` the host must authenticate with (``data.id``, ``data.token``) |
| ``DELETE /hosts/me`` | Host | Unregister the authenticated host |
| ``PUT /hosts/me/status`` | Host | Send the host general information: ``hostname``, ``os``, ``os_version``, ``os_family``, ``type``, ``cpu``, ``ram``, ``network``, ``kernel``, ``arch``, ``profile``, ``env``, ``agent_status``, ``agent_version``, ``reboot_required``, ``uptime``, ``public_ip``, ``behind_nat``. If ``network`` contains all the addresses of each interface (``addresses``) and the main interface (``main``), the registered IP is replaced by the main IP when the host no longer owns it. ``public_ip`` is cleared when the host does not send it and is not declared ``behind_nat``. Returns the updated values in ``data.updated`` |
| ``PUT /hosts/me/packages/installed`` | Host | Send the installed packages. Parameter: ``installed_packages`` |
| ``PUT /hosts/me/packages/available`` | Host | Send the available updates. Parameter: ``available_packages`` |
| ``PUT /hosts/me/packages/events`` | Host | Send the packages events history. Parameter: ``events`` |
| ``GET /profiles/<PROFILE>`` | API key or host | Profile configuration (packages exclusions, services to reload or restart...) |
| ``GET /profiles/<PROFILE>/repositories`` | API key or host | Repositories of the profile, as configured on the host |
| ``GET /profiles`` | API key | List the profiles |


## Migrating from API v2

The main changes:

- Repositories and mirrors are a single resource, ``/repositories/<TYPE>/<ID>``, and snapshots are addressed within their repository.
- Responses have a new format: ``data`` and ``meta`` instead of ``results``, ``message``, ``rc`` and ``return``, and errors are Problem Details objects with a stable ``code`` (see [Responses](#responses) and [Errors](#errors)).
- Response keys are always lowercase (``Package_type`` is now ``package_type``), the ``?normalize`` parameter is removed.
- Operations executed by a task return the task (``data.id`` is its Id) instead of ``task-id``.
- Responses use the appropriate HTTP status codes (``404``, ``403``...) instead of ``400`` for every error, and an empty list is a successful response.
- Repositories can be created, synced, edited and deleted.

| API v2 | API v3 |
|---|---|
| ``GET /repo/`` | ``GET /repositories`` |
| ``GET /repo/<ID>`` | ``GET /repositories/<TYPE>/<ID>/snapshots`` |
| ``GET /snapshot/<SNAPSHOT_ID>/`` | ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>`` |
| ``GET /snapshot/<SNAPSHOT_ID>/packages`` | ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` |
| ``GET /snapshot/<SNAPSHOT_ID>/diff/<SNAPSHOT_ID>`` | ``GET /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/diff/<SNAPSHOT_ID>`` |
| ``POST /snapshot/<SNAPSHOT_ID>/upload`` | ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` (``ignore-if-exists`` is now ``ignore_if_exists``) |
| ``DELETE /snapshot/<SNAPSHOT_ID>/packages`` | ``DELETE /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/packages`` |
| ``PUT /snapshot/<SNAPSHOT_ID>/rebuild`` (``gpgSign``) | ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/rebuild`` (``gpg_sign``) |
| ``PATCH /environment/<ENV>/point`` | ``POST /repositories/<TYPE>/<ID>/snapshots/<SNAPSHOT_ID>/environments`` |
| ``GET /mirror/`` | ``GET /repositories/mirror`` |
| ``GET /mirror/<ID>/`` | ``GET /repositories/mirror/<ID>`` |
| ``POST /mirror/<ID>/sync`` | ``POST /repositories/mirror/<ID>/snapshots`` |
| ``POST /mirror/<ID>/env`` | ``POST /repositories/mirror/<ID>/snapshots/<SNAPSHOT_ID>/environments`` |
| ``GET /source/``, ``GET /source/<TYPE>/`` | ``GET /sources``, ``GET /sources?package_type=<TYPE>`` |
| ``GET /source/<TYPE>/<NAME>`` | ``GET /sources/<TYPE>/<NAME>`` |
| ``POST /source/<TYPE>/import`` | ``POST /sources/import`` |
| ``GET /tasks/``, ``GET /tasks/<STATUS>`` | ``GET /tasks``, ``GET /tasks?status=<STATUS>`` |
| ``GET /task/<TASK_ID>/`` | ``GET /tasks/<TASK_ID>`` |
| ``POST /task/<TASK_ID>/enable`` (``disable``, ``stop``) | ``POST /tasks/<TASK_ID>/enable`` (``disable``, ``stop``) |
| ``DELETE /task/<TASK_ID>/delete`` | ``DELETE /tasks/<TASK_ID>`` |
| ``GET /hosts/<FILTER>/<VALUE>`` | ``GET /hosts?<FILTER>=<VALUE>`` |
| ``GET /hosts/uptodate``, ``outdated``, ``compliant``, ``non-compliant`` | ``GET /hosts?status=<STATUS>`` |
| ``GET /host/<HOST_ID>/installed``, ``/host/<HOST_ID>/updates`` | ``GET /hosts/<HOST_ID>/packages/installed``, ``/hosts/<HOST_ID>/packages/available`` |
| ``POST /host/registering`` | ``POST /hosts`` |
| ``DELETE /host/registering`` | ``DELETE /hosts/me`` (host), ``DELETE /hosts/<HOST_ID>`` (API key) |
| ``PUT /host/status`` | ``PUT /hosts/me/status`` |
| ``PUT /host/packages/installed``, ``available``, ``event`` | ``PUT /hosts/me/packages/installed``, ``available``, ``events`` |
| ``GET /profile/``, ``GET /profile/<PROFILE>`` | ``GET /profiles``, ``GET /profiles/<PROFILE>`` |
| ``GET /profile/<PROFILE>/repos?new-format`` | ``GET /profiles/<PROFILE>/repositories`` |

``GET /profile/<PROFILE>/excludes`` has no equivalent, the packages exclusions are part of the profile configuration (``GET /profiles/<PROFILE>``). ``GET /profile/server-settings`` has no equivalent either.
