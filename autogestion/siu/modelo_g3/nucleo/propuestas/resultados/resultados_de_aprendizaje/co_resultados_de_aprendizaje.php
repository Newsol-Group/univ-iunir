<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class co_resultados_de_aprendizaje
{
	/**
        Obtiene una lista de resultados de aprendizaje según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
	    	$where = ' AND '.$where;
		}

		$sql = "	SELECT 	sga_resultados_aprendizaje.resultado,
							sga_resultados_aprendizaje.nombre as resultado_alcanzado,
							sga_resultados_aprendizaje.resultado_tipo,
							sga_resultados_aprendizaje.alcance,
							sga_resultados_tipos.descripcion as tipo_resultado_descripcion,
							sga_resultados_alcance.nombre as alcance_nombre
					FROM 	sga_resultados_aprendizaje,
							sga_resultados_tipos,
							sga_resultados_alcance
					WHERE	sga_resultados_aprendizaje.resultado_tipo = sga_resultados_tipos.resultado_tipo AND
							sga_resultados_aprendizaje.alcance = sga_resultados_alcance.alcance
							$where
					ORDER BY sga_resultados_aprendizaje.nombre;
		"; 
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene la lista de resultados de aprendizaje de tipo distinto de "Competencias"
	 * 
	 */
	function get_listado_no_competencia($where='')
	{
		if ($where) {
	    	$where = $where.' AND ';
		}
		$where .= "sga_resultados_tipos.resultado_tipo <> '".comunes::resultado_tipo_competencias."'";
		return $this->get_listado($where);
	}

	/**
     * Obtiene una lista de resultados de aprendizaje según el filtro recibido
     */
	function get_campos_carga_externa($resultado)
	{
		$resultado = toba::db()->quote($resultado);

		$sql = "	SELECT 	sga_resultados_aprendizaje.resultado,
							sga_resultados_aprendizaje.nombre as resultado_alcanzado,
							sga_resultados_alcance.nombre as alcance_nombre,
							sga_resultados_tipos.descripcion as tipo_resultado_descripcion
					FROM 	sga_resultados_aprendizaje,
							sga_resultados_alcance,
							sga_resultados_tipos
					WHERE	sga_resultados_aprendizaje.alcance = sga_resultados_alcance.alcance
							AND sga_resultados_aprendizaje.resultado_tipo = sga_resultados_tipos.resultado_tipo
							AND sga_resultados_aprendizaje.resultado = $resultado
					ORDER BY sga_resultados_aprendizaje.nombre;
		"; 
		return guarani_db::consultar($sql);
	}	
	
	/**
        Obtiene una lista de los tipos de resultados según el filtro recibido.
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_resultados_tipos.resultado_tipo,
						sga_resultados_tipos.descripcion
				FROM 	sga_resultados_tipos
				$where
				ORDER BY sga_resultados_tipos.descripcion;
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene la lista de los tipos de resultados distintos de "Competencias"
	 *
	 */
	function get_listado_tipos_no_competencia()
	{
		$where = "sga_resultados_tipos.resultado_tipo <> '".comunes::resultado_tipo_competencias."'";
		return $this->get_listado_tipos($where);
	}

	/**
        Obtiene una lista de los alcances de resultados según el filtro recibido.
    */
	function get_listado_alcances($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_resultados_alcance.alcance,
						sga_resultados_alcance.nombre
				FROM 	sga_resultados_alcance
				$where
				ORDER BY sga_resultados_alcance.nombre;
		";
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene una lista de nombres de resultados de aprendizaje según el filtro recibido
    */
	function get_resultados_aprendizaje_nombres($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_resultados_aprendizaje.resultado as valor,
						sga_resultados_aprendizaje.nombre as descr
  				FROM 	sga_resultados_aprendizaje
				$where
				ORDER BY sga_resultados_aprendizaje.nombre
				;";	
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de resultados de aprendizaje se puede usar o no.
    */	
	function validar_datos_resultado_de_aprendizaje($datos)
	{
		if (!isset($datos['nombre'])) {
			return false;
		}
		
		$resultado_alcanzado = toba::db()->quote($datos['nombre']);
		$resultado_tipo = toba::db()->quote($datos['resultado_tipo']);
		$alcance = toba::db()->quote($datos['alcance']);
		
		$sql = 'SELECT	count(sga_resultados_aprendizaje.resultado) as cant
                FROM 	sga_resultados_aprendizaje
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_resultados_aprendizaje.nombre', $resultado_alcanzado).'
				AND		'.guarani_sql::armar_condicion_compara_cadenas('sga_resultados_aprendizaje.resultado_tipo', $resultado_tipo).'
				AND		'.guarani_sql::armar_condicion_compara_cadenas('sga_resultados_aprendizaje.alcance', $alcance).'
				';
		
		if (!is_null($datos['resultado'])) {
			$resultado = toba::db()->quote($datos['resultado']);
			$sql .= " AND sga_resultados_aprendizaje.resultado <> $resultado ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
	 * Obtiene un listado de resultados asociados a un certificado específico
	 *
	 */
	function get_resultados_por_certificado($certificado, $where='')
	{
		$certificado = toba::db()->quote($certificado);
		
		if ($where) {
	    	$where = ' AND '.$where;
		}

		$sql = "	SELECT 	sga_resultados_aprendizaje.resultado,
							sga_resultados_aprendizaje.nombre as resultado_alcanzado,
							sga_resultados_aprendizaje.resultado_tipo,
							sga_resultados_aprendizaje.alcance,
							sga_resultados_tipos.descripcion as tipo_resultado_descripcion,
							sga_resultados_alcance.nombre as alcance_nombre
					FROM 	sga_competencias_x_alcance,
							sga_resultados_aprendizaje,
							sga_resultados_tipos,
							sga_resultados_alcance
					WHERE	sga_competencias_x_alcance.certificado = $certificado
							AND sga_competencias_x_alcance.resultado = sga_resultados_aprendizaje.resultado
							AND sga_resultados_aprendizaje.resultado_tipo = sga_resultados_tipos.resultado_tipo 
							AND	sga_resultados_aprendizaje.alcance = sga_resultados_alcance.alcance
							$where
					ORDER BY sga_resultados_aprendizaje.nombre;
		"; 
		
		return guarani_db::consultar($sql);
	}
	
}

?>