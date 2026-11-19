---
name: gestion-toba
description: Diagnosticar personalizaciones SIU Gestión que requieren TobaEditor y preparar instrucciones manuales verificables; nunca modificar metadatos ni autoloads.
---

# Skill · Gestión / Toba

**Activación:** altas y modificaciones de operaciones, CI, formularios, cuadros, eventos, JavaScript, permisos o cualquier configuración dependiente de TobaEditor.

## Procedimiento
1. Leer `AGENTS.md`, `gestion/AGENTS.md`, información de cliente pertinente y código real. Identificar versión observada y comparar con operaciones análogas.
2. Rastrear operación, componente, relaciones, clases PHP y configuración observable sin inventar IDs. Distinguir cambios de código editable frente a acciones manuales.
3. Preparar instrucciones humanas concretas: prerrequisitos, componente/propiedad, pasos en orden, valor verificado o «a verificar», vinculación de clases/eventos, SQL a entregar **como texto**, comprobaciones y recuperación.
4. Si corresponde, incluir «generar autoloads» como **paso humano posterior**. No ejecutarlo ni crear el archivo.
5. Comunicar al orquestador riesgos, acciones manuales y evidencia. Si involucra arquitectura de negocio compleja, escalar a Astra.

## Prohibiciones
No escribir metadatos Toba ni autoloads directa o indirectamente, no ejecutar TobaEditor, SQL ni consultas a bases de datos, no gestionar servidores ni marcar instalación como completa sin evidencia.

## Resultado mínimo
Operación/componente, archivos editables, pasos manuales, elementos no verificables, validación esperada y restricciones. Guardar en la ficha del trabajo o entrega existente, sin duplicar documentos.
