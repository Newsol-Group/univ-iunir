<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('alumnos');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('inscripciones');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('insc_aceptadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_insc_aceptadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('insc_pendientes');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_insc_pendientes', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('insc_rechazadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_insc_rechazadas', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	function extender_objeto_js ()
	{
		echo "
		{$this->objeto_js}.evt__rep_insc_aceptadas = function()
		{
			var params = [];
			params['evento'] = 'rep_insc_aceptadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_insc_pendientes = function()
		{
			var params = [];
			params['evento'] = 'rep_insc_pendientes';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_insc_rechazadas = function()
		{
			var params = [];
			params['evento'] = 'rep_insc_rechazadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta);
		}
		";
	}
}
?>