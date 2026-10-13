"""Regenerate the ESB endpoint catalogs from the vendor's machine-readable sources.

The two products are documented in different formats:

- ESB Core is a Stoplight Elements site backed by an OpenAPI 3.0 document
  (`openapi.json`). Its apiDoc `api_data.json` has returned 404 since
  2026-09-25.
- ESB OMS is still an apiDoc site (`api_data.json` + `api_project.json`).

Usage:
    curl -sSL https://developers.esb.co.id/esb-core/openapi.json     -o esb-core.openapi.json
    curl -sSL https://developers.esb.co.id/esb-oms/api_data.json     -o esb-oms.api_data.json
    curl -sSL https://developers.esb.co.id/esb-oms/api_project.json  -o esb-oms.api_project.json
    python generate-catalog.py <output-dir>

Run it from the directory holding the downloaded files. Never hand-edit the
generated catalogs -- regenerate them instead. `php artisan esb:import-catalog`
parses the tables this script writes, so keep the row shape stable:

    | `METHOD` | `path` | Operation | `version` | [docs](url) |
"""
import collections
import datetime
import json
import re
import sys

SOURCES = {
    "esb-core": {
        "title": "ESB Core API",
        "docs": "https://developers.esb.co.id/esb-core/",
        "format": "openapi",
    },
    "esb-oms": {
        "title": "ESB OMS API",
        "docs": "https://developers.esb.co.id/esb-oms/",
        "format": "apidoc",
    },
}
HTTP_METHODS = ("get", "post", "put", "patch", "delete", "head", "options")
ENTITIES = [("&quot;", '"'), ("&lt;", "<"), ("&gt;", ">"), ("&amp;", "&"),
            ("&#39;", "'"), ("&nbsp;", " ")]
NOTE = ("This is a mechanical transcription of the vendor documentation, not a design "
        "document. Do not hand-edit — regenerate it, and record contract changes in "
        "`docs/05-api-integration.md`.")


def cell(text):
    """Text safe for a markdown table cell."""
    return re.sub(r"\s+", " ", (text or "")).strip().replace("|", "\\|")


def anchor(group):
    return re.sub(r"[\s_]+", "-", group.strip().lower())


def groups_index(groups):
    lines = ["## Groups", ""]
    for group in sorted(groups):
        lines.append(f"- [{group}](#{anchor(group)}) — {len(groups[group])} operations")
    lines.append("")
    return lines


# --------------------------------------------------------------------- OpenAPI

def stoplight_slug(path):
    """The path segment Stoplight Elements puts in an operation's URL.

    Transcribed from the vendor's own stoplight-elements.js (function `jC`):
    "/", "{", "}" and whitespace become "-", then the *first* run of dashes
    collapses to one, then one leading and one trailing dash are removed.
    Used only when an operation has no operationId, which is every Core
    operation as of 2026-09-30.
    """
    slug = re.sub(r"[/{}\s]", "-", path)
    slug = re.sub(r"-{2,}", "-", slug, count=1)
    slug = re.sub(r"^-", "", slug, count=1)
    return re.sub(r"-$", "", slug, count=1)


def operation_url(docs, path, method, operation):
    if operation.get("operationId"):
        return f"{docs}#/operations/{operation['operationId']}"
    return f"{docs}#/paths/{stoplight_slug(path)}/{method}"


def production_servers(servers):
    return [s["url"].rstrip("/") for s in servers or []
            if (s.get("description") or "").strip().lower() == "production" and s.get("url")]


def render_openapi(meta, spec, generated_on):
    version = spec.get("info", {}).get("version") or "—"
    document_servers = spec.get("servers") or []

    groups = collections.defaultdict(list)
    for path, item in (spec.get("paths") or {}).items():
        for method, operation in item.items():
            if method not in HTTP_METHODS or not isinstance(operation, dict):
                continue
            group = (operation.get("tags") or ["Ungrouped"])[0]
            servers = operation.get("servers") or item.get("servers") or document_servers
            groups[group].append((path, method, operation, servers))

    total = sum(len(rows) for rows in groups.values())
    out = [
        f"# {meta['title']} — Endpoint Catalog (v{version})",
        "",
        f"> Generated from the official OpenAPI description (`{meta['docs']}openapi.json`) "
        f"on {generated_on} by `docs/esb/generate-catalog.py`. {NOTE}",
        "",
        f"Official documentation: {meta['docs']}",
        "",
        f"**Total operations:** {total} across {len(groups)} groups.",
        "",
        "Base URLs declared by the document:",
        "",
    ]
    for server in document_servers:
        out.append(f"- {server.get('description') or 'Unnamed'}: `{server.get('url')}`")
    out += [
        "",
        "Paths below are relative to those base URLs unless a group says otherwise. "
        "OpenAPI has no per-operation version, so the Version column carries the "
        f"document's version ({version}).",
        "",
    ]
    out += groups_index(groups)

    for group in sorted(groups):
        out += [f"## {group}", ""]
        declared = collections.Counter()
        for _, _, _, servers in groups[group]:
            for url in production_servers(servers):
                declared[url] += 1
        if declared:
            listed = ", ".join(f"`{url}`" for url, _ in declared.most_common())
            out += [f"Production base URL: {listed}", ""]
        out += ["| Method | Path | Operation | Version | Ref |", "| --- | --- | --- | --- | --- |"]
        for path, method, operation, _ in sorted(groups[group], key=lambda r: (r[0], r[1])):
            title = cell(operation.get("summary") or f"{method.upper()} {path}")
            if operation.get("deprecated"):
                title += " (deprecated)"
            out.append(
                f"| `{method.upper()}` | `{cell(path)}` | {title} | `{version}` | "
                f"[docs]({operation_url(meta['docs'], path, method, operation)}) |"
            )
        out.append("")
    return "\n".join(out) + "\n", total


# ---------------------------------------------------------------------- apiDoc

def plain(html):
    text = re.sub(r"<[^>]+>", "", html or "")
    for entity, char in ENTITIES:
        text = text.replace(entity, char)
    return re.sub(r"\s+", " ", text).strip()


def apidoc_path_cell(entry):
    """Render an entry's path for a table cell.

    Four Core "Flow" entries carried a literal "|" as their url in the old
    apiDoc source -- they are documentation pages, not operations. Anything
    that is not a real path renders as an em dash.
    """
    path = (entry.get("url") or "").replace("{{base_url}}", "").strip()

    if not path.startswith("/"):
        return "—"

    return path.replace("|", "\\|")


def apidoc_base_urls(entry):
    """Return {environment label: url} declared in an endpoint description."""
    found = re.findall(r"Base URL ([A-Za-z ]+?)\s*:\s*(https?://[^\s<]+)",
                       plain(entry.get("description")))
    return {label.strip(): url.rstrip("/") for label, url in found}


def render_apidoc(meta, data, project, generated_on):
    groups = collections.defaultdict(list)
    for entry in data:
        groups[(entry.get("group") or "Ungrouped").replace("_", " ")].append(entry)

    out = [
        f"# {meta['title']} — Endpoint Catalog (v{project.get('version')})",
        "",
        f"> Generated from the official apiDoc source (`{meta['docs']}api_data.json`) on "
        f"{generated_on} by `docs/esb/generate-catalog.py`. {NOTE}",
        "",
        f"Official documentation: {meta['docs']}",
        "",
        f"**Total operations:** {len(data)} across {len(groups)} groups.",
        "",
        "Paths below are relative to the base URL declared for their group. Base URLs are "
        "not uniform across groups — see `docs/05-api-integration.md`.",
        "",
    ]
    out += groups_index(groups)

    for group in sorted(groups):
        out += [f"## {group}", ""]
        declared = collections.Counter()
        for entry in groups[group]:
            production = apidoc_base_urls(entry).get("Production")
            if production:
                declared[production] += 1
        if declared:
            listed = ", ".join(f"`{url}`" for url, _ in declared.most_common())
            out += [f"Production base URL: {listed}", ""]
        # Version is part of an apiDoc operation's identity, not decoration:
        # the vendor has shipped two operations that differ only by version,
        # method, and host.
        out += ["| Method | Path | Operation | Version | Ref |", "| --- | --- | --- | --- | --- |"]
        for entry in sorted(groups[group], key=lambda e: (e.get("url") or "", e.get("name") or "")):
            method = (entry.get("type") or "").upper() or "—"
            title = cell(entry.get("title") or entry.get("name"))
            version = entry.get("version") or "—"
            ref = f"{meta['docs']}#api-{entry.get('group')}-{entry.get('name')}"
            out.append(f"| `{method}` | `{apidoc_path_cell(entry)}` | {title} | `{version}` | [docs]({ref}) |")
        out.append("")
    return "\n".join(out) + "\n", len(data)


def main():
    target = sys.argv[1] if len(sys.argv) > 1 else "."
    generated_on = datetime.date.today().isoformat()
    for slug, meta in SOURCES.items():
        if meta["format"] == "openapi":
            with open(f"{slug}.openapi.json", encoding="utf-8") as handle:
                text, count = render_openapi(meta, json.load(handle), generated_on)
        else:
            with open(f"{slug}.api_data.json", encoding="utf-8") as handle:
                data = json.load(handle)
            with open(f"{slug}.api_project.json", encoding="utf-8") as handle:
                project = json.load(handle)
            text, count = render_apidoc(meta, data, project, generated_on)
        path = f"{target}/{slug}-endpoints.md"
        with open(path, "w", encoding="utf-8", newline="\n") as handle:
            handle.write(text)
        print(f"{path}: {count} operations")


if __name__ == "__main__":
    main()
