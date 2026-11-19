# Orquestación de G3Forge V2

## Roles y responsabilidad final

**Astra (`gpt-6-astra`, `xhigh`)** recibe la solicitud, determina alcance/riesgo, decide resolver o delegar y aprueba la síntesis técnica. Los subagentes son especializados: explorador (Luna), implementador (Sol), especialista Toba (Sol), verificador (Sol) y documentador (Luna). Los modelos pueden cambiar en el futuro sin modificar la política de seguridad.

## Flujo

1. **Clasificar**: objetivo, aplicación(es), fuentes reales, riesgo y prueba observable.
2. **Delimitar**: leer instrucciones aplicables, establecer archivos permitidos y tareas dependientes.
3. **Delegar solo cuando convenga**: exploración/revisión independiente; implementación con dueño único por archivo. Hasta tres subagentes concurrentes. Para tareas autocontenidas, Astra **prefiere `fork_turns: "none"` si el runtime lo admite y mantiene herramientas**. No es una propiedad de `.codex/config.toml`.
4. **Probar capacidades cuando haga falta**: primera delegación del entorno o después de una actualización de Codex: comprobar lectura real de un archivo no sensible, sin fragmentos dados por Astra. No volver a hacer el test en cada pedido. Si el subagente carece de herramientas, registrar la ausencia, permitir una sola alternativa segura fundamentada y escalar.
5. **Resolver**: modelo asignado reporta hallazgos y pruebas. Un reintento razonado máximo. Riesgo alto o segundo fallo → Astra.
6. **Verificar**: revisión independiente de seguridad, diff, sintaxis, consumidores y prueba de regresión disponible. No declarar funcionalidad comprobada por análisis estático.
7. **Consolidar**: Astra cierra, devuelve estado y actualiza documentación significativa una sola vez.

## Matriz

| Riesgo | Ejemplos | Estrategia |
| --- | --- | --- |
| Bajo | Traducción simple, búsqueda acotada, documentación | Astra directo o Luna/Sol delegado |
| Medio | Cambios de una operación, nueva UI/CO acotada | Sol implementa; verificador revisa si hay impacto |
| Alto | Identidad, inscripciones, varias apps, datos críticos, migraciones, seguridad, fallos repetidos | Astra analiza; subagentes hacen tareas independientes y delimitadas |

## Contrato de finalización

- Código permitido solamente; diff revisado y sin modificaciones ajenas.
- Evidencia de validaciones ejecutadas y no ejecutadas; estado de aceptación funcional pendiente si aplica.
- Acciones humanas Toba/autoload identificadas, no ejecutadas.
- Sin acceso a BD y sin despliegue.
- Documentación mínima actualizada y próximo paso cuando el trabajo sea relevante.

**Limitación deliberada:** esto es una política Codex/AGENTS y no un motor de escalamiento externo determinista. La detección de fallos y decisión de escalar depende del orquestador; el aislamiento fuerte requiere controles del host/sandbox. Ver `SEGURIDAD.md`.

## Mensaje de delegación autocontenido (sin heredar historial)

Astra prepara para cada subagente: (1) objetivo y aplicación; (2) raíz y rutas reales relativas al repo; (3) `AGENTS.md` y skill pertinente que debe abrir; (4) restricciones técnicas, de escritura, BD y despliegue; (5) criterio de aceptación y evidencia mínima; (6) formato de `HANDOFF.md`; (7) modalidad preferida `fork_turns: "none"`, si funciona en ese runtime. **No le envía extractos de código para simular autonomía**.

Antes de una investigación amplia, verificar lectura real de un archivo local seguro y acotado. Reportar herramientas realmente observadas y diferenciar modelo configurado de modelo efectivo comprobado. Si la aplicación ofrece herramientas dinámicas que faltan con `none`, evaluar compatibilidad **sin reducir permisos**; no asumir que `all` siempre las restaura.

## Desacuerdos entre agentes

1. Separar referencias confirmadas, inferencias y contradicciones.
2. Pedir a los agentes las rutas, símbolos y líneas que sostienen cada afirmación (no interpretaciones circulares).
3. Contrastar desde Astra el código común o consumidores sin base de datos.
4. Si no hay prueba suficiente, mantener **no determinado**; no afirmar equivalencias de tablas, APIs o estados de pago.
5. Escalar razonamiento a Astra para decisiones críticas y evitar pedir a Luna/Sol reintentos no fundamentados.

## Evidencia del piloto UB (2026-11-13)

- Primer intento de exploración paralela: dos subagentes iniciados pero sin herramientas de lectura en sus sesiones.
- Nueva prueba con `fork_turns: "none"`: un explorador leyó un archivo de catálogo directamente.
- Prueba paralela posterior: Gestión y Autogestión confirmaron lectura directa con herramientas de PowerShell. Hubo contraste de referencias de tablas diferentes sin asumir equivalencia.
- **No verificado**: modelo efectivo Luna/Sol, implementación o verificación con escritura, recuperación tras un fallo de programación.

Esta evidencia es de una ejecución concreta de Codex; no establece una garantía para todas las versiones del runtime.

## Registros de ejecuciones (2.1.2)

Para una QA o tarea multiagente sustantiva, Astra puede invocar `scripts/trazabilidad.py` y registrar **reportes** de subagentes reales sin producir documentación adicional por cada paso. Ver [TRAZABILIDAD.md](TRAZABILIDAD.md). No hay captura automática: la evidencia de uso de herramientas, escalamiento y modelo efectivo debe contrastarse contra la traza de Codex.
