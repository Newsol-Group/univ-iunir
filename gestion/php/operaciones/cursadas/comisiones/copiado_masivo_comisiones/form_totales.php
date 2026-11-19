<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('comisiones');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_comisiones', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('comisiones_con_obs');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_comisiones_con_obs', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('comisiones_no_copiadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_comisiones_no_copiadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('actividades_con_comisiones');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_actividades_con_comisiones', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js ()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}

		{$this->objeto_js}.evt__rep_comisiones = function()
		{
			var params = [];
			params['evento'] = 'rep_comisiones';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_comisiones_con_obs = function()
		{
			var params = [];
			params['evento'] = 'rep_comisiones_con_obs';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_comisiones_no_copiadas = function()
		{
			var params = [];
			params['evento'] = 'rep_comisiones_no_copiadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}
		
		{$this->objeto_js}.evt__rep_actividades_con_comisiones = function()
		{
			var params = [];
			params['evento'] = 'rep_actividades_con_comisiones';
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