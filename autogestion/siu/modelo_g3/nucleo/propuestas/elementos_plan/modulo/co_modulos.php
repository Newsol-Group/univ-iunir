<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class co_modulos
{
	function get_listado($where='')
	{
		if ($where) {
			$where = " WHERE $where ";
		}

		$sql = "SELECT 		vw_modulos.elemento,
							vw_modulos.codigo,
							vw_modulos.nombre,
							vw_modulos.entidad_subtipo_nombre
							
				FROM 		vw_modulos
				
				$where
				
				ORDER BY	vw_modulos.codigo
				";
		
		return guarani_db::consultar($sql);
	}
    
	/**
	 * Retorna el nombre de un módulo como un string
     */
	function get_nombre_modulo($elemento)
	{
		$filtro = "vw_modulos.elemento = $elemento";
		$datos = $this->get_listado($filtro);
		
		if (!empty($datos)) {
			return $datos[0]['nombre'];
		}
	}
	
	 /**
	 * Retorna las opciones de origen de créditos de un módulo
     *
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                )
    */
	//TODO esta funcion esta en co_elementos get_elementos_origen_creditos
    function get_modulos_origen_creditos()
    {
    	
    	$sql = "SELECT sga_elementos_origen_creditos.origen_creditos as valor,
    				   sga_elementos_origen_creditos.nombre as descr 
  				FROM  sga_elementos_origen_creditos";
		return guarani_db::consultar($sql);
    }

	/**
	* Retorna las opciones de formas de aprobacion de un módulo
     *
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                )        	
    */
    function get_modulos_formas_aprob()
    {
        $sql = "SELECT sga_reglas.regla as valor,
                       sga_reglas.nombre as descr 
  				FROM  sga_reglas
  				WHERE sga_reglas.regla_tipo = " . regla::tipo_cumplimiento_modulos;
       
        return guarani_db::consultar($sql);
    }

	/**
	* Retorna los algoritmos que pueden usarse para determinar la aprobación de un modulo
	 *
     * @params String $filtro Si existe filtra los campos varchar de la consulta
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                )        	
    */
    function get_modulos_algoritmos($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
        $sql = "SELECT 	sga_reglas.regla as valor, 
        				sga_reglas.nombre || '/' || sga_reglas.php_clase as descr 
  				FROM 	sga_reglas
  				WHERE 	sga_reglas.nombre ILIKE $filtro
  				 OR 	sga_reglas.php_clase ILIKE $filtro
  				 OR 	sga_reglas.descripcion ILIKE $filtro ";
       
        return guarani_db::consultar($sql);
    }
    
}
?>