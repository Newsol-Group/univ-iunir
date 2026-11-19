<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class co_sanciones
{
	function get_listado($filtro='')
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro.' ';
		}

		$sql = "SELECT 	sga_sanciones.sancion,
						vw_personas.nombres,
						vw_personas.apellido,
						vw_personas.apellido_nombres AS nombre_completo,
						vw_personas.tipo_nro_documento AS persona_nro_documento,
						sga_sanciones.tipo_sancion,
						sga_sanciones.persona,
						sga_sanciones.fecha_desde,
						sga_sanciones.fecha_hasta,
						sga_sanciones.documento_alta,
						sga_sanciones.observaciones,
						sga_sanciones.fecha_cancelacion,
						sga_sanciones.documento_cancelacion,
						sga_sanciones.observaciones_cancelacion,
						sga_sanciones_tipos.nombre AS tipo_sancion_nombre
				FROM 	sga_sanciones,
						sga_sanciones_tipos,
						vw_personas
				WHERE	sga_sanciones.tipo_sancion = sga_sanciones_tipos.tipo_sancion AND
						sga_sanciones.persona = vw_personas.persona
						$filtro
				ORDER BY 3
				;";
		
		return guarani_db::consultar($sql);
	}

	function get_sanciones_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
			$where .= 	"
						-- Sanciones sin propuesta (para todas).
						AND (sga_sanciones.propuesta IS NULL OR
						-- Sanciones por propuesta filtradas por perfil de datos.
						$where_gatillo)";
		}
		$sql = "SELECT		sga_sanciones.sancion,
							vw_personas.nombres,
							vw_personas.apellido,
							vw_personas.tipo_nro_documento as persona_nro_documento,
                            to_char(sga_sanciones.fecha_desde,'".comunes::formato_fecha_visual."') AS fecha_desde,
                            to_char(sga_sanciones.fecha_hasta,'".comunes::formato_fecha_visual."') AS fecha_hasta,
                            to_char(sga_sanciones.fecha_cancelacion,'".comunes::formato_fecha_visual."') AS fecha_cancelacion,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
							sga_sanciones_tipos.nombre as tipo_sancion_nombre
				FROM		sga_sanciones
							LEFT JOIN sga_propuestas ON sga_sanciones.propuesta = sga_propuestas.propuesta,
							sga_sanciones_tipos,
							vw_personas
				WHERE		sga_sanciones.tipo_sancion = sga_sanciones_tipos.tipo_sancion AND
							sga_sanciones.persona = vw_personas.persona
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
     * Retorna Tipos de Sancion
     */
	function get_listado_tipos($where='')
	{
		if ($where) {
			
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_sanciones_tipos.tipo_sancion,
						sga_sanciones_tipos.nombre, 
						sga_sanciones_tipos.descripcion
				FROM 	sga_sanciones_tipos
				$where
				ORDER BY 2
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de tipo de sanción se puede usar o no.
    */
	function validar_nombre_tipo_sancion($nombre, $tipo_sancion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_sanciones_tipos.tipo_sancion) as cant
                FROM 	sga_sanciones_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_sanciones_tipos.nombre', $nombre).'
				';
		
		if (!is_null($tipo_sancion)) {
			$tipo_sancion = toba::db()->quote($tipo_sancion);
			$sql .= " AND sga_sanciones_tipos.tipo_sancion <> $tipo_sancion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
		
}

?>