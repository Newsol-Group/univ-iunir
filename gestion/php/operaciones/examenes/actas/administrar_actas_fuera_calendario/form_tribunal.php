<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class form_tribunal extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo'<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('tribunal');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('aplicar');
		echo '</td>';
		echo '</tr>';
		$this->evento('aplicar')->set_en_botonera(false);
	}

}
?>