<?php
/**
 *
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class co_expulsados_en_otras_instituciones
{
	/**
        Obtiene una lista de expulsados en otras instituciones según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "	SELECT 	sga_alumnos_expulsados.nro_expulsion,
							sga_alumnos_expulsados.institucion,
							sga_instituciones.nombre_abreviado as institucion_nombre,
							sga_alumnos_expulsados.pais,
							sga_alumnos_expulsados.tipo_documento,
							sga_alumnos_expulsados.nro_documento,
							mdp_tipo_documento.desc_abreviada || ' ' || sga_alumnos_expulsados.nro_documento as tipo_nro_documento,
							sga_alumnos_expulsados.cuil,
							sga_alumnos_expulsados.apellido,
							sga_alumnos_expulsados.nombres,
							sga_alumnos_expulsados.tipo_sancion,
							sga_sanciones_tipos.nombre as tipo_sancion_nombre,
							sga_alumnos_expulsados.resolucion,
							sga_alumnos_expulsados.nro_expediente_notificacion,
							sga_alumnos_expulsados.fecha_desde,
							sga_alumnos_expulsados.fecha_hasta,
							sga_alumnos_expulsados.nombres || ' ' || sga_alumnos_expulsados.apellido as persona
					FROM 	sga_alumnos_expulsados,
							mdp_tipo_documento,
							sga_instituciones,
							sga_sanciones_tipos
					WHERE	mdp_tipo_documento.tipo_documento = sga_alumnos_expulsados.tipo_documento AND
							sga_instituciones.institucion = sga_alumnos_expulsados.institucion AND
							sga_sanciones_tipos.tipo_sancion = sga_alumnos_expulsados.tipo_sancion
							$where
					ORDER BY apellido
							;";
		return guarani_db::consultar($sql);
	}
	
	/**
        Verifica si una persona fue expulsada en otra institución
    */
	function fue_expulsado($persona)
	{
		if (isset($persona)) {
			$persona = toba::db()->quote($persona);
		}

		$sql = "	SELECT 	COUNT(sga_alumnos_expulsados.nro_expulsion) as cant 
					FROM 	sga_alumnos_expulsados,
							mdp_personas,
							mdp_personas_documentos
					WHERE	mdp_personas.persona = $persona AND 
							mdp_personas.persona = mdp_personas_documentos.persona AND 
							mdp_personas_documentos.tipo_documento = sga_alumnos_expulsados.tipo_documento AND
							mdp_personas_documentos.nro_documento = sga_alumnos_expulsados.nro_documento;";
		$res = guarani_db::consultar_fila($sql);		
		
		return ($res['cant'] != 0);
	}
	
	/**
	 * Determina si un nro. de identificación dado registra expulsiones en alguna institución.
	 *
	 * @param int $pais
	 * @param int $tipo_documento
	 * @param int $nro_documento
	 * @return boolean
	 */
	function identificacion_registra_expulsiones($pais_documento, $tipo_documento, $nro_documento)
	{
		$pais_documento = is_null($pais_documento) ? persona::pais_argentina : toba::db()->quote($pais_documento);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$nro_documento = toba::db()->quote($nro_documento);
		
		$sql = "SELECT 	COUNT	(sga_alumnos_expulsados.nro_expulsion) as cant 
				FROM 	sga_alumnos_expulsados
				WHERE	sga_alumnos_expulsados.pais = $pais_documento
						AND sga_alumnos_expulsados.tipo_documento = $tipo_documento
						AND sga_alumnos_expulsados.nro_documento = $nro_documento";
		
		$res = guarani_db::consultar_fila($sql);		
		
		return ($res['cant'] != 0);
	}
	
}

?>