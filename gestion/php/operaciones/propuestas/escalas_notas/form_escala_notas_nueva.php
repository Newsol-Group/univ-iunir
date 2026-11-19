<?php
class form_escala_notas_nueva extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	
	function extender_objeto_js()
	{
		$js_validacion_nota			= $this->controlador()->get_js_validar_nota_escala_nueva();
		$msj_error					= guarani_mensajes::get('800_nota_invalida');
				
		echo "
		$js_validacion_nota
		
		{$this->objeto_js}.validar_nota = function(fila, campo)
		{
			if (this.ef(campo).ir_a_fila(fila).tiene_estado()) {
				var nota = this.ef(campo).ir_a_fila(fila).get_estado();
				var es_nota_valida = eval('es_nota_valida_para_escala_nueva(nota)');

				if(!es_nota_valida){

					//La nota no pertenece a la escala, se retorna el msj de error
					notificacion.limpiar();
					notificacion.agregar('La nota ingresada es inválida');
					notificacion.mostrar();

					//Reseteo estados
					this.ef(campo).ir_a_fila(fila).resetear_estado();
				} else {

					//La nota corresponde a la escala y se retorna el resultado por defecto obtenido a partir de ambas y la fila a setear
					//Se setea un flag que permite distinguir el seteo por defecto del valor del resultado, de un cambio del usuario
					var nota_format = eval('formatear_nota_escala_nueva(nota)');
					this.ef(campo).ir_a_fila(fila).set_estado(nota_format);
				}
			}
		  
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__nota_inicial__procesar = function(es_inicial, fila)
		{
			var campo = 'nota_inicial';
			this.validar_nota(fila,campo);
		}
		
		{$this->objeto_js}.evt__nota_final__procesar = function(es_inicial, fila)
		{
			var campo = 'nota_final';
			this.validar_nota(fila, campo);
		}
		
		";
		
		if (!$this->controlador()->cn()->entidad()->es_nueva() && $this->controlador()->cn()->entidad()->esta_en_uso()) {
			echo "
				{$this->objeto_js}.hay_cambios = function(ef_actual) 
				{
					return false;
				}
			";
		}
		
	}

}

?>