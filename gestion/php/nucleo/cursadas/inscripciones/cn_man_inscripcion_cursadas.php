<?php
class cn_man_inscripcion_cursadas extends cn_manejador_entidad
{
	protected $s__estado = 'NUEVA';  // Estados internos posibles de la Inscripción: NUEVA | CONFIRMADA | ERROR
	protected $s__retornos_ptos_control = array();
	protected $s__fuera_de_termino = false;
	protected $s__nro_transaccion;
	protected $s__estado_inscripcion;
	protected $s__fecha_transaccion;
	protected $s__alumno;
	protected $s__alumno_datos;
	protected $s__actividad;
	protected $s__comisiones;
	protected $s__comision_prioridad_1;
	protected $s__subcomisiones;
	protected $s__comisiones_disponibles;
	protected $s__subcomisiones_disponibles;
	protected $s__autorizacion;
	protected $s__atrib_propuestas; // Propuestas utilizadas como atributos para resolver parámetros
	
	function cargar_atributos_parametros($propuesta = null, $plan = null)
	{
		$operacion = guarani::get_id_operacion();
		
		$this->s__atrib_propuestas = array($propuesta);
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $this->s__atrib_propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($plan));
	}
	
	//--------------------------------------------------------------------------
	//---- SETTERS -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Setea la inscripción como fuera de término
	 */
	function set_fuera_de_termino($estado = true)
	{
		$this->s__fuera_de_termino = $estado;
	}

	/**
	 * Setea el alumno que se inscribirá a cursar.
	 */
	function set_alumno($alumno)
	{
		$this->validar_alumno($alumno);
		$this->s__alumno = $alumno;
		$datos = guarani::co('co_alumnos')->get_datos_basicos_alumno($this->s__alumno);
		$this->s__alumno_datos = $datos;
	}

	/**
	 * Setea la actividad a la cual se inscribirá a cursar.
	 */
	function set_actividad($actividad)
	{
		$this->validar_actividad($actividad);
		$comisiones_disponibles = $this->get_comisiones_disponibles($actividad);
		if ($this->get_cant_comisiones_disponibles() == 0) {
			throw guarani::error('No hay comisiones disponibles para el alumno y actividad seleccionados.');
		}
		$this->s__actividad = $actividad;
	}

	/**
	 * Setea las comisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	protected function set_comisiones_disponibles($comisiones)
	{
		$this->s__comisiones_disponibles = $comisiones;
	}

	/**
	 * Setea las subcomisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	protected function set_subcomisiones_disponibles($subcomisiones)
	{
		$this->s__subcomisiones_disponibles = $subcomisiones;
	}

	/**
	 * Setea las comisiones elegidas para inscribirse para el alumno y actividad elegidas
	 */
	function set_comisiones($comisiones)
	{
		$comisiones = $this->purgar_comisiones($comisiones);
		$this->validar_comisiones($comisiones);
		$comisiones = rs_ordenar_por_columna($comisiones, 'prioridad');
		// Si es necesario se reenumeran las prioridades
		if ($comisiones && $comisiones[guarani_arreglos::count($comisiones) - 1]['prioridad'] > guarani_arreglos::count($comisiones)) {
			foreach ($comisiones as $key => $comision) {
				$comisiones[$key]['prioridad'] = $key + 1;
			}
		}
		$this->s__comisiones = $comisiones;
	}

	/**
	 * Setea las subcomisiones elegidas para inscribirse para el alumno y actividad elegidas
	 */
	function set_subcomisiones($subcomisiones)
	{
		$this->s__subcomisiones = $subcomisiones;
		$this->validar_subcomisiones($subcomisiones);
	}

	/**
	 * Setea el texto de la autorización para las inscripciones fuera de término.
	 */
	function set_autorizacion($autorizacion)
	{
		$this->s__autorizacion = $autorizacion;
	}

	function deseleccionar_alumno()
	{
		$this->s__alumno = null;
		$this->s__alumno_datos = null;
		$this->deseleccionar_actividad();
		unset($this->s__retornos_ptos_control['alumno']);
	}

	function deseleccionar_actividad()
	{
		$this->s__actividad = null;
		$this->s__comisiones_disponibles = null;
		$this->deseleccionar_comisiones();
		unset($this->s__retornos_ptos_control['actividad']);
	}

	function deseleccionar_comisiones()
	{
		$this->s__comisiones_disponibles = null;
		$this->s__comisiones = null;
		$this->s__comision_prioridad_1 = null;
		$this->deseleccionar_subcomisiones();
		unset($this->s__retornos_ptos_control['comision']);
	}

	function deseleccionar_subcomisiones()
	{
		$this->s__subcomisiones_disponibles = null;
		$this->s__subcomisiones = null;
		$this->s__autorizacion = null;
		$this->deseleccionar_final();
		unset($this->s__retornos_ptos_control['subcomision']);
	}

	function deseleccionar_final()
	{
		unset($this->s__retornos_ptos_control['final']);
	}

	function deseleccionar_todo()
	{
		$this->deseleccionar_alumno();
	}

	protected function purgar_comisiones($comisiones)
	{
		// Se eliminan las comisiones no seleccionadas
		foreach ($comisiones as $key => $comision) {
			if (!isset($comision['prioridad']) || !is_numeric($comision['prioridad'])) {
				unset($comisiones[$key]);
			}
		}
		return $comisiones;
	}

	//--------------------------------------------------------------------------
	//---- GETTERS -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function entidad($id = null)
	{
		return $this;
	}

	function etiquetas($concepto = null)
	{
		if (!isset($this->etiquetas[$concepto])) {
			$this->etiquetas[$concepto] = guarani_etiquetas::get_concepto($concepto);
		}
		return $this->etiquetas[$concepto];
	}

	function es_fuera_de_termino()
	{
		return ($this->get_fuera_de_termino() == comunes::si);
	}

	function get_indice_instancia()
	{
		return 'inscripcion_cursada';
	}

	function get_clase_consulta()
	{
		return 'co_inscripciones_cursadas';
	}

	function get_estado()
	{
		return $this->s__estado;
	}

	function get_fuera_de_termino()
	{
		if ($this->s__fuera_de_termino) {
			return comunes::si;
		} else {
			return comunes::no;
		}
	}
	
	function get_alumno_seleccionado()
	{
		return $this->s__alumno;
	}

	/**
	 * Retorna los alumnos activos
	 */
	function get_alumnos($filtro = null)
	{
		
		// Versiones de plan en estado Activo
		$where = " sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')";
		if ($filtro) {
			$where .= " AND $filtro";
		}
		return guarani::co('co_alumnos')->get_alumnos_activos_con_insc_aceptada_pendiente($where);
	}

	function hay_actividades_con_comisiones_vigentes()
	{
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		return toba::consulta_php('co_inscripciones_cursadas')->hay_actividades_con_comisiones_vigentes(
                    $this->s__alumno_datos['propuesta'],
                    $this->s__alumno_datos['plan'],
                    $this->s__alumno_datos['plan_version'],
                    $this->s__alumno_datos['ubicacion'],
                    $this->s__alumno_datos['alumno'],
                    $param_cursa_en_cualquier_ubicacion,
                    comunes::interfaz_gestion
                );
	}

	/**
	 * Retorna las actividades disponibles para que se inscriba el alumno.
	 */
	function get_actividades_disponibles($filtro = null)
	{
		$alumno = $this->s__alumno;
		$propuesta = $this->s__alumno_datos['propuesta'];
		$plan = $this->s__alumno_datos['plan'];
		$plan_version = $this->s__alumno_datos['plan_version'];
		$ubicacion = $this->s__alumno_datos['ubicacion'];

		$fuera_de_termino = $this->s__fuera_de_termino;

		$param_ingresantes_oferta_actividades = guarani::parametros()->get_valor('cur_ingresantes_oferta_actividades');
		$param_filtro_actividades_con_comision = guarani::parametros()->get_valor('cur_filtro_actividades_con_comision');
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		$param_recursa_regularizadas = guarani::parametros()->get_valor('cur_recursar_regularizadas');

		return $this->co()->get_actividades_disponibles_inscripcion($param_ingresantes_oferta_actividades, $param_filtro_actividades_con_comision, $param_cursa_en_cualquier_ubicacion, $param_recursa_regularizadas, $alumno, $propuesta, $plan, $plan_version, $ubicacion, $filtro, $fuera_de_termino);
	}

	/**
	 * Retorna las comisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	function get_comisiones_disponibles($actividad = null)
	{
		if ($this->s__comisiones_disponibles) {
			return $this->s__comisiones_disponibles;
		}

		$alumno = $this->s__alumno;
		$elemento = ($actividad) ? $actividad : $this->s__actividad;
		$propuesta = $this->s__alumno_datos['propuesta'];
		$plan = $this->s__alumno_datos['plan'];
		$plan_version = $this->s__alumno_datos['plan_version'];
		$ubicacion = $this->s__alumno_datos['ubicacion'];
		$inicial_apellido = $this->s__alumno_datos['inicial_apellido'];
		$fuera_de_termino = $this->s__fuera_de_termino;

		$param_usa_subcomisiones = guarani::parametros()->get_valor('cur_usa_subcomisiones');
		$param_cursa_en_cualquier_ubicacion = guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion');
		$param_filtra_rango_apellido_comision = guarani::parametros()->get_valor('cur_filtra_rango_apellido_comision');

		$comisiones = guarani::co('co_comisiones')->get_comisiones_disponibles_para_inscripcion($param_usa_subcomisiones, $param_cursa_en_cualquier_ubicacion, $param_filtra_rango_apellido_comision, $alumno, $elemento, $propuesta, $plan, $plan_version, $ubicacion, $inicial_apellido, $fuera_de_termino);
		$this->set_comisiones_disponibles($comisiones);
		return $comisiones;
	}

	/**
	 * Retorna las subcomisiones disponibles para inscribirse para el alumno y actividad elegidas
	 */
	function get_subcomisiones_disponibles()
	{
		if ($this->s__subcomisiones_disponibles) {
			return $this->s__subcomisiones_disponibles;
		}
		$inicial_apellido = $this->s__alumno_datos['inicial_apellido'];

		$filtro = '';

		$filtro .= guarani_sql::armar_condicion_in($this->s__comisiones, 'sga_subcomisiones', 'comision');
		$filtro .= " AND sga_subcomisiones.inscripcion_habilitada = 'S'";

		$filtra_rango_apellido = (guarani::parametros()->get_valor('cur_filtra_rango_apellido_subco') == comunes::si);
		if ($filtra_rango_apellido) {
			$inicial_apellido = toba::db()->quote($inicial_apellido);
			$filtro .= " AND (($inicial_apellido BETWEEN sga_subcomisiones.letra_desde AND sga_subcomisiones.letra_hasta)
							OR (sga_subcomisiones.letra_desde IS NULL AND sga_subcomisiones.letra_hasta IS NULL))";
		}

		$subcomisiones = guarani::co('co_comisiones')->get_subcomisiones($filtro);
		$this->set_subcomisiones_disponibles($subcomisiones);
		return $subcomisiones;
	}

	function maneja_alternativas()
	{
		$max = $this->get_alternativa_maxima();
		return ($max > 1);
	}

	function get_alternativa_maxima()
	{
		$datos = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($this->s__actividad);
		return ($datos['insc_cursada_por_prioridad'] == comunes::si) ? $datos['prioridad_cant_maxima'] : 1;
	}

	/**
	 * Determina si hay alguna comisión de las seleccionadas que tenga subcomisiones
	 */
	function hay_subcomisiones()
	{
		if (!$this->s__comisiones) {
			return false;
		}
		foreach ($this->s__comisiones as $comision) {
			if (isset($comision['subcomisiones_cant']) && $comision['subcomisiones_cant'] > 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determina si la comisión recibida está entre las elegidas y devuelve la prioridad de la misma.
	 */
	function get_prioridad_comision($comision)
	{
		if ($this->s__comisiones) {
			foreach ($this->s__comisiones as $key => $_comision) {
				if ($_comision['comision'] == $comision) {
					return $_comision['prioridad'];
				}
			}
		}
		return 0;
	}
	
	/**
	 * Determina si la comisión recibida está entre las elegidas y devuelve el código de validación que
	 * se había cargado para la misma.
	 */
	function get_codigo_validacion_comision($comision)
	{
		if ($this->s__comisiones) {
			foreach ($this->s__comisiones as $key => $_comision) {
				if ($_comision['comision'] == $comision) {
					return $_comision['codigo_validacion'];
				}
			}
		}
		return '';
	}

	/**
	 * Retorna la cantidad de comisiones disponibles para el alumno / actividad seteados
	 */
	function get_cant_comisiones_disponibles()
	{
		return guarani_arreglos::count($this->s__comisiones_disponibles);
	}

	/**
	 * Determina si la subcomisión recibida está entre las elegidas.
	 */
	function es_subcomision_elegida($subcomision)
	{
		if ($this->s__subcomisiones) {
			return in_array($subcomision, aplanar_matriz($this->s__subcomisiones, 'subcomision'));
		}
		return false;
	}

	/**
	 * Retorna las comisiones elegidas.
	 */
	function get_comisiones()
	{
		return $this->s__comisiones;
	}

	/**
	 * Retorna las subcomisiones elegidas.
	 */
	function get_subcomisiones()
	{
		return $this->s__subcomisiones;
	}

	/**
	 * Retorna las subcomisiones elegidas para una comisión.
	 */
	function get_subcomisiones_elegidas_comision($comision)
	{
		$subcomisiones = array();
		if ($this->s__subcomisiones) {
			foreach ($this->s__subcomisiones as $subcomision) {
				if ($subcomision['comision'] == $comision) {
					$subcomisiones[] = $subcomision['subcomision'];
				}
			}
		}
		return $subcomisiones;
	}

	/**
	 * Retorna las instancias configuradas para la comisión recibida.
	 */
	function get_comision_instancias($comision)
	{
		return guarani::co('co_comisiones')->get_instancias_comision($comision);
	}

	/**
	 * Retorna el texto de la autorización para las inscripciones fuera de término.
	 */
	function get_autorizacion()
	{
		return $this->s__autorizacion;
	}

	/**
	 * Retorna los puntos de control a validar para la instancia recibida.
	 */
	function get_puntos_de_control_instancia($instancia)
	{
		return toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
	}

	/**
	 * Devuleve el Nro. de Transacción correspondiente al id de la inscricpión recibida.
	 */
	function get_nuevo_nro_transaccion()
	{
		return $this->co()->get_nuevo_nro_transaccion();
	}

	function get_nro_transaccion()
	{
		return $this->s__nro_transaccion;
	}

	/**
	 * Esta función se utiliza desde el ci_nav para recuperar la descripción asociada al estado en que se realizó la
	 * inscripción. Sólo se invocará en aquellos casos en que la inscripción finalizó, es decir cuando no fue rechazada.
	 */
	function get_estado_inscripcion_visual()
	{
		if (isset($this->s__estado_inscripcion) && !is_null($this->s__estado_inscripcion)) {
			return inscripcion_cursada::get_estado_descripcion($this->s__estado_inscripcion);
		}
	}

	function get_fecha_inscripcion()
	{
		return $this->s__fecha_transaccion;
	}

	//--------------------------------------------------------------------------
	//---- VALIDACIONES --------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function validar_alumno($alumno)
	{
		$datos_alumno = current(guarani::co('co_alumnos')->get_datos_alumno($alumno));

		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_entidad, guarani_punto_de_control::persona_operacion, guarani_punto_de_control::alumno_operacion, guarani_punto_de_control::persona_propuesta);
		$param = array(
			'alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $datos_alumno['plan_version'],
			'propuesta' => $datos_alumno['propuesta']
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $param);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->deseleccionar_alumno();
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('alumno', $retorno);
	}

	protected function validar_actividad($actividad)
	{
		if (!$this->s__alumno) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('algun Alumno'));
			throw guarani::error($mje);
		}

		$pto_control_a_validar = guarani_punto_de_control::alumno_actividad;
		$params = array(
			'alumno' => $this->s__alumno_datos['alumno'],
			'elemento' => $actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $this->s__alumno_datos['plan_version']
		);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);

		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->deseleccionar_actividad();
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}

		$this->guardar_retorno_ptos_control('actividad', $retorno);
	}

	function get_codigo_inscripcion_comision($id)
	{
		$codigo = null;
		foreach ($this->s__comisiones_disponibles as $comision) {
			if ($comision['comision'] == $id) {
				$codigo = $comision['inscripcion_cerrada_codigo'];
				break;
			}
		}
		return $codigo;
	}
	
	protected function validar_comisiones($comisiones)
	{
		if (!$this->s__actividad) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('alguna Actividad'));
			throw guarani::error($mje);
		}

		if (guarani_arreglos::count($comisiones) == 0) {
			throw guarani::error("No hay ninguna Comisión seleccionada.");
		}

		// Se valida que todas las seleccionadas pertenezcan al mismo período lectivo.
		foreach ($comisiones as $key => $comision) {
			if (!isset($periodo_lectivo)) {
				$periodo_lectivo = $comision['periodo_lectivo'];
				continue;
			}
			if ($comision['periodo_lectivo'] != $periodo_lectivo) {
				$mje = guarani_mensajes::get('800CUR_comisiones_igual_periodo_lectivo');
				throw guarani::error($mje);
			}
		}

		// Se verifica que no haya duplicidad de prioridades
		$anio_acad = null;
		$prioridades_usadas = array();
		foreach ($comisiones as $key => $comision) {
			if (in_array($comision['prioridad'], $prioridades_usadas)) {
				throw guarani::error("Hay duplicidad de prioridades.");
			}
			$prioridades_usadas[] = $comision['prioridad'];
			if ($comision['prioridad'] == 1) {
				$this->s__comision_prioridad_1 = $comision['comision'];
				$anio_acad = $comision['anio_academico'];
			}
		}

		// Se valida que para las Comisiones con inscripción cerrada, se haya ingresado correctamente el
		// código de validación de la Comisión.
		foreach ($comisiones as $comision) {
			if ($comision['inscripcion_cerrada_desc'] == comunes::si_desc) {
				$cod_introducido = $comision['codigo_validacion'];
				$cod_comision = $comision['inscripcion_cerrada_codigo'];
				if (empty($cod_introducido) || ($cod_introducido != $cod_comision)) {
					$mje = guarani_mensajes::get('800CUR_insc_cursada_codigo_inv', array($comision['comision_nombre']));
					throw guarani::error($mje);
				}
			}
		}	
		
		// Cantidad máxima de inscripciones por actividad y período lectivo.
		$cant_inscripciones = $this->co()->get_cant_inscripciones_actividad_periodo_lectivo($this->s__alumno, $this->s__actividad, $periodo_lectivo);
		$cant_max_inscripciones = guarani::parametros()->get_valor('cur_cant_max_insc_igual_actividad_periodo_lectivo');
		if ($cant_inscripciones >= $cant_max_inscripciones) {
			$nombre_actividad = toba::consulta_php('co_elementos')->get_nombre_actividad($this->s__actividad);
			$mje = guarani_mensajes::get('800CUR_max_inscripcion_excedida', array($nombre_actividad), false);
			throw guarani::error($mje);
		}

		// Se valida primero el punto de control 8 (Alumno - Año Académico Reinscripción)
		// que requiere el año académico del período lectivo y debe ser el mismo para todas las comisiones.
		$pto_control_a_validar = guarani_punto_de_control::alumno_anio_academico;
		$retorno_comisiones = guarani_punto_de_control::autorizado;
		$params = array(
			'alumno' => $this->s__alumno,
			'anio_academico' => $anio_acad,
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $this->s__alumno_datos['plan_version']
		);
		$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);
		if ($retorno == guarani_punto_de_control::rechazado) {
			$this->deseleccionar_comisiones();
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		} elseif ($retorno == guarani_punto_de_control::advertido) {
			$retorno_comisiones = guarani_punto_de_control::advertido;
		}

		// Se valida el punto de control 6 (Alumno - Comisión) para todas las comisiones a las que se asignó prioridad.
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_comision);
		$params = array(
			'alumno' => $this->s__alumno,
			'elemento' => $this->s__actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $this->s__alumno_datos['plan_version']
		);
		foreach ($comisiones as $comision) {
			$params['comision'] = $comision['comision'];
			$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
			if ($retorno == guarani_punto_de_control::rechazado) {
				$this->deseleccionar_comisiones();
				throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
			} elseif ($retorno == guarani_punto_de_control::advertido) {
				$retorno_comisiones = guarani_punto_de_control::advertido;
			}
		}

		$this->guardar_retorno_ptos_control('comision', $retorno_comisiones);
	}

	protected function validar_subcomisiones($subcomisiones)
	{
		// Se controla que se haya seleccionado una subcomisión por cada uno
		// de los tipos que haya disponibles en la comisión.
		// Se determinan los distintos tipos por comision disponibles
		$tipos_disponibles = array();
		foreach ($this->s__subcomisiones_disponibles as $subcomision) {
			if (!isset($tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']])) {
				$tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']] = 0;
			}
		}
		// Se determinan los distintos tipos por comision seleccionados
		if ($subcomisiones) {
			foreach ($subcomisiones as $subcomision) {
				$tipos_disponibles[$subcomision['comision']][$subcomision['tipo_clase']] ++;
			}
		}
		// Se determina si faltó seleccionar alguno o si se seleccionó más de uno de algún tipo.
		foreach ($tipos_disponibles as $comision => $tipos_clases) {
			foreach ($tipos_clases as $tipo_clase => $valor) {
				if ($valor == 0) {
					throw guarani::error('Debe seleccionar una opción por cada tipo de clase disponible por comisión.');
				}
				if ($valor > 1) {
					throw guarani::error('Debe seleccionar sólo una opción por cada tipo de clase disponible por comisión.');
				}
			}
		}

		// Se valida el punto de control 7 (Alumno - Subcomisión) para todas las subcomisiones de las comisiones a las que se asignó prioridad.
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_subcomision);
		$params = array(
			'alumno' => $this->s__alumno,
			'elemento' => $this->s__actividad,
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $this->s__alumno_datos['plan_version']
		);
		$retorno_subc = guarani_punto_de_control::autorizado;
		foreach ($this->s__comisiones as $comision) {
			$params['comision'] = $comision['comision'];
			$subcomisiones = $this->get_subcomisiones_elegidas_comision($comision['comision']);
			if ($subcomisiones != array()) {
				foreach ($subcomisiones as $subcomision) {
					$params['subcomision'] = $subcomision;
					$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
					if ($retorno == guarani_punto_de_control::rechazado) {
						$this->deseleccionar_subcomisiones();
						throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
					}
					if ($retorno == guarani_punto_de_control::advertido) {
						$retorno_subc = guarani_punto_de_control::advertido;
					}
				}
			}
		}

		$this->guardar_retorno_ptos_control('subcomisiones', $retorno_subc);
	}

	protected function validar_instancias()
	{
		// Validación de Instancias. Determina a qué instancias se inscribe y con qué estado
		$instancia_mandatoria = guarani::parametros()->get_valor('cur_instancia_mandatoria');

		foreach ($this->s__comisiones as $key => $comision) {
			$this->s__comisiones[$key]['instancias_a_inscribir'] = array();
			if (!isset($this->s__comisiones[$key]['instancias'])) {
				$this->s__comisiones[$key]['instancias'] = $this->get_comision_instancias($comision['comision']);
			}

			$estado_inscripcion = guarani_punto_de_control::autorizado;
			foreach ($this->s__comisiones[$key]['instancias'] as $instancia) {
				// Sólo valido puntos de control en la comisión de prioridad #1
				if ($comision['prioridad'] == 1) {
					$ptos_control_a_validar = $this->get_puntos_de_control_instancia($instancia['instancia']);
					$params = array(
						'alumno' => $this->s__alumno,
						'elemento' => $this->s__actividad,
						'fecha' => guarani_fecha::get_hoy(),
						'plan_version' => $this->s__alumno_datos['plan_version']
					);

					$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);

					if ($retorno == guarani_punto_de_control::rechazado) {
						if ($instancia['instancia'] == $instancia_mandatoria) { 
							// Si es mandatoria aborta inscripcion
							throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
						}
					} else {
						$this->s__comisiones[$key]['instancias_a_inscribir'][] = $instancia;
					}
					// Se guarda el peor estado acumulado de las no rechazadas
					if ($retorno == guarani_punto_de_control::advertido) {
						$estado_inscripcion = $retorno;
					}
				} else {
					$this->s__comisiones[$key]['instancias_a_inscribir'][] = $instancia;
				}
			}
			$this->guardar_retorno_ptos_control('final', $estado_inscripcion);

			$this->s__comisiones[$key]['estado_inscripcion'] = $this->maneja_alternativas() ? inscripcion_cursada::estado_pendiente : inscripcion_cursada::get_estado_equivalente($this->get_peor_estado_retorno_ptos_control());
		}
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS -------------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		if (!inscripcion_cursada::ejecutar_controles($this->s__atrib_propuestas)) {
			$retorno = guarani_punto_de_control::advertido;
		} else {
			$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params)->controlar();
			guarani::validador_puntos_de_control()->notificar();
		}
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

	/*
	 * Sincroniza en la base de datos la inscripción correspondiente del alumno en la 
	 * actividad a las comisiones y sus instancias y a las subcomisiones seleccionadas
	 */
	function guardar()
	{
		$this->validar_instancias();
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db('guarani')->abrir_transaccion();
			$nro_transaccion = $this->get_nuevo_nro_transaccion();
			$maneja_alternativas = $this->maneja_alternativas();
			foreach ($this->s__comisiones as $key => $comision) {
				if ($maneja_alternativas) {
					$comision['tipo'] = inscripcion_cursada::tipo_preinscripcion;
				} else {
					$comision['tipo'] = inscripcion_cursada::tipo_inscripcion;
				}
				$comision['subcomisiones'] = $this->get_subcomisiones_elegidas_comision($comision['comision']);
				$result = $this->guardar_inscripcion($comision, $nro_transaccion);
				
				// Si da error salgo
				if ($result['res'] < 1) {
					// Si da error salgo
					$mje = guarani_mensajes::get($result['msg_indice'], $result['pars']);
					throw guarani::error($mje);
				}
				
				// Si hay que notificar al alumno
				if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si) {
					
					$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones("vw_insc_cursada.nro_transaccion = {$nro_transaccion}");
					$fila = current($datos);
					$parametros['estado'] = (isset($fila['estado']))? $fila['estado'] : null;
					$parametros['nombre_actividad'] = (isset($fila['actividad_nombre']))? $fila['actividad_nombre'] : null;
					$parametros['nombre_comision'] = (isset($fila['comision_nombre']))? $fila['comision_nombre'] : null;
					
					if (isset($fila['persona'])) {
						$parametros['persona'] = $fila['persona'];
						$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas([$fila['persona']]);
					} else {
						$parametros['persona'] = null;
						$parametros['email'] = null;
					}
					
					$parametros['nombre_alumno'] = (isset($fila['nombres']))? $fila['nombres'] : null;
					guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
					
				}
				
				// Datos que se mantienen para la impresión
				if (!$this->s__fecha_transaccion) {
					$datos_inscripcion = current($this->co()->get_datos_inscripcion($nro_transaccion));
					$this->s__fecha_transaccion = $datos_inscripcion['fecha_inscripcion_visual'];
					$this->s__estado_inscripcion = $datos_inscripcion['estado'];
				}
				$this->s__comisiones[$key]['fecha_de_inscripcion'] = $this->s__fecha_transaccion;
				$this->s__comisiones[$key]['nro_transaccion'] = $nro_transaccion;
				$this->s__comisiones[$key]['subcomisiones'] = $comision['subcomisiones'];
			}

			toba::db('guarani')->cerrar_transaccion();
			$this->s__nro_transaccion = $nro_transaccion;
			$this->s__estado = 'CONFIRMADA';
			$this->activar_transacciones_anidadas();
			$mje = guarani_mensajes::get($result['msg_indice'], $result['pars']);
			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (toba_error $e) {
			$this->s__estado = 'ERROR';
			toba::db('guarani')->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		} catch (guarani_error $e) {
			$this->s__estado = 'ERROR';
			toba::db('guarani')->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			$mje = guarani_mensajes::get('800_alta_inscripcion_error');
			throw guarani::error($mje);
			return;
		}
	}

	/**
	 * Sincronización con la BD
	 */
	protected function guardar_inscripcion($comision, $nro_transaccion)
	{
		// Se sincroniza inscripción
		$datos_inscripcion = array();
		$datos_inscripcion['comision'] = $comision['comision'];
		$datos_inscripcion['periodo_lectivo'] = $comision['periodo_lectivo'];
		$datos_inscripcion['alumno'] = $this->s__alumno;
		$datos_inscripcion['prioridad'] = $comision['prioridad'];
		$datos_inscripcion['tipo'] = $comision['tipo'];
		$datos_inscripcion['plan_version'] = $this->s__alumno_datos['plan_version'];
		$datos_inscripcion['estado'] = $comision['estado_inscripcion'];
		$datos_inscripcion['fuera_de_termino'] = $this->get_fuera_de_termino();
		$datos_inscripcion['autorizacion'] = $this->get_autorizacion();
		$datos_inscripcion['propuesta'] = $this->s__alumno_datos['propuesta'];
		$instancias = aplanar_matriz($comision['instancias_a_inscribir'], 'instancia');
		$subcomisiones = isset($comision['subcomisiones']) ? $comision['subcomisiones'] : array();

		$params_sistema = array(
			'cur_controlar_cupo' => guarani::parametros()->get_valor('cur_controlar_cupo'),
			'cur_controlar_cupo_por' => guarani::parametros()->get_valor('cur_controlar_cupo_por'),
			'cur_insc_siempre_pendiente' => guarani::parametros()->get_valor('cur_insc_siempre_pendiente'),
		);
		
		return guarani::act('act_inscripcion_cursadas')->guardar_inscripcion_a_comision_func($nro_transaccion, $datos_inscripcion, $instancias, $subcomisiones, false, comunes::interfaz_gestion, $params_sistema);
	}

	/**
	 * Se reinicializa la clase de negocio para quedar preparada para una nueva inscripción
	 */
	function resetear()
	{
		$this->deseleccionar_todo();
		$this->s__retornos_ptos_control = array();
		$this->s__nro_transaccion = null;
		$this->s__estado_inscripcion = null;
		$this->s__fecha_transaccion = null;
		$this->s__estado = 'NUEVA';
		$this->s__fuera_de_termino = false;
	}

}