<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		$this->dep('editor')->generar_html();
	}

}
?>