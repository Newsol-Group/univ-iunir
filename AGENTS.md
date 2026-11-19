<!-- G3FORGE:BEGIN:AGENTS.md -->
# G3Forge AI · Contrato raíz (V2.2)

> El bloque delimitado por `G3FORGE:BEGIN`/`G3FORGE:END` es administrado por el actualizador: **no editarlo directamente**. Las reglas específicas del cliente se escriben fuera de él. Aplicar también el `AGENTS.md` específico de la aplicación afectada. Leer `config/cliente.toml` y `docs/PROYECTO.md` solo cuando la tarea dependa del cliente o la estructura observada; no cargar indiscriminadamente todos los documentos.

## Misión
Ayudar a desarrollar y mantener personalizaciones SIU Guaraní, y desarrollar, revisar, documentar y gestionar el propio G3Forge AI con precisión. La operación de entregas y ambientes corresponde a G3Forge Deploy, proyecto independiente; no invocar sus herramientas o procesos directa ni indirectamente desde AI. Evitar burocracia, archivos de relleno y soluciones no sustentadas. El orquestador principal es **GPT-6 Astra con `xhigh`**; delegar cuando aporte calidad o velocidad, no por defecto.

## Invariantes innegociables
- **V2 sin acceso a bases de datos**: prohibido conectar o realizar cualquier consulta/actualización, incluidos `SELECT`, herramientas externas, drivers, conectores, scripts o CLI. Se permite leer y revisar SQL **como texto**, sin ejecutarlo.
- **Toba y autoloads manuales**: no crear, editar, eliminar, importar, exportar, regenerar o modificar indirectamente metadatos Toba ni archivos/autogeneración de autoload. No ejecutar comandos que los generen. Documentar los pasos para el desarrollador.
- **Legacy solo lectura**: no modificar fuentes originales SIU (`php/` en Gestión, `siu/` en G3W3/Preinscripción), ni `pers_gestion/` derivado. Editar solo las áreas de personalización comprobadas para el proyecto.
- Nunca copiar secretos, datos personales ni dumps a Git, paquetes, respuestas o documentación.
- No operar entregas ni ambientes, ni invocar G3Forge Deploy directa o indirectamente. No ejecutar SQL, cambiar servidores, hacer push/merge/commit ni destruir archivos sin autorización explícita. Un pedido de implementación no equivale a un permiso de despliegue.
- No inventar cliente, versión, APIs, IDs de Toba, rutas, estado de ambientes o pruebas exitosas.

## Inicio de trabajo
1. Detectar **fuentes reales** de la aplicación; carpetas `AGENTS.md` no prueban instalación. Revisar rama, `git status` y trabajo local ajeno antes de editar.
2. Leer instrucciones de las aplicaciones involucradas, el código real y documentos de proyecto/estado estrictamente relevantes. Buscar primero un patrón análogo y comprobar símbolos y firmas.
3. Delimitar objetivo, archivos permitidos, restricciones, riesgo y criterio verificable de éxito. Para tareas de alto riesgo, Astra planifica y resuelve la parte crítica.

## Orquestación por Astra
- **Riesgo bajo**: documentación puntual, exploración acotada, cambios cosméticos; Astra puede resolver o delegar a Luna/Sol.
- **Riesgo medio**: cambios funcionales locales, lógica en varias clases; delegar implementación a Sol, solicitar verificación independiente según impacto.
- **Riesgo alto**: identidad, inscripciones, estado de alumnos, seguridad, transacciones, migraciones, integraciones, varias aplicaciones o ambigüedad grave; Astra debe analizar directamente antes de delegar tareas delimitadas.
- Astra puede iniciar hasta **tres subagentes simultáneos** cuando existan frentes realmente independientes. No permitir escritores concurrentes sobre el mismo archivo o estado compartido. Asignar archivos y resultados esperados por tarea.
- Permitir a un subagente **un único reintento fundamentado** tras un fallo; escalar de inmediato por incertidumbre crítica, contradicción, permisos o repetición. Nada de bucles de ensayo y error.
- Un bloqueo devuelto al orquestador incluye: hipótesis, archivos y símbolos, evidencia, intentos, cambios, pruebas y motivo preciso del bloqueo. Usar `docs/arquitectura/HANDOFF.md`.
- Astra consolida resultados, revisa cambios permitidos, distingue pruebas hechas de no hechas, decide aceptación técnica y comunica pendientes.
- Los roles implementan una **política de trabajo**; las instrucciones no garantizan aislamiento técnico. Aplicar verificaciones de Git y sandbox del anfitrión.


## Delegación confiable y herramientas (2.1.1)
- **Preferir `fork_turns: "none"`** al crear un subagente para una tarea autocontenida, *si el runtime admite ese parámetro y conserva las herramientas requeridas*. Es una preferencia comprobada en el piloto UB, **no** una garantía universal ni una clave de `.codex/config.toml`.
- Al usar `none`, el subagente **no hereda la conversación**: Astra debe entregar en el mensaje de creación el objetivo, aplicación, raíz/rutas reales, instrucciones `AGENTS.md` y skills a consultar, restricciones, criterio de éxito y formato de respuesta. No pasar grandes volcados de código; habilitar lectura autónoma si existen herramientas.
- En la primera delegación de una sesión nueva o tras cambiar Codex, verificar con un archivo **no sensible del alcance** que el subagente dispone de una herramienta real de lectura y puede usarla. No repetir la prueba en cada tarea una vez constatada la capacidad. Usar `scripts/diagnostico_multiagente.py` para preparar la comprobación; el script local **no inicia modelos**.
- Si faltan herramientas, registrar cuáles se observaron y el error; permitir **un único reintento con una modalidad compatible y segura**, solo si hay un motivo técnico. La herencia de herramientas dinámicas puede variar según `fork_turns`. No repetir sin cambios ni otorgar permisos adicionales por comodidad. Si persiste, Astra asume o informa el bloqueo.
- Mantener separados **modelo configurado** y **modelo efectivo comprobado**: no dar por verificadas las credenciales/modelo de un subagente solo porque el TOML dice Luna o Sol. No presentar un análisis hecho por Astra a partir de extractos como lectura autónoma del subagente.
- Aplicar el contrato breve `docs/arquitectura/HANDOFF.md` al devolver hallazgos y bloqueos. Astra coteja contradicciones con referencias a código; por ejemplo, nombres de tablas diferentes **no demuestran equivalencia** ni permiten consultar BD en V2.
- Evitar delegaciones redundantes: tareas cortas o secuenciales se resuelven en la sesión principal; paralelismo solo para frentes independientes y con propiedad exclusiva de archivos si hay escritura.

## Desarrollo y validación
- Edición mínima justificada; respetar namespace, mecanismos de consulta/actualización SIU y compatibilidad concreta.
- Usar `.agents/skills/validar-personalizacion/SKILL.md` ante modificación relevante. Ejecutar solamente comprobaciones locales, estáticas y compatibles. Nada de conexiones BD, generación de autoloads/Toba ni servidores.
- No afirmar aceptación funcional por pasar `php -l`. Anotar «no ejecutado» si no existen medios locales seguros.
- Para Toba, aplicar `.agents/skills/gestion-toba/SKILL.md`; para errores G3W3 complejos, `.agents/skills/diagnostico-g3w3/SKILL.md`.
- Antes de entregar, contrastar el diff con el alcance autorizado y verificar que no se tocaron archivos protegidos. En repositorios cliente, ejecutar `scripts/auditar_cambios.py --modo trabajo` si Git está disponible, además de inspeccionar el diff; solo comprueba rutas sin confirmar. `scripts/validar_framework.py` revisa la plantilla, **no** reemplaza la revisión del diff de un cliente.

## Trazabilidad de ejecuciones relevantes (2.1.2)
- Para QA o desarrollos **multiagente relevantes**, crear un registro local con `scripts/trazabilidad.py iniciar` antes de delegar; agregar el resultado **reportado** de cada agente con `anotar` y cerrar con `cerrar`. No abrir registros por tareas triviales ni exigir intervención del usuario.
- El registro vive en `.g3forge/ejecuciones/` (ignorado por Git). No copiar conversaciones completas, claves, datos de alumnos, SQL con información real ni código fuente a esos registros. Exportar una evidencia resumida a `docs/g3forge/` **solo por solicitud expresa**.
- Registrar `modelo_configurado` y dejar el modelo **efectivo desconocido** salvo que exista evidencia identificable. Una afirmación del agente o el archivo TOML no demuestra cuál ejecutó la sesión.
- Diferenciar `solo_declaracion` de `referencia_con_hash_no_auditoria`. El hash de un archivo no certifica que Codex usó una herramienta o que ejecutó un modelo concreto.
- Si el CLI no está disponible, no inventar un registro. Explicar la limitación. Leer `docs/arquitectura/TRAZABILIDAD.md` cuando se necesite rastrear un trabajo importante.

## Documentación y continuidad
- `docs/ESTADO.md`: estado real y próximos pasos con evidencia, por aplicación/ambiente; no deducir despliegues de Git.
- `docs/personalizaciones/`: conocimiento estable agrupado por función, no por archivo.
- `docs/trabajos/<ID>.md`: solo para trabajo relevante que necesite continuidad; nunca para ediciones triviales. Actualizar el trabajo existente en vez de duplicarlo.
- El desarrollo de G3Forge AI no administra entregas ni despliega personalizaciones; las instrucciones operativas de G3Forge Deploy pertenecen a ese proyecto independiente.
- La documentación la mantiene Codex durante el trabajo. No pedir planillas ni emitir informes repetitivos; la escritura documental se consolida al finalizar para evitar conflictos.
- Para primera inicialización y relevamiento preexistente usar **únicamente** `inicializado.md`: está prohibido alterar código funcional durante ese proceso.

## Git y ambientes
Una sola rama permanente `main`; ramas cortas cuando faciliten aislamiento. Las ramas no equivalen a Desarrollo/Testing/Producción. Conservar todos los cambios ajenos. Preparar no es instalar, copiar no es probar y un commit no acredita un despliegue.

## Reutilización y actualización
G3Forge AI se incorpora a un workspace desde `G3Forge.ps1`; la instalación global opcional del CLI usa `Instalar-CLI.ps1`. Para actualizar un workspace, ejecutar primero la previsualización y revisar sus propuestas y conflictos; no ejecutar actualizaciones reales durante tareas funcionales. Los recursos operativos heredados pueden ser neutralizados por el actualizador mediante stubs informativos: los datos existentes se conservan, una edición local produce conflicto y los stubs no se instalan en workspaces nuevos. La existencia de ese mecanismo no significa que una actualización se haya ejecutado.
<!-- G3FORGE:END:AGENTS.md -->
