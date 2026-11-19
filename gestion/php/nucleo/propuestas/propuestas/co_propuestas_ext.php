<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class co_propuestas_ext
{
	function get_listado($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_propuestas_ext.propuesta_ext,
							sga_propuestas_ext.nombre,
							sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
							sga_instituciones.nombre as institucion_nombre,
							0 as propuesta
				FROM		sga_propuestas_ext
							LEFT JOIN sga_propuestas_tipos ON sga_propuestas_ext.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo,
							sga_responsables_academicas,
							sga_instituciones
				WHERE		sga_propuestas_ext.responsable_academica = sga_responsables_academicas.responsable_academica AND
							sga_responsables_academicas.institucion = sga_instituciones.institucion
							$where
				ORDER BY	sga_propuestas_ext.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_datos_propuesta_ext($propuesta_ext)
	{
		$propuesta_ext = toba::db()->quote($propuesta_ext);
		$sql = "SELECT	sga_propuestas_ext.nombre as propuesta_ext_nombre,
						sga_propuestas_tipos.descripcion as propuesta_tipo_ext_desc,
						sga_instituciones.institucion as institucion_ext,
						sga_instituciones.nombre as institucion_ext_nombre
				FROM	sga_propuestas_ext
						LEFT JOIN sga_propuestas_tipos ON sga_propuestas_ext.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo,
						sga_responsables_academicas,
						sga_instituciones
				WHERE	sga_propuestas_ext.responsable_academica = sga_responsables_academicas.responsable_academica AND
						sga_responsables_academicas.institucion = sga_instituciones.institucion AND
						sga_propuestas_ext.propuesta_ext = $propuesta_ext
		";
		return guarani_db::consultar_fila($sql);
	}
}
?>