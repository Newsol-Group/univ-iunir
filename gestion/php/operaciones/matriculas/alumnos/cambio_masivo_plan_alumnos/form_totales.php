<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_alumnos');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_modificados');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_modificados', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_no_modificados');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_cant_no_modificados', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_equiv_otorgadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_equiv_por_alumno', false);
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_equiv_por_actividad', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js ()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_modificados = function()
		{
			var params = [];
			params['evento'] = 'rep_cant_modificados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_cant_no_modificados = function()
		{
			var params = [];
			params['evento'] = 'rep_cant_no_modificados';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_equiv_por_alumno = function()
		{
			var params = [];
			params['evento'] = 'rep_equiv_por_alumno';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}
		
		{$this->objeto_js}.evt__rep_equiv_por_actividad = function()
		{
			var params = [];
			params['evento'] = 'rep_equiv_por_actividad';
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