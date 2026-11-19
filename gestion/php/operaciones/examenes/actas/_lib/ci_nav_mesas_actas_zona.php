<?php
/**
 *
 * @package		Exámenes
 * @subpackage	Actas
 * 
 */
class ci_nav_mesas_actas_zona extends ci_nav_mesas_actas
{
	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$mesa_entidad = toba::zona()->get_editable();
			$this->setear_datos_mesa(array('entidad' => $mesa_entidad));
			$this->set_pantalla('seleccion_acta');
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro(mesas) -------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		toba::zona()->cargar($seleccion['entidad']);
		$this->setear_datos_mesa($seleccion);
		$this->set_pantalla('seleccion_acta');
	}
	
	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__cancelar()
	{
		if (toba::zona()->cargada()) {
			toba::zona()->resetear();
		}
		$this->dependencia('editor')->resetear_variables();
		$this->resetear_variables();
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_acta');
	}
	
	function evt__cancelar_preseleccion()
	{
		$this->resetear_variables_llamado_mesa();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_mesa');
	}
}
?>