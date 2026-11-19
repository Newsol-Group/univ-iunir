<?php
/**
 * 
 * 
 * @package		Matrícula
 * @subpackage	Inscripciones
 * 
 */
class inscripcion_nucleo
{
	static protected $instancia;

	// Valores posibles para el parámetro al rechazar inscripción a propuesta e invalidación de HA (mat_rechazar_inscripcion)
	const PARAM_RECHAZO_ANULA_HA = 'A';
	const PARAM_RECHAZO_NO_ANULA_HA = 'N';
	const PARAM_RECHAZO_NO_RECHAZA_CON_HA = 'M';
	
	// Operaciones sobre la inscripción a propuesta
	const operacion_baja					= 'B';
	const operacion_cambio_calidad_pasivo	= 'P';
	const operacion_cambio_calidad_activo	= 'A';
	const operacion_rechazo					= 'R';
	const operacion_revertir_rechazo		= 'RR';

	const tipo_ingreso_mayor_25 = 3;
	
	const motivo_rechazo_cupo = 1;
	const motivo_rechazo_otra_comision = 2;	
	
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new persona();
		}
		return self::$instancia;
	}

	static function cumple_requisitos_ingreso($propuesta_aspira, $restrictivos=false)
	{
		$filtro = $restrictivos ? "sga_requisitos_aspirante.restrictivo = '".comunes::si."'" : '';
		$cumple_requisitos_ingreso_documentales = toba::consulta_php('co_inscripciones')->cumple_requisitos_ingreso_documentales($propuesta_aspira, $filtro);

		$cumple_requisitos_ingreso_tipo_proceso = true;
		if ($cumple_requisitos_ingreso_documentales) {
			$requisitos_ingreso_tipo_proceso = toba::consulta_php('co_inscripciones')->get_requisitos_ingreso_tipo_proceso($propuesta_aspira, $filtro);
			foreach ($requisitos_ingreso_tipo_proceso as $requisito) {
				$regla = guarani::regla($requisito['regla']);
				$regla->set_parametros($requisito['parametros']);

				$parametros_contexto['fecha'] = guarani_fecha::get_hoy();
				$parametros_contexto['persona'] = $requisito['persona'];
				$parametros_contexto['propuesta'] = $requisito['propuesta'];
				$parametros_contexto['anio_academico'] = $requisito['anio_academico'];
				$parametros_contexto['plan_version'] = $requisito['plan_version'];
				$regla->set_parametros_contexto($parametros_contexto);

				$cumple_requisitos_ingreso_tipo_proceso = $regla->validar();
				if (!$cumple_requisitos_ingreso_tipo_proceso) {
					break;
				}
			}
		}

		return ($cumple_requisitos_ingreso_documentales && $cumple_requisitos_ingreso_tipo_proceso);
	}

	//--------------------------------------------------------------------------
	//---- Inscripción a cursos ------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Se realiza una inscripción a una propuesta para administrar cursos.
	 * Recibe la comisión de la primera inscripción para obtener ciertos parámetros
	 * para completar la inscripción.
	 * 
	 * @param int $persona
	 * @param int $propuesta
	 * @param int $comision
	 */
	static function inscribir_propuesta_curso($persona, $propuesta, $comision, $params_sistema, $interfaz=comunes::interfaz_gestion)
	{
		// Se obtienen los datos de plan y plan_version a partir de la propuesta
		$datos_plan = toba::consulta_php('co_propuestas')->get_plan_y_plan_version_propuesta_cursos($propuesta);
		$plan = $datos_plan['plan'];
		$plan_version = $datos_plan['plan_version'];

		// Se obtienen los datos básicos de la comisión, para obtener parámetros de la inscripción
		$where_comision = 'sga_comisiones.comision = ' . toba::db()->quote($comision);
		$datos_comision = current(guarani::co('co_comisiones')->get_cursos($propuesta, $plan, $plan_version, $where_comision, false, false, 'comision', true, $params_sistema));

		// Se obtiene una modalidad de la comisión para usar como modalidad de la inscripción
		$modalidad = toba::consulta_php('co_comisiones')->get_modalidad_inscripcion($comision);

		// Se obtiene el período de inscripción
		$datos_per_insc = toba::consulta_php('co_periodos_de_inscripcion')->get_periodo_inscripcion_para_insc_cursos($plan_version, $modalidad, $datos_comision['ubicacion'], $datos_comision['anio_academico'], $interfaz);
		if (empty($datos_per_insc['periodo_inscripcion']) || empty($datos_per_insc['periodo_insc'])) {
			$datos_propuesta = guarani::co('co_propuestas')->get_datos_propuesta($propuesta);
			//TODO: Ver la forma de que los mensajes se puedan ver en Gestión y 3W
			//$msj = guarani_mensajes::get('800CUR_Sin_PI_Propuesta', array($datos_propuesta['nombre']));
			$msj = "¡Atención! No se puede realizar la inscripción ya que no está abierta la inscripción a '{$datos_propuesta['nombre']}'.";
			throw guarani::error($msj);
		}
		$periodo_inscripcion = $datos_per_insc['periodo_inscripcion'];
		$per_insc_fechas = $datos_per_insc['periodo_insc'];

		// Se procesa la inscripción
		preinscripcion_nucleo::procesar_inscripcion($persona, $periodo_inscripcion, $per_insc_fechas, $datos_comision['anio_academico'], $propuesta, $plan_version, $modalidad, $datos_comision['ubicacion'], 'now()', null, null, comunes::no, propuesta::situacion_asp_insc_aspirante_a_prop, null, planes::tipo_estructurado);
	}

}
?>