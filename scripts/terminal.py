#!/usr/bin/env python3
"""Interfaz local G3Forge AI. Contrato visual derivado de G3Forge Deploy.

Sin dependencias ni conectores. No captura secretos. La consola administra rutas
locales y presenta operaciones; el motor conserva validaciones e integridad.
"""
from __future__ import annotations

import contextlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import threading
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Callable

COLORS = {"cyan":"\033[96m", "darkcyan":"\033[36m", "yellow":"\033[93m", "green":"\033[92m", "red":"\033[91m", "muted":"\033[90m", "white":"\033[97m", "selection":"\033[97;46m"}
LEGACY_COLORS = {"cyan":11, "darkcyan":3, "yellow":14, "green":10, "red":12, "muted":8, "white":15, "selection":0x3F}


def terminal_key() -> str:
    """Lectura por tecla, sin depender de secuencias VT en PowerShell 5.1."""
    if os.name == "nt":
        import msvcrt
        c = msvcrt.getwch()
        if c in ("\xe0", "\x00"):
            return {"H":"up", "P":"down", "K":"left", "M":"right", "G":"home", "O":"end"}.get(msvcrt.getwch(), "other")
        return {"\r":"enter", "\n":"enter", "\x1b":"esc", "\b":"backspace", "\t":"tab", " ":"space"}.get(c,c.lower())
    import select
    import termios
    import tty
    fd = sys.stdin.fileno()
    before = termios.tcgetattr(fd)
    try:
        tty.setraw(fd)
        c = os.read(fd, 1)
        if c == b"\x1b":
            if select.select([fd], [], [], .06)[0]:
                following = os.read(fd, 2)
                return {b"[A":"up", b"[B":"down", b"[C":"right", b"[D":"left", b"[H":"home", b"[F":"end", b"OH":"home", b"OF":"end"}.get(following, "esc")
            return "esc"
        return {b"\r":"enter", b"\n":"enter", b"\x7f":"backspace", b"\t":"tab", b" ":"space"}.get(c,c.decode("utf-8","ignore").lower())
    finally:
        termios.tcsetattr(fd, termios.TCSADRAIN, before)


def config_dir() -> Path:
    if os.name == "nt":
        base = Path(os.environ.get("APPDATA") or (Path.home()/"AppData/Roaming"))
    else:
        base = Path(os.environ.get("XDG_CONFIG_HOME") or (Path.home()/".config"))
    return base / "Newsol" / "G3Forge"


def git_root(folder: Path) -> Path | None:
    if not folder.is_dir(): return None
    result = subprocess.run(["git", "-C", str(folder), "rev-parse", "--show-toplevel"], capture_output=True, text=True, encoding="utf-8", errors="replace")
    if result.returncode: return None
    root = Path(result.stdout.strip()).resolve()
    return root if root.is_dir() else None


def detect_repo(start: Path, *, installed_only: bool = True) -> Path | None:
    root = git_root(start.resolve())
    if root is None: return None
    if installed_only and not (root/".g3forge/manifest.json").is_file(): return None
    return root


class ProjectRegistry:
    """Proyectos recientes del usuario, fuera de repositorios y sin credenciales."""
    def __init__(self, location: Path | None = None):
        self.location = location or config_dir()/"projects.json"

    def list(self) -> list[Path]:
        try:
            payload = json.loads(self.location.read_text(encoding="utf-8"))
            if payload.get("schema") != 1 or not isinstance(payload.get("projects"), list): return []
            out = []
            for value in payload["projects"]:
                if isinstance(value, str):
                    p = Path(value).expanduser().resolve()
                    if git_root(p) == p and p not in out: out.append(p)
            return out[:20]
        except (OSError, ValueError, TypeError):
            return []

    def remember(self, repo: Path) -> None:
        repo = repo.resolve()
        if git_root(repo) != repo: raise ValueError("No se puede recordar una carpeta que no es raíz Git")
        projects = [str(repo)] + [str(p) for p in self.list() if p != repo]
        self.location.parent.mkdir(parents=True, exist_ok=True)
        payload = json.dumps({"schema":1, "projects":projects[:20]}, ensure_ascii=False, indent=2) + "\n"
        with tempfile.NamedTemporaryFile(mode="w", encoding="utf-8", dir=self.location.parent, prefix=".projects-", suffix=".tmp", delete=False) as f:
            f.write(payload)
            tmp = Path(f.name)
        try:
            if os.name != "nt": tmp.chmod(0o600)
            tmp.replace(self.location)
        finally: tmp.unlink(missing_ok=True)


class UI:
    """Navegación con render paginado, Enter/Esc y selección DarkCyan."""
    def __init__(self, *, interactive: bool | None = None, reader=None):
        self.interactive = sys.stdin.isatty() and sys.stdout.isatty() if interactive is None else interactive
        self.reader = reader or terminal_key
        self.last: dict[str, int] = {}
        self._vt = False
        if self.interactive:
            if os.name != "nt": self._vt = True
            else:
                try:
                    import ctypes
                    k = ctypes.windll.kernel32
                    h = k.GetStdHandle(-11)
                    mode = ctypes.c_ulong()
                    if k.GetConsoleMode(h, ctypes.byref(mode)):
                        self._vt = bool(k.SetConsoleMode(h, mode.value | 0x0004))
                except (OSError, AttributeError, ValueError): pass

    def _color(self, s: str, kind: str) -> str:
        if self._vt and "NO_COLOR" not in os.environ:
            return COLORS[kind]+s+"\033[0m"
        return s

    def _line(self, s: str, kind: str | None = None):
        if self.interactive and not self._vt and os.name == "nt" and kind and "NO_COLOR" not in os.environ:
            # Windows Console tradicional (PowerShell 5.1 sin soporte ANSI).
            try:
                import ctypes
                k = ctypes.windll.kernel32
                h = k.GetStdHandle(-11)
                k.SetConsoleTextAttribute(h, LEGACY_COLORS[kind]); print(s)
                k.SetConsoleTextAttribute(h, 7)
                return
            except (OSError, AttributeError): pass
        print(self._color(s, kind) if kind else s)

    def clear(self):
        if self.interactive:
            if self._vt: sys.stdout.write("\033[2J\033[H"); sys.stdout.flush()
            elif os.name == 'nt': os.system('cls')

    @staticmethod
    def _clip(value: str, width: int) -> str:
        value = value.replace("\n", " ").replace("\r", " ")
        return value if len(value) <= width else (value[:max(0,width-3)]+"..." if width > 3 else value[:width])

    def draw(self, title: str, options: list[tuple[str,str]], index: int, subtitle: str = "", *, checked: set[str] | None = None):
        self.clear()
        size=shutil.get_terminal_size(fallback=(100,30))
        width=max(30, min(94,size.columns-1))
        divider="="*width
        self._line(divider,'darkcyan')
        self._line("                        G3FORGE AI",'cyan')
        self._line("                 Desarrollado por Newsol Group")
        self._line("         Desarrollo asistido de SIU Guaraní",'muted')
        self._line(divider,'darkcyan')
        self._line(self._clip(title, width),'yellow')
        if subtitle:self._line(self._clip(subtitle,width),'green')
        self._line("-"*width,'darkcyan')
        # La ventana puede ser baja: no imprimir opciones fuera del buffer.
        available=max(2,size.lines-(12 if subtitle else 11))
        visible=max(1,min(len(options),available))
        first=max(0,min(index-visible//2,len(options)-visible))
        for i in range(first,min(first+visible,len(options))):
            key,label=options[i]
            flag=("[x] " if key in checked else "[ ] ") if checked is not None else ""
            display=(f"  > {flag}{label}" if i==index else f"    {flag}{label}")
            self._line(self._clip(display,width).ljust(width),'selection' if i==index else None)
        if len(options)>visible:self._line(f"  Opciones {first+1}-{first+visible} de {len(options)}",'muted')
        self._line("-"*width,'darkcyan')
        self._line(self._clip("  Flechas: mover | Enter/Derecha: elegir | Esc/Izquierda: volver | N+Enter"+(" | Espacio: marcar" if checked is not None else ""),width),'yellow')

    def menu(self,title:str,options:list[tuple[str,str]],subtitle:str="",menu_id:str="") -> str|None:
        if not options:return None
        idx=min(self.last.get(menu_id or title,0),len(options)-1)
        full=options+[("__back__","0. Volver / salir")]
        if not self.interactive:
            self.draw(title,full,idx,subtitle)
            choice=input("Opción (número, 0 = volver): ").strip()
            if not choice or choice.lower() in ('0','esc','q'):return None
            for i,(key,_) in enumerate(options):
                if choice==key or choice==str(i+1):return key
            return None
        digits=""
        while True:
            self.draw(title,full,idx,subtitle)
            key=self.reader()
            if key in ('up',):idx=(idx-1)%len(full);digits=""
            elif key in ('down','tab'):idx=(idx+1)%len(full);digits=""
            elif key=='home':idx=0;digits=""
            elif key=='end':idx=len(full)-1;digits=""
            elif key in ('esc','left'):return None
            elif key=='backspace':digits=digits[:-1]
            elif key.isdigit() and len(digits)<5:
                digits+=key
                number=int(digits)
                if number==0:idx=len(full)-1
                elif number<=len(options):idx=number-1
            elif key in ('enter','right'):
                if digits:
                    n=int(digits)
                    if n<0 or n>len(options):digits="";continue
                    idx=len(full)-1 if n==0 else n-1
                if idx==len(full):return None
                self.last[menu_id or title]=idx
                return options[idx][0]

    def multi(self,title:str,options:list[tuple[str,str]],subtitle:str="",initial:set[str]|None=None)->list[str]|None:
        if not options:return []
        selected=set(initial or ());idx=0
        if not self.interactive:
            self.draw(title,options,idx,subtitle,checked=selected)
            s=input("Números separados por comas (0 = cancelar; vacío = selección actual): ").strip()
            if s=='0':return None
            if s:
                numbers=[int(x.strip()) for x in s.split(',') if x.strip().isdigit()]
                selected={options[n-1][0] for n in numbers if 1<=n<=len(options)}
            return [k for k,_ in options if k in selected]
        while True:
            self.draw(title,options,idx,subtitle,checked=selected)
            self._line('  A: todos | X: limpiar | Enter: confirmar | Esc: cancelar','yellow')
            key=self.reader()
            if key=='up':idx=(idx-1)%len(options)
            elif key in ('down','tab'):idx=(idx+1)%len(options)
            elif key=='home':idx=0
            elif key=='end':idx=len(options)-1
            elif key=='space':selected.symmetric_difference_update({options[idx][0]})
            elif key=='a':selected={k for k,_ in options}
            elif key=='x':selected.clear()
            elif key in ('enter','right'):return [k for k,_ in options if k in selected]
            elif key in ('esc','left','0'):return None
            elif key.isdigit():
                n=int(key)
                if 1<=n<=len(options):selected.symmetric_difference_update({options[n-1][0]})

    def ask(self,title:str,default:str="") -> str:
        self._line(title,'yellow')
        text=input(f"> [{default}] " if default else "> ").strip()
        return text or default

    def confirm(self,message:str)->bool:
        self._line(message,'yellow')
        return input("Confirmar [s/N]: ").strip().lower() in ('s','si','sí')

    def pause(self,message:str=''):
        if message:self._line(message,'yellow')
        if self.interactive:input("\nEnter para continuar...")

    def progress(self,label:str,operation:Callable):
        """Progreso sin reubicar el cursor: spinner discreto + duración real."""
        started=time.monotonic()
        timestamp=datetime.now().strftime('%H:%M:%S')
        prefix=f"[{timestamp}] [{label[:40]}]"
        end=threading.Event()
        def render():
            frames='|/-\\';i=0
            while not end.wait(.15):
                text=f"{prefix} {frames[i%4]}"
                sys.stdout.write('\r'+text);sys.stdout.flush();i+=1
            sys.stdout.write('\r'+' '*(len(prefix)+3)+'\r');sys.stdout.flush()
        thread=None
        if self.interactive:
            thread=threading.Thread(target=render,daemon=True)
            thread.start()
        try:
            result=operation()
        except Exception:
            end.set()
            if thread:thread.join(timeout=1)
            self._line(f"{prefix} --> [{time.monotonic()-started:7.2f} s] [ERROR]",'red')
            raise
        end.set()
        if thread:thread.join(timeout=1)
        self._line(f"{prefix} --> [{time.monotonic()-started:7.2f} s] [OK]",'green')
        return result


def browse_directory(ui:UI, start:Path|None=None, *, require_git:bool=True)->Path|None:
    """Selector local de carpetas. No recorre el disco ni utiliza red."""
    current=(start or Path.cwd()).expanduser().resolve()
    if not current.is_dir():current=Path.home()
    while True:
        dirs=[]
        with contextlib.suppress(OSError):
            dirs=sorted((p for p in current.iterdir() if p.is_dir() and not p.is_symlink() and p.name not in ('.git','.svn','node_modules','vendor','__pycache__')),
                       key=lambda p:p.name.casefold())[:100]
        options=[]
        if not require_git or git_root(current)==current:
            options.append(('select','[Seleccionar esta carpeta]'))
        if current.parent!=current:options.append(('parent','[..] Directorio superior'))
        if os.name=='nt' and current.parent==current:
            options.append(('drives','[Cambiar unidad]'))
        options.extend((str(i),f"[Carpeta] {p.name}") for i,p in enumerate(dirs))
        options.append(('manual','Introducir ruta (opcional)'))
        choice=ui.menu('Explorar carpetas',options,str(current),'browser')
        if choice is None:return None
        if choice=='select':return current
        if choice=='parent':current=current.parent
        elif choice=='drives':
            drives=[Path(f'{chr(i)}:/') for i in range(ord('A'),ord('Z')+1) if Path(f'{chr(i)}:/').is_dir()]
            candidate=ui.menu('Seleccionar unidad',[(str(i),str(d)) for i,d in enumerate(drives)])
            if candidate is not None:current=drives[int(candidate)]
        elif choice=='manual':
            raw=ui.ask('Ruta de carpeta')
            if raw:
                target=Path(raw.strip('"')).expanduser().resolve()
                if target.is_dir():current=target
                else:ui.pause('No existe la carpeta indicada.')
        else:current=dirs[int(choice)]


def choose_project(ui:UI,registry:ProjectRegistry,*,allow_unconfigured:bool=False)->Path|None:
    projects=registry.list()
    options=[(str(p),f'{p.name}  |  {p}') for p in projects]
    options.append(('browse','Buscar repositorio en carpetas...'))
    choice=ui.menu('Seleccionar cliente',options,'Proyectos recientes guardados localmente','recent')
    if choice is None:return None
    if choice=='browse':
        chosen=browse_directory(ui,Path.cwd(),require_git=True)
    else:chosen=Path(choice)
    if chosen is None:return None
    if git_root(chosen)!=chosen:
        ui.pause('No es la raíz de un repositorio Git.')
        return None
    if not allow_unconfigured and not (chosen/'config/cliente.toml').is_file():
        ui.pause('No hay configuración de cliente. Usá Administrar para instalar G3Forge.')
        return None
    registry.remember(chosen)
    return chosen
