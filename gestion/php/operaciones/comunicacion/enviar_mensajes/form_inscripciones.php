<?php
class form_inscripciones extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "	<table>
					<tr>
						<td><span class='ei-form-etiq'><strong>Inscripciones: </strong></span></td>
						<td>";
		echo $this->generar_html_ef('insc_aceptadas');
		echo "			</td>
						<td>";
		echo $this->generar_html_ef('insc_pendientes');
		echo "			</td>
						<td>";
		echo $this->generar_html_ef('insc_rechazadas');
		echo "			</td>
					</tr>
				</table>";
	}

}
?>