<?php
class seleccion_planes extends form_ml_requerido
{

	function ini()
	{
		$this->controlador()->cn()->get_elementos_planes_exeptuados();
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$usados = $this->controlador()->cn()->get_elementos_planes_exeptuados();		
		echo "			
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__requerido__procesar = function(es_inicial, fila)
		{
			this.ef('plan').ir_a_fila(fila).ocultar();
			var i = 0;
			";
			if (guarani_arreglos::count($usados) > 1) {
				$usados = implode(',', $usados);
				echo "
					var valores = new Array($usados);					
					for(i; i < valores.length; i ++) {						
						if (this.ef('plan').ir_a_fila(fila).get_estado() == valores[i]) {
							this.ef('requerido').ir_a_fila(fila).set_solo_lectura();
						}
					}";
				
				
			} elseif (guarani_arreglos::count($usados) == 1) {
				$usados = current($usados);
				echo "
						if (this.ef('plan').ir_a_fila(fila).get_estado() == $usados) {
							this.ef('requerido').ir_a_fila(fila).set_solo_lectura();
						}
					";				
			}
		echo "
		}
		";
	}

}
?>