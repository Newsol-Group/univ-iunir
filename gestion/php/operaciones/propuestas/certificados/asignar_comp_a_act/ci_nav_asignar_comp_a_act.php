<?php
/**
 * 
 * 
 * @package 	Propuestas Formativas
 * @subpackage 	Certificados
 *
 */
class ci_nav_asignar_comp_a_act extends ci_navegacion 
{
	protected $ev_info;
	
	protected $s__datos_planes;
	
	function get_msg_eof_con_filtro($etiqueta, $filtro)
	{
		$indx_msg = '800_eof_cuadro';
		if (!empty($filtro)) {
			$indx_msg .= '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('propuesta_datos', 'plan_datos', 'version_nombre', 'certificado_nombre', 'elemento_nombre'));
			
			$this->ev_info->set_columnas_fila(array('propuesta_datos'), 1);
			$this->ev_info->set_columnas_fila(array('plan_datos', 'version_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('certificado_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('elemento_nombre'), 4);
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_etiqueta('propuesta_datos', 'Propuesta');
			
			$this->ev_info->set_etiqueta('plan_datos', 'Plan');
			$this->ev_info->set_etiqueta('version_nombre', 'Versión');
			
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado');
			
			$this->ev_info->set_etiqueta('elemento_nombre', 'Actividad');
			
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_plan
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_planes(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_planes', false);
	}

	function evt__filtro_planes__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_planes', $datos);
	}

	function evt__filtro_planes__cancelar()
	{
		$this->filtro_limpiar('filtro_planes');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_planes(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_planes');
		$this->s__datos_planes = $this->cn()->get_listado_planes($filtro);
		$cuadro->set_datos($this->s__datos_planes);
		if (empty($this->s__datos_planes)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
			$msj = $this->get_msg_eof_con_filtro($etiqueta->get_enf('plural'), $filtro);
			$cuadro->set_eof_mensaje($msj);
		}
	}

	function evt__cuadro_planes__seleccion($seleccion)
	{
		$this->cn()->seleccionar_plan($seleccion);
		$this->set_pantalla('sel_certificado');
	}
	
	function conf_evt__cuadro_planes__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (!((isset($this->s__datos_planes[$fila]['escala_cumplimiento'])) 
				&& (!empty($this->s__datos_planes[$fila]['escalas_cumplimiento_activo']))
				&& ($this->s__datos_planes[$fila]['escalas_cumplimiento_activo'] == comunes::si))) {
			$evento->anular();
		}
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_certificado
	//-----------------------------------------------------------------------------------
	
	function conf__sel_certificado(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->cn()->get_ev_info_datos());
	}
	
	function evt__volver_certificado()
	{
		$this->cn()->deseleccionar_plan();
		$this->set_pantalla('sel_plan');
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_certificados ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_certificados(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_certificados', false);
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_certificados__filtrar($datos)
	{
		$filtro_ra = '';
		
		if ((isset($datos['responsable_academica']))) {
			$ra = toba::db()->quote($datos['responsable_academica']['valor']);
			$filtro_ra = " AND EXISTS(	SELECT 	1 
									FROM 	sga_certificados_ra
									WHERE	sga_certificados_ra.certificado = sga_certificados.certificado
											AND sga_certificados_ra.responsable_academica = $ra)";
			$this->dep('filtro_certificados')->columna('responsable_academica')->resetear_estado();
		}
		
		$this->filtro_filtrar('filtro_certificados', $datos);
		
		$this->s__filtros['filtro_certificados'] .= $filtro_ra;		
	}

	function evt__filtro_certificados__cancelar()
	{
		$this->filtro_limpiar('filtro_certificados');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_certificados ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_certificados(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_certificados');
		$datos = $this->cn()->get_listado_certificados($filtro);
		$cuadro->set_datos($datos);	
		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_certificados');
			$msj = $this->get_msg_eof_con_filtro($etiqueta->get_enf('plural'), $filtro);
			$cuadro->set_eof_mensaje($msj);
		}
	}

	function evt__cuadro_certificados__seleccion($seleccion)
	{
		$this->cn()->seleccionar_certificado($seleccion);
		$this->set_pantalla('sel_actividad');
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA sel_actividad
	//-----------------------------------------------------------------------------------
	
	function conf__sel_actividad(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->cn()->get_ev_info_datos());
	}
	
	function evt__volver_actividad()
	{
		$this->cn()->deseleccionar_certificado();
		$this->set_pantalla('sel_certificado');
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_actividades', false);
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_actividades', $datos);
	}

	function evt__filtro_actividades__cancelar()
	{
		$this->filtro_limpiar('filtro_actividades');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_actividades');
		$datos = $this->cn()->get_listado_actividades($filtro);
		$cuadro->set_datos($datos);	
		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
			$msj = $this->get_msg_eof_con_filtro($etiqueta->get_enf('plural'), $filtro);
			$cuadro->set_eof_mensaje($msj);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->cn()->seleccionar_actividad($seleccion);
		$this->set_pantalla('edicion');
	}	

	
	//-----------------------------------------------------------------------------------
	//---- Eventos pantalla edición -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->cn()->deseleccionar_actividad();
		$this->cn()->resetear();
		$this->set_pantalla('sel_actividad');
	}

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			
			/**
			 * Por pedido en ticket #623 el día 24/01/2014, se redirije al usuario 
			 * a la pantalla de "Actividades" en lugar de a la de "Planes" (para 
			 * que pueda seleccionar otra actividad de ese plan y seguir cargando 
			 * competencias). 
			 */
			$this->cn()->deseleccionar_actividad();
			$this->cn()->resetear();
			$this->set_pantalla('sel_actividad');
			
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}

}
?>