<?php
class ci_nav_generacion_masiva_actas_promocion extends ci_nav_procesos_bk
{
	// Componente Visual de Información.
	protected $s__datos_ev_info;
	protected $ev_info;

    function set_nombre_proceso()
    {
        $this->s__nombre_proceso = pro_generacion_actas_cursada::nombre;
    }
    
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: comisiones
	//-----------------------------------------------------------------------------------
	
	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if (!empty($datos)) {
			$pantalla->set_descripcion('Sólo pueden seleccionarse comisiones para las que el parámetro que determina el modo de carga del número de acta de promoción (<strong>cur_carga_manual_nro_acta_promocion</strong>) esté definido como <strong>automático</strong>.');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
		} else {
			$msg_eof = $this->get_msg_eof('Comisiones');

			$datos = $this->cn()->get_listado(acta::origen_promociones, $this->get_filtro_condicion());
			$this->s__datos_ev_info = current($datos);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$comision = $datos[$fila]['comision'];
		
		$operacion = guarani::get_id_operacion();
		$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		
		if (guarani::parametros()->get_valor('cur_carga_manual_nro_acta_promocion', $atributos) == comunes::si) {
			$evento->anular();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: resultados
	//-----------------------------------------------------------------------------------

	function conf__pant_resultados(toba_ei_pantalla $pantalla)
	{        
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(toba_ei_formulario $form)
	{
		parent::conf__form_totales($form);
		$form->set_solo_lectura();
		$form->evento('imprimir_actas')->vinculo()->set_parametros(array('operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
		$form->evento('imprimir_actas')->vinculo()->set_celda_memoria('celda_imp_prom');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

    function evt__volver_inicio()
	{
		$this->set_pantalla('comisiones');
	}
    
	function evt__volver()
	{
		unset($this->s__seleccion);
		$this->set_pantalla('comisiones');
	}

	function evt__generar()
	{
		if (guarani_arreglos::count($this->s__seleccion) == 0) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Comisión'));
			toba::notificacion()->error($mje);
		} else {
			$parametros = array('comisiones' => $this->s__seleccion, 'origen' => acta::origen_promociones);
			$this->cn()->ejecutar_proceso($parametros);
			$this->set_pantalla('pant_resultados');
		}
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_generacion_actas_cursada::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
		
		$datos = $this->cn()->get_resultado_proceso(pro_generacion_actas_cursada::reporte_actas_generadas);
		guarani_datos_impresion::instancia()->set_datos($datos['mensajes']);
		guarani_datos_impresion::instancia()->set_conf($this->s__datos_ev_info);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
    }
    
    function ajax__reporte($params, toba_ajax_respuesta $respuesta)
    {        
        switch ($params['evento']){
			case 'rep_actas_generadas':
				$reporte = pro_generacion_actas_cursada::reporte_actas_generadas;
				$titulo = 'Listado de Actas de Promoción Generadas Masivamente';
                $operacion = '34000026';
				break;        
        }        
        if ($this->s__modo_visualizacion) {
			$datos = $this->get_resultado_reporte($reporte);
		} else {
			// Obtengo el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}      
		
		// Se almacenan los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(
			'actas' => $datos['mensajes'], //actas
			'titulo' => $titulo)
		);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
		
		// Se crea el vinculo para que se abra en el popup
        $vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('width: 50%;');
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre_periodo'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_periodo'));
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
		}
		return $this->ev_info;
	}
}
?>