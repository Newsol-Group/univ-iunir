<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class form_datos_principales extends guarani_pers_ei_formulario 
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('ubicacion', '90px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('tipo_acta_descr');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nro_acta_rectificada', '90px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nro_acta', '70px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_inicio', '90px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_finalizacion', '70px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('catedra');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$msj_confirmacion_catedra = guarani_mensajes::get('800EXA_docentes_catedra');
		
		echo "
		
		//---- Procesamiento de EFs -----------------------------------------------------
		
		/**
		 * En caso de que se cambie la cátedra elegida pueden existir asignaciones de docentes de la misma, por lo que
		 * se pide confirmación al usuario, ya que en caso de continuar NO se eliminarán todos los docentes de la cátedra 
		 * asignados a la mesa del Acta.
		 */
		{$this->objeto_js}.evt__catedra__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				return confirmar_cambio_catedra();
			}
		}
			
		function confirmar_cambio_catedra(proyecto, operacion, url, es_popup) {
			if (! es_popup || typeof(es_popup) == 'undefined') {
				resp = confirm('$msj_confirmacion_catedra');
				if (resp) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
			
		toba.set_callback_menu(confirmar_cambios);
		
		";
	}

}
?>