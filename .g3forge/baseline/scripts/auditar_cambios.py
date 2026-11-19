#!/usr/bin/env python3
"""Auditoría LOCAL de rutas modificadas (staged/unstaged/untracked). No bloquea herramientas externas."""

import argparse
import json
import subprocess
import sys
import tomllib
from pathlib import Path, PurePosixPath

APP_AREA = {"gestion": "personalizacion", "autogestion": "pers", "preinscripcion": "pers"}


def paths_from_porcelain(raw):
    """Consume git status --porcelain -z, incluyendo ambos lados de renames/copies."""
    items = raw.decode("utf-8", "surrogateescape").split("\x00")
    found = []
    i = 0
    while i < len(items) and items[i]:
        record = items[i]
        if len(record) < 4:
            raise ValueError("Estado git no reconocido")
        xy = record[:2]
        found.append(record[3:])
        i += 1
        if "R" in xy or "C" in xy:
            if i >= len(items) or not items[i]:
                raise ValueError("Rename/copy incompleto en git status")
            found.append(items[i])
            i += 1
    return list(dict.fromkeys(found))


def app_roots(repo):
    config = Path(repo) / "config/cliente.toml"
    if not config.is_file():
        raise ValueError("Falta config/cliente.toml para auditar rutas")
    with config.open("rb") as stream:
        data = tomllib.load(stream)
    roots = {}
    for app in APP_AREA:
        value = data.get("aplicaciones", {}).get(app, {}).get("ruta", app)
        if not isinstance(value, str) or not value or value.startswith("/") or any(x in ("", ".", "..") for x in value.replace("\\", "/").split("/")):
            raise ValueError(f"Ruta inválida para {app}")
        roots[app] = PurePosixPath(value.replace("\\", "/"))
    return roots


def app_part(path, root):
    p = PurePosixPath(path)
    return p.relative_to(root) if p == root or root in p.parents else None


def protected_reason(path, roots=None):
    roots = roots or {app: PurePosixPath(app) for app in APP_AREA}
    parts = PurePosixPath(path.replace("\\", "/")).parts
    lowered = [p.casefold() for p in parts]
    if any("autoload" in x for x in lowered):
        return "autoload"
    if any(x in ("metadatos", "metadata_toba", "toba_editor") for x in lowered):
        return "metadatos Toba"
    for app, root in roots.items():
        within = app_part(path, root)
        if within is None or not within.parts:
            continue
        section = within.parts[0].casefold()
        if app == "gestion" and section == "php":
            return "legacy Gestión"
        if app in ("autogestion", "preinscripcion") and section == "siu":
            return "legacy SIU"
        if app == "autogestion" and section == "pers_gestion":
            return "derivado Gestión"
    return None


def allowed(path, mode, roots=None):
    roots = roots or {app: PurePosixPath(app) for app in APP_AREA}
    norm = path.replace("\\", "/")
    parts = PurePosixPath(norm).parts
    if not parts or norm.startswith("/") or ".." in parts:
        return False
    if protected_reason(norm, roots):
        return False
    if parts[0] == "docs" and len(parts) >= 2:
        return True
    if mode == "inicializacion":
        if norm in ("config/cliente.toml", "README.md", "AGENTS.md", ".gitignore"):
            return True
        return any(app_part(norm, root) == PurePosixPath("AGENTS.md") for root in roots.values())
    return any(
        (part := app_part(norm, root)) is not None and
        len(part.parts) >= 2 and part.parts[0] == APP_AREA[app]
        for app, root in roots.items()
    )


def allowed_framework_update(path, manifest):
    """Whitelist narrow de cambios DEL ACTUALIZADOR, no de desarrollos.

    Se usa junto con `g3forge.py verificar` y la revisión Git. No certifica
    que un bloque administrado esté intacto solo por pertenecer a esta lista.
    """
    norm = path.replace("\\", "/")
    if norm in (".gitattributes", ".gitignore", ".g3forge/manifest.json"):
        return True
    managed = manifest.get("managed", {})
    if norm in managed:
        return True
    if norm.startswith(".g3forge/baseline/"):
        return norm.removeprefix(".g3forge/baseline/") in managed
    return False


def audit(root, mode):
    roots = app_roots(root)
    status = subprocess.run(["git", "-C", root, "status", "--porcelain", "-z"],
                            capture_output=True, check=True).stdout
    paths = paths_from_porcelain(status)
    if mode == "actualizacion-framework":
        manifest_path = Path(root) / ".g3forge/manifest.json"
        if not manifest_path.is_file():
            raise ValueError("Falta manifiesto para auditar actualización de framework")
        manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
        if manifest.get("framework") != "G3Forge" or not isinstance(manifest.get("managed"), dict):
            raise ValueError("Manifiesto de actualización incompatible")
        invalid = [(p, protected_reason(p, roots) or "fuera del alcance de actualización")
                   for p in paths if protected_reason(p, roots) or not allowed_framework_update(p, manifest)]
    else:
        invalid = [(p, protected_reason(p, roots) or "fuera del alcance permitido") for p in paths if not allowed(p, mode, roots)]
    return paths, invalid


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", default=".")
    parser.add_argument("--modo", choices=("trabajo", "inicializacion", "actualizacion-framework"), default="trabajo")
    args = parser.parse_args()
    try:
        changed, invalid = audit(args.repo, args.modo)
    except (OSError, ValueError, json.JSONDecodeError, subprocess.CalledProcessError) as err:
        print(f"ERROR: {err}", file=sys.stderr)
        return 2
    for path, reason in invalid:
        print(f"BLOQUEADO: {path} ({reason})", file=sys.stderr)
    if invalid:
        print(f"Auditoría: {len(invalid)} ruta(s) fuera de política", file=sys.stderr)
        return 2
    print(f"Auditoría de rutas OK ({len(changed)}). Revisar también git diff y cambios ya comprometidos.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
