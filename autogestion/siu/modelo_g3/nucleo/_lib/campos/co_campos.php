<?php
/**
 *
 *
 * @package Campos
 * @subpackage Campos
 */
class co_campos
{
	/**
	 * Retorna la lista de campos con sus datos asociados.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_lista_campos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
        $sql = "SELECT 		mce_campos.campo,
        					mce_campos.etiqueta,
        					mce_campos.tipo_de_dato,
        					mce_campos.descripcion,
        					mce_campos.longitud,
        					mce_campos.cant_decimales
  				FROM 		mce_campos
  				$where
        		ORDER BY 	mce_campos.etiqueta
  		";
        return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a un campo en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array código de campo o arreglo de código/s de campo/s.
    */
	function get_datos_campo($campo)
	{
		if (is_array($campo)) {
			$filtro = guarani_sql::armar_condicion_in($campo, 'mce_campos', 'campo');
		} else {
			$campo = toba::db()->quote($campo);
			$filtro = "mce_campos.campo = $campo";
		}
		return $this->get_lista_campos($filtro);
	}
}
?>