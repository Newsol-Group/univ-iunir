<?php
class pant_reasignar extends toba_ei_pantalla
{
	function generar_layout()
	{		
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		
		if (!empty($this->controlador()->cn()->get_actividades_genericas())) {
			echo "<table style='BORDER-COLLAPSE: collapse;'>";
			echo "<tr>";

			echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: collapse; text-align: left; vertical-align: top'>";
			echo "<div style='text-align: left; vertical-align: top; width:400px;'>";
				$this->dep('ci_cont_arbol_reasignacion')->generar_html();

			echo "</div>";
			echo '</td>';

			if ($this->existe_dependencia('ci_reasignar')) {
				echo "<td style='text-align: left;vertical-align: top; '>";
				echo "<div style='width:600px;'>";
				$this->dep('ci_reasignar')->generar_html();
				echo "</div>";
				echo '</td>';
			}		
			echo "</tr>";
			echo "</table>";		
		} else {			
			$this->eliminar_dep('ci_cont_arbol_reasignacion');
			if ($this->existe_dependencia('ci_reasignar')) {
				$this->eliminar_dep('ci_reasignar');
			}
			
			echo "<div style='text-align: center; vertical-align: top; width:900px; font-size:14px; '>";
			echo "No hay Actividades Optativas compartidas entre las Actividades Genéricas del Plan de Estudios";
			echo "</div><br>";
			
		}
		
	}

}
?>