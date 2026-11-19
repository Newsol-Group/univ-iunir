<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 */
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('modificadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_modificadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('a_rechazar');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rechazar', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js() 
	{
		echo "
		{$this->objeto_js}.evt__rep_modificadas = function()
		{
			var params = [];
			params['evento'] = 'rep_modificadas';
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