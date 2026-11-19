<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100%>
			<tr>
				<td>'; $this->generar_html_ef('campo_completar'); echo '</td>
				<td>'; $this->generar_html_ef('instancia', '80px'); echo '</td>
				<td>'; $this->generar_html_ef('nota'); echo '</td>
				<td>'; $this->generar_html_ef('observaciones'); echo '</td>
				<td>'; $this->generar_html_ef('fecha'); echo '</td>
				<td>'; $this->generar_html_ef('para', '30px'); echo '</td>
				<td>'; $this->generar_botones(); echo '</td>
				<td>
			</tr>
		</table>';

		$this->evento('completar')->set_en_botonera(false);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_error = guarani_mensajes::get('800_nota_invalida', array('autocompletar'));
		$mapeo_instancia_escala = $this->controlador()->generar_mapeo_js_instancia_escala();
		$campo_nota = mesa_de_examen::autocompletar_campo_nota;
		$campo_observaciones = mesa_de_examen::autocompletar_campo_obs;
		$campo_fecha = mesa_de_examen::autocompletar_campo_fecha;
		$campo_ausente = mesa_de_examen::autocompletar_result_ausente;

		$para_todos = mesa_de_examen::autocompletar_para_todos;
		$para_sin_datos = mesa_de_examen::autocompletar_para_sin_datos;

		echo "

		{$this->objeto_js}.instancia_escala = $mapeo_instancia_escala;
		
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__campo_completar__procesar = function(es_inicial)
		{
			var completar = this.ef('campo_completar').get_estado();
			
			if (completar == '$campo_nota') {
				this.ef('para').set_estado('$para_todos');
				this.ef('para').activar();
				this.ef('nota').activar();
				this.ef('nota').mostrar(true);
				this.ef('observaciones').ocultar(true);
                                this.ef('fecha').ocultar(true);
			} else if (completar == '$campo_observaciones') {
				this.ef('para').set_estado('$para_todos');
				this.ef('para').activar();
				this.ef('nota').activar();
				this.ef('nota').ocultar(true);
				this.ef('fecha').ocultar(true);
				this.ef('observaciones').mostrar(true);			
			} else if (completar == '$campo_fecha') {
				this.ef('para').set_estado('$para_todos');
				this.ef('para').activar();
				this.ef('nota').ocultar(true);
				this.ef('observaciones').ocultar(true);
				this.ef('fecha').mostrar(true);
			} else if (completar == '$campo_ausente') {
				this.ef('para').set_estado('$para_sin_datos');
				this.ef('para').desactivar();
				this.ef('nota').desactivar();
				this.ef('nota').resetear_estado();
				this.ef('observaciones').resetear_estado();
				this.ef('fecha').resetear_estado();
				this.ef('nota').ocultar(true);
				this.ef('observaciones').ocultar(true);
				this.ef('fecha').ocultar(true);
			}
		}
		
		{$this->objeto_js}.evt__nota__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('nota').tiene_estado()) {
					var nota 	= this.ef('nota').get_estado();
					var escala 	= this.instancia_escala[this.ef('instancia').get_estado()];
					var es_nota_valida = eval('es_nota_valida_para_escala_'+escala+'(nota)');

					if(!es_nota_valida){
						//La nota no pertenece a la escala, se retorna el msj de error.
						notificacion.limpiar();
						notificacion.agregar('$msj_error');
						notificacion.mostrar();
						this.ef('nota').resetear_estado();
					} else {
						this.ef('nota').set_estado(eval('formatear_nota_escala_'+escala+'(nota)'));
					}
                                }
			}
		}
		
		{$this->objeto_js}.completar_nota_y_resultado = function(fila)
		{
			var nota = this.ef('nota').get_estado();

			if (this.form_ml.ef('instancia').ir_a_fila(fila).get_estado() ==  this.ef('instancia').get_estado() ) {
				if (this.campo == '$campo_nota') {
					this.form_ml.ef('resultado').ir_a_fila(fila).set_estado(this.resultado);
				}
				this.form_ml.ef(this.campo).ir_a_fila(fila).set_estado(this.ef(this.campo).get_estado());
			}
		}
		
		{$this->objeto_js}.setear_resultado_ausente = function()
		{
			var form_ml = {$this->objeto_js}.controlador.dep('form_ml_notas');
			var filas = form_ml.filas(); 
			
			for (id_fila in filas) {
				//si la instancia de la fila del form_ml_notas es igual que la instancia seleccionada en el form_autocompletar
				if (form_ml.ef('instancia').ir_a_fila(id_fila).get_estado() == this.ef('instancia').get_estado() ) {
					//si la nota no tiene estado se debe poner el resultado en Ausente
					if(!(form_ml.ef('nota').ir_a_fila(id_fila).tiene_estado())) {
						form_ml.ef('resultado').ir_a_fila(id_fila).set_estado('" . escalas_notas::resultado_ausente . "');
					}
				}
			}
		}

		{$this->objeto_js}.evt__completar = function()
		{
			var     nota	= this.ef('nota').get_estado();
			var	para	= this.ef('para').get_estado();
			{$this->objeto_js}.form_ml	= this.controlador.dep('form_ml_notas');
			{$this->objeto_js}.campo	= this.ef('campo_completar').get_estado();

			if (this.campo == '$campo_nota') {

                                var escala 	= this.instancia_escala[this.ef('instancia').get_estado()];
                                var es_nota_valida = eval('es_nota_valida_para_escala_'+escala+'(nota)');
                                // La nota es válida
                                if (es_nota_valida) {
                                    {$this->objeto_js}.resultado = eval('get_resultado_para_escala_'+escala+'(nota)');
                                // La nota no pertenece a la escala, se retorna el msj de error.
                                } else {
                                    notificacion.limpiar();
                                    notificacion.agregar('$msj_error');
                                    notificacion.mostrar();
                                    this.ef('nota').resetear_estado();
                                    return false;
                                }
				
			} else {
                            {$this->objeto_js}.resultado = 'nopar';
                        }

			var filas = this.form_ml.filas();
			for (valor in filas) {
				if (para == '$para_sin_datos') {
					if (this.campo == '$campo_nota') {
						if (!this.form_ml.ef(this.campo).ir_a_fila(filas[valor]).tiene_estado() && !this.form_ml.ef('resultado').ir_a_fila(filas[valor]).tiene_estado()) {
							 this.completar_nota_y_resultado(filas[valor]);
						}
					} else
						if (this.campo == '$campo_ausente') {
							this.setear_resultado_ausente();
						} else {
							if (!this.form_ml.ef(this.campo).ir_a_fila(filas[valor]).tiene_estado()) {
								 this.completar_nota_y_resultado(filas[valor]);
							}
						}
				} else {
					this.completar_nota_y_resultado(filas[valor]);
			    }
			}
			return false;
		}
		";
	}

}