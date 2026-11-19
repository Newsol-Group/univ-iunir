# Contrato de transferencia entre agentes · 2.1.1

El handoff es una **respuesta breve a Astra**, no un archivo por tarea. Incluir solo campos sustentados por evidencia; usar `no_verificado` o `no_aplica` cuando corresponda. No incluir secretos, datos personales ni volcados de código.

```yaml
estado: completado             # completado | requiere_revision | bloqueado
objetivo: "Analizar la facturación de Autogestión"
aplicacion: autogestion
modalidad:
  fork_turns: none              # none | all | N | desconocido
  autonomia: directa           # directa | extractos | no_disponible
  herramientas_observadas: ["terminal/lectura"]
  modelo_configurado: "gpt-6-luna"
  modelo_efectivo: no_verificado
archivos_consultados:
  - ruta: "autogestion/src/pers/.../controlador.php"
    simbolos: ["index"]
hallazgos_confirmados:
  - "La clase consulta un método en el catálogo (identificar línea real)"
inferencias: []                # hipótesis separadas de hechos
contradicciones: []             # evidencia comparada de otros agentes
cambios: []                    # rutas realmente editadas, si las hay
comprobaciones:
  ejecutadas: []               # comando + resultado real
  no_ejecutadas: ["Prueba funcional sin entorno autorizado"]
intentos: 0                     # reintentos de recuperación tras fallo
bloqueo: null                   # si existe: error, herramienta ausente y causa
recomendacion: "Contrastar con Gestión sin acceder a BD"
```

**El ejemplo es ilustrativo.** No inventar líneas, símbolos, modelos efectivos ni ejecuciones. Para un trabajo trivial reducir campos; no burocratizar.

## Validación por Astra

1. Verificar que el objetivo recibido coincide con el resultado.
2. Comprobar rutas/símbolos por muestreo cuando el resultado sea crítico o contradictorio.
3. Distinguir **lectura autónoma** de análisis de fragmentos aportados por Astra; ambos pueden ser útiles, pero no son equivalentes.
4. Resolver contradicciones solo con evidencia; nombres de tablas distintos no prueban equivalencia.
5. Documentar bloqueos en la respuesta, evitar reintentos idénticos y aplicar validación independiente cuando hubo escritura.

## Registro opcional de ejecuciones

Cuando la ejecución multiagente sea relevante o una QA, Astra puede registrar el handoff con `scripts/trazabilidad.py` sin crear una ficha nueva. Estos registros son **declarativos** y no garantizan telemetría del runtime: las herramientas realmente observadas y los modelos efectivos requieren evidencia específica. Ver [TRAZABILIDAD.md](TRAZABILIDAD.md).
