## Error logs

Most Repomanager errors are caught and displayed through the web interface. Here are the locations of log files that can help with debugging or opening an issue:

Enter the container:

```
docker exec -it repomanager /bin/bash
```

Tail error logs:

```
tail -f /var/log/nginx/repomanager_error.log
```

## Operation errors

List of errors that can occur during Repomanager operations, with their meaning and how to resolve them.

### Mirroring repository


**DEB**

| Error title | Message | Description |
|---|---|---|
| `Release file not found` | No Release file has been found in the source repository xxxxx (looked for InRelease, Release and Release.gpg) | This means that no Release file (InRelease, Release, or Release.gpg) could be downloaded from the source repository. The repository might be temporarily inaccessible, no longer exist, or the URL could be incorrect. |
| `GPG signature check failed` | No GPG key could verify the signature of downloaded file xxxxx | This means that no GPG key (imported into Repomanager) is able to verify the GPG signature of the downloaded file.<br><br>To address this issue, retrieve the GPG public key of the source repository's publisher and import it into Repomanager, then retry the operation (see [Import a source repository GPG key](/configuration/source-repositories/#import-a-source-repository-gpg-signing-key) to import a new GPG key). |

**RPM**

| Error title | Message | Description |
|---|---|---|
| `GPG signature check failed` | GPG signature is not OK (unknown GPG signing key ID: xxxxx) | This means that no GPG key (imported into Repomanager) is able to verify the GPG signature of the downloaded file.<br><br>To address this issue, retrieve the GPG public key of the source repository's publisher and import it into Repomanager, then retry the operation (see [Import a source repository GPG key](/configuration/source-repositories/#import-a-source-repository-gpg-signing-key) to import a new GPG key). |

