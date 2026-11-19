<?php

class ci_nav_planes_correlativas extends ci_navegacion_planes
{

	//------ Pantallas  ----------------------
	function conf__edicion(toba_ei_pantalla $pantalla){}
	
	function evt__cancelar()
	{
		// Cuando se sale al cuadro de seleccion de planes se resetea el filtro 
		// de el Tab Cuadro.
		unset($this->dep('editor')->dep('arbol_cuadro')->s__datos);
		$this->dep('editor')->dep('arbol_cuadro')->s__filtro = "";
		parent::evt__cancelar();
	}
	
	
	
	
}

?>