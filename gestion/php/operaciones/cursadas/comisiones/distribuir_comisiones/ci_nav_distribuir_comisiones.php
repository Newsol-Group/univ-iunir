<?php
/**
	*
	*
	* @package        Cursadas
	* @subpackage    Comisiones
	* 
	*/
class ci_nav_distribuir_comisiones extends ci_nav_procesos_bk_wizzard
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $mje_confirma_copiado = '800CUR_confirmar_distribucion_alumnos';
	protected $mje_confirma_deshacer = '800CUR_confirmar_cancelar_distribucion';
	protected $mje_confirma_eliminacion_subcomision = '800CUR_quitar_alumno_subcomision';
	
	// Cupos
	protected $msj_confirmar_supera_cupos;
	
	// Filtros
	protected $s__filtro_alumnos;
	protected $s__limit;
	
	
	// Datos Filtros
	protected $s__datos_filtro_alumnos;
	protected $s__descr_filtros;
	
	// Selección de alumnos.
	protected $seleccion_alumnos_filtrada;
	
	// Distribución de alumnos.
	protected $s__seleccion_alumnos_temp;
	protected $s__datos_destino_temp;
	protected $s__comision_destino;
	protected $s__subcos_destino;
	
	// Componentes Visuales de información
	protected $ev_info;

	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(200);
	}

	//-----------------------------------------------------------------------------------

	function resetear_variables_filtro_alumnos()
	{
		$this->s__filtro_alumnos = '';
		unset($this->s__datos_filtro_alumnos);
		unset($this->s__descr_filtros);
	}

	function resetear_variables_dist_alumnos()
	{
		unset($this->s__seleccion_alumnos_temp);
		unset($this->s__datos_destino_temp);
		unset($this->s__comision_destino);
		unset($this->s__subcos_destino);
	}

	/**
		* Se resetean todas las variables de sesión de la clase. Utilizado en el evento volver al inicio de la operación.
		*/
	function resetear_variables()
	{
		$this->resetear_variables_filtro_alumnos();
		$this->resetear_variables_dist_alumnos();
		// Elimina las selecciones realizadas en todas las pantallas del wizard.
		$this->eliminar_seleccion_completa();
	}

	/*
		* Se maneja el avance de pantalla.
		*/
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'distribucion_alumnos') {
			$tipo_dist = $this->get_seleccion('tipo_distribucion');
			$datos_origen = array('datos_origen' => $this->s__descr_filtros);
			$alumnos_reubicacion = $this->get_seleccion('alumnos_distribuidos');
			// Si la distribución es para subcomisiones, debe realizarse un procesamiento de los alumnos para que no 
			// se repitan las inscripciones origen para cada subcomisión destino.
			if ($tipo_dist != comision::dist_entre_com) {
				$alumnos = current($alumnos_reubicacion);
				$inscripciones_distribuidas = array();
				$aux = array();
				// Si se distribuye entre las subcomisiones de la misma comision y se tiene el tipo de clase, se debe mantener el origen
				if ($tipo_dist['tipo_distribucion'] == comision::dist_com_subcom && isset($this->s__datos_filtro_alumnos['clase'])) {
					$origen = $this->get_seleccion_valor('alumnos_a_distribuir');
					$datos_origen['datos_origen']['alumnos'] = $this->s__datos_filtro_alumnos['alumnos'];
					foreach ($origen as $or) {
						$aux[$or['inscripcion']] = $or['subcomision'];
					}
				}
				foreach ($alumnos as $alumno) {
					if (!empty($aux)) {
						$inscripciones_distribuidas[$alumno['inscripcion']] = array('inscripcion' => $alumno['inscripcion'], 'destino' => $alumno['destino'], 'origen' => $aux[$alumno['inscripcion']]);
					} else {
						$inscripciones_distribuidas[$alumno['inscripcion']] = array('inscripcion' => $alumno['inscripcion'], 'destino' => $alumno['destino']);
					}
				}
				$alumnos_reubicacion = array('alumnos_distribuidos' => $inscripciones_distribuidas);
			}

			$parametros = array_merge($tipo_dist, $datos_origen, $alumnos_reubicacion, array('operacion' => guarani::get_id_operacion()));
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	/*
		* Se maneja el retroceso de pantalla para resetear variables de sesión que se mantienen más allá de la selección.
		*/
	function evt__cambiar_tab__anterior()
	{
		parent::evt__cambiar_tab__anterior();

		// Si se vuelve de la pantalla Selección de Alumnos se resetean los filtros y variables asociadas
		if ($this->s__pantalla_actual == 'seleccion_alumnos') {
			$this->resetear_variables_filtro_alumnos();
		}
		if ($this->s__pantalla_actual == 'distribucion_alumnos') {
			$this->resetear_variables_dist_alumnos();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA TIPO DISTRIBUCIÓN 
	//-----------------------------------------------------------------------------------

	function conf__tipo_distribucion(toba_ei_pantalla $pantalla)
	{
		// Se resetean atributos para resolución de parámetros
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_distribucion ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_distribucion(toba_ei_formulario $form)
	{
		if (($this->existe_seleccion('tipo_distribucion'))) {
			$datos = $this->get_seleccion_valor('tipo_distribucion');
			$form->set_datos($datos);
		}
	}

	function evt__form_distribucion__modificacion($datos)
	{
		switch ($datos['tipo_distribucion']) {
			case comision::dist_entre_com:
				$datos['tipo_distribucion_descr'] = comision::dist_entre_com_desc;
				break;
			case comision::dist_com_subcom:
				$datos['tipo_distribucion_descr'] = comision::dist_com_subcom_desc;
				break;
			case comision::dist_com_subcom_otra_com:
				$datos['tipo_distribucion_descr'] = comision::dist_com_subcom_otra_com_desc;
				break;
		}
		$this->set_seleccion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- SELECCION ALUMNOS 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_alumnos(toba_ei_pantalla $pantalla)
	{
		//Datos para el ev de información
		$datos = $this->get_seleccion('tipo_distribucion_descr');
		$this->ev_info()->set_datos($datos);
		$dist = $this->get_seleccion_valor('tipo_distribucion');
		if ($dist == comision::dist_com_subcom) {
			$pantalla->eliminar_dep('cuadro_alumnos');
		} else {
			$pantalla->eliminar_dep('cuadro_alumnos_subcom');
		}
		//Si se filtró por alumnos se limpia la selección de las mismas.
		if ($this->seleccion_alumnos_filtrada) {
			$this->eliminar_seleccion_previa();
		}
		
		// Se resetean atributos para resolución de parámetros
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- formulario de filtro sobre inscripciones -------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_dist_alumnos_comisiones(toba_ei_formulario $form)
	{
		$ubicacion = $form->ef('ubicacion')->get_etiqueta();
		$form->ef('comision')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($ubicacion)));
		$distribucion = $this->get_seleccion_valor('tipo_distribucion');
		
		// Si la distribución es entre Subcomisiones de la misma u otra Comisión, el filtro por Comisión es obligatorio.
		if ($distribucion == comision::dist_com_subcom) {
			$form->ef('alumnos')->set_obligatorio();
		} else {
			$form->desactivar_efs(array('alumnos', 'clase'));
		}

		if ($distribucion == comision::dist_com_subcom || $distribucion == comision::dist_com_subcom_otra_com) {
			$form->ef('ubicacion')->set_obligatorio();
			$form->ef('comision')->set_obligatorio();
		}
		if (isset($this->s__datos_filtro_alumnos)) {
			$form->set_datos_defecto($this->s__datos_filtro_alumnos);
		}
	}

	function evt__form_filtro_dist_alumnos_comisiones__filtrar($datos)
	{
		$this->s__filtro_alumnos = '';
		if (isset($datos)) {
			// Se arma la condición where para los filtros sobre las Inscripciones según los datos ingresados en el
			// formulario y las tablas a las que los mismos afectan.
			$this->s__datos_filtro_alumnos = $datos;
			
			// Filtros sobre las Comisiones. El año académico no se considera porque filtra a los períodos lectivos que se 
			// muestran y ambos campos son obligatorios.
			$periodo_lectivo = toba::db()->quote($datos['periodo_lectivo']);
			$this->s__filtro_alumnos .= " sga_comisiones.periodo_lectivo = $periodo_lectivo";

			$actividad = toba::db()->quote($datos['actividad']);
			$this->s__filtro_alumnos .= " AND sga_comisiones.elemento = $actividad";

			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']);
				$this->s__filtro_alumnos .= " AND sga_comisiones.ubicacion = $ubicacion";
			}

			if (isset($datos['comision'])) {
				$comision = toba::db()->quote($datos['comision']);
				$this->s__filtro_alumnos .= " AND sga_comisiones.comision = $comision";
			}

			if (isset($datos['turno'])) {
				$turno = toba::db()->quote($datos['turno']);
				$this->s__filtro_alumnos .= " AND sga_comisiones.turno = $turno";
			}

			if (isset($datos['modalidad'])) {
				$modalidad = toba::db()->quote($datos['modalidad']);
				$this->s__filtro_alumnos .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = $modalidad)";
			}

			if (isset($datos['catedra'])) {
				$catedra = guarani_texto::limpiar_acentos($datos['catedra']);
				$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
				$this->s__filtro_alumnos .= ' AND '.$filtro_condicion->get_sql('f_limpiar_acentos(sga_catedras.nombre::varchar)', $catedra);
			}

			if (isset($datos['alumnos']) == comision::alumno_con_subcomision && isset($datos['clase'])) {
				$clase = toba::db()->quote($datos['clase']);
				$this->s__filtro_alumnos .= " AND sga_clases_tipos.tipo_clase = $clase";
			}
			
			if (isset($datos['apellido_desde'])) {
				$apellido_desde =$datos['apellido_desde']||'%';
				$apellido_desde = toba::db()->quote($datos['apellido_desde']);
				$this->s__filtro_alumnos .= "AND vw_personas.apellido >= $apellido_desde";
			}

			if (isset($datos['apellido_hasta'])) {
				$apellido_hasta =$datos['apellido_hasta']||'%';
				$apellido_hasta = toba::db()->quote($datos['apellido_hasta']);
				var_dump($apellido_hasta) ;
				$this->s__filtro_alumnos .= "AND vw_personas.apellido <= $apellido_hasta";
			}

			if (isset($datos['propuesta'])) {
				$propuesta = toba::db()->quote($datos['propuesta']);
				$this->s__filtro_alumnos .= "AND sga_propuestas.propuesta= $propuesta";
			}

			if (isset($datos['cant_registros'])) {
				$cant_registros = toba::db()->quote($datos['cant_registros']);
				$this->s__limit =  "limit $cant_registros";
				var_dump($this->s__limit );
			}

			$this->set_seleccion($datos);

			// Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_alumnos_filtrada = true;
			$this->s__descr_filtros = $this->get_datos_filtros_inscripciones();
		} else {
			unset($this->s__datos_filtro_alumnos);
			$this->s__filtro_alumnos = '';
			unset($this->s__descr_filtros);
		}
	}

	function evt__form_filtro_dist_alumnos_comisiones__cancelar()
	{
		unset($this->s__datos_filtro_alumnos);
		$this->s__filtro_alumnos = '';
		unset($this->s__descr_filtros);
	}

	function get_ubicaciones()
	{
		$datos = guarani::co('co_ubicaciones')->get_ubicaciones();
		if (guarani_arreglos::count($datos) == 1) {
			return $datos;
		} else {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'));
			return array_merge($no_seleccion, $datos);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_alumnos) && !empty($this->s__filtro_alumnos)) {
			$indx_msg .= '_filtrado';
			$filtro = $this->s__filtro_alumnos;
			$limit = $this->s__limit;
			// Se verifica que las comisiones cuyos alumnos se van a distribuir no tengas Actas de Cursada no anuladas.
			$where_sin_actas = "NOT EXISTS (SELECT    sga_actas.id_acta
											FROM    sga_actas
											WHERE    sga_actas.comision = sga_comisiones.comision
											AND        sga_actas.estado <> '" . acta::estado_anulada . "')";

			$filtro = (isset($filtro) && !empty($filtro)) ? $filtro . ' AND ' . $where_sin_actas : $where_sin_actas;
			$comisiones = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones($filtro, $limit);

			// Se arma la descripción del corte de control del cuadro.
			foreach ($comisiones as $clave => $comision) {
				$descr = 'Comisión: ' . $comision['comision_nombre'] . ' <br> ' .
						'Turno: ' . $comision['turno_nombre'] . ' | ' .
						'Ubicación: ' . $comision['ubicacion_nombre'] . ' | ' .
						'Subcomisiones:' . $comision['cant_subcomisiones'];
				if (isset($comision['docentes'])) {
					$descr .= ' | Docentes: ' . $comision['docentes'];
				}
				$descr .= ' | Cupo / Insc: ';
				$descr .= (isset($comision['cupo']) && $comision['cupo'] != 0) ? $comision['cupo'] : 'Sin Cupo';
				$descr .= ' / ';
				$descr .= (isset($comision['cant_inscriptos'])) ? $comision['cant_inscriptos'] : '0';
				$comisiones[$clave]['comision_descr'] = $descr;
			}
			$cuadro->set_datos($comisiones);
		} else {
			$indx_msg .= '_sinfiltro';
		}
		$msj = guarani_mensajes::get($indx_msg, array('Alumnos'));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__cuadro_alumnos__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion(array('alumnos_a_distribuir' => $seleccion));
			$this->cn()->cargar_atributos_parametros($seleccion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos_subcom --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos_subcom(toba_ei_cuadro $cuadro)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_alumnos) && !empty($this->s__filtro_alumnos)) {
			$indx_msg .= '_filtrado';
			$filtro = $this->s__filtro_alumnos;
			$filtro_alumnos = $this->s__datos_filtro_alumnos;
			// Se verifica que las comisiones cuyos alumnos se van a distribuir no tengas Actas de Cursada no anuladas.
			$where_sin_actas = "NOT EXISTS (SELECT    sga_actas.id_acta
											FROM    sga_actas
											WHERE    sga_actas.comision = sga_comisiones.comision
											AND        sga_actas.estado <> '" . acta::estado_anulada . "')";

			$filtro = (isset($filtro) && !empty($filtro)) ? $filtro . ' AND ' . $where_sin_actas : $where_sin_actas;
			
			// Para la distribución entre las Subcomisiones de una Comisión, sólo se traerán las inscripciones 
			// de aquellos alumnos en Comisiones que tengan al menos una Subcomisión.
			if ($filtro_alumnos['alumnos'] == comision::alumno_sin_subcomision) {
				$subcomisiones = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_sin_subcomisiones($filtro);
			} else {
				$subcomisiones = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_subcomisiones($filtro);
			}

			// Se arma la descripción del corte de control del cuadro.
			foreach ($subcomisiones as $clave => $subcomision) {
				$descr = 'Comisión: ' . $subcomision['comision_nombre'] . ' <br> ' .
						'Turno: ' . $subcomision['turno_nombre'] . ' | ' .
						'Ubicación: ' . $subcomision['ubicacion_nombre'];
				;
				if (isset($subcomision['docentes'])) {
					$descr .= ' | Docentes: ' . $subcomision['docentes'];
				}
				$descr .= ' | Cupo / Insc: ';
				$descr .= (isset($subcomision['cupo']) && $subcomision['cupo'] != 0) ? $subcomision['cupo'] : 'Sin Cupo';
				$descr .= ' / ';
				$descr .= (isset($subcomision['cant_inscriptos'])) ? $subcomision['cant_inscriptos'] : '0';
				$subcomisiones[$clave]['comision_descr'] = $descr;
				// Los que ya están en la subcomisión
				if ($subcomisiones[$clave]['insc_subcomision'] != 0) {
					$descr = ' Subcomisión: ' . $subcomision['subcomision_desc'];
					$descr .= ' | ' . $subcomision['tipo_clase'];
					$descr .= ' | Cupo / Insc: ';
					$descr .= (isset($subcomision['cupo_subcomision']) && $subcomision['cupo_subcomision'] != 0) ? $subcomision['cupo_subcomision'] : 'Sin Cupo';
					$descr .= ' / ';
					$descr .= (isset($subcomision['inscriptos_subcomision'])) ? $subcomision['inscriptos_subcomision'] : '0';

					$subcomisiones[$clave]['subcomision_desc'] = $descr;
				}
			}
			$cuadro->set_datos($subcomisiones);
		} else {
			$indx_msg .= '_sinfiltro';
		}
		$msj = guarani_mensajes::get($indx_msg, array('Alumnos'));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__cuadro_alumnos_subcom__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion(array('alumnos_a_distribuir' => $seleccion));
			$this->cn()->cargar_atributos_parametros($seleccion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Seleccion Alumnos 
	//-----------------------------------------------------------------------------------

	function validar_seleccion_alumnos()
	{
		// Se valida que se haya seleccionado al menos un alumno.
		if ($this->falta_seleccionar('alumnos_a_distribuir')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Alumno'));
			throw guarani::error($mje);
		}
		$dist = $this->get_seleccion_valor('tipo_distribucion');
		if (($dist == comision::dist_entre_com || $dist == comision::dist_com_subcom_otra_com) &&
				(!$this->hay_mas_de_una_comision_actividad())) {
			if ($dist == comision::dist_com_subcom_otra_com) {
				$param = array(' (con subcomisiones asignadas) ');
			} else {
				$param = array();
			}
			$mje = guarani_mensajes::get('800CUR_error_sin_mas_comisiones', $param);
			throw guarani::error($mje);
		}
	}

	function hay_mas_de_una_comision_actividad()
	{
		// Verifica que haya más de una comisión para la actividad seleccionada dentro del año académico y 
		// período lectivo seleccionados.
		$p_lectivo = $this->s__datos_filtro_alumnos['periodo_lectivo'];
		$elemento = $this->s__datos_filtro_alumnos['actividad'];
		return (toba::consulta_php('co_comisiones')->get_cant_comisiones_de_actividad_en_per_lectivo($elemento, $p_lectivo) > 0);
	}
	
	//-----------------------------------------------------------------------------------
	//---- DISTRIBUCION ALUMNOS 
	//-----------------------------------------------------------------------------------

	function conf__distribucion_alumnos(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$distribucion = $this->get_seleccion('tipo_distribucion_descr');
		$filtros = $this->s__descr_filtros;
		$datos_origen = array_merge($distribucion, $filtros);
		$this->ev_info()->set_datos($datos_origen);
		// Según el tipo de distribución seleccionada, se modifica el cuadro a mostrar para los alumnos distribuidos.
		$id_dist = $this->get_seleccion_valor('tipo_distribucion');
		if ($id_dist == comision::dist_entre_com) {
			$pantalla->eliminar_dep('cuadro_alumnos_distribuidos_subcomision');
		} else {
			$pantalla->eliminar_dep('cuadro_alumnos_distribuidos_comision');
			$mensaje = guarani_mensajes::get($this->mje_confirma_eliminacion_subcomision);
			$this->dep('cuadro_alumnos_distribuidos_subcomision')->evento('eliminar')->set_msg_confirmacion($mensaje);
		}

		// Mensaje de confirmación y etiqueta de boton para finalizar.
		$mensaje = guarani_mensajes::get($this->mje_confirma_copiado);
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Distribuir Alumnos');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/aplicar.png');

		// Mensaje de confirmación para finalizar deshacer cambios.
		$mensaje = guarani_mensajes::get($this->mje_confirma_deshacer);
		$this->evento('deshacer_cambios')->set_msg_confirmacion($mensaje);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos_a_distribuir --------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos_a_distribuir(toba_ei_cuadro $cuadro)
	{
		$msj = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos a distribuir'));
		$cuadro->set_eof_mensaje($msj);
		// En el primer pedido se recuperan las Inscripciones de Alumnos seleccionadas en la pantalla anterior.
		// En los siguientes pedidos se utiliza la variable de esta pantalla que actualiza la lista de Alumnos.
		$alumnos_en_distribucion = $this->get_seleccion('alumnos_en_distribucion');
		if (isset($alumnos_en_distribucion) && !empty($alumnos_en_distribucion)) {
			$inscripciones = $this->get_seleccion_valor('alumnos_en_distribucion');
		} else {
			// Caso inicial.
			$inscripciones = $this->get_seleccion_valor('alumnos_a_distribuir');
			$this->set_seleccion(array('alumnos_en_distribucion' => $inscripciones));
		}
		// Se recuperan los datos de los alumnos asociados a dichas inscripciones.
		$cond_in_inscripciones = guarani_sql::armar_condicion_in($inscripciones, 'vw_insc_cursada', 'inscripcion', 'inscripcion');
		$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones($cond_in_inscripciones);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_alumnos_a_distribuir__seleccion($seleccion)
	{
		$this->s__seleccion_alumnos_temp = $seleccion;
	}

	function conf_evt__cuadro_alumnos_a_distribuir__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->s__seleccion_alumnos_temp)) {
			$datos = $this->dep('cuadro_alumnos_a_distribuir')->get_datos();
			$inscripcion = $datos[$fila]['inscripcion'];
			$evento->set_check_activo(in_array(array('inscripcion' => $inscripcion), $this->s__seleccion_alumnos_temp));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_comision_destino --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_comision_destino(toba_ei_formulario $form)
	{
		// Popup para la selección de la Comisión/Subcomisión destino a donde reubicar las inscripciones seleccionadas.
		$distribucion = $this->get_seleccion_valor('tipo_distribucion');
		$periodo_lectivo = $this->s__datos_filtro_alumnos['periodo_lectivo'];
		$actividad = $this->s__datos_filtro_alumnos['actividad'];

		if ($distribucion == comision::dist_entre_com) {
			$pars = array('periodo_lectivo' => $periodo_lectivo, 'actividad' => $actividad, 'distribucion' => $distribucion);
		} else {
			$comision = $this->s__datos_filtro_alumnos['comision'];
			if ($distribucion == comision::dist_com_subcom && isset($this->s__datos_filtro_alumnos['alumnos']) && $this->s__datos_filtro_alumnos['alumnos'] == comision::alumno_con_subcomision) {
				$clase = $this->s__datos_filtro_alumnos['clase'];
				$alumnos = $this->s__datos_filtro_alumnos['alumnos'];
				$pars = array('comision' => $comision, 'periodo_lectivo' => $periodo_lectivo, 'actividad' => $actividad, 'distribucion' => $distribucion, 'clase' => $clase, 'alumnos' => $alumnos);
			} else {
				$pars = array('comision' => $comision, 'periodo_lectivo' => $periodo_lectivo, 'actividad' => $actividad, 'distribucion' => $distribucion);
			}
		}
		$form->ef('destino')->vinculo()->set_parametros($pars);

		if (isset($this->s__datos_destino_temp)) {
			$form->set_datos($this->s__datos_destino_temp);
		}
	}

	function evt__form_comision_destino__modificacion($datos)
	{
		$datos_dest = $datos['destino'];
		if (!is_null($datos_dest)) {
			$distribucion = $this->get_seleccion_valor('tipo_distribucion');
			if ($distribucion != comision::dist_entre_com) {
				$this->s__subcos_destino = explode('$$&$$', $datos_dest);
				// Obtener comisión 
				$filtro = 'sga_subcomisiones.subcomision = ' . current($this->s__subcos_destino);
				$comision = current(toba::consulta_php('co_comisiones')->get_datos_subcomisiones($filtro));
				$this->s__comision_destino = $comision['comision'];
			} else {
				$this->s__subcos_destino = array();
				$this->s__comision_destino = current($datos);
			}
			$this->s__datos_destino_temp = $datos;
		}
	}

	/**
		* Método descripción para el popup de selección de Comision/Subcomisiones.
		* Distingue el tipo de distribución seleccionado para distinguir si hay múltiples claves y sql debe utilizar.
		*/
	function desc_ef_destino($dato)
	{
		$ret = '';
		$datos = explode('$$&$$', $dato);
		$distribucion = $this->get_seleccion_valor('tipo_distribucion');
		if ($distribucion != comision::dist_entre_com) {
			foreach ($datos as $subco) {
				$ret .= ' / ' . toba::consulta_php('co_comisiones')->get_nombre_subcomision($subco);
			}
			$ret = substr($ret, 3);
		} else {
			$ret = toba::consulta_php('co_comisiones')->get_nombre_comision(current($datos));
		}
		return $ret;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos_distribuidos_comision -----------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos_distribuidos_comision(toba_ei_cuadro $cuadro)
	{
		$msj = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos distribuidos'));
		$cuadro->set_eof_mensaje($msj);
		// Se verifica si ya hay alumnos distribuidos, se recuperan sus datos para mostrar la nueva comisión.
		$alumnos_distribuidos = $this->get_seleccion_valor('alumnos_distribuidos');
		if (!empty($alumnos_distribuidos)) {
			foreach ($alumnos_distribuidos as $key => $alumno) {
				// Se arma la descripción del corte de control del cuadro.
				$descr = 'Comisión: ' . $alumno['comision_nombre'] . ' <br> ' .
						'Turno: ' . $alumno['turno_nombre'] . ' | ' .
						'Ubicación: ' . $alumno['ubicacion_nombre'] . ' | ' .
						'Subcomisiones:' . $alumno['cant_subcomisiones'];
				if (isset($alumno['comision_docentes'])) {
					$descr .= ' | Docentes: ' . $alumno['comision_docentes'];
				}
				$descr .= ' | Cupo / Insc: ';
				$descr .= (isset($alumno['cupo']) && $alumno['cupo'] != 0) ? $alumno['cupo'] : 'Sin Cupo';
				$descr .= ' / ';
				$descr .= (isset($alumno['cant_inscriptos'])) ? $alumno['cant_inscriptos'] : '0';

				$alumnos_distribuidos[$key]['comision_descr'] = $descr;
			}
			$cuadro->set_datos($alumnos_distribuidos);
		}
	}

	function evt__cuadro_alumnos_distribuidos_comision__eliminar($seleccion)
	{
		// El alumno seleccionado se elimina de la distribución y se vuelve al origen.
		$this->eliminar_alumno_distribuido(current($seleccion));
		$this->agregar_alumno_a_distribuir($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos_distribuidos_subcomision --------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos_distribuidos_subcomision(toba_ei_cuadro $cuadro)
	{
		$msj = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos distribuidos'));
		$cuadro->set_eof_mensaje($msj);
		// Se verifica si ya hay alumnos distribuidos, se recuperan sus datos para mostrar la nueva comisión.
		$alumnos_distribuidos = $this->get_seleccion_valor('alumnos_distribuidos');
		if (!empty($alumnos_distribuidos)) {
			foreach ($alumnos_distribuidos as $key => $alumno) {
				// Se arma la descripción del corte de control del cuadro por Comisión.
				$descr = 'Comisión: ' . $alumno['comision_nombre'] . ' <br> ' .
						'Turno: ' . $alumno['turno_nombre'] . ' | ' .
						'Ubicación: ' . $alumno['ubicacion_nombre'];

				$alumnos_distribuidos[$key]['comision_descr'] = $descr;

				// Se arma la descripción del corte de control del cuadro por Subcomisión.
				$descr_sub = 'Subcomisión: ' . $alumno['subcomision_nombre'] . ' <br> ' .
						'Tipo: ' . $alumno['tipo_clase_nombre'] . ' | ';
				if (isset($alumno['subcomision_docentes'])) {
					$descr_sub .= ' | Docentes: ' . $alumno['subcomision_docentes'];
				}
				$descr_sub .= ' | Cupo / Insc: ';
				$descr_sub .= (isset($alumno['cupo']) && $alumno['cupo'] != 0) ? $alumno['cupo'] : 'Sin Cupo';
				$descr_sub .= ' / ';
				$descr_sub .= (isset($alumno['cant_inscriptos'])) ? $alumno['cant_inscriptos'] : '0';

				$alumnos_distribuidos[$key]['subcomision_descr'] = $descr_sub;
			}
			$cuadro->set_datos($alumnos_distribuidos);
		}
	}

	function evt__cuadro_alumnos_distribuidos_subcomision__eliminar($seleccion)
	{
		// El alumno seleccionado se elimina de la distribución y se vuelve al origen.
		$this->eliminar_alumno_distribuido(current($seleccion));
		$this->agregar_alumno_a_distribuir($seleccion);
	}

	//---------------------------------------------------------------------------------------

	function validar_distribucion_alumnos()
	{
		// Se valida que se haya modificado la distribución de alguna inscripción de alumno.
		if ($this->falta_seleccionar('alumnos_distribuidos')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('al menos una inscripción de alumno para reubicar'));
			throw guarani::error($mje);
		}
	}

	/**
		* Función auxiliar para recuperar los datos de la selección del filtro de alumnos inscriptos.
		*/
	function get_datos_filtros_inscripciones()
	{
		$p_lectivo = $this->s__datos_filtro_alumnos['periodo_lectivo'];
		$elemento = $this->s__datos_filtro_alumnos['actividad'];
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_nombre_y_anio_periodo_lectivo($p_lectivo);
		$datos_actividad = current(toba::consulta_php('co_elementos')->get_actividad_nombre($elemento));
		return array_merge($datos_periodo, $datos_actividad);
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
		* Realiza las pre validaciones necesarias para ejecutar el Reubicar. En principio que se hayan seleccionado todos los
		* datos necesarios, y a continuación, que en caso de que la Comisión o Subcomisiones destino superen sus cupos con 
		* la selección de alumnos dada, emitir un mensaje de alerta.
		*/
	function evt__verificar_reubicacion_alumnos()
	{
		// Para ejecutar el Reubicar deben haberse seleccionado al menos una Inscripción y la Comisión/Subcomisión destino.
		if (!empty($this->s__seleccion_alumnos_temp) && isset($this->s__datos_destino_temp)) {
			// Si el parámetro indica que se controlan cupos, se verifica para la Comisión o Subcomisiones destino, 
			// según si el tipo de distribución seleccionado y el tipo de control indicado por el parámetro "control por": 
			// aula o comisión.
			if (guarani::parametros()->get_valor('cur_controlar_cupo') == comunes::si) {
				// Primero recupera los alumnos que ya están en distribución en el proceso de esta operación.
				$alumnos_en_distribucion = $this->get_seleccion_valor('alumnos_distribuidos');
				$tipo_distribucion = current($this->get_seleccion('tipo_distribucion'));
				if ($tipo_distribucion == comision::dist_entre_com) {
					$this->msj_confirmar_supera_cupos = $this->cn()->validar_cupo_comisiones($this->s__seleccion_alumnos_temp, $tipo_distribucion, $alumnos_en_distribucion, guarani::parametros()->get_valor('cur_controlar_cupo_por'), $this->s__comision_destino);
				} else {
					$this->msj_confirmar_supera_cupos = $this->cn()->validar_cupo_subcomisiones($this->s__seleccion_alumnos_temp, $tipo_distribucion, $alumnos_en_distribucion, guarani::parametros()->get_valor('cur_controlar_cupo_por'), $this->s__subcos_destino);
				}
			}
			// En caso de que no deban verificarse cupos o que los mismos no se superen, se reubican normalmente los 
			// alumnos. En otro caso, se emitirá el confirm en el próximo pedido de página, verificándolo en la extensión
			// js del ci.
			if (empty($this->msj_confirmar_supera_cupos)) {
				$this->evt__reubicar_alumnos();
			}
		} else {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Alumno y un Destino'));
			throw guarani::error($mje);
		}
	}

	/**
		*  Con los datos de las inscripciones seleccionadas para modificar y la Comisión/Subcomisiones destino, se
		*  mofifican las variables de selección que mantienen la nueva distribución de Alumnos, para invocar al proceso
		*  al confirmar.
		*/
	function evt__reubicar_alumnos()
	{
		$this->cn()->validar_alumnos($this->s__seleccion_alumnos_temp, $this->s__comision_destino, current($this->get_seleccion('tipo_distribucion')));
		foreach ($this->s__seleccion_alumnos_temp as $alumno) {
			// Debería controlarse si se va a superar el cupo de comisión/subcomisión para marcarla.
			$this->agregar_alumno_distribuido($alumno['inscripcion']);
			$this->eliminar_alumno_a_distribuir($alumno['inscripcion']);
		}
		// Una vez reubicados se resetean las variables de sesión con los valores temporales
		$this->resetear_variables_dist_alumnos();
	}

	/**
		* Agrega la inscripcion de alumno dada a los alumnos distribuidos a nuevas Comisiones/Subcomisiones.
		*/
	function agregar_alumno_distribuido($id_inscripcion)
	{
		$alumnos_distribuidos = $this->get_seleccion_valor('alumnos_distribuidos');
		if (is_null($alumnos_distribuidos)) {
			$alumnos_distribuidos = array();
		}
		$id_dist = $this->get_seleccion_valor('tipo_distribucion');
		$datos_alumno = current(toba::consulta_php('co_inscripciones_cursadas')->get_datos_alumnos_inscripciones($id_inscripcion));
		if ($id_dist == comision::dist_entre_com) {
			$alumno_a_agregar = array('inscripcion' => $id_inscripcion, 'destino' => $this->s__comision_destino);
			// Se recuperan los datos asociados a la nueva inscripción a agregar.
			// Se necesitan los datos del alumno asociado al id de inscripción origen, pero con la comisión destino nueva.    
			$datos_comision_destino = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->s__comision_destino));
			$inscr_nueva = array_merge($alumno_a_agregar, $datos_alumno, $datos_comision_destino);
			array_push($alumnos_distribuidos, $inscr_nueva);
		} else {
			$alumno_a_agregar = array('inscripcion' => $id_inscripcion, 'destino' => array($this->s__comision_destino, $this->s__subcos_destino));
			// Se recuperan los datos asociados a la nueva inscripción a agregar.
			// Se necesitan los datos del alumno asociado al id de inscripción origen, pero con los datos destino nuevos.
			foreach ($this->s__subcos_destino as $subco) {
				$filtro = "sga_subcomisiones.subcomision = $subco";
				$datos_destino = current(toba::consulta_php('co_comisiones')->get_subcomisiones_cupo($filtro));
				$inscr_nueva = array_merge($alumno_a_agregar, $datos_alumno, $datos_destino);
				array_push($alumnos_distribuidos, $inscr_nueva);
			}
		}
		$this->set_seleccion(array('alumnos_distribuidos' => $alumnos_distribuidos));
	}

	/**
		*  Elimina la inscripción de alumno dada de los alumnos seleccionados para distribuir.
		*/
	function eliminar_alumno_a_distribuir($id_inscripcion)
	{
		$alumnos_en_distribucion = $this->get_seleccion_valor('alumnos_en_distribucion');
		// Modifica la selección de alumnos a distribuir para luego setearla sin la inscripción ya distribuida.
		foreach ($alumnos_en_distribucion as $key => $alumno) {
			if ($alumno['inscripcion'] == $id_inscripcion) {
				unset($alumnos_en_distribucion[$key]);
				break;
			}
		}
		$this->set_seleccion(array('alumnos_en_distribucion' => $alumnos_en_distribucion));
	}

	/**
		* Elimina la inscripcion de alumno dada de los alumnos distribuidos, para volverlo al origen en alumnos a distribuir.
		*/
	function eliminar_alumno_distribuido($id_inscripcion)
	{
		$alumnos_distribuidos = $this->get_seleccion_valor('alumnos_distribuidos');
		// Modifica la selección de alumnos a distribuir para luego setearla sin la inscripción que se desea eliminar.
		foreach ($alumnos_distribuidos as $key => $alumno) {
			if ($alumno['inscripcion'] == $id_inscripcion) {
				unset($alumnos_distribuidos[$key]);
			}
		}
		$this->set_seleccion(array('alumnos_distribuidos' => $alumnos_distribuidos));
	}

	/**
		* Agrega la inscripción eliminada de los alumnos distribuidos nuevamente a alumnos en distribución, en su comisión origen.
		*/
	function agregar_alumno_a_distribuir($alumno_a_agregar)
	{
		$alumnos_en_distribucion = $this->get_seleccion_valor('alumnos_en_distribucion');
		if (is_null($alumnos_en_distribucion)) {
			$alumnos_en_distribucion = array();
		}
		array_push($alumnos_en_distribucion, $alumno_a_agregar);
		$this->set_seleccion(array('alumnos_en_distribucion' => $alumnos_en_distribucion));
	}

	/**
		* Regresa a la pantalla de inicio luego de finalizado el proceso.
		*/
	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('tipo_distribucion');
	}

	/**
		* Deshace los cambios de la Distribución y vuelve a la selección origen de Alumnos.
		*/
	function evt__deshacer_cambios()
	{
		$alumnos_a_distribuir = $this->get_seleccion_valor('alumnos_a_distribuir');
		$this->eliminar_seleccion_previa('distribucion_alumnos', 'alumnos_distribuidos');
		$this->set_seleccion(array('alumnos_en_distribucion' => $alumnos_a_distribuir));
		$this->resetear_variables_dist_alumnos();
	}

	//-----------------------------------------------------------------------------------
	//---- RESULTADOS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$distribucion = $this->get_seleccion('tipo_distribucion_descr');
		$filtros = $this->s__descr_filtros;
		$datos_origen = array_merge($distribucion, $filtros);
		$this->ev_info()->set_datos($datos_origen);

		// Se oculta el boton Anterior
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- Auxiliares el manejo del avance del proceso ----------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$resultados = $this->cn()->get_resultado_proceso(pro_distribuir_comisiones::reporte_resultados);

		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_alumnos_distribuidos':
				$reporte = pro_distribuir_comisiones::reporte_alumnos_distribuidos;
				$operacion = '37000108';
				$titulo = 'Alumnos Distribuidos';
				break;
			case 'rep_alumnos_no_distribuidos':
				$reporte = pro_distribuir_comisiones::reporte_alumnos_no_distribuidos;
				$operacion = '37000108';
				$titulo = 'Alumnos No Distribuidos';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se obtienen los datos correspondientes al encabezado del reporte.
		$origen = $this->s__descr_filtros;
		$tipo_dist = $this->get_seleccion('tipo_distribucion_descr');

		$datos_conf = array('tipo_distribucion' => $tipo_dist,
			'anio' => $origen['periodo_anio_academico'],
			'periodo_lectivo' => $origen['periodo_nombre'],
			'actividad' => $origen['codigo_y_nombre']);


		// Se almacenan los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array('alumnos' => $datos['mensajes'],
			'reporte' => $reporte,
			'titulo' => $titulo));
		guarani_datos_impresion::instancia()->set_conf($datos_conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width(120);
			$this->ev_info->set_columnas_a_mostrar(array('tipo_distribucion_descr', 'periodo_anio_academico', 'periodo_nombre', 'codigo_y_nombre'));
			$this->ev_info->set_columnas_fila(array('tipo_distribucion_descr'), 1);
			$this->ev_info->set_columnas_fila(array('periodo_anio_academico', 'periodo_nombre', 'codigo_y_nombre'), 2);
			$this->ev_info->set_etiqueta('tipo_distribucion_descr', 'Distribución');
			$this->ev_info->set_etiqueta('periodo_anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre', 'Actividad');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if (isset($this->msj_confirmar_supera_cupos)) {
			$confirmar = 'var confirmar_cupo = true;';
		} else {
			$confirmar = 'var confirmar_cupo = false;';
		}

		echo "
			{$this->objeto_js}.confirmar_reubicacion_supera_cupos = function(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm('$this->msj_confirmar_supera_cupos');
				}
				if (resp) {
					// Si confirma el Reubicar a pesar de que se superen los cupos, invoca al evento correspondiente.
					this.set_evento(new evento_ei('reubicar_alumnos'), true); 
				} else {
					// Si no, se queda en la misma pantalla sin pasar por el Reubicar alumnos.
					return false;
				}
				;
			}
			
			$confirmar;
			if (confirmar_cupo) {
				{$this->objeto_js}.confirmar_reubicacion_supera_cupos();
			}
		";
	}

}
?>