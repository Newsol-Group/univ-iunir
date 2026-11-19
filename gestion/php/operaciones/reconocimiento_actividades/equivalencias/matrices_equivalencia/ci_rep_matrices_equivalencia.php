<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_rep_matrices_equivalencia extends ci_rep_sin_listado_inicial
{
	protected $s__matriz_seleccionada;

	//Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		//Se desactiva el reciclado de memoria
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}
	
	function get_tabla_principal()
	{
		return 'sga_equiv_matrices';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_matrices_de_equivalencia')->get_listado_con_prop_plan_version_origen_y_destino($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__matriz_seleccionada = $seleccion['matriz'];
		$this->set_pantalla('pant_detalle');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DETALLE -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_detalle()
	{
		if (isset($this->s__matriz_seleccionada)) {
			$datos = toba::consulta_php('co_matrices_de_equivalencia')->get_datos_matriz_con_props_planes_versiones($this->s__matriz_seleccionada);
			$this->ev_info()->set_datos($datos);
		}
	}
	
	//---- cuadro_detalle ---------------------------------------------------------------
	
	function conf__cuadro_detalle(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_matrices_de_equivalencia')->get_datos_actividades_grupos_equivalencia($this->s__matriz_seleccionada);
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_grupos');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width:100%;');
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'alcance_descr', 'nro_resolucion_descr', 'aplicacion_automatica_descr', 'activo_descr', 'nro_resolucion_baja_descr', 'fecha_baja_format', 'propuesta_destino_nombre', 'plan_destino_nombre', 'plan_version_destino_nombre', 'propuesta_origen_nombre', 'plan_origen_nombre'));
			$this->ev_info->set_columnas_fila(array('nombre'), 1);
			$this->ev_info->set_columnas_fila(array('alcance_descr', 'nro_resolucion_descr'), 2);
			$this->ev_info->set_columnas_fila(array('aplicacion_automatica_descr', 'activo_descr'), 3);
			$this->ev_info->set_columnas_fila(array('nro_resolucion_baja_descr', 'fecha_baja_format'), 4);
			$this->ev_info->set_columnas_fila(array('propuesta_destino_nombre'), 5);
			$this->ev_info->set_columnas_fila(array('plan_destino_nombre'), 6);
			$this->ev_info->set_columnas_fila(array('plan_version_destino_nombre'), 7);
			$this->ev_info->set_columnas_fila(array('propuesta_origen_nombre'), 8);
			$this->ev_info->set_columnas_fila(array('plan_origen_nombre'), 9);
			$this->ev_info->set_etiqueta('nombre', 'Nombre:');
			$this->ev_info->set_etiqueta('alcance_descr', 'Alcance:');
			$this->ev_info->set_etiqueta('nro_resolucion_descr', 'N° Resolución:');
			$this->ev_info->set_etiqueta('aplicacion_automatica_descr', 'Aplic. Automática:');
			$this->ev_info->set_etiqueta('activo_descr', 'Activo:');
			$this->ev_info->set_etiqueta('nro_resolucion_baja_descr', 'N° Resolución Baja:');
			$this->ev_info->set_etiqueta('fecha_baja_format', 'Fecha Baja:');
			$this->ev_info->set_etiqueta('propuesta_destino_nombre', 'Propuesta Destino:');
			$this->ev_info->set_etiqueta('plan_destino_nombre', 'Plan Destino:');
			$this->ev_info->set_etiqueta('plan_version_destino_nombre', 'Versión Destino:');
			$this->ev_info->set_etiqueta('propuesta_origen_nombre', 'Propuesta Origen:');
			$this->ev_info->set_etiqueta('plan_origen_nombre', 'Plan Origen:');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__matriz_seleccionada);
		$this->set_pantalla('pant_inicial');
	}
}
?>