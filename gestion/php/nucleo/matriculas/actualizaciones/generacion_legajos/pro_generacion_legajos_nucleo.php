<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class pro_generacion_legajos_nucleo extends guarani_procesos_bk
{
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	
	// Datos de inscripciones con legajos para actualizar.
	protected $datos;
	
	// Parámetros
	protected $param_unico_legajo;
	protected $param_generacion;

	const reporte_generados = 1;
	const reporte_no_generados = 2;
	const reporte_resultados = 3;
	
	const nombre = 'pro_generacion_legajos';

	function set_datos($datos = array())
	{
		$this->operacion = $datos['operacion'];
		$this->param_unico_legajo = $datos['unico_legajo'];
		$this->param_generacion = $datos['generacion'];
		$this->datos = $datos['datos'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->datos);

		$cant_generados = 0;
		$cant_no_generados = 0;

		foreach ($this->datos as $key => $alumno) {

			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}

			try {
				$retorno = $this->validar_puntos_de_control($alumno);
				$mensajes = $retorno['mensajes'];
				$retorno = $retorno['retorno'];

				if ($retorno != guarani_punto_de_control::rechazado) {
					if ($this->param_unico_legajo == comunes::si && !empty($alumno['legajo_otra_propuesta'])) {
						// Se copia el legajo de otra propuesta.
						guarani::act('act_inscripciones')->actualizar_legajo($alumno['persona'], $alumno['propuesta'], $alumno['propuesta_aspira'], $alumno['ubicacion'], $this->param_unico_legajo, $this->param_generacion, $alumno['legajo_otra_propuesta']);
						$alumno['legajo'] = $alumno['legajo_otra_propuesta'];
						$this->log->set($alumno, array(self::reporte_generados));
						$cant_generados++;
					} else {
						if ($this->param_generacion == legajos::generacion_manual) {
							// Controlo que el legajo no se haya asignado previamente.
							$legajo_en_uso = $this->validar_legajo_en_uso($alumno['legajo'], $alumno['propuesta'], $this->param_unico_legajo);
							if ($legajo_en_uso) {
								$alumno['observaciones'] = "El legajo {$alumno['legajo']} ya se encuentra asignado";
								$this->log->set($alumno, array(self::reporte_no_generados));
								$cant_no_generados++;
							} else {
								// Si la generación es manual, se pasa también el nro de legajo.
								guarani::act('act_inscripciones')->actualizar_legajo($alumno['persona'], $alumno['propuesta'], $alumno['propuesta_aspira'], $alumno['ubicacion'], $this->param_unico_legajo, $this->param_generacion, $alumno['legajo']);
								// Guardo el resultado como exitoso.
								$this->log->set($alumno, array(self::reporte_generados));
								$cant_generados++;
							}
						} else {
							// Si la generación es automática, el legajo lo genera el proceso de actualización.
							guarani::act('act_inscripciones')->actualizar_legajo($alumno['persona'], $alumno['propuesta'], $alumno['propuesta_aspira'], $alumno['ubicacion'], $this->param_unico_legajo, $this->param_generacion);
							$datos_alumno = current(guarani::co('co_alumnos')->get_datos_alumno($alumno['alumno']));
							$alumno['legajo'] = $datos_alumno['legajo'];
							$this->log->set($alumno, array(self::reporte_generados));
							$cant_generados++;
						}
					}
				} else {
					$alumno['observaciones'] = $mensajes;
					$this->log->set($alumno, array(self::reporte_no_generados));
					$cant_no_generados++;
				}
			} catch (toba_error $e) {
				$this->log_estado->set_error();
				$alumno['observaciones'] = $e->get_mensaje();
				$this->log->set($alumno, array(self::reporte_no_generados));
				$cant_no_generados++;
			}

			$this->log->guardar();

			if (!$this->abortar) {
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		$mensaje_resultado['generados'] = $cant_generados;
		$mensaje_resultado['no_generados'] = $cant_no_generados;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	/**
	 * Determina si un nro de legajo se encuentra ya en uso, ya sea de manera global en el sistema 
	 * o por propuesta, dependiendo del parámetro $param_legajo_unico
	 *
	 * @param $legajo				legajo que se quiere consultar
	 * @param $propuesta			propuesta para la que se pretende utilizar el legajo
	 * @param $param_legajo_unico	parámetro que indica si se debe validar a nivel global o por propuesta
	 */
	function validar_legajo_en_uso($legajo, $propuesta, $param_legajo_unico)
	{
		return toba::consulta_php('co_alumnos')->legajo_en_uso($legajo, $propuesta, $param_legajo_unico);
	}

	function validar_puntos_de_control($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $alumno['persona'],
			'propuesta' => $alumno['propuesta'],
			'alumno' => $alumno['alumno'],
			'plan_version' => $alumno['plan_version']
		);
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_entidad, guarani_punto_de_control::persona_propuesta);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes();
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

}
?>