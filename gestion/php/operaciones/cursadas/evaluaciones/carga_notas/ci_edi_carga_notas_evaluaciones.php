<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class ci_edi_carga_notas_evaluaciones extends ci_reporte
{
	// Datos temporales para el formulario multilínea de notas.
	protected $datos_notas_temp;
	
	// Datos para paginado.
	protected $s__pagina_actual = 1;
	protected $s__tamanio_pagina = 20;
	protected $s__cantidad_paginas;
		
	// Componente Visual de Información de datos de la evaluación.
	protected $ev_info_evaluacion;
	
	// Impresión.
	protected $s__imprimir = false;

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
		$this->s__datos = $this->controlador()->get_datos_evaluacion();
	}

	function conf__pant_inicial()
	{
		$datos = $this->s__datos;
		$nombre_escala = (!empty($datos['nombre_escala_nota'])) ? $datos['nombre_escala_nota'] : '';
		$datos['icono_info_escala_notas'] = $nombre_escala . ' ' . $this->get_html_icono_info_escala_notas();
		if (guarani::co('co_evaluaciones')->es_hijo_de_evaluacion($datos['evaluacion'])) {
			$datos['icono_info_eval_relacionadas'] = $this->get_html_icono_info_eval_relacionadas();
		}
		$this->ev_info_evaluacion()->set_datos($datos);
	}

	function ev_info_evaluacion()
	{
		if (!isset($this->ev_info_evaluacion)) {
			$this->ev_info_evaluacion = guarani::ev_info_datos();
			$this->ev_info_evaluacion->set_columnas_a_mostrar(array('nombre_comision', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'nombre_evaluacion', 'fecha_visual', 'icono_info_escala_notas', 'icono_info_eval_relacionadas'));

			$this->ev_info_evaluacion->set_columnas_fila(array('nombre_comision', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info_evaluacion->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info_evaluacion->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info_evaluacion->set_columnas_fila(array('nombre_evaluacion', 'fecha_visual'), 4);
			$this->ev_info_evaluacion->set_columnas_fila(array('icono_info_escala_notas', 'icono_info_eval_relacionadas'), 5);

			$this->ev_info_evaluacion->set_max_columnas_por_fila(3);
			$this->ev_info_evaluacion->set_etiqueta('nombre_comision', 'Comisión');
			$this->ev_info_evaluacion->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info_evaluacion->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info_evaluacion->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info_evaluacion->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info_evaluacion->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info_evaluacion->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info_evaluacion->set_etiqueta('nombre_evaluacion', 'Evaluación');
			$this->ev_info_evaluacion->set_etiqueta('fecha_visual', 'Fecha');
			$this->ev_info_evaluacion->set_etiqueta('icono_info_escala_notas', 'Escala de Notas');
			$this->ev_info_evaluacion->set_etiqueta('icono_info_eval_relacionadas', 'Evaluaciones Relacionadas');
		}
		return $this->ev_info_evaluacion;
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

	//-----------------------------------------------------------------------------------
	//---- form_ml_notas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_notas(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_notas_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_notas_temp);
		} else {
			$datos = $this->cn()->get_detalles(array('agregado' => true), $this->get_columna_ordenamiento());
			$form_ml->set_datos($this->generar_paginado($datos));
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

	function evt__form_ml_notas__primera($datos)
	{
		try {
			$this->cn()->procesar_detalles($datos);
			$this->s__pagina_actual = 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__anterior($datos)
	{
		try {
			$this->cn()->procesar_detalles($datos);
			$this->s__pagina_actual = $this->s__pagina_actual - 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__siguiente($datos)
	{
		try {
			$this->cn()->procesar_detalles($datos);
			$this->s__pagina_actual = $this->s__pagina_actual + 1;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__ultima($datos)
	{
		try {
			$this->cn()->procesar_detalles($datos);
			$this->s__pagina_actual = $this->s__cantidad_paginas;
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__modificacion($datos)
	{
		try {
			$this->cn()->procesar_detalles($datos);
		} catch (guarani_error $e) {
			$this->datos_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
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

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
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

	/**
	 * Retorna el nombre de la columna por la cual se ordenarán los detalles,
	 * según el valor definido para el parámetro del sistema 'cur_orden_detalle_actas'.
	 */
	function get_columna_ordenamiento()
	{
		$ordenamiento = guarani::parametros()->get_valor('cur_orden_detalle_actas');
		if ($ordenamiento == 1) {
			$columna = 'legajo';
		} elseif ($ordenamiento == 2) {
			$columna = 'apellido_y_nombre';
		} elseif ($ordenamiento == 3) {
			$columna = 'documento_principal';
		}
		return $columna;
	}

	/**
	 * Retorna el código de la escala de notas asociada a la evaluación.
	 */
	function get_escala_notas()
	{
		return $this->s__datos['escala_nota'];
	}

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
	 */
	function get_html_icono_info_escala_notas()
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000054', array('escala' => $this->s__datos['escala_nota']), array('celda_memoria' => 'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'420'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
		$salida .= '</a>';

		return $salida;
	}

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de resultados en evaluaciones relacionadas.
	 */
	function get_html_icono_info_eval_relacionadas()
	{
		$datos_eval = $this->cn()->get_datos_evaluacion();
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '41000126', array('evaluacion' => $datos_eval['evaluacion']), array('celda_memoria' => 'evaluacion'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'1000','height':'600'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver evaluaciones relacionadas');
		$salida .= '</a>';

		return $salida;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__pdf()
	{
		$this->s__imprimir = true;
	}

	function evt__cerrar_evaluacion()
	{
		$this->cn()->eliminar_alumnos_sin_resultado();
		$this->cn()->cerrar_evaluacion();
		$this->controlador()->evt__cancelar_alumnos();
		toba::notificacion()->agregar('Se ha cerrado la evaluación con éxito.', 'info');
	}

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		// ----------------------------------------------------------
		//	Configuraciones Iniciales
		// ----------------------------------------------------------
		// Se modifican las dimensiones de la página y los márgenes.
		$this->xml_set_margenes(array('sup' => '0.3cm', 'inf' => '0.3cm', 'izq' => '0.3cm', 'der' => '0.3cm'));
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------
		// Se busca el path del logo.
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// Primera tabla (que reemplaza a la cabecera).
		$this->xml_set_tabla_cols();

		// Se setean los datos de la primer tabla.
		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'border' => 'none'), array('valor' => '', 'border' => 'none', 'number-columns-spanned' => 2));
		$filas[] = array(array('valor' => $this->xml_texto($this->s__titulo, array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center')), 'border-bottom' => 'solid black', 'text-align' => 'center', 'number-columns-spanned' => 3));

		// Se genera la primera tabla.
		$cabecera = $this->xml_tabla($filas);

		// Se generan los datos básicos del acta y se resetean las columnas de tablas y $fila.
		$cols = array();
		$cols[] = array('column-width' => '2cm');
		$cols[] = array('column-width' => '5cm');
		$cols[] = array('column-width' => '3cm');
		$cols[] = array('column-width' => '4cm');
		$cols[] = array('column-width' => '3cm');
		$cols[] = array('column-width' => '3cm');
		$this->xml_set_tabla_cols($cols);

		$filas = array();
		$filas[] = array(
			array('valor' => 'EVALUACIÓN: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_evaluacion'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'COMISIÓN: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_comision'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'TURNO: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_turno'], 'border' => 'none', 'text-align' => 'left'),
		);
		$filas[] = array(
			array('valor' => 'FECHA: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['fecha_visual'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'AÑO ACADÉMICO: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['anio_academico_periodo'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'PERÍODO LECTIVO: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_periodo'], 'border' => 'none', 'text-align' => 'left'),
		);
		$filas[] = array(
			array('valor' => 'ACTIVIDAD: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['codigo_y_nombre_actividad'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'UBICACIÓN: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_ubicacion'], 'border' => 'none', 'text-align' => 'left'),
			array('valor' => 'MODALIDAD: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left'),
			array('valor' => $this->s__datos['nombre_modalidad'], 'border' => 'none', 'text-align' => 'left'),
		);

		// Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	PIE
		// ----------------------------------------------------------
		$fecha_impresion = guarani_fecha::get_ahora(true);
		$this->xml_set_tabla_cols();

		// Se setean los datos de la tabla.
		$filas = array();
		$filas[] = array(array('valor' => '', 'text-align' => 'left', 'border' => 'none'));
		$filas[] = array(array('valor' => 'IMPRESO: ' . $fecha_impresion, 'text-align' => 'left', 'border' => 'none'));

		// Se genera la tabla.
		$pie = $this->xml_tabla($filas);
		$this->xml_set_pie($pie);

		$xml = $this->xml_get_tag_inicio();

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------	
		// Se agregan los datos de los alumnos.
		$datos_ml = $this->cn()->get_detalles(array('agregado' => true), $this->get_columna_ordenamiento());
		$cant_datos_ml = guarani_arreglos::count($datos_ml);
		for ($i = 0; $i < $cant_datos_ml; $i++) {
			if (isset($datos_ml[$i]['resultado'])) {
				$datos_ml[$i]['resultado'] = escalas_notas::get_resultado_descripcion($datos_ml[$i]['resultado']);
			}
		}
		$this->dep('cuadro_impresion')->set_datos($datos_ml);
		$xml .= $this->dep('cuadro_impresion')->vista_xml(false, $xmlns);

		// ----------------------------------------------------------
		//	Configuraciones Finales
		// ----------------------------------------------------------
		$xml .= $this->xml_get_tag_fin();

		return $xml;
	}

	function cuadro()
	{
		return $this->dep('cuadro_impresion');
	}

	function datos_encabezado()
	{
		$datos = $this->ev_info_evaluacion()->get_datos();
		// Se elimina la quinta fila no deseada en el encabezado del reporte
		unset($datos[5]);
		return $datos;
	}

	function get_titulo()
	{
		return 'REPORTE DE RESULTADOS DE EVALUACIONES';
	}

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		//Se agregan los datos de los alumnos
		$datos_ml = $this->cn()->get_detalles(array('agregado' => true), $this->get_columna_ordenamiento());
		$cant_datos_ml = guarani_arreglos::count($datos_ml);
		for ($i = 0; $i < $cant_datos_ml; $i++) {
			if (!isset($datos_ml[$i]['nota'])) {
				$datos_ml[$i]['nota'] = '';
			}
			if (isset($datos_ml[$i]['resultado'])) {
				$datos_ml[$i]['resultado'] = escalas_notas::get_resultado_descripcion($datos_ml[$i]['resultado']);
			}
			if (!isset($datos_ml[$i]['corregido_por'])) {
				$datos_ml[$i]['corregido_por'] = ' ';
			}
			if (!isset($datos_ml[$i]['observaciones'])) {
				$datos_ml[$i]['observaciones'] = ' ';
			}
		}
		$this->dep('cuadro_impresion')->set_datos($datos_ml);

		parent::vista_jasperreports($report);
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id]));
			";
		}

		// Se obtienen las cantidades de alumnos no elegidos en la pantalla anterior con y sin resultado.
		$alumnos_no_agregados = $this->cn()->get_cantidades_alumnos_no_agregados();

		$msj_confirmacion = toba::proyecto()->get_mensaje_proyecto('800CUR_confirmar_cierre_parcial');
		$msj_confirmacion = $msj_confirmacion['m'];

		echo "
			//---- Eventos ---------------------------------------------
			
			{$this->objeto_js}.evt__cerrar_evaluacion = function()
			{
				var alumnos_con_resultado = {$alumnos_no_agregados['cant_con_resultado']};
				var alumnos_sin_resultado = {$alumnos_no_agregados['cant_sin_resultado']};
				var form_ml = this.dep('form_ml_notas');
				var filas = form_ml.filas();

				for (id_fila in filas) {
					if (form_ml.ef('resultado').ir_a_fila(filas[id_fila]).tiene_estado()) {
						alumnos_con_resultado+=1;
					} else {
						alumnos_sin_resultado+=1;
					}
				}

				var msj = '$msj_confirmacion';
				var msj_con_params = msj.replace('%1%', alumnos_con_resultado).replace('%2%', alumnos_sin_resultado);
				return confirm(msj_con_params);
			}
		";
	}

	/**
	 * Retorna el código JS necesario para validar las notas de las distintas escalas de la comisión.
	 */
	function get_js_validar_nota_escalas()
	{
		$escalas = array($this->s__datos['escala_nota'] => array('escala_nota' => $this->s__datos['escala_nota']));

		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}

}
?>