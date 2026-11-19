# G3Forge · Trazabilidad de ejecuciones multiagente

**Alcance 2.1.2–2.1.3:** registros **locales declarativos**, no instrumentación interna de Codex. No se interceptan llamadas, mensajes, prompts o tokens. No se confirma el modelo efectivo sin una fuente verificable. No requiere internet, base de datos ni acceso al servidor.

## Cuándo usarlo

- QA del framework, investigaciones multiagente relevantes, correcciones con escalamiento o revisión independiente.
- Evitar registros para consultas triviales, tareas de un solo paso o trabajo que ya tiene una evidencia suficiente en otro documento.
- Astra debería crear el registro al comenzar, completar una anotación por subagente **realmente invocado** y cerrarlo al finalizar. Si no pudo registrar, informarlo, no inventar ejecuciones retrospectivas.

## Comandos para Codex (desde la raíz Git del cliente)

Las siguientes órdenes las puede ejecutar Codex en la terminal del proyecto cuando esté autorizado; no hace falta completarlas manualmente cada vez. Usar Python 3.11+ (en Windows `py -3`, si está disponible).

```powershell
# 1. Registrar inicio de una tarea relevante (copia el ID RUN-... devuelto)
py -3 scripts/trazabilidad.py iniciar --repo . --tipo exploracion --objetivo "Investigar circuito de facturacion"

# 2. Registrar SOLO lo reportado por cada subagente; sustituir el ID real
py -3 scripts/trazabilidad.py anotar --repo . --id RUN-AAAAMMDDTHHMMSSZ-XXXXXXXX --agente explorador_gestion --rol explorador --resultado completado --modelo-configurado gpt-6-luna --acceso reportado --herramientas "PowerShell/lectura" --detalle "Identifico controladores y dependencias; ver informe de la sesion"

# 3. Cierre con el resultado realmente conocido
py -3 scripts/trazabilidad.py cerrar --repo . --id RUN-AAAAMMDDTHHMMSSZ-XXXXXXXX --estado completado --resumen "Exploracion terminada; pendientes de confirmacion identificados"

# 4. Consultar y revisar
py -3 scripts/trazabilidad.py resumen --repo . --limite 10
py -3 scripts/trazabilidad.py detalle --repo . --id RUN-AAAAMMDDTHHMMSSZ-XXXXXXXX
py -3 scripts/trazabilidad.py verificar --repo .
```

Los IDs mostrados son **marcadores de formato**, no registros reales. No copiarlos sin reemplazarlos por el ID recibido de `iniciar`.

## Evidencias y niveles de confianza

| Campo | Interpretación |
| --- | --- |
| `modelo_configurado` | Valor declarado en el TOML; **no demuestra** el modelo usado por Codex. |
| `modelo_efectivo_declarado` | Solo si alguien dispone de una fuente concreta, declarada y acompañada de referencia local; el CLI **no autentica** ese dato. |
| `acceso_workspace_reportado` | `reportado`, `bloqueado` o `no_comprobado`; no prueba automáticamente herramientas usadas. |
| `evidencia_archivo.sha256` | Integridad de una referencia local y su tamaño, **no** que una herramienta se haya ejecutado. |
| `calidad_evidencia` | `solo_declaracion` o `referencia_con_hash_no_auditoria`. |

Si necesitás referenciar una evidencia local sin copiarla al registro, podés usar `--evidencia` al anotar, con una ruta relativa a un archivo del repositorio. El script rechaza rutas fuera del repositorio, `.git`, `.env` y extensiones típicas de credenciales/bases. Revisá que el archivo **no contenga secretos ni información personal** antes de adjuntarlo o versionarlo.

Por defecto los registros viven en `.g3forge/ejecuciones/` y están excluidos de Git. Son independientes de `docs/ESTADO.md` y **no prueban despliegues**. Para compartir un resumen en el equipo, exportar por pedido expreso:

```powershell
py -3 scripts/trazabilidad.py exportar --repo . --id RUN-AAAAMMDDTHHMMSSZ-XXXXXXXX --salida docs/g3forge/ejecucion-revisada.json
```

No se sobrescriben exportaciones; revisar su contenido antes de hacer commit.

## Limitaciones y evolución futura

1. **No hay telemetría automática ni enlace con la API interna de Codex**. El orquestador debe invocar el CLI explícitamente. Si la sesión termina abruptamente, el registro puede permanecer `en_curso`.
2. Los registros son editables por usuarios con acceso al filesystem; no constituyen una auditoría inmutable ni una firma de confianza.
3. No se registra consumo de tokens, latencia real por modelo, aprobaciones del host ni identidad verificable del proveedor.
4. Una integración futura con eventos exportados/autorizados de Codex podría elevar la calidad probatoria, sin ampliar permisos ni recopilar contenido sensible.

## Consulta individual en 2.1.3

El comando `detalle` muestra agentes, rol, estado reportado, modelo configurado y modelo efectivo **solo declarado**, y vuelve a calcular el SHA-256 de cada evidencia local. Muestra `hash coincidente`, `hash diferente` o `evidencia no disponible`. No reconstruye sesiones Codex ni prueba que el modelo informado se haya ejecutado.
