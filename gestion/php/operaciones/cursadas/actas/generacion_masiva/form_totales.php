<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table>';
		echo'<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('actas_generadas');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('rep_actas_generadas', false);
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		{$this->objeto_js}.evt__rep_actas_generadas = function()
		{
			var params = [];
			params['evento'] = 'rep_actas_generadas';            
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