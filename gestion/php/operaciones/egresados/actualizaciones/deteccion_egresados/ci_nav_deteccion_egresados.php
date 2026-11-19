<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_deteccion_egresados extends ci_nav_procesos_bk
{
	protected $s__datos;

	function set_nombre_proceso() {
		$this->s__nombre_proceso = pro_deteccion_egresados::nombre;
	}
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__datos)) {
			$form->set_datos($this->s__datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__datos = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- resultados -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_formulario $form)
	{
		parent::conf__resultados($form);
		//Se agrega la desactivación del botón rechazar si el ef asociado es <> 0
		if ($this->modo_visualizacion()){
			if ($form->existe_ef('alumnos_en_condiciones') && $form->existe_evento('rep_en_condiciones') && $form->ef('alumnos_en_condiciones')->get_estado() == 0)
				$form->evento('rep_en_condiciones')->desactivar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		// Conjunto de alumnos del plan-versión que aún no solicitaron el certificado.
		$alumnos = toba::consulta_php('co_tramites_certificacion')->get_alumnos_plan_version_anios_certificado($this->s__datos['plan_version'], $this->s__datos['anio_desde'], $this->s__datos['anio_hasta'], $this->s__datos['certificado']);

		if (empty($alumnos)) {
			throw guarani::error(guarani_mensajes::get('800EGR_eof_alumnos'));
		} else {
			$this->cn()->ejecutar_proceso(array('certificado' => $this->s__datos['certificado'],
												'plan_version' => $this->s__datos['plan_version'],
												'anio_desde' => $this->s__datos['anio_desde'],
												'anio_hasta' => $this->s__datos['anio_hasta'],
												'alumnos' => $alumnos));
			$this->set_pantalla('procesamiento');
		}
	}

	function evt__volver()
	{
		unset($this->s__datos);
		$this->set_pantalla('seleccion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
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
		$contenido = $this->cn()->get_resultado_proceso(pro_deteccion_egresados::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		$reporte = pro_deteccion_egresados::reporte_en_condiciones;
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
			$datos_conf = $this->controlador()->get_resultado_reporte(pro_deteccion_egresados::reporte_resultados);
			$this->s__datos = current($datos_conf['mensajes']);
		} else {
			//Obtengo el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}
		guarani_datos_impresion::instancia()->set_datos(array(	'datos' => $datos['mensajes'],
																'reporte' => pro_deteccion_egresados::reporte_en_condiciones,
																'titulo' => 'Alumnos en condiciones de obtener certificación',
																'ev_info_datos' => $this->get_datos_ev_info()));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000079', null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_ev_info()
	{
		$ev_info_datos = array();

		$datos_plan_version = current(toba::consulta_php('co_planes')->get_datos_plan_version($this->s__datos['plan_version']));
		$ev_info_datos['propuesta'] = $datos_plan_version['propuesta_nombre'];
		$ev_info_datos['plan'] = $datos_plan_version['plan_nombre'];
		$ev_info_datos['plan_version'] = $datos_plan_version['version_nombre'];

		$ev_info_datos['anios'] = ($this->s__datos['anio_desde'] == $this->s__datos['anio_hasta']) ? 'Ingresantes del año académico '.$this->s__datos['anio_hasta'] : 'Ingresantes desde el año académico '.$this->s__datos['anio_desde'].' hasta el '.$this->s__datos['anio_hasta'];

		$datos_certificado = toba::consulta_php('co_certificados')->get_certificado($this->s__datos['certificado']);
		$ev_info_datos['certificado'] = $datos_certificado['nombre'];

		return $ev_info_datos;
	}
}
?>