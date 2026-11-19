---
name: relevar-personalizaciones
description: Inventariar por función personalizaciones preexistentes de Guaraní mediante lectura estática sin cambiar código ni inventar estado de ambientes.
---

# Skill · Relevamiento inicial estático

**Activación:** fase documental de `inicializado.md` con fuentes verificables. Puede repartir exploración por aplicación; Astra consolida IDs y fichas.

1. Identificar código real, rama, commit, cambios locales, fuente/procedencia comunicada y cobertura observable. No presumir ambientes.
2. Recorrer áreas personalizadas completas o parciales; usar legado y metadatos solo como referencia. Seguir rutas, clases, operaciones, SQL como texto e integraciones.
3. Agrupar por funcionalidad, incluso transversal: un ID `PERS-###` por función, nunca por archivo. Reutilizar IDs existentes en reanudaciones.
4. Editar **únicamente** `docs/personalizaciones/INDICE.md`, fichas informativas vinculadas y enlace documental mínimo si falta. No crear tickets ni estados de despliegue retroactivos.
5. Registrar mapa de archivos, clases, evidencia y límites. Cobertura: completa, parcial o no leída.
6. Verificar integridad de IDs, enlaces y que no se modificó código. Devolver a Astra resultados compactos por aplicación para consolidación final.

**Prohibiciones:** no ejecutar pruebas, SQL, BD, comandos que regeneren Toba/autoload, ni editar código, traducciones, configuración funcional o estado del servidor. Sin secretos ni datos personales.
