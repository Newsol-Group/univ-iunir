<?php
class form_periodo_generico extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$periodo_lectivo = periodo_generico::tipo_periodo_lectivo;
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__periodo_generico_tipo__procesar = function(es_inicial)
		{
			var periodo_generico_tipo = this.ef('periodo_generico_tipo').get_estado();
			var ocultar = false;
			if (periodo_generico_tipo != $periodo_lectivo) {
				ocultar = true;
			}
			if (ocultar) {
				this.ef('periodo_lectivo_tipo').ocultar();
			} else {
				this.ef('periodo_lectivo_tipo').mostrar();
			}
			this.ef('periodo_lectivo_tipo')._obligatorio = !ocultar;
			//Reseteo al final para que muestre correctamente el indicador de obligatoriedad
			if (ocultar) {
				this.ef('periodo_lectivo_tipo').resetear_estado();
			}
		}
		";
	}

}
?>