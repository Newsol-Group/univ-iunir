<!-- G3FORGE:BEGIN:preinscripcion/AGENTS.md -->
# G3Forge · Especialización Preinscripción

Aplicar `../AGENTS.md`. **Esta carpeta existe por defecto incluso si el cliente no utiliza Preinscripción**. No deducir instalación, esquema ni versión por su presencia. Detectar primero fuentes auténticas.

## Capacidades
- Cuando exista código, investigar controladores, formularios, validaciones, identidad, estado de trámite, persistencia e integración con Guaraní Gestión/Autogestión.
- Personalizar en `pers/` (o área verificada); `siu/` y cualquier fuente original o derivada son **solo lectura**.
- Reconstruir estados y transiciones desde código, reglas conocidas y evidencia estática, sin asumir funcionamiento del servidor.

## Contratos y restricciones
- Evitar duplicaciones de persona, documento, email, trámite o inscripción mediante análisis de lógica **en archivos**, no por consultas a BD.
- No alterar semántica de estados, permisos, identidad ni recuperación de trámites sin analizar efectos cruzados. Comprobar mecanismos reales de autenticación y validación.
- No asumir que las APIs o convenciones de Preinscripción sean las mismas que Gestión o G3W3; verificar implementaciones existentes.
- Prohibidas todas las conexiones y consultas a bases de datos, ejecución de SQL, cambios en legacy y despliegues no autorizados.

## Diagnóstico y validación
1. Identificar fuente, versión y flujo real involucrado.
2. Rastrear punto de entrada → validación → estado → persistencia → intercambio con Gestión, solo a través de código y documentación.
3. Delimitar posibles inconsistencias y casos límite antes de editar.
4. Aplicar modificación mínima a personalizaciones permitidas; verificar consumidores y regresiones mediante pruebas locales inocuas, si están disponibles.
5. Diferenciar código existente, hipótesis y estado comprobado del servidor.

## Escalamiento a Astra
Obligatorio para transiciones de preinscripto a alumno, identidad compartida, validaciones cruzadas, relaciones de tablas críticas y cualquier caso ambiguo que pueda crear inconsistencias. Sin improvisaciones sobre SQL ni datos reales.

## Cierre
La documentación se agrega cuando existen fuentes y observaciones reales; jamás fabricar estructura SIU, operaciones, versiones ni inventarios para rellenar el template.
<!-- G3FORGE:END:preinscripcion/AGENTS.md -->
