<?php
class form_comisiones extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$cuadro = $this->controlador()->dep('cuadro_comisiones')->get_objeto_js();
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__comisiones_a_copiar__procesar = function(es_inicial)
		{
			if (this.ef('comisiones_a_copiar').tiene_estado()) {
				if (this.ef('comisiones_a_copiar').get_estado() == '".comunes::todas."') {
					$cuadro.seleccionar_todos('seleccion');
					$cuadro.ocultar();
				} else {
					$cuadro.deseleccionar_todos('seleccion');
					$cuadro.mostrar();
				}
			}
		}
		";
	}
}
?>