<?php
/**
 * Consultas sobre Correlativas
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */

class co_correlativas
{
    
    function get_condiciones_tipos_nombre($condicion_tipo)
    {
    	return toba::consulta_php('co_condiciones')->get_condiciones_tipos_nombre($condicion_tipo);
    }	
    
    function get_condiciones_tipos()
    {
		$datos1 = toba::consulta_php('co_condiciones')->get_condiciones_tipos(condicion::tipo_rendir);
		$datos2 = toba::consulta_php('co_condiciones')->get_condiciones_tipos(condicion::tipo_cursar);
		return array_merge($datos1, $datos2);
    }	
    

    /**
     * Elementos de un Plan Version. Por cada elemento verifica si tiene requisitos asignados
     * 
     * @param $plan_version
     * @return Arrya de elementos del plan
     */
	function get_correlativas_plan_version($plan_version, $where='')
	{	
		if ($where) {
			$where = " AND $where";
		}
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	get_plan_contenido.elemento,
						sga_elementos.entidad,
						get_plan_contenido.codigo,
						get_plan_contenido.entidad_tipo,
						get_plan_contenido.entidad_subtipo,
						get_plan_contenido.entidad_clase_gui as elemento_clase_gui,
						get_plan_contenido.elemento_padre,
						get_plan_contenido.nombre,
						get_plan_contenido.elemento_revision,
						get_plan_contenido.elemento_comp,
						COALESCE(get_plan_contenido.orden, -1) as orden,
						(	SELECT 	count(*) 
							FROM  	sga_condiciones
							WHERE	sga_condiciones.plan_version = $plan_version
							AND		sga_condiciones.entidad = sga_elementos.entidad
							AND		sga_condiciones.condicion_tipo = '".condicion::tipo_rendir."') as cond_aprobada,
						(	SELECT 	count(*) 
							FROM  	sga_condiciones
							WHERE	sga_condiciones.plan_version = $plan_version
							AND		sga_condiciones.entidad = sga_elementos.entidad
							AND		sga_condiciones.condicion_tipo = '".condicion::tipo_cursar."') as cond_cursada
				FROM	get_plan_contenido($plan_version),
						sga_elementos
				WHERE	get_plan_contenido.elemento = sga_elementos.elemento
						$where
		";			  
		$datos = guarani_db::consultar($sql, null, false);
		if (!isset($datos)) {
			throw new guarani_error("No se pudo recuperar datos del plan_version [$plan_version]");
		}
		return $datos;
	}

	/**
	 * Recupera los requisitos (Actividades y Materias Genericas) que son de tipo 
	 * Elemento 'E', que forman parte del plan version
	 * 
	 * 
	 * @param $plan_version
	 * @return unknown_type
	 */
	 
	function get_correlativas_actividades_plan_version($plan_version, $where='')
	{	
		if ($where) $where = ' and '.$where.' ';
		$planv = toba::db()->quote($plan_version);
		$sql = "SELECT  DISTINCT 
		                get_plan_contenido.elemento,
		                '' as codigo,
		                sga_g3entidades.entidad,
						get_plan_contenido.entidad_tipo,
						get_plan_contenido.entidad_subtipo,
						get_plan_contenido.nombre
 				FROM	get_plan_contenido ($planv), 
 						sga_g3entidades 
 				WHERE   get_plan_contenido.entidad = sga_g3entidades.entidad  $where 
 				ORDER BY get_plan_contenido.nombre
				;";
			$datos = guarani_db::consultar($sql);
		if(!isset($datos)) {
			throw new guarani_error("No se pudo recuperar datos del plan_version [$plan_version] ");
		}
		return $datos;
	}
	
	
	/**
	 * Recupera los requisitos (Correlativas especiales) que son de tipo Algoritmo 'A' 
	 * 
	 * @param $plan_version
	 * @return unknown_type
	 */
	function get_requisitos_especiales_plan_version($plan_version, $where='')
	{
		//TODO constantes
		//TODO cambiar nombre y sacar parametro
        if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT	sga_requisitos.requisito,
						sga_requisitos.codigo,
  						sga_requisitos.nombre,
  						sga_requisitos.requisito_tipo,
  						sga_requisitos.requisito_subordinado_de,
  						sga_requisitos.observaciones,
  						sga_requisitos.elemento,
  						sga_requisitos.certificado,
  						sga_requisitos.certificado_digital,
  						sga_requisitos.algoritmo
				FROM sga_requisitos
				WHERE sga_requisitos.requisito_tipo = 'A'
                $where";

		$datos = guarani_db::consultar($sql);
		if(!isset($datos)) {
			throw new guarani_error("No se pudieron recuperar los requisitos especiales.");
		}
		return $datos;
	}

	function get_elementos_tienen_correlativa_a ($elemento,$plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	DISTINCT sga_condiciones.entidad,
						vw_elementos.codigo,
						sga_elementos_plan.nombre
				FROM	sga_elementos,
						sga_condiciones_requisitos,
						sga_condiciones_grupos,
						sga_condiciones,
						vw_elementos,
						sga_elementos_plan
				WHERE 	sga_elementos.elemento = $elemento
				AND		sga_condiciones_requisitos.entidad = sga_elementos.entidad
				AND		sga_condiciones_grupos.grupo_condicion = sga_condiciones_requisitos.grupo_condicion
				AND		sga_condiciones_grupos.condicion = sga_condiciones.condicion
				AND 	sga_condiciones.plan_version = $plan_version
				AND		vw_elementos.entidad  = sga_condiciones.entidad
				AND 	sga_elementos_plan.plan_version = $plan_version
				AND		sga_elementos_plan.elemento_revision = vw_elementos.elemento_revision
				";

		$datos = guarani_db::consultar($sql);
		if(!isset($datos)) {
			//TODO ver error
			throw new guarani_error("Error interno, get_elementos");
		}
		return $datos;
	}

	
	function get_entidades_tienen_condiciones($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	DISTINCT sga_condiciones.entidad
				FROM	sga_condiciones
				WHERE	sga_condiciones.plan_version = $plan_version
				AND		sga_condiciones.condicion_tipo IN (".implode(',',toba::db()->quote(condicion::get_condiciones_correlativas())).")
				";
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'entidad');
	}

}
?>