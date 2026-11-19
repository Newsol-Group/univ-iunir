<?php
class cuadro_inscripciones_cursada_salida_html extends toba_ei_cuadro_salida_html
{
	protected function html_barra_total_registros()
	{
		$total_registros = $this->_cuadro->get_total_registros();
		echo "<tr><td>";
		echo "<div class='ei-cuadro-pag ei-cuadro-pag-total'>Cantidad de Inscripciones: $total_registros</div>";
		echo "</td></tr>\n";
	}
}
?>