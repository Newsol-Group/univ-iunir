<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class form_notas extends guarani_pers_ei_formulario_ml
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$mensaje_nota_duplicada = guarani_mensajes::get('dato_duplicado', array('nota','Escala de Notas'));
		$si = comunes::si;
		
		echo "
			
		//---- Validacion general --------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var filas = this.filas();
			var notas = [];
			for (nota in filas){
				var nota = this.ef('nota').ir_a_fila(filas[nota]).get_estado();
				if (in_array(nota, notas)) {
					notificacion.agregar('$mensaje_nota_duplicada' + '(' + nota + ')');
					return false;
				} else {
					notas.push(nota);
				}
			}
			return true;
		}
			
		//---- Validacion de EFs -----------------------------------
		
		function esNumerico(n) 
		{
			return !isNaN(parseFloat(n)) && isFinite(n);
		}
		
		{$this->objeto_js}.evt__nota__validar = function(fila)
		{
			var es_numerica = this.controlador.dep('escala_notas').ef('es_numerica').get_estado();
			if (es_numerica == '$si') {
				var nota = this.ef('nota').ir_a_fila(fila).get_estado();
				this.ef('nota').set_error('No es numérico.');
				return esNumerico(nota);
			} else {
				return true;
			}
		}
		
		ei_formulario_ml.prototype.refrescar_numeracion_filas = function () {
			var orden = 1;
			var filas = this.filas();
			for (id_fila in filas) {
				this.ef('orden').ir_a_fila(filas[id_fila]).set_estado(orden);
				orden++;
			}
		};
		";
	}


}
?>