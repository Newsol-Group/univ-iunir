#!/usr/bin/env python3
"""G3Forge: instalación, adopción, auditoría y actualización local SIN RED.

Python >= 3.11; Git opcional para consulta pero requerido en repositorio cliente.
No ejecuta SQL, no conecta a bases, no toca Toba ni autoloads ni despliega.
Los AGENTS preexistentes conservan su texto fuera de un bloque administrado.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import subprocess
import sys
import tomllib
from datetime import datetime, timezone
from pathlib import Path

NAME = "G3Forge"
SCHEMA = 1
BEGIN = "<!-- G3FORGE:BEGIN:{} -->"
END = "<!-- G3FORGE:END:{} -->"
APPS = ("gestion", "autogestion", "preinscripcion")
SOURCE_AGENT_PATHS = ("AGENTS.md", "gestion/AGENTS.md", "autogestion/AGENTS.md", "preinscripcion/AGENTS.md")
LOCAL_TEMPLATES = ("config/cliente.toml", "docs/PROYECTO.md", "docs/ESTADO.md", "docs/personalizaciones/INDICE.md")
FILES = ("inicializado.md", "config/README.md", ".codex/config.toml",
         "scripts/auditar_cambios.py", "scripts/validar_framework.py", "scripts/diagnostico_multiagente.py", "scripts/trazabilidad.py", "scripts/workspace.py", "scripts/consola.py", "scripts/terminal.py", "scripts/G3Forge.ps1")
GLOBS = (".codex/agents/*.toml", ".agents/skills/**/SKILL.md",
         ".agents/skills/**/scripts/*.py", "docs/arquitectura/*.md", "docs/evaluacion/*.md", "docs/instalacion/*.md")
ATTR_BEGIN = "# G3FORGE:ATTRIBUTES:BEGIN"
ATTR_END = "# G3FORGE:ATTRIBUTES:END"
IGNORE_LINES = (".pases-locales/", ".g3forge/pending/", ".g3forge/reports/", ".g3forge/ejecuciones/",
                "__pycache__/", "*.py[cod]")
RUTA_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._-]{0,79}$")
# Sólo rutas históricas del framework. Nunca incluye datos ni paquetes del cliente.
RETIRED_FILES = frozenset({
    "scripts/personalizaciones.py",
    ".agents/skills/pase-controlado/scripts/paquete.py",
    ".agents/skills/pase-controlado/SKILL.md",
    ".agents/skills/catalogar-personalizacion/SKILL.md",
    ".agents/skills/preparar-release-selectiva/SKILL.md",
    "docs/arquitectura/CATALOGO.md",
})


def retired_content(rel: str) -> bytes:
    """Aviso inerte para accesos antiguos; nunca invoca otro motor ni escribe datos."""
    if rel not in RETIRED_FILES:
        raise G3Error(f"Recurso no autorizado para retirada: {rel}")
    message = ("La administracion de entregas fue retirada de G3Forge AI. "
               "Corresponde a G3Forge Deploy; no se invoca automaticamente. "
               "Los catalogos, documentos y paquetes existentes se conservan.")
    if rel.endswith(".py"):
        return ("#!/usr/bin/env python3\n"
                '"""Acceso historico retirado. Sin operaciones sobre el workspace."""\n'
                "import sys\n\n"
                "def main():\n"
                f"    print({message!r}, file=sys.stderr)\n"
                "    return 2\n\n"
                "if __name__ == '__main__':\n"
                "    raise SystemExit(main())\n").encode("utf-8")
    frontmatter = ""
    if rel.endswith("/SKILL.md"):
        name = rel.split("/")[-2]
        frontmatter = (f"---\nname: {name}\n"
                       "description: Aviso de funcionalidad retirada; no ejecutar operaciones de entregas.\n---\n\n")
    return (frontmatter + "# Recurso retirado de G3Forge AI\n\n" + message +
            "\n\nEste aviso no autoriza crear catalogos, versiones o paquetes, "
            "ni ejecutar comandos de Deploy.\n").encode("utf-8")


class G3Error(Exception):
    """Error de uso o de seguridad. Los cambios ajenos se preservan."""


def relative(value: str) -> str:
    if not isinstance(value, str) or not value or value.startswith(("/", "\\")):
        raise G3Error(f"Ruta relativa inválida: {value!r}")
    value = value.replace("\\", "/")
    parts = value.split("/")
    if any(p in ("", ".", "..") for p in parts) or ":" in parts[0] or any(ord(c) < 32 for c in value):
        raise G3Error(f"Ruta relativa inválida: {value!r}")
    return value


def check_portable_path(value: str) -> str:
    """Reject names invalid on Windows, even when working on Linux."""
    rel = relative(value)
    for part in rel.split("/"):
        stem = part.split(".")[0].upper()
        if (part.endswith((" ", ".")) or any(c in part for c in '<>"|?*') or stem in {
            "CON", "PRN", "AUX", "NUL", *(f"COM{i}" for i in range(1, 10)),
            *(f"LPT{i}" for i in range(1, 10))
        }):
            raise G3Error(f"Ruta incompatible con Windows: {value!r}")
    return rel


def fold_lf(data: bytes) -> bytes:
    """Only accept EOL equivalence for valid UTF-8 text without NUL bytes."""
    if b"\0" in data:
        return data
    try:
        data.decode("utf-8")
    except UnicodeError:
        return data
    return data.replace(b"\r\n", b"\n")


def same_text_bytes(a: bytes, b: bytes) -> bool:
    return a == b or fold_lf(a) == fold_lf(b)


def trusted_baseline(data: bytes, expected_sha: str) -> tuple[bytes, bool] | None:
    """Return original bytes or strictly verified LF-normalized bytes, never ignore other edits."""
    if sha(data) == expected_sha:
        return data, False
    norm = fold_lf(data)
    if norm != data and sha(norm) == expected_sha:
        return norm, True
    return None


def safe_target(repo: Path, rel: str) -> Path:
    rel = check_portable_path(rel)
    p = repo
    for seg in rel.split("/"):
        p = p / seg
        if p.is_symlink():
            raise G3Error(f"Enlace simbólico no permitido: {rel}")
    if not p.resolve().is_relative_to(repo):
        raise G3Error(f"Ruta fuera de repositorio: {rel}")
    return p


def sha(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def timestamp() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def read_json(path: Path):
    return json.loads(path.read_text(encoding="utf-8"))


def write_bytes(path: Path, data: bytes, root: Path, dry: bool):
    if dry:
        return
    rel = path.relative_to(root).as_posix()
    safe_target(root, rel)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(data)


def run_git(repo: Path, *args: str) -> str:
    result = subprocess.run(["git", "-C", str(repo), *args], capture_output=True, text=True,
                            check=False, encoding="utf-8", errors="replace")
    if result.returncode:
        raise G3Error(f"Git no disponible o repositorio inválido: {result.stderr.strip()}")
    return result.stdout.strip()


def check_repo(repo: Path, clean: bool):
    if not repo.is_dir():
        raise G3Error(f"La carpeta del repositorio no existe: {repo}")
    actual = Path(run_git(repo, "rev-parse", "--show-toplevel")).resolve()
    if actual != repo:
        raise G3Error(f"--repo debe ser la raíz Git, no un subdirectorio: {actual}")
    if clean and run_git(repo, "status", "--porcelain", "--untracked-files=all"):
        raise G3Error("El repositorio tiene cambios locales. Confirmá o resguardá esos cambios antes de instalar/actualizar.")


def source_dir(raw: str | None) -> Path:
    src = Path(raw).expanduser().resolve() if raw else Path(__file__).resolve().parents[1]
    if not (src / "VERSION").is_file() or not (src / "tools/g3forge.py").is_file():
        raise G3Error("Indicá --fuente con la carpeta extraída de la distribución G3Forge (contiene VERSION y tools/g3forge.py)")
    return src


def check_separated(src: Path, repo: Path):
    """El repositorio cliente y la distribucion nunca pueden compartir arbol."""
    if src == repo or src.is_relative_to(repo) or repo.is_relative_to(src):
        raise G3Error('La distribucion G3Forge y el repositorio cliente deben estar en carpetas separadas')


def version(src: Path) -> str:
    v = (src / "VERSION").read_text(encoding="utf-8").strip()
    if not re.fullmatch(r"\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?", v):
        raise G3Error("VERSION de la distribución no es válida")
    return v


def version_tuple(value: str):
    """Conservative semver ordering for G3Forge alpha/beta/stable releases."""
    match = re.fullmatch(r"(\d+)\.(\d+)\.(\d+)(?:-([A-Za-z]+)\.(\d+))?", value)
    if not match:
        raise G3Error(f"Versión incompatible para comparar: {value}")
    major, minor, patch, label, serial = match.groups()
    rank = {None: 3, "rc": 2, "beta": 1, "alpha": 0}.get(label, -1)
    return (int(major), int(minor), int(patch), rank, int(serial or 0))


def get_routes(opts, current=None):
    if current:
        return validate_routes({app: check_portable_path(current["apps"][app]) for app in APPS})
    result = {}
    for app in APPS:
        value = getattr(opts, "ruta_" + app, None) or app
        result[app] = check_portable_path(value)
    return validate_routes(result)


def validate_routes(result: dict) -> dict:
    if len(set(v.casefold() for v in result.values())) != 3:
        raise G3Error("Las tres rutas de aplicaciones deben ser diferentes (sin diferenciar mayúsculas)")
    for app, path in result.items():
        parts = path.split("/")
        if any(part.lower() in {".git", ".g3forge", ".codex", ".agents"} for part in parts):
            raise G3Error(f"Ruta de aplicación reservada: {app}={path}")
    for a in APPS:
        for b in APPS:
            if a != b and result[a].casefold().startswith(result[b].casefold() + "/"):
                raise G3Error("Las rutas de aplicaciones no deben anidarse entre sí")
    return result


def files_to_install(src: Path, routes: dict, repo: Path | None = None, manifest: dict | None = None) -> dict:
    entries = {}
    for p in SOURCE_AGENT_PATHS:
        if not (src / p).is_file():
            raise G3Error(f"Falta contrato: {p}")
        target = p if p == "AGENTS.md" else f'{routes[p.split("/")[0]]}/AGENTS.md'
        entries[check_portable_path(target)] = (p, "agent_block")
    for p in FILES:
        if not (src / p).is_file():
            raise G3Error(f"Falta archivo compartido: {p}")
        entries[check_portable_path(p)] = (p, "file")
    entries["scripts/g3forge.py"] = ("tools/g3forge.py", "file")
    for pattern in GLOBS:
        for f in src.glob(pattern):
            if f.is_file():
                rel = f.relative_to(src).as_posix()
                if rel in RETIRED_FILES:
                    continue
                entries[check_portable_path(rel)] = (rel, "file")
    for rel, (source, kind) in entries.items():
        raw_source = src / source
        if raw_source.is_symlink() or not raw_source.is_file() or not raw_source.resolve().is_relative_to(src):
            raise G3Error(f"Fuente inválida o enlace simbólico: {source}")
    if len({p.casefold() for p in entries}) != len(entries):
        raise G3Error("Colisión de rutas administradas entre Windows y Linux")
    if repo is not None:
        owned = set((manifest or {}).get("managed", {})) | set((manifest or {}).get("pending", {}))
        for rel in sorted(RETIRED_FILES):
            # No instalar avisos en workspaces nuevos. Una copia ajena preexistente
            # se trata como conflicto; nunca se presupone propiedad del framework.
            if rel in owned or safe_target(repo, rel).exists():
                entries[rel] = (rel, "retired")
    return dict(sorted(entries.items()))


def start_marker(name: str) -> str:
    return BEGIN.format(name)


def end_marker(name: str) -> str:
    return END.format(name)


def get_managed_block(text: str, name: str) -> tuple[int, int, str] | None:
    st = start_marker(name)
    en = end_marker(name)
    if text.count(st) != 1 or text.count(en) != 1:
        if st in text or en in text:
            raise G3Error(f"Marcadores G3Forge duplicados o rotos: {name}")
        return None
    a = text.index(st) + len(st)
    b = text.index(en)
    if b <= a:
        raise G3Error(f"Orden incorrecto de marcadores G3Forge: {name}")
    fragment = text[a:b]
    first = 2 if fragment.startswith("\r\n") else 1 if fragment.startswith("\n") else 0
    last = 2 if fragment.endswith("\r\n") else 1 if fragment.endswith("\n") else 0
    if not first or not last or len(fragment) < first + last:
        raise G3Error(f"Formato de bloque G3Forge modificado: {name}")
    return a, b, fragment[first:len(fragment) - last]


def detected_eol(text: str) -> str:
    return "\r\n" if text.count("\r\n") > (text.count("\n") - text.count("\r\n")) else "\n"


def inject_block(original: bytes | None, rel: str, body: bytes) -> tuple[bytes, str | None]:
    text = original.decode("utf-8") if original is not None else ""
    newline = detected_eol(text)
    value = body.decode("utf-8").rstrip("\n").replace("\n", newline) + newline
    present = get_managed_block(text, rel)
    if present is not None:
        return original or b"", "already-present"
    prefix = text
    if prefix and not prefix.endswith("\n"):
        prefix += newline
    if prefix and not prefix.endswith(newline * 2):
        prefix += newline
    combined = prefix + start_marker(rel) + newline + value + end_marker(rel) + newline
    return combined.encode("utf-8"), None


def replace_block(existing: bytes, rel: str, body: bytes, expected_base: bytes) -> tuple[bytes | None, str]:
    text = existing.decode("utf-8")
    part = get_managed_block(text, rel)
    if part is None:
        return None, "missing-block"
    a, b, current_body = part
    current = current_body.encode("utf-8")
    if fold_lf(current).rstrip(b"\n") == fold_lf(body).rstrip(b"\n"):
        return existing, "already-current"
    if fold_lf(current).rstrip(b"\n") != fold_lf(expected_base).rstrip(b"\n"):
        return None, "edited-managed-block"
    newline = "\r\n" if text[a:b].startswith("\r\n") else "\n"
    new_fragment = newline + body.decode("utf-8").rstrip("\n").replace("\n", newline) + newline
    return (text[:a] + new_fragment + text[b:]).encode("utf-8"), "updated"


def read_manifest(repo: Path) -> dict | None:
    path = safe_target(repo, ".g3forge/manifest.json")
    if not path.is_file():
        return None
    data = read_json(path)
    if data.get("schema") != SCHEMA or data.get("framework") != NAME:
        raise G3Error("El manifiesto de la instalación es incompatible")
    if not isinstance(data.get("managed"), dict) or not isinstance(data.get("pending"), dict):
        raise G3Error("El manifiesto de G3Forge está incompleto")
    for rel in data["managed"] | data["pending"]:
        relative(rel)
    return data


def create_manifest(repo: Path, src_version: str, routes: dict, client: str, code: str) -> dict:
    return {"schema": SCHEMA, "framework": NAME, "installed_version": None,
            "last_target_version": src_version, "updated_utc": timestamp(),
            "cliente": {"nombre": client, "codigo": code}, "apps": routes,
            "managed": {}, "pending": {}}


def ensure_cliente(src: Path, repo: Path, name: str, code: str, routes: dict, dry: bool, report: list):
    if not name or not code or not RUTA_RE.fullmatch(code):
        raise G3Error("Informá --cliente y --codigo (código sin espacios y con letras, números, _ o -)")
    rel = "config/cliente.toml"
    dest = safe_target(repo, rel)
    if dest.exists():
        with dest.open("rb") as fd:
            data = tomllib.load(fd)
        old = data.get("cliente", {})
        prior = str(old.get("codigo", ""))
        if prior not in (code, "sin-configurar"):
            raise G3Error(f"config/cliente.toml pertenece a otro código de cliente ({prior}); no se modificó")
        report.append((rel, "preservado-cliente"))
        return
    value = (src / rel).read_text(encoding="utf-8")
    # No inferimos presencia de fuentes ni versión por la existencia de un AGENTS.
    value = value.replace('nombre = "SIN_CONFIGURAR"', f'nombre = {json.dumps(name, ensure_ascii=False)}')
    value = value.replace('codigo = "sin-configurar"', f'codigo = {json.dumps(code, ensure_ascii=False)}')
    for app in APPS:
        value = value.replace(f'ruta = "{app}"', f'ruta = {json.dumps(routes[app], ensure_ascii=False)}')
    write_bytes(dest, value.encode("utf-8"), repo, dry)
    report.append((rel, "creado-local"))


def ensure_local_templates(src: Path, repo: Path, dry: bool, report: list):
    for rel in LOCAL_TEMPLATES[1:]:
        dest = safe_target(repo, rel)
        if dest.exists():
            report.append((rel, "preservado-cliente"))
            continue
        write_bytes(dest, (src / rel).read_bytes(), repo, dry)
        report.append((rel, "creado-local"))


def ensure_gitignore(repo: Path, dry: bool, report: list):
    path = safe_target(repo, ".gitignore")
    text = path.read_text(encoding="utf-8") if path.exists() else ""
    missing = [line for line in IGNORE_LINES if line not in text.splitlines()]
    if not missing:
        report.append((".gitignore", "sin-cambios"))
        return
    block = "\n".join(("# G3Forge: exclusiones locales", *missing))
    prefix = text
    if prefix and not prefix.endswith("\n"):
        prefix += "\n"
    if prefix and not prefix.endswith("\n\n"):
        prefix += "\n"
    updated = prefix + block + "\n"
    write_bytes(path, updated.encode("utf-8"), repo, dry)
    report.append((".gitignore", "extendido-sin-borrar"))


def attr_escape(rel: str) -> str:
    """Git attributes use C-style quoting for patterns containing whitespace."""
    pattern = rel.replace("\\", "\\\\").replace('"', '\\"')
    return f'"{pattern}"' if any(x.isspace() for x in pattern) else pattern


def ensure_gitattributes(repo: Path, entries: dict, dry: bool, report: list, expected_sha: str | None = None):
    """Preserve exact bytes of managed files on checkout; never mark SIU directories."""
    lines = ["# Mantener bytes exactos de recursos administrados y sus baselines.",
             ".g3forge/baseline/** -text", ".g3forge/manifest.json -text"]
    lines.extend(f"{attr_escape(rel)} -text" for rel in sorted(entries))
    body = "\n".join(lines)
    new_sha = sha(body.encode("utf-8"))
    path = safe_target(repo, ".gitattributes")
    previous = path.read_bytes() if path.is_file() else b""
    if previous and b"\0" in previous:
        raise G3Error(".gitattributes no es un archivo de texto válido")
    try:
        text = previous.decode("utf-8")
    except UnicodeError as exc:
        raise G3Error(".gitattributes debe ser UTF-8; corregilo manualmente") from exc
    count_open = text.count(ATTR_BEGIN)
    count_close = text.count(ATTR_END)
    eol = detected_eol(text)
    new_block = ATTR_BEGIN + eol + body.replace("\n", eol) + eol + ATTR_END
    if count_open == 0 and count_close == 0:
        sep = "" if not text else eol if text.endswith("\n") else eol + eol
        updated = text + sep + new_block + eol
    elif count_open == 1 and count_close == 1 and text.index(ATTR_BEGIN) < text.index(ATTR_END):
        a = text.index(ATTR_BEGIN)
        b = text.index(ATTR_END) + len(ATTR_END)
        current_block = text[a:b]
        # Manifest hash detects modifications inside our block; outside rules are untouched.
        inner = text[a + len(ATTR_BEGIN):text.index(ATTR_END)]
        old_body = fold_lf(inner.encode("utf-8")).strip(b"\n")
        if expected_sha is None or sha(old_body) != expected_sha:
            raise G3Error("Bloque de .gitattributes modificado o sin baseline; revisar manualmente")
        updated = text[:a] + new_block + text[b:]
    else:
        raise G3Error("Marcadores de .gitattributes duplicados o incompletos")
    if updated.encode("utf-8") != previous:
        write_bytes(path, updated.encode("utf-8"), repo, dry)
        report.append((".gitattributes", "política EOL aplicada sin modificar reglas ajenas"))
    else:
        report.append((".gitattributes", "política EOL vigente"))
    return new_sha


def stage_pending(repo: Path, rel: str, incoming: bytes, ver: str, kind: str, src_rel: str,
                  pending: dict, dry: bool, report: list, reason: str):
    dest = safe_target(repo, f".g3forge/pending/{rel}.incoming")
    write_bytes(dest, incoming, repo, dry)
    pending[rel] = {"kind": kind, "source": src_rel, "sha256": sha(incoming), "version": ver,
                    "motivo": reason, "pending_path": f".g3forge/pending/{rel}.incoming"}
    report.append((rel, f"CONFLICTO ({reason})"))


def apply_framework(src: Path, repo: Path, manifest: dict, dry: bool, report: list):
    ver = version(src)
    entries = files_to_install(src, manifest["apps"], repo, manifest)
    managed = manifest["managed"]
    pending = manifest["pending"]
    # No remover archivos de versiones anteriores automáticamente. Señalarlo.
    old_missing = set(managed) - set(entries)
    for rel in sorted(old_missing):
        report.append((rel, "archivo-retirado-en-origen-conservar-revisión-manual"))
    for rel, (source_rel, kind) in entries.items():
        incoming = retired_content(rel) if kind == "retired" else (src / source_rel).read_bytes()
        dest = safe_target(repo, rel)
        baseline = safe_target(repo, f".g3forge/baseline/{rel}")
        current = dest.read_bytes() if dest.is_file() else None
        stored = managed.get(rel)
        if stored is not None:
            retiring_file = kind == "retired" and stored.get("kind") == "file" and rel in RETIRED_FILES
            if (stored.get("kind") != kind and not retiring_file) or stored.get("source") != source_rel:
                stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, "cambió la identidad del archivo")
                continue
            trusted = (trusted_baseline(baseline.read_bytes(), stored.get("sha256"))
                       if baseline.is_file() else None)
            if trusted is None:
                stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, "base local ausente o alterada")
                continue
            old_content, base_eol_only = trusted
            if base_eol_only:
                report.append((rel, "base de Git recuperable: solo LF/CRLF, hash original confirmado"))
            if current is None:
                stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, "archivo administrado desaparecido")
                continue
            if kind == "agent_block":
                try:
                    next_content, outcome = replace_block(current, rel, incoming, old_content)
                except (UnicodeError, G3Error) as e:
                    next_content, outcome = None, str(e)
            elif same_text_bytes(current, incoming):
                next_content, outcome = incoming, "already-current" if current == incoming else "EOL-normalizado"
            elif same_text_bytes(current, old_content):
                next_content, outcome = incoming, "updated"
            else:
                next_content, outcome = None, "archivo administrado editado localmente"
            if next_content is None:
                if incoming == old_content and kind != "retired":
                    # Sin cambio upstream: conservamos la personalización, sin crear conflicto.
                    managed[rel]["version"] = ver
                    pending.pop(rel, None)
                    report.append((rel, "modificado-localmente-sin-cambio-upstream"))
                    continue
                stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, outcome)
                continue
            if current != next_content:
                write_bytes(dest, next_content, repo, dry)
            write_bytes(baseline, incoming, repo, dry)
            managed[rel] = {"kind": kind, "source": source_rel, "sha256": sha(incoming), "version": ver}
            pending.pop(rel, None)
            report.append((rel, "retirado-acceso-informativo" if kind == "retired" else
                           outcome if current == next_content else "actualizado"))
            continue

        # Archivo aún no gestionado (instalación nueva o conflicto pendiente).
        if kind == "agent_block":
            try:
                if current is None:
                    next_content, reason = inject_block(None, rel, incoming)
                else:
                    next_content, reason = inject_block(current, rel, incoming)
            except (UnicodeError, G3Error) as e:
                stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, str(e))
                continue
            if reason == "already-present":
                # Puede ser una integración manual ya hecha, pero no confiar a ciegas.
                try:
                    block = get_managed_block(current.decode("utf-8"), rel)
                    matches = block and fold_lf(block[2].encode("utf-8")).rstrip(b"\n") == fold_lf(incoming).rstrip(b"\n")
                except (UnicodeError, G3Error):
                    matches = False
                if not matches:
                    stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, "bloque anterior no administrado")
                    continue
                report.append((rel, "bloque-integrado-reconocido"))
            else:
                write_bytes(dest, next_content, repo, dry)
                report.append((rel, "AGENTS agregado sin borrar reglas anteriores"))
        elif current is None:
            write_bytes(dest, incoming, repo, dry)
            report.append((rel, "creado"))
        elif current != incoming:
            stage_pending(repo, rel, incoming, ver, kind, source_rel, pending, dry, report, "archivo preexistente distinto")
            continue
        else:
            report.append((rel, "coincidente-con-origen"))
        write_bytes(baseline, incoming, repo, dry)
        managed[rel] = {"kind": kind, "source": source_rel, "sha256": sha(incoming), "version": ver}
        pending.pop(rel, None)
    if not pending and not old_missing and all(managed.get(rel, {}).get("version") == ver for rel in entries):
        manifest["installed_version"] = ver
    manifest["last_target_version"] = ver
    manifest["updated_utc"] = timestamp()


def save_manifest(repo: Path, manifest: dict, dry: bool):
    payload = (json.dumps(manifest, ensure_ascii=False, indent=2, sort_keys=True) + "\n").encode("utf-8")
    write_bytes(safe_target(repo, ".g3forge/manifest.json"), payload, repo, dry)


def report_result(repo: Path, manifest: dict, report: list, dry: bool):
    print(f"G3Forge | última-versión-completa={manifest.get('installed_version') or 'NINGUNA'} | objetivo={manifest.get('last_target_version')}" + (" | PREVISUALIZACIÓN" if dry else ""))
    for rel, state in report:
        print(f"  {state:53s} {rel}")
    conflicts = len(manifest["pending"])
    print(f"Resumen: {len(report)} actuaciones; {conflicts} conflicto(s). Ninguna operación de BD, Toba, autoload o servidor.")
    if conflicts:
        print("Archivos propuestos en .g3forge/pending/ (solo si no es previsualización). Resolver antes de dar la instalación por completa.")
    if not dry:
        path = safe_target(repo, ".g3forge/reports/ultima-ejecucion.json")
        write_bytes(path, (json.dumps({"when": timestamp(), "actions": report,
                                      "pending": list(manifest["pending"]),
                                      "installed_version": manifest.get("installed_version")},
                                     ensure_ascii=False, indent=2) + "\n").encode("utf-8"), repo, dry)
    return 2 if conflicts else 0


def instalar(args):
    src, repo = source_dir(args.fuente), Path(args.repo).expanduser().resolve()
    check_separated(src, repo)
    check_repo(repo, clean=(args.modo == "existente"))
    if read_manifest(repo) is not None:
        raise G3Error("G3Forge ya está instalado: usá 'actualizar' o 'verificar'")
    routes = get_routes(args)
    manifest = create_manifest(repo, version(src), routes, args.cliente, args.codigo)
    report = []
    # Preflight: no modificar ningún archivo si la política de atributos es inválida.
    entries = files_to_install(src, routes, repo, manifest)
    ensure_gitattributes(repo, entries, True, [])
    ensure_cliente(src, repo, args.cliente, args.codigo, routes, args.previsualizar, report)
    ensure_local_templates(src, repo, args.previsualizar, report)
    ensure_gitignore(repo, args.previsualizar, report)
    manifest["attributes_sha256"] = ensure_gitattributes(repo, entries, args.previsualizar, report)
    apply_framework(src, repo, manifest, args.previsualizar, report)
    save_manifest(repo, manifest, args.previsualizar)
    return report_result(repo, manifest, report, args.previsualizar)


def actualizar(args):
    src, repo = source_dir(args.fuente), Path(args.repo).expanduser().resolve()
    check_separated(src, repo)
    check_repo(repo, clean=True)
    manifest = read_manifest(repo)
    if manifest is None:
        raise G3Error("No hay instalación de G3Forge. Usá instalar")
    newer = version(src)
    previous = manifest.get("last_target_version") or manifest.get("installed_version")
    if previous and version_tuple(newer) < version_tuple(previous):
        raise G3Error(f"Downgrade bloqueado ({previous} -> {newer}). Usá una distribución más reciente.")
    report = []
    entries = files_to_install(src, manifest["apps"], repo, manifest)
    ensure_gitattributes(repo, entries, True, [], manifest.get("attributes_sha256"))
    ensure_gitignore(repo, args.previsualizar, report)
    manifest["attributes_sha256"] = ensure_gitattributes(repo, entries, args.previsualizar, report, manifest.get("attributes_sha256"))
    apply_framework(src, repo, manifest, args.previsualizar, report)
    save_manifest(repo, manifest, args.previsualizar)
    return report_result(repo, manifest, report, args.previsualizar)


def verificar(args):
    repo = Path(args.repo).expanduser().resolve()
    check_repo(repo, clean=False)
    manifest = read_manifest(repo)
    if manifest is None:
        raise G3Error("No existe .g3forge/manifest.json")
    errors = []
    warnings = []
    for rel in sorted(RETIRED_FILES):
        dest = safe_target(repo, rel)
        if dest.exists() and (not dest.is_file() or not same_text_bytes(dest.read_bytes(), retired_content(rel))):
            errors.append(f"Recurso de entregas pendiente de retirada: {rel}")
    for rel, entry in manifest["managed"].items():
        dest = safe_target(repo, rel)
        base = safe_target(repo, f".g3forge/baseline/{rel}")
        trusted = (trusted_baseline(base.read_bytes(), entry.get("sha256"))
                   if base.is_file() else None)
        if not dest.is_file() or trusted is None:
            errors.append(f"Falta archivo/base o hash incorrecto: {rel}")
            continue
        base_exact, eol_only = trusted
        if eol_only:
            warnings.append(f"Base convertida por Git LF/CRLF (recuperable en actualización): {rel}")
        try:
            data = dest.read_bytes()
            body = get_managed_block(data.decode("utf-8"), rel) if entry["kind"] == "agent_block" else None
            if entry["kind"] == "agent_block" and body is None:
                errors.append(f"Falta bloque administrado: {rel}")
                continue
            actual = body[2].encode("utf-8").rstrip(b"\n") + b"\n" if body else data
            if fold_lf(actual).rstrip(b"\n") != fold_lf(base_exact).rstrip(b"\n"):
                warnings.append(f"Modificado localmente: {rel}")
        except (UnicodeError, G3Error) as e:
            errors.append(f"Bloque AGENTS inválido en {rel}: {e}")
    if manifest["pending"]:
        errors.append(f"{len(manifest['pending'])} conflicto(s) pendientes")
    attr = safe_target(repo, ".gitattributes")
    if (manifest.get("installed_version") or "").startswith("2.1.3"):
        if not attr.is_file() or ATTR_BEGIN not in attr.read_text(encoding="utf-8", errors="replace"):
            errors.append("Falta política .gitattributes de G3Forge 2.1.3")
        else:
            text = attr.read_text(encoding="utf-8", errors="replace")
            inner = text.split(ATTR_BEGIN, 1)[1].split(ATTR_END, 1)[0]
            if sha(fold_lf(inner.encode("utf-8")).strip(b"\n")) != manifest.get("attributes_sha256"):
                errors.append("La política .gitattributes fue modificada")
    config = safe_target(repo, ".codex/config.toml")
    if config.exists():
        try:
            with config.open("rb") as fd:
                cfg = tomllib.load(fd)
            if cfg.get("model") != "gpt-6-astra" or cfg.get("model_reasoning_effort") != "xhigh":
                warnings.append("Codex no está configurado con Astra xhigh (revisar manualmente)")
            if cfg.get("mcp_servers"):
                errors.append("Codex configura servidores MCP: verificar prohibición de BD V2")
        except tomllib.TOMLDecodeError as e:
            errors.append(f".codex/config.toml TOML inválido: {e}")
    else:
        errors.append("Falta .codex/config.toml")
    local_cfg = safe_target(repo, "config/cliente.toml")
    if local_cfg.is_file():
        try:
            with local_cfg.open("rb") as fd:
                client = tomllib.load(fd).get("cliente", {})
            if client.get("codigo") != manifest["cliente"]["codigo"]:
                errors.append("Código de cliente inconsistente con manifest")
        except tomllib.TOMLDecodeError as e:
            errors.append(f"config/cliente.toml TOML inválido: {e}")
    else:
        errors.append("Falta config/cliente.toml")
    print(f"G3Forge: versión instalada={manifest['installed_version'] or 'PARCIAL'} | última versión solicitada={manifest['last_target_version']}")
    print(f"Archivos administrados: {len(manifest['managed'])}; pendientes: {len(manifest['pending'])}")
    for item in warnings:
        print("AVISO:", item)
    for item in errors:
        print("ERROR:", item)
    return 2 if errors else 0


def resolver(args):
    repo = Path(args.repo).expanduser().resolve()
    check_repo(repo, clean=False)
    manifest = read_manifest(repo)
    if manifest is None:
        raise G3Error("No hay instalación de G3Forge")
    rel = relative(args.ruta)
    if rel not in manifest["pending"]:
        raise G3Error(f"No hay conflicto pendiente para {rel}")
    entry = manifest["pending"][rel]
    candidate = safe_target(repo, entry["pending_path"])
    if not candidate.is_file() or sha(candidate.read_bytes()) != entry["sha256"]:
        raise G3Error("La propuesta pendiente fue alterada; no se puede resolver")
    dest = safe_target(repo, rel)
    if not dest.is_file():
        raise G3Error("Archivo destino ausente; conciliá la propuesta antes de resolver")
    if entry["kind"] == "retired":
        expected = retired_content(rel)
        if candidate.read_bytes() != expected or not same_text_bytes(dest.read_bytes(), expected):
            raise G3Error("La retirada requiere sustituir el recurso por el aviso informativo exacto antes de resolver")
    if entry["kind"] == "agent_block":
        try:
            block = get_managed_block(dest.read_text(encoding="utf-8"), rel)
        except (UnicodeError, G3Error) as e:
            raise G3Error(f"Revisar marcadores de {rel}: {e}") from e
        if block is None:
            raise G3Error("Falta bloque G3Forge; integralo manualmente antes de resolver")
    if not args.acepto_integracion:
        raise G3Error("Para registrar la resolución manual usá --acepto-integracion después de conciliar")
    baseline = safe_target(repo, f".g3forge/baseline/{rel}")
    write_bytes(baseline, candidate.read_bytes(), repo, dry=False)
    manifest["managed"][rel] = {"kind": entry["kind"], "source": entry["source"],
                                 "sha256": entry["sha256"], "version": entry["version"]}
    del manifest["pending"][rel]
    if not manifest["pending"] and all(v.get("version") == manifest["last_target_version"] for v in manifest["managed"].values()):
        manifest["installed_version"] = manifest["last_target_version"]
    save_manifest(repo, manifest, False)
    print(f"Conflicto de {rel} reconocido tras integración manual. No se modificó el archivo destino.")
    return 0


def build_parser():
    p = argparse.ArgumentParser(description="G3Forge · instalador y actualizador local (sin red ni BD)")
    sub = p.add_subparsers(dest="accion", required=True)
    ins = sub.add_parser("instalar", help="instalar en repo Git nuevo o existente sin sobrescribir datos cliente")
    ins.add_argument("--repo", required=True, help="ruta raíz del repositorio Git del cliente")
    ins.add_argument("--fuente", help="carpeta de distribución G3Forge extraída")
    ins.add_argument("--cliente", required=True, help="nombre descriptivo del cliente")
    ins.add_argument("--codigo", required=True, help="código estable, sin espacios")
    ins.add_argument("--modo", required=True, choices=("nuevo", "existente"))
    for app in APPS:
        ins.add_argument("--ruta-" + app, dest="ruta_" + app,
                         help=f"ruta real de {app} dentro del repo; predeterminado {app}")
    ins.add_argument("--previsualizar", action="store_true", help="calcular cambios sin escribir nada")
    up = sub.add_parser("actualizar", help="actualizar archivos comunes con conflictos detectados")
    up.add_argument("--repo", required=True)
    up.add_argument("--fuente", help="carpeta de la nueva versión extraída")
    up.add_argument("--previsualizar", action="store_true")
    ck = sub.add_parser("verificar", help="auditar manifiesto, integridad y Codex")
    ck.add_argument("--repo", required=True)
    rs = sub.add_parser("resolver", help="registrar integración manual de un conflicto")
    rs.add_argument("--repo", required=True)
    rs.add_argument("--ruta", required=True, help="ruta exacta listada en .g3forge/manifest.json")
    rs.add_argument("--acepto-integracion", action="store_true")
    return p


def main(argv=None):
    args = build_parser().parse_args(argv)
    try:
        return {"instalar": instalar, "actualizar": actualizar,
                "verificar": verificar, "resolver": resolver}[args.accion](args)
    except (G3Error, ValueError, OSError, json.JSONDecodeError, tomllib.TOMLDecodeError) as e:
        print(f"ERROR G3Forge: {e}", file=sys.stderr)
        return 3


if __name__ == "__main__":
    sys.exit(main())
