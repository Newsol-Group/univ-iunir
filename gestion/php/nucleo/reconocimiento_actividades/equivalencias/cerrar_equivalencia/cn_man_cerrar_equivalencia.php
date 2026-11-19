<?php
/**
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class cn_man_cerrar_equivalencia extends cn_manejador_entidad
{
	function cargar_atributos_parametros($propuesta)
	{
		$operacion = guarani::get_id_operacion();
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
	}
	
	/**
	 * Se redefine la función que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	 * Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	 */
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_equiv_tramite');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "'";
		if (isset($filtro) && trim($filtro) != '') {
			$where .= ' AND ' . $filtro;
		}
		return toba::consulta_php('co_equivalencias')->get_listado_tramites($where);
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
			guarani_punto_de_control::persona_propuesta,
			guarani_punto_de_control::alumno_anio_academico			
		);
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'plan_version' => $plan_version_tramite,
			'fecha' => $fecha_equiv,
			'propuesta' => $datos_alumno['propuesta'],
			'anio_academico' => guarani_fecha::get_parte($fecha_equiv, 'anio')
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(guarani::validador_puntos_de_control()->get_mensajes(), null, guarani_mensajes::get('800EQU_error_tramite', array('cerrar')));
		}
	}
	
	protected function validar_actividad($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_equiv, $id_tramite_original=null)
	{
		$ptos_control_a_validar = array(
			guarani_punto_de_control::actividad_equivalencia
		);
		$params = array(
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $plan_version_tramite,
			'elemento' => $actividad,
			'tipo_tramite_equiv' => $tipo_tramite_equiv,
			'fecha' => $fecha_equiv
		);
		if (!is_null($id_tramite_original)) {
			$params['id_equiv_tramite_original'] =  $id_tramite_original;
		}

		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(guarani::validador_puntos_de_control()->get_mensajes(), null, guarani_mensajes::get('800EQU_error_tramite_actividad', array('cerrar', $actividad_descr)));
		}
	}

	protected function validar_instancia($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_equiv, $instancia, $actividades_otorgadas, $id_tramite_original=null, $notificar = true)
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
			'equivalencias_otorgadas' => $equiv_otorgadas
		);
		if (!is_null($id_tramite_original)) {
			$params['id_equiv_tramite_original'] = $id_tramite_original;
		}

		$ptos_control = $this->get_puntos_de_control_instancia_equivalencia($instancia);
		$retorno = $this->validar_puntos_de_control($ptos_control, $params);

		if ($retorno == guarani_punto_de_control::rechazado && $notificar) {
			throw new guarani_error(guarani::validador_puntos_de_control()->get_mensajes(), null, guarani_mensajes::get('800EQU_error_tramite_actividad', array('cerrar', $actividad_descr)));
		}
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		//No se detiene la validación de controles ante el fallo en un control.

		/*
		 * Cuando No se tiene el id de operacion es que esta usandose 
		 * el pro_cierre_masivo_equivalencia que usa este mismo cn
		 */
		if (!guarani::get_id_operacion()) {
			$params['operacion'] = '800SIUEQU013';			
		}
		$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params, false)->controlar();
		
		/*
		 * Se quita el notificar porque sino para la operación que cierra un solo
		 * trámite se mostrarían dos veces cada mensaje.
		 */
		
		return $retorno;
	}

	/**
	 * Se ejecutan los puntos de control correspondientes y luego se sincroniza en la BD el trámite
	 * de la equivalencia otorgada con sus datos.
	 * @param array $equiv_tramite datos del trámite de equivalencia a cerrar, sobre el cual se corren los controles.
	 * @param array $equiv_otorgadas detalle de las actividades otorgadas en el trámite de equivalencia a cerrar.
	 * @throws guarani_error si los puntos de ejecución no retornan el valor aceptado.
	 */
	function validar_controles_alumno_instancias_equivalencia($equiv_tramite, $equiv_otorgadas)
	{
		try {
			$fecha_tramite = $equiv_tramite['fecha'];
			$plan_version_tramite = $equiv_tramite['plan_version'];
			$tipo_tramite_equiv = $equiv_tramite['tipo_tramite'];
			$id_tramite_original = $equiv_tramite['rectifica_a'];
			
			// Validación de puntos de control sobre el Alumno al que se le otorgó la equivalencia.
			$alumno = $equiv_tramite['alumno'];
			$resultado = toba::consulta_php('co_alumnos')->get_datos_alumno($alumno);
			$datos_alumno = $resultado[0];
			$this->validar_alumno($datos_alumno, $fecha_tramite, $plan_version_tramite);
			
			// Validación de puntos de control específicos de equivalencias sobre las actividades e instancias
			// de la equivalencia otorgada.
			foreach ($equiv_otorgadas as $equiv) {
				$actividad = $equiv['elemento'];
				$actividad_descr = $equiv['elemento_descr'];
				$fecha_otorgada = $equiv['fecha'];
				$this->validar_actividad($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_otorgada, $id_tramite_original);
				$instancia = $equiv['instancia'];
				$this->validar_instancia($datos_alumno, $actividad, $actividad_descr, $tipo_tramite_equiv, $plan_version_tramite, $fecha_otorgada, $instancia, $equiv_otorgadas, $id_tramite_original);
			}
		} catch (guarani_error $e) {
			throw $e;
		} catch (toba_error $e) {			
			toba::notificacion()->agregar($e->get_mensaje_log(), comunes::error_critico);
			$mje = guarani_mensajes::get('800EQU_error_tramite', array('cerrar'));
			throw guarani::error($mje);
		}
	}

	/**
	 * Si el trámite es Normal entonces sólo se modifica su estado a Cerrado.
	 * Si el trámite es Rectificativo además se modifica el detalle del mismo.
	 * Se utiliza también desde la opción de cierre automático en la operación de Rectificar trámites de equivalencia.
	 * Verificar que en dicho caso no se duplique la ejecución de controles.
	 */
	function cerrar_tramite_equivalencia($id_tramite, $equiv_utiliza_acta_digital, $usuario=null)
	{
		$datos_tramite = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($id_tramite);
		$equiv_otorgadas = toba::consulta_php('co_equivalencias')->get_detalle_actividades_tramite_equivalencia($id_tramite);
		
		$utiliza_acta_digital = ($equiv_utiliza_acta_digital == comunes::si);
		$ras = toba::consulta_php('co_alumnos')->get_ras_alumno($datos_tramite['alumno']);

		/**
		 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
		 */
		$cierre_ok = false;
		if ($utiliza_acta_digital) {
			comunes::verificar_jasper_corriendo();
			arai_documentos::validar_cuenta_usuario_origen_arai_documentos($usuario);
			equivalencia::validar_autorizantes_asociados_acta($ras);
		}
		
		$this->validar_modificaciones($datos_tramite, $equiv_otorgadas);
		$this->validar_controles_alumno_instancias_equivalencia($datos_tramite, $equiv_otorgadas);
		
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			guarani::act('act_equivalencias')->cerrar_tramite_equivalencia_cabecera($id_tramite);
			if (!is_null($datos_tramite['rectifica_a'])) {
				guarani::act('act_equivalencias')->rectificar_detalle_tramite_cerrado($id_tramite);
			}
		
			toba::db()->cerrar_transaccion();
			$cierre_ok = true;
		} catch (toba_error $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		} catch (\Exception $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
		
		/**
		 * Envía a SIU-Araí Documentos.
		 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
		 */
		if ($cierre_ok) {
			$parametros = [];
			$parametros['utiliza_acta_digital'] = $equiv_utiliza_acta_digital;
			equivalencia::enviar_tramite_equivalencia_arai_documentos($datos_tramite['equivalencia_tramite'], $parametros, $ras, $usuario);
		}
	}

	function validar_modificaciones($datos_tramite, $equiv_otorgadas)
	{
		if ($datos_tramite['tipo_tramite'] == equivalencia::tipo_tramite_rectificativo) {
			$campos_modificados = array('instancia', 'fecha', 'nota', 'resultado', 'fecha_vigencia', 'temas_a_rendir');
			foreach ($equiv_otorgadas as $equiv_otorgada) {
				if (!empty($equiv_otorgada['rectifica_a'])) {
					$equiv_otorgada_original = toba::consulta_php('co_equivalencias')->get_datos_equiv_otorgada($equiv_otorgada['rectifica_a']);
					$hay_modificaciones = false;
					$i = 0;
					$cant_campos = guarani_arreglos::count($campos_modificados);
					while ($i < $cant_campos && !$hay_modificaciones) {
						$campo = $campos_modificados[$i];
						if ($equiv_otorgada[$campo] != $equiv_otorgada_original[$campo]) {
							$hay_modificaciones = true;
						} else {
							$hay_modificaciones = ($datos_tramite['origen'] == equivalencia::origen_ext_convenio || $datos_tramite['origen'] == equivalencia::origen_ext_libre || $datos_tramite['origen'] == equivalencia::origen_ext_pase) ? $this->hay_modificaciones_equiv_externas($equiv_otorgada['equivalencia'], $equiv_otorgada_original['equivalencia']) : $this->hay_modificaciones_equiv_internas($equiv_otorgada['equivalencia'], $equiv_otorgada_original['equivalencia']);
						}
						$i++;
					}
					if (!$hay_modificaciones) {
						$mje = guarani_mensajes::get('800EQU_tramite_rect_sin_cambios');
						throw guarani::error($mje);
						break;
					}
				}
			}
		}
	}

	function hay_modificaciones_equiv_externas($equivalencia, $equivalencia_original)
	{
		$equiv_externas = toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_externas($equivalencia);
		$equiv_externas_originales = toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_externas($equivalencia_original);
		if (guarani_arreglos::count($equiv_externas) != guarani_arreglos::count($equiv_externas_originales)) {
			return true;
		} else {
			$campos_modificados = array('fecha', 'nota', 'libro', 'acta', 'folio');
			$equiv_externas_originales = rs_convertir_asociativo_matriz($equiv_externas_originales, array('actividad'));
			foreach ($equiv_externas as $equiv_externa) {
				$equiv_externa_original = isset($equiv_externas_originales[$equiv_externa['actividad']]) ? $equiv_externas_originales[$equiv_externa['actividad']] : null;
				foreach ($campos_modificados as $campo) {
					if ($equiv_externa[$campo] != $equiv_externa_original[$campo]) {
						return true;
					}
				}
			}
			return false;
		}
	}

	function hay_modificaciones_equiv_internas($equivalencia, $equivalencia_original)
	{
		$equiv_internas = toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_internas($equivalencia);
		$equiv_internas_originales = toba::consulta_php('co_equivalencias')->get_tramite_equivalencias_internas($equivalencia_original);
		if (guarani_arreglos::count($equiv_internas) != guarani_arreglos::count($equiv_internas_originales)) {
			return true;
		} else {
			$campos_modificados = array('fecha', 'nota', 'origen', 'resultado');
			$equiv_internas_originales = rs_convertir_asociativo_matriz($equiv_internas_originales, array('propuesta', 'elemento'));
			foreach ($equiv_internas as $equiv_interna) {
				$equiv_interna_original = isset($equiv_internas_originales[$equiv_interna['propuesta'] . apex_qs_separador . $equiv_interna['elemento']]) ? $equiv_internas_originales[$equiv_interna['propuesta'] . apex_qs_separador . $equiv_interna['elemento']] : null;
				foreach ($campos_modificados as $campo) {
					if ($equiv_interna[$campo] != $equiv_interna_original[$campo]) {
						return true;
					}
				}
			}
			return false;
		}
	}

}