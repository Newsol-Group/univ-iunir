<?php
class form_comisiones extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__catedras_activas__procesar = function(es_inicial)
		{			
			var catedras_activas = this.ef('catedras_activas').get_estado();
			
			if (catedras_activas) {
				this.ef('crear_comision_x_catedra_act').activar();
				this.ef('utilizar_docentes').activar();
			} else {
				this.ef('crear_comision_x_catedra_act').resetear_estado();
				this.ef('crear_comision_x_catedra_act').desactivar();
				this.ef('utilizar_docentes').resetear_estado();
				this.ef('utilizar_docentes').desactivar();
			}
		}
		";
	}

}
?>