<?php
/**
 *
 * 
 * @package     Calendario
 * @subpackage 	Años Academicos
 * 
 */
class ci_cont_arbol extends ci_contenedor_arbol
{
    //-----------------------------------------------------------------------------------
	//---- arbol ------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__arbol(guarani_ei_arbol $arbol)
	{
		parent::conf__arbol($arbol);
		$arbol->set_ancho_nombres(255);
		$arbol->set_todos_abiertos();
	}
}
?>