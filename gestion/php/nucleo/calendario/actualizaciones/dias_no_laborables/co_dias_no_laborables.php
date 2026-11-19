<?php
/**
 *	
 *
 * @package Calendario
 * @subpackage Días No Laborables
 */
class co_dias_no_laborables
{
	/**
	 * Obtiene una lista de los días no laborables según el filtro recibido
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where ";
		}

		$sql = "SELECT		sga_dias_no_laborables.fecha,
							sga_dias_no_laborables.descripcion,
                            sga_dias_no_laborables.dia_no_laborable,
                            COALESCE(sga_ubicaciones.nombre, 'Todas') as ubicacion
				FROM 		sga_dias_no_laborables
                LEFT JOIN   sga_ubicaciones
                ON          sga_ubicaciones.ubicacion = sga_dias_no_laborables.ubicacion                
                            $where                
				ORDER BY 	sga_dias_no_laborables.fecha
				";
		
		return guarani_db::consultar($sql);
	}

	function get_dias_no_laborables_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_ubicaciones', 'sga_ubicaciones');
			$where .= 	"
						-- Días no laborables sin ubicación (para todas).
						AND (sga_dias_no_laborables.ubicacion IS NULL OR
						-- Días no laborables por ubicación filtradas por perfil de datos.
						$where_gatillo)";
		}
		$sql = "SELECT		sga_dias_no_laborables.fecha,
							sga_dias_no_laborables.descripcion,
							sga_dias_no_laborables.dia_no_laborable,
							COALESCE(sga_ubicaciones.nombre, 'Todas') as ubicacion
				FROM		sga_dias_no_laborables
							LEFT JOIN sga_ubicaciones ON sga_dias_no_laborables.ubicacion = sga_ubicaciones.ubicacion
				WHERE		true
							$where
				ORDER BY 	sga_dias_no_laborables.fecha
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna true en caso de que el día dado como parámetro sea no laborable, y false en caso contrario.
	 * 
	 * @param date $dia
	 */
	function es_dia_no_laborable($dia, $dia_no_laborable = null)
	{
		$where = '';
		if (!is_null($dia_no_laborable)) {
			$where = ' AND dia_no_laborable <> '.$dia_no_laborable;
		}
		$dia = toba::db()->quote($dia);
		$sql = "SELECT 	sga_dias_no_laborables.fecha
				FROM 	sga_dias_no_laborables
				WHERE 	sga_dias_no_laborables.fecha = $dia
						$where
		";
		$datos = guarani_db::consultar_fila($sql);
		return !empty($datos);
	}
    
    /**
	 * Retorna true en caso de que el día dado como parámetro sea no laborable, y false en caso contrario.
	 * 
	 * @param date $dia
	 */
	function es_dia_no_laborable_ubicacion($dia, $ubicacion, $dia_no_laborable = null)
	{
        $where = '';
        //Se usa la pk para que no compare contra si misma.
        if (!is_null($dia_no_laborable)) {
            $where = ' AND dia_no_laborable <> '.$dia_no_laborable;
        }
		$dia = toba::db()->quote($dia);
		if (is_array($ubicacion)) {
			$filtro_ubicacion = guarani_sql::armar_condicion_in($ubicacion, 'sga_dias_no_laborables', 'ubicacion');
		} else {
			$ubicacion = toba::db()->quote($ubicacion);
			$filtro_ubicacion = "sga_dias_no_laborables.ubicacion = $ubicacion";
		}
		$sql = "SELECT 	sga_dias_no_laborables.fecha
	    		FROM 	sga_dias_no_laborables
	    		WHERE 	sga_dias_no_laborables.fecha = $dia AND
						-- Días no laborables sin ubicación (para todas).
						(sga_dias_no_laborables.ubicacion IS NULL OR
						-- Días no laborables para la ubicación.
                        $filtro_ubicacion)
                        $where
		";
		$datos = guarani_db::consultar_fila($sql);
		return !empty($datos);
	}
}
?>