<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class cn_man_modificar_insc_propuesta extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'modificar_insc_propuesta';
	}
	
	function get_listado($filtro)
	{
		if (! empty($filtro)) {
			$filtro .= " AND ";
		}
		$filtro .= "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_pendiente . "', '" . propuesta::inscripcion_aceptada . "')";
		return guarani::co('co_inscripciones')->get_alumnos_inscriptos($filtro);
	}
}
?>