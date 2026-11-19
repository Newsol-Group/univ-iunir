<?php

class ci_cont_arbol_resasignar extends ci_contenedor_arbol
{
	function conf__arbol(guarani_ei_arbol $arbol)
	{
		parent::conf__arbol($arbol);
		$arbol->set_ancho_nombres(255);
		$arbol->set_todos_abiertos();
	}
	
	function hay_nodo_seleccionado()
	{
		return (!empty($this->s__nodos_seleccionados));			
	}
}
?>