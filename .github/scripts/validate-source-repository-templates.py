#!/usr/bin/env python3
from pathlib import Path
import sys

import yaml


ROOT = Path(__file__).resolve().parents[2]
TEMPLATES_DIR = ROOT / "www" / "templates" / "source-repositories"
VALID_TYPES = {"deb", "rpm"}


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
        error(errors, path, "`type` must be `deb` or `rpm`")

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
