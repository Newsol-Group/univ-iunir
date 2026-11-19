<?php
/**
 * Consultas sobre Condiciones
 *
 * @package Propuestas
 * @subpackage Plan
 */

class co_condiciones
{
	function get_condiciones_tipos_nombre($condicion_tipo='')
    {
    	$solo_una = true;
    	if (is_array($condicion_tipo)) {
			$where = 'WHERE '.guarani_sql::armar_condicion_in($condicion_tipo,'sga_condiciones_tipos', 'condicion_tipo');
			$solo_una = false;
		} else {
    		$condicion_tipo = toba::db()->quote($condicion_tipo);
    		$where = "WHERE sga_condiciones_tipos.condicion_tipo = $condicion_tipo ";
    	}
    	$sql = "SELECT 	sga_condiciones_tipos.condicion_tipo,
    					sga_condiciones_tipos.nombre 
               	FROM 	sga_condiciones_tipos
               	$where	;";
		$datos = guarani_db::consultar($sql);
		if ($solo_una) {
			return $datos[0];
        } else {
        	return $datos;
        }	
    }
    
	function get_condiciones_tipos($aplica=null)
    {
    	$where = '';
    	if (isset ($aplica)){
    		$aplica = toba::db()->quote($aplica);
    		$where = "WHERE sga_condiciones_tipos.condicion_tipo = $aplica "; 
    	}
    	$sql = "SELECT sga_condiciones_tipos.condicion_tipo,
    	               sga_condiciones_tipos.nombre,
                       sga_condiciones_tipos.descripcion,
                       sga_condiciones_tipos.icono
                FROM   sga_condiciones_tipos 
                $where 
                		;"; 
		return guarani_db::consultar($sql);
    }
    
    function get_condiciones_correlativas()
    {
		$datos1 = toba::consulta_php('co_condiciones')->get_condiciones_tipos(condicion::tipo_rendir);
		$datos2 = toba::consulta_php('co_condiciones')->get_condiciones_tipos(condicion::tipo_cursar);
		return array_merge($datos1, $datos2);
    }	
    
    function get_condiciones_certificados()
    {
		$datos = toba::consulta_php('co_condiciones')->get_condiciones_tipos(condicion::tipo_certificado);
		return $datos;
    }

    /**
     * Retorna la condición de cumplimiento correspondiente a un certificado y un plan-versión.
     *
     * @param int $certificado código de certificado.
     * @param int $plan_version código de plan-versión.
     * @return array
     */
	function get_condicion_certificado_plan_version($certificado, $plan_version)
	{
		$certificado = toba::db()->quote($certificado);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_condiciones.condicion,
						sga_certificados.nombre
				FROM	sga_condiciones,
						sga_certificados
				WHERE	sga_condiciones.entidad = sga_certificados.entidad AND
						sga_condiciones.condicion_tipo = '".condicion::tipo_certificado."' AND
						sga_certificados.certificado = $certificado AND
						sga_condiciones.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql, null, false);
	}

    /**
     * Retorna las opciones de cumplimiento correspondientes a un certificado y plan-versión.
     *
     * @param int $certificado código de certificado.
     * @param int $plan_version código de plan-versión.
     * @return array
     */
	function get_opciones_certificado_plan_version($certificado, $plan_version)
	{
		$certificado = toba::db()->quote($certificado);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT		sga_condiciones_grupos.grupo_condicion,
							sga_condiciones_grupos.orden
				FROM		sga_condiciones,
							sga_certificados,
							sga_condiciones_grupos
				WHERE		sga_condiciones.entidad = sga_certificados.entidad AND
							sga_condiciones.condicion = sga_condiciones_grupos.condicion AND
							sga_condiciones.condicion_tipo = '".condicion::tipo_certificado."' AND
							sga_certificados.certificado = $certificado AND
							sga_condiciones.plan_version = $plan_version
				ORDER BY	sga_condiciones_grupos.orden
		";
		return guarani_db::consultar($sql);
	}

    /**
     * Retorna las opciones de cumplimiento correspondientes a un elemento, plan-versión y tipo.
     *
     * @param int $elemento código de elemento.
     * @param int $plan_version código de plan-versión.
     * @param int $tipo_condicion código de tipo de condición (correlativas para cursar o rendir).
     * @return array
     */
	function get_opciones_elemento_plan_version_tipo($elemento, $plan_version, $tipo_condicion)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$tipo_condicion = toba::db()->quote($tipo_condicion);
		$sql = "SELECT		sga_condiciones_grupos.grupo_condicion,
							sga_condiciones_grupos.orden
				FROM		sga_condiciones,
							sga_elementos,
							sga_condiciones_grupos
				WHERE		sga_condiciones.entidad = sga_elementos.entidad AND
							sga_condiciones.condicion = sga_condiciones_grupos.condicion AND
							sga_condiciones.condicion_tipo = $tipo_condicion AND
							sga_elementos.elemento = $elemento AND
							sga_condiciones.plan_version = $plan_version
				ORDER BY	sga_condiciones_grupos.orden
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de opciones asociadas a una condición de cumplimiento.
	 *
	 * @param int $condicion código de condición.
	 * @return array
	 */
	function get_opciones_condicion($condicion)
	{
		$condicion = toba::db()->quote($condicion);
		$sql = "SELECT		sga_condiciones_grupos.grupo_condicion,
							sga_condiciones_grupos.orden
				FROM		sga_condiciones_grupos
				WHERE		sga_condiciones_grupos.condicion = $condicion
				ORDER BY	sga_condiciones_grupos.orden
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de actividades, módulos y requisitos asociados a una opción de cumplimiento.
	 *
	 * @param int $opcion código de opción.
	 * @return array
	 */
	function get_requisitos_opcion($opcion)
	{
		$opcion = toba::db()->quote($opcion);
		$sql = "SELECT		sga_condiciones_requisitos.requisito_condicion,
							sga_condiciones_requisitos.orden,
							sga_condiciones_requisitos.operador_not,
							sga_condiciones_requisitos.tipo,
							sga_condiciones_requisitos.requisito,
							sga_requisitos.nombre,
							sga_condiciones_requisitos.entidad,
							sga_elementos.elemento,
							sga_elementos.entidad_subtipo,
							sga_g3entidades_subtipos.entidad_tipo,
							sga_condiciones_requisitos.estado,
							sga_condiciones_requisitos.regla,
							sga_reglas.php_clase,
							sga_condiciones_requisitos.parametros,
							COALESCE(sga_elementos.nombre, sga_requisitos.nombre) as requisito_nombre
				FROM		sga_condiciones_requisitos
							LEFT OUTER JOIN sga_requisitos ON sga_condiciones_requisitos.requisito = sga_requisitos.requisito
							LEFT OUTER JOIN sga_elementos ON sga_condiciones_requisitos.entidad = sga_elementos.entidad
							LEFT OUTER JOIN sga_g3entidades_subtipos ON sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo,
							sga_reglas
				WHERE		sga_condiciones_requisitos.grupo_condicion = $opcion AND
							sga_condiciones_requisitos.regla = sga_reglas.regla
				ORDER BY	sga_condiciones_requisitos.orden
		";
		return guarani_db::consultar($sql, null, false);
	}
}
?>