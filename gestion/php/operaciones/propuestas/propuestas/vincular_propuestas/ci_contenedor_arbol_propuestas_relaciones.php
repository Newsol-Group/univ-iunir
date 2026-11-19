<?php
/**
 *
 *
 * @package Propuestas Formativas
 */
class ci_contenedor_arbol_propuestas_relaciones extends ci_contenedor_arbol 
{
	function conf__arbol(guarani_ei_arbol $arbol)
	{
		parent::conf__arbol($arbol);
		$arbol->set_ancho_nombres(120);
	}
}
?>
