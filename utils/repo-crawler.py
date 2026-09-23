#!/usr/bin/env python3
"""
Synchronizes the YAML templates in www/templates/source-repositories with
the actual state of remote repositories (architectures, distributions,
components, releasever), while preserving order, comments, and blank lines.

Reading Zstandard data requires Python 3.14+ or the zstandard package.
"""

from __future__ import annotations

import argparse
import bz2
import difflib
import lzma
import os
import re
import sys
import zlib
from dataclasses import dataclass, field
from io import StringIO
from pathlib import Path
from typing import Any, Callable, TextIO
from urllib.parse import unquote, urljoin, urlparse
from xml.etree import ElementTree

import requests
from bs4 import BeautifulSoup
from requests.adapters import HTTPAdapter
from ruamel.yaml import YAML
from ruamel.yaml.comments import CommentedMap, CommentedSeq
from ruamel.yaml.error import CommentMark, YAMLError
from ruamel.yaml.scalarstring import SingleQuotedScalarString
from ruamel.yaml.tokens import CommentToken
from urllib3.util.retry import Retry


def colorize(message: str, stream: TextIO) -> str:
    if not stream.isatty() or "NO_COLOR" in os.environ or os.environ.get("TERM") == "dumb":
        return message

    text = message.strip()

    if text.startswith(("+++", "---", "@@", "[", "Repository:", "Résumé", "======")):
        color = "36"
    elif text.startswith("-") or re.search(r"ERREUR|ERROR|^Erreur|HTTP 5\d\d", text):
        color = "31"
    elif re.search(
        r"ATTENTION|IGNORE|ignor|exclu|supprim|NON MODIFIÉ|désactiv|dry-run|"
        r"Aucun dépôt|Aucun template|HTTP 4\d\d",
        text,
    ):
        color = "33"
    elif text.startswith("+") or re.search(r"ajout|Fichier modifié|Aucun changement", text):
        color = "32"
    elif re.search(r"mises à jour|Templates analysés|Fichiers modifiés|Changements", text):
        color = "36"
    else:
        color = "90"

    body = message.rstrip("\r\n")
    return f"\033[{color}m{body}\033[0m" + message[len(body):]


def print_message(message: str, *, file: TextIO | None = None) -> None:
    stream = file if file is not None else sys.stdout
    print(colorize(message, stream), file=stream)


# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

def load_configuration(path: Path) -> dict[str, Any]:
    try:
        with path.open(encoding="utf-8") as source:
            config = YAML(typ="safe").load(source)
    except (OSError, YAMLError) as exc:
        raise ValueError(f"{path}: {exc}") from exc

    if not isinstance(config, dict):
        raise ValueError("la configuration doit être un mapping YAML")

    expected = {
        "DEFAULT_TIMEOUT": int,
        "DEFAULT_TEMPLATES_ROOT": str,
        "DEFAULT_RPM_ARCHITECTURES": list,
        "IGNORED_TEMPLATE_NAMES": list,
        "IGNORED_DEB_ARCHITECTURES": list,
        "IGNORED_DEB_SUITES": list,
        "IGNORED_DEB_DIRECTORIES": list,
        "IGNORED_DEB_COMPONENTS": list,
        "MISSING_STATUS_CODES": list,
    }

    if set(config) != set(expected):
        raise ValueError(
            "clés manquantes ou inconnues : "
            + ", ".join(sorted(str(key) for key in set(config) ^ set(expected)))
        )

    for key, kind in expected.items():
        value = config[key]

        if type(value) is not kind:
            raise ValueError(f"{key}: type attendu {kind.__name__}")

        if kind is list:
            item_kind = int if key == "MISSING_STATUS_CODES" else str

            if any(type(item) is not item_kind for item in value):
                raise ValueError(f"{key}: éléments attendus {item_kind.__name__}")

            if key != "DEFAULT_RPM_ARCHITECTURES":
                config[key] = set(value)

    if config["DEFAULT_TIMEOUT"] <= 0:
        raise ValueError("DEFAULT_TIMEOUT doit être positif")

    config["DEFAULT_TEMPLATES_ROOT"] = (
        path.parent / Path(config["DEFAULT_TEMPLATES_ROOT"]).expanduser()
    ).resolve()
    return config


try:
    CONFIGURATION = load_configuration(Path(__file__).resolve().with_suffix(".conf"))
except ValueError as exc:
    print_message(f"Erreur de configuration : {exc}", file=sys.stderr)
    raise SystemExit(2) from exc

DEFAULT_TIMEOUT = CONFIGURATION["DEFAULT_TIMEOUT"]

DEFAULT_TEMPLATES_ROOT = CONFIGURATION["DEFAULT_TEMPLATES_ROOT"]

DEFAULT_RPM_ARCHITECTURES = CONFIGURATION["DEFAULT_RPM_ARCHITECTURES"]

IGNORED_TEMPLATE_NAMES = CONFIGURATION["IGNORED_TEMPLATE_NAMES"]

# Pseudo-architectures listed in Debian Release files
IGNORED_DEB_ARCHITECTURES = CONFIGURATION["IGNORED_DEB_ARCHITECTURES"]

# Development suites never added automatically
IGNORED_DEB_SUITES = CONFIGURATION["IGNORED_DEB_SUITES"]

IGNORED_DEB_DIRECTORIES = CONFIGURATION["IGNORED_DEB_DIRECTORIES"]

# Components never added and removed from templates
IGNORED_DEB_COMPONENTS = CONFIGURATION["IGNORED_DEB_COMPONENTS"]

# Only these codes confirm that a resource is absent. Any other code
# (403, 5xx, timeout...) is indeterminate and does not trigger deletion.
MISSING_STATUS_CODES = CONFIGURATION["MISSING_STATUS_CODES"]


# ---------------------------------------------------------------------------
# Models
# ---------------------------------------------------------------------------

@dataclass
class DebRelease:
    name: str
    components: dict[str, list[str]] = field(default_factory=dict)
    version: str | None = None
    origin: str | None = None


@dataclass
class RpmRelease:
    name: str
    architectures: list[str] = field(default_factory=list)
    # False if at least one architecture could not be checked
    complete: bool = True


@dataclass
class ScanResult:
    success: bool
    # Versions present in the remote repository (existing or to be added)
    releases: dict[str, Any] = field(default_factory=dict)
    # Existing versions confirmed to be absent (HTTP 404/410)
    missing: set[str] = field(default_factory=set)
    error: str | None = None


@dataclass
class SyncOptions:
    add: bool = True
    remove: bool = True
    exclude: list[re.Pattern[str]] = field(default_factory=list)


# ---------------------------------------------------------------------------
# HTTP client
# ---------------------------------------------------------------------------

class HttpClient:
    def __init__(
        self,
        timeout: int = DEFAULT_TIMEOUT,
        retries: int = 2,
        verbose: bool = False,
    ) -> None:
        self.timeout = timeout
        self.verbose = verbose

        self.session = requests.Session()

        retry_config = Retry(
            total=retries,
            connect=retries,
            read=retries,
            backoff_factor=0.5,
            status_forcelist=(429, 500, 502, 503, 504),
            allowed_methods=frozenset(["GET", "HEAD"]),
            raise_on_status=False,
        )

        adapter = HTTPAdapter(max_retries=retry_config)
        self.session.mount("http://", adapter)
        self.session.mount("https://", adapter)

        self.session.headers.update(
            {
                "User-Agent": "repo-crawler/1.0",
                "Accept": "*/*",
            }
        )

    def log(self, message: str) -> None:
        if self.verbose:
            print_message(f"    {message}")

    def get(self, url: str) -> requests.Response | None:
        self.log(f"GET {url}")

        try:
            response = self.session.get(
                url,
                timeout=self.timeout,
                allow_redirects=True,
            )
        except requests.RequestException as exc:
            self.log(f"ERROR {exc}")
            return None

        self.log(f"HTTP {response.status_code} {response.url}")

        return response

    def probe(
        self,
        url: str,
    ) -> tuple[bool | None, requests.Response | None]:
        """
        True: present, False: absent (404/410), None: indeterminate.
        """
        response = self.get(url)

        if response is None:
            return None, None

        if response.status_code == 200:
            return True, response

        if response.status_code in MISSING_STATUS_CODES:
            return False, response

        return None, response

    def exists(self, url: str) -> bool | None:
        """
        Like probe(), but without downloading the response body.
        """
        self.log(f"GET (statut) {url}")

        try:
            with self.session.get(
                url,
                timeout=self.timeout,
                stream=True,
            ) as response:
                status = response.status_code
        except requests.RequestException as exc:
            self.log(f"ERROR {exc}")
            return None

        self.log(f"HTTP {status}")

        if status == 200:
            return True

        if status in MISSING_STATUS_CODES:
            return False

        return None

    def get_prefix(self, url: str, size: int = 65536) -> bytes | None:
        """
        Reads only the first bytes of a (potentially large) resource.
        """
        self.log(f"GET (début) {url}")

        try:
            with self.session.get(
                url,
                timeout=self.timeout,
                stream=True,
            ) as response:
                if response.status_code != 200:
                    return None

                return next(response.iter_content(size), b"")
        except requests.RequestException as exc:
            self.log(f"ERROR {exc}")
            return None


# ---------------------------------------------------------------------------
# Generic helpers
# ---------------------------------------------------------------------------

def get_name(item: Any) -> str | None:
    if not isinstance(item, dict):
        return None

    name = item.get("name")
    if name is None:
        return None

    return str(name)


def get_names(items: Any) -> list[str]:
    names: list[str] = []

    for item in items or []:
        name = get_name(item)

        if name is not None:
            names.append(name)

    return names


def yaml_name(name: str) -> str | int:
    """
    Integer versions remain numeric; dotted versions are single-quoted
    strings to prevent them from being parsed as YAML floats.
    """
    if name.isdigit() and str(int(name)) == name:
        return int(name)

    if re.fullmatch(r"\d+(?:\.\d+)+", name):
        return SingleQuotedScalarString(name)

    return name


def version_key(value: Any) -> tuple[int, ...] | None:
    if value is None:
        return None

    parts = re.findall(r"\d+", str(value))

    if not parts:
        return None

    return tuple(int(part) for part in parts)


def name_pattern(name: str) -> re.Pattern[str]:
    """
    Pattern derived from an existing name: '10' accepts any number, '8.10'
    any pair of numbers, and 'trixie-security' any '<word>-security'.
    """
    pattern = re.sub(r"\d+", r"\\d+", re.escape(name))
    pattern = re.sub(r"^[a-z]+", "[a-z]+", pattern)

    return re.compile(f"^{pattern}$")


def is_new_candidate(
    name: str,
    existing: list[str],
    exclude: list[re.Pattern[str]],
) -> bool:
    """
    A discovered name is proposed for addition only if it resembles the
    existing names (avoids adding 8.10, 10-kitten, trixie-backports, etc.).
    """
    if name in existing:
        return False

    if any(pattern.search(name) for pattern in exclude):
        return False

    if not existing:
        return True

    return any(name_pattern(item).match(name) for item in existing)


def replace_word(text: str, old: str, new: str) -> str:
    return re.sub(
        rf"(?<![\w.]){re.escape(old)}(?![\w.])",
        lambda _: new,
        text,
    )


def make_yaml_map(**values: Any) -> CommentedMap:
    result = CommentedMap()

    for key, value in values.items():
        if value is not None:
            result[key] = value

    return result


def make_yaml_seq(values: list[Any]) -> CommentedSeq:
    result = CommentedSeq()

    for value in values:
        result.append(value)

    return result


def normalize_architectures(values: list[str]) -> list[str]:
    """
    Supprime les doublons tout en conservant l'ordre.
    """
    result: list[str] = []

    for value in values:
        if value not in result:
            result.append(value)

    return result


def parse_directory_listing(base_url: str, html: str) -> list[str]:
    """
    Extracts directory names from a typical HTML listing.

    Works, in particular, with listings generated by Apache/nginx.
    """
    soup = BeautifulSoup(html, "html.parser")
    result: list[str] = []

    for link in soup.find_all("a", href=True):
        href = str(link["href"])

        if href.startswith("?") or href.startswith("#"):
            continue

        if not href.endswith("/"):
            continue

        parsed = urlparse(href)

        if parsed.query or parsed.fragment:
            continue

        name = unquote(parsed.path.rstrip("/").split("/")[-1])

        if not name:
            continue

        if name in {".", ".."}:
            continue

        if name not in result:
            result.append(name)

    return result


def list_directories(
    client: HttpClient,
    url: str,
) -> tuple[bool, list[str], str | None]:
    """
    Returns:

    - True if the listing could be accessed;
    - the list of directories found;
    - any error encountered.

    The success boolean prevents versions from being deleted when the server
    is temporarily unavailable.
    """
    if not url.endswith("/"):
        url += "/"

    response = client.get(url)

    if response is None:
        return False, [], "connexion impossible"

    if response.status_code != 200:
        return False, [], f"HTTP {response.status_code}"

    directories = parse_directory_listing(url, response.text)

    return True, directories, None


def parse_debian_release(text: str) -> dict[str, str]:
    """
    Parses the main fields of a Debian Release/InRelease file.

    Exemple :

        Components: main contrib non-free
        Architectures: amd64 arm64
    """
    fields: dict[str, str] = {}

    for line in text.splitlines():
        if not line or line.startswith(" "):
            continue

        match = re.match(r"^([A-Za-z0-9_-]+):\s*(.*)$", line)

        if match:
            key = match.group(1)
            value = match.group(2).strip()
            fields[key] = value

    return fields


def parse_debian_index_sizes(text: str) -> dict[str, int]:
    """
    File sizes listed in the first hash section of a Release file:
    {'main/binary-amd64/Packages': 32757, ...}.
    """
    for section in ("SHA512", "SHA256", "SHA1", "MD5Sum"):
        sizes: dict[str, int] = {}
        inside = False

        for line in text.splitlines():
            if line.startswith(f"{section}:"):
                inside = True
                continue

            if not inside:
                continue

            if not line.startswith(" "):
                break

            parts = line.split()

            if len(parts) == 3 and parts[1].isdigit():
                sizes[parts[2]] = int(parts[1])

        if sizes:
            return sizes

    return {}


def debian_index_available(
    client: HttpClient,
    distribution_url: str,
    paths: list[str],
    listings: dict[str, list[str] | None],
) -> bool | None:
    """
    Checks whether an index declared in Release actually exists: Ubuntu
    lists on archive.ubuntu.com architectures served from ports.
    """
    parent, _, directory = paths[0].rsplit("/", 1)[0].rpartition("/")

    if parent not in listings:
        success, directories, _ = list_directories(
            client,
            urljoin(distribution_url, parent + "/"),
        )
        # A page without any binary-* directories is not a usable listing
        usable = success and any(d.startswith("binary-") for d in directories)
        listings[parent] = directories if usable else None

    directories = listings[parent]

    if directories is not None:
        return directory in directories

    statuses = []

    for path in paths:
        status = client.exists(urljoin(distribution_url, path))

        if status:
            return True

        statuses.append(status)

    return False if all(status is False for status in statuses) else None


def fetch_debian_release(
    client: HttpClient,
    base_url: str,
    distribution: str,
) -> tuple[bool | None, DebRelease | None, dict[str, str], str | None]:
    """
    Checks InRelease and then Release for a Debian distribution.

    The status is True (found), False (absent), or None (indeterminate).
    """
    distribution_url = urljoin(base_url, f"dists/{distribution}/")

    statuses: list[bool | None] = []
    response: requests.Response | None = None

    for filename in ("InRelease", "Release"):
        status, current = client.probe(urljoin(distribution_url, filename))
        statuses.append(status)

        if status:
            response = current
            break

    if response is None:
        if all(status is False for status in statuses):
            return False, None, {}, "Release/InRelease introuvable"

        return None, None, {}, "Release/InRelease inaccessible"

    fields = parse_debian_release(response.text)

    raw_components = fields.get("Components", "").split()

    architectures = [
        arch
        for arch in fields.get("Architectures", "").split()
        if arch not in IGNORED_DEB_ARCHITECTURES
    ]

    if not raw_components:
        return None, None, fields, "champ Components absent"

    sizes = parse_debian_index_sizes(response.text)
    components: dict[str, list[str]] = {}
    listings: dict[str, list[str] | None] = {}

    for raw in raw_components:
        # Security repositories expose 'updates/main' as an alias for 'main'
        name = raw.removeprefix("updates/")

        if name in IGNORED_DEB_COMPONENTS:
            client.log(f"{distribution}/{name} ignoré : composant exclu")
            continue

        if not architectures or not sizes:
            components[name] = list(architectures)
            continue

        # A missing or zero-sized Packages index provides no packages.
        # Release paths may omit the 'updates/' prefix.
        def index_paths(arch: str) -> list[str]:
            for base in (raw, name):
                # Formats read by Deb.php; prefer the uncompressed size
                paths = [
                    f"{base}/binary-{arch}/Packages{extension}"
                    for extension in ("", ".gz", ".bz2", ".xz")
                    if f"{base}/binary-{arch}/Packages{extension}" in sizes
                ]

                if paths:
                    return paths

            return []

        with_packages: list[str] = []

        for arch in architectures:
            paths = index_paths(arch)

            if not paths or sizes[paths[0]] == 0:
                continue

            # Indeterminate: keep the architecture
            if debian_index_available(
                client, distribution_url, paths, listings
            ) is False:
                client.log(
                    f"{distribution}/{name}/{arch} ignorée : "
                    "index déclaré mais absent du serveur"
                )
                continue

            with_packages.append(arch)

        if with_packages:
            components[name] = with_packages
        else:
            client.log(f"{distribution}/{name} ignoré : aucun paquet")

    release = DebRelease(
        name=distribution,
        components=components,
        version=fields.get("Version") or None,
        origin=fields.get("Origin") or None,
    )

    return True, release, fields, None


def deb_skip_reason(name: str, fields: dict[str, str]) -> str | None:
    """
    Reason for not automatically adding a discovered distribution.
    """
    codename = fields.get("Codename")
    suite = fields.get("Suite", "")

    if codename and codename != name:
        return f"alias de {codename}"

    if suite.split("-")[0] in IGNORED_DEB_SUITES:
        return f"suite de développement ({suite})"

    return None


# ---------------------------------------------------------------------------
# Debian scanner
# ---------------------------------------------------------------------------

def scan_debian_repository(
    client: HttpClient,
    repository: dict[str, Any],
    exclude: list[re.Pattern[str]],
) -> ScanResult:
    url = str(repository.get("url") or "").strip().rstrip("/")

    if not url:
        return ScanResult(False, error="URL Debian absente")

    existing = get_names(repository.get("distributions"))
    ignored = repository.get("ignored-distributions", [])

    if not isinstance(ignored, list) or any(
        not isinstance(name, str) for name in ignored
    ):
        return ScanResult(
            False,
            error="ignored-distributions doit être une liste de chaînes",
        )

    if ignored:
        print_message("    Distributions exclues : " + ", ".join(sorted(set(ignored))))

    ignored = set(ignored)

    # A URL containing $dist has one repository per distribution; any listing
    # is done on the part before $dist.
    if "$dist" in url:
        listing_url = url.split("$dist", 1)[0]
    else:
        listing_url = urljoin(url + "/", "dists/")

    def repository_url(distribution: str) -> str:
        return url.replace("$dist", distribution) + "/"

    listing_success, listed, error = list_directories(client, listing_url)

    if not listing_success:
        if not existing:
            return ScanResult(False, error=error)

        client.log(f"listing indisponible ({error}), vérification seule")

    candidates = existing + [
        name
        for name in listed
        if name not in IGNORED_DEB_DIRECTORIES
        and is_new_candidate(name, existing, exclude)
    ]

    result = ScanResult(True)

    for distribution in candidates:
        is_new = distribution not in existing

        if distribution in ignored:
            if not is_new:
                result.missing.add(distribution)
            client.log(f"{distribution} ignorée : exclusion explicite du dépôt")
            continue

        status, release, fields, release_error = fetch_debian_release(
            client,
            repository_url(distribution),
            distribution,
        )

        if status is False:
            if not is_new:
                result.missing.add(distribution)
            continue

        if release is None:
            client.log(f"{distribution} ignorée : {release_error}")
            continue

        # Keep architectures with indeterminate status: empty means confirmed
        if not release.components:
            if not is_new:
                result.missing.add(distribution)
            client.log(f"{distribution} ignorée : aucun paquet")
            continue

        if is_new:
            reason = deb_skip_reason(distribution, fields)

            if reason:
                client.log(f"{distribution} ignorée : {reason}")
                continue

        result.releases[distribution] = release

    return result


# ---------------------------------------------------------------------------
# RPM scanner
# ---------------------------------------------------------------------------

def rpm_root_url(repository_url: str) -> str | None:
    marker = "$releasever"

    if marker not in repository_url:
        return None

    root = repository_url.split(marker, 1)[0]

    if not root.endswith("/"):
        root += "/"

    return root


def decompress_zstd_prefix(data: bytes, size: int = 8192) -> bytes:
    try:
        from compression import zstd
    except ImportError:
        try:
            import zstandard
        except ImportError as exc:
            raise ValueError(
                "Zstandard indisponible : installer le paquet zstandard "
                "ou utiliser Python 3.14+"
            ) from exc

        try:
            with zstandard.ZstdDecompressor().stream_reader(data) as reader:
                return reader.read(size)
        except zstandard.ZstdError as exc:
            raise ValueError(str(exc)) from exc

    try:
        return zstd.ZstdDecompressor().decompress(data, size)
    except zstd.ZstdError as exc:
        raise ValueError(str(exc)) from exc


def rpm_package_count(
    client: HttpClient,
    metadata_url: str,
    repomd: str,
) -> int | None:
    """
    Number of packages declared in the primary.xml header, or None if it
    cannot be read or uses an unsupported format.
    """
    try:
        root = ElementTree.fromstring(repomd)
    except ElementTree.ParseError:
        return None

    if root.tag.rsplit("}", 1)[-1] != "repomd":
        return None

    href: str | None = None

    for entry in root:
        if entry.tag.rsplit("}", 1)[-1] != "data" or entry.get("type") != "primary":
            continue
        for location in entry:
            if location.tag.rsplit("}", 1)[-1] == "location":
                href = location.get("href")
                break
        break

    if not href:
        return None

    base = metadata_url.rsplit("repodata/", 1)[0]
    data = client.get_prefix(urljoin(base, href))

    if not data:
        return None

    try:
        path = urlparse(href).path

        if path.endswith(".gz"):
            data = zlib.decompressobj(zlib.MAX_WBITS | 16).decompress(
                data, 8192
            )
        elif path.endswith(".xz"):
            data = lzma.LZMADecompressor().decompress(data, 8192)
        elif path.endswith(".bz2"):
            data = bz2.BZ2Decompressor().decompress(data, 8192)
        elif path.endswith(".zst"):
            data = decompress_zstd_prefix(data)
        elif not path.endswith(".xml"):
            return None
    except (zlib.error, lzma.LZMAError, OSError, ValueError, EOFError) as exc:
        client.log(f"Métadonnées RPM illisibles ({href}) : {exc}")
        return None

    parser = ElementTree.XMLPullParser(events=("start",))

    try:
        parser.feed(data)
        for _, element in parser.read_events():
            if element.tag.rsplit("}", 1)[-1] != "metadata":
                return None
            count = element.get("packages", "")
            return int(count) if count.isascii() and count.isdigit() else None
    except (ElementTree.ParseError, ValueError):
        return None

    return None


def check_rpm_metadata(
    client: HttpClient,
    repository_url: str,
    releasever: str,
    arch: str | None,
) -> tuple[bool | None, bool]:
    """
    Returns (whether repomd.xml is present, whether it contains any packages).
    """
    url = repository_url.replace("$releasever", releasever)

    if arch is not None:
        url = url.replace("$basearch", arch)

    url = url.rstrip("/") + "/repodata/repomd.xml"

    status, response = client.probe(url)

    if not status or response is None:
        return status, False

    count = rpm_package_count(client, url, response.text)

    if count is None:
        return None, False

    return True, count > 0


def scan_rpm_repository(
    client: HttpClient,
    repository: dict[str, Any],
    rpm_architectures: list[str],
    exclude: list[re.Pattern[str]],
) -> ScanResult:
    repository_url = str(repository.get("url") or "").strip()

    if not repository_url:
        return ScanResult(False, error="URL RPM absente")

    existing = get_names(repository.get("releasever"))
    root = rpm_root_url(repository_url)
    listed: list[str] = []

    if root is None:
        if not existing:
            return ScanResult(
                False,
                error="URL RPM sans variable $releasever",
            )
    else:
        listing_success, listed, error = list_directories(client, root)

        if not listing_success:
            if not existing:
                return ScanResult(False, error=error)

            client.log(f"listing indisponible ({error}), vérification seule")

    candidates = existing + [
        name
        for name in listed
        if is_new_candidate(name, existing, exclude)
    ]

    # Without $basearch, only the presence of the release can be checked
    architectures: list[str | None] = (
        list(rpm_architectures)
        if "$basearch" in repository_url
        else [None]
    )

    result = ScanResult(True)

    for releasever in candidates:
        release_architectures = list(architectures)

        if "$basearch" in repository_url:
            for item in repository.get("releasever", []) or []:
                if get_name(item) == releasever:
                    for arch in item.get("archs", []) or []:
                        if str(arch) not in release_architectures:
                            release_architectures.append(str(arch))

        checks = {
            arch: check_rpm_metadata(
                client,
                repository_url,
                releasever,
                arch,
            )
            for arch in release_architectures
        }
        present = [arch for arch, (status, _) in checks.items() if status]
        with_packages = [
            arch for arch, (status, filled) in checks.items()
            if status and filled
        ]

        if with_packages:
            result.releases[releasever] = RpmRelease(
                name=releasever,
                architectures=[
                    arch for arch in with_packages if arch is not None
                ],
                complete=all(
                    status is not None for status, _ in checks.values()
                ),
            )
        elif present:
            if all(status is not None for status, _ in checks.values()):
                if releasever in existing:
                    result.missing.add(releasever)
                client.log(f"{releasever} : aucun paquet, ignorée")
            else:
                client.log(f"{releasever} : état indéterminé, ignorée")
        elif all(status is False for status, _ in checks.values()):
            if releasever in existing:
                result.missing.add(releasever)
            client.log(f"{releasever} : aucun repomd.xml trouvé")
        else:
            client.log(f"{releasever} : état indéterminé, ignorée")

    return result


# ---------------------------------------------------------------------------
# YAML comments
# ---------------------------------------------------------------------------

# ruamel.yaml attaches blank lines and comments between list items to the
# previous item's last scalar, and the first item's comment to the parent key.
# Adding, removing, or inserting an item without moving them shifts comments,
# so they are detached and then reattached to the correct item.

Slot = tuple[Any, Any]


def is_node(value: Any) -> bool:
    return isinstance(value, (CommentedMap, CommentedSeq))


def last_scalar_slot(container: Any, key: Any) -> Slot:
    value = container[key]

    while is_node(value) and len(value) > 0:
        container = value
        key = (
            list(value.keys())[-1]
            if isinstance(value, CommentedMap)
            else len(value) - 1
        )
        value = container[key]

    return container, key


def _token_index(container: Any) -> int:
    return 2 if isinstance(container, CommentedMap) else 0


def get_token(slot: Slot) -> CommentToken | None:
    container, key = slot
    entry = container.ca.items.get(key)

    if not entry:
        return None

    return entry[_token_index(container)]


def set_token(slot: Slot, token: CommentToken | None) -> None:
    container, key = slot
    entry = container.ca.items.setdefault(key, [None, None, None, None])
    entry[_token_index(container)] = token


def pop_gap(slot: Slot) -> str:
    """
    Detaches and returns anything after the scalar's line ending (blank lines
    and comments). An end-of-line comment is preserved.
    """
    token = get_token(slot)

    if token is None:
        return ""

    value = token.value

    if value.startswith("#"):
        eol, _, gap = value.partition("\n")
        token.value = eol + "\n"
    else:
        gap = value[1:] if value.startswith("\n") else value
        set_token(slot, None)

    return gap


def push_gap(slot: Slot, gap: str) -> None:
    if not gap:
        return

    token = get_token(slot)

    if token is not None:
        token.value = token.value + gap
    else:
        set_token(slot, CommentToken("\n" + gap, CommentMark(0)))


def get_head(sequence: CommentedSeq) -> str:
    comment = sequence.ca.comment

    if not comment or len(comment) < 2 or not comment[1]:
        return ""

    return "".join(
        " " * (token.column or 0) + token.value
        for token in comment[1]
    )


def set_head(
    parent: CommentedMap,
    key: str,
    sequence: CommentedSeq,
    text: str,
) -> None:
    text = text.lstrip("\n")
    comment = sequence.ca.comment
    before = comment[0] if comment else None
    entry = parent.ca.items.get(key)

    if "#" not in text:
        sequence.ca.comment = [before, []] if before else None

        if entry and len(entry) > 3:
            entry[3] = None
        return

    stripped = text.lstrip(" ")
    token = CommentToken(stripped, CommentMark(len(text) - len(stripped)))

    sequence.ca.comment = [before, [token]]
    parent.ca.items.setdefault(key, [None, None, None, None])[3] = [token]


def rebuild_sequence(
    parent: CommentedMap,
    key: str,
    new_items: list[Any],
    headers: dict[int, str | None] | None = None,
) -> None:
    """
    Replaces parent[key] with new_items. Each retained item keeps its header
    comment, the end of the list keeps its separator, and new items receive a
    separator consistent with the others (blank line, or '# <description>'
    comment if the list uses them).
    """
    sequence = parent[key]
    old_items = list(sequence)
    headers = headers or {}

    def ident(item: Any) -> Any:
        return id(item) if is_node(item) else ("scalar", str(item))

    gaps = [
        pop_gap(last_scalar_slot(sequence, index))
        for index in range(len(old_items))
    ]
    entries = {
        ident(item): sequence.ca.items.get(index)
        for index, item in enumerate(old_items)
    }
    head = get_head(sequence)
    tail = gaps[-1] if gaps else ""
    between = gaps[:-1]

    if between:
        separator = "\n" if any(gap.startswith("\n") for gap in between) else ""
    else:
        separator = "\n" if new_items and is_node(new_items[0]) else ""

    indent: str | None = None

    for text in [head, *between]:
        match = re.search(r"^( *)#", text, re.M)

        if match:
            indent = match.group(1)
            break

    leading: dict[Any, str] = {}

    if old_items:
        leading[ident(old_items[0])] = separator + head

    for index in range(1, len(old_items)):
        leading[ident(old_items[index])] = between[index - 1]

    def leading_of(item: Any) -> str:
        if ident(item) in leading:
            return leading[ident(item)]

        header = headers.get(id(item))

        if header and indent is not None:
            return f"{separator}{indent}# {header}\n"

        return separator

    sequence.ca.items.clear()
    list.clear(sequence)
    list.extend(sequence, new_items)

    for index, item in enumerate(new_items):
        entry = entries.get(ident(item))

        if entry is not None:
            sequence.ca.items[index] = entry

    if not new_items:
        set_head(parent, key, sequence, "")
        return

    set_head(parent, key, sequence, leading_of(new_items[0]))

    for index in range(1, len(new_items)):
        push_gap(
            last_scalar_slot(sequence, index - 1),
            leading_of(new_items[index]),
        )

    push_gap(last_scalar_slot(sequence, len(new_items) - 1), tail)


def set_map_value(
    mapping: CommentedMap,
    key: str,
    value: Any,
    before: tuple[str, ...] = ("gpgkeys",),
) -> None:
    """
    Adds a key before the first key from 'before' that is present; otherwise,
    appends it to the mapping and moves the separator after the previous last key.
    """
    if key in mapping:
        mapping[key] = value
        return

    keys = list(mapping.keys())

    for anchor in before:
        if anchor in mapping:
            mapping.insert(keys.index(anchor), key, value)
            return

    gap = pop_gap(last_scalar_slot(mapping, keys[-1])) if keys else ""
    mapping[key] = value
    push_gap(last_scalar_slot(mapping, key), gap)


# ---------------------------------------------------------------------------
# YAML synchronization
# ---------------------------------------------------------------------------

def update_archs(mapping: CommentedMap, detected: list[str]) -> bool:
    """
    Updates 'archs' while preserving the existing order; does not write if
    only the order differs.
    """
    detected = normalize_architectures(detected)

    if not detected:
        return False

    current = mapping.get("archs")

    if not isinstance(current, CommentedSeq):
        set_map_value(mapping, "archs", make_yaml_seq(detected))
        return True

    current_values = [str(arch) for arch in current]

    if set(current_values) == set(detected):
        return False

    kept = [arch for arch in current if str(arch) in detected]
    added = [arch for arch in detected if arch not in current_values]
    rebuild_sequence(mapping, "archs", kept + added)

    return True


def insert_position(
    items: list[Any],
    name: str,
    order_key: Callable[[str], tuple[int, ...] | None],
) -> int:
    """
    Insertion position that follows the existing items' order (ascending or
    descending). Without a sort key, inserts at the end of the list.
    """
    new_key = order_key(name)

    if new_key is None:
        return len(items)

    keyed: list[tuple[int, tuple[int, ...]]] = []

    for index, item in enumerate(items):
        item_name = get_name(item)
        key = order_key(item_name) if item_name is not None else None

        if key is not None:
            keyed.append((index, key))

    if not keyed:
        return len(items)

    # Templates list the most recent versions first
    descending = len(keyed) < 2 or keyed[0][1] >= keyed[-1][1]

    for index, key in keyed:
        if (descending and key < new_key) or (not descending and key > new_key):
            return index

    return keyed[-1][0] + 1


def copy_gpgkeys(
    sibling: CommentedMap | None,
    new_name: str,
) -> CommentedSeq | None:
    """
    Copies gpgkeys from a neighboring item; replaces the version name in
    links (e.g. RPM-GPG-KEY-AlmaLinux-10 -> RPM-GPG-KEY-AlmaLinux-11).
    """
    if sibling is None:
        return None

    gpgkeys = sibling.get("gpgkeys")

    if not isinstance(gpgkeys, list) or not gpgkeys:
        return None

    old_name = get_name(sibling)
    result = CommentedSeq()

    for entry in gpgkeys:
        if not isinstance(entry, dict):
            continue

        copied = CommentedMap()

        for key, value in entry.items():
            if key == "link" and old_name and value is not None:
                value = replace_word(str(value), old_name, new_name)

            copied[key] = value

        result.append(copied)

    return result or None


def sync_release_list(
    repository: CommentedMap,
    key: str,
    label: str,
    scan: ScanResult,
    options: SyncOptions,
    update_item: Callable[[CommentedMap, Any], list[str]],
    build_item: Callable[[str, Any, CommentedMap | None], CommentedMap],
    order_key: Callable[[str], tuple[int, ...] | None],
    kind_key: Callable[[str], str] = lambda _: "",
) -> list[str]:
    """
    Synchronizes a distributions/releasever list in place: existing order is
    preserved, and additions are inserted in the appropriate position.
    """
    changes: list[str] = []

    sequence = repository.get(key)
    items = list(sequence) if isinstance(sequence, CommentedSeq) else []
    by_name: dict[str, CommentedMap] = {}

    for item in items:
        name = get_name(item)

        if name is not None and isinstance(item, CommentedMap):
            by_name[name] = item

    for name, item in by_name.items():
        detected = scan.releases.get(name)

        if detected is not None:
            for change in update_item(item, detected):
                changes.append(f"{name}: {change}")

    to_add = (
        [name for name in scan.releases if name not in by_name]
        if options.add
        else []
    )
    to_remove = (
        [name for name in by_name if name in scan.missing]
        if options.remove
        else []
    )

    if to_remove and len(to_remove) == len(items) and not to_add:
        changes.append(
            f"ATTENTION suppression annulée, la liste {key} serait vide : "
            + ", ".join(to_remove)
        )
        to_remove = []

    final = list(items)
    headers: dict[int, str | None] = {}

    for name in to_remove:
        final.remove(by_name[name])
        changes.append(f"{label} supprimée : {name}")

    for name in to_add:
        position = insert_position(final, name, order_key)

        # Template: the nearest neighbor of the same type (e.g. '-updates')
        same_kind = [
            index
            for index, other in enumerate(final)
            if kind_key(get_name(other) or "") == kind_key(name)
        ]
        candidates = same_kind or list(range(len(final)))

        sibling: CommentedMap | None = None

        if candidates:
            nearest = min(candidates, key=lambda i: abs(i + 0.5 - position))
            sibling = final[nearest]

        item = build_item(name, scan.releases[name], sibling)
        final.insert(position, item)
        headers[id(item)] = item.get("description")
        changes.append(f"{label} ajoutée : {name}")

        if sibling is not None and "gpgkeys" in item:
            changes.append(
                f"{name}: ATTENTION gpgkeys copiées depuis "
                f"{get_name(sibling)}, à vérifier"
            )

    if [id(item) for item in final] == [id(item) for item in items]:
        return changes

    if isinstance(sequence, CommentedSeq):
        rebuild_sequence(repository, key, final, headers)
    else:
        set_map_value(repository, key, make_yaml_seq(final))

    return changes


def make_component(name: str, architectures: list[str]) -> CommentedMap:
    if architectures:
        return make_yaml_map(
            name=name,
            archs=make_yaml_seq(normalize_architectures(architectures)),
        )

    return make_yaml_map(name=name)


def sync_components(
    distribution: CommentedMap,
    release: DebRelease,
    options: SyncOptions,
) -> list[str]:
    changes: list[str] = []
    components = distribution.get("components")

    if not isinstance(components, CommentedSeq):
        if options.add and release.components:
            set_map_value(
                distribution,
                "components",
                make_yaml_seq(
                    [
                        make_component(name, archs)
                        for name, archs in release.components.items()
                    ]
                ),
            )
            changes.append("composants ajoutés")

        return changes

    items = list(components)
    by_name: dict[str, CommentedMap] = {}

    for item in items:
        name = get_name(item)

        if name is not None and isinstance(item, CommentedMap):
            by_name[name] = item

    ignored = [name for name in by_name if name in IGNORED_DEB_COMPONENTS]
    by_name = {
        name: item
        for name, item in by_name.items()
        if name not in IGNORED_DEB_COMPONENTS
    }

    for name, item in by_name.items():
        archs = release.components.get(name)

        if archs is not None and update_archs(item, archs):
            changes.append(f"architectures mises à jour pour {name}")

    to_remove = (
        [name for name in by_name if name not in release.components]
        if options.remove
        else []
    )
    to_add = (
        [name for name in release.components if name not in by_name]
        if options.add
        else []
    )

    if to_remove and len(to_remove) == len(by_name) and not to_add:
        to_remove = []

    # Ignored components are removed even with --no-remove
    if ignored and len(ignored) + len(to_remove) == len(items) and not to_add:
        changes.append(
            "ATTENTION suppression annulée, la liste components serait "
            "vide : " + ", ".join(ignored)
        )
        ignored = []

    to_remove += ignored
    final = [item for item in items if get_name(item) not in to_remove]

    for name in to_remove:
        changes.append(f"composant supprimé : {name}")

    for name in to_add:
        final.append(make_component(name, release.components[name]))
        changes.append(f"composant ajouté : {name}")

    if to_remove or to_add:
        rebuild_sequence(distribution, "components", final)

    return changes


def deb_description(
    release: DebRelease,
    sibling: CommentedMap | None,
    scan: ScanResult,
) -> str | None:
    """
    Infers a new distribution's description from a neighbor's description:
    'Debian 13' + Version 13.7 -> the new 'Version: 14.1' yields 'Debian 14'.
    """
    if sibling is not None and release.version:
        description = str(sibling.get("description") or "")
        sibling_release = scan.releases.get(get_name(sibling) or "")
        sibling_version = sibling_release.version if sibling_release else None
        match = re.search(r"\d+(?:\.\d+)*", description)
        new_version = re.match(r"\d+(?:\.\d+)*", release.version)

        if match and new_version and sibling_version and (
            sibling_version.startswith(match.group())
        ):
            count = match.group().count(".") + 1
            version = ".".join(new_version.group().split(".")[:count])

            return (
                description[:match.start()]
                + version
                + description[match.end():]
            )

    new_version = re.match(r"\d+(?:\.\d+)*", release.version or "")

    if release.origin and new_version:
        return f"{release.origin} {new_version.group()}"

    return None


def sync_debian_repository(
    repository: CommentedMap,
    scan: ScanResult,
    options: SyncOptions,
) -> list[str]:
    def order_key(name: str) -> tuple[int, ...] | None:
        release = scan.releases.get(name)
        return version_key(release.version) if release else None

    def update_item(item: CommentedMap, release: DebRelease) -> list[str]:
        return sync_components(item, release, options)

    def build_item(
        name: str,
        release: DebRelease,
        sibling: CommentedMap | None,
    ) -> CommentedMap:
        return make_yaml_map(
            name=name,
            description=deb_description(release, sibling, scan),
            components=make_yaml_seq(
                [
                    make_component(component, archs)
                    for component, archs in release.components.items()
                ]
            ),
            gpgkeys=copy_gpgkeys(sibling, name),
        )

    return sync_release_list(
        repository,
        "distributions",
        "distribution",
        scan,
        options,
        update_item,
        build_item,
        order_key,
        kind_key=lambda name: name.partition("-")[2],
    )


def sync_rpm_repository(
    repository: CommentedMap,
    scan: ScanResult,
    options: SyncOptions,
) -> list[str]:
    def update_item(item: CommentedMap, release: RpmRelease) -> list[str]:
        changes: list[str] = []
        name = yaml_name(get_name(item) or "")

        if isinstance(name, SingleQuotedScalarString) and not isinstance(
            item.get("name"), SingleQuotedScalarString
        ):
            item["name"] = name
            changes.append("version placée entre quotes simples")

        if release.complete and update_archs(item, release.architectures):
            changes.append("architectures mises à jour")

        return changes

    def build_item(
        name: str,
        release: RpmRelease,
        sibling: CommentedMap | None,
    ) -> CommentedMap:
        description: str | None = None
        sibling_name = get_name(sibling)

        if sibling is not None and sibling_name:
            sibling_description = str(sibling.get("description") or "")
            replaced = replace_word(sibling_description, sibling_name, name)

            if replaced != sibling_description:
                description = replaced

        archs = normalize_architectures(release.architectures)

        return make_yaml_map(
            name=yaml_name(name),
            description=description,
            archs=make_yaml_seq(archs) if archs else None,
            gpgkeys=copy_gpgkeys(sibling, name),
        )

    return sync_release_list(
        repository,
        "releasever",
        "release",
        scan,
        options,
        update_item,
        build_item,
        version_key,
    )


# ---------------------------------------------------------------------------
# File processing
# ---------------------------------------------------------------------------

def get_yaml() -> YAML:
    yaml = YAML(typ="rt")
    yaml.preserve_quotes = True
    yaml.width = 120
    yaml.indent(mapping=2, sequence=4, offset=2)
    yaml.default_flow_style = False
    return yaml


def dump_yaml_to_string(yaml: YAML, data: Any) -> str:
    buffer = StringIO()
    yaml.dump(data, buffer)
    return buffer.getvalue()


def process_file(
    yaml: YAML,
    client: HttpClient,
    path: Path,
    template_type: str | None,
    options: SyncOptions,
    rpm_architectures: list[str],
    dry_run: bool,
    show_diff: bool,
) -> tuple[bool, bool, int]:
    """
    Returns (processed, modified, number of changes).
    """
    print_message(f"\n[{path}]")

    try:
        text = path.read_text(encoding="utf-8")
        data = yaml.load(text)
    except Exception as exc:
        print_message(f"  ERREUR YAML : {exc}")
        return False, False, 0

    if not isinstance(data, CommentedMap):
        print_message("  IGNORE : document YAML invalide")
        return False, False, 0

    file_type = str(data.get("type", "")).lower()

    if template_type is not None and file_type != template_type:
        print_message(
            f"  IGNORE : type racine '{file_type}' "
            f"différent de '{template_type}'"
        )
        return False, False, 0

    repositories = data.get("repositories", []) or []

    if not isinstance(repositories, list):
        print_message("  ERREUR : repositories n'est pas une liste")
        return False, False, 0

    # ruamel.yaml does not preserve the initial '---' on its own
    yaml.explicit_start = text.lstrip().startswith("---")

    original = dump_yaml_to_string(yaml, data)
    total_changes = 0
    successful_repositories = 0

    for repository in repositories:
        if not isinstance(repository, CommentedMap):
            continue

        repository_name = str(repository.get("name", "<sans nom>"))
        repository_type = str(repository.get("type", file_type)).lower()

        print_message(f"  Repository: {repository_name}")

        if repository_type == "deb":
            scan = scan_debian_repository(
                client,
                repository,
                options.exclude,
            )
        elif repository_type == "rpm":
            scan = scan_rpm_repository(
                client,
                repository,
                rpm_architectures,
                options.exclude,
            )
        else:
            print_message(f"    IGNORE : type de dépôt inconnu '{repository_type}'")
            continue

        if not scan.success:
            print_message(
                f"    NON MODIFIÉ : découverte impossible "
                f"({scan.error})"
            )
            continue

        successful_repositories += 1

        if repository_type == "deb":
            changes = sync_debian_repository(repository, scan, options)
        else:
            changes = sync_rpm_repository(repository, scan, options)

        for change in changes:
            print_message(f"    {change}")

        total_changes += len(changes)

    if successful_repositories == 0:
        print_message("  Aucun dépôt n'a pu être analysé")
        return False, False, 0

    updated = dump_yaml_to_string(yaml, data)
    modified = original != updated

    if modified and show_diff:
        sys.stdout.writelines(
            colorize(line, sys.stdout)
            for line in difflib.unified_diff(
                text.splitlines(keepends=True),
                updated.splitlines(keepends=True),
                fromfile=f"{path} (actuel)",
                tofile=f"{path} (nouveau)",
            )
        )

    if modified and not dry_run:
        try:
            path.write_text(updated, encoding="utf-8")
            print_message("  Fichier modifié")
        except OSError as exc:
            print_message(f"  ERREUR écriture : {exc}")
            return True, False, total_changes
    elif modified:
        print_message("  Modification détectée, dry-run : aucune écriture")
    else:
        print_message("  Aucun changement")

    return True, modified, total_changes


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

def find_template_files(
    templates_root: Path,
    template_type: str,
) -> list[Path]:
    directory = templates_root / template_type

    if not directory.exists():
        return []

    files: list[Path] = []

    for path in sorted(directory.iterdir()):
        if not path.is_file():
            continue

        if path.name in IGNORED_TEMPLATE_NAMES:
            continue

        if path.suffix.lower() not in {".yml", ".yaml"}:
            continue

        files.append(path)

    return files


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Découvre et synchronise les distributions Debian "
            "et releases RPM dans des templates YAML."
        )
    )

    parser.add_argument(
        "--templates-root",
        type=Path,
        default=DEFAULT_TEMPLATES_ROOT,
        help=(
            "Racine des templates, contenant les répertoires "
            f"deb/ et rpm/, défaut : {DEFAULT_TEMPLATES_ROOT}"
        ),
    )

    parser.add_argument(
        "--type",
        choices=["all", "deb", "rpm"],
        default="all",
        help="Type de templates à traiter, défaut : all",
    )

    parser.add_argument(
        "--file",
        type=Path,
        action="append",
        dest="files",
        help="Traite uniquement ce fichier YAML. Peut être répétée.",
    )

    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Analyse les changements sans modifier les fichiers",
    )

    parser.add_argument(
        "--diff",
        action="store_true",
        help="Affiche le diff des fichiers modifiés",
    )

    parser.add_argument(
        "--no-add",
        action="store_true",
        help="N'ajoute aucune distribution, release ou composant",
    )

    parser.add_argument(
        "--no-remove",
        action="store_true",
        help="Ne supprime aucune distribution, release ou composant",
    )

    parser.add_argument(
        "--exclude",
        action="append",
        default=[],
        metavar="REGEX",
        help=(
            "N'ajoute jamais les distributions/releases correspondant "
            "à cette regex. Peut être répétée."
        ),
    )

    parser.add_argument(
        "--timeout",
        type=int,
        default=DEFAULT_TIMEOUT,
        help=f"Timeout HTTP en secondes, défaut : {DEFAULT_TIMEOUT}",
    )

    parser.add_argument(
        "--retries",
        type=int,
        default=2,
        help="Nombre de tentatives HTTP supplémentaires",
    )

    parser.add_argument(
        "--rpm-arch",
        action="append",
        dest="rpm_architectures",
        help=(
            "Architecture RPM à tester. Peut être répétée. "
            "Par défaut : x86_64, aarch64, ppc64le, s390x..."
        ),
    )

    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Affiche les requêtes HTTP détaillées",
    )

    return parser.parse_args()


def main() -> int:
    args = parse_args()

    print_message(
        "Composants Debian ignorés (et supprimés des templates) : "
        + ", ".join(sorted(IGNORED_DEB_COMPONENTS))
    )

    exclude: list[re.Pattern[str]] = []

    for pattern in args.exclude:
        try:
            exclude.append(re.compile(pattern))
        except re.error as exc:
            print_message(
                f"Erreur : regex invalide '{pattern}' : {exc}",
                file=sys.stderr,
            )
            return 2

    options = SyncOptions(
        add=not args.no_add,
        remove=not args.no_remove,
        exclude=exclude,
    )

    files_by_type: list[tuple[Path, str | None]] = []

    if args.files:
        for file in args.files:
            file_path = file.expanduser().resolve()

            if not file_path.is_file():
                print_message(
                    f"Erreur : fichier introuvable : {file_path}",
                    file=sys.stderr,
                )
                return 2

            # The type is read from the file's 'type' field
            files_by_type.append((file_path, None))
    else:
        templates_root = args.templates_root.expanduser().resolve()

        if not templates_root.is_dir():
            print_message(
                f"Erreur : répertoire introuvable : {templates_root}",
                file=sys.stderr,
            )
            return 2

        types = (
            ["deb", "rpm"]
            if args.type == "all"
            else [args.type]
        )

        for template_type in types:
            for path in find_template_files(
                templates_root,
                template_type,
            ):
                files_by_type.append((path, template_type))

    if not files_by_type:
        print_message("Aucun template trouvé.")
        return 0

    rpm_architectures = (
        args.rpm_architectures
        if args.rpm_architectures
        else DEFAULT_RPM_ARCHITECTURES
    )

    client = HttpClient(
        timeout=args.timeout,
        retries=args.retries,
        verbose=args.verbose,
    )

    yaml = get_yaml()

    modified_files = 0
    total_changes = 0
    processed_files = 0

    for path, template_type in files_by_type:
        processed, modified, changes = process_file(
            yaml=yaml,
            client=client,
            path=path,
            template_type=template_type,
            options=options,
            rpm_architectures=rpm_architectures,
            dry_run=args.dry_run,
            show_diff=args.diff,
        )

        if processed:
            processed_files += 1

        if modified:
            modified_files += 1

        total_changes += changes

    print_message("\nRésumé")
    print_message("======")
    print_message(f"Templates analysés : {processed_files}")
    print_message(f"Fichiers modifiés  : {modified_files}")
    print_message(f"Changements        : {total_changes}")

    if args.dry_run:
        print_message("Mode               : dry-run")

    if args.no_add:
        print_message("Ajout              : désactivé")

    if args.no_remove:
        print_message("Suppression        : désactivée")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
