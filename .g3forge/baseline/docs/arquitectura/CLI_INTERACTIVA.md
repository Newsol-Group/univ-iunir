# Consola de G3Forge AI

G3Forge AI se inicia desde la carpeta de la distribución con `.\G3Forge.ps1`. La instalación global opcional del lanzador se realiza con `.\Instalar-CLI.ps1`; una vez configurado, `g3forge` abre la consola. La versión declarada en `VERSION` es `2.2.2-alpha.2`; cambios locales posteriores no publicados no constituyen una versión nueva.

La consola ofrece **Validaciones e historial**, **Administrar G3Forge** y **Cambiar cliente**. El primer menú verifica el framework, consulta trazas y prepara un diagnóstico de lectura multiagente; el diagnóstico no inicia agentes. Administración permite instalar, actualizar y verificar recursos del propio framework. G3Forge Deploy es responsable de forma independiente de la operación de entregas y ambientes.

## Actualizar un workspace

Desde la distribución, elegir **Administrar G3Forge → Actualizar framework del cliente**. El workspace debe tener Git limpio. Revisar la previsualización y confirmar escribiendo `ACTUALIZAR` solamente si se acepta su alcance; después verificar y revisar `git diff`. La actualización no realiza commits ni push y no requiere repetir la inicialización documental.

Las actualizaciones pueden neutralizar recursos operativos heredados con stubs informativos. El mecanismo conserva los datos existentes; si detecta que un recurso fue editado localmente, informa un conflicto. Los stubs informativos no se instalan en workspaces nuevos. Al resolver un retirado, el destino admite equivalencia LF/CRLF respecto del contenido informativo esperado; la propuesta incoming requiere coincidencia exacta de bytes. Las demás diferencias quedan para conciliación manual. No se describe eliminación silenciosa ni una actualización ya aplicada.

## Límites

- No administra catálogo operativo, versiones PERS, paquetes de entrega ni despliegues.
- No ejecuta SQL, accede a bases de datos, modifica Toba/autoloads ni se conecta a servidores.
- La presencia de archivos, instrucciones o historial Git no prueba estado de Testing o Producción.

La selección de repositorio conserva por ahora el comportamiento anterior. La revisión completa de la preferencia Git actual/recientes, selección manual y cancelación corresponde a una intervención posterior.
