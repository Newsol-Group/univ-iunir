<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class ci_edi_cargar_notas extends guarani_pers_ci
{
	// Datos temporales para el formulario multilínea de notas.
	protected $datos_notas_temp;
	
	// Datos para paginado.
	protected $s__pagina_actual = 1;
	protected $s__tamanio_pagina = 20;
	protected $s__cantidad_paginas;
	
	// Nombre de las instancias.
	protected $s__nombre_instancia_cursada;
	protected $s__nombre_instancia_promocion;
	
	// Datos de la comisión seleccionada.
	protected $s__datos;
	
	// Datos del acta seleccionada.
	protected $s__acta_seleccionada;
	protected $s__datos_acta;
	
	// Observaciones cargadas
	protected $s__observaciones;
	
	// Parámetros enviados en la invocación desde otra operación.
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	
	// Componente Visual de Información.
	protected $ev_info;

	function ini__operacion()
	{
		// Se obtiene el nombre de cada instancia.
		$instancia_cursada = toba::consulta_php('co_comisiones')->get_datos_instancia(comision::inst_cursar);
		$this->s__nombre_instancia_cursada = $instancia_cursada['instancia_nombre'];
		$instancia_promocion = toba::consulta_php('co_comisiones')->get_datos_instancia(comision::inst_promocionar);
		$this->s__nombre_instancia_promocion = $instancia_promocion['instancia_nombre'];
	}

	function ini()
	{
		$this->s__acta_seleccionada = $this->controlador()->get_acta_seleccionada();
		$this->s__datos = $this->controlador()->get_datos_comision();
		$this->s__datos_acta = $this->controlador()->get_datos_acta();
	}

	/**
	 * Resetea las variables de sesión de este CI
	 */
	function resetear_vars()
	{
		unset($this->s__observaciones);
	}

	function get_observaciones()
	{
		if (isset($this->s__observaciones)) {
			return $this->s__observaciones['observaciones'];
		} else {
			return '';
		}
	}

	function get_operacion_a_volver()
	{
		return $this->s__operacion_a_volver;
	}

	function get_celda_a_restaurar()
	{
		return $this->s__celda_a_restaurar;
	}

	function conf__pant_inicial()
	{
		$datos = $this->s__datos_acta;
		$descr_instancias_escalas = (!empty($this->s__datos_acta['instancias_escalas'])) ? $this->s__datos_acta['instancias_escalas'] . ' ' : '';
		$datos['icono_info_escalas_notas'] = $descr_instancias_escalas . $this->get_html_icono_info_escalas_notas();

		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'instancia_nombre', 'tipo_acta_descr', 'comision_nombre',
				'nombre_turno', 'modalidad_nombre', 'anio_academico_periodo', 'periodo_lectivo_nombre',
				'nombre_ubicacion', 'codigo_y_nombre_actividad', 'icono_info_escalas_notas'));

			$this->ev_info->set_columnas_fila(array('nro_acta', 'instancia_nombre', 'tipo_acta_descr', 'comision_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_turno', 'modalidad_nombre', 'anio_academico_periodo', 'periodo_lectivo_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'codigo_y_nombre_actividad', 'icono_info_escalas_notas'), 3);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('instancia_nombre', 'Instancia');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');

			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');

			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escala de notas');
		}
		return $this->ev_info;
	}

	function get_detalles()
	{
		$condiciones = array();
		$origen = $this->s__datos_acta['origen'];
		if ($origen == acta::origen_regulares) {
			$condiciones['id_acta_cursada'] = $this->s__acta_seleccionada;
		} elseif ($origen == acta::origen_promociones) {
			$condiciones['id_acta_promocion'] = $this->s__acta_seleccionada;
		}
		$param_ordenamiento = guarani::parametros()->get_valor('cur_orden_detalle_actas');
		$datos = $this->cn()->get_detalles($condiciones, $param_ordenamiento);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['comision'] = $this->s__datos['comision'];
			$datos[$clave]['codigo_instancia_cursada'] = comision::inst_cursar;
			$datos[$clave]['nombre_instancia_cursada'] = $this->s__nombre_instancia_cursada;
			$datos[$clave]['codigo_instancia_promocion'] = comision::inst_promocionar;
			$datos[$clave]['nombre_instancia_promocion'] = $this->s__nombre_instancia_promocion;
		}
		return $datos;
	}
	
	function get_escala_nota_regularidad()
	{
		$where = "sga_comisiones_instancias.instancia = ".comision::inst_cursar;
		$escala_nota_regularidad = toba::consulta_php('co_comisiones')->get_instancias_comision($this->s__datos['comision'], $where);
		return reset($escala_nota_regularidad);
	}
	
	function get_escala_nota_promocion()
	{
		$where = "sga_comisiones_instancias.instancia = ".comision::inst_promocionar;
		$escala_nota_promocion = toba::consulta_php('co_comisiones')->get_instancias_comision($this->s__datos['comision'], $where);
		return reset($escala_nota_promocion);
	}

	//-----------------------------------------------------------------------------------
	//---- form_observaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_observaciones(toba_ei_formulario $form)
	{
		if (isset($this->s__observaciones)) {
			$form->set_datos($this->s__observaciones);
		}
	}

	function evt__form_observaciones__modificacion($datos)
	{
		$this->controlador()->set_hubo_cambios();
		if ($datos) {
			$this->s__observaciones = $datos;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_notas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_notas(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_notas_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_notas_temp);
		} else {
			$form_ml->set_datos($this->generar_paginado($this->get_detalles()));
		}

		if ($this->s__pagina_actual == 1) {
			$form_ml->evento('primera')->desactivar();
			$form_ml->evento('anterior')->desactivar();
		}
		if ($this->s__pagina_actual == $this->s__cantidad_paginas) {
			$form_ml->evento('siguiente')->desactivar();
			$form_ml->evento('ultima')->desactivar();
		}
	}

	function conf_fila__form_ml_notas($fila)
	{
		$datos_ml = $this->dep('form_ml_notas')->get_datos(true);

		// Si el alumno está inscripto a la instancia cursar y está incluido en un acta de regulares cerrada,
		// se deberán deshabilitar los campos de la instancia cursar.
		if ($datos_ml[$fila]['instancia_cursada'] && $datos_ml[$fila]['acta_cursada_cerrada']) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_regular', 'cond_regularidad', 'resultado_cursada', 'observacion_cursada'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_regular', 'cond_regularidad', 'resultado_cursada', 'observacion_cursada'), false);
		}

		// Si el alumno está inscripto a la instancia promoción y está incluido en un acta de promoción cerrada,
		// se deberán deshabilitar los campos de la instancia promocionar.
		if ($datos_ml[$fila]['instancia_promocion'] && $datos_ml[$fila]['acta_promocion_cerrada']) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_promocion', 'resultado_promocion', 'observacion_promocion'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_promocion', 'resultado_promocion', 'observacion_promocion'), false);
		}

		// Si el alumno está inscripto a la instancia cursar y está incluido en un acta de regulares cerrada o cur_asentar_notas_actas_regulares = 'S',
		// se deberá deshabilitar el campo Nota de Cursada.
		if ($datos_ml[$fila]['instancia_cursada'] && ($datos_ml[$fila]['acta_cursada_cerrada'] || guarani::parametros()->get_valor('cur_asentar_notas_actas_regulares') == acta::ingreso_notas_sin_notas)) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_cursada'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_cursada'), false);
		}

		// Si el alumno está inscripto a la instancia promoción y está incluido en un acta de promoción cerrada o cur_asentar_notas_actas_promocion = 'S',
		// se deberá deshabilitar el campo Nota de Promoción.
		if ($datos_ml[$fila]['instancia_promocion'] && ($datos_ml[$fila]['acta_promocion_cerrada'] || guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion') == acta::ingreso_notas_sin_notas)) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_promocion'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_promocion'), false);
		}
	}

	function conf_evt__form_ml_notas__ver_evaluaciones(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('form_ml_notas')->get_datos(true);
		$alumno = $datos[$fila]['alumno'];
		$comision = $this->s__datos['comision'];

		$evento->vinculo()->agregar_parametro(utf8_encode('alumno'), utf8_encode($alumno));
		$evento->vinculo()->agregar_parametro(utf8_encode('comision'), utf8_encode($comision));
	}

	function evt__form_ml_notas__primera($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_notas__anterior($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__pagina_actual - 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_notas__siguiente($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__pagina_actual + 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_notas__ultima($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__cantidad_paginas;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_notas__modificacion($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw $e;
		}
	}

	/**
	 * Retorna una porción de los datos recibidos como parámetro, teniendo en cuenta la página actual, y calcula la cantidad de páginas posibles.
	 *
	 * @param array $datos arreglo asociativo de datos a paginar.
	 */
	function generar_paginado($datos)
	{
		$this->s__cantidad_paginas = ceil(guarani_arreglos::count($datos) / $this->s__tamanio_pagina);
		if ($this->s__pagina_actual > $this->s__cantidad_paginas) {
			$this->s__pagina_actual = 1;
		}
		$offset = ($this->s__pagina_actual - 1) * $this->s__tamanio_pagina;
		return array_slice($datos, $offset, $this->s__tamanio_pagina);
	}

	function get_pagina_actual()
	{
		return $this->s__pagina_actual;
	}

	function get_cantidad_paginas()
	{
		return $this->s__cantidad_paginas;
	}

	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de escalas de notas por instancia para una comisión.
	 */
	function get_html_icono_info_escalas_notas()
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '41000035', array('comision' => $this->s__datos['comision']), array('celda_memoria' => 'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'630','scrollbars':'yes'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalles de escalas');
		$salida .= '</a>';

		return $salida;
	}

	function get_fecha_inicio_periodo_comision()
	{
		return $this->s__datos['fecha_inicio_periodo'];
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Genera un arreglo JS con el mapeo instancia->escala de notas.
	 */
	function generar_mapeo_js_instancia_escala()
	{
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($this->s__datos['comision']);
		$instancias = rs_convertir_asociativo($instancias, array('instancia'), 'escala_nota');
		return toba_js::arreglo($instancias, true);
	}

	/**
	 * Genera un arreglo asociativo de dos componentes:
	 * 'posibles': arreglo JS con el mapeo resultado->condiciones de regularidad posibles.
	 * 'defecto': arreglo JS con el mapeo resultado->condición de regularidad por defecto.
	 */
	function generar_mapeo_js_resultado_condicion()
	{
		$condiciones = toba::consulta_php('co_condiciones_regularidad')->get_condiciones_activas();
		$posibles = array();
		$defecto = array();
		foreach ($condiciones as $cond) {
			$posibles[$cond['resultado']][] = $cond['cond_regularidad'];
			if ($cond['es_condicion_defecto'] == comunes::si) {
				$defecto[$cond['resultado']] = $cond['cond_regularidad'];
			} elseif (empty($defecto[$cond['resultado']])) {
				$defecto[$cond['resultado']] = 'nopar';
			}
		}
		return array('posibles' => toba_js::arreglo($posibles, true, false), 'defecto' => toba_js::arreglo($defecto, true));
	}

	/**
	 * Retorna el código JS necesario para validar las notas de las distintas escalas de la comisión.
	 */
	function get_js_validar_nota_escalas()
	{
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($this->s__datos['comision']);
		$escalas = rs_convertir_asociativo_matriz($instancias, array('escala_nota'), array('escala_nota'));

		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}
}