<?php
/**
 *
 *
 * @package 	Matriculas
 * @subpackage 	Personas
 * 
 */
class form_ant_prof extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$current_date = guarani_fecha::get_hoy_js();
		$mensaje_fecha_desde = guarani_mensajes::get('800_control_fechas', array('desde', 'mayor', 'actual'));
		$mensaje_fecha_hasta = guarani_mensajes::get('800_control_fechas', array('hasta', 'menor', 'desde'));

		echo "
		//---- Validacion de EFs -----------------------------------

		{$this->objeto_js}.evt__fecha_desde__validar = function()
		{
			var hoy = new $current_date;

			if (this.ef('fecha_desde').tiene_estado() && this.ef('fecha_desde').fecha() > hoy) {
				this.ef('fecha_desde').set_error('$mensaje_fecha_desde');
				return false;
			}
			return true;
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_desde').tiene_estado() && this.ef('fecha_hasta').tiene_estado()) {
				if (this.ef('fecha_hasta').fecha() < this.ef('fecha_desde').fecha()) {
					notificacion.agregar('$mensaje_fecha_hasta');
					return false;
				}
			}
			return true;
		}
		";
	}

}
?>