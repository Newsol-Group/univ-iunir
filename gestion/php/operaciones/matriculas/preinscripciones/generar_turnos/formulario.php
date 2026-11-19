<?php
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
		echo "			<fieldset>";
		echo "				<legend>Configuración</legend>";
								$this->generar_html_ef('fecha_inicio');
								$this->generar_html_ef('fecha_fin');
								$this->generar_html_ef('minutos_intervalo');
								$this->generar_html_ef('cant_operadores');
                                $this->generar_html_ef('descripcion');
								$this->generar_html_ef('atiende_feriados');
		echo "			</fieldset>";
		echo "		</td>";
		echo "		<td style='padding-left: 6px;'>";
		echo "			<fieldset>";
		echo "				<legend>Días de atención</legend>";
								$this->generar_html_ef('atiende_lunes', '80px');
								$this->generar_html_ef('atiende_martes', '80px');
								$this->generar_html_ef('atiende_miercoles', '80px');
								$this->generar_html_ef('atiende_jueves', '80px');
								$this->generar_html_ef('atiende_viernes', '80px');
								$this->generar_html_ef('atiende_sabado', '80px');
								$this->generar_html_ef('atiende_domingo', '80px');
		echo "			</fieldset>";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";
	}

}
?>