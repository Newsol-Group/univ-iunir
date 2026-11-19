<?php
/**
 *
 *
 * @package     Reconocimiento de Actividades
 * @subpackage  Equivalencias
 */
class form_totales_equiv extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td colspan="2">'; 
					$this->generar_html_ef('procesados');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_equiv_otorgada');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_equiv_otorgada', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_alumnos_equiv_otorgada');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_alumnos_equiv_otorgada', false);
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
	
	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_cant_equiv_otorgada = function()
		{
			var params = [];
			params['evento'] = 'rep_cant_equiv_otorgada';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_alumnos_equiv_otorgada = function()
		{
			var params = [];
			params['evento'] = 'rep_cant_alumnos_equiv_otorgada';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_errores = function()
		{
			var params = [];
			params['evento'] = 'rep_errores';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta, {'width':'1000','height':'700'});
		}
		
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		";
		
	}
}
?>