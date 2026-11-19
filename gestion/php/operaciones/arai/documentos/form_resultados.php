<?php
/**
 *
 * 
 * @package 	Araí
 * @subpackage 	Documentos
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
					$this->generar_html_ef('sincronizados');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_sincronizados', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fallidos');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_fallidos', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js() 
	{
		echo "
		{$this->objeto_js}.evt__rep_sincronizados = function()
		{
			var params = [];
			params['evento'] = 'rep_sincronizados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_fallidos = function()
		{
			var params = [];
			params['evento'] = 'rep_fallidos';
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