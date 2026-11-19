<?php
class formulario extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$cant_clases = $this->controlador()->controlador()->get_cant_clases_comision();
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__porcentaje_regular__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('porcentaje_regular').tiene_estado()) {
				var porcentaje_regular = this.ef('porcentaje_regular').get_estado();
				if (porcentaje_regular >= 0 && porcentaje_regular <= 100) {
					var cant_clases_regular = (porcentaje_regular * $cant_clases) / 100;
					this.ef('cant_clases_regular').set_estado(Math.floor(cant_clases_regular));
				} else {
					this.ef('cant_clases_regular').resetear_estado();
				}
			}
		}
		
		{$this->objeto_js}.evt__porcentaje_promocion__procesar = function(es_inicial)
		{
			if (! es_inicial && this.ef('porcentaje_promocion').tiene_estado()) {
				var porcentaje_promocion = this.ef('porcentaje_promocion').get_estado();
				if (porcentaje_promocion >= 0 && porcentaje_promocion <= 100) {
					var cant_clases_promocion = (porcentaje_promocion * $cant_clases) / 100;
					this.ef('cant_clases_promocion').set_estado(Math.floor(cant_clases_promocion));
				} else {
					this.ef('cant_clases_promocion').resetear_estado();
				}
			}
		}
		";
	}
}
?>