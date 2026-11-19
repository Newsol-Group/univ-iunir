<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class pant_datos_personales extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "	<table width='100%'>
					<tr>
						<td style='vertical-align:text-top;'>";
							echo '<p style="font-weight:bold; text-decoration: underline">Datos Personales</p>';
							$this->controlador()->ev_info_datos_personales()->generar_html();						
		echo '			</td>
						<td>';
							$this->dep('form_foto')->generar_html();			
		echo '			</td>
					</tr>';
		if ($this->controlador()->ev_info_procedencia()->get_datos()) {
			echo '	<tr>
						<td colspan="2">
							<p style="font-weight:bold; text-decoration: underline">Procedencia</p>';
							$this->controlador()->ev_info_procedencia()->generar_html();
			echo '		</td>
					</tr>';
		}
		if ($this->controlador()->ev_info_datos_censales()->get_datos()) {
			echo '	<tr>
						<td colspan="2">
							<p style="font-weight:bold; text-decoration: underline">Última residencia declarada</p>';
							$this->controlador()->ev_info_datos_censales()->generar_html();
			echo '		</td>
					</tr>';
		}
		if ($this->controlador()->ev_info_datos_redes_sociales()->get_datos()) {
			echo '	<tr>
						<td colspan="2">
							<p style="font-weight:bold; text-decoration: underline">Redes sociales</p>';
							$this->controlador()->ev_info_datos_redes_sociales()->generar_html();
			echo '		</td>
					</tr>
			  	</table>';
		}
	}

}
?>