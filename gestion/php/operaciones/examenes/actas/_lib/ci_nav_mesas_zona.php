<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_mesas_zona extends ci_nav_mesas
{
	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$mesa_entidad = toba::zona()->get_editable();
			$this->setear_datos_mesa(array('entidad' => $mesa_entidad));
			$this->seleccionar_mesa('edicion');
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->seleccionar($seleccion);
		toba::zona()->cargar($this->get_mesa_seleccionada());
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Evento utilizado en caso de que haya dos pantallas de selección: de mesa y de acta. En este caso al cancelar, 
	 * deben resetearse sólo las variables locales y volver la pantalla de selección de mesas.
	 */
	function evt__cancelar_preseleccion()
	{
		$this->resetear_variables();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_mesa');
	}

	/**
	 * Evento utilizado en caso de que sólo haya una pantalla de selección de mesa. En este caso al cancelar, deben resetearse
	 * las variables locales, el cn y las variables de edición, y volver la pantalla de selección de mesas.
	 */
	function evt__cancelar()
	{
		$this->resetear_variables();
		$this->cn()->resetear();
		$this->dependencia('editor')->resetear_variables();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_mesa');
	}

}
?>