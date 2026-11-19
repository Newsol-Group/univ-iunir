<?php
class form_ml_perfiles extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		$perfil_datos = empty($perfil_datos) ? '' : current($perfil_datos);
		
		$mensaje = guarani_mensajes::get('800MAT_perfiles_funcionales_default');
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		var fila_chequeada = false;
		{$this->objeto_js}.evt__usuario_grupo_acc__procesar = function(es_inicial, fila)
		{
			if ('$perfil_datos' != '') {
				this.ef('usuario_perfil_datos').ir_a_fila(fila).set_obligatorio(true);
			}
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__grupo_acceso_default__procesar = function(es_inicial, fila)
		{
			// Verifico si hay alguno chequeado, si es asi deshabilito los demás
			var filas = this.filas();
			//Para el caso cuando se agrega una fila nueva, y ya hay uno seleccionado
			if (es_inicial && fila_chequeada && !this.ef('grupo_acceso_default').ir_a_fila(fila).chequeado()) {
				this.ef('grupo_acceso_default').ir_a_fila(fila).desactivar();
			}
		
			if (this.ef('grupo_acceso_default').ir_a_fila(fila).chequeado()) {
				fila_chequeada = true;
				for (id_fila in filas) {
					if (fila != filas[id_fila]) {						
						this.ef('grupo_acceso_default').ir_a_fila(filas[id_fila]).desactivar();
					}
				}				
			} else {
				//Tengo que ver si hay alguna de todas chequeada, sino las paso todas activas				
				fila_chequeada = false;
				for (id_fila in filas) {
					if (this.ef('grupo_acceso_default').ir_a_fila(filas[id_fila]).chequeado()) {
						fila_chequeada = true;
					}
				}
				if (!fila_chequeada) {
					for (id_fila in filas) {									
						this.ef('grupo_acceso_default').ir_a_fila(filas[id_fila]).activar();
					}
				}
			}
		}				
		";
	}
}
?>