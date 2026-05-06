!!! info "API root URL"
    ``https://<FQDN>/api/v2``

Repomanager exposes an API that allows performing certain actions.

:material-server: From a client host:

- Register or unregister a host into Repomanager
- Send general host information to Repomanager
- Send packages information to Repomanager
- Retrieve the configuration of a host profile from Repomanager

:octicons-device-desktop-16: From a desktop:

- Import source repositories
- Upload a package to a repository
- Rebuild repository metadata
- ...


## API key

An API key must be retrieved from userspace.

Once generated, copy the key and keep it safe. This key is used to authenticate with the API and to perform certain actions when there is no host Id+token pair available.

[![Generate API key](https://assets.repomanager.net/repomanager/reference/api/generate-api-key.png)](https://assets.repomanager.net/repomanager/reference/api/generate-api-key.png)

!!! info
    If a new API key is generated, the old key becomes invalid and unusable.


## Endpoints

!!! info
    ``<SNAPSHOT_ID>`` can be retrieved when you browse a snapshot from the repositories list:

    [![Retrieve snapshot ID](https://github.com/lbr38/repomanager/assets/54670129/d849e588-d4c9-459a-9e5c-98f3d5b37b19)](https://github.com/lbr38/repomanager/assets/54670129/d849e588-d4c9-459a-9e5c-98f3d5b37b19)


### Repositories

<table>
  <thead>
    <tr>
      <th style="min-width: 250px">Endpoint and method</th>
      <th style="min-width: 150px">Authentication</th>
      <th style="min-width: 200px">Parameter(s)</th>
      <th style="min-width: 300px">Description</th>
      <th>Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>/repo/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all repositories</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/
        ```
      </td>
    </tr>
    <tr>
      <td>/repo/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all repositories whose name is <code>nginx</code></td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/ | jq -r '.results[] | select(.Name == "nginx")'
        ```
      </td>
    </tr>
    <tr>
      <td>/repo/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>(RPM repos) List all repositories whose name is <code>nginx</code> and the release version is <code>8</code></td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/ | jq -r '.results[] | select(.Name == "nginx")'
        ```
      </td>
    </tr>
    <tr>
      <td>/repo/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>(DEB repos) List all repositories whose name is <code>nginx</code>, the distribution is <code>bookworm</code> and the section/component is <code>nginx</code></td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/ | jq -r '.results[] | select(.Name == "nginx" and .Dist == "bookworm" and .Section == "nginx")'
        ```
      </td>
    </tr>
    <tr>
      <td>/repo/<code>&lt;REPO_ID&gt;</code>/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all snapshots of a repository whose ID is <code>12</code></td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/12/
        ```
      </td>
    </tr>
    <tr>
      <td>/repo/<code>&lt;REPO_ID&gt;</code>/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>Get the ID of the most recent snapshot of a repository whose ID is <code>12</code></td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/repo/12/ | jq -r .results[].Id | head -n1
        ```
      </td>
    </tr>
  </tbody>
</table>

### Snapshots

<table>
  <thead>
    <tr>
      <th style="min-width: 250px">Endpoint and method</th>
      <th style="min-width: 150px">Authentication</th>
      <th style="min-width: 200px">Parameter(s)</th>
      <th style="min-width: 300px">Description</th>
      <th>Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>/snapshot/<code>&lt;SNAPSHOT_ID&gt;</code>/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List snapshot details</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/
        ```
      </td>
    </tr>
    <tr>
      <td>/snapshot/<code>&lt;SNAPSHOT_ID&gt;</code>/packages<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all packages of a snapshot</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/packages
        ```
      </td>
    </tr>
    <tr>
      <td>/snapshot/<code>&lt;SNAPSHOT_ID&gt;</code>/upload<br><code>POST</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td>
        <code>file</code> (required)
        <code>overwrite</code> (optional, default: <code>false</code>)
        <code>ignore-if-exists</code> (optional, default: <code>false</code>)
      </td>
      <td>
        Upload one or more packages to a repository snapshot. Overwrite the package if it already exists in the snapshot if <code>overwrite</code> is set to <code>true</code>. Ignore the upload if the package already exists if <code>ignore-if-exists</code> is set to <code>true</code>.
      </td>
      <td markdown="block">
        Single file example:
        ```bash
        curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <APIKEY>" -F "file1=@/tmp/mypackage.deb" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/upload
        ```
        Multiple files example:
        ```bash
        curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <APIKEY>" -F "file1=@/tmp/mypackage1.deb" -F "file2=@/tmp/mypackage2.deb" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/upload
        ```
        Overwrite example:
        ```bash
        curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <APIKEY>" -F "file1=@/tmp/mypackage.deb" -F "overwrite=true" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/upload
        ```
        Ignore if exists example:
        ```bash
        curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <APIKEY>" -F "file1=@/tmp/mypackage.deb" -F "ignore-if-exists=true" https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/upload
        ```
      </td>
    </tr>
    <tr>
      <td>/snapshot/<code>&lt;SNAPSHOT_ID&gt;</code>/rebuild<br><code>PUT</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>gpgSign</code> (required)</td>
      <td>Rebuild repository snapshot metadata.</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X PUT -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" -d '{"gpgSign":"true"}' https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/rebuild
        ```
      </td>
    </tr>
    <tr>
      <td>/snapshot/<code>&lt;SNAPSHOT_ID&gt;</code>/packages<br><code>DELETE</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>packages</code> (required, JSON array of package names)</td>
      <td>Delete one or more packages from a snapshot</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X DELETE -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" -d '{"packages":["mypackage1.deb", "mypackage2.deb"]}' https://repomanager.mydomain.net/api/v2/snapshot/<SNAPSHOT_ID>/packages
        ```
      </td>
    </tr>
</tbody>
</table>


### Hosts

<table>
  <thead>
    <tr>
      <th style="min-width: 250px">Endpoint and method</th>
      <th style="min-width: 150px">Authentication</th>
      <th style="min-width: 200px">Parameter(s)</th>
      <th style="min-width: 300px">Description</th>
      <th>Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>/host/registering<br><code>POST</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>hostname</code> (required)<br><code>ip</code> (required)</td>
      <td>Register a host to Repomanager and retrieve host ID and token</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X POST -H "Authorization: Bearer <APIKEY>" -H "Content-Type: application/json" -d '{"hostname":"<FQDN>","ip":"<IP>"}' https://repomanager.mydomain.net/api/v2/host/registering
        ```
      </td>
    </tr>
    <tr>
      <td>/host/registering<br><code>DELETE</code></td>
      <td><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td></td>
      <td>Unregister a host from Repomanager</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X DELETE -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" -H "Content-Type: application/json" https://repomanager.mydomain.net/api/v2/host/registering
        ```
      </td>
    </tr>
    <tr>
      <td>/host/status<br><code>PUT</code></td>
      <td><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td><code>hostname</code> (optional)<br><code>os</code> (optional)<br><code>os_version</code> (optional)<br><code>os_family</code> (optional)<br><code>type</code> (=virtualization type) (optional)<br><code>kernel</code> (optional)<br><code>arch</code> (optional)<br><code>profile</code> (optional)<br><code>env</code> (optional)<br><code>agent_status</code> (optional)<br><code>linupdate_version</code> (optional)<br><code>reboot_required</code> (optional)</td>
      <td>Send host general information to Repomanager</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X PUT -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" -H "Content-Type: application/json" -d '{"hostname":"myfqdn.localhost","os":"ubuntu","os_version":"22.04","os_family":"Debian","type":"Bare metal","kernel":"5.15.0-89-generic","arch":"x86_64","profile":"PC","env":"prod","agent_status":"running","linupdate_version":"2.2.2","reboot_required":"false"}' https://repomanager.mydomain.net/api/v2/host/status
        ```
      </td>
    </tr>
    <tr>
      <td>/host/packages/installed<br><code>PUT</code></td>
      <td><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td><code>installed_packages</code> (required)<br>Each package must be separated by a comma and contains the package name and the version number separated by a pipe</td>
      <td>Send list of installed packages to Repomanager</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X PUT -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" -H "Content-Type: application/json" -d '{"installed_packages":"accountsservice|22.07.5-2ubuntu1.4,acl|2.3.1-1,acpi-support|0.144,acpid|1:2.0.33-1ubuntu1,add-apt-key|1.0-0.5,adduser, etc..."}' https://repomanager.mydomain.net/api/v2/host/packages/installed
        ```
      </td>
    </tr>
    <tr>
      <td>/host/packages/available<br><code>PUT</code></td>
      <td><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td><code>available_packages</code> (required)<br>Each package must be separated by a comma and contains the package name and the version number separated by a pipe</td>
      <td>Send list of available packages (can be updated) to Repomanager</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X PUT -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" -H "Content-Type: application/json" -d '{"available_packages":"accountsservice|22.07.6-2ubuntu1,add-apt-key|1.0-0.6,adduser|3.118ubuntu5, etc..."}' https://repomanager.mydomain.net/api/v2/host/packages/available
        ```
      </td>
    </tr>
    <tr>
      <td>/host/packages/event<br><code>PUT</code></td>
      <td><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td><code>events</code> (required)</td>
      <td>Send package event history (installed / upgraded / removed / downgraded) to Repomanager</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body --post301 -L -s -X PUT -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" -H "Content-Type: application/json" -d '
        {
            "events": [
                {
                    "date_start": "2022-11-01",
                    "date_end": "2022-11-01",
                    "time_start": "12:47:30",
                    "time_end": "12:47:57",
                    "command": "/usr/bin/apt upgrade",
                    "upgraded": [
                        {
                            "name": "firefox-locale-en",
                            "version": "106.0.3+linuxmint1+vanessa"
                        },
                        {
                            "name": "php8.1-opcache",
                            "version": "8.1.12-1+ubuntu22.04.1+deb.sury.org+1"
                        },
                    ],
                },
                {
                    "date_start": "2022-11-05",
                    "date_end": "2022-11-05",
                    "time_start": "12:21:40",
                    "time_end": "12:21:41",
                    "command": "/usr/bin/apt install php8.1-curl",
                    "installed": [
                        {
                            "name": "php8.1-curl",
                            "version": "8.1.12-1+ubuntu22.04.1+deb.sury.org+1"
                        }
                    ]
                },
                {
                    "date_start": "2022-11-16",
                    "date_end": "2022-11-16",
                    "time_start": "16:26:15",
                    "time_end": "16:26:20",
                    "command": "/usr/bin/apt autoremove",
                    "removed": [
                        {
                            "name": "linux-headers-5.15.0-50-generic",
                            "version": "5.15.0-50.56"
                        },
                        {
                            "name": "linux-modules-5.15.0-50-generic",
                            "version": "5.15.0-50.56"
                        }
                    ]
                }
            ]
        }' https://repomanager.mydomain.net/api/v2/host/packages/event
        ```
      </td>
    </tr>
    <tr>
      <td>/profile<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code><br>or<br><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td></td>
      <td>Retrieve all available profile configurations</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" https://repomanager.mydomain.net/api/v2/profile
        ```
      </td>
    </tr>
    <tr>
      <td>/profile/<code>&lt;PROFILE&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code><br>or<br><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td></td>
      <td>Retrieve profile's global configuration</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" https://repomanager.mydomain.net/api/v2/profile/app_server
        ```
      </td>
    </tr>
    <tr>
      <td>/profile/<code>&lt;PROFILE&gt;</code>/excludes<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code><br>or<br><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td></td>
      <td>Retrieve profile's package exclusion configuration</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" https://repomanager.mydomain.net/api/v2/profile/app_server/excludes
        ```
      </td>
    </tr>
    <tr>
      <td>/profile/<code>&lt;PROFILE&gt;</code>/repos<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code><br>or<br><code>&lt;HOST_ID&gt;</code> and <code>&lt;HOST_TOKEN&gt;</code></td>
      <td></td>
      <td>Retrieve profile's repository configuration</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Host <HOST_ID>:<HOST_TOKEN>" https://repomanager.mydomain.net/api/v2/profile/app_server/repos
        ```
      </td>
    </tr>
  </tbody>
</table>


### Hosts listing

<table>
  <thead>
    <tr>
      <th style="min-width: 250px">Endpoint and method</th>
      <th style="min-width: 150px">Authentication</th>
      <th style="min-width: 200px">Parameter(s)</th>
      <th style="min-width: 300px">Description</th>
      <th>Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>/hosts/<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all hosts</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/os/<code>&lt;OS&gt;</code>/<code>&lt;OS_VERSION?&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>os</code> (required, in URL)<br><code>os_version</code> (optional, in URL)</td>
      <td>List hosts by OS name and optionally by OS version. Use <code>%20</code> to encode spaces in OS names (e.g. <code>linux%20mint</code>).</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/os/ubuntu/
        ```
        With OS version:
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/os/ubuntu/26.04
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/kernel/<code>&lt;KERNEL&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>kernel</code> (required, in URL)</td>
      <td>List hosts by kernel version</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/kernel/5.15.0-89-generic
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/arch/<code>&lt;ARCH&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>arch</code> (required, in URL)</td>
      <td>List hosts by architecture</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/arch/x86_64
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/profile/<code>&lt;PROFILE&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>profile</code> (required, in URL)</td>
      <td>List hosts by profile name</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/profile/app_server
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/environment/<code>&lt;ENV&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>environment</code> (required, in URL)</td>
      <td>List hosts by environment</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/environment/prod
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/package/<code>&lt;PACKAGE&gt;</code>/<code>&lt;VERSION?&gt;</code><br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td><code>package</code> (required, in URL)<br><code>version</code> (optional, in URL)</td>
      <td>List hosts that have the specified package installed, optionally filtered by version</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/package/nginx
        ```
        With version:
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/package/nginx/1.24.0-1
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/uptodate<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all up-to-date hosts (hosts with no or few available updates based on configured threshold)</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/uptodate
        ```
      </td>
    </tr>
    <tr>
      <td>/hosts/outdated<br><code>GET</code></td>
      <td><code>&lt;APIKEY&gt;</code></td>
      <td></td>
      <td>List all outdated hosts (hosts with available updates exceeding the configured threshold)</td>
      <td markdown="block">
        ```bash
        curl --fail-with-body -L -s -X GET -H "Authorization: Bearer <APIKEY>" https://repomanager.mydomain.net/api/v2/hosts/outdated
        ```
      </td>
    </tr>
  </tbody>
</table>

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
