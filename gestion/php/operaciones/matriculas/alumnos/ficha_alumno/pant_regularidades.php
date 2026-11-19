<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Actualizaciones
 *
 */
class pant_regularidades extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('opciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro')->generar_html();
		if ($this->controlador()->hay_datos()) {
			echo '* Actividades aprobadas por Examen Final, Promoción, Equivalencia, Resolución...';
		}
	}
}

?>