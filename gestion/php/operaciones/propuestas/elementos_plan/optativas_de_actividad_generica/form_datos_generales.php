<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class form_datos_generales extends guarani_pers_ei_formulario  
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
					echo '<td colspan=3>';
					$this->generar_html_ef('nombre_abreviado');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo "<td>";
					$this->generar_html_ef('anio_de_cursada');
					echo '</td>';
					echo "<td colspan = 2>";
					$this->generar_html_ef('periodo_de_cursada');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('creditos');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('creditos_min', '100px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('creditos_max', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('aprobacion_x_resolucion');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('permite_promocion', '100px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('permite_rendir_libre', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('sale_listado');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('requiere_cursada', '100px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('requiere_examen', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('promediable');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('horas_semanales', '100px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('horas_totales', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('vigencia');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('plazo', '100px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cantidad_de_aplazos', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('coeficiente');
					echo '</td>';
					echo '<td colspan = 2>';
					$this->generar_html_ef('cobrable');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<fieldset title="Escalas de Notas"><legend>Escalas de Notas:</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('escala_nota_cursada_regular');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('escala_nota_cursada_promocion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('escala_nota_examen');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('escala_nota_equivalencia');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
	}
	
	function extender_objeto_js()
	{
		$no = comunes::no;
		$msj_menor = guarani_mensajes::get('800_valor_menor', array('Creditos Max', 'Creditos Min'));
		$msj_mayor = guarani_mensajes::get('800_valor_mayor', array('Hs. Semanales', 'Hs. Totales'));
		$msj_obligatorio_min = guarani_mensajes::get('dato_faltante', array('Créditos Min'));
		$msj_obligatorio_max = guarani_mensajes::get('dato_faltante', array('Créditos Max'));
		$msj_valor_entre = guarani_mensajes::get('800_valor_entre', array('Créditos', 'Créditos Min', 'Créditos Max'));
		
		echo "
		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Creditos
			var creditos	= this.ef('creditos').get_estado();
			var min			= this.ef('creditos_min').get_estado();
			var max			= this.ef('creditos_max').get_estado();;
		
			if ( this.ef('creditos_min').tiene_estado() != this.ef('creditos_max').tiene_estado() ) {
				if ( this.ef('creditos_min').tiene_estado() ) {
					notificacion.agregar('$msj_obligatorio_max');
					return false
				} else {
					notificacion.agregar('$msj_obligatorio_min');
					return false
				}
			} else {
				if (this.ef('creditos_min').tiene_estado()) {
					if (min >= max) {
						notificacion.agregar('$msj_menor');
						return false
					} else {
						if (this.ef('creditos').tiene_estado()) {
							if (! validacion.esta_entre(creditos,min,max)) {
								notificacion.agregar('$msj_valor_entre');
								return false
							}
						}
					}
				}
			}
		
			if (this.ef('horas_totales').tiene_estado() == this.ef('horas_semanales').tiene_estado()) {
				var horas_totales = this.ef('horas_totales').get_estado();
				var horas_semanales = this.ef('horas_semanales').get_estado();
				if (horas_totales < horas_semanales) {
					notificacion.agregar('$msj_mayor');
					return false;
				}
			}
			
			return true
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__permite_promocion__procesar = function(es_inicial)
		{
			var valor = this.ef('permite_promocion').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_promocion').resetear_estado();
				this.ef('escala_nota_cursada_promocion').ocultar();
			} else {
				this.ef('escala_nota_cursada_promocion').mostrar();
			}
		}
		
		{$this->objeto_js}.evt__requiere_cursada__procesar = function(es_inicial)
		{
			var valor = this.ef('requiere_cursada').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_regular').resetear_estado();
				this.ef('escala_nota_cursada_regular').ocultar();
			} else {
				this.ef('escala_nota_cursada_regular').mostrar();
			}
		}
		
		{$this->objeto_js}.evt__requiere_examen__procesar = function(es_inicial)
		{
			var valor = this.ef('requiere_examen').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_examen').resetear_estado();
				this.ef('escala_nota_examen').ocultar();
			} else {
				this.ef('escala_nota_examen').mostrar();
			}
		}
		
		";
	}
}
?>