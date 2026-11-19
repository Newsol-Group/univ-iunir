<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class pant_datos_generales extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<table border='0' style='BORDER-COLLAPSE: collapse;' width='100%'><tr>";
		echo '	<tr>';
		echo "		<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
		echo "			<div style='text-align: left; vertical-align: top; overflow: hidden;'>";
		$this->dep('datos_generales')->generar_html();
		echo "			</div>";
		echo "		</td>";
		echo "		<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
		echo "			<div style='text-align: left; vertical-align: top; width:150px; overflow: hidden;'>";
		echo '				<fieldset><legend>Modalidades:</legend>';
		$this->dep('seleccion_modalidades')->generar_html();
		echo '				</fieldset>';
		echo "			</div>";
		echo "		</td>";
		echo '	</tr>';
		echo '</table>';
		echo '<hr />';
		
		echo '<fieldset><legend>Llamados habilitados para la Mesa de Examen:</legend>';
		$this->dep('seleccion_llamados')->generar_html();
		echo '</fieldset>';
		echo '<hr />';
		echo '<fieldset><legend>Propuestas habilitadas para la Mesa de Examen</legend>';
		$this->dep('seleccion_propuestas')->generar_html();
		echo '</fieldset>';
	}

}
?>