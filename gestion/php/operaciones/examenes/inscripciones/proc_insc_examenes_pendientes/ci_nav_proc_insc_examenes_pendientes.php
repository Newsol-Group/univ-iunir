<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_proc_insc_examenes_pendientes extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $inscripciones_seleccionadas;

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_proc_insc_examenes_pendientes::nombre;
	}
        
	function conf__rechazar()
	{
		$inscripciones = $this->cn()->get_inscripciones_a_rechazar();
		toba::memoria()->set_dato('requisitos_no_cumplidos', rs_convertir_asociativo_matriz($inscripciones, array('inscripcion'), array('observaciones','requisitos')));
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- resultados -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__resultados(toba_ei_formulario $form)
	{
		parent::conf__resultados($form);
		if ($this->s__modo_visualizacion) {
			$form->evento('rechazar')->desactivar();
		}
	}
	
	function evt__resultados__rechazar()
	{
		$this->set_pantalla('rechazar');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_rechazo ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_rechazo(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje($this->get_msg_eof());
		$cuadro->set_datos($this->cn()->get_inscripciones_a_rechazar());
	}

	function evt__cuadro_rechazo__seleccion($datos)
	{
		$this->inscripciones_seleccionadas = $datos;
	}

	function conf_evt__cuadro_rechazo__info_errores(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_rechazo')->get_datos();
		$datos_fila = $datos[$fila];

		$evento->vinculo()->agregar_parametro(utf8_encode('inscripcion'), utf8_encode($datos_fila['inscripcion']));
		$evento->vinculo()->agregar_parametro(utf8_encode('apellido_y_nombre'), utf8_encode($datos_fila['apellido_y_nombre']));
		$evento->vinculo()->agregar_parametro(utf8_encode('documento_principal'), utf8_encode($datos_fila['nro_identificacion']));
		if (! empty($datos_fila['legajo'])) {
			$evento->vinculo()->agregar_parametro(utf8_encode('legajo'), utf8_encode($datos_fila['legajo']));
		}
		$evento->vinculo()->agregar_parametro(utf8_encode('actividad_nombre'), utf8_encode($datos_fila['actividad_nombre']));
		$evento->vinculo()->agregar_parametro(utf8_encode('instancia_nombre'), utf8_encode($datos_fila['instancia_nombre']));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		if (guarani_arreglos::count($this->s__seleccion) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_insc_examen');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		$this->procesar();
		$this->set_pantalla('procesar');
	}

	function evt__volver()
	{
		unset($this->s__seleccion);
		$this->cn()->limpiar_memoria();
		$this->set_pantalla('seleccion');
	}
	
	function evt__rechazar()
	{
		if (guarani_arreglos::count($this->inscripciones_seleccionadas) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_insc_examen');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		$this->cn()->rechazar_inscripciones_examen($this->inscripciones_seleccionadas);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Procesamiento ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function procesar()
	{
		$parametros = array('inscripciones' => $this->s__seleccion, 'operacion' => guarani::get_id_operacion());
		$this->cn()->ejecutar_proceso($parametros);
		$this->set_pantalla('procesar');
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
		$contenido = $this->cn()->get_resultado_proceso(pro_proc_insc_examenes_pendientes::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_aceptadas':
				$reporte = pro_proc_insc_examenes_pendientes::reporte_aceptadas;
				$operacion = '41000069';
				$titulo = 'Inscripciones Aceptadas';
				break;
			case 'rep_con_observaciones':
				$reporte = pro_proc_insc_examenes_pendientes::reporte_con_observaciones;
				$operacion = '41000069';
				$titulo = 'Inscripciones con observaciones que pasaron los controles';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->get_resultado_reporte($reporte);
		} else {
			// Obtengo el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}
		
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(	'inscripciones' => $datos['mensajes'],
																'reporte' => $reporte,
																'titulo' => $titulo));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);		
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$mensaje_confirmacion = '¿Confirma que desea rechazar las inscripciones seleccionadas?';
		
		echo "
		
		//---- Eventos ---------------------------------------------
		{$this->objeto_js}.evt__rechazar = function()
		{
			resp = confirm('$mensaje_confirmacion');
			return resp;
		}
		";
	}

}
?>