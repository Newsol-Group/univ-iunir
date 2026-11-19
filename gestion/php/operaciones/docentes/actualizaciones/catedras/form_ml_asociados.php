<?php
class form_ml_asociados extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Obtengo las filas
			var filas = this.filas();
			
			// Realizo las validaciones por fila
 			for (id_fila in filas) {
 				var ok = true;
      			
      			var dia = this.ef('dia_examen').ir_a_fila(filas[id_fila]).get_estado();				
				
				if (dia != 'nopar') {
					// Se seleccionó un día de la semana >> validar que al menos se ingrese el horario de inicio del examen
					if (!this.ef('hora_inicio_examen').ir_a_fila(filas[id_fila]).tiene_estado()) {
						var msj = 'Debe indicar Hora de Inicio para la actividad ' + this.ef('nombre_actividad').ir_a_fila(filas[id_fila]).get_estado();						
						alert(msj);
						return false;
					}
				}
				
				if ((this.ef('hora_inicio_examen').ir_a_fila(filas[id_fila]).tiene_estado()) && (this.ef('hora_fin_examen').ir_a_fila(filas[id_fila]).tiene_estado())) {
					// Si se ingresa el horario de finalización que este sea mayor al horario de inicio.
					
					var hora_ini = this.ef('hora_inicio_examen').ir_a_fila(filas[id_fila]).hora();
					var hora_fin = this.ef('hora_fin_examen').ir_a_fila(filas[id_fila]).hora();
					
					if (hora_fin <= hora_ini) {
						var msj = 'La Hora de Finalización debe ser posterior a la Hora de Inicio para la actividad ' + this.ef('nombre_actividad').ir_a_fila(filas[id_fila]).get_estado();						
						alert(msj);
						return false;
					}
				
				}
							
 			}			

			return true;
		}
		";
	}

}

?>