<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Actas
 * 
 */
class form_ml_notas_eval extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if (!$this->controlador()->es_alumno_sin_evaluaciones()) {

			$msj_error = guarani_mensajes::get('800_nota_invalida');
			$js_validacion_notas_eval = $this->controlador()->get_js_validar_notas_eval_escalas();
			$mapeo_resultado_valor_desc = $this->controlador()->generar_mapeo_resultados_valor_desc();

			echo "
				$js_validacion_notas_eval
			
				{$this->objeto_js}.resultados = {$mapeo_resultado_valor_desc['resultados']};

				{$this->objeto_js}.evt__resultado__procesar = function(es_inicial, fila)
				{
					this.mostrar_columna('resultado',false);
					this.mostrar_columna('escala_nota',false);
				}

				{$this->objeto_js}.evt__nota__procesar = function(es_inicial, fila) 
				{
				 	if (!es_inicial) {
						if (this.ef('nota').ir_a_fila(fila).tiene_estado()) {
							
							var nota 	= this.ef('nota').ir_a_fila(fila).get_estado();
							var escala 	= this.ef('escala_nota').ir_a_fila(fila).get_estado();
							var evaluacion 		= this.ef('tipo_eval_nombre').ir_a_fila(fila).get_estado();
							var es_nota_valida	= eval('es_nota_valida_para_escala_'+escala+'(nota)');

							if(! es_nota_valida){
								
								//La nota no pertenece a la escala, se retorna el msj de error.
								notificacion.limpiar();
								notificacion.agregar('$msj_error'+evaluacion);
								notificacion.mostrar();
								this.ef('nota').ir_a_fila(fila).resetear_estado();
								this.ef('resultado_nombre').ir_a_fila(fila).resetear_estado();
							} else {
								
								//La nota corresponde a la escala y se retorna el resultado por defecto obtenido a partir de ambas y la fila a setear.
								//Se setea un flag que permite distinguir el seteo por defecto del valor del resultado, de un cambio del usuario.
								var valor_resultado = eval('get_resultado_para_escala_'+escala+'(nota)');
								this.ef('resultado_nombre').ir_a_fila(fila).set_estado(this.resultados[valor_resultado]);
								this.ef('resultado').ir_a_fila(fila).set_estado(valor_resultado);
								this.ef('nota').set_estado(eval('formatear_nota_escala_'+escala+'(nota)'));
							}
						} else {
							this.ef('resultado_nombre').ir_a_fila(fila).resetear_estado();
							this.ef('resultado').ir_a_fila(fila).resetear_estado();
						}
				 	}
				}
			";
		}
	}

}
?>