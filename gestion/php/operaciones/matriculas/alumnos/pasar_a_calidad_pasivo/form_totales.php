<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width="100%">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_alumnos');
					echo '</td>';
					echo '<td width="50%">';
					$this->generar_boton('rep_alumnos', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	function extender_objeto_js()
	{
		echo "

		{$this->objeto_js}.evt__rep_alumnos = function()
		{
			this.controlador.ajax('reporte', [], this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta, {'width':'870','height':'550'});
		}
		";
	}
}
?>