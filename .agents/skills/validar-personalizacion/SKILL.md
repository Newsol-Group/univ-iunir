---
name: validar-personalizacion
description: Verificar cambios a personalizaciones con Git, inspección estática, sintaxis y restricciones antes de pruebas y revisión técnica, sin BD ni servidores.
---

# Skill · Validar personalización

**Activación:** modificación funcional relevante o revisión técnica del desarrollo. No aplicarla retrospectivamente al inventario de código preexistente.

## Procedimiento
1. Delimitar aplicación, versión observada, cambios asignados y estado de Git. Consultar los `AGENTS.md` de las aplicaciones involucradas. Identificar cambios locales ajenos.
2. Clasificar el diff en código personalizado, referencia legacy, `pers_gestion`, SQL, documentación, Toba y autoload. Ejecutar `python3 scripts/auditar_cambios.py --modo trabajo` si corresponde y revisar el resultado contra cambios ajenos. **Bloquear** si hay escritura en código protegido o violación del alcance. El script solo detecta rutas sin confirmar, no reemplaza revisar el diff.
3. Ejecutar `git diff --check` según el trabajo. Si PHP compatible está disponible, `php -l` sobre PHP editados. No instalar dependencias ni generar artefactos para poder validar.
4. Revisar lógica, contratos, consumidores, autorización, transacciones y dependencias mediante código y archivos, **sin ejecutar consultas a ninguna base**.
5. Gestión: aplicar `gestion-toba` cuando corresponda. G3W3: claves de traducción, catálogo `_info_catalogo.php` ante firmas, pagelets, sesión y JS. Preinscripción: estados e integración, con convenciones reales.
6. Revisar SQL **solo como texto**: estructura, riesgos e idempotencia cuando aplique. Sin ejecución ni conexiones.
7. Entregar dictamen **apto para probar / requiere ajustes / bloqueado** con archivos, pruebas realizadas y no ejecutadas, evidencias y pendientes manuales. No confundir sintaxis con aceptación funcional.

## Prohibiciones
Sin BD ni siquiera SELECT, sin SQL ejecutado, sin Toba/autoload, sin despliegues, push, merge o alteraciones de legado. El verificador no cambia código: devuelve hallazgos a Astra.
