#!/usr/bin/env python3
"""Consola local de desarrollo y administración del framework G3Forge AI.

Interfaz sin dependencias externas (Windows PowerShell 5.1 / terminales ANSI).
No ejecuta SQL, red, SSH, operaciones Toba, autoloads ni despliegues.
"""
from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

import workspace


from terminal import UI, ProjectRegistry, detect_repo, choose_project


class App:
    def __init__(self,ui:UI,root:Path,distribution:Path|None=None,registry:ProjectRegistry|None=None):
        self.ui=ui
        self.root=root
        self.distribution=distribution or root
        self.repo: Path|None=None
        self.registry=registry or ProjectRegistry()

    def choose_repo(self, *, allow_unconfigured:bool=False)->bool:
        # Desde VS Code o cualquier subcarpeta, no solicitar ninguna ruta.
        auto=detect_repo(Path.cwd(),installed_only=not allow_unconfigured)
        if auto is not None and (allow_unconfigured or (auto/'config/cliente.toml').is_file()):
            self.repo=auto
            self.registry.remember(auto)
            return True
        choice=choose_project(self.ui,self.registry,allow_unconfigured=allow_unconfigured)
        if choice is None:return False
        self.repo=choice
        return True

    def required_repo(self)->Path:
        if self.repo is None and not self.choose_repo():
            raise workspace.Error("No seleccionaste un repositorio")
        assert self.repo is not None
        return self.repo

    def repo_title(self)->str:
        if not self.repo:return "Sin repositorio seleccionado"
        try:
            code=workspace.cliente_code(self.repo)
            return f"Cliente: {code} | Repo: {self.repo.name} | Rama: {workspace.git(self.repo,'branch','--show-current')}"
        except Exception:
            return f"Repositorio {self.repo.name} (sin instalar / configurar)"

    def main(self):
        if self.repo is None:
            self.choose_repo()
        while True:
            action=self.ui.menu("Panel principal",[
                ("verify","Validaciones e historial"),
                ("admin","Administrar G3Forge"),
                ("repo","Cambiar cliente")],self.repo_title(),menu_id="main")
            if action is None:return
            if action=='verify' and self.repo is None:
                if not self.choose_repo():
                    continue  # cancelación de selección: volver, no informar un error
            try:
                if action=='repo':
                    self.repo=None
                    self.choose_repo()
                elif action=='verify':self.verificaciones()
                elif action=='admin':self.administrar()
            except (workspace.Error,OSError,ValueError,subprocess.CalledProcessError) as e:
                self.ui.pause(f"ERROR: {e}")

    def verificaciones(self):
        repo=self.required_repo()
        choice=self.ui.menu('Validaciones e historial',[
            ('framework','Verificar archivos administrados de G3Forge'),
            ('history','Resumen de ejecuciones de agentes'),
            ('diagnostic','Preparar diagnóstico de lectura multiagente'),('back','Volver')])
        if choice in (None,'back'):return
        script={'framework':'g3forge.py','history':'trazabilidad.py',
                'diagnostic':'diagnostico_multiagente.py'}[choice]
        args=['verificar','--repo',str(repo)] if choice=='framework' else ['--repo',str(repo)]
        if choice=='history':args=['resumen',*args,'--limite','12']
        subprocess.run([sys.executable,str(repo/'scripts'/script),*args],check=True)
        self.ui.pause()

    def _tool(self,*args:str)->int:
        tool=self.distribution/'tools/g3forge.py'
        if not tool.is_file():raise workspace.Error('No se encontró el motor de instalación de la distribución global.')
        return subprocess.run([sys.executable,str(tool),*args],check=False).returncode

    def _check_clean(self,repo:Path):
        dirty=workspace.git(repo,'status','--porcelain','--untracked-files=all')
        if dirty:
            raise workspace.Error('Hay cambios locales en Git. Confirmalos o resguardalos antes de instalar/actualizar; no se hizo nada.')

    def _administrar_instalacion(self,repo:Path,existing:bool):
        self._check_clean(repo)
        dist=self.distribution
        if not (dist/'VERSION').is_file() or not (dist/'tools/g3forge.py').is_file():
            raise workspace.Error('Faltan recursos de instalación globales.')
        if repo == dist or dist.is_relative_to(repo) or repo.is_relative_to(dist):
            raise workspace.Error('La distribución y el cliente deben estar en carpetas independientes.')
        operation='actualizar' if existing else 'instalar'
        args=[operation,'--fuente',str(dist),'--repo',str(repo)]
        if not existing:
            tracked=workspace.git(repo,'ls-files')
            mode='existente' if tracked else 'nuevo'
            if (repo/'config/cliente.toml').is_file():
                conf=workspace.read_client_config(repo)
                label=conf.get('cliente',{}).get('nombre') or repo.name
                code=conf.get('cliente',{}).get('codigo') or repo.name.lower()
            else:
                label=repo.name
                code=repo.name.lower()
            if code == 'sin-configurar':code=repo.name.lower()
            # Sólo preguntar por identidad si todavía no está acreditada.
            if not (repo/'config/cliente.toml').is_file():
                label=self.ui.ask('Nombre real del cliente (Enter acepta lo sugerido)',label)
                code=self.ui.ask('Código estable del cliente',code)
            args+=['--modo',mode,'--cliente',label,'--codigo',code,
                   '--ruta-gestion','gestion','--ruta-autogestion','autogestion',
                   '--ruta-preinscripcion','preinscripcion']
            if self.ui.confirm('¿Configurar rutas no estándar? (normalmente NO)'):
                for app in ('gestion','autogestion','preinscripcion'):
                    key='--ruta-'+app
                    pos=args.index(key)+1
                    args[pos]=self.ui.ask('Carpeta de instrucciones '+app,args[pos])
        print(f'\n{operation.upper()} | {repo.name} | G3Forge {(dist/"VERSION").read_text(encoding="utf-8").strip()}')
        print('Previsualización (no escribe archivos):')
        rc=self._tool(*args,'--previsualizar')
        if rc not in (0,2):raise workspace.Error(f'Previsualización detenida (código {rc})')
        if rc==2:print('AVISO: se detectaron conflictos; deberán conciliarse manualmente.')
        answer=self.ui.ask(f'Para continuar escribí {operation.upper()} (Enter cancela)').upper()
        if answer!=operation.upper():
            self.ui.pause('Cancelado sin aplicar cambios.');return
        rc=self._tool(*args)
        if rc not in (0,2):raise workspace.Error(f'Instalación no completada: código {rc}')
        if rc==2:
            self.ui.pause('Existen conflictos en .g3forge/pending/. No se sobrescribieron archivos distintos.')
        else:
            verification=self._tool('verificar','--repo',str(repo))
            if verification!=0:raise workspace.Error('La instalación terminó pero falló la verificación.')
            self.ui.pause('Instalación/actualización local verificada. Revisá git diff antes de hacer commit.')
        self.registry.remember(repo)

    def administrar(self):
        while True:
            choice=self.ui.menu('Administrar G3Forge',[
                ('install','Instalar o adoptar framework en un repositorio'),
                ('update','Actualizar framework del cliente'),
                ('verify','Verificar integridad del framework'),
                ('change','Elegir otro repositorio')],self.repo_title(),menu_id='admin')
            if choice is None:return
            if choice=='change':
                self.repo=None
                self.choose_repo(allow_unconfigured=True)
                continue
            if self.repo is None and not self.choose_repo(allow_unconfigured=True):continue
            repo=self.required_repo()
            try:
                if choice=='install':
                    if (repo/'.g3forge/manifest.json').is_file():
                        self.ui.pause('G3Forge ya está instalado. Elegí Actualizar.');continue
                    self._administrar_instalacion(repo,False)
                elif choice=='update':
                    if not (repo/'.g3forge/manifest.json').is_file():
                        self.ui.pause('No hay G3Forge instalado. Elegí Instalar.');continue
                    self._administrar_instalacion(repo,True)
                elif choice=='verify':
                    rc=self._tool('verificar','--repo',str(repo))
                    self.ui.pause('Integridad correcta.' if rc==0 else 'Verificación fallida: revisá los errores.')
            except (workspace.Error,OSError) as exc:self.ui.pause(f'ERROR: {exc}')


def main(argv=None)->int:
    parser=argparse.ArgumentParser(description='G3Forge AI · desarrollo y administración local del framework')
    parser.add_argument('--repo',help='Raíz Git del cliente (opcional)')
    parser.add_argument('--distribucion',help='Distribución global de G3Forge')
    args=parser.parse_args(argv)
    distribution=Path(args.distribucion).expanduser().resolve() if args.distribucion else Path(__file__).resolve().parents[1]
    app=App(UI(),Path(__file__).resolve().parents[1],distribution)
    if args.repo:
        app.repo=workspace.repo_root(args.repo)
        app.registry.remember(app.repo)
    else:
        app.repo=detect_repo(Path.cwd(),installed_only=True)
        if app.repo is not None:app.registry.remember(app.repo)
    try:app.main()
    except (workspace.Error,OSError,ValueError,EOFError,KeyboardInterrupt) as exc:
        print(f'G3Forge: {exc}',file=sys.stderr)
        return 2
    return 0


if __name__=='__main__':
    raise SystemExit(main())
