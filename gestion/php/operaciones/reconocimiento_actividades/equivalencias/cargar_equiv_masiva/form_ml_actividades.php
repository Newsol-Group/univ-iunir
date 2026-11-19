<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class form_ml_actividades extends form_ml_requerido
{	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$js_escalas_nota = $this->controlador()->get_js_escalas_defecto();
		
		echo "
		$js_escalas_nota

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__requerido__procesar = function(es_inicial, fila) {
			if (es_inicial) {
				this.ef('requerido').ir_a_fila(fila).toggle();
				// Setear estado escala nota por defecto
				if (escalas_defecto[fila]) {
					this.ef('escala_nota').ir_a_fila(fila).set_estado(escalas_defecto[fila]);
				}
				this.ef('escala_nota').ir_a_fila(fila).desactivar();			
			} else {
				var requerido = this.ef('requerido').ir_a_fila(fila).chequeado();
				if (requerido) {
					this.ef('escala_nota').ir_a_fila(fila).activar();
					// Setear estado escala nota por defecto
					if (escalas_defecto[fila]) {
						this.ef('escala_nota').ir_a_fila(fila).set_estado(escalas_defecto[fila]);
					}
				} else {
					//this.ef('escala_nota').ir_a_fila(fila).resetear_estado();
					this.ef('escala_nota').ir_a_fila(fila).desactivar();
				}
			}
		}
		";
	}
}
?>