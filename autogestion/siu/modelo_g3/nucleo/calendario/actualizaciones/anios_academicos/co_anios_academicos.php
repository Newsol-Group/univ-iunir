<?php
/**
 *
 *
 * @package Unidades de Gestión
 * @subpackage Calendario Académico 
 */
class co_anios_academicos
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "	SELECT 	sga_anios_academicos.anio_academico
					FROM 	sga_anios_academicos
							$where
					ORDER BY 1 DESC
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de años académicos según el filtro recibido
    */
	function get_anios_academicos($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_anios_academicos.anio_academico as valor,
						sga_anios_academicos.anio_academico as descr
  				FROM 	sga_anios_academicos
				$where
				ORDER BY 2 DESC
				;";	
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista de años académicos mayores al año académico dado.
    */
	function get_anios_academicos_mayores($anio)
	{
		$anio = toba::db()->quote($anio);
		$where = "sga_anios_academicos.anio_academico > $anio";
		return $this->get_anios_academicos($where);
	}
	
	/**
	 * Obtiene una lista de años académicos mayores o iguales al año académico dado.
    */
	function get_anios_academicos_mayores_o_iguales($anio)
	{
		$anio = toba::db()->quote($anio);
		$where = "sga_anios_academicos.anio_academico >= $anio ";
		return $this->get_anios_academicos($where);
	}

	/**
	 * Retorna los datos del año académico pasado como parámetro
	 */
	function get_datos_anio_academico($anio_academico)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$where = "sga_anios_academicos.anio_academico = $anio_academico";
		$datos = $this->get_listado($where);
		return $datos[0];
	}
    
    function solapa_fecha_responsable_academica($ra, $fecha_inicio, $fecha_fin, $id_fecha = null) 
    {
        $where = '';
        if ($id_fecha) {
            $where = ' AND sga_anios_academicos_fechas.id_fecha <> '.$id_fecha;
        }
        
        $sql = "
                SELECT	
                    (DATE '$fecha_inicio' BETWEEN sga_anios_academicos_fechas.fecha_inicio AND sga_anios_academicos_fechas.fecha_fin)
                    OR 
                    (DATE '$fecha_fin' BETWEEN  sga_anios_academicos_fechas.fecha_inicio AND  sga_anios_academicos_fechas.fecha_fin) as fecha_solapa
                FROM
                    sga_anios_academicos_fechas,
                    sga_anios_academicos_ra
                WHERE	
                    sga_anios_academicos_fechas.id_fecha = sga_anios_academicos_ra.id_fecha
                    AND sga_anios_academicos_ra.responsable_academica = $ra
                    $where
                ";
        $datos = guarani_db::consultar_fila($sql, null, false);
        return $datos['fecha_solapa'] ?? null;
    }
	
	/**
	 * Retorna el año académico actual según la propuesta del alumno y fecha actual. 
	 * Si no definieron rango de fechas, devuelve null
	 *
     * @param	int	$alumno	Alumno 
     * @return	int|null	Año academico actual o null si no definieron rango de fechas
	 */
	
	function get_anio_academico_actual($alumno)
	{
		
		$alumno = toba::db()->quote($alumno);
	
		$sql = "SELECT	sga_anios_academicos_fechas.anio_academico 
				FROM	sga_alumnos 
				JOIN    sga_propuestas_ra			ON sga_propuestas_ra.propuesta						= sga_alumnos.propuesta
				JOIN	sga_anios_academicos_ra		ON sga_anios_academicos_ra.responsable_academica	= sga_propuestas_ra.responsable_academica 
				JOIN	sga_anios_academicos_fechas	ON sga_anios_academicos_fechas.id_fecha				= sga_anios_academicos_ra.id_fecha
				WHERE	sga_alumnos.alumno = $alumno
				  AND   CURRENT_DATE BETWEEN sga_anios_academicos_fechas.fecha_inicio AND sga_anios_academicos_fechas.fecha_fin
				
			ORDER BY sga_anios_academicos_fechas.anio_academico DESC
				";

		$datos = guarani_db::consultar_fila($sql, null, false);
		return (isset($datos['anio_academico']))? $datos['anio_academico'] : null;
	}
}