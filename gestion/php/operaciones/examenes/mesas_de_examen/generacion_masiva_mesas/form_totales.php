<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('actividades');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_actividades', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('mesas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_mesas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('mesas_con_obs');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_mesas_con_obs', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('actividades_sin_mesas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_actividades_sin_mesas', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_actividades = function()
		{
			var params = [];
			params['evento'] = 'rep_actividades';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_actividades);
			return false;
		}

		{$this->objeto_js}.evt__rep_mesas = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas);
			return false;
		}

		{$this->objeto_js}.evt__rep_mesas_con_obs = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_con_obs';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas);
			return false;
		}

		{$this->objeto_js}.evt__rep_actividades_sin_mesas = function()
		{
			var params = [];
			params['evento'] = 'rep_actividades_sin_mesas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_actividades);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte_actividades = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'600','height':'500'});
		}
		
		{$this->objeto_js}.respuesta_reporte_mesas = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'950','height':'700'});
		}
		
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		";
		
	}
}

?>