# Inicialización documental del workspace

> Instrucción del desarrollador para un cliente: **«Leé `inicializado.md` y ejecutalo».** Ejecutá el reconocimiento y la documentación verificable sobre la copia local abierta, sin pedir otra orden de ejecución. No alteres código funcional ni inventes datos.

## Contrato no negociable

Leer `AGENTS.md`, `config/cliente.toml`, `docs/PROYECTO.md`, `docs/ESTADO.md`, instrucciones de aplicaciones con fuentes realmente presentes y skills relevantes. Respetar las prohibiciones de V2: **nunca conectar a BD (ni SELECT), ejecutar SQL, generar/editar metadatos Toba o autoloads, modificar fuentes, instalar dependencias, generar artefactos SIU, acceder a servidores, hacer commits/push/merge ni desplegar**. Durante la inicialización solamente editar documentación, instrucciones y configuración de cliente **cuando existan datos demostrados**.

La presencia por defecto de `gestion/AGENTS.md`, `autogestion/AGENTS.md` y `preinscripcion/AGENTS.md` **no demuestra que esas aplicaciones estén instaladas**. No crear carpetas de código ni clases para completar una estructura ideal.

## Fase 0 · Reconocimiento de la copia

1. Leer rama, commit, `git status` y cambios locales, sin pisar trabajo preexistente.
2. Reconocer Gestión, Autogestión y Preinscripción **solo por fuentes reales**, no por AGENTS ni nombres de carpetas. Identificar rutas y namespace propios.
3. Determinar versiones de Guaraní, Toba, PHP y dependencias únicamente si hay evidencia documental o en archivos; si no, «desconocida».
4. Si el usuario reporta una copia de Producción/Testing, registrar **procedencia declarada**, fecha/alcance conocidos y exclusiones; no presentar la copia como prueba de estado actual del servidor.
5. Identificar rutas potencialmente sensibles sin leer ni exponer contenidos; documentar si necesitan exclusión de Git. No agregar secretos o datos personales.

## Fase 1 · Configuración e instrucciones por cliente

1. Actualizar `config/cliente.toml` con nombre/código si están informados; marcar `presente=true` **solo** para aplicación con código real observado. Registrar versiones/rutas comprobadas y conservar «desconocida» donde corresponda.
2. Mantener las reglas del cliente fuera de los bloques `<!-- G3FORGE:BEGIN:... -->` y `<!-- G3FORGE:END:... -->` de los `AGENTS.md`. **No modificar bloques administrados por G3Forge**, para permitir actualizaciones seguras. Consultar convenciones y patrones reales; registrar particularidades verificadas en documentación local o en texto fuera del bloque. No personalizar el bloque universal con datos del cliente.
3. Actualizar `docs/PROYECTO.md` con mapa real y `docs/ESTADO.md` solo con observaciones comprobadas, estado inicial y próximo paso. No deducir despliegues de Git ni de capturas parciales.
4. Mantener skills reutilizables y el núcleo Codex. No agregar conectores, credenciales o acceso a bases de datos.

## Fase 2 · Relevamiento estático de personalizaciones

Usar `.agents/skills/relevar-personalizaciones/SKILL.md`. Astra puede delegar lectura de Gestión, Autogestión y Preinscripción a exploradores **si los frentes son independientes**, con hasta tres subagentes; **la consolidación documental es de Astra** para evitar IDs repetidos.

- Inspeccionar de forma completa o parcial áreas personalizadas efectivamente presentes. Leer legacy/Toba/catálogos exclusivamente como referencia.
- Seguir operaciones, rutas, clases, consultas/actualizaciones SQL **desde código sin ejecutarlas**, pagelets, integraciones y dependencias.
- Una ficha informativa `PERS-###` por **funcionalidad**, incluso transversal; no por cada archivo. Reutilizar IDs existentes.
- Escribir `docs/personalizaciones/INDICE.md` y fichas documentales; registrar evidencia, rutas/símbolos y cobertura completa/parcial/no leída. No inventar historias, validaciones, aceptaciones ni despliegues.
- No convertir personalizaciones previas en trabajos nuevos o hallazgos obligatorios por el solo inventario. No modificar funcionalidad existente para alinearla con nuevas reglas.

## Fase 3 · Control estático y cierre

1. Ejecutar `python3 scripts/g3forge.py verificar --repo .` y `python3 scripts/validar_framework.py --cliente` si Python 3.11+ está disponible; con Git disponible, ejecutar `python3 scripts/auditar_cambios.py --modo inicializacion` y revisar cualquier cambio ajeno preexistente antes de interpretarlo como incumplimiento. No instalar dependencias.
2. Comparar `git status` inicial/final. La inicialización solo puede haber actualizado documentación, instrucciones por cliente y `config/cliente.toml`; no alterar ninguna fuente funcional, metadatos, autoloads o código SIU.
3. Confirmar referencias, IDs, cobertura, versiones y caminos; las observaciones dudosas quedan «desconocidas».
4. Responder con resumen corto: aplicaciones con fuentes, versiones confirmadas, personalizaciones agrupadas, cobertura, archivos documentales cambiados, limitaciones y **próximo sector a leer** si quedó trabajo pendiente.

## Reanudación e idempotencia

Si la lectura excede el contexto disponible, guardar avance en documentos existentes antes de cerrar, con cobertura y sectores pendientes. Al volver a decir «Leé `inicializado.md` y ejecutalo», continuar exactamente desde las áreas incompletas. No duplicar fichas, IDs ni documentación.

Si no hay fuentes: completar únicamente lo que pueda comprobarse en la plantilla, registrar «sin código observado» y finalizar. No inventar estructura, versión o ausencia en servidores.

Los índices y fichas documentales de personalizaciones que ya existan forman parte de la documentación funcional del workspace: conservar sus identificadores y contenido, y actualizarlos solo con evidencia verificada. No crear ni mantener desde G3Forge AI un catálogo operativo, versiones de personalizaciones o paquetes de entrega.
