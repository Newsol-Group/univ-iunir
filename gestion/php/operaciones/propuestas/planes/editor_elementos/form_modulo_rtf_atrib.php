<?php
class form_modulo_rtf_atrib extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$encabezado = array(
			'codigo',
			'nombre',
			'nombre_abreviado'
		);
		$cuerpo1 = array(
			'origen_creditos',
			'creditos'
		);

		echo $this->get_html_alineado($encabezado, 1, '170px');
		echo "<hr/>";
		echo $this->get_html_alineado($cuerpo1, 1, '170px');
	}
}
?>