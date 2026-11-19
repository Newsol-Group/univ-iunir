<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Actas
 * 
 */
class form_ml_folios extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.completar_datos = function(libro, primer_ff)
		{
			var filas = this.filas();
			var folio_fisico = primer_ff;
			
			for (id_fila in filas) {
				this.ef('folio_fisico').ir_a_fila(filas[id_fila]).set_estado(folio_fisico);
				folio_fisico++;
			}
		}
		";
	}

}
?>