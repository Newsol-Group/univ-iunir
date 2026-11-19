<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Constancias
 * 
 */
class form_seleccion_autoridades extends form_ml_requerido
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__requerido__procesar = function(es_inicial, fila) {
			var requerido = this.ef('requerido').ir_a_fila(fila).chequeado();
			if (requerido) {
				this.ef('en_calidad_de').ir_a_fila(fila).activar();
			} else {
				this.ef('en_calidad_de').ir_a_fila(fila).resetear_estado();
				this.ef('en_calidad_de').ir_a_fila(fila).desactivar();
			}
		}
		";
	}
}
?>