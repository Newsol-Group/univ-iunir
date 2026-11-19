#!/usr/bin/env python3
"""Servicios locales del workspace; sin catálogo, versiones ni entregas."""
from __future__ import annotations

import subprocess
import tomllib
from pathlib import Path


class Error(Exception):
    """Error de contexto o lectura local."""


def git(repo: Path, *params: str) -> str:
    result = subprocess.run(["git", "-C", str(repo), *params], capture_output=True)
    if result.returncode:
        detail = result.stderr.decode("utf-8", "replace").strip()
        raise Error(f"Git {' '.join(params[:2])} falló: {detail}")
    return result.stdout.decode("utf-8", "replace").strip()


def repo_root(raw: str) -> Path:
    folder = Path(raw).expanduser().resolve()
    if not folder.is_dir():
        raise Error(f"Carpeta inexistente: {folder}")
    actual = Path(git(folder, "rev-parse", "--show-toplevel")).resolve()
    if actual != folder:
        raise Error(f"Usá la raíz Git, no un subdirectorio: {actual}")
    return folder


def read_client_config(repo: Path) -> dict:
    """Lee solamente config/cliente.toml dentro del repositorio real."""
    root = repo.resolve()
    config_dir = root / "config"
    config = config_dir / "cliente.toml"
    if config_dir.is_symlink() or config.is_symlink() or not config.resolve().is_relative_to(root):
        raise Error("config/cliente.toml debe estar dentro del repositorio y sin enlaces")
    if not config.is_file():
        raise Error("Falta config/cliente.toml")
    try:
        with config.open("rb") as stream:
            data = tomllib.load(stream)
    except tomllib.TOMLDecodeError as exc:
        raise Error("config/cliente.toml no contiene TOML válido") from exc
    if not isinstance(data.get("cliente"), dict):
        raise Error("Falta la sección cliente en config/cliente.toml")
    return data


def cliente_code(repo: Path) -> str:
    code = read_client_config(repo)["cliente"].get("codigo")
    if not isinstance(code, str) or not code.strip() or code == "sin-configurar":
        raise Error("Configurá un código de cliente real en config/cliente.toml")
    return code
