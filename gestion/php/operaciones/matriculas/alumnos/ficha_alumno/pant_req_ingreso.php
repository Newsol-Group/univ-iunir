<?php
class pant_req_ingreso extends toba_ei_pantalla
{
	function generar_layout()
	{
		echo '<h2>Requisitos de ingreso DOCUMENTALES presentados y vencidos</h2>';
		$this->dep('cuadro_presentados')->generar_html();
		echo '<hr />';
		echo '<h2>Requisitos de ingreso DOCUMENTALES adeudados y exceptuados (por Propuesta)</h2>';
		$this->dep('cuadro_pend_excep')->generar_html();
		echo '<hr />';
		echo '<h2>Otros requisitos de ingreso (por Propuesta)</h2>';
		$this->dep('cuadro_procesos')->generar_html();
	}

}
?>