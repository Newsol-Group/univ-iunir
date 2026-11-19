<?php
class form_calcular_notas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table>
					<tr>
						<td>'; $this->generar_html_ef('instancia', '100px'); echo '</td>
						<td>'; $this->generar_html_ef('aplicar_a', '60px'); echo '</td>
						<td>'; $this->generar_html_ef('regla_calculo', '100px'); echo '</td>
						<td>'; $this->generar_botones('ei-botonera-bajita'); echo '</td>
					</tr>
				</table>';
						
		$this->evento('calcular_notas')->set_en_botonera(false);
		$this->evento('calcular_notas_todos')->set_en_botonera(false);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}";

		$autocompletar_para_todos_pagina = comision::autocompletar_para_todos_pagina;
		$autocompletar_para_sin_datos_pagina = comision::autocompletar_para_sin_datos_pagina;
		$autocompletar_para_todos = comision::autocompletar_para_todos;
		$autocompletar_para_sin_datos = comision::autocompletar_para_sin_datos;

		$instancia_cursar = comision::inst_cursar;
		$instancia_promocionar = comision::inst_promocionar;

		$mapeo_instancia_escala = $this->controlador()->controlador()->generar_mapeo_js_instancia_escala();
		$mapeo_resultado_condicion = $this->controlador()->controlador()->generar_mapeo_js_resultado_condicion();

		echo "
		var escalas = $mapeo_instancia_escala;
		var condiciones_defecto = {$mapeo_resultado_condicion['defecto']};

		var campo;
		var mapeo_alumnos_filas = [];
		
		{$this->objeto_js}.evt__aplicar_a__procesar = function(es_inicial)
		{
			var aplicar_a = this.ef('aplicar_a').get_estado();
			if (aplicar_a == '$autocompletar_para_todos' || aplicar_a == '$autocompletar_para_sin_datos') {
				this.ocultar_boton('calcular_notas');
				this.mostrar_boton('calcular_notas_todos');
			} else {
				this.mostrar_boton('calcular_notas');
				this.ocultar_boton('calcular_notas_todos');
			}
		}

		{$this->objeto_js}.evt__calcular_notas = function()
		{
			var aplicar_a = this.ef('aplicar_a').get_estado();
			var instancia = this.ef('instancia').get_estado();

			if (instancia == $instancia_cursar) {
				campo = 'nota_cursada';
				campo_control = 'instancia_cursada';
			} else {
				campo = 'nota_promocion';
				campo_control = 'instancia_promocion';
			}

			var form_ml = this.controlador.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();
			var alumnos = [];
			var alumno;
			for (id_fila in filas) {
				if (form_ml.ef(campo_control).ir_a_fila(filas[id_fila]).get_estado() &&
					form_ml.ef(campo).ir_a_fila(filas[id_fila]).activo() &&
					(aplicar_a == '$autocompletar_para_todos_pagina' || aplicar_a == '$autocompletar_para_sin_datos_pagina' && ! form_ml.ef(campo).ir_a_fila(filas[id_fila]).tiene_estado())) {
					alumno = form_ml.ef('alumno').ir_a_fila(filas[id_fila]).get_estado();
					mapeo_alumnos_filas[alumno] = id_fila;
					alumnos.push(alumno);
				}
			}

			var params = [];
			params['instancia'] = instancia;
			params['aplicar_a'] = aplicar_a;
			params['regla_calculo'] = this.ef('regla_calculo').get_estado();
			params['alumnos'] = alumnos;

			this.controlador.ajax('calcular_notas', params, this, this.respuesta_calcular_notas);
			return false;
		}
		
		{$this->objeto_js}.evt__calcular_notas_todos = function()
		{		
		
			var aplicar_a = this.ef('aplicar_a').get_estado();
			var instancia = this.ef('instancia').get_estado();
			var escala;
			
			if (instancia == $instancia_cursar) {
				campo = 'nota_cursada';
				campo_control = 'instancia_cursada';
				escala = escalas[$instancia_cursar];
			} else {
				campo = 'nota_promocion';
				campo_control = 'instancia_promocion';
				escala = escalas[$instancia_promocionar];
			}
			
			var form_ml = this.controlador.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();
			var alumno;
			for (id_fila in filas) {
				if (form_ml.ef(campo_control).ir_a_fila(filas[id_fila]).get_estado() &&
					form_ml.ef(campo).ir_a_fila(filas[id_fila]).activo() &&
					(aplicar_a == '$autocompletar_para_todos' || aplicar_a == '$autocompletar_para_sin_datos' && ! form_ml.ef(campo).ir_a_fila(filas[id_fila]).tiene_estado())) {
					alumno = form_ml.ef('alumno').ir_a_fila(filas[id_fila]).get_estado();
					mapeo_alumnos_filas[alumno] = id_fila;
				}
			}
			
			var params = [];
			params['instancia'] = instancia;
			params['aplicar_a'] = aplicar_a;
			params['regla_calculo'] = this.ef('regla_calculo').get_estado();
			params['escala'] = escala;

			this.controlador.ajax('calcular_notas_todos', params, this, this.respuesta_calcular_notas);
			return false;
		}

		{$this->objeto_js}.respuesta_calcular_notas = function(respuesta)
		{
			var form_ml = this.controlador.controlador.dep('form_ml_notas');
			var filas = form_ml.filas();
			var nota;
			var nota_formateada;
			var resultado;
			var condicion;
			var escala_no_numerica = 0;
			var evaluaciones_no_coherentes = 0;
			var evaluaciones_escalas_no_comp = 0;
			var sin_evaluaciones = 0;
			for (alumno in respuesta) {
			
				// Los alumnos de otras paginas no hay que actualizarlos en 'form_ml_notas', se actualizan solos al cambiar de página
				if(mapeo_alumnos_filas[alumno] == undefined) {
					continue;
				}
				
				nota = respuesta[alumno];
				var i = 0;
				if (nota != null &&  nota!= -1 && nota!=-2 && nota!=-3 && nota!=-4) {
					if (campo == 'nota_cursada') {
						nota_formateada = eval('formatear_nota_escala_'+escalas[$instancia_cursar]+'(nota)');
						resultado = eval('get_resultado_para_escala_'+escalas[$instancia_cursar]+'(nota)');
						if (resultado != '') {
							form_ml.ef('resultado_cursada').ir_a_fila(filas[mapeo_alumnos_filas[alumno]]).set_estado(resultado);
						}
						condicion = condiciones_defecto[resultado];
						form_ml.ef('cond_regularidad').ir_a_fila(filas[mapeo_alumnos_filas[alumno]]).set_estado(condicion);
					} else {
						nota_formateada = eval('formatear_nota_escala_'+escalas[$instancia_promocionar]+'(nota)');
						resultado = eval('get_resultado_para_escala_'+escalas[$instancia_promocionar]+'(nota)');
						if (resultado != '') {
							form_ml.ef('resultado_promocion').ir_a_fila(filas[mapeo_alumnos_filas[alumno]]).set_estado(resultado);
						}
					}
					form_ml.ef(campo).ir_a_fila(filas[mapeo_alumnos_filas[alumno]]).set_estado(nota_formateada);
				} else {
					i = i+1;
					if(nota == -1) {
						escala_no_numerica = 1;
					}
					if(nota == -2) {
						evaluaciones_no_coherentes = 1;
					}
					if(nota == -3) {
						evaluaciones_escalas_no_comp = 1;
					}
					if(nota == -4) {
						sin_evaluaciones = 1;
					}
				}
			}
			if (i >= 1) {
				if (escala_no_numerica == 1) {
					notificacion.agregar('No se ha podido calcular el promedio porque la escala de notas de la comisión no es numérica.');
					notificacion.mostrar();
					notificacion.limpiar();
				}
				if (evaluaciones_no_coherentes == 1) {
					notificacion.agregar('No se ha podido calcular el promedio porque no hay evaluaciones promediables.');
					notificacion.mostrar();
					notificacion.limpiar();
				}
				if (evaluaciones_escalas_no_comp == 1) {
					notificacion.agregar('No se ha podido calcular el promedio porque las evaluaciones no poseen escalas de notas compatibles.');
					notificacion.mostrar();
					notificacion.limpiar();
				}
				if (sin_evaluaciones == 1) {
					notificacion.agregar('No se ha podido calcular el promedio porque no hay evaluaciones.');
					notificacion.mostrar();
					notificacion.limpiar();
				}
			}
		}
		";
	}
}