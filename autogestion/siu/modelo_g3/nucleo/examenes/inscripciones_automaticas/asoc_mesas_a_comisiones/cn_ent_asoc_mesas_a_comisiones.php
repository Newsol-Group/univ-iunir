<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class cn_ent_asoc_mesas_a_comisiones extends cn_entidad
{
	//-----------------------------------------------------------------------------------
	//---- llamados_mesa ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos($tabla = null)
	{
		return $this->tabla('llamados_mesa')->get();
	}

	//-----------------------------------------------------------------------------------
	//---- insc_auto_examen -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_asociaciones()
	{
		return $this->tabla('insc_auto_examen')->get_filas();
	}

	function asignar($asignacion)
	{
		$this->tabla('insc_auto_examen')->nueva_fila($asignacion);
	}

	function eliminar_fila($asignacion)
	{
		$fila = $this->tabla('insc_auto_examen')->get_filas($asignacion);
		if (!empty($fila)) {
			$this->tabla('insc_auto_examen')->eliminar_fila($fila[0]['x_dbr_clave']);
		}
	}

}
?>