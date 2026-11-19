<?php
class ci_seleccion_modulo extends guarani_pers_ci
{
	//-------------------------------------------------------------------------------
	//---- SELECCION ----------------------------------------------------------------
	//-------------------------------------------------------------------------------
	
	//---- cuadro -------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();

		$filtro = $this->get_filtro('filtro');
		$datos = toba::consulta_php('co_modulos')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

}
?>