#!/usr/bin/env python3
from pathlib import Path
from urllib.parse import urlparse
import posixpath
import re
import sys

import yaml


ROOT = Path(__file__).resolve().parents[2]
TEMPLATES_DIR = ROOT / "www" / "templates" / "source-repositories"
VALID_TYPES = {"deb", "rpm", "arch", "alpine", "openbsd"}

# Name of an Alpine Linux key file on clients (/etc/apk/keys/<name>), referenced by the index signature (see Controllers\Utils\Apk::checkKeyName())
ALPINE_KEY_NAME = re.compile(r"^[A-Za-z0-9_@+\-][A-Za-z0-9_.@+\-]*$")

# Name of an OpenBSD signify key file (e.g. openbsd-80-pkg.pub, see Controllers\Utils\Signify::checkKeyName())
OPENBSD_KEY_NAME = re.compile(r"^[A-Za-z0-9_\-][A-Za-z0-9_.\-]*$")

# OpenBSD release version (e.g. 8.0), snapshots are not supported (see Controllers\Mirror\Mirror::checkOpenbsdParams())
OPENBSD_VERSION = re.compile(r"^[0-9]+\.[0-9]+$")


def error(errors, path, message):
    errors.append(f"{path.relative_to(ROOT)}: {message}")


def is_non_empty_string(value):
    return isinstance(value, str) and value.strip() != ""


def require_non_empty_string(errors, path, value, field):
    if not is_non_empty_string(value):
        error(errors, path, f"missing or empty `{field}`")


def require_list(errors, path, value, field):
    if not isinstance(value, list) or len(value) == 0:
        error(errors, path, f"`{field}` must be a non-empty list")
        return False

    return True


def validate_gpgkeys(errors, path, gpgkeys, context):
    if not require_list(errors, path, gpgkeys, f"{context}.gpgkeys"):
        return

    for index, gpgkey in enumerate(gpgkeys):
        key_context = f"{context}.gpgkeys[{index}]"

        if not isinstance(gpgkey, dict):
            error(errors, path, f"`{key_context}` must be a mapping")
            continue

        fingerprint = gpgkey.get("fingerprint")
        link = gpgkey.get("link")

        if not is_non_empty_string(fingerprint) and not is_non_empty_string(link):
            error(errors, path, f"`{key_context}` must define `fingerprint` or `link`")


def validate_deb_distribution(errors, path, distribution, context):
    if not isinstance(distribution, dict):
        error(errors, path, f"`{context}` must be a mapping")
        return

    require_non_empty_string(errors, path, distribution.get("name"), f"{context}.name")
    require_non_empty_string(errors, path, distribution.get("description"), f"{context}.description")

    components = distribution.get("components")
    if require_list(errors, path, components, f"{context}.components"):
        for index, component in enumerate(components):
            component_context = f"{context}.components[{index}]"

            if not isinstance(component, dict):
                error(errors, path, f"`{component_context}` must be a mapping")
                continue

            require_non_empty_string(errors, path, component.get("name"), f"{component_context}.name")

    validate_gpgkeys(errors, path, distribution.get("gpgkeys"), context)


def validate_rpm_releasever(errors, path, releasever, context):
    if not isinstance(releasever, dict):
        error(errors, path, f"`{context}` must be a mapping")
        return

    if releasever.get("name") in (None, ""):
        error(errors, path, f"missing or empty `{context}.name`")

    require_non_empty_string(errors, path, releasever.get("description"), f"{context}.description")
    validate_gpgkeys(errors, path, releasever.get("gpgkeys"), context)


def validate_arch_items(errors, path, items, context, require_description):
    if not require_list(errors, path, items, context):
        return

    for index, item in enumerate(items):
        item_context = f"{context}[{index}]"

        if not isinstance(item, dict):
            error(errors, path, f"`{item_context}` must be a mapping")
            continue

        require_non_empty_string(errors, path, item.get("name"), f"{item_context}.name")

        if require_description:
            require_non_empty_string(errors, path, item.get("description"), f"{item_context}.description")


def validate_keys(errors, path, keys, context, key_name_pattern):
    if not require_list(errors, path, keys, f"{context}.keys"):
        return

    names = set()

    for index, key in enumerate(keys):
        key_context = f"{context}.keys[{index}]"

        if not isinstance(key, dict):
            error(errors, path, f"`{key_context}` must be a mapping")
            continue

        link = key.get("link")
        name = key.get("name")

        # A key is downloaded from its link, or defined by its content and its name
        if is_non_empty_string(key.get("content")):
            if not is_non_empty_string(name):
                error(errors, path, f"`{key_context}` must define `name` with `content`")
                continue
        elif not is_non_empty_string(link) or not re.match(r"^https?://", link):
            error(errors, path, f"`{key_context}` must define a http(s) `link` or `content`")
            continue

        # Without name, the key is named after the file name of its link
        if not is_non_empty_string(name):
            name = posixpath.basename(urlparse(link).path)

        if not key_name_pattern.match(name):
            error(errors, path, f"`{key_context}` has an invalid key name `{name}`, it must be the name of the key file on clients (set `name` if the link file name is not)")
        elif name in names:
            error(errors, path, f"`{key_context}` duplicate key name `{name}`")

        names.add(name)


def validate_repository(errors, path, repository, index, expected_type):
    context = f"repositories[{index}]"

    if not isinstance(repository, dict):
        error(errors, path, f"`{context}` must be a mapping")
        return

    require_non_empty_string(errors, path, repository.get("name"), f"{context}.name")
    require_non_empty_string(errors, path, repository.get("description"), f"{context}.description")
    require_non_empty_string(errors, path, repository.get("url"), f"{context}.url")

    repo_type = repository.get("type")
    if repo_type != expected_type:
        error(errors, path, f"`{context}.type` must be `{expected_type}`")

    if expected_type == "deb":
        distributions = repository.get("distributions")
        if require_list(errors, path, distributions, f"{context}.distributions"):
            for distribution_index, distribution in enumerate(distributions):
                validate_deb_distribution(errors, path, distribution, f"{context}.distributions[{distribution_index}]")

    # Arch Linux repositories (core, extra...) and architectures, GPG keys are optional as most repositories do not sign their database
    if expected_type == "arch":
        validate_arch_items(errors, path, repository.get("repos"), f"{context}.repos", True)
        validate_arch_items(errors, path, repository.get("archs"), f"{context}.archs", False)

        if "gpgkeys" in repository:
            validate_gpgkeys(errors, path, repository.get("gpgkeys"), context)

    # Alpine Linux branches (v3.24, edge...) and repositories (main, community...) replace $branch and $repo in the URL:
    # required if the URL contains them, meaningless otherwise (single repository, e.g. https://apk.corretto.aws)
    if expected_type == "alpine":
        url = repository.get("url") if is_non_empty_string(repository.get("url")) else ""

        for field, variable in (("branches", "$branch"), ("repos", "$repo")):
            if variable in url:
                validate_arch_items(errors, path, repository.get(field), f"{context}.{field}", True)
            elif field in repository:
                error(errors, path, f"`{context}.{field}` is defined but `{context}.url` does not contain `{variable}`")

        validate_arch_items(errors, path, repository.get("archs"), f"{context}.archs", False)

        # RSA keys are optional: an unsigned repository is mirrored with the signature check disabled
        if "keys" in repository:
            validate_keys(errors, path, repository.get("keys"), context, ALPINE_KEY_NAME)

        if "gpgkeys" in repository:
            error(errors, path, f"`{context}.gpgkeys` is not used by alpine repositories, signing keys are RSA keys defined in `{context}.keys`")

    # OpenBSD versions (8.0, 7.9...) and platforms (amd64, arm64...) of the tree of an OpenBSD mirror, signed with signify keys
    if expected_type == "openbsd":
        versions = repository.get("versions")
        validate_arch_items(errors, path, versions, f"{context}.versions", True)

        for version_index, version in enumerate(versions if isinstance(versions, list) else []):
            if not isinstance(version, dict):
                continue

            # Unquoted, a version is parsed as a number (8.0 would become 8)
            if not isinstance(version.get("name"), str):
                error(errors, path, f"`{context}.versions[{version_index}].name` must be a quoted string (e.g. '8.0')")
            elif not OPENBSD_VERSION.match(version.get("name")):
                error(errors, path, f"`{context}.versions[{version_index}].name` must be a release version (e.g. 8.0), snapshots are not supported")

        validate_arch_items(errors, path, repository.get("archs"), f"{context}.archs", False)

        # Signify keys are optional: files lists are then mirrored with the signature check disabled
        if "keys" in repository:
            validate_keys(errors, path, repository.get("keys"), context, OPENBSD_KEY_NAME)

        if "gpgkeys" in repository:
            error(errors, path, f"`{context}.gpgkeys` is not used by openbsd repositories, signing keys are signify keys defined in `{context}.keys`")

    if expected_type == "rpm":
        releasevers = repository.get("releasever")
        if require_list(errors, path, releasevers, f"{context}.releasever"):
            for releasever_index, releasever in enumerate(releasevers):
                validate_rpm_releasever(errors, path, releasever, f"{context}.releasever[{releasever_index}]")


def validate_file(path):
    errors = []

    try:
        with path.open("r", encoding="utf-8") as stream:
            data = yaml.safe_load(stream)
    except yaml.YAMLError as exception:
        error(errors, path, f"invalid YAML: {exception}")
        return errors

    if not isinstance(data, dict):
        error(errors, path, "root document must be a mapping")
        return errors

    require_non_empty_string(errors, path, data.get("description"), "description")

    template_type = data.get("type")
    if template_type not in VALID_TYPES:
        error(errors, path, "`type` must be `deb`, `rpm`, `arch`, `alpine` or `openbsd`")

    repositories = data.get("repositories")
    if require_list(errors, path, repositories, "repositories") and template_type in VALID_TYPES:
        names = set()

        for index, repository in enumerate(repositories):
            if isinstance(repository, dict) and repository.get("name") in names:
                error(errors, path, f"duplicate repository name `{repository.get('name')}`")
            elif isinstance(repository, dict) and is_non_empty_string(repository.get("name")):
                names.add(repository.get("name"))

            validate_repository(errors, path, repository, index, template_type)

    return errors


def main():
    paths = sorted(TEMPLATES_DIR.glob("*/*.yml"))

    if not paths:
        print("No source repository templates found.", file=sys.stderr)
        return 1

    errors = []

    for path in paths:
        errors.extend(validate_file(path))

    if errors:
        print("Source repository template validation failed:", file=sys.stderr)

        for validation_error in errors:
            print(f"- {validation_error}", file=sys.stderr)

        return 1

    print(f"Validated {len(paths)} source repository templates.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
