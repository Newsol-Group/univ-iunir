<?php
class form_resultados extends guarani_pers_ei_formulario
{

	public function generar_layout()
	{
		echo '<table width="100%" cellspacing="1" cellpadding="1" border="0" align="left">';
		echo'<tbody>';
		echo '<tr width="100%" >';
			echo '<td>';
				$this->generar_html_ef('reporte_cerrados');
			echo '</td>';		
			echo '<td>';
				$this->generar_boton('reporte_cerrados', false);
			echo '</td>';
		echo '</tr>';
		echo '<tr width="100%" >';
			echo '<td>';
				$this->generar_html_ef('reporte_no_pasaron');
			echo '</td>';
			echo '<td>';
			$this->generar_boton('reporte_no_pasaron');			
			echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}	
	
	public function extender_objeto_js()
	{
		echo "
		
		{$this->objeto_js}.evt__reporte_cerrados = function()
		{
			var params = [];
			params['evento'] = 'reporte_cerrados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__reporte_no_pasaron = function()
		{
			var params = [];
			params['evento'] = 'reporte_no_pasaron';
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