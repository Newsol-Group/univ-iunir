<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Documentos
 * 
 */
class form_documentos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('nombre_archivo');
		echo '</td><td nowrap>';
		$this->generar_botones_eventos();
		if ($this->existe_evento('descargar')) {
			$this->evento('descargar')->set_en_botonera(false);
		}
		if ($this->existe_evento('eliminar')) {
			$this->evento('eliminar')->set_en_botonera(false);
		}
		echo '</div></td></tr></table>';
	}
}
?>