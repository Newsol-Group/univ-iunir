<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Constancia Alumno Regular
 * 
 */
class ci_constancia_alumno_regular extends ci_constancia 
{
	
	/**
	 * Se extiende el validar datos del ci constancia genérico para verificar al Guardar, que el alumno
	 * este activo
	 *
	 */
	function validar_datos()
	{
		$alumno = $this->get_alumno_solicitud();
		if (! (guarani::co('co_alumnos')->es_alumno_activo($alumno) && guarani::co('co_alumnos')->es_alumno_regular($alumno))) {
			throw guarani::error(guarani_mensajes::get('800MAT_alumno_no_activo_regular')); 
		}
		return true;
	}
	
}
?>