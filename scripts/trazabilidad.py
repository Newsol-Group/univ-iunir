#!/usr/bin/env python3
"""Registro explícito, local y limitado de ejecuciones de agentes G3Forge.

NO intercepta conversaciones ni invoca Codex. Las declaraciones se conservan como
reportes, no como pruebas de hechos. La evidencia externa se referencia por SHA-256.
No conecta con servidores, bases, ni servicios. Python 3.11+.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import secrets
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

SCHEMA = 1
REGISTRY = '.g3forge/ejecuciones'
ROLES = ('explorador', 'implementador', 'verificador', 'especialista_toba',
         'documentador', 'orquestador', 'otro')
RESULTADOS = ('completado', 'bloqueado', 'fallido', 'parcial')
ID_RE = re.compile(r'^RUN-\d{8}T\d{6}Z-[0-9a-f]{8}$')


class ErrorRegistro(Exception):
    pass


def ahora():
    return datetime.now(timezone.utc).isoformat(timespec='seconds')


def comprobacion_repo(value):
    base = Path(value).expanduser().resolve()
    if not base.is_dir():
        raise ErrorRegistro('La carpeta del repositorio no existe.')
    git = subprocess.run(['git', '-C', str(base), 'rev-parse', '--show-toplevel'],
                         capture_output=True, encoding='utf-8', errors='replace')
    if git.returncode or Path(git.stdout.strip()).resolve() != base:
        raise ErrorRegistro('--repo debe ser la raíz del repositorio Git.')
    directory = base / REGISTRY
    if (base / '.g3forge').is_symlink() or directory.is_symlink():
        raise ErrorRegistro('No se admiten enlaces simbólicos en la ruta de registros.')
    return base


def archivo_registro(repo, identificador):
    if not ID_RE.fullmatch(identificador):
        raise ErrorRegistro('ID de ejecución inválido.')
    target = repo / REGISTRY / (identificador + '.json')
    if target.is_symlink():
        raise ErrorRegistro('No se admite un registro con enlace simbólico.')
    return target


def cargar(repo, identificador):
    target = archivo_registro(repo, identificador)
    if not target.is_file():
        raise ErrorRegistro('No existe ese registro de ejecución.')
    obj = json.loads(target.read_text(encoding='utf-8'))
    if obj.get('schema') != SCHEMA or obj.get('id') != identificador:
        raise ErrorRegistro('Registro dañado o con esquema incompatible.')
    return obj


def guardar(repo, registro, nuevo=False):
    target = archivo_registro(repo, registro['id'])
    if nuevo and target.exists():
        raise ErrorRegistro('El registro ya existe.')
    target.parent.mkdir(parents=True, exist_ok=True)
    temp = target.with_name(target.name + '.' + secrets.token_hex(4) + '.tmp')
    try:
        temp.write_text(json.dumps(registro, ensure_ascii=False, indent=2,
                                   sort_keys=True) + '\n', encoding='utf-8')
        os.replace(temp, target)
    finally:
        if temp.exists():
            temp.unlink()


def commit(repo):
    result = subprocess.run(['git', '-C', str(repo), 'rev-parse', 'HEAD'],
                            capture_output=True, encoding='utf-8', errors='replace')
    return result.stdout.strip() if result.returncode == 0 else None


def no_sensible(repo, path):
    if not path:
        return None
    p = Path(path)
    if p.is_absolute() or '..' in p.parts or not p.parts or ':' in str(path):
        raise ErrorRegistro('La evidencia debe ser una ruta relativa dentro del repositorio.')
    rel = p.as_posix().replace('\\', '/')
    parts = [x.lower() for x in rel.split('/')]
    if parts[0] == '.git' or any(x.startswith('.env') for x in parts):
        raise ErrorRegistro('La ruta de evidencia es sensible o está reservada.')
    if any(x.endswith(('.key', '.pem', '.p12', '.pfx', '.sqlite', '.db')) for x in parts):
        raise ErrorRegistro('No se admiten evidencias de credenciales o bases de datos.')
    target = repo / rel
    if target.is_symlink() or not target.is_file() or not target.resolve().is_relative_to(repo):
        raise ErrorRegistro('La evidencia no existe o no es un archivo local regular.')
    raw = target.read_bytes()
    return {'ruta': rel, 'sha256': hashlib.sha256(raw).hexdigest(), 'bytes': len(raw),
            'nota': 'Hash de referencia; no acredita por sí solo la ejecución o el modelo real.'}


def iniciar(args):
    repo = comprobacion_repo(args.repo)
    ident = 'RUN-' + datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + secrets.token_hex(4)
    record = {'schema': SCHEMA, 'id': ident, 'creada_utc': ahora(),
              'cerrada_utc': None, 'objetivo': args.objetivo, 'tipo': args.tipo,
              'sesion_codex_reportada': args.sesion, 'commit_inicial': commit(repo),
              'estado': 'en_curso', 'agentes': [], 'resumen': None,
              'advertencia': 'Los datos reportados por agentes no equivalen a telemetría verificada.'}
    guardar(repo, record, nuevo=True)
    print(ident)
    print('Registro local creado (no se conecta a Codex ni inicia agentes).')
    return 0


def anotar(args):
    repo = comprobacion_repo(args.repo)
    obj = cargar(repo, args.id)
    if obj['estado'] != 'en_curso':
        raise ErrorRegistro('No se pueden añadir agentes a un registro cerrado.')
    evidence = no_sensible(repo, args.evidencia)
    if args.modelo_efectivo and not args.fuente_modelo:
        raise ErrorRegistro('--modelo-efectivo requiere --fuente-modelo; se registra como declaración, no como verificación independiente.')
    if args.fuente_modelo and not evidence:
        raise ErrorRegistro('Para indicar la fuente del modelo efectivo, agregá --evidencia con un archivo comprobable.')
    event = {
        'instante_utc': ahora(), 'nombre': args.agente, 'rol': args.rol,
        'resultado_reportado': args.resultado, 'modelo_configurado': args.modelo_configurado,
        'modelo_efectivo_declarado': args.modelo_efectivo,
        'fuente_modelo_declarada': args.fuente_modelo,
        'acceso_workspace_reportado': args.acceso,
        'herramientas_reportadas': args.herramientas,
        'resumen': args.detalle, 'evidencia_archivo': evidence,
        'calidad_evidencia': 'referencia_con_hash_no_auditoria' if evidence else 'solo_declaracion',
    }
    obj['agentes'].append(event)
    guardar(repo, obj)
    print(f"Agente registrado: {args.agente}; evidencia: {event['calidad_evidencia']}")
    return 0


def cerrar(args):
    repo = comprobacion_repo(args.repo)
    obj = cargar(repo, args.id)
    if obj['estado'] != 'en_curso':
        raise ErrorRegistro('La ejecución ya está cerrada.')
    obj['estado'] = args.estado
    obj['resumen'] = args.resumen
    obj['cerrada_utc'] = ahora()
    obj['commit_final'] = commit(repo)
    guardar(repo, obj)
    print(f"Ejecución cerrada: {args.id} | {args.estado} | agentes reportados: {len(obj['agentes'])}")
    return 0


def resumen(args):
    repo = comprobacion_repo(args.repo)
    directory = repo / REGISTRY
    if not directory.exists():
        print('Sin registros locales de ejecuciones. No implica que Codex no haya ejecutado tareas.')
        return 0
    files = sorted(directory.glob('RUN-*.json'), reverse=True)[:args.limite]
    if not files:
        print('Sin registros locales de ejecuciones.')
        return 0
    for file in files:
        if file.is_symlink():
            raise ErrorRegistro('Registro de ejecución con enlace simbólico detectado.')
        o = cargar(repo, file.stem)
        print(f"{o['id']} | {o['estado']:10s} | {len(o['agentes'])} agente(s) | {o['objetivo'][:90]}")
    print('Datos registrados localmente; la ejecución real debe contrastarse con la traza de Codex.')
    return 0


def detalle(args):
    """Consulta humana de un registro local, sin convertir reportes en telemetría."""
    repo = comprobacion_repo(args.repo)
    obj = cargar(repo, args.id)
    print(f"{obj['id']} | {obj['estado']} | {obj.get('tipo')} | {obj.get('objetivo')}")
    print(f"Creada: {obj.get('creada_utc')} | Cerrada: {obj.get('cerrada_utc') or 'pendiente'}")
    print(f"Commit inicial: {obj.get('commit_inicial') or 'desconocido'}")
    for i, agent in enumerate(obj.get('agentes', []), 1):
        evidence = agent.get('evidencia_archivo')
        evidence_str = 'sin evidencia de archivo'
        if evidence:
            try:
                actual = no_sensible(repo, evidence['ruta'])
                ok = actual['sha256'] == evidence['sha256'] and actual['bytes'] == evidence['bytes']
                evidence_str = ('hash coincidente' if ok else 'hash diferente') + ': ' + evidence['ruta']
            except (ErrorRegistro, OSError, ValueError):
                evidence_str = 'evidencia no disponible'
        print(f"  {i}. {agent.get('nombre', '?')} [{agent.get('rol', '?')}]: {agent.get('resultado_reportado', '?')}")
        print(f"     Modelo configurado: {agent.get('modelo_configurado') or 'desconocido'}; "
              f"efectivo declarado: {agent.get('modelo_efectivo_declarado') or 'no confirmado'}")
        print(f"     Evidencia: {evidence_str}")
    print('Fuente: registro declarativo local. Consultar trazas reales de Codex para confirmar delegación.')
    return 0


def verificar(args):
    repo = comprobacion_repo(args.repo)
    directory = repo / REGISTRY
    if not directory.exists():
        print('No hay registros que comprobar.')
        return 0
    errors = []
    for f in directory.glob('RUN-*.json'):
        try:
            obj = cargar(repo, f.stem)
            for ag in obj.get('agentes', []):
                ev = ag.get('evidencia_archivo')
                if ev:
                    new = no_sensible(repo, ev['ruta'])
                    if new['sha256'] != ev['sha256'] or new['bytes'] != ev['bytes']:
                        errors.append(f"Evidencia cambió: {f.stem}: {ev['ruta']}")
        except (ErrorRegistro, ValueError, KeyError, OSError, json.JSONDecodeError) as exc:
            errors.append(f'{f.name}: {exc}')
    for e in errors:
        print('ERROR:', e)
    print(f'Registros examinados: {len(list(directory.glob("RUN-*.json")))}. Errores: {len(errors)}')
    return 2 if errors else 0


def exportar(args):
    repo = comprobacion_repo(args.repo)
    obj = cargar(repo, args.id)
    path = Path(args.salida)
    if path.is_absolute() or '..' in path.parts or len(path.parts) < 3 or path.parts[:2] != ('docs', 'g3forge'):
        raise ErrorRegistro('Exportar únicamente a docs/g3forge/<nombre>.json dentro del repositorio.')
    target = repo / path
    if not target.resolve().is_relative_to(repo) or any((repo / Path(*path.parts[:i])).is_symlink() for i in range(1, len(path.parts) + 1)):
        raise ErrorRegistro('La exportacion no puede atravesar enlaces simbolicos ni salir del repositorio.')
    if target.exists() or target.is_symlink():
        raise ErrorRegistro('El archivo de exportación ya existe; no se sobrescribe.')
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(obj, ensure_ascii=False, indent=2, sort_keys=True) + '\n', encoding='utf-8')
    print(f'Exportado por solicitud explícita: {path}. Revisá los datos antes de versionarlos.')
    return 0


def main():
    p = argparse.ArgumentParser(description='G3Forge · trazabilidad declarativa local (no telemetría)')
    sp = p.add_subparsers(dest='accion', required=True)
    a = sp.add_parser('iniciar', help='Crear registro local para una tarea multiagente relevante')
    a.add_argument('--repo', default='.')
    a.add_argument('--objetivo', required=True)
    a.add_argument('--tipo', choices=('exploracion', 'implementacion', 'verificacion', 'qa', 'otro'), default='otro')
    a.add_argument('--sesion', help='ID de sesión Codex, solo si se conoce')
    a.set_defaults(handler=iniciar)
    a = sp.add_parser('anotar', help='Registrar resultado reportado por un agente')
    a.add_argument('--repo', default='.')
    a.add_argument('--id', required=True)
    a.add_argument('--agente', required=True)
    a.add_argument('--rol', choices=ROLES, required=True)
    a.add_argument('--resultado', choices=RESULTADOS, required=True)
    a.add_argument('--modelo-configurado')
    a.add_argument('--modelo-efectivo')
    a.add_argument('--fuente-modelo')
    a.add_argument('--acceso', choices=('no_comprobado', 'reportado', 'bloqueado'), default='no_comprobado')
    a.add_argument('--herramientas', help='Herramientas reportadas (texto breve)')
    a.add_argument('--evidencia', help='Archivo local con evidencia; se guarda la ruta y el SHA-256, no el contenido')
    a.add_argument('--detalle', required=True)
    a.set_defaults(handler=anotar)
    a = sp.add_parser('cerrar', help='Cerrar ejecución y registrar dictamen reportado')
    a.add_argument('--repo', default='.')
    a.add_argument('--id', required=True)
    a.add_argument('--estado', choices=RESULTADOS, required=True)
    a.add_argument('--resumen', required=True)
    a.set_defaults(handler=cerrar)
    a = sp.add_parser('resumen', help='Ver últimos registros locales')
    a.add_argument('--repo', default='.')
    a.add_argument('--limite', type=int, default=10)
    a.set_defaults(handler=resumen)
    a = sp.add_parser('detalle', help='Mostrar agentes y comprobación actual de evidencia de una ejecución')
    a.add_argument('--repo', default='.')
    a.add_argument('--id', required=True)
    a.set_defaults(handler=detalle)
    a = sp.add_parser('verificar', help='Verificar esquema y hashes de evidencias referenciadas')
    a.add_argument('--repo', default='.')
    a.set_defaults(handler=verificar)
    a = sp.add_parser('exportar', help='Exportación explícita y revisable para versionar')
    a.add_argument('--repo', default='.')
    a.add_argument('--id', required=True)
    a.add_argument('--salida', required=True)
    a.set_defaults(handler=exportar)
    args = p.parse_args()
    try:
        return args.handler(args)
    except (ErrorRegistro, OSError, ValueError, json.JSONDecodeError) as exc:
        print('ERROR:', exc, file=sys.stderr)
        return 2


if __name__ == '__main__':
    sys.exit(main())
