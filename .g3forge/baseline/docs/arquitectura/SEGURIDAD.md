# Seguridad y límites V2

## Políticas

- Prohibido conectar a bases de datos o ejecutar cualquier consulta, incluidas de solo lectura, tanto local como remota. SQL solo como documento para revisión/entrega manual.
- Prohibido generar, editar, exportar o regenerar metadatos Toba y autoloads (también indirectamente). Documentar acciones humanas necesarias.
- Original SIU y derivados (`php/`, `siu/`, `pers_gestion/`, según aplicación): lectura solamente. No modificar fuentes ni artefactos ajenos a la tarea.
- G3Forge AI no opera entregas ni ambientes. Las instrucciones y herramientas de operación pertenecen a G3Forge Deploy, que es independiente; no inferir que un pase se ejecutó ni el estado de un ambiente a partir de Git.
- En el ámbito de AI, no hacer pushes, merges, cambios destructivos ni transferencias remotas sin autorización expresa.
- Evitar credenciales, datos de alumnos, dumps y otros datos personales. Los nombres de archivos sensibles se pueden identificar sin revelar su contenido.

## Límites reales de las instrucciones

Los roles Codex **heredan los permisos y conectores de la sesión**. Un prompt que diga «solo lectura» no es una frontera de seguridad. No se definen MCP de BD en la plantilla; en equipos con conexiones globales, la política del host debe impedir su uso en este proyecto. Para cumplimiento fuerte se requieren sandbox, permisos de filesystem y controles externos.

El script `scripts/validar_framework.py` revisa la integridad **estática** de la plantilla y `.codex`; `scripts/auditar_cambios.py` detecta rutas de trabajo modificadas que violan la política, sin interceptar llamadas de agentes ni revisar cambios ya comprometidos. La skill `validar-personalizacion` obliga a revisar el diff antes de aceptar cambios. La política de este framework no ofrece un flujo de empaquetado ni publicación de personalizaciones.

## Controles recomendados en el entorno

- Trabajar con proyecto Codex confiable y sandbox de escritura limitado al workspace; no autorizar herramientas privilegiadas por comodidad.
- No conectar bases ni añadir proveedores de acceso remoto al contexto de Codex V2.
- Revisar `git diff` antes de cerrar cada tarea y verificar que no se afectaron rutas protegidas.
- Separar credenciales de entornos y mantener `gitignore` como defensa auxiliar, no única.

## Disponibilidad de herramientas en subagentes

`fork_turns: "none"` es una preferencia operacional observada en el piloto UB, no un permiso nuevo. Si el host inyecta herramientas dinámicas a la sesión principal, pueden no propagarse al subagente. En ese caso **detener la tarea que depende de ellas**, diagnosticar el runtime y escalar, sin aumentar permisos ni habilitar conectores prohibidos. No confundir instrucciones de solo lectura con sandbox efectivo.

**Próximo paso:** revisar esta política junto con las restricciones del workspace concreto y los permisos efectivos del host.
