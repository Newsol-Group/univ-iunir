<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class pant_seleccion_clase extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		$datos_subco = $this->controlador()->get_datos_subcomision();
		if (isset($datos_subco)) {
			echo '<br>';
			$this->controlador()->ev_info_subcomision()->generar_html();
		}
		echo '<hr />';
		parent::generar_layout();
	}

}
?>