<?php
/**
 *
 *
 * @package 	Operaciones Generales
 * @subpackage 	Videos
 *
 */
class pant_video extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		echo $this->controlador()->mostrar_video(640, 424);
	}
	
}

?>