<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Actualizaciones
 *
 */
class pant_hist_acad_nsg extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('opciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro')->generar_html();
	}
}

?>