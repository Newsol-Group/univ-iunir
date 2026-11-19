<?php
class form_convenio extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('nombre');
		echo '</td><td>';
		$this->generar_html_ef('convenio_tipo', '50px');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('realizado_con');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('descripcion');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('propuesta_base');
		echo '</td><td>';
		$this->generar_html_ef('estado', '70px');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('documento');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('anio_academico_vigencia');
		echo '</td><td>';
		$this->generar_html_ef('emite_certificado', '135px');
		echo '</td></tr></table>';
	}

}
?>