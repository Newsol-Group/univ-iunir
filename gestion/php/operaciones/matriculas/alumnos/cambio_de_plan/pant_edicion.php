<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('formulario')->generar_html();
		echo "<h3>Opciones Disponibles</h3>";
		$this->dep('cuadro_planes')->generar_html();
	}

}
?>