<?php
class pant_req_egreso extends toba_ei_pantalla
{
	function generar_layout()
	{
		echo '<h2>Requisitos de egreso presentados y vencidos</h2>';
		$this->dep('cuadro_presentados')->generar_html();
		echo '<hr />';
		echo '<h2>Requisitos de egreso adeudados y exceptuados (por certificado)</h2>';
		$this->dep('cuadro_pend_excep')->generar_html();
	}
}