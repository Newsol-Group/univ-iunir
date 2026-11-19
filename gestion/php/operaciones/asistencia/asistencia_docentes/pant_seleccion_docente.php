<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class pant_seleccion_docente extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		if (isset($this->controlador->ev_info_subcomision)) {
			echo '<br>';
			$this->controlador()->ev_info_subcomision()->generar_html();
		}
		echo '<br>';
		$this->controlador()->ev_info_clase()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}

}
?>