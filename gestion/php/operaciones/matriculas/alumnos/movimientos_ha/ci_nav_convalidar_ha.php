<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class ci_nav_convalidar_ha extends ci_navegacion
{
	function get_alumno_seleccionado()
	{
		return $this->s__seleccion['alumno'];
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__seleccion);
		$this->cn()->unset_vars();
		$this->set_pantalla('seleccion');
	}

	function evt__convalidar()
	{
		try {
			$this->cn()->convalidar($this->get_alumno_seleccionado());
			$this->evt__cancelar();
			toba::notificacion()->agregar(guarani_mensajes::get('800MAT_conv_inv_ok', array('convalidados')), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function js_evt_cancelar()
	{
		return "
			{$this->objeto_js}.evt__cancelar = function() {}
		";
	}
}
?>