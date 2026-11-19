<?php
class pant_parametros extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_destino()->generar_html();
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr/>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '<tbody>';
			echo '<tr>';
				echo '<td>';
					echo '<b>Instancias</b>';
					echo '<br> <br>';
					$this->dep('ml_instancias')->generar_html();
				echo '</td>';
				echo '<td>';
				echo '</td>';
				echo '<td>';
					echo '<b>Modalidades</b>';
					echo '<br> <br>';
					$this->dep('ml_modalidades')->generar_html();
				echo '</td>';
			echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		$this->dep('form_parametros')->generar_html();
	}
}
?>