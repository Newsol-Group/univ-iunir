<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class form_filtro_dist_alumnos_comisiones extends guarani_pers_ei_formulario
{
	function extender_objeto_js()
	{
		$alumno_con_subcomision = comision::alumno_con_subcomision;
		echo "			
			{$this->objeto_js}.hay_cambios = function(ef_actual)
			{
				return false;
			}
			
            //---- Procesamiento de EFs --------------------------------

            {$this->objeto_js}.evt__alumnos__procesar = function(es_inicial)
            {
                if (this.ef('alumnos').get_estado() == $alumno_con_subcomision) {
                    this.ef('clase').mostrar();
                } else {
                    this.ef('clase').ocultar();
                    this.ef('clase').resetear_estado();
                }
            }

            //---- Validacion de EFs -----------------------------------

            {$this->objeto_js}.evt__clase__validar = function()
            {
                if (this.ef('alumnos').tiene_estado() &&
                    this.ef('alumnos').get_estado() == $alumno_con_subcomision &&
                    !this.ef('clase').tiene_estado()) {
                    this.ef('clase').set_error('Debe seleccionar un tipo de clase');
                    return false;
                }
                return true;
            }
		";
	}

}
?>