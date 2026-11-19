<?php
/**
 *
 * 
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class cn_man_rectificar_equivalencia extends cn_manejador_entidad
{
	function cargar_atributos_parametros($propuesta)
	{
		$this->resetear_atributos_parametros();
		$operacion = guarani::get_id_operacion();
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
	}
	
	function get_indice_instancia()
	{
		return 'rectificar_equivalencia';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_equivalencias')->get_listado_tramites($filtro);
	}

	function get_datos_tramite_equiv_otorgado($id_tramite)
	{
		return toba::consulta_php('co_equivalencias')->get_datos_vista_tramite_equiv_otorgado($id_tramite);
	}

	function get_datos_equiv_internas($equivalencia)
	{
		return toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_internas($equivalencia);
	}

	function get_datos_equiv_externas($equivalencia)
	{
		return toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_externas($equivalencia);
	}

	function get_clase_consulta()
	{
		return 'co_equivalencias';
	}
	
	/*
	 * Devuelve los puntos de control a validar para la instancia recibida.
	 */
	function get_puntos_de_control_instancia_equivalencia($instancia)
	{
		return toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
	}
	
	//---- VALIDACIONES --------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function validar_alumno($datos_alumno, $fecha_equiv, $plan_version_tramite)
	{
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::persona_propuesta
		);
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'plan_version' => $plan_version_tramite,
			'fecha' => $fecha_equiv,
			'propuesta' => $datos_alumno['propuesta']
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800EQU_error_tramite', array('rectificar')));
		}
	}
	
	protected function validar_actividad($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_equiv, $id_tramite_original)
	{
		$ptos_control_a_validar = array(
			guarani_punto_de_control::actividad_equivalencia
		);
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $plan_version_tramite,
			'elemento' => $actividad,
			'tipo_tramite_equiv' => $tipo_tramite_equiv,
			'fecha' => $fecha_equiv,
			'id_equiv_tramite_original' => $id_tramite_original
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800EQU_error_tramite_actividad', array('rectificar', $actividad_descr)));
		}
	}

	protected function validar_instancia($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_equiv, $instancia, $actividades_otorgadas, $id_tramite_original, $notificar = true)
	{
		// Arma el parámetro de equivalencias otorgadas para el control de correlativas, incluyendo las actividades
		// otorgadas en el mismo trámite. Éste consta de un arreglo asociativo id_elemento => id_instancia, de todas
		// las actividades que se otorgan en el trámite con resultado Aprobado, con la instancia para la que se 
		// otorgan asociada. Solo se registra para cada actividad la instancia de mayor peso.
		$equiv_otorgadas = array();
		foreach ($actividades_otorgadas as $actividad_otorg) {
			if ($actividad_otorg['resultado'] == escalas_notas::resultado_aprobado &&
				$actividad_otorg['instancia'] != equivalencia::equiv_instancia_parcial) {
				$registrar_instancia = true;
				if (isset($equiv_otorgadas[$actividad_otorg['elemento']])) {
					if ($actividad_otorg['instancia'] == equivalencia::equiv_instancia_regularidad_correlativas ||
						$actividad_otorg['instancia'] == equivalencia::equiv_instancia_regularidad && $equiv_otorgadas[$actividad_otorg['elemento']] == equivalencia::equiv_instancia_total) {
						$registrar_instancia = false;
					}
				}
				if ($registrar_instancia) {
					$equiv_otorgadas[$actividad_otorg['elemento']] = $actividad_otorg['instancia'];
				}
			}
		}
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $plan_version_tramite,
			'elemento' => $actividad,
			'tipo_tramite_equiv' => $tipo_tramite_equiv,
			'fecha' => $fecha_equiv,
			'equivalencias_otorgadas' => $equiv_otorgadas,
			'id_equiv_tramite_original' => $id_tramite_original
		);
		$ptos_control = $this->get_puntos_de_control_instancia_equivalencia($instancia);
		$retorno = $this->validar_puntos_de_control($ptos_control, $params);

		if ($retorno == guarani_punto_de_control::rechazado && $notificar) {
			throw new guarani_error(null, null, guarani_mensajes::get('800EQU_error_tramite_actividad', array('rectificar', $actividad_descr)));
		}
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		//No se detiene la validación de controles ante el fallo en un control.
		$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params, false)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		return $retorno;
	}

	/**
	 * Se ejecutan los puntos de control correspondientes, en caso de corresponder según el flag y luego se
	 * sincroniza en la BD el trámite de la equivalencia otorgada con sus datos.
	 * @param boolean $ejecutar_controles se permite indicar si ejecutar o no los controles para evitar la
	 * ejecución duplicada de los mismos en el caso del cierre automático.
	 */
	function guardar($ejecutar_controles=true)
	{
		try {
			if ($ejecutar_controles) {
				$equiv_tramite = $this->entidad()->get_datos();
				$fecha_tramite = $equiv_tramite['fecha'];
				$plan_version_tramite = $equiv_tramite['plan_version'];
				$tipo_tramite_equiv = equivalencia::tipo_tramite_rectificativo;
				$id_tramite_original = $equiv_tramite['rectifica_a'];
				$equiv_otorgadas = $this->entidad()->get_datos_equiv_otorgada();

				// Validación de puntos de control sobre el Alumno al que se le otorgó la equivalencia.
				$alumno = $equiv_tramite['alumno'];
				$resultado = toba::consulta_php('co_alumnos')->get_datos_alumno($alumno);
				$datos_alumno = $resultado[0];
				$this->validar_alumno($datos_alumno, $fecha_tramite, $plan_version_tramite);

				// Validación de puntos de control específicos de equivalencias sobre las actividades e instancias
				// de la equivalencia otorgada.
				foreach ($equiv_otorgadas as $equiv) {
					$actividad = $equiv['elemento'];
					$actividad_descr = $equiv['elemento_nombre'];
					$fecha_otorgada = $equiv['fecha'];
					$this->validar_actividad($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_otorgada, $id_tramite_original);
					$instancia = $equiv['instancia'];
					$this->validar_instancia($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_otorgada, $instancia, $equiv_otorgadas, $id_tramite_original);
				}
			}
			parent::guardar();
		} catch (guarani_error $e) {
			throw $e;
		} catch (toba_error $e) {
			toba::notificacion()->agregar($e->get_mensaje_log(), comunes::error_critico);
			$mje = guarani_mensajes::get('800EQU_error_tramite', array('rectificar'));
			throw guarani::error($mje);
		}
	}
}
?>