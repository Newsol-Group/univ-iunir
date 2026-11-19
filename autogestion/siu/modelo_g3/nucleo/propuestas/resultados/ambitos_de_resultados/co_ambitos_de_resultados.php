<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class co_ambitos_de_resultados
{
	/**
	 * Obtiene una lista de ambitos de resultados según el filtro recibido
	 */
	function get_listado($where = '')
	{
		if ($where) {
			$where = 'WHERE ' . $where . ' ';
		}

		$sql = "	SELECT 	sga_resultados_ambitos.ambito,
							sga_resultados_ambitos.nombre,
							sga_resultados_ambitos.ambito_de_referencia,
							" . guarani_sql::get_case_si_no('sga_resultados_ambitos', 'ambito_de_referencia', 'ambito_de_referencia_descr') . " 
					FROM	sga_resultados_ambitos
							$where
					ORDER BY sga_resultados_ambitos.nombre
							;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene las Áreas Temáticas de un Ámbito de Resultado
	 */
	function get_listado_areas_tem($ambito)
	{
		if (isset($ambito)) {
			$ambito = toba::db()->quote($ambito);
		}

		$sql = "	SELECT 	DISTINCT sga_resultados_areas_tem.nombre as area_tematica_nombre,
							sga_resultados_areas_tem.area_tematica,
							sga_resultados_x_campo.ambito
					FROM	sga_resultados_areas_tem,
							sga_resultados_x_campo
					WHERE	sga_resultados_x_campo.ambito = $ambito AND
							sga_resultados_x_campo.area_tematica = sga_resultados_areas_tem.area_tematica
					ORDER BY nombre
							;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de un Ámbito de Resultado
	 */
	function get_datos_ambito_de_resultado($ambito_de_resultado)
	{
		$ambito_de_resultado = toba::db()->quote($ambito_de_resultado);
		$where = " sga_resultados_ambitos.ambito = $ambito_de_resultado ";
		$datos = current($this->get_listado($where));
		return $datos;
	}

	/**
	 * Valida si un nombre de ambito de resultados se puede usar o no.
	 */
	function validar_nombre_ambito_de_resultados($nombre, $ambito)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_resultados_ambitos.ambito) as cant
                FROM 	sga_resultados_ambitos
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_resultados_ambitos.nombre', $nombre) . '
				';

		if (!is_null($ambito)) {
			$ambito = toba::db()->quote($ambito);
			$sql .= " AND sga_resultados_ambitos.ambito <> $ambito ";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Obtiene las Áreas Temáticas asociadas a un Ámbito dado
	 */
	function get_areas_tematicas_asociadas($ambito)
	{
		if (isset($ambito)) {
			$ambito = toba::db()->quote($ambito);
		}

		$sql = "SELECT	sga_resultados_x_campo.resultado_campo
				FROM	sga_resultados_x_campo
				WHERE	sga_resultados_x_campo.ambito = $ambito
					";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los Certificados asociados a un Ámbito dado
	 */
	function get_certificados_asociados($ambito)
	{
		if (isset($ambito)) {
			$ambito = toba::db()->quote($ambito);
		}

		$sql = "SELECT	sga_resultados_certificados.certificado
				FROM	sga_resultados_certificados,
						sga_resultados_aprendizaje,
						sga_resultados_x_campo
				WHERE	sga_resultados_certificados.resultado = sga_resultados_aprendizaje.resultado AND
						sga_resultados_aprendizaje.resultado = sga_resultados_x_campo.resultado AND
						sga_resultados_x_campo.ambito = $ambito
					";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene las Actividades asociadas a un Ámbito dado
	 */
	function get_actividades_asociadas($ambito)
	{
		if (isset($ambito)) {
			$ambito = toba::db()->quote($ambito);
		}

		$sql = "SELECT	sga_resultados_actividades.elemento
				FROM	sga_resultados_actividades,
						sga_resultados_aprendizaje,
						sga_resultados_x_campo
				WHERE	sga_resultados_actividades.resultado = sga_resultados_aprendizaje.resultado AND
						sga_resultados_aprendizaje.resultado = sga_resultados_x_campo.resultado AND
						sga_resultados_x_campo.ambito = $ambito
					";
		return guarani_db::consultar($sql);
	}

}
?>