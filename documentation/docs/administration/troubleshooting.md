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

### Syncing a curated repository

The error message is displayed in the task log, under the task step that failed.

**DEB**

| Task step | Message | Description |
|---|---|---|
| `GETTING INRELEASE / RELEASE FILE` | No InRelease or Release file has been found in the source repository xxxxx (looked for InRelease, Release and Release.gpg). Is the URL of the repository correct? | This means that no Release file (InRelease, Release, or Release.gpg) could be downloaded from the source repository. The repository might be temporarily inaccessible, no longer exist, or the URL could be incorrect. |
| `CHECKING INRELEASE FILE GPG SIGNATURE`<br>or<br>`CHECKING RELEASE FILE GPG SIGNATURE` | GPG signature check failed: No GPG key could verify the signature of xxxxx file. Please check that you have imported the GPG key used to sign the repository. | This means that no GPG key (imported into Repomanager) is able to verify the GPG signature of the Release file.<br><br>To address this issue, retrieve the GPG public key of the source repository's publisher and import it into Repomanager, then retry the operation (see [Import a source repository GPG key](/configuration/source-repositories/#import-a-source-repository-gpg-signing-key) to import a new GPG key).<br><br>This message is displayed when the `WHEN RELEASE FILE SIGNATURE IS INVALID` setting is set to `End sync task with error` (see [Deb curated repositories settings](/configuration/settings/#deb-curated-repositories-settings)). |
| `CHECKING INRELEASE FILE GPG SIGNATURE`<br>or<br>`CHECKING RELEASE FILE GPG SIGNATURE` | No InRelease or Release file found with a valid GPG signature. Please check that you have imported the GPG key used to sign the repository. | Same cause and resolution as above.<br><br>This message is displayed when the `WHEN RELEASE FILE SIGNATURE IS INVALID` setting is set to `Ignore and try another Release file if possible`: each Release file with an invalid signature is reported as a warning, and the error is raised when none of them has a valid signature. |

**RPM**

| Task step | Message | Description |
|---|---|---|
| `DOWNLOADING PACKAGE (x/y)` | GPG signature check failed (unknown GPG signing key ID: xxxxx) | This means that no GPG key (imported into Repomanager) is able to verify the GPG signature of the downloaded package.<br><br>To address this issue, retrieve the GPG public key of the source repository's publisher and import it into Repomanager, then retry the operation (see [Import a source repository GPG key](/configuration/source-repositories/#import-a-source-repository-gpg-signing-key) to import a new GPG key).<br><br>This error is raised when the `WHEN PACKAGE SIGNATURE IS INVALID` setting is set to `End sync task with error` (see [Rpm curated repositories settings](/configuration/settings/#rpm-curated-repositories-settings)). With the other values, the same message is displayed as a warning and the package is ignored or downloaded anyway. |

<script data-goatcounter="https://repomanager.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
