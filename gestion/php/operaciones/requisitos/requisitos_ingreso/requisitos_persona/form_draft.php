<?php
/**
	*
	* 
	* @package     Reconocimiento de Actividades
	* @subpackage     Aprobación por Resolución
	* 
	*/
class form_draft extends guarani_pers_ei_formulario
{

	function extender_objeto_js()
	{
		
		echo "
		//---- carga de datos o opcion radio --------------------------------

		{$this->objeto_js}.evt__aceptar_rechazar__procesar = function()
		{
			var opcion_value = this.ef('aceptar_rechazar').get_estado();
			if (opcion_value == 'R') {
				this.ef('observaciones').set_solo_lectura(false)
				this.ef('observaciones').set_obligatorio(true);
			} else {
				this.ef('observaciones').resetear_estado();
				this.ef('observaciones').set_solo_lectura(true)
				this.ef('observaciones').set_obligatorio(false);
		
			}
		}";
		
	}

	function generar_layout()
	{
		echo '<fieldset><legend>Acción sobre el documento</legend>';
		$this->generar_html_ef('aceptar_rechazar',0);
		$this->generar_html_ef('observaciones');
		echo "</fieldset>";
	}
}