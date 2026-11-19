<?php
/**
 *
 * @package		Matrícula
 * @subpackage	Regularidades
 */
class co_solicitudes_readmision
{
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		sga_readmisiones_solicitud.solicitud_readmision,
							sga_readmisiones_solicitud.perdida_regularidad,
							sga_readmisiones_solicitud.anio_academico,
							sga_readmisiones_solicitud.alumno,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
							sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
							sga_alumnos.legajo,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_readmisiones_solicitud.estado,
							to_char( sga_readmisiones_solicitud.fecha_solicitud, '".comunes::formato_fecha_visual."') as fecha_formato_visual, 
							sga_readmisiones_solicitud.fecha_solicitud,
							sga_readmisiones_solicitud.observaciones
				FROM		sga_readmisiones_solicitud
							JOIN sga_alumnos ON sga_readmisiones_solicitud.alumno = sga_alumnos.alumno
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
				$where
				ORDER BY	vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
							sga_propuestas.nombre_abreviado
		";
		return guarani_db::consultar($sql);
	}
}
?>