<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_contenedor_arbol_comisiones extends ci_contenedor_arbol
{
	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__pant_inicial(guarani_ei_pantalla $pantalla)
	{
		$pantalla->set_no_mostrar_extras_cabecera();
	}

}
?>