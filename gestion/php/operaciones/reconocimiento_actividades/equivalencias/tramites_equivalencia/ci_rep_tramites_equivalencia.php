<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_rep_tramites_equivalencia extends ci_rep_sin_listado_inicial
{
	protected $s__tramite_seleccionado;

	function ini()
	{
		//Se desactiva el reciclado de memoria
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}
	
	function get_tabla_principal()
	{
		return 'sga_equiv_tramite';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_equivalencias')->get_listado_tramites($filtro);
	}

	function get_tramite_seleccionado()
	{
		return $this->s__tramite_seleccionado;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__tramite_seleccionado = $seleccion['equivalencia_tramite'];
		$this->set_pantalla('pant_detalle');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__tramite_seleccionado);
		$this->set_pantalla('pant_inicial');
	}
}
?>