<!-- G3FORGE:BEGIN:gestion/AGENTS.md -->
# G3Forge · Especialización Gestión SIU Guaraní

Aplicar `../AGENTS.md`. **Esta carpeta y este AGENTS no prueban la presencia de Gestión**: detectar código real y versión antes de intervenir.

## Capacidades y área de trabajo
- Personalizaciones en `personalizacion/` (o ruta equivalente comprobada). `php/` original SIU es referencia **solo lectura**.
- Investigar operaciones, CI, formularios, cuadros, eventos, permisos, componentes, CO y clases de actualización. Reutilizar arquitectura y estilo de la versión observada.
- Trazar referencias reales de cada operación antes de modificarla; no inventar IDs, nombres de API o menús de TobaEditor.

## Restricciones obligatorias
- **Prohibido** crear, editar o regenerar metadatos Toba y autoloads, incluso con SQL, scripts, generadores o exportaciones indirectas. Leerlos para diagnóstico sí está permitido.
- Ante operaciones que requieren TobaEditor, preparar una guía humana verificable con componente, pasos, propiedades, código a vincular, dependencias, resultado esperado y pendientes no demostrados.
- Usar invocación SIU apropiada (por ejemplo `toba::consulta_php(...)->metodo(...)` y `guarani::act(...)->metodo(...)` **solo cuando corresponda y exista**). No `require`/`include` directos de CO/ACT como atajo.
- Prohibido conectar a PostgreSQL o ejecutar consultas/SQL; solo lectura estática de SQL o definiciones en archivos.

## Diagnóstico y desarrollo
1. Identificar versión, operación, CI y flujo de eventos/metadatos observable; comparar con una operación equivalente.
2. Separar causa de configuración Toba, vinculación, autoload, PHP, negocio y permisos. No cambiar PHP como parche de un error de metadatos.
3. Mapear clases, llamadas, consumidores y tablas **desde texto y código**, sin consultar la base. Para lógica compartida o datos críticos, pedir análisis de Astra.
4. Modificar solo código permitido; indicar explícitamente qué debe hacer la persona en TobaEditor y cuándo generar autoloads manualmente.
5. Validar con comprobaciones estáticas disponibles y reportar pruebas funcionales pendientes.

## Escalamiento a Astra
Intervenir ante alta complejidad de componentes, contratos transversales, inscripciones, identidad, transacciones, integraciones, diferencias de versión o dos hipótesis sin evidencia suficiente. Escalar tras un reintento fundamentado fallido. El especialista Toba investiga y prepara pasos: **no manipula metadatos**.

## Cierre
Activar `../.agents/skills/gestion-toba/SKILL.md` si aplica y la skill `validar-personalizacion`. Registrar acciones manuales concretas sin duplicar documentos. No declarar instalado lo que requiere TobaEditor o autoload humano.
<!-- G3FORGE:END:gestion/AGENTS.md -->
