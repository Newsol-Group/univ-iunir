<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class form_banda_horaria extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width="100%">';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('dia_semana');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('tipo_clase');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('fecha_desde');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hora_inicio');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('fecha_hasta');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hora_finalizacion');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('cantidad_horas');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('periodicidad');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('modalidad');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('asistencia', '230px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan=2>';
				$this->generar_html_ef('observaciones');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_fecha_invalida = guarani_mensajes::get('800_control_fechas', array(' hasta', 'menor o igual', ' desde.'));
		echo "		
		//---- Validacion general ----------------------------------
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			if (this.ef('fecha_hasta').fecha() <= this.ef('fecha_desde').fecha()) {
				notificacion.agregar('$mensaje_fecha_invalida');				
				ok = false;
			}
			return ok;
		}
		";
	}
}
?>