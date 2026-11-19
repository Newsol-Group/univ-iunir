<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_reprocesar_inscripciones_actividades extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $inscripciones_seleccionadas;

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_reprocesar_inscripciones_actividades::nombre;
	}

	function evt__resultados_visualizacion__salida()
	{
		// Se agrega esta función vacía porque en el padre se limpian las variables de sesión.
	}

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	function conf__rechazo()
	{
		if ($this->modo_visualizacion()) {
			$reporte = pro_reprocesar_inscripciones_actividades::reporte_a_rechazar;
			$inscripciones = $this->get_resultado_reporte($reporte);
		} else {
			$inscripciones = $this->cn()->get_inscripciones_a_rechazar();
		}
		toba::memoria()->set_dato('requisitos_no_cumplidos', rs_convertir_asociativo_matriz($inscripciones, array('inscripcion'), array('observaciones','requisitos')));
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('periodo')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
	}
	
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$propuesta = $datos[$fila]['propuesta'];
		if (!inscripcion_cursada::ejecutar_controles($propuesta)) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- resultados -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_formulario $form)
	{
		parent::conf__resultados($form);
		// Se agrega la desactivación del botón rechazar si el ef asociado es <> 0
		if ($this->modo_visualizacion()) {
			if ($form->existe_ef('a_rechazar') && $form->existe_evento('rechazar') && $form->ef('a_rechazar')->get_estado() == 0) {
				$form->evento('rechazar')->desactivar();
			}
		}
	}

	function evt__resultados__rechazar()
	{
		$this->set_pantalla('rechazo');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_rechazo ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_rechazo(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje($this->get_msg_eof());
		if ($this->modo_visualizacion()) {
			$reporte = pro_reprocesar_inscripciones_actividades::reporte_a_rechazar;
			$inscripciones = $this->get_resultado_reporte($reporte);
			$inscripciones = rs_convertir_asociativo_matriz($inscripciones, array('inscripcion'));
			if (isset($inscripciones['inscripcion'])) {
				$cuadro->set_datos(rs_convertir_asociativo_matriz($inscripciones, array('inscripcion')));
			}
		} else {
			$cuadro->set_datos($this->cn()->get_inscripciones_a_rechazar());
		}
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
		$evento->vinculo()->agregar_parametro(utf8_encode('documento_principal'), utf8_encode($datos_fila['documento_principal']));
		if (!empty($datos_fila['legajo'])) {
			$evento->vinculo()->agregar_parametro(utf8_encode('legajo'), utf8_encode($datos_fila['legajo']));
		}
		$evento->vinculo()->agregar_parametro(utf8_encode('actividad_nombre'), utf8_encode($datos_fila['actividades']));
		$evento->vinculo()->agregar_parametro(utf8_encode('instancia_nombre'), utf8_encode($datos_fila['instancias']));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__reprocesar()
	{
		if (guarani_arreglos::count($this->s__seleccion) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_insc_cursada');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		
		$propuestas = toba::consulta_php('co_inscripciones_cursadas')->get_propuestas_inscripciones($this->s__seleccion);
		$this->cn()->cargar_atributos_parametros($propuestas);
		
		$parametros = array('inscripciones' => $this->s__seleccion, 'operacion' => guarani::get_id_operacion());
		$this->cn()->ejecutar_proceso($parametros);
		$this->set_pantalla('procesamiento');
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
			$etiqueta = guarani_etiquetas::get_tabla('sga_insc_cursada');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		$this->cn()->rechazar_inscripciones_cursada($this->inscripciones_seleccionadas);
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento
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
		$contenido = $this->cn()->get_resultado_proceso(pro_reprocesar_inscripciones_actividades::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		$reporte = pro_reprocesar_inscripciones_actividades::reporte_modificadas;
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		guarani_datos_impresion::instancia()->set_datos(array(
			'inscripciones' => $datos['mensajes'],
			'reporte' => pro_reprocesar_inscripciones_actividades::reporte_modificadas,
			'titulo' => 'Inscripciones Modificadas'
		));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000045', null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
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

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resultados_visualizacion(toba_ei_pantalla $pantalla)
	{
		// No se permite rechazar inscripciones que no pasaron los controles en ejecuciones anteriores.
		$this->dep('resultados')->evento('rechazar')->anular();
	}

}
?>