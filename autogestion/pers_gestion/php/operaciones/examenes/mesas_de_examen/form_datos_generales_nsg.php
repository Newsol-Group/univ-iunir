<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class form_datos_generales_nsg extends form_datos_generales
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion_catedra = guarani_mensajes::get('800EXA_docentes_catedra');

		echo "
		
		//---- Procesamiento de EFs -----------------------------------
		
		/**
		 * En caso de que se cambie la cátedra elegida y existan asignaciones de docentes activas (sin fecha de 
		 * finalización), se deberá mostrar el mensaje 800CUR_docentes_catedra, ya que en caso de que el usuario confirme,
		 * se eliminarán todos los docentes de la cátedra asignados a la comisión y sus correspondientes dedicaciones 
		 * horarias.
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
			
		{$this->objeto_js}.evt__aplica_a_cursos__procesar = function(es_inicial) {			
			if (this.ef('aplica_a_cursos').get_estado() == 'S'){
				this.ef('exige_aprobacion').mostrar();
			}else{
				this.ef('exige_aprobacion').ocultar();
			}
		}

		{$this->objeto_js}.evt__cobrable__procesar = function(es_inicial) {
			if (this.ef('cobrable').get_estado() == 'S'){
				this.ef('concepto').mostrar(true);
			}else{
				this.ef('concepto').ocultar(true);
			}
		}

		toba.set_callback_menu(confirmar_cambios);
		
		";
	}

	function generar_layout()
	{
		$this->generar_html_ef('elemento');
		$this->generar_html_ef('turno_examen');
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '<tbody>';
		echo '<tr>';
		echo '<td colspan=3>';
		$this->generar_html_ef('nombre', '130px');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('ubicacion');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('catedra');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('cobrable');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('concepto');
		echo '</td>';
		echo '</tr>';				
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('acepta_planes_personalizados');
		echo '</td>';
		echo '<td colspan=2>';
		$this->generar_html_ef('division');
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td colspan=3>';
		$this->generar_html_ef('observaciones');
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}

}
?>