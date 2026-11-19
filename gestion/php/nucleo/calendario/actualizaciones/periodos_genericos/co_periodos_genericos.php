<?php
/**
 *	
 *
 * @package Calendario
 * @subpackage Períodos Genéricos
 */
class co_periodos_genericos
{
	/**
        Obtiene una lista de períodos genéricos según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = '	SELECT 	sga_periodos_genericos.periodo_generico,
							sga_periodos_genericos.nombre,
							sga_periodos_genericos.descripcion,
							sga_periodos_genericos.periodo_generico_tipo,
							sga_periodos_genericos.orden_en_su_tipo,
							sga_periodos_genericos.periodo_lectivo_tipo,
							sga_periodos_genericos_tipos.nombre as periodo_generico_tipo_nombre,
							'. guarani_sql::get_case_si_no('sga_periodos_genericos', 'activo', 'activo') ."
					FROM 	sga_periodos_genericos LEFT JOIN sga_periodos_genericos_tipos ON sga_periodos_genericos.periodo_generico_tipo = sga_periodos_genericos_tipos.periodo_generico_tipo
							$where
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los datos de un Período Genérico
    */
	function get_datos_periodo_generico($periodo_generico, $dato=null)
	{
		$periodo_generico = toba::db()->quote($periodo_generico);
		$where = " sga_periodos_genericos.periodo_generico = $periodo_generico ";
		$datos = current($this->get_listado($where));
		return isset($dato) ? $datos[$dato] : $datos;
	}
	
	/**
        Valida si un período genérico se puede usar o no
    */
	function validar_nombre_periodo_generico($nombre, $periodo_generico)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_periodos_genericos.periodo_generico) as cant
                FROM 	sga_periodos_genericos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_periodos_genericos.nombre', $nombre).'
				';
		
		if (!is_null($periodo_generico)) {
			$periodo_generico = toba::db()->quote($periodo_generico);
			$sql .= " AND sga_periodos_genericos.periodo_generico <> $periodo_generico ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	/*
	*	Retorna el listado de tipos de períodos genéricos
	*/
	function get_periodos_genericos_tipos()
	{
		$sql = "	SELECT 	sga_periodos_genericos_tipos.periodo_generico_tipo as valor,
							sga_periodos_genericos_tipos.nombre as descr
					FROM 	sga_periodos_genericos_tipos
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
   /*
	*	Retorna el listado de tipos de períodos genéricos que tienen periodos de inscripción 
	*/
	function get_periodos_genericos_tipos_con_periodos_inscripcion()
	{
		$si = toba::db()->quote(comunes::si);
		
		$sql = "	SELECT 	sga_periodos_genericos_tipos.periodo_generico_tipo as valor,
							sga_periodos_genericos_tipos.nombre as descr
					FROM 	sga_periodos_genericos_tipos
					WHERE	sga_periodos_genericos_tipos.tiene_periodos_inscripcion = $si
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/*
	*	Retorna el listado de períodos genéricos según el filtro recibido
	*/
	function get_periodos_genericos($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		
		$sql = "	SELECT 	sga_periodos_genericos.periodo_generico as valor,
							sga_periodos_genericos.nombre as descr
					FROM 	sga_periodos_genericos
					$where
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/*
	*	Retorna el listado de Períodos Genéricos de tipo Período Lectivo
	*/
	function get_periodos_genericos_tipo_per_lec()
	{
		$sql = "	SELECT 	sga_periodos_genericos.periodo_generico as valor,
							sga_periodos_genericos.nombre as descr
					FROM 	sga_periodos_genericos,
							sga_periodos_genericos_tipos
					WHERE	sga_periodos_genericos.periodo_generico_tipo		= sga_periodos_genericos_tipos.periodo_generico_tipo
					AND		sga_periodos_genericos_tipos.periodo_generico_tipo	= '" .periodo_generico::tipo_periodo_lectivo. "'
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
}
?>