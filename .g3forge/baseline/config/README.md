# Configuración de cliente

`cliente.toml` identifica una **instancia**, no el framework central. Al copiar la plantilla a un cliente nuevo, reemplazar `SIN_CONFIGURAR` y `sin-configurar` con valores reales. Nunca adivinar versiones o disponibilidad.

`aplicaciones.*.presente` significa **fuente real identificada en la copia local**; no equivale a «instalada en un servidor». El directorio `preinscripcion/` existe siempre como parte de la plantilla, pero puede no haber fuentes de la aplicación. Las rutas son relativas a la raíz del repositorio y deben reflejar el árbol verdadero.

No almacenar aquí IP, credenciales, claves, información personal ni accesos a bases de datos. Los estados operativos y la procedencia de las copias pertenecen a `docs/ESTADO.md` y `docs/PROYECTO.md` con sus evidencias.
