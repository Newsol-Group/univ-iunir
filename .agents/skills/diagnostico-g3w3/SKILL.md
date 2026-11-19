---
name: diagnostico-g3w3
description: Diagnosticar errores de Autogestión G3W3 a través de controlador, modelo, pagelet, Twig, sesión, caché y JavaScript sin intervención remota.
---

# Skill · Diagnóstico G3W3

**Activación:** fallas en operaciones, refresco de pagelets, traducción, sesión/caché, archivos multipart, AJAX o interfaz.

1. Delimitar síntoma y ruta/operación afectada; determinar si la evidencia proviene del usuario, archivos, logs autorizados o pruebas ejecutadas.
2. Seguir el recorrido de código: ruta → controlador → modelo → `modelo/datos/db`/catálogo → respuesta/pagelets → Twig → JavaScript.
3. Revisar parámetros POST/GET, `enctype`, `FormData` vs `x-www-form-urlencoded`, CSRF/permisos, estado en sesión, invalidación de caché, carga de pagelets y respuestas HTTP **solo cuando corresponda al caso**.
4. Comparar con una implementación funcional de la misma versión. Exponer hipótesis con archivos/métodos verificables. Evitar cambios simultáneos de muchas capas sin justificación.
5. Delegar corrección mínima al implementador, y revisión independiente al verificador. Si persiste tras un intento justificado, escalar con handoff a Astra.
6. Validar sintaxis/contratos locales y enumerar pruebas funcionales que no pudieron ejecutarse.

**Prohibiciones:** sin conexiones BD ni SQL, sin publicar cambios, sin editar código original `siu/` o derivado `pers_gestion/`.
