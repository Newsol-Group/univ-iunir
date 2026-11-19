<?php
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
		$msj_nota_obligatoria = guarani_mensajes::get('800_dato_dep_obligatorio', array('nota', 'resultado'));
		$res_ausente = escalas_notas::resultado_ausente;
		$mapeo_instancia_escala = $this->controlador()->generar_mapeo_js_instancia_escala();
		$js_validacion_nota = $this->controlador()->get_js_validar_nota_escalas();
		$msj_error = guarani_mensajes::get('800_nota_invalida');

		echo "
		$js_validacion_nota
		var seteo_resultado_defecto = false;
		
		//---- Validacion de Datos --------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			//Se verifica que si se cargó el resultado con un valor diferente de 'Ausente' debe estar cargada la nota 
			//(a la inversa también es obligatorio pero no se controla ya que el resultado se setea automáticamente 
			//cuando se ingresa un valor para la nota).
			var filas = this.filas();
			for (id_fila in filas) {
				if (this.ef('resultado').ir_a_fila(filas[id_fila]).tiene_estado()) {
					var resultado = this.ef('resultado').ir_a_fila(filas[id_fila]).get_estado();
					if ((resultado != '$res_ausente') && !this.ef('nota').ir_a_fila(filas[id_fila]).tiene_estado()) {
						ok = false;
						break;
					}
				}
			}
			if (!ok) {
				notificacion.agregar('$msj_nota_obligatoria');
			}
			return ok;
		}
		
		//---- Procesamiento de ef's ------------------------------
		
		{$this->objeto_js}.evt__nota__procesar = function(es_inicial, fila) 
		{
			if (!es_inicial) {
				if (this.ef('nota').ir_a_fila(fila).tiene_estado()) {
					var nota = this.ef('nota').ir_a_fila(fila).get_estado();
					var instancia = this.ef('instancia').ir_a_fila(fila).get_estado();
					var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();
					var escala = $mapeo_instancia_escala;

					var es_nota_valida = eval('es_nota_valida_para_escala_'+escala[instancia]+'(nota)');

					if(! es_nota_valida){
						//La nota no pertenece a la escala, se retorna el msj de error.
						
						notificacion.limpiar();
						notificacion.agregar('$msj_error'+alumno);
						notificacion.mostrar();
						this.ef('nota').ir_a_fila(fila).resetear_estado();
						this.ef('resultado').ir_a_fila(fila).resetear_estado();
					} else {
						//La nota corresponde a la escala y se retorna el resultado por defecto obtenido a partir de ambas y la fila a setear.
						//Se setea un flag que permite distinguir el seteo por defecto del valor del resultado, de un cambio del usuario.
						var resultado = eval('get_resultado_para_escala_'+escala[instancia]+'(nota)');
						seteo_resultado_defecto = true;
						this.ef('resultado').ir_a_fila(fila).set_estado(resultado);
						var nota_format = eval('formatear_nota_escala_'+escala[instancia]+'(nota)');
						this.ef('nota').ir_a_fila(fila).set_estado(nota_format);
					}
				} else {
					this.ef('resultado').ir_a_fila(fila).resetear_estado();
				}
		 	}
		}

		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial, fila) 
		{
		 	if (!es_inicial) {
		 		//Si el resultado por defecto, correspondiente a la escala y nota, es modificado por el usuario, debe
		 		//resetearse el valor de la nota.
		 		if (!seteo_resultado_defecto) {
		 			this.ef('nota').ir_a_fila(fila).resetear_estado();
		 		} else {
		 			//Se resetea el flag utilizado para distinguir el seteo por codigo del ef y el del usuario.
		 			seteo_resultado_defecto = false;
		 		}
		 	}
		}
		";
	}

}
?>