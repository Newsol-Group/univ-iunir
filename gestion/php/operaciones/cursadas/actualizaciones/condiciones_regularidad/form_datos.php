<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 * 
 */
class form_datos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100$>
					<tr>
						<td colspan=3>'; $this->generar_html_ef('nombre'); echo '</td>
					</tr>
					<tr>
						<td colspan=3>'; $this->generar_html_ef('descripcion'); echo '</td>
					</tr>
					<tr>
						<td>'; $this->generar_html_ef('resultado'); echo '</td>
						<td>'; $this->generar_html_ef('es_condicion_defecto', '165px'); echo '</td>
						<td>'; $this->generar_html_ef('activo'); echo '</td>
					</tr>
				</table>';
	}

}

?>