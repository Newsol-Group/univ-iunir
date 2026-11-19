#!/usr/bin/env python3
"""Prepara una prueba de lectura autónoma de Codex. No inicia agentes ni modifica archivos.

La comprobación real de herramientas ocurre EN Codex, a partir del prompt generado.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import sys
import tomllib
from pathlib import Path, PurePosixPath

BLOQUEADOS = {".git", ".g3forge", ".env", "secrets.json", "credentials.json", "id_rsa", "id_ed25519"}
EXT_BLOQUEADAS = {".key", ".pem", ".p12", ".pfx"}


def archivo_seguro(repo: Path, valor: str) -> tuple[Path, str]:
    valor = valor.replace("\\", "/")
    rel = PurePosixPath(valor)
    if (not valor or rel.is_absolute() or ":" in rel.parts[0]
            or any(x in ("", ".", "..") for x in valor.split("/"))):
        raise ValueError("La ruta debe ser relativa al repositorio y no contener '..'")
    if any(x.lower() in BLOQUEADOS for x in rel.parts) or rel.suffix.lower() in EXT_BLOQUEADAS:
        raise ValueError("Archivo sensible o interno excluido del diagnóstico")
    file = repo
    for piece in rel.parts:
        file = file / piece
        if file.is_symlink():
            raise ValueError("No se permiten enlaces simbólicos")
    if not file.is_file() or not file.resolve().is_relative_to(repo):
        raise ValueError("El archivo indicado no existe dentro de la raíz del repositorio")
    return file, rel.as_posix()


def verificar_contexto(repo: Path):
    required = ["AGENTS.md", ".codex/config.toml", ".codex/agents/explorador.toml"]
    missing = [item for item in required if not (repo / item).is_file()]
    if missing:
        raise ValueError("No parece una instalación G3Forge completa: " + ", ".join(missing))
    with (repo / ".codex/config.toml").open("rb") as f:
        config = tomllib.load(f)
    with (repo / ".codex/agents/explorador.toml").open("rb") as f:
        explorer = tomllib.load(f)
    if config.get("agents", {}).get("enabled") is not True:
        raise ValueError("Multiagente no está habilitado en .codex/config.toml")
    if explorer.get("name") != "explorador":
        raise ValueError("Falta definición del rol explorador")
    return explorer.get("model", "no-configurado"), explorer.get("model_reasoning_effort", "no-configurado")


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", default=".", help="raíz local del repo cliente; predeterminado .")
    parser.add_argument("--archivo", default="AGENTS.md", help="archivo NO sensible relativo al repositorio")
    args = parser.parse_args(argv)
    try:
        repo = Path(args.repo).expanduser().resolve()
        if not repo.is_dir():
            raise ValueError("Repositorio inexistente")
        model, effort = verificar_contexto(repo)
        file, relative = archivo_seguro(repo, args.archivo)
        data = file.read_bytes()
        digest = hashlib.sha256(data).hexdigest()
        size = len(data)
    except (OSError, ValueError, tomllib.TOMLDecodeError, IndexError) as e:
        print(f"ERROR: {e}", file=sys.stderr)
        return 2
    print(f"G3Forge · diagnóstico local (sin ejecutar Codex) · modelo configurado: {model} / {effort}")
    print(f"Archivo de prueba: {relative}")
    print(f"Tamaño esperado: {size} bytes")
    print(f"SHA-256 esperado: {digest}")
    print("\n--- PROMPT PARA PEGAR EN CODEX (NO EJECUTADO POR ESTE SCRIPT) ---\n")
    print(
        "Astra, realizá una prueba de capacidades de G3Forge sin modificar archivos.\n"
        'Iniciá un único agente `explorador` con `fork_turns: "none"` si el runtime lo admite.\n'
        "El encargo debe ser autocontenido: identificá la raíz del workspace, indicá al agente\n"
        "que lea los AGENTS.md aplicables y que no use bases de datos, Toba, autoloads o servidores.\n"
        f"El explorador debe abrir DIRECTAMENTE `{relative}` desde el workspace, sin extractos\n"
        "suministrados por vos, e informar tamaño en bytes y SHA-256 calculados con sus herramientas.\n"
        "Debe indicar qué herramientas de lectura pudo ejecutar y sus resultados reales.\n"
        "Si no puede usar herramientas, debe devolver bloqueo sin simular autonomía.\n"
        "Al cerrar, diferenciá el modelo configurado del modelo efectivo comprobado y\n"
        "compará el tamaño/hash reportados con el resultado esperado que adjunto abajo.\n"
        f"Tamaño esperado: {size} bytes; SHA-256 esperado: {digest}.\n"
        "No modifiques archivos, no hagas commits ni pushes y no ejecutes consultas a BD."
    )
    print("\n--- FIN DEL PROMPT ---")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
