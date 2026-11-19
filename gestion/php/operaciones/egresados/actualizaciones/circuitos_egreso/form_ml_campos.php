<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_ml_campos extends form_ml_requerido 
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__requerido__procesar = function(es_inicial, fila)
		{
			if (this.ef('requerido').ir_a_fila(fila).chequeado()) {
				this.ef('obligatorio').ir_a_fila(fila).set_solo_lectura(false);
			} else {
				this.ef('obligatorio').ir_a_fila(fila).set_solo_lectura(true);
				this.ef('obligatorio').ir_a_fila(fila).resetear_estado();
			}
		}
		";
	}

}

?>