<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Cursos
 * 
 */
class cn_insc_cursos extends guarani_pers_cn
{
	protected $s__persona;
	protected $s__persona_datos;
	
	protected $s__propuesta;
	protected $s__plan;
	protected $s__plan_version;	
	
	protected $s__comision;
	protected $s__comision_datos;
	
	protected $s__alumno;
	protected $s__alumno_datos;
	
	protected $s__datos_impresion;
	
	protected $s__retornos_ptos_control = array();
    protected $s__token;
	
	function resetear()
	{
		unset($this->s__persona);
		unset($this->s__persona_datos);

		unset($this->s__propuesta);
		unset($this->s__plan);
		unset($this->s__plan_version);	

		unset($this->s__comision);
		unset($this->s__comision_datos);

		unset($this->s__alumno);
		unset($this->s__alumno_datos);
		
		unset($this->s__datos_impresion);
		
		$this->s__alumno_datos = array();
	}
	
	function cargar_atributos_parametros($propuestas)
	{
		$this->resetear_atributos_parametros();
		
		$operacion = guarani::get_id_operacion();
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($this->s__plan));

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		if (guarani::parametros()->get_valor('sq_usa_sanaviron') == comunes::no) {
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'sq_id_externo');
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de persona
	//--------------------------------------------------------------------------
	
	/**
	 * Listado de personas según filtro especificado
	 */
	function get_personas($filtro='')
	{
		return toba::consulta_php('co_personas')->get_listado($filtro);
	}
	
	/**
	 * Establece la persona que se inscribirá a cursar.
	 * Obtiene un conjunto de datos básicos de la persona.
	 */
	function set_persona($persona)
	{
		$this->s__persona = $persona;
		$this->s__persona_datos = toba::consulta_php('co_personas')->get_datos_persona($this->s__persona);
	}
	
	/**
	 * Retorna el conjunto de datos básicos de la persona
	 */
	function get_datos_persona()
	{
		return $this->s__persona_datos;
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de curso
	//--------------------------------------------------------------------------
	
	/**
	 * Listado de cursos disponibles para inscripción según la propuesta elegida
	 * y demás filtros especificados.
	 */
	function get_cursos_disponibles($where='')
	{
		$params_sistema = array('sq_usa_sanaviron' => guarani::parametros()->get_valor('sq_usa_sanaviron'));
		return guarani::co('co_comisiones')->get_cursos($this->s__propuesta, $this->s__plan, $this->s__plan_version, $where, true, true, 'comision', true, $params_sistema);
	}
	
	/**
	 * Establece la propuesta seleccionada al filtrar.
	 * Obtiene el plan y plan_version asociados a la misma.
	 */
	function set_propuesta($propuesta)
	{
		$this->s__propuesta = $propuesta;
		
		$datos_plan = toba::consulta_php('co_propuestas')->get_plan_y_plan_version_propuesta_cursos($propuesta);
		if (empty($datos_plan['plan']) || empty($datos_plan['plan_version'])) {
			throw guarani::error('No hay un plan activo para el tipo de cursos seleccionado.');
		}
		$this->s__plan = $datos_plan['plan'];
		$this->s__plan_version = $datos_plan['plan_version'];
	}
	
	function resetear_propuesta()
	{
		unset($this->s__propuesta);
		unset($this->s__plan);
		unset($this->s__plan_version);
	}
	
	/**
	 * Establece la comisión asociada al curso elegido.
	 * Obtiene un conjunto de datos básicos de la misma.
	 */
	function set_comision($comision)
	{
		$this->s__comision = $comision;
		
		$where = 'sga_comisiones.comision = ' . toba::db()->quote($comision);
		$this->s__comision_datos = current($this->get_cursos_disponibles($where));
	}
	
	function resetear_comision()
	{
		unset($this->s__comision);
		unset($this->s__comision_datos);
	}
	
	/**
	 * Información para la pantalla "Resumen"
	 */
	function get_datos_resumen()
	{
		$datos = array_merge($this->s__persona_datos, $this->s__comision_datos);
		unset($datos['instancias_a_inscribir']);
		return $datos;
	}
	
	function es_cobrable()
	{
		return !empty($this->s__comision_datos['costo']);
	}

	function es_inscripcion_cerrada()
	{
		return ($this->s__comision_datos['inscripcion_cerrada'] == comunes::si);
	}

	function get_codigo_inscripcion_cerrada()
	{
		return $this->s__comision_datos['inscripcion_cerrada_codigo'];
	}
	
    /**
     * Setea el token para despues poder seguir el proceso de pago en SQ
     */
    function set_token($token)
    {
        $this->s__token = $token;
    }
    
    /**
     * Retorna el token que se genero la solicitud de consumo en SQ
     */
    function get_token()
    {
        return $this->s__token;
    }
    
	//--------------------------------------------------------------------------
	//---- INSCRIPCIÓN
	//--------------------------------------------------------------------------
	
	/**
	 * Efectúa la inscripción a la comisión del curso seleccionado.
	 * Si es necesario, inscribe previamente a la persona en la propuesta que maneja cursos.
	 * 
	 * @return array estado de la inscripción
	 */
	function inscribir()
	{
		$this->s__alumno_datos = toba::consulta_php('co_alumnos')->get_datos_alumno_por_persona_propuesta($this->s__persona, $this->s__propuesta);

		// Si es su primera inscripción a a un curso de la propuesta englobadora,
		// se lo inscribe en primera instancia
		toba::db()->abrir_transaccion();
		try {
			if (empty($this->s__alumno_datos)) {
				$params_sistema = array('sq_usa_sanaviron' => guarani::parametros()->get_valor('sq_usa_sanaviron'));
				inscripcion::inscribir_propuesta_curso($this->s__persona, $this->s__propuesta, $this->s__comision, $params_sistema);
				$this->s__alumno_datos = toba::consulta_php('co_alumnos')->get_datos_alumno_por_persona_propuesta($this->s__persona, $this->s__propuesta);
			}

			// Se obtiene el ID de alumno para manejar la inscripción
			$this->s__alumno = $this->s__alumno_datos['alumno'];

			$this->validaciones();

			$params_sistema = array(
				'cur_controlar_cupo' => guarani::parametros()->get_valor('cur_controlar_cupo'),
				'cur_controlar_cupo_por' => guarani::parametros()->get_valor('cur_controlar_cupo_por'),
				'cur_insc_siempre_pendiente' => guarani::parametros()->get_valor('cur_insc_siempre_pendiente'),
				'sq_usa_sanaviron' => guarani::parametros()->get_valor('sq_usa_sanaviron')
			);

			$res = inscripcion_cursada::inscripcion_curso($this->s__alumno, $this->s__propuesta, $this->s__plan_version, $this->s__comision_datos, comunes::interfaz_gestion, $params_sistema);

			if ($res['res'] == 1) {
				// Resultado exitoso
				$this->s__datos_impresion = $this->get_datos_resumen();
				$nro_transaccion = $res['pars'][1];
				$datos_insc = current(toba::consulta_php('co_inscripciones_cursadas')->get_datos_inscripcion($nro_transaccion));
				$this->s__datos_impresion['nro_transaccion'] = $datos_insc['nro_transaccion'];
				$this->s__datos_impresion['estado_nombre'] = $datos_insc['estado_nombre'];
				$this->s__datos_impresion['fecha_inscripcion_visual'] = $datos_insc['fecha_inscripcion_visual'];
				$this->s__datos_impresion['propuesta'] = $this->s__propuesta;

				// Si corresponde, se debe informar a SQ
				$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron');

				if (($usa_sq == comunes::si) && ($this->s__comision_datos['cobrable'] == comunes::si)) {
					$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($this->s__persona);
					$callback_url = $_SERVER['HTTP_HOST'] . toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '14000005', array('retorno' => guarani::get_id_operacion_toba()));
					$body = array(
						"cliente" => array(
							"id_externo" => $this->s__persona,
							"tipo_documento" => $datos_persona['tipo_doc'],
							"numero_documento" => $datos_persona['nro_documento'],
							"apellido" => $datos_persona['apellido'],
							"nombres" => $datos_persona['nombres']
						),
						"tipo_consumo" => "S",
						"id_consumo" => $this->s__propuesta . '|' . $this->s__comision_datos['elemento'] . '|' . $this->s__comision_datos['comision'],
						"id_tramite_ref" => $nro_transaccion,
						"callback_url" => $callback_url,
					);

					try {
						$respuesta_SQ = cobro::generar_solicitud_consumo_externo($body);
						$token_SQ = $respuesta_SQ['token'];
						$this->set_token($token_SQ);
						guarani::act('act_inscripcion_cursadas')->actualizar_SQ_token($nro_transaccion, $token_SQ);
					} catch (Exception $e) {
						throw new guarani_error("Error al generar solicitud_consumo_externo en SQ:" . $e->getMessage());
						toba::logger()->error('Error al generar solicitud_consumo_externo en SQ: ' . $e->getMessage());
					}
				}
			}
			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}

		return $res;
	}

	function get_datos_impresion()
	{
		return $this->s__datos_impresion;
	}
	
	//--------------------------------------------------------------------------
	//---- Validaciones 
	//--------------------------------------------------------------------------
	
	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		return $retorno;
	}
	
	protected function guardar_retorno_ptos_control($momento_orden, $retorno)
	{
		$this->s__retornos_ptos_control[$momento_orden] = $retorno;
	}
	
	protected function get_peor_estado_retorno_ptos_control()
	{
		foreach ($this->s__retornos_ptos_control as $retorno) {
			if ($retorno == guarani_punto_de_control::advertido) {
				return guarani_punto_de_control::advertido;
			}
		}
		return guarani_punto_de_control::autorizado;
	}
	
	protected function validaciones()
	{
		if (inscripcion_cursada::ejecutar_controles($this->s__propuesta)) {
			$ptos_control_a_validar = array(
				guarani_punto_de_control::alumno_entidad,
				guarani_punto_de_control::persona_operacion,
				guarani_punto_de_control::alumno_operacion, 
				guarani_punto_de_control::alumno_actividad,
				guarani_punto_de_control::alumno_comision,
				guarani_punto_de_control::alumno_anio_academico,
			);

			$param = array(	
				'persona'			=> $this->s__persona,
				'fecha'				=> guarani_fecha::get_hoy(),
				'alumno'			=> $this->s__alumno,
				'propuesta'			=> $this->s__propuesta,
				'plan_version'		=> $this->s__plan_version,
				'comision'			=> $this->s__comision_datos['comision'],
				'elemento'			=> $this->s__comision_datos['elemento'],
				'anio_academico'	=> $this->s__comision_datos['anio_academico']
			);
	
			$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $param);

			if ($retorno == guarani_punto_de_control::rechazado) {
				throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
			}

			$this->guardar_retorno_ptos_control('persona', $retorno);
		}
		
		// La validación de instancias se realiza independientemente del parámetro 'cur_ejecuta_controles'
		$this->validar_instancias();
	}

	protected function validar_instancias()
	{
		// Validación de Instancias
		// Determina a qué instancias se inscribe y con qué estado

		$this->s__comision_datos['instancias_a_inscribir'] = array();
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($this->s__comision);

		$estado_inscripcion = guarani_punto_de_control::autorizado;
		foreach ($instancias as $instancia) {
			$ptos_control_a_validar = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia['instancia']);
			$params = array(
				'alumno' => $this->s__alumno,
				'elemento' => $this->s__comision_datos['elemento'],
				'fecha'	=> guarani_fecha::get_hoy(),
				'plan_version' => $this->s__plan_version
			);

			$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

			if ($retorno != guarani_punto_de_control::rechazado) {
				$this->s__comision_datos['instancias_a_inscribir'][] = $instancia;
			}
			// Se guarda el peor estado acumulado de las no rechazadas
			if ($retorno == guarani_punto_de_control::advertido) {
				$estado_inscripcion = $retorno;
			}
		}
		
		$this->guardar_retorno_ptos_control('final', $estado_inscripcion);
		
		$this->s__comision_datos['estado_inscripcion'] = inscripcion_cursada::get_estado_equivalente($this->get_peor_estado_retorno_ptos_control());
	}
}