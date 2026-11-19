<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_confirmar extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$date = guarani_fecha::get_hoy_js();
		$mje_fecha_excep = guarani_mensajes::get('800_control_fechas', array(' Excepción', 'mayor o igual', ' Actual'));
		
		echo "
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_excepcion__validar = function()
		{
			var ok = true;
			var hoy = new $date;
			
			if(this.ef('fecha_excepcion').tiene_estado()) {
				if (this.ef('fecha_excepcion').fecha() > hoy) {
					this.ef('fecha_excepcion').set_error('$mje_fecha_excep');
					ok = false;		
				}
			}
			return ok;
		}
		";
	}
}

?>