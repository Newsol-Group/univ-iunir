<?php
class ci_nav_generacion_masiva_actas extends ci_nav_procesos_bk
{
	protected $s__pantalla;
	
	// Proceso background
	protected $s__proceso;
	
	// Selección del usuario más forma de composición (para pasar al procesamiento masivo)
	protected $s__datos_composicion;
	
	// Llamados mesas seleccionados
	protected $s__llamados_mesas;
	protected $s__datos_llamados_mesas;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		$this->dep('filtro')->set_sin_filtrado_inicial();
		$this->s__pantalla = $this->get_id_pantalla();
		parent::conf();
	}

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_generacion_actas';
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA MESAS 
	//-----------------------------------------------------------------------------------

	function conf__mesas(toba_ei_pantalla $pantalla)
	{
		unset($this->s__datos_composicion);
		$datos = $this->dep('cuadro')->get_datos();
		if (!empty($datos)) {
			$pantalla->set_descripcion('Sólo pueden seleccionarse mesas para las que el parámetro que determina el modo de carga del número de acta de examen (<strong>exa_carga_manual_nro_acta_examen</strong>) esté definido como <strong>automático</strong>.');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof($etiqueta = null)
	{
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		} else {
			$indx_msg = $this->indx_msg_eof . '_sinfiltro';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__filtro)) {
			$where = $this->get_filtro_condicion() . " AND sga_llamados_mesa.estado = '" . mesa_de_examen::mesa_estado_activo . "'";
			$datos = toba::consulta_php('co_mesas_de_examen')->get_listado_mesas_con_inscriptos_sin_actas_gen($where);
			if ($datos) {
				$this->s__datos_llamados_mesas = array(
					'anio_academico' => $datos[0]['anio_academico'],
					'nombre_turno_examen' => $datos[0]['nombre_turno']
				);
				$cuadro->set_datos($datos);
			}
		}

		$etiqueta = guarani_etiquetas::get_tabla('sga_mesas_examen');
		$msj = $this->get_msg_eof($etiqueta->get_enf('plural'));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__llamados_mesas = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true)));

		$datos = $this->dep('cuadro')->get_datos();
		$mesa_examen = $datos[$fila]['mesa_examen'];
		$atributos = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');

		if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen', $atributos) == comunes::si) {
			$evento->anular();
		}
	}

	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__llamados_mesas)) {
			foreach ($this->s__llamados_mesas as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ALCANCE 
	//-----------------------------------------------------------------------------------

	function conf__alcance(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_llamados_mesas);
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_composicion ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_composicion(guarani_pers_ei_formulario_ml $form_ml)
	{
		if (!isset($this->s__datos_composicion)) {
			$where = guarani_sql::armar_condicion_in($this->s__llamados_mesas, 'sga_llamados_mesa', 'llamado_mesa');
			$this->s__datos_composicion = toba::consulta_php('co_mesas_de_examen')->get_listado_mesas_con_inscriptos_sin_actas_gen($where);
		}
		$form_ml->set_datos($this->s__datos_composicion);
	}

	function evt__form_ml_composicion__modificacion($datos)
	{
		$this->s__datos_composicion = $datos;
	}

	function get_opciones_composicion($mesa_examen)
	{
		$atributos = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
		if (guarani::parametros()->get_valor('exa_multiples_actas_examen', $atributos) == comunes::no) {
			return array(
				array('valor' => 'M', 'descr' => 'Acta x Mesa')
			);
		} else {
			return array(
				array('valor' => 'M', 'descr' => 'Acta x Mesa'),
				array('valor' => 'I', 'descr' => 'Acta x Instancia')
			);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA RESULTADOS 
	//-----------------------------------------------------------------------------------

	function conf__resultados_proceso(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_llamados_mesas);
		$pantalla->evento('generar')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
		$form->evento('imprimir_actas')->vinculo()->set_parametros(array('operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
		$form->evento('imprimir_actas')->vinculo()->set_celda_memoria('celda_imp_reg');
		parent::conf__form_totales($form);
	}

	function evt__form_totales__imprimir_actas($datos)
	{
		/**
		 * Navegar a Impresión Masiva de Actas de Examen.
		 * Enviar por parámetro el rango de Nº de Actas que fueron creados.
		 */
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Controla que se haya seleccionado al menos una mesa y pasa a la pantalla de Alcance
	 */
	function evt__siguiente()
	{
		if ($this->falta_seleccionar_mesa()) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Mesa'));
			throw guarani::error($mje);
		}

		$this->set_pantalla('alcance');
	}

	/**
	 * Realiza los reseteos que sean necesarios y vuelve a la pantalla de mesas
	 */
	function evt__volver()
	{
		if ($this->s__pantalla == 'resultados_proceso') {
			$this->resetear_variables();
		}

		$this->set_pantalla('mesas');
	}

	function evt__volver_inicio()
	{
		$this->set_pantalla('mesas');
	}

	/**
	 * Cambia a la nueva pantalla y corre el proceso
	 */
	function evt__generar()
	{

		foreach ($this->s__datos_composicion as $key => $dato) {
			$mesa_examen = $dato['mesa_examen'];
			$atributos_propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
			$atributos_ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($atributos_propuestas);
			$this->s__datos_composicion[$key]['renglones_folio'] = guarani::parametros()->get_valor('exa_lineas_folio_acta_examen', $atributos_propuestas);
			$this->s__datos_composicion[$key]['digital'] = guarani::parametros()->get_valor('exa_utiliza_acta_digital', $atributos_ras);
		}
		$parametros = array('datos' => $this->s__datos_composicion);

		$this->set_pantalla('resultados_proceso');

		// Se crea y lanza el proceso background 
		$this->s__proceso = guarani::proceso_bk('pro_generacion_actas');
		$this->s__proceso->set_datos($parametros);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->s__proceso->get_estado();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		// Se obtienen los resultados del proceso
		$cant_actas = $this->s__proceso->get_resultado('cantidad_actas');
		$actas = $this->s__proceso->get_resultado('actas');

		// Contenido de la respuesta
		$contenido = array();
		$cant_actas = current($cant_actas['mensajes']);
		$contenido['cant_actas'] = (!empty($cant_actas['cantidad_actas'])) ? $cant_actas['cantidad_actas'] : 0;

		// Datos para la instancia
		$datos = $this->procesar_datos_actas($actas['mensajes']);

		// Se almacenan los datos en la instancia para que sean recuperados luego en la impresión
		guarani_datos_impresion::instancia()->set_datos($datos);
		guarani_datos_impresion::instancia()->set_conf($this->s__datos_llamados_mesas);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		$respuesta->set($contenido);
	}

	/**
	 * Se obtienen los datos de las actas, llamados mesas e instancias para el reporte
	 */
	function procesar_datos_actas($actas)
	{
		foreach ($actas as $clave => $valor) {
			// Datos Acta
			$filtro_acta = 'sga_actas.id_acta = ' . $valor['id_acta'];
			$datos_acta = current(toba::consulta_php('co_actas')->get_listado_actas_examen($filtro_acta));

			// Datos Instancias
			$instancias = toba::consulta_php('co_actas')->get_nombres_instancias_acta($valor['id_acta']);
			$lista_instancias = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($instancias, 'nombre'), ' / ');

			// Datos Mesa
			$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($valor['llamado_mesa']);

			// Array a retornar
			$actas[$clave]['codigo_nombre_actividad'] = $datos_mesa['codigo_nombre_actividad'];
			$actas[$clave]['cant_alumnos'] = $datos_acta['cant_alumnos'];
			$actas[$clave]['nombre'] = $datos_mesa['nombre'];
			$actas[$clave]['nombre_llamado'] = $datos_mesa['nombre_llamado'];
			$actas[$clave]['instancias_nombre'] = $lista_instancias;
			$actas[$clave]['fecha'] = $datos_mesa['fecha'];
			$actas[$clave]['nombre_ubicacion'] = $datos_mesa['nombre_ubicacion'];
			$actas[$clave]['version'] = $datos_acta['version'];
			$actas[$clave]['observaciones'] = $datos_acta['observaciones'];
		}

		return $actas;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 50%;');
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre_turno_examen'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_turno_examen'));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');
		}

		return $this->ev_info;
	}

	function falta_seleccionar_mesa()
	{
		return !isset($this->s__llamados_mesas);
	}

	function resetear_variables()
	{
		unset($this->s__llamados_mesas);
		unset($this->s__datos_composicion);
		unset($this->s__filtro);
		unset($this->s__datos);
	}

}
?>