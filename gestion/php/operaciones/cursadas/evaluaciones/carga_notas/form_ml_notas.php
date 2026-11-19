<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class form_ml_notas extends guarani_pers_ei_formulario_ml
{
	function generar_botones($clase = '', $extra = '')
	{
		if ($this->hay_botones()) {
			echo "<div class='ei-botonera ei-botonera-centrada'>";
			$this->generar_boton('primera', false);
			$this->generar_boton('anterior', false);
			echo "<div style='padding-top:6px; display:inline-block;'>Página <strong>{$this->controlador()->get_pagina_actual()}</strong> de <strong>{$this->controlador()->get_cantidad_paginas()}</strong></div>";
			$this->generar_boton('siguiente', false, false);
			$this->generar_boton('ultima', false, false);
			echo "</div>";
		}
	}

	function generar_layout($ancho = 'auto')
	{
		if ($this->controlador()->get_cantidad_paginas() == 1) {
			$this->evento('primera')->set_en_botonera(false);
			$this->evento('anterior')->set_en_botonera(false);
			$this->evento('siguiente')->set_en_botonera(false);
			$this->evento('ultima')->set_en_botonera(false);
		}
		parent::generar_layout();
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_nota_invalida = guarani_mensajes::get('800_nota_invalida');
		$escala = $this->controlador()->get_escala_notas();
		$js_validacion_nota = $this->controlador()->get_js_validar_nota_escalas();

		echo "
		$js_validacion_nota
		var seteo_resultado_defecto = false;

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__nota__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				if (this.ef('nota').ir_a_fila(fila).tiene_estado()) {
					var nota = this.ef('nota').ir_a_fila(fila).get_estado();

					if (! eval('es_nota_valida_para_escala_'+$escala+'(nota)')) {
						var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();
						notificacion.limpiar();
						notificacion.agregar('$msj_nota_invalida' + alumno + '.');
						notificacion.mostrar();
						this.ef('nota').ir_a_fila(fila).resetear_estado();
						this.ef('resultado').ir_a_fila(fila).resetear_estado();
					} else {
						var resultado = eval('get_resultado_para_escala_'+$escala+'(nota)');
						seteo_resultado_defecto = true;
						this.ef('resultado').ir_a_fila(fila).set_estado(resultado);
						var nota_formateada = eval('formatear_nota_escala_'+$escala+'(nota)');
						this.ef('nota').ir_a_fila(fila).set_estado(nota_formateada);
					}
				} else {
					this.ef('resultado').ir_a_fila(fila).resetear_estado();
				}
			}
		}
		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				// Al cambiar el resultado de cursada (siempre que el mismo NO haya sido autocompletado), se resetea la nota de cursada.
				if (! seteo_resultado_defecto) {
					this.ef('nota').ir_a_fila(fila).resetear_estado();
				} else {
					seteo_resultado_defecto = false;
				}
			}
		}
		";
	}
}
?>