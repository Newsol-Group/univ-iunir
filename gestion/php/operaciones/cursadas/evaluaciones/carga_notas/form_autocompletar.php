<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width=100$>
					<tr>
						<td>'; $this->generar_html_ef('campo_completar'); echo '</td>
						<td>'; $this->generar_html_ef('valor_nota'); echo '</td>
						<td>'; $this->generar_html_ef('valor_resultado'); echo '</td>
						<td>'; $this->generar_html_ef('valor_corregido_por'); echo '</td>
						<td>'; $this->generar_html_ef('valor_observaciones'); echo '</td>
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
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		";

		$msj_nota = guarani_mensajes::get('800_nota_invalida', array('autocompletar.'));

		$campo_nota = comision::autocompletar_campo_nota;
		$campo_resultado = comision::autocompletar_campo_resultado;
		$campo_corregido_por = comision::autocompletar_campo_corregido_por;
		$campo_observaciones = comision::autocompletar_campo_observaciones;

		$autocompletar_para_todos_pagina = comision::autocompletar_para_todos_pagina;
		$autocompletar_para_sin_datos_pagina = comision::autocompletar_para_sin_datos_pagina;

		$escala = $this->controlador()->get_escala_notas();

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__campo_completar__procesar = function(es_inicial)
		{
			var completar = this.ef('campo_completar').get_estado();

			if (completar == '$campo_nota') {
				this.ef('valor_nota').mostrar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_corregido_por').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
			} else if (completar == '$campo_resultado') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').mostrar(true);
				this.ef('valor_corregido_por').ocultar(true);
				this.ef('valor_observaciones').ocultar(true);
			} else if (completar == '$campo_corregido_por') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_corregido_por').mostrar(true);
				this.ef('valor_observaciones').ocultar(true);
			} else if (completar == '$campo_observaciones') {
				this.ef('valor_nota').ocultar(true);
				this.ef('valor_resultado').ocultar(true);
				this.ef('valor_corregido_por').ocultar(true);
				this.ef('valor_observaciones').mostrar(true);
			}
		}

		{$this->objeto_js}.evt__valor_nota__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('valor_nota').tiene_estado()) {
				var nota = this.ef('valor_nota').get_estado();
				if (! eval('es_nota_valida_para_escala_'+$escala+'(nota)')) {
					notificacion.limpiar();
					notificacion.agregar('$msj_nota');
					notificacion.mostrar();
					this.ef('valor_nota').resetear_estado();
				} else {
					var nota_formateada = eval('formatear_nota_escala_'+$escala+'(nota)');
					this.ef('valor_nota').set_estado(nota_formateada);
				}
			}
		}

		//---- Eventos ---------------------------------------------

		{$this->objeto_js}.evt__completar = function()
		{
			var form_ml = this.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();

			var criterio = this.ef('para').get_estado();

			var campos;
			var valores;
			switch (this.ef('campo_completar').get_estado()) {
			case '$campo_nota':
				campos = ['resultado', 'nota'];
				var nota = this.ef('valor_nota').get_estado();
				var resultado = eval('get_resultado_para_escala_'+$escala+'(nota)');
				valores = {'nota': nota, 'resultado': resultado};
				break;
			case '$campo_resultado':
				campos = ['resultado'];
				var resultado = this.ef('valor_resultado').get_estado();
				valores = {'resultado': resultado};
				break;
			case '$campo_corregido_por':
				campos = ['corregido_por'];
				var corregido_por = this.ef('valor_corregido_por').get_estado();
				valores = {'corregido_por': corregido_por};
				break;
			case '$campo_observaciones':
				campos = ['observaciones'];
				var observaciones = this.ef('valor_observaciones').get_estado();
				valores = {'observaciones': observaciones};
				break;
			}

			var ef_campo;
			for (id_fila in filas) {
				for (i=0; i<campos.length; i++) {
					ef_campo = form_ml.ef(campos[i]).ir_a_fila(filas[id_fila]);
					if (criterio == '$autocompletar_para_todos_pagina' || criterio == '$autocompletar_para_sin_datos_pagina' && ! ef_campo.tiene_estado()) {
						ef_campo.set_estado(valores[campos[i]]);
					}
				}
			}

			return false;
		}
		";
	}
}
?>