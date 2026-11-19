<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class pant_alumnos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<fieldset><legend>Alumnos Seleccionados</legend>';
		$this->dep('alumnos_asociados')->generar_html();
		echo '</fieldset>';
		$this->dep('form_instancia')->generar_html();
		echo '<fieldset><legend>Alumnos Disponibles</legend>';
		$this->dep('alumnos_disponibles')->generar_html();
		echo '</fieldset>';
	}

}
?>