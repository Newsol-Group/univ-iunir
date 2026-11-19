<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Mesas de Examen
 */
class form_datos_principales extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('nombre');
		echo '</td><td>';
		$this->generar_html_ef('actividad_descripcion');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('anio_academico');
		echo '</td><td>';
		$this->generar_html_ef('turno_examen_nombre');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('mesa_examen_nombre');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('fecha');
		echo '</td><td>';
		$this->generar_html_ef('ubicacion_nombre');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('hora_inicio');
		echo '</td><td>';
		$this->generar_html_ef('hora_finalizacion');
		echo '</td></tr></table>';
	}
}
?>