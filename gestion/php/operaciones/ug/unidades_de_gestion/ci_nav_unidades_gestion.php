<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class ci_nav_unidades_gestion extends ci_navegacion 
{
	function evt__guardar()
	{
		try {
			$this->cn()->guardar();

			$this->dep('editor')->dep('editor_dominio')->resetear();
			$this->cn()->resetear();

			$this->set_pantalla('seleccion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}

	function evt__cancelar()
	{
		$this->dep('editor')->dep('editor_dominio')->resetear();
		parent::evt__cancelar();
	}
	
	function evt__eliminar()
	{
		$this->dep('editor')->dep('editor_dominio')->resetear();
		parent::evt__eliminar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se reemplaza el código del evento para que el control de cambios no afecte la 
	 * selección en cuadros.
	 */
	function js_evt_guardar() 
	{
		return '';
	}

}
?>