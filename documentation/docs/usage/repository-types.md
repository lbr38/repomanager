## :material-help-circle: Repository types

When creating a new repository from the **REPOSITORIES** tab, you must choose between three repository types:

| | Curated repository | Local repository | Mirror |
|---|---|---|---|
| **Package types** | `deb`, `rpm` | `deb`, `rpm` | `deb`, `rpm`, `arch` |
| **Content** | All or selected packages from a [source repository](/configuration/source-repositories/): one distribution + one component (deb), one release version (rpm) | Your own packages, uploaded manually | All packages of a source repository, copied as is: one or several distributions with all their selected components, sharing the same pool as upstream (deb), one release version with all its architectures (rpm), one or several repositories such as `core` and `extra` (arch) |
| **Package filtering** (include, exclude, keep latest versions) | Yes | No | No, all packages are mirrored |
| **Metadata** (`Release`, `Packages`, `repomd.xml`, `core.db`...) | Rebuilt by Repomanager | Rebuilt by Repomanager | Upstream files, byte for byte |
| **GPG key checked by clients** | Repomanager signing key | Repomanager signing key | **Upstream key** (e.g. Debian, Rocky Linux, nginx...) |
| **Packages upload, deletion, metadata rebuild** | Yes | Yes | No, snapshots are read-only |
| **Published under** | `https://<FQDN>/repo/` | `https://<FQDN>/repo/` | `https://<FQDN>/mirror/deb/<name>/<environment>/`<br>`https://<FQDN>/mirror/rpm/<name>/<release version>/<environment>/`<br>`https://<FQDN>/mirror/arch/<name>/<environment>/` |
| **Storage** | `repomanager-repo` volume | `repomanager-repo` volume | `repomanager-mirror` volume |
| **Use case** | Provide a controlled copy of an upstream repository: selected architectures and packages, signed with your own GPG key | Distribute internal or custom packages | Provide a public or internal mirror that clients use exactly as the upstream repository, with the upstream GPG key |

All types share the same features: snapshots, environments, groups, description and tags, scheduled tasks, statistics and permissions. They are listed together in the **REPOSITORIES** tab, the type is displayed on the right of each repository card (`local`, `curated` or `mirror`, next to the source repository).

Arch Linux repositories can only be mirrored.

To create a repository of each type, see [Operations on repositories](/usage/operations/).

!!! info "Curated repositories were named mirror repositories before 6.2.0"
    Before version `6.2.0`, curated repositories were named *mirror repositories*. Since `6.2.0`, a *mirror* is an upstream repository copied as is, with its original metadata and signatures.


## :material-filter: Curated repositories

Curated repositories are built from a [source repository](/configuration/source-repositories/): Repomanager retrieves all or only selected packages (include and exclude filters, latest versions only), then rebuilds the repository metadata and signs it with the Repomanager GPG key. Clients check packages with the Repomanager signing key.

A curated repository covers one distribution and one component (deb) or one release version (rpm). Each update creates a new snapshot, see [Update an existing curated repository](/usage/operations/#update-an-existing-curated-repository).

Curated repositories are stored in the `repomanager-repo` volume (`/home/repo` inside the container).


## :material-upload: Local repositories

Local repositories contain your own packages. They are created empty, then packages are uploaded from the web interface or the API (see [Upload package(s) in a repository](/usage/operations/#upload-packages-in-a-repository)). As for curated repositories, the metadata is rebuilt and signed by Repomanager.

Local repositories are stored in the `repomanager-repo` volume (`/home/repo` inside the container).


## :material-content-copy: Mirrors

Mirrors are meant to provide **public or internal mirrors** that clients use exactly as they would use the upstream repository. Nothing is rebuilt: metadata and signatures are the upstream ones, so clients keep checking packages with the **upstream GPG key**, not with the Repomanager signing key.

A mirror can cover several distributions (deb) or repositories (arch) at once. Each sync creates a new snapshot, see [Sync a mirror](/usage/operations/#sync-a-mirror).

Mirrors are stored in the `repomanager-mirror` volume (`/home/mirror` inside the container). See [Standard installation](/getting-started/installation/) and [Backup and restore](/administration/backup-restore/#mirrors).

### Mirrors limitations

- deb: the `$component` variable is not supported in the source repository URL, and the `$dist` variable requires a single distribution.
- deb: installer files (`debian-installer`, udebs) are not mirrored.
- rpm: only `baseurl` source repositories are supported (no `metalink` or `mirrorlist`).
- rpm: many repositories do not sign `repomd.xml`. In this case the sync prints a warning and packages signatures are checked by clients (`gpgcheck=1`).
- rpm: packages declared with a `xml:base` attribute in the upstream metadata are downloaded by clients from that URL, not from the mirror. The sync prints a warning in this case.
- arch: most repositories, including Arch Linux official ones, do not sign their database (`<repo>.db.sig`). In this case the sync prints a note, the snapshot is labelled `Unsigned database`, and packages signatures (`.sig` files, mirrored with the packages) are checked by pacman on clients.
- arch: the `<repo>.db.tar.gz` and `<repo>.files.tar.gz` aliases of upstream mirrors are not created, pacman only uses `<repo>.db` and `<repo>.files`.
- Directory listing of `/mirror/` is disabled by default, see [Enable repository web browsing](/reference/miscellaneous/#enable-repository-web-browsing).
- Mirrors cannot be added to [host profiles](/usage/hosts/#profiles): hosts configured from a profile (e.g. with linupdate) do not get the mirrors configuration automatically, configure them with the commands of the `Install` button (see [Install a repository on a client](/usage/operations/#install-a-repository-on-a-client)).
- As mirrors may be public and heavily used, keep in mind that each access request is stored in the statistics database when statistics are enabled (requests older than one year are deleted).

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
