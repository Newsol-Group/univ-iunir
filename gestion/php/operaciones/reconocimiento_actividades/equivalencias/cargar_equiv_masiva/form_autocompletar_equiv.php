<?php
/**
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class form_autocompletar_equiv extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100$>
					<tr>
						<td>'; $this->generar_html_ef('campo_completar'); echo '</td>
						<td>'; $this->generar_html_ef('valor_nota'); echo '</td>
						<td>'; $this->generar_html_ef('valor_acta'); echo '</td>
						<td>'; $this->generar_html_ef('valor_folio'); echo '</td>
						<td>'; $this->generar_html_ef('valor_resultado'); echo '</td>
						<td>'; $this->generar_html_ef('valor_fecha'); echo '</td>
						<td>'; $this->generar_html_ef('valor_libro'); echo '</td>
						<td>'; $this->generar_html_ef('para'); echo '</td>
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
		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('para autocompletar', 'mayor', 'actual'));
		$msj_nota = guarani_mensajes::get('800_nota_invalida', array('autocompletar.'));

		$autocompletar_para_todos = equivalencia::autocompletar_para_todos;
		$autocompletar_para_sin_datos = equivalencia::autocompletar_para_sin_datos;

		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__campo_completar__procesar = function(es_inicial)
		{
			var completar = this.ef('campo_completar').get_estado();

			if (completar == '". equivalencia::autocompletar_campo_nota ."') {
				this.ef('valor_nota').mostrar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_acta').ocultar(true);
				this.ef('valor_folio').ocultar(true);
				this.ef('valor_libro').ocultar(true);
			} else if (completar == '" . equivalencia::autocompletar_campo_resultado . "') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').mostrar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_acta').ocultar(true);
				this.ef('valor_folio').ocultar(true);
				this.ef('valor_libro').ocultar(true);
			} else if (completar == '" . equivalencia::autocompletar_campo_fecha . "') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_fecha').mostrar(true);
				this.ef('valor_acta').ocultar(true);
				this.ef('valor_folio').ocultar(true);
				this.ef('valor_libro').ocultar(true);
			} else if (completar == '" . equivalencia::autocompletar_campo_acta . "') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_acta').mostrar(true);
				this.ef('valor_folio').ocultar(true);
				this.ef('valor_libro').ocultar(true);
			} else if (completar == '" . equivalencia::autocompletar_campo_libro . "') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_acta').ocultar(true);
				this.ef('valor_folio').ocultar(true);
				this.ef('valor_libro').mostrar(true);
			} else if (completar == '" . equivalencia::autocompletar_campo_folio . "') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_fecha').ocultar(true);
				this.ef('valor_acta').ocultar(true);
				this.ef('valor_folio').mostrar(true);
				this.ef('valor_libro').ocultar(true);
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Validación sobre el campo fecha.
			var hoy = new $date;
			if (this.ef('valor_fecha').tiene_estado()) {
				var fecha = this.ef('valor_fecha').fecha();
				if (fecha > hoy) {
					notificacion.agregar('$msj_fecha');
					return false;
				}
			}
			return true;
		}

		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.evt__completar = function()
		{
			var form_ml = this.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();

			var criterio = this.ef('para').get_estado();

			var campos;
			var valores;
			var ef_campo;
			var completar = true;
			switch (this.ef('campo_completar').get_estado()) {
				case '" . equivalencia::autocompletar_campo_nota . "':
					// No se recorren los campos posteriormente para completar valores. Se hace aqui
					completar = false;
					
					var nota = this.ef('valor_nota').get_estado();
					var resultado = null;
					
					// Valido que la nota sea valida para todas las escalas
					var valido = true;
					for (id_fila in filas) {
						valido = valido && eval('es_nota_valida_para_escala_'+escalas[id_fila]+'(nota)');
					}
					
					if (valido) {
						// Si la nota es valida, completo los campos nota y resultado en el form_ml
						for (id_fila in filas) {
							ef_campo = form_ml.ef('nota').ir_a_fila(filas[id_fila]);
							if (criterio == '$autocompletar_para_todos' || criterio == '$autocompletar_para_sin_datos' && ! ef_campo.tiene_estado()) {
								ef_campo.set_estado(nota);
								ef_campo = form_ml.ef('resultado').ir_a_fila(filas[id_fila]);					
								resultado = eval('get_resultado_para_escala_'+escalas[id_fila]+'(nota)');
								ef_campo.set_estado(resultado);
							}
						}
					} else {
						alert('$msj_nota');
					}

					break;
				case '" . equivalencia::autocompletar_campo_resultado . "':
					campos = ['resultado','nota'];
					var nota = null;
					var resultado = this.ef('valor_resultado').get_estado();
					valores = {'nota': nota, 'resultado': resultado};
					break;
				case '" . equivalencia::autocompletar_campo_fecha . "':
					campos = ['fecha'];
					var fecha = this.ef('valor_fecha').get_estado();
					valores = {'fecha': fecha};
					break;
				case '" . equivalencia::autocompletar_campo_acta . "':
					campos = ['acta'];
					var acta = this.ef('valor_acta').get_estado();
					valores = {'acta': acta};
					break;
				case '" . equivalencia::autocompletar_campo_folio . "':
					campos = ['folio'];
					var folio = this.ef('valor_folio').get_estado();
					valores = {'folio': folio};
					break;
				case '" . equivalencia::autocompletar_campo_libro . "':
					campos = ['libro'];
					var libro = this.ef('valor_libro').get_estado();
					valores = {'libro': libro};
					break;
			}

			if (completar) {
				for (id_fila in filas) {
					for (i=0; i<campos.length; i++) {
						ef_campo = form_ml.ef(campos[i]).ir_a_fila(filas[id_fila]);
						if (criterio == '$autocompletar_para_todos' || criterio == '$autocompletar_para_sin_datos' && ! ef_campo.tiene_estado()) {
							ef_campo.set_estado(valores[campos[i]]);
						}
					}
				}
			}

			return false;
		}		
		";
	}
}
?>