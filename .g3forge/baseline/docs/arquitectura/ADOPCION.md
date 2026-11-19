# Adopción, actualización y versiones de G3Forge 2.1

G3Forge es una distribución versionada independiente de cada repositorio de cliente. La misma release puede instalarse en distintos clientes sin arrastrar datos de otros proyectos.

El instalador `tools/g3forge.py` distingue archivos administrados de archivos locales:

- `AGENTS.md` se integra mediante bloques marcados; las reglas propias del cliente se editan **fuera** del bloque.
- Configuración de cliente y documentación operativa son **locales** y no se pisan al actualizar.
- Agentes, skills, orquestación e inicializador son **administrados**; los cambios locales generan propuestas de conflicto, nunca reemplazos silenciosos.
- `.g3forge/manifest.json` conserva hashes y versiones por archivo. La actualización no borra automáticamente archivos retirados de la distribución.
- `.g3forge/baseline/` sirve para distinguir cambios propios y del framework. No contiene secretos ni datos de alumnos.

**Manual paso a paso:** [`../instalacion/GUIA.md`](../instalacion/GUIA.md).

Los comandos trabajan exclusivamente sobre archivos locales; no conectan a GitHub ni crean repositorios. La incorporación al repositorio central, los commits y los pushes son actos separados y explícitos.
