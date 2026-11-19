<?php
/**
 *
 * 
 * @package 	Tesis
 * @subpackage 	
 * 
 */
class form_ml_forms extends form_ml_requerido
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		$msj_copias_obligatorio = guarani_mensajes::get('dato_faltante', array('Cant. Copias'));

		echo "
		
		//---- Validacion de EFs -----------------------------------
			
		{$this->objeto_js}.evt__cant_copias__validar = function(fila)
		{
			if (this.ef('requerido').ir_a_fila(fila).chequeado() && !this.ef('cant_copias').ir_a_fila(fila).tiene_estado()) {
				this.ef('cant_copias').ir_a_fila(fila).set_error('$msj_copias_obligatorio');
				return false;
			}
			return true;
		} 
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__requerido__procesar = function(es_inicial, fila)
		{
			if (this.ef('requerido').ir_a_fila(fila).chequeado()) {
				this.ef('cant_copias').ir_a_fila(fila).set_solo_lectura(false);
			} else {
				this.ef('cant_copias').ir_a_fila(fila).resetear_estado();
				this.ef('cant_copias').ir_a_fila(fila).set_solo_lectura(true);
			}
		}
		
		";
	}

}
?>