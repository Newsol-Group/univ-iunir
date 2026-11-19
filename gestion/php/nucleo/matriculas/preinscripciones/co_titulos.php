<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Preinscripciones
 */
class co_titulos
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';			        
		}
		$sql = "
				SELECT
					mdp_titulos.titulo,
					mdp_titulos.nombre as titulo_nombre,
					mdp_titulos.titulo_tipo,
					mdp_titulos.categoria_coneau,
					mdp_titulos_tipos.nombre as titulos_tipo_nombre,
					mdp_titulos_tipos.titulo_tipo
				FROM
					mdp_titulos,
					mdp_titulos_tipos
				WHERE
					mdp_titulos.titulo_tipo = mdp_titulos_tipos.titulo_tipo
					$where
				ORDER BY
					mdp_titulos.nombre, mdp_titulos_tipos.nombre
				";
		return guarani_db::consultar($sql);
	}
	
	function get_tipos_titulos()
	{
		$sql = "
				SELECT 
					titulo_tipo as valor, 
					nombre as descr, 
					nombre_abreviado, 
					tiempo_trabajo_final
				FROM 
					mdp_titulos_tipos					
				";		
		return guarani_db::consultar($sql);
	}
	
	function get_categoria_coneau()
	{
		$categoria = array (
							array ('valor' => 'A', 'descr' => 'A'),
							array ('valor' => 'B', 'descr' => 'B'),
							array ('valor' => 'C', 'descr' => 'C'),
							array ('valor' => 'T', 'descr' => 'En trámite'),
							array ('valor' => 'N', 'descr' => 'Ninguna'),
					);
		return $categoria;
					
	}
	
	function existe_estudio_titulo($titulo)
	{
		$sql = "
				SELECT
					1
				FROM
					mdp_datos_estudios
				WHERE
					titulo = $titulo
				";		
		$datos = guarani_db::consultar($sql);
		if (empty($datos)) {
			return false;
		}
		return true;
	}
	
	/*
	 * Se usa en 'Reporte de Alumnos"
	 */
	function get_titulos_propuesta($propuesta, $filtro = "")
	{
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$where = " WHERE sga_planes.propuesta = $propuesta";
		}
		if (isset($filtro) && trim($filtro) != '') {
			if (isset($where)) {
				$where .= ' AND ' . $filtro;
			} else {
				$where = ' WHERE ' . $filtro;
			}
		}
		$sql = "SELECT DISTINCT sga_certificados.certificado, sga_certificados.nombre
				FROM sga_planes_certificados
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_planes_certificados.plan_version
				JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN sga_certificados on sga_certificados.certificado = sga_planes_certificados.certificado
				$where
				ORDER BY sga_certificados.nombre
				;";
		
		return guarani_db::consultar($sql);
	}
	
}

?>