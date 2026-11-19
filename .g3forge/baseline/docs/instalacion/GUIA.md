# G3Forge AI — Instalación, adopción y actualización del framework

Esta guía cubre desarrollo, revisión, documentación y gestión del framework. La administración de entregas y ambientes corresponde a G3Forge Deploy, proyecto independiente.

La versión declarada es `2.2.2-alpha.2`; hay cambios locales posteriores no publicados. No se declara una versión nueva ni una actualización aplicada a clientes reales.

## Iniciar desde la distribución

Requisitos: Python 3.11 o superior, Git y Windows PowerShell 5.1 para los lanzadores `.ps1`. Desde la carpeta de la distribución, ejecutar `.\G3Forge.ps1`. Usa sus recursos locales aunque exista un comando global anterior. No requiere instalar el CLI global ni modificar PATH. El menú ofrece validaciones e historial, administración del framework y selección de repositorio.

> **Windows PowerShell 5.1 · corrección 2.2.2-alpha.2:** si la instalación global de la alpha.1 terminó en `WriteAllText` o mostró textos como `instalaciÃ³n`, extraé la distribución en una carpeta independiente y ejecutá nuevamente `./Instalar-CLI.ps1`. La reejecución permite recuperar una instalación global parcial; no altera repositorios. Después comprobá `g3forge --version` (esperado según `VERSION`: `2.2.2-alpha.2`). No repitas `inicializado.md` por una actualización rutinaria.


## Instalación o actualización global opcional

1. Extraer el ZIP de G3Forge 2.2.2 en una carpeta de herramientas fuera de repositorios de clientes.
2. Abrir **Windows PowerShell 5.1** en esa carpeta (debe verse `Instalar-CLI.ps1`).
3. Ejecutar `.\Instalar-CLI.ps1`; confirmar escribiendo `INSTALAR` después de revisar la ubicación.
4. Ejecutar `g3forge --version` en la misma terminal, o abrir una terminal nueva si todavía no se reconoce `g3forge`.
5. Abrir VS Code en el repositorio de un cliente y ejecutar **`g3forge`** desde su terminal. Se detecta la raíz Git desde cualquier subcarpeta, sin copiar rutas.

El instalador global **no modifica clientes**. En el menú elegí `Administrar G3Forge > Actualizar framework del cliente` para actualizar una instalación 2.2.1, o `Instalar o adoptar` para un repositorio sin G3Forge. La adopción/actualización exige árbol Git limpio y confirmación después de previsualizar. No ejecutar `inicializado.md` por una actualización rutinaria.

En el uso habitual: `g3forge` desde el repositorio, con la distribución global actualizada. Desde otra carpeta, seleccionar un proyecto reciente o recorrer las carpetas con las flechas. El registro de rutas recientes se guarda bajo `%APPDATA%\Newsol\G3Forge\projects.json`, **fuera de los repositorios**. El lanzador `scripts/G3Forge.ps1` usa siempre la consola local del workspace para evitar un CLI global antiguo con operaciones retiradas. Para instalar o actualizar el framework, abrir la distribución central con `.\G3Forge.ps1`; el workspace no contiene `tools/`.

**Pendiente de QA:** comprobar visualmente instalación global, navegación y menús en Windows PowerShell 5.1 real. 2.2.2 no instala releases ni ejecuta Git por SSH.

---

## Asistente alternativo y CLI del framework

Los procedimientos siguientes conservan instalación, adopción, actualización y resolución de conflictos del framework. La CLI global es opcional; la consola de la distribución permite realizar estas operaciones desde **Administrar G3Forge**.

> **Primera opción en Windows: asistente interactivo.** Solicita rutas de recursos y destinos, previsualiza y exige confirmación antes de escribir. No realiza conexión a BD, servidores, Toba ni autoloads.

## Instalador/actualizador interactivo en Windows

1. Extraé la distribución elegida a una carpeta local, **separada** del repositorio del cliente.
2. En esa carpeta abrí PowerShell y ejecutá:

```powershell
.\tools\G3Forge.ps1
```

3. Elegí una de las opciones: **1 instalar/adoptar**, **2 actualizar**, **3 verificar**, **4 consultar registro de ejecuciones**.
4. El asistente solicita la **ruta de los recursos G3Forge**, la **carpeta objetivo** (raíz Git del cliente) y, al instalar, el **nombre/código real del cliente**, si el repositorio es nuevo o ya tiene personalizaciones, y la ubicación de los tres `AGENTS.md` de aplicación. Los campos muestran valores sugeridos entre corchetes, que podés aceptar con Enter.
5. Revisa la **previsualización** (sin escrituras). Para instalar ingresá `INSTALAR`; para actualizar `ACTUALIZAR`. De lo contrario, cancela.
6. Revisa el `git status`, la validación y cualquier conflicto en `.g3forge/pending/`. Los commits/pushes son manuales, luego de revisar el diff.

Para actualizar también funciona el nombre de la versión anterior:

```powershell
.\tools\Actualizar-G3Forge.ps1
```

Ambos scripts usan el mismo asistente. **No contienen rutas de un cliente determinado**. Si tu proyecto usa `autogestion/src/`, generalmente el `AGENTS.md` se sitúa en `autogestion/`: lo que solicita el asistente es la ubicación de las instrucciones, no el directorio del código legacy.

### Controles importantes

- Si la ruta no es raíz Git, no continúa. Para instalaciones nuevas, puede crear una carpeta e inicializar Git **solo con confirmación específica**.
- Para adoptar/actualizar exige árbol Git limpio.
- Si hay archivos administrados distintos, no los pisa: deja propuestas `.incoming` y la instalación queda parcial hasta conciliación.
- No crea repositorios remotos ni se conecta a servidores. Solo instala o actualiza las reglas de desarrollo y herramientas de G3Forge.
- Las trazas locales se consultan desde el menú, pero Codex debe registrarlas explícitamente con `scripts/trazabilidad.py`; no existe captura automática.

---

## Procedimientos CLI avanzados (opcional)

> **Actualización directa con el asistente:** desde la distribución ejecutá `.\G3Forge.ps1 -Accion actualizar`. El asistente pregunta las rutas, detecta la versión instalada, previsualiza el cambio y exige `ACTUALIZAR`. No vuelvas a ejecutar `inicializado.md` por una actualización rutinaria.

## A. Instalar desde cero — repositorio Git nuevo

### Paso 1 — Crear un repositorio vacío

Creá en GitHub un repositorio **privado**, por ejemplo `Newsol-Group/repo-cliente`. Clonalo en tu equipo:

```powershell
git clone https://github.com/Newsol-Group/repo-cliente.git C:\Proyectos\repo-cliente
```

Si GitHub no tiene todavía el repositorio o preferís crearlo localmente:

```powershell
mkdir C:\Proyectos\repo-cliente
cd C:\Proyectos\repo-cliente
git init -b main
```

El instalador necesita que la carpeta ya sea una **raíz de repositorio Git**. No hace commits, pushes ni crea repositorios remotos.

### Paso 2 — Previsualizar la instalación (sin escribir)

```powershell
py -3 C:\Herramientas\g3forge-ai\tools\g3forge.py instalar --repo C:\Proyectos\repo-cliente --fuente C:\Herramientas\g3forge-ai --modo nuevo --cliente "Nombre real del cliente" --codigo codigo-cliente --previsualizar
```

Revisá las rutas y comprobá que no aparezca ningún `CONFLICTO`.

### Paso 3 — Instalar

```powershell
py -3 C:\Herramientas\g3forge-ai\tools\g3forge.py instalar --repo C:\Proyectos\repo-cliente --fuente C:\Herramientas\g3forge-ai --modo nuevo --cliente "Nombre real del cliente" --codigo codigo-cliente
```

Esto crea los `AGENTS.md` (universal y de las tres aplicaciones), `.codex/`, `.agents/skills/`, `scripts/`, las plantillas documentales, `config/cliente.toml` y `.g3forge/manifest.json`. No copia código Guaraní y no llama a ninguna base.

### Paso 4 — Verificar

```powershell
py -3 C:\Proyectos\repo-cliente\scripts\g3forge.py verificar --repo C:\Proyectos\repo-cliente
cd C:\Proyectos\repo-cliente
py -3 scripts\validar_framework.py --cliente
git status
```

### Paso 5 — Revisar y confirmar la instalación en Git

Antes de importar código fuente o iniciar relevamientos, revisá los archivos agregados, los hashes del manifiesto y las exclusiones. Confirmá manualmente **solo los archivos de G3Forge**:

```powershell
cd C:\Proyectos\repo-cliente
git status
git diff --check
git add AGENTS.md inicializado.md .codex .agents config docs gestion/AGENTS.md autogestion/AGENTS.md preinscripcion/AGENTS.md scripts .g3forge .gitignore
git commit -m "Instalar framework G3Forge"
```

Si el repositorio contiene archivos distintos en alguna de esas rutas, revisá el listado antes de agregarlo. Este commit crea una **base limpia** para la auditoría de la inicialización. `git push -u origin main` es opcional y siempre manual, con autorización y remoto configurado.

### Paso 6 — Incorporar las fuentes autorizadas

Copiá las fuentes reales en las rutas nativas de cada aplicación, si corresponde. No muevas ni reestructures el código SIU solo para satisfacer el esquema sugerido. Las rutas y versiones no se infieren de los `AGENTS.md`.

Ajustá `config/cliente.toml` con los datos observados. `presente=true` se marca **solo cuando se comprobó código real local**. Si las aplicaciones viven en rutas anidadas, indicá las rutas **en el momento de instalar** con `--ruta-gestion`, `--ruta-autogestion` y `--ruta-preinscripcion`; el manifiesto no reubica apps automáticamente.

Antes de ejecutar la inicialización, verificá `git status`. Si incorporaste fuentes y está autorizado versionarlas, confirmá su baseline manualmente; **no** agregues a Git credenciales, datos de alumnos o fuentes que no estés autorizado a almacenar. Si las fuentes permanecen sin confirmar, el inicializador deberá distinguir esos cambios preexistentes de los propios y podrá limitar sus conclusiones.

### Paso 7 — Inicializar el conocimiento del cliente

Abrí la raíz del repositorio en VS Code, con Codex y un modelo compatible. Indicá:

> Leé `inicializado.md` y ejecutalo.

Astra debe limitarse al análisis estático y a la documentación comprobada; **no** debe tocar código funcional ni conectarse a BD. Si el Codex disponible no admite algún modelo o función multiagente, corregí esa compatibilidad antes de aceptar que la orquestación funciona.

### Paso 8 — Revisar documentación generada y registrar su estado

Revisá `git diff`, el índice documental y `docs/ESTADO.md`. Solo cuando las observaciones sean fieles a la copia real, confirmá los documentos modificados manualmente. Un commit no equivale a despliegue en un servidor.

## B. Instalar sobre repositorio existente

### Paso 1 — Resguardar el trabajo y verificar Git

Abrí la raíz real del repositorio y comprobá:

```powershell
cd C:\Proyectos\guarani-cliente-existente
git status
git branch --show-current
```

**La instalación exige árbol de trabajo limpio.** Completá/resguardá cambios propios antes de continuar. No exige que todas las aplicaciones existan.

### Paso 2 — Identificar las rutas reales

Ejemplo: Gestión está en `gestion/`, Autogestión en `apps/g3w3/` y Preinscripción todavía no se incorporó. Aun así, el instalador crea sus instrucciones en `preinscripcion/`, sin generar fuentes ni suponer que está instalada.

### Paso 3 — Previsualizar (sin modificar)

```powershell
py -3 C:\Herramientas\g3forge-ai\tools\g3forge.py instalar --repo C:\Proyectos\guarani-cliente-existente --fuente C:\Herramientas\g3forge-ai --modo existente --cliente "Cliente Existente" --codigo cliente-existente --ruta-gestion gestion --ruta-autogestion apps/g3w3 --ruta-preinscripcion preinscripcion --previsualizar
```

### Paso 4 — Instalar sin sobrescribir archivos propios

Ejecutá el mismo comando sin `--previsualizar`. El instalador:

- **Conserva** los textos preexistentes de `AGENTS.md`, agregando instrucciones G3Forge en un bloque `<!-- G3FORGE:BEGIN:... -->` / `<!-- G3FORGE:END:... -->`.
- **Nunca pisa** `config/cliente.toml`, `docs/PROYECTO.md`, `docs/ESTADO.md` o `docs/personalizaciones/INDICE.md` existentes.
- **Nunca pisa** archivos coincidentes por ruta si tienen contenido distinto (`.codex/config.toml`, skills, scripts, etc.). Crea una propuesta `.g3forge/pending/<ruta>.incoming` y marca **instalación parcial**.
- Extiende `.gitignore` sin eliminar las exclusiones previas.
- No toca fuentes originales SIU, `personalizacion/`, `pers/`, autoloads o metadatos.

**Importante:** si aparece un `CONFLICTO` en `.codex/config.toml`, la orquestación aún no queda configurada. No afirmes que la instalación terminó hasta resolverlo.

### Paso 5 — Conciliar conflictos (si existen)

Compará el archivo actual con `.g3forge/pending/<ruta>.incoming` en el editor de diferencias de VS Code. Incorporá manualmente los cambios necesarios en el archivo destino y conservá el contenido propio del cliente.

Para un conflicto de `AGENTS.md`, **no elimines los marcadores G3Forge**; conservá su contenido administrado y agregá las reglas del cliente *fuera* del bloque. Para `.codex/config.toml`, el resultado debe ser TOML válido, con Astra `xhigh` y los agentes habilitados. Una configuración MCP que conecte a BD no es compatible con V2.

Una vez conciliado y revisado el archivo, reconocé la resolución:

```powershell
py -3 scripts\g3forge.py resolver --repo . --ruta .codex/config.toml --acepto-integracion
```

Repetí con los otros conflictos. **`resolver` no mezcla ni modifica el archivo destino**: solo registra que ya hiciste la integración manual. El flag representa una confirmación explícita del desarrollador.

### Paso 6 — Validar y registrar en Git

```powershell
py -3 scripts\g3forge.py verificar --repo .
py -3 scripts\validar_framework.py --cliente
git status
git diff --check
```

Revisá los archivos resultantes y confirmá manualmente el punto de partida de G3Forge en Git. **Después**, ejecutá `inicializado.md` con Codex tras verificar permisos y código real. Volvé a revisar/confirmar la documentación generada en un commit separado si resulta relevante. Ningún push es automático.

## C. Actualizar un cliente a una distribución posterior

1. Descargá/descomprimí la **nueva** versión del framework en una carpeta separada. Comprobá la versión leyendo `VERSION`.
2. En el repo del cliente, confirmá que `git status` esté limpio. No se actualizan archivos con trabajo sin confirmar.
3. Previsualizá:

```powershell
py -3 C:\Herramientas\g3forge-ai-NUEVO\tools\g3forge.py actualizar --repo C:\Proyectos\guarani-cliente-existente --fuente C:\Herramientas\g3forge-ai-NUEVO --previsualizar
```

4. Cuando estés de acuerdo, ejecutá sin `--previsualizar`.
5. Si un archivo administrado no cambió localmente, el actualizador trae la revisión nueva. Si un bloque `AGENTS.md` contiene personalizaciones *fuera* de marcadores, las preserva. Si el propio bloque o archivo completo fue modificado, **no lo pisa**: deja conflicto pendiente.
6. Conciliá las propuestas `.incoming` con VS Code y utilizá `resolver` únicamente después de integrar los cambios.
7. Ejecutá `verificar`, `validar_framework.py` y las pruebas pertinentes. Revisá el diff y confirmá el cambio en Git manualmente.

El manifiesto `.g3forge/manifest.json` registra versión objetivo, versión completamente instalada, archivos administrados con SHA-256 y conflictos. Las bases en `.g3forge/baseline/` permiten identificar cambios locales; esas bases **sí se versionan en Git**. Las propuestas y reportes temporales se excluyen con `.gitignore`.

## D. Linux/macOS (equivalente)

```bash
python3 /opt/g3forge-ai/tools/g3forge.py instalar --repo "$HOME/proyectos/guarani-cliente" --fuente /opt/g3forge-ai --modo nuevo --cliente "Nombre real del cliente" --codigo codigo-cliente --previsualizar
python3 /opt/g3forge-ai/tools/g3forge.py instalar --repo "$HOME/proyectos/guarani-cliente" --fuente /opt/g3forge-ai --modo nuevo --cliente "Nombre real del cliente" --codigo codigo-cliente
python3 "$HOME/proyectos/guarani-cliente/scripts/g3forge.py" verificar --repo "$HOME/proyectos/guarani-cliente"
```

## E. Problemas frecuentes

- **Git no disponible / no es raíz Git:** ejecutá `git init -b main` o abrí la carpeta correcta.
- **Trabajo no limpio:** hacé commit, guardá una copia o usá Git stash *manualmente*, sin perder archivos.
- **Conflictos y código de salida 2:** instalación parcialmente completada; revisar `.g3forge/pending/`, conciliar y resolver.
- **Error por archivo preexistente:** G3Forge no lo sobrescribe. El archivo del cliente prevalece hasta revisión.
- **Modelo no disponible:** las configuraciones son declarativas; necesitan una versión de Codex con soporte del modelo y subagentes. No sustituir silenciosamente modelos.
- **No hay fuentes reales:** `AGENTS.md` existe como plantilla, pero el inicializador debe registrar «no observado».
- **Cambio de rutas de aplicaciones después de instalar:** el administrador no mueve carpetas SIU. Requiere una migración de rutas revisada, fuera del comando de actualización.

**Antecedentes de validación:** pruebas locales Python/Git, actualización desde el ZIP real 2.1.2 con checkout `core.autocrlf=true`, y aceptación multiagente 2.1.1 en UB. La evidencia de esta retirada está en `docs/trabajos/RETIRADA-ENTREGAS.md` de la distribución central, que no se instala en clientes; no acredita adopción ni aceptación funcional SIU.

## Acceso directo a la actualización en Windows

Desde la distribución elegida, en una carpeta separada del repositorio, ejecutá:

```powershell
.\G3Forge.ps1 -Accion actualizar
```

El asistente pide **ambas rutas**, verifica la versión anterior, previsualiza sin escribir y exige el texto `ACTUALIZAR`. Conserva las instrucciones y el código del cliente; cualquier conflicto queda pendiente en `.g3forge/pending/`.

También se puede usar el nombre de archivo de la versión anterior:

```powershell
.\tools\Actualizar-G3Forge.ps1
```

Ambos siguen el mismo procedimiento interactivo. Tras la actualización, verificar `git status`, revisar el diff y confirmar los cambios manualmente. **No vuelvas a ejecutar `inicializado.md` por una actualización rutinaria.**

Para trazabilidad local de los agentes, ver [TRAZABILIDAD.md](../arquitectura/TRAZABILIDAD.md).

## Reglas de estabilización 2.1.3

- G3Forge genera/actualiza un bloque bajo `.gitattributes` que configura `-text` **solo** para los archivos administrados por el framework y `.g3forge/baseline/**`. No cambia el tratamiento de fin de línea de código SIU.
- En instalaciones 2.1.2 puede encontrarse un baseline convertido a CRLF. El CLI lo reconoce **únicamente** cuando convertir CRLF a LF devuelve exactamente el SHA-256 almacenado; al actualizar restituye la base original y mantiene advertencia de recuperación. Cambios de texto reales no se aceptan como equivalencia.
- Antes de aplicar cambios a una instalación existente, el script exige un árbol Git limpio. Si hay conflictos, muestra `.incoming` sin sobreescribir el archivo modificado.
- El asistente PowerShell 2.1.3 presenta un menú recurrente; puede hacer verificaciones y consultar trazas sin reiniciarse. Las rutas ingresadas se recuerdan solo durante esa sesión, **no se guardan en el perfil de Windows**.
- La política de atributos del framework se verifica con SHA-256 propio. Cualquier modificación de su bloque obliga a revisión, sin borrar reglas del cliente fuera de él.
- El CLI rechaza rutas con nombres reservados de Windows, rutas de aplicación superpuestas o versiones inferiores.
- Ninguna acción transfiere archivos al servidor, ejecuta SQL, genera autoloads o modifica metadatos Toba.

## Consola G3Forge AI y recursos heredados

Desde la carpeta de la distribución, ejecutá `.\G3Forge.ps1` para abrir la consola. La instalación global es opcional y se realiza con `.\Instalar-CLI.ps1`; tras configurarla, podés abrir el CLI con `g3forge`.

Para actualizar un workspace, primero revisá la previsualización, las propuestas y los conflictos; luego el desarrollador puede decidir si confirma la actualización. No se ejecutó una actualización real como parte de esta documentación.

La actualización neutraliza los seis recursos operativos heredados identificados por el instalador mediante avisos informativos. Conserva los datos existentes, genera conflicto si un recurso tiene edición local y no instala esos avisos en workspaces nuevos. Para resolver un retirado, el destino admite equivalencia LF/CRLF respecto del contenido informativo esperado; la propuesta incoming se valida por coincidencia exacta de bytes. Los cambios distintos requieren conciliación manual.

G3Forge AI no administra catálogo operativo, versionado PERS, paquetes de entrega ni despliegues. Las referencias históricas de la guía describen versiones anteriores.

Las copias centrales o workspaces que no se actualicen conservan su comportamiento anterior. Actualizar el framework en un workspace no actualiza automáticamente el CLI global ni otra distribución.
