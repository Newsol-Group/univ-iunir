<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_activ_con_mesas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_activ_con_mesas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
                        $this->generar_html_ef('cant_mesas_copiadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_mesas_copiadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_mesas_con_observ');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_mesas_con_observ', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_mesas_no_copiadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_mesas_no_copiadas', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_cant_activ_con_mesas = function()
		{
			var params = [];
			params['evento'] = 'rep_actividades_con_mesas_copiadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_actividades);
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_mesas_copiadas = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_copiadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas_copiadas);
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_mesas_no_copiadas = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_no_copiadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas_no_copiadas);
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_mesas_con_observ = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_con_obs';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas_copiadas);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte_actividades = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'600','height':'500'});
		}
		
		{$this->objeto_js}.respuesta_reporte_mesas_copiadas = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'950','height':'700'});
		}

		{$this->objeto_js}.respuesta_reporte_mesas_no_copiadas = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'800','height':'600'});
		}
		
		";
		
	}
}

?>