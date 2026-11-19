<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan="2">';
					$this->generar_html_ef('exitosos');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('errores');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_errores', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__rep_errores = function()
		{
			var params = [];
			params['evento'] = 'rep_errores';
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