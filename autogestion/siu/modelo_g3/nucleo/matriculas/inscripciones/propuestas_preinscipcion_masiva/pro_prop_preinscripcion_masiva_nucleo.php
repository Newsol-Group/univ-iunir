<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class pro_prop_preinscripcion_masiva_nucleo extends guarani_procesos_bk
{
	protected $datos_propuestas;
	protected $propuestas;
	protected $anio_academico;
	protected $periodo_inscripcion;
	protected $tipo_ingreso;
	
	protected $datos;
	
	protected $s__persona;
	protected $s__propuesta;
	
	protected $s__fuente;

	const reporte_exitosos = 1;
	const reporte_errores = 2;
	const reporte_resultados = 3;
	
	const nombre = 'pro_prop_preinscripcion_masiva';

	function set_datos($datos = array())
	{
		$this->datos_propuestas = rs_convertir_asociativo_matriz($datos['datos_propuestas'], array('propuesta'));
		$this->propuestas = aplanar_matriz($this->datos_propuestas, 'propuesta');

		$this->anio_academico = $datos['anio_academico'];
		$this->periodo_inscripcion = $datos['periodo_inscripcion'];
		$this->tipo_ingreso = $datos['tipo_ingreso'];

		$this->s__fuente = $datos['fuente'];

		// Se obtienen los datos de preinscripciones que hayan impreso al menos 
		// una vez el comprobante y que tengan al menos una inscripción no vencida
		// sin ser procesada.
		$where = "	sga_preinscripcion.estado = '" . preinscripcion::estado_preparado . "' 
					AND	EXISTS (
						SELECT	1
						FROM	sga_preinscripcion_propuestas
						WHERE	sga_preinscripcion_propuestas.id_preinscripcion = sga_preinscripcion.id_preinscripcion
								AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."'
								AND sga_preinscripcion_propuestas.fecha_fin_vigencia >= CURRENT_DATE
					)";

		$this->datos = toba::consulta_php('co_preinscripciones')->get_listado($where);
	}

	function get_periodo_insc($periodo_inscripcion, $plan_version, $modalidad, $ubicacion)
	{
		return current(toba::consulta_php('co_periodos_de_inscripcion')->get_periodo_insc_vigente_o_vencido($periodo_inscripcion, $plan_version, $modalidad, $ubicacion));
	}

	//-----------------------------------------------------------------------------------

	function procesar()
	{
		if (!empty($this->datos)) {
			$paso = 100 / guarani_arreglos::count($this->datos);
		} else {
			$paso = 100;
			$this->log_estado->incrementar_progreso($paso);
		}

		$cant_exitosos = 0;
		$cant_errores = 0;

		// Se setea la variable en esta instancia ya que sino no se podia 
		// obtener la misma en los siguientes request
		preinscripcion::set_fuente($this->s__fuente);
		foreach ($this->datos as $inscripcion) {
			try {
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}
				toba::db()->abrir_transaccion();

				$apellido_y_nombres_persona = $inscripcion['apellido'] . ', ' . $inscripcion['nombres'];

				$this->s__persona = preinscripcion::procesar_persona($inscripcion);
				$this->procesar_inscripcion($inscripcion, $this->s__persona);
				$cant_exitosos++;

				toba::db()->cerrar_transaccion();
			} catch (guarani_error $e) {
				// Si no hay un periodo_inscripcion_fechas vigente ni vencido para el período de inscripción.
				toba::db()->abortar_transaccion();

				$this->log_estado->set_error();

				$datos_reporte = array();
				$datos_reporte['apellido_y_nombre'] = $apellido_y_nombres_persona;
				if (!empty($this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'])) {
					$datos_reporte['propuesta_nombre'] = $this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'];
				}
				$datos_reporte['error'] = $e->get_mensaje();

				$this->log->set($datos_reporte, array(self::reporte_errores));
				$cant_errores++;
			} catch (toba_error_db $e) {
				toba::db()->abortar_transaccion();

				$this->log_estado->set_error();

				$datos_reporte = array();
				$datos_reporte['apellido_y_nombre'] = $apellido_y_nombres_persona;
				if (!empty($this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'])) {
					$datos_reporte['propuesta_nombre'] = $this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'];
				}
				$datos_reporte['error'] = "Error de Base de Datos";

				// Se deja el registro del mensaje de error completo en el log
				ei_arbol($e->get_mensaje());

				$this->log->set($datos_reporte, array(self::reporte_errores));
				$cant_errores++;
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();

				$this->log_estado->set_error();

				$datos_reporte = array();
				$datos_reporte['apellido_y_nombre'] = $apellido_y_nombres_persona;
				if (!empty($this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'])) {
					$datos_reporte['propuesta_nombre'] = $this->datos_propuestas[$this->s__propuesta]['propuesta_nombre'];
				}
				$datos_reporte['error'] = $e->get_mensaje();

				$this->log->set($datos_reporte, array(self::reporte_errores));
				$cant_errores++;
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['exitosos'] = $cant_exitosos;
		$mensaje_resultado['errores'] = $cant_errores;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_preinscripciones($preinscripcion)
	{
		return toba::consulta_php('co_preinscripciones')->get_preinscripciones_preparadas_insc_por_id($preinscripcion['id_preinscripcion'], $this->s__fuente);
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento
	//-----------------------------------------------------------------------------------

	function procesar_inscripcion($datos_preinscripcion, $persona)
	{
		$this->validar_expulsiones($datos_preinscripcion);
		$this->validar_version_impresa($datos_preinscripcion);

		$preinscripciones = $this->get_preinscripciones($datos_preinscripcion);

		foreach ($preinscripciones as $preinscripcion) {
			if (in_array($preinscripcion['propuesta'], $this->propuestas)) {
				$this->s__propuesta = $preinscripcion['propuesta'];
				$datos_propuesta = $this->datos_propuestas[$this->s__propuesta];
				// El Plan / Versión se obtiene de la selección del operador
				$plan_version = $datos_propuesta['plan_version'];
				// Modalidad y Ubicación se obtienen de la selección del alumno
				$modalidad = $preinscripcion['modalidad'];
				$ubicacion = $preinscripcion['ubicacion'];
				// El periodo_insc se obtiene automáticamente
				$datos_periodo_insc = $this->get_periodo_insc($this->periodo_inscripcion, $plan_version, $modalidad, $ubicacion);
				$periodo_insc = $datos_periodo_insc['periodo_insc'];
				$insc_fuera_de_termino = ($datos_periodo_insc['vigente'] == 1) ? comunes::no : comunes::si;
				if (empty($periodo_insc)) {
					throw guarani::error(toba::mensajes()->get('800MAT_sin_pi_preinscripcion'));
				}

				// Validaciones
				$this->validar_cant_inscripciones_periodo($persona, $this->s__propuesta, $periodo_insc);
				$this->validar_otras_inscripciones($persona, $this->s__propuesta);

				// Procesamiento de la inscripción
				preinscripcion::procesar_inscripcion($persona, $this->periodo_inscripcion, $periodo_insc, $this->anio_academico, $this->s__propuesta, $plan_version, $modalidad, $ubicacion, 'now()', $this->tipo_ingreso, null, $insc_fuera_de_termino);
				
				// Se marca como procesada la inscripción en las tablas del sistema de Preinscripción.
				guarani::act('act_preinscripciones')->marcar_inscripcion_propuesta($preinscripcion['preinscripcion_propuesta'], $this->s__fuente);
			}
		}
		
		// Cerrar la preinscripción (a partir de este momento, se gestionará todo a través de 3w)
		guarani::act('act_preinscripciones')->cerrar_preinscripcion($datos_preinscripcion['id_preinscripcion'], $persona, $this->s__fuente);
	}

	function validar_expulsiones($datos_preinscripcion)
	{
		// Se valida que la persona que se está inscribiendo no esté expulsada en otra Institución. 
		if (toba::consulta_php('co_expulsados_en_otras_instituciones')->identificacion_registra_expulsiones($datos_preinscripcion['pais_documento'], $datos_preinscripcion['tipo_documento'], $datos_preinscripcion['nro_documento'])) {
			$mje = guarani_mensajes::get('800MAT_persona_expulsada');
			throw guarani::error($mje);
		}
	}

	function validar_version_impresa($datos_preinscripcion)
	{
		if ($datos_preinscripcion['version_modificacion'] != $datos_preinscripcion['version_impresa']) {
			throw guarani::error(toba::mensajes()->get('800MAT_preinscripcion_reimprimir'));
		}
	}

	function validar_cant_inscripciones_periodo($persona, $propuesta, $periodo_insc)
	{
		$filtro = "	sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' AND
					sga_propuestas_aspira.periodo_insc = $periodo_insc";
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $propuesta, null, $filtro);
		if ($cant_inscripciones != 0) {
			$mje = guarani_mensajes::get('800MAT_persona_rechazada_periodo', array($this->datos_propuestas[$this->s__propuesta]['propuesta_nombre']));
			throw guarani::error($mje);
		}
	}

	function validar_otras_inscripciones($persona, $propuesta)
	{
		$filtro = "	sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_aceptada . "', '" . propuesta::inscripcion_pendiente . "')";
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $propuesta, null, $filtro);
		if ($cant_inscripciones != 0) {
			$mje = guarani_mensajes::get('800MAT_persona_inscripta');
			throw guarani::error($mje);
		}
	}

}
?>