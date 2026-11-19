<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class form_datos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>
			    <tbody>
			        <tr>";
		
		if (!$this->controlador()->cn()->entidad()->es_nueva()) {
			echo "		<td width='33%'>"; $this->generar_html_ef('licencia'); echo "</td>
			            <td width='33%'>"; $this->generar_html_ef('puede_rendir_examen'); echo "</td>
			            <td width='33%'>"; $this->generar_html_ef('es_licencia_financiera'); echo "</td>
			        </tr>";
		} else {
			echo "      <td width='33%'>"; $this->generar_html_ef('puede_rendir_examen'); echo "</td>
			            <td colspan='2'>"; echo $this->generar_html_ef('es_licencia_financiera'); echo "</td>
			        </tr>";
		}
		
		echo "		<tr>
						<td colspan='3'>"; echo $this->generar_html_ef('propuestas'); echo "</td>
					</tr>
			       	<tr>
			            <td width='33%'>"; $this->generar_html_ef('alcance'); echo "</td>
			            <td colspan='2'>"; echo $this->generar_html_ef('periodo_lectivo_tipo'); echo "</td>
			        </tr>
			        <tr>
			            <td width='33%'>"; $this->generar_html_ef('fecha_desde'); echo "</td>
			            <td colspan='2'>"; echo $this->generar_html_ef('fecha_hasta', '50px'); echo "</td>
			        </tr>
			        <tr>
			            <td width='33%'>"; $this->generar_html_ef('anio_academico'); echo "</td>
			            <td colspan='2'>"; $this->generar_html_ef('cant_periodos_lectivos'); echo "</td>
			        </tr>
			        <tr>
			        	<td colspan='3'>"; $this->generar_html_ef('periodo_lectivo_desde'); echo "</td>
			        </tr>
			        <tr>
			            <td colspan='3'>"; $this->generar_html_ef('motivo_licencia'); echo "</td>
			        </tr>
			        <tr>
			            <td colspan='3'>"; $this->generar_html_ef('estado'); echo "</td>
			        </tr>
			        <tr>
			            <td colspan='3'>"; $this->generar_html_ef('observaciones'); echo "</td>
			        </tr>
			        <tr>
			            <td colspan='3'>"; $this->generar_html_ef('documento'); echo "</td>
			        </tr>
			    </tbody>
			</table>";
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$periodos_lectivos = licencia::alcance_periodos_lectivos;
		$rango_fechas = licencia::alcance_rango_fechas;

		$nopar = comunes::ef_combo_seleccione;
		$si = comunes::si;
		$no = comunes::no;
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__alcance__procesar = function(es_inicial)
		{
			var alcance = this.ef('alcance').get_estado();
			if (alcance == $periodos_lectivos) {
				this.ef('anio_academico').mostrar(true, false);
				this.ef('periodo_lectivo_tipo').mostrar(true, false);
				this.ef('cant_periodos_lectivos').mostrar(true, false);
				this.ef('periodo_lectivo_desde').mostrar(true, false);
				
				this.ef('fecha_desde').ocultar(true);
				this.ef('fecha_hasta').ocultar(true);
			} else if (alcance == $rango_fechas) {
				this.ef('fecha_desde').mostrar(true, false);
				this.ef('fecha_hasta').mostrar(true, false);	
				
				this.ef('anio_academico').ocultar(true);
				this.ef('periodo_lectivo_tipo').ocultar(true);
				this.ef('cant_periodos_lectivos').ocultar(true);
				this.ef('periodo_lectivo_desde').ocultar(true);
			} else {
				// Si no hay selección -> oculto todos
				this.ef('fecha_desde').ocultar(true);
				this.ef('fecha_hasta').ocultar(true);
				this.ef('anio_academico').ocultar(true);
				this.ef('periodo_lectivo_tipo').ocultar(true);
				this.ef('cant_periodos_lectivos').ocultar(true);
				this.ef('periodo_lectivo_desde').ocultar(true);
			}
		}
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__es_licencia_financiera__procesar = function(es_inicial)
		{
                    var es_licencia_financiera = this.ef('es_licencia_financiera').get_estado();
                    var alcance = this.ef('alcance').get_estado();
                    var opciones = [];

                    switch (es_licencia_financiera) {
                      // Si 'Licencia financiera' es '-- Seleccione --'
                      case '$nopar':

                        // En 'Alcance Licencia' habilito solo '-- Seleccione --'
                        $('#ef_form_41000831_formularioalcance option').prop('disabled', true);
                        $('#ef_form_41000831_formularioalcance option[value=$nopar]').prop('disabled', false);

                        opciones = ['$nopar'];
                        break;
                      // Si 'Licencia financiera' es 'Sí'
                      case '$si':

                        // En 'Alcance Licencia' habilito '-- Seleccione --' y 'Rango de Fechas'
                        $('#ef_form_41000831_formularioalcance option').prop('disabled', true);
                        $('#ef_form_41000831_formularioalcance option[value=$nopar]').prop('disabled', false);
                        $('#ef_form_41000831_formularioalcance option[value=$rango_fechas]').prop('disabled', false);

                        opciones = ['$nopar', '$rango_fechas'];
                        break;
                      // Si 'Licencia financiera' es 'No'
                      case '$no':

                        // En 'Alcance Licencia' habilito todas las opciones
                        $('#ef_form_41000831_formularioalcance option').prop('disabled', false);

                        opciones = ['$nopar', '$rango_fechas', '$periodos_lectivos'];
                        break;
                    }

                    // Si el 'Alcance Licencia' seleccionado no coincide con las opciones le pongo '-- Seleccione --'
                    if ($.inArray(alcance, opciones) === -1) {
                        this.ef('alcance').set_estado('$nopar');
                    }
                    
		}
		";
	}

}