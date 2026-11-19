<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class form_ml_actividades_destino extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$tipo_equiv_total = equivalencia::tipo_total;
		$no = comunes::no;
		
		echo "
	
		//---- Procesamiento de EFs -----------------------------------
		
		/**
		 * Se determina si el campo vencimiento será editable según el tipo de equivalencia de las actividades destino.
		 */
		{$this->objeto_js}.evt__condicion__procesar = function(es_inicial, fila)
		{
			var mostrar_vencimiento = false;
			if (this.ef('condicion').ir_a_fila(fila).tiene_estado()) {
				var tipo_equiv = this.ef('condicion').ir_a_fila(fila).get_estado();
				if (tipo_equiv != '$tipo_equiv_total') {
					mostrar_vencimiento = true;
				} 
			}
			if (!mostrar_vencimiento) {
				this.ef('tiene_vencimiento').ir_a_fila(fila).set_estado('$no');
				this.ef('tiene_vencimiento').ir_a_fila(fila).desactivar();
			} else {
				this.ef('tiene_vencimiento').ir_a_fila(fila).activar();
				if (!es_inicial) {
					this.ef('tiene_vencimiento').ir_a_fila(fila).resetear_estado();
				}
			}	
		}
		
		";
	}
}

?>