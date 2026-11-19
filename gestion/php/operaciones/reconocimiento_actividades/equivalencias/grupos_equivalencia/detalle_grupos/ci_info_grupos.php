<?php
/**
 *
 *
 * @package Equivalencias
 * @subpackage Detalle Grupos Equivalencias
 */
class ci_info_grupos extends guarani_pers_ci
{
	//Matriz de Equivalencia
	protected $s__matriz_equivalencia;
	
	//Elementos Visuales de información
	protected $ev_info_datos;
	
	function ini()
	{
		$parametro_matriz = toba::memoria()->get_parametro('matriz_equivalencia');
		if (!empty($parametro_matriz)) {
			$this->s__matriz_equivalencia = $parametro_matriz;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos = toba::consulta_php('co_matrices_de_equivalencia')->get_datos_matriz_con_props_planes_versiones($this->s__matriz_equivalencia);
		$this->ev_info_datos()->set_datos($datos);
	}
	
	//---- cuadro_actividades_grupos ----------------------------------------------------
	
	function conf__cuadro_actividades_grupos(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_matrices_de_equivalencia')->get_datos_actividades_grupos_equivalencia($this->s__matriz_equivalencia);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_datos()
	{
		if (!isset($this->ev_info_datos)) {
			$this->ev_info_datos = guarani::ev_info_datos();
			$this->ev_info_datos->set_controlador($this);
			$this->ev_info_datos->set_columnas_a_mostrar(array('nombre', 'alcance', 'nro_resolucion_descr', 'aplicacion_automatica_descr', 'activo_descr', 'propuesta_destino_nombre', 'plan_destino_nombre', 'plan_version_destino_nombre', 'propuesta_origen_nombre', 'plan_origen_nombre'));
			$this->ev_info_datos->set_td_etiqueta_width('90px');

			$this->ev_info_datos->set_columnas_fila(array('nombre'), 1);
			$this->ev_info_datos->set_columnas_fila(array('alcance', 'nro_resolucion_descr'), 2);
			$this->ev_info_datos->set_columnas_fila(array('aplicacion_automatica_descr', 'activo_descr'), 3);
			$this->ev_info_datos->set_columnas_fila(array('propuesta_destino_nombre'), 4);
			$this->ev_info_datos->set_columnas_fila(array('plan_destino_nombre'), 5);
			$this->ev_info_datos->set_columnas_fila(array('plan_version_destino_nombre'), 6);
			$this->ev_info_datos->set_columnas_fila(array('propuesta_origen_nombre'), 7);
			$this->ev_info_datos->set_columnas_fila(array('plan_origen_nombre'), 8);

			$this->ev_info_datos->set_etiqueta('nombre', 'Nombre');
			$this->ev_info_datos->set_etiqueta('alcance', 'Alcance');
			$this->ev_info_datos->set_etiqueta('nro_resolucion_descr', 'N° Resolución');
			$this->ev_info_datos->set_etiqueta('aplicacion_automatica_descr', 'Aplicación Automática');
			$this->ev_info_datos->set_etiqueta('activo_descr', 'Activo');
			$this->ev_info_datos->set_etiqueta('propuesta_destino_nombre', 'Propuesta Destino');
			$this->ev_info_datos->set_etiqueta('plan_destino_nombre', 'Plan');
			$this->ev_info_datos->set_etiqueta('plan_version_destino_nombre', 'Versión');
			$this->ev_info_datos->set_etiqueta('propuesta_origen_nombre', 'Propuesta Origen');
			$this->ev_info_datos->set_etiqueta('plan_origen_nombre', 'Plan');
		}
		return $this->ev_info_datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		//Se recupera el id en javascript del cuadro para atender su evento Cerrar, y provocar que se cierre la ventana popup.
		$id_js_cuadro = $this->dependencia('cuadro_actividades_grupos')->get_id_objeto_js();
		echo "
			{$id_js_cuadro}.evt__cerrar = function() {
				window.close();
			}
		";
	}
	
}
?>