<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class co_categorias_actividades
{
	/**
	 * Obtiene un listado de categorías de actividades
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}

		$sql = "SELECT 
					sga_categorias_actividades.categoria,
					sga_categorias_actividades.nombre,
					sga_categorias_actividades.descripcion
				FROM 
					sga_categorias_actividades
				$where
				ORDER BY
					sga_categorias_actividades.nombre
				"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de categoría de actividades se puede usar o no.
     */
	function validar_nombre_categoria($nombre, $categoria)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_categorias_actividades.categoria) as cant
                FROM 	sga_categorias_actividades
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_categorias_actividades.nombre', $nombre).'
				';
		
		if (!is_null($categoria)) {
			$categoria = toba::db()->quote($categoria);
			$sql .= " AND sga_categorias_actividades.categoria <> $categoria ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
	 * Determina si una categoría dada está siendo referenciada por otra tabla.
	 *
	 */
	function categoria_en_uso($categoria) 
	{
		$categoria = toba::db()->quote($categoria);
		
		$sql = "SELECT 
					COUNT (sga_elementos_categoria.elemento) AS cant
				FROM 
					sga_elementos_categoria
				WHERE
					sga_elementos_categoria.categoria = $categoria";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] > 0);
	}
	
	/**
	 * Obtiene un listado de categorías de actividades para llenado de combo
	 */
	function get_categorias()
	{
		$sql = "SELECT 
					sga_categorias_actividades.categoria as valor,
					sga_categorias_actividades.nombre as descr
				FROM 
					sga_categorias_actividades
				ORDER BY
					sga_categorias_actividades.nombre
				"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de los valores asociados a una categoría de actividad
	 */
	function get_valores_categoria($categoria)
	{
		$categoria = toba::db()->quote($categoria);

		$sql = "SELECT 
					sga_categorias_actividades_det.valor,
					sga_categorias_actividades_det.descripcion as descr
				FROM 
					sga_categorias_actividades_det
				WHERE
					sga_categorias_actividades_det.categoria = $categoria
				ORDER BY
					sga_categorias_actividades_det.valor
				"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de los valores asociados a una categoría de actividad.
	 * En caso de que la descripción del valor sea nula se mostrará la cadena que representa al valor propiamente dicho.
	 */
	function get_combo_valores_categoria($categoria)
	{
		$categoria = toba::db()->quote($categoria);

		$sql = "SELECT 
					sga_categorias_actividades_det.valor,
					COALESCE(sga_categorias_actividades_det.descripcion, sga_categorias_actividades_det.valor) as descr
				FROM 
					sga_categorias_actividades_det
				WHERE
					sga_categorias_actividades_det.categoria = $categoria
				ORDER BY
					sga_categorias_actividades_det.valor
				"; 
  				 
		return guarani_db::consultar($sql);
	}
}
?>