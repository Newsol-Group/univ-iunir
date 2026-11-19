<?php
class form_ml_modulos_orden extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "

		{$this->objeto_js}.mostrar_puntaje = function(mostrar)
		{
			this.mostrar_columna('puntaje', mostrar);
		}

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var regla = this.controlador.dep('regla').dep('formulario').ef('regla').get_estado();
			if (regla === ".regla::por_puntaje_aprobadas." || regla === ".regla::por_puntaje_cursadas.") {		
				var filas = this.filas();
				for (id_fila in filas) {
					var puntaje = this.ef('puntaje').ir_a_fila(filas[id_fila]).get_estado();
					if (puntaje == null || puntaje <= 0) {
						notificacion.agregar('Los puntajes de los componentes deben ser mayor a Cero');
						return false;
					}
				}
			}
			return true;
		}



		{$this->objeto_js}.puntajes_estan_cargados = function()
		{
			var filas = this.filas();
			for (id_fila in filas) {
				var puntaje = this.ef('puntaje').ir_a_fila(filas[id_fila]).get_estado();
				if (puntaje == null || puntaje == 0) {
					return false;
				}
			}
			return true;
		}
		
		{$this->objeto_js}.get_puntaje_total = function()
		{
			var puntaje = 0;
			var filas = this.filas();
			for (id_fila in filas) {
				puntaje += this.ef('puntaje').ir_a_fila(filas[id_fila]).get_estado();
			}
			return puntaje;
		}

		";
	}

}
?>
