<?php
class form_totales extends guarani_pers_ei_formulario
{
	function extender_objeto_js()
	{
		echo "
		
		{$this->objeto_js}.evt__rep_docentes = function()
		{
			var params = [];
			params['evento'] = 'rep_docentes';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_alumnos = function()
		{
			var params = [];
			params['evento'] = 'rep_alumnos';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_cursos = function()
		{
			var params = [];
			params['evento'] = 'rep_cursos';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta);
		}
		
	
	";
	}

	function generar_layout()
	{
		echo '<table width="100%" cellspacing="1" cellpadding="1" border="0" align="left">';
		echo'<tbody>';
		echo '<tr width="100%" >';
		echo '<td>';
		$this->generar_html_ef('cursos_creados_vinculados');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('cursos_creados_vinculados_error', '40px');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('rep_cursos', false);
		echo '</td>';
		echo '</tr>';
		echo '<tr width="100%" >';
		echo '<td>';
		$this->generar_html_ef('alumnos_sincronizados');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('alumnos_sincronizados_error', '40px');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('rep_alumnos', false);
		echo '</td>';
		echo '</tr>';
		echo '<tr width="100%" >';
		echo '<td>';
		$this->generar_html_ef('docentes_sincronizados');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('docentes_sincronizados_error', '40px');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('rep_docentes', false);
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}

}
?>