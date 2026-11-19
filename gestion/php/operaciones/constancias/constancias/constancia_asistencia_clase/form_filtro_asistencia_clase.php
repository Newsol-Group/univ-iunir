<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class form_filtro_asistencia_clase extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('elemento_codigo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('comision');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_desde');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_hasta', '75px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js()
	{
		$mensaje_fecha_invalida = guarani_mensajes::get('800_control_fechas', array(' Hasta', 'menor', ' Desde'));
		
		echo "		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			
			if (this.ef('fecha_hasta').fecha() < this.ef('fecha_desde').fecha()) {
			
				notificacion.agregar('$mensaje_fecha_invalida');				
				ok = false;
			}
			return ok;
		}
		";
	}
}
?>