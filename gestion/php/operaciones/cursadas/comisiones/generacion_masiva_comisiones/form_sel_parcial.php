<?php
class form_sel_parcial extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset title="Filtros para las actividades"><legend>Filtros para las comisiones</legend>'; 

		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico');
					echo '</td>';
					echo '<td>'; 
					$this->generar_html_ef('periodo_lectivo_destino');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>'; 

		$this->generar_html_ef('ubicacion_nombre');
		$this->generar_html_ef('resp_academicas');
		$this->generar_html_ef('propuestas');
		$this->generar_html_ef('anio_cursado_planes');
		$this->generar_html_ef('per_lec_actividades');
		$this->generar_html_ef('inscripcion');
		$this->generar_html_ef('instancias');
		$this->generar_html_ef('modalidades');
		$this->generar_html_ef('criterio_gen');
		echo '</fieldset>';
	}

}
?>