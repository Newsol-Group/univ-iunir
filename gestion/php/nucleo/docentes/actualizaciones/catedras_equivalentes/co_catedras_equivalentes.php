<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class co_catedras_equivalentes
{
	function get_catedras_equivalentes_perfil_de_datos($where='')
	{
		if ($where) {
			$where = "AND $where";
		}
		$where_gatillo = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_catedras_actividades', 'sga_catedras_actividades');
		}
		$sql = "SELECT		DISTINCT sga_catedras.catedra,
							sga_catedras.nombre as nombre_original,
							sga_catedras.catedra_tipo as tipo_original,
							catedra_equivalente.catedra as catedra_equivalente,
							catedra_equivalente.nombre as nombre_equivalente,
							catedra_equivalente.catedra_tipo as tipo_equivalente,
							sga_catedras_tipos.nombre as cat_orig_nombre_tipo,
							catedra_tipo_equivalente.nombre as cat_equiv_nombre_tipo,
							".guarani_sql::get_case('sga_catedras', 'estado', 'cat_orig_estado')." 
				FROM		sga_catedras
							LEFT JOIN sga_catedras_equivalentes ON sga_catedras.catedra = sga_catedras_equivalentes.catedra
							LEFT JOIN sga_catedras as catedra_equivalente ON sga_catedras_equivalentes.catedra_equivalente = catedra_equivalente.catedra
							LEFT JOIN sga_catedras_actividades ON sga_catedras.catedra = sga_catedras_actividades.catedra 
							LEFT JOIN (sga_catedras_docentes LEFT JOIN (sga_docentes LEFT JOIN mdp_personas ON sga_docentes.persona = mdp_personas.persona) ON sga_catedras_docentes.docente = sga_docentes.docente) ON sga_catedras.catedra = sga_catedras_docentes.catedra
							LEFT JOIN sga_catedras_tipos ON sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo
							LEFT JOIN sga_catedras_tipos as catedra_tipo_equivalente ON catedra_equivalente.catedra_tipo = catedra_tipo_equivalente.catedra_tipo
				WHERE		-- Cátedras sin actividades.
							(sga_catedras_actividades.elemento IS NULL OR
							-- Cátedras con actividades filtradas por perfil de datos.
							$where_gatillo)
							$where
				ORDER BY	sga_catedras.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
        Obtiene el Tipo de una Cátedra
    */
	function get_catedra_tipo_nombre($catedra)
	{
		$catedra = toba::db()->quote($catedra);
		$sql = "SELECT	sga_catedras_tipos.nombre
  				FROM 	sga_catedras,
  						sga_catedras_tipos
  				WHERE 	sga_catedras.catedra = $catedra AND
						sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene el Estado de una Cátedra
    */
	function get_catedra_estado($catedra)
	{
		$catedra = toba::db()->quote($catedra);
		$sql = "SELECT	".guarani_sql::get_case('sga_catedras','estado','catedra_estado')." 
  				FROM 	sga_catedras
  				WHERE 	sga_catedras.catedra = $catedra
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene las Actividades de una Cátedra
    */
	function get_catedra_actividades($catedra)
	{
		$catedra = toba::db()->quote($catedra);
		$sql = "SELECT	sga_elementos.nombre as nombre_actividad,
						sga_elementos.codigo as codigo_actividad,
						sga_g3entidades_subtipos.nombre as tipo_actividad
  				FROM 	sga_catedras,
  						sga_catedras_actividades,
  						sga_elementos,
  						sga_g3entidades_subtipos
  				WHERE 	sga_catedras.catedra = $catedra AND
  						sga_catedras.catedra = sga_catedras_actividades.catedra AND
  						sga_catedras_actividades.elemento = sga_elementos.elemento AND
  						sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los Docentes de una Cátedra
    */
	function get_catedra_docentes($catedra)
	{
		$catedra = toba::db()->quote($catedra);
		$sql = "SELECT	sga_docentes.legajo as legajo_docente,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) || ' ' ||  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as nombre_docente,
						sga_docentes_responsabilidades.nombre as responsabilidad_docente
				FROM 	sga_catedras,
						sga_catedras_docentes,
						sga_docentes,
						mdp_personas,
						sga_docentes_responsabilidades
				WHERE 	sga_catedras.catedra = $catedra AND
						sga_catedras.catedra = sga_catedras_docentes.catedra AND
						sga_catedras_docentes.docente = sga_docentes.docente AND
						sga_docentes.persona = mdp_personas.persona AND
						sga_catedras_docentes.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene la lista de todas las Cátedras Equivalentes
    */
	function get_catedras_equivalentes($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		sga_catedras_equivalentes.catedra,
							sga_catedras_equivalentes.catedra_equivalente
				FROM		sga_catedras_equivalentes
				$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna true en caso de que una cátedra dada tenga definida una cátedra equivalente, y false en caso contrario
    */
	function tiene_catedra_equivalente($catedra)
	{
		$filtro = "sga_catedras_equivalentes.catedra = $catedra";
		$datos_catedra = $this->get_catedras_equivalentes($filtro);
    	if (empty($datos_catedra)) {
    		return false;
    	}
    	return true;
	}
	
}

?>