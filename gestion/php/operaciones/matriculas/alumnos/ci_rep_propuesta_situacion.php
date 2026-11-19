<?php
class ci_rep_propuesta_situacion extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$filtro .= " AND (	sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."' OR
							sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' OR
							sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_rechazada."')";
		$datos = guarani::co('co_inscripciones')->get_alumnos_detallado($filtro);
		return $datos;
	}

	function get_datos_cant($filtro)
	{
		$datos = guarani::co('co_inscripciones')->get_alumnos_detallado($filtro);
		return $datos;
	}
	}
?>