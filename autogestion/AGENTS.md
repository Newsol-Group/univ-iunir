<!-- G3FORGE:BEGIN:autogestion/AGENTS.md -->
# G3Forge · Especialización Autogestión (G3W3)

Aplicar `../AGENTS.md`. Detectar fuentes reales; este directorio preparado no acredita instalación ni versión.

## Capacidades y áreas
- Editar personalizaciones bajo `pers/` (o equivalente comprobado); `siu/` original y `pers_gestion/` derivado son **solo lectura**.
- Analizar operaciones y recorrido: rutas → controlador → modelo → datos/catálogo → respuesta/pagelet → Twig/JS, junto con autenticación, sesión y caché cuando intervengan.
- Buscar un patrón análogo de la misma versión antes de desarrollar o cambiar una operación.

## Contratos técnicos
- Mensajes PHP mostrados al usuario mediante `kernel::traductor()->trans('operacion.mensaje')` cuando esa sea la convención efectiva; agregar o reutilizar traducciones en `mensajes.es.php` personalizado, evitando claves duplicadas.
- Ante cambios de firma en `modelo/datos/db`, sincronizar `modelo/datos/_info_catalogo.php` y comprobar consumidores relevantes (solo si dichos archivos y convención existen en la instalación).
- Respetar pagelets, nombres, plantilla, relaciones con Gestión y convenciones de envío de formularios; los archivos multipart y AJAX necesitan verificar el flujo de transporte efectivo.
- Usar mecanismos SIU para invocar consultas y actualizaciones; no reemplazarlos por inclusiones directas. No aplicar reglas de TobaEditor salvo evidencia real de dependencia.
- Sin conexiones a base de datos, sin ejecución SQL, sin modificaciones de código SIU original ni derivados.

## Procedimiento de diagnóstico
1. Reproducir o delimitar el síntoma con evidencia disponible, sin simular resultados como hechos.
2. Localizar controlador, modelo y vista; seguir parámetros POST/GET, sesión, caché, pagelets y JS según la operación.
3. Identificar una causa probable documentada; proponer un cambio mínimo en la capa responsable.
4. Verificar funciones, claves de traducción, firmas de catálogo, contratos de página y efectos colaterales; distinguir estático de funcional.
5. Si hay fallo persistente o recorrido por muchas capas, devolver diagnóstico a Astra. Usar `.agents/skills/diagnostico-g3w3/SKILL.md`.

## Escalamiento a Astra
Astra debe asumir análisis profundo ante errores de sesión/caché difíciles, flujos de autorización, inconsistencias Gestión–G3W3, subida de archivos con varias capas, identidad, inscripción o fallos repetidos. Un reintento fundamentado como máximo.

## Cierre
Validar solo lo disponible de forma local y segura. Documentar dependencias entre apps; no afirmar desplegado sin evidencia de ambiente y aceptación funcional.
<!-- G3FORGE:END:autogestion/AGENTS.md -->
