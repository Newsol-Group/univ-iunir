#!/usr/bin/env python3
"""Valida archivos y contratos ESTÁTICOS de G3Forge. No conecta a BD, red ni Codex."""

import argparse
import re
import sys
import tomllib
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
AGENTS = ["explorador", "implementador", "especialista_toba", "verificador", "documentador"]
SKILLS = ["gestion-toba", "validar-personalizacion", "diagnostico-g3w3", "relevar-personalizaciones"]
APPS = ["gestion", "autogestion", "preinscripcion"]
REQUIRED = ["VERSION", "README.md", "AGENTS.md", "inicializado.md", "config/cliente.toml",
            ".codex/config.toml", "docs/PROYECTO.md", "docs/ESTADO.md",
            "docs/personalizaciones/INDICE.md", "docs/arquitectura/ORQUESTACION.md",
            "docs/arquitectura/SEGURIDAD.md", "docs/arquitectura/HANDOFF.md",
            "scripts/diagnostico_multiagente.py", "scripts/trazabilidad.py", "scripts/workspace.py", "scripts/consola.py", "scripts/terminal.py", "scripts/G3Forge.ps1", "docs/arquitectura/TRAZABILIDAD.md"]


def toml(path):
    with path.open("rb") as stream:
        return tomllib.load(stream)


def good_relative(value):
    if not isinstance(value, str) or not value:
        return False
    normalized = value.replace("\\", "/")
    parts = normalized.split("/")
    return not (normalized.startswith("/") or ":" in parts[0] or
                any(x in ("", ".", "..") for x in parts) or PurePosixPath(normalized).is_absolute())


def validate(root=ROOT, require_client=False):
    issues = []
    installed = (root / ".g3forge/manifest.json").is_file()
    routes = {app: app for app in APPS}
    if installed:
        try:
            import json
            routes.update(json.loads((root / ".g3forge/manifest.json").read_text(encoding="utf-8"))["apps"])
        except (OSError, ValueError, KeyError) as e:
            return [f"Manifiesto de cliente inválido: {e}"]
    required = (["AGENTS.md", "inicializado.md", "config/cliente.toml", ".codex/config.toml",
                 "docs/PROYECTO.md", "docs/ESTADO.md", "docs/personalizaciones/INDICE.md",
                 "docs/arquitectura/ORQUESTACION.md", "docs/arquitectura/SEGURIDAD.md",
                 "docs/arquitectura/HANDOFF.md", "scripts/diagnostico_multiagente.py", "scripts/trazabilidad.py", "scripts/workspace.py", "scripts/consola.py", "scripts/terminal.py", "scripts/G3Forge.ps1", "docs/arquitectura/TRAZABILIDAD.md"] if installed else REQUIRED)
    for item in required + [f"{routes[app]}/AGENTS.md" for app in APPS] + [f".codex/agents/{name}.toml" for name in AGENTS] + [f".agents/skills/{skill}/SKILL.md" for skill in SKILLS]:
        if not (root / item).is_file():
            issues.append(f"Falta archivo obligatorio: {item}")
    if issues:
        return issues
    try:
        codex = toml(root / ".codex/config.toml")
        client = toml(root / "config/cliente.toml")
        roles = {name: toml(root / ".codex/agents" / f"{name}.toml") for name in AGENTS}
    except (OSError, tomllib.TOMLDecodeError) as e:
        return [f"Error de TOML: {e}"]

    if codex.get("model") != "gpt-6-astra" or codex.get("model_reasoning_effort") != "xhigh":
        issues.append("El orquestador debe ser gpt-6-astra con xhigh")
    settings = codex.get("agents", {})
    if settings.get("enabled") is not True or settings.get("max_concurrent_threads_per_session") != 3:
        issues.append("La orquestación debe estar habilitada y limitada a tres subagentes")
    if "mcp_servers" in codex:
        issues.append("La V2 no admite servidores MCP definidos por el proyecto")
    for name, role in roles.items():
        if role.get("name") != name:
            issues.append(f"Rol inconsistente en {name}.toml")
        for field in ("description", "developer_instructions", "model", "model_reasoning_effort"):
            if not isinstance(role.get(field), str) or not role[field].strip():
                issues.append(f"Falta {field} en {name}.toml")
        if role.get("model") == "gpt-6-astra":
            issues.append(f"El subagente {name} no debe utilizar Astra por defecto")
    persona = client.get("cliente", {})
    if not all(isinstance(persona.get(key), str) and persona[key].strip() for key in ("nombre", "codigo")):
        issues.append("Cliente sin nombre/código")
    elif require_client and (persona["nombre"] == "SIN_CONFIGURAR" or persona["codigo"] == "sin-configurar"):
        issues.append("El cliente todavía está sin configurar")
    apps = client.get("aplicaciones", {})
    for app in APPS:
        conf = apps.get(app)
        if not isinstance(conf, dict):
            issues.append(f"Falta aplicación {app} en config/cliente.toml")
            continue
        if not isinstance(conf.get("presente"), bool):
            issues.append(f"{app}.presente debe ser booleano")
        if not good_relative(conf.get("ruta")):
            issues.append(f"{app}.ruta no es una ruta relativa segura")
        if not isinstance(conf.get("version"), str) or not conf["version"].strip():
            issues.append(f"Falta versión o valor desconocida de {app}")
    for skill in SKILLS:
        content = (root / ".agents/skills" / skill / "SKILL.md").read_text(encoding="utf-8")
        if not content.startswith("---\n") or not re.search(r"(?m)^name: " + re.escape(skill) + r"\s*$", content):
            issues.append(f"Skill {skill} carece de frontmatter válido")
    root_rules = (root / "AGENTS.md").read_text(encoding="utf-8").casefold()
    for token in ("bases de datos", "autoload", "toba", "legac", "astra", "reintento", "fork_turns", "handoff"):
        if token not in root_rules:
            issues.append(f"No se encuentra la regla esencial {token} en AGENTS.md")
    if "fork_turns" in (root / ".codex/config.toml").read_text(encoding="utf-8"):
        issues.append("fork_turns debe pasarse al crear subagentes, no como configuración global")
    return issues


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--cliente", action="store_true", help="exigir nombre/código de cliente efectivamente configurados")
    options = parser.parse_args()
    problems = validate(require_client=options.cliente)
    if problems:
        for item in problems:
            print(f"ERROR: {item}", file=sys.stderr)
        return 2
    print("G3Forge: estructura y TOML estáticos OK. No se probaron Codex, SIU, BD ni servidores.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
