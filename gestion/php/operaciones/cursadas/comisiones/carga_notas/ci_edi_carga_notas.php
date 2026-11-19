<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 * 
 */
class ci_edi_carga_notas extends ci_reporte
{
	// Datos temporales para el formulario multilínea de notas.
	protected $datos_notas_temp;
	protected $s__datos;
	
	// Datos para paginado.
	protected $s__pagina_actual = 1;
	protected $s__tamanio_pagina = 20;
	protected $s__cantidad_paginas;
	
	// Nombre de las instancias.
	protected $s__nombre_instancia_cursada;
	protected $s__nombre_instancia_promocion;
	
	// Componente Visual de Información.
	protected $ev_info;
	
	// Impresión.
	protected $s__imprimir = false;

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
		toba::memoria()->desactivar_reciclado();
		parent::ini();
		$this->s__datos = $this->controlador()->get_datos_comision();
	}

	function conf__pant_inicial()
	{
		$datos = $this->s__datos;
		$descr_instancias_escalas = (!empty($this->s__datos['instancias_escalas'])) ? $this->s__datos['instancias_escalas'] . ' ' : '';
		$datos['icono_info_escalas_notas'] = $descr_instancias_escalas . $this->get_html_icono_info_escalas_notas();

		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('110px');
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escalas_notas'));
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info->set_columnas_fila(array('icono_info_escalas_notas'), 4);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
		}
		return $this->ev_info;
	}

	function get_tabla_principal()
	{
		
	}

	function get_datos($filtro)
	{
		
	}

	function conf()
	{
		
	}

	function get_detalles()
	{
		$param_ordenamiento = guarani::parametros()->get_valor('cur_orden_detalle_actas');
		$datos = $this->cn()->get_detalles(array('agregado' => true), $param_ordenamiento);
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
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__anterior($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__pagina_actual - 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__siguiente($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__pagina_actual + 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__ultima($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
			$this->s__pagina_actual = $this->s__cantidad_paginas;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__modificacion($datos)
	{
		try {
			$this->cn()->procesar_carga_notas($datos);
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	/**
	 * Retorna una porción de los datos recibidos como parámetro, teniendo en cuenta 
	 * la página actual, y calcula la cantidad de páginas posibles.
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
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__pdf()
	{
		$this->s__imprimir = true;
	}

	function cuadro()
	{
		return $this->dep('cuadro_impresion');
	}

	function datos_encabezado()
	{
		$datos = $this->s__datos;
		$descr_instancias_escalas = (!empty($this->s__datos['instancias_escalas'])) ? $this->s__datos['instancias_escalas'] . ' ' : '';
		$datos['icono_info_escalas_notas'] = $descr_instancias_escalas;
		$this->ev_info()->set_datos($datos);
		$de = $this->ev_info()->get_datos();
		$datos['icono_info_escalas_notas'] = $descr_instancias_escalas . $this->get_html_icono_info_escalas_notas();
		$this->ev_info()->set_datos($datos);
		return $de;
	}

	function get_titulo()
	{
		return 'Carga de notas a Comisiones';
	}

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		// Se agregan los datos de los alumnos.
		$param_ordenamiento = guarani::parametros()->get_valor('cur_orden_detalle_actas');
		$datos_ml = $this->cn()->get_detalles(array('agregado' => true), $param_ordenamiento);

		$resultados_cursada = toba::consulta_php('co_escalas_notas')->get_resultados_instancia(comision::inst_cursar);
		$resultados_cursada = rs_convertir_asociativo($resultados_cursada, array('valor'), 'descr');

		$resultados_promocion = toba::consulta_php('co_escalas_notas')->get_resultados_instancia(comision::inst_promocionar);
		$resultados_promocion = rs_convertir_asociativo($resultados_promocion, array('valor'), 'descr');

		$condiciones = toba::consulta_php('co_condiciones_regularidad')->get_listado();
		$condiciones = rs_convertir_asociativo($condiciones, array('cond_regularidad'), 'nombre');

		$imp = array();
		foreach ($datos_ml as $dml) {
			$dato = $dml;
			if (isset($dml['cond_regularidad'])) {
				$dato['cond_regularidad'] = $condiciones[$dml['cond_regularidad']];
			} else {
				$dato['cond_regularidad'] = ' ';
			}
			if ($dml['instancia_cursada']) {
				if (isset($dml['resultado_cursada'])) {
					$dato['resultado'] = $resultados_cursada[$dml['resultado_cursada']];
				} else {
					$dato['resultado'] = ' ';
				}
				$dato['fecha'] = !empty($dml['fecha_regular']) ? $dml['fecha_regular'] : ' ';
				$dato['nota'] = !empty($dml['nota_cursada']) ? $dml['nota_cursada'] : ' ';
				$dato['instancia'] = $this->s__nombre_instancia_cursada;
				$imp[] = $dato;
				
				if ($dml['instancia_promocion']) {
					$dato['cond_regularidad'] = ' ';
					$dato['documento_principal'] = ' ';
					$dato['apellido_y_nombre'] = ' ';
					if (isset($dml['resultado_promocion'])) {
						$dato['resultado'] = $resultados_promocion[$dml['resultado_promocion']];
					} else {
						$dato['resultado'] = ' ';
					}
					$dato['fecha'] = !empty($dml['fecha_promocion']) ? $dml['fecha_promocion'] : ' ';
					$dato['nota'] = !empty($dml['nota_promocion']) ? $dml['nota_promocion'] : ' ';
					$dato['instancia'] = $this->s__nombre_instancia_promocion;
					$imp[] = $dato;
				}
			} else {
				if ($dml['instancia_promocion']) {
					if (isset($dml['resultado_promocion'])) {
						$dato['resultado'] = $resultados_promocion[$dml['resultado_promocion']];
					} else {
						$dato['resultado'] = ' ';
					}
					$dato['cond_regularidad'] = ' ';
					$dato['fecha'] = !empty($dml['fecha_promocion']) ? $dml['fecha_promocion'] : ' ';
					$dato['nota'] = !empty($dml['nota_promocion']) ? $dml['nota_promocion'] : ' ';
					$dato['instancia'] = $this->s__nombre_instancia_promocion;
					$imp[] = $dato;
				}
			}
		}

		$this->dep('cuadro_impresion')->set_datos($imp);

		parent::vista_jasperreports($report);
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
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

	function get_fecha_inicio_periodo_comision()
	{
		return $this->s__datos['fecha_inicio_periodo'];
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id])); 
			";
		}
	}
}