<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('modificados');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_modificados', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('no_modificados');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_no_modificados', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__rep_modificados = function()
		{
			var params = [];
			params['evento'] = 'rep_modificados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_no_modificados = function()
		{
			var params = [];
			params['evento'] = 'rep_no_modificados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta);
		}
		
		{$this->objeto_js}.hay_cambios = function()
		{
			return false;
		}
		";
	}
}
?>