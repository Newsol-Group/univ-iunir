<?php
class form_cambio_calidad extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$cant_inscripciones = $this->controlador()->controlador()->get_cant_inscripciones_alumno_seleccionado();
		$msj_confirmar = guarani_mensajes::get('800MAT_existen_inscripciones');
		echo "
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var calidad = this.ef('calidad').get_estado();
			if (calidad == '".alumno::calidad_pasivo."' && $cant_inscripciones > 0) {
				resp = confirm('$msj_confirmar');
				if (!resp) {
					return false;
				}
			}
			return true;
		}
		";
	}
}