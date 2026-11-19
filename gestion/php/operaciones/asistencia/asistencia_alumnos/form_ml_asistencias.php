<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class form_ml_asistencias extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$valor_minimo = $this->controlador()->controlador()->get_minimo_inasistencias();
		$valor_maximo = $this->controlador()->controlador()->get_maximo_inasistencias();
		$msj_valor_maximo = guarani_mensajes::get('800_valor_mayor', array('Cantidad Inasistencias', $valor_maximo));
		$msj_valor_minimo = guarani_mensajes::get('800_valor_menor', array('Cantidad Inasistencias', $valor_minimo));

		echo "
				
		//---- Validacion de EFs -----------------------------------
			
		{$this->objeto_js}.evt__cant_inasistencias__validar = function(fila)
		{
			var ok = true;
				
			if (this.ef('cant_inasistencias').ir_a_fila(fila).tiene_estado()) {
				var cant = this.ef('cant_inasistencias').ir_a_fila(fila).get_estado();
				if (cant != 0 && cant < $valor_minimo) {
					this.ef('cant_inasistencias').ir_a_fila(fila).set_error('$msj_valor_minimo');
					ok = false;		
				}
				if (cant > $valor_maximo) {
					this.ef('cant_inasistencias').ir_a_fila(fila).set_error('$msj_valor_maximo');
					ok = false;
				}	
			}
			return ok;
		} 
	
		";
	}

}
?>