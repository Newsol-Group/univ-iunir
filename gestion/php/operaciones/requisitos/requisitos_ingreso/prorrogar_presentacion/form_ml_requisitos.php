<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_ml_requisitos extends form_ml_requerido
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
				this.ef('fecha_tope').ir_a_fila(fila).set_solo_lectura(false);
			} else {
				this.ef('fecha_tope').ir_a_fila(fila).resetear_estado();
				this.ef('fecha_tope').ir_a_fila(fila).set_solo_lectura(true);
			}
		}

		{$this->objeto_js}.evt__fecha_tope__validar = function(fila) {
			var requerido = this.ef('requerido').ir_a_fila(fila).chequeado();
			var fecha_tope = this.ef('fecha_tope').ir_a_fila(fila).get_estado();
			if (requerido && fecha_tope == '') {
				this.ef('fecha_tope').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
		";
	}
}

?>