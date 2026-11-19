<?php
class form_ml_resoluciones extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_fecha_hasta = guarani_mensajes::get('800_control_fechas', array("de fin de vigencia", 'menor o igual', 'de inicio.'));
		echo "
		//---- Validacion de EFs -----------------------------------
		{$this->objeto_js}.evt__fecha_hasta__validar = function(fila)
		{
			if (this.ef('fecha_hasta').ir_a_fila(fila).tiene_estado()) {
				fecha_desde = this.ef('fecha_desde').ir_a_fila(fila).fecha();
				fecha_hasta = this.ef('fecha_hasta').ir_a_fila(fila).fecha();
				if (fecha_hasta <= fecha_desde) {
					this.ef('fecha_hasta').ir_a_fila(fila).set_error('$mje_fecha_hasta');
					return false;
				}
			}
			return true;
		}
		";
	}
}
?>