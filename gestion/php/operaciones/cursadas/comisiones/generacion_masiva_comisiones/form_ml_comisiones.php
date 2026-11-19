<?php
class form_ml_comisiones extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_turnos_sin_com = guarani_mensajes::get('800_no_seleccion', array('la cantidad de Comisiones de un Turno'));

		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = false;
			var filas = this.filas();
			for (id_fila in filas) {
				var cantidad = this.ef('cantidad').ir_a_fila(filas[id_fila]).get_estado();
				if ((cantidad != null) && (cantidad != 0)) {
					ok = true;
					break;
				}
			}
			if (!ok) notificacion.agregar('$mje_turnos_sin_com');
			return ok;
		}
		";
	}

}
?>