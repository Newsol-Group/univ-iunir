<?php
class form_actividades extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
        $cuadro = $this->controlador()->dep('cuadro_actividades')->get_objeto_js();
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tod_alg__procesar = function(es_inicial)
		{
            if (this.ef('tod_alg').tiene_estado()) {
                if (this.ef('tod_alg').get_estado() == 'T') {
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