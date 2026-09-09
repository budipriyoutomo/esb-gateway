"""Regenerate the ESB endpoint catalogs from the vendor apiDoc source.

Usage:
    curl -sSL https://developers.esb.co.id/esb-core/api_data.json -o esb-core.api_data.json
    curl -sSL https://developers.esb.co.id/esb-core/api_project.json -o esb-core.api_project.json
    curl -sSL https://developers.esb.co.id/esb-oms/api_data.json  -o esb-oms.api_data.json
    curl -sSL https://developers.esb.co.id/esb-oms/api_project.json -o esb-oms.api_project.json
    python gen.py <output-dir>

The vendor documentation is an apiDoc site; api_data.json is its machine-readable
source. Never hand-edit the generated catalogs -- regenerate them instead.
"""
import collections
import datetime
import json
import re
import sys

SOURCES = {
    "esb-core": {"title": "ESB Core API", "docs": "https://developers.esb.co.id/esb-core/"},
    "esb-oms": {"title": "ESB OMS API", "docs": "https://developers.esb.co.id/esb-oms/"},
}
ENTITIES = [("&quot;", '"'), ("&lt;", "<"), ("&gt;", ">"), ("&amp;", "&"),
            ("&#39;", "'"), ("&nbsp;", " ")]


def plain(html):
    text = re.sub(r"<[^>]+>", "", html or "")
    for entity, char in ENTITIES:
        text = text.replace(entity, char)
    return re.sub(r"\s+", " ", text).strip()


def base_urls(entry):
    """Return {environment label: url} declared in an endpoint description."""
    found = re.findall(r"Base URL ([A-Za-z ]+?)\s*:\s*(https?://[^\s<]+)",
                       plain(entry.get("description")))
    return {label.strip(): url.rstrip("/") for label, url in found}


def render(slug, meta, data, project, generated_on):
    groups = collections.defaultdict(list)
    for entry in data:
        groups[entry.get("group") or "Ungrouped"].append(entry)

    out = [
        f"# {meta['title']} — Endpoint Catalog (v{project.get('version')})",
        "",
        f"> Generated from the official apiDoc source (`{meta['docs']}api_data.json`) on "
        f"{generated_on} by `docs/esb/generate-catalog.py`. This is a mechanical "
        "transcription of the vendor documentation, not a design document. Do not "
        "hand-edit — regenerate it, and record contract changes in "
        "`docs/05-api-integration.md`.",
        "",
        f"Official documentation: {meta['docs']}",
        "",
        f"**Total operations:** {len(data)} across {len(groups)} groups.",
        "",
        "Paths below are relative to the base URL declared for their group. Base URLs are "
        "not uniform across groups — see `docs/05-api-integration.md`.",
        "",
        "## Groups",
        "",
    ]
    for group in sorted(groups):
        anchor = group.lower().replace("_", "-")
        out.append(f"- [{group.replace('_', ' ')}](#{anchor}) — {len(groups[group])} operations")
    out.append("")

    for group in sorted(groups):
        out.append(f"## {group.replace('_', ' ')}")
        out.append("")
        declared = collections.Counter()
        for entry in groups[group]:
            production = base_urls(entry).get("Production")
            if production:
                declared[production] += 1
        if declared:
            listed = ", ".join(f"`{url}`" for url, _ in declared.most_common())
            out.append(f"Production base URL: {listed}")
            out.append("")
        out.append("| Method | Path | Operation | Ref |")
        out.append("| --- | --- | --- | --- |")
        for entry in sorted(groups[group], key=lambda e: (e.get("url") or "", e.get("name") or "")):
            method = (entry.get("type") or "").upper() or "—"
            path = (entry.get("url") or "").replace("{{base_url}}", "").strip() or "—"
            title = (entry.get("title") or entry.get("name") or "").replace("|", "\|")
            ref = f"{meta['docs']}#api-{group}-{entry.get('name')}"
            out.append(f"| `{method}` | `{path}` | {title} | [docs]({ref}) |")
        out.append("")
    return "\n".join(out) + "\n"


def main():
    target = sys.argv[1] if len(sys.argv) > 1 else "."
    generated_on = datetime.date.today().isoformat()
    for slug, meta in SOURCES.items():
        with open(f"{slug}.api_data.json", encoding="utf-8") as handle:
            data = json.load(handle)
        with open(f"{slug}.api_project.json", encoding="utf-8") as handle:
            project = json.load(handle)
        path = f"{target}/{slug}-endpoints.md"
        with open(path, "w", encoding="utf-8") as handle:
            handle.write(render(slug, meta, data, project, generated_on))
        print(f"{path}: {len(data)} operations")


if __name__ == "__main__":
    main()
