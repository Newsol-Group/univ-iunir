<?php
class form_ml_roles_docentes extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$mensaje_valor_duplicado = guarani_mensajes::get('dato_duplicado', array('valor', 'Rol'));
		echo "
		//---- Validacion general --------------------------------
		{$this->objeto_js}.evt__validar_datos = function()
		{
				var filas = this.filas();
				var valores = [];
				for (valor in filas){
					var valor = this.ef('rol').ir_a_fila(filas[valor]).get_estado();
					if (in_array(valor, valores)) {
						notificacion.agregar('$mensaje_valor_duplicado');
						return false;
					} else {
						valores.push(valor);
					}
				}
				return true;
		}
		";
	}
}
?>