<?php
class ci_nav_actividad extends ci_navegacion
{
	protected $s__estoy_duplicando;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__duplicar($seleccion)
	{
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->entidad()->duplicar();
		$this->cn()->entidad()->limpiar_campos_elemento();
		$this->s__estoy_duplicando = true;
	}

	function estoy_duplicando()
	{
		return $this->s__estoy_duplicando;
	}
	
	function evt__guardar()
	{
		parent::evt__guardar();
		$this->desetear_duplicando();
	}

	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->desetear_duplicando();
	}
	
	function desetear_duplicando()
	{
		if ($this->s__estoy_duplicando) {
			$this->s__estoy_duplicando = false;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->s__estoy_duplicando) {
			$pantalla->eliminar_evento('eliminar');
		}
		$this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
	}

}
?>