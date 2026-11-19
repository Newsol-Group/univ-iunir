<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class form_ml_docentes_asociados_subcomision extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_existencia_fechas = guarani_mensajes::get('800CUR_completar_fechas');

		echo "
			//-------------------- Validación de efs ---------------------------------------
			
			{$this->objeto_js}.evt__validar_datos = function()
			{
				//Se verifica que se hayan seteado ambas o ninguna de las fechas de las asignaciones docentes a la subcomisión.
				var ok = true;
				var filas = this.filas();
				for (id_fila in filas) {
					if ((this.ef('fecha_desde').ir_a_fila(filas[id_fila]).tiene_estado() && !this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).tiene_estado())
					   || (!this.ef('fecha_desde').ir_a_fila(filas[id_fila]).tiene_estado() && this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).tiene_estado())) {
						var docente = this.ef('nombre_persona').ir_a_fila(fila).get_estado();
						notificacion.agregar('$msj_existencia_fechas'+docente);
						ok = false;
					}
				}
				
				return ok;
			}
		";
	}

}
?>