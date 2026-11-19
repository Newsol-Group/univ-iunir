<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class co_requisitos_x_operacion
{
	/**
	 * Obtiene un listado de Operación con Acciones asociadas
	 * según filtro especificado
	 *
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		$schema_toba = toba::instancia()->get_db()->get_schema();
		$sql = "SELECT 	sga_acciones_operacion.operacion,
						$schema_toba.apex_item.nombre AS operacion_nombre,
						sga_acciones_operacion.accion,
						sga_acciones.nombre AS accion_nombre,
						sga_acciones.descripcion AS accion_descripcion,
						'(' || sga_acciones_operacion.operacion || ') ' || $schema_toba.apex_item.nombre AS operacion_codigo_y_nombre
				FROM 	sga_acciones_operacion,
						sga_acciones,
						$schema_toba.apex_item
				WHERE	sga_acciones_operacion.accion = sga_acciones.accion AND
						$schema_toba.apex_item.parametro_a = sga_acciones_operacion.operacion
					    $where
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 *  Obtiene los requisitos asociados a acciones de una operacion dada
	 *
	 * @param <type> $operacion
	 * @return <type>
	 */

	function get_requisitos_x_accion_operacion($filtro='')
	{
		$where = '';
		if ($filtro) {
			$where = 'WHERE '.$filtro.' ';
		}
		$sql = "SELECT  DISTINCT 
						CASE
							WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_requisito . "'
								THEN sga_requisitos_x_accion.requisito
							WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_entidad_estado . "'
								THEN sga_requisitos_x_accion.entidad
						END as id,
						sga_requisitos_x_accion.tipo,
						CASE
							WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_requisito . "'
								THEN sga_requisitos.nombre
							WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_entidad_estado . "'
								THEN NULL
						END as requisito_nombre,
						sga_requisitos_x_accion.estado,
						sga_estados.nombre AS estado_nombre,
						sga_requisitos_x_accion.parametros,
						sga_requisitos_x_accion.operador_not
				FROM 	sga_acciones_operacion
						JOIN sga_requisitos_grupos		ON (sga_requisitos_grupos.accion			= sga_acciones_operacion.accion)
						JOIN sga_requisitos_x_accion 	ON (sga_requisitos_x_accion.grupo_requisito	= sga_requisitos_grupos.grupo_requisito)
						LEFT JOIN sga_requisitos		ON (sga_requisitos.requisito				= sga_requisitos_x_accion.requisito)
						LEFT JOIN sga_elementos 		ON (sga_elementos.entidad					= sga_requisitos_x_accion.entidad)
						LEFT JOIN sga_estados			ON (sga_requisitos_x_accion.estado			= sga_estados.estado)
				$where
		";
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_requisitos_grupos'));
		$datos = guarani_db::consultar($sql, null, false);

		/**
		 * Se recorren los registros obtenidos para poder recuperar los nombres de los requisitos por acción de tipo
		 * entidad. Para esto necesita recuperarse el subtipo de cada entidad y la tabla correspondiente al mismo.
		 */
		foreach ($datos as $key=>$req_accion) {
			if ($req_accion['tipo'] == requisito::comp_entidad_estado) {
				$datos[$key]['requisito_nombre'] = toba::consulta_php('co_g3entidades')->get_nombre_entidad($req_accion['id']);
			}
		}
		return $datos;
	}

	function get_grupos_de_requisito($parametros, $operacion, $devolver_sql=false)
	{
		$operacion	= toba::db()->quote($operacion);
		$id			= toba::db()->quote($parametros['id']);
		$tipo		= toba::db()->quote($parametros['tipo']);
		$estado		= toba::db()->quote($parametros['estado']);
		$param		= toba::db()->quote($parametros['parametros']);
		$operador_not	= toba::db()->quote($parametros['operador_not']);
		
        $where = ''; 
		
		if ($tipo == "'".requisito::comp_requisito."'"){
			$where .= " AND sga_requisitos_x_accion.requisito = $id";
		}else {
			$where .= " AND sga_requisitos_x_accion.entidad = $id";
		}
		if (isset($estado) && $estado != "''"){	
			$where .= " AND sga_requisitos_x_accion.estado = $estado";
		}else {
			$where .= " AND sga_requisitos_x_accion.estado IS NULL";
		}
		if (isset($param) && $param != "''"){
			$where .= " AND sga_requisitos_x_accion.parametros = $param";
		} else {
			$where .= " AND sga_requisitos_x_accion.parametros IS NULL";
		}
		if (isset($operador_not)){
			$where .= " AND sga_requisitos_x_accion.operador_not = $operador_not";
		} 	
		
		$sql = "SELECT	sga_requisitos_conf_x_oper.operacion,
						sga_requisitos_conf_x_oper.interfaz,
						sga_requisitos_conf_x_oper.requisito_accion,
						sga_requisitos_conf_x_oper.actua_como,
						sga_requisitos_conf_x_oper.activo,
						sga_requisitos_x_accion.grupo_requisito
				FROM	sga_requisitos_conf_x_oper
				        JOIN sga_requisitos_x_accion ON (sga_requisitos_x_accion.requisito_accion = sga_requisitos_conf_x_oper.requisito_accion)
				WHERE 	sga_requisitos_x_accion.tipo = $tipo
				AND		sga_requisitos_conf_x_oper.operacion = $operacion
				$where 
		       ";
		
		if ($devolver_sql) {
			return toba::perfil_de_datos()->filtrar($sql);
		}

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Listado de opciones del combo comportamiento
	 *
	 */
	function get_opciones_estado()
	{
		return array(	array('valor' => 'S', 'descr' => 'Activo'), 
						array('valor' => 'N', 'descr' => 'Inactivo'));
	}
	
	function get_datos_requisitos_grupo($requisito_accion)
	{
		$requisito_accion = toba::db()->quote($requisito_accion);
		$where = "sga_requisitos_x_accion.requisito_accion = $requisito_accion";
		$sql = "SELECT 	sga_requisitos_grupos.grupo_requisito,
						sga_requisitos_grupos.accion,
						sga_requisitos_grupos.entidad_subtipo,
						sga_requisitos_grupos.entidad
				FROM	sga_requisitos_x_accion
				JOIN	sga_requisitos_grupos ON (sga_requisitos_grupos.grupo_requisito = sga_requisitos_x_accion.grupo_requisito)
				WHERE	sga_requisitos_x_accion.requisito_accion = $requisito_accion
				";
		
		return guarani_db::consultar_fila($sql);
	}
	
	function get_listado_reporte_req_x_oper($where='')
	{
		$schema_toba = toba::instancia()->get_db()->get_schema();
		
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$sql = "SELECT 
					-- Cortes de control
					'Operación: ' || $schema_toba.apex_item.parametro_a || ' - ' || $schema_toba.apex_item.nombre as operacion_codigo_y_nombre,
					$schema_toba.apex_item.parametro_a as operacion_id,
					CASE
						WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_requisito . "'
							THEN sga_requisitos_x_accion.requisito
						WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_entidad_estado . "'
							THEN sga_requisitos_x_accion.entidad
					END as requisito_id,
					CASE
						WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_requisito . "'
							THEN sga_requisitos.nombre
						WHEN sga_requisitos_x_accion.tipo = '" . requisito::comp_entidad_estado . "'
							THEN NULL
					END as requisito_nombre,
						
				    -- Datos del reporte
					acc_interfaces.nombre as interfaz_nombre,
					sga_requisitos_conf_x_oper.actua_como, -- Comportamiento
				   	sga_requisitos_conf_x_oper.activo,
				   	-- 
				   	sga_requisitos_x_accion.requisito_accion,
					sga_requisitos_x_accion.parametros,
					sga_requisitos_x_accion.operador_not,
					sga_requisitos_x_accion.tipo,
					sga_requisitos_x_accion.estado,
					sga_estados.nombre AS estado_nombre,
					--
				   	sga_requisitos_grupos.grupo_requisito,
					sga_requisitos_grupos.accion,
					sga_requisitos_grupos.entidad_subtipo,
					sga_requisitos_grupos.entidad
				
				FROM 
					sga_requisitos_conf_x_oper,
				    acc_interfaces, 
				    $schema_toba.apex_item,
				    sga_requisitos_x_accion
					LEFT JOIN sga_requisitos	ON (sga_requisitos.requisito		= sga_requisitos_x_accion.requisito)
					LEFT JOIN sga_estados		ON (sga_requisitos_x_accion.estado	= sga_estados.estado)
				    JOIN sga_requisitos_grupos	ON 
				    	(sga_requisitos_grupos.grupo_requisito = sga_requisitos_x_accion.grupo_requisito)
				    
				WHERE
					acc_interfaces.interfaz = sga_requisitos_conf_x_oper.interfaz
				    AND $schema_toba.apex_item.parametro_a = sga_requisitos_conf_x_oper.operacion
				    AND sga_requisitos_x_accion.requisito_accion = sga_requisitos_conf_x_oper.requisito_accion
				    $where
				
				ORDER BY
					operacion_codigo_y_nombre,
				    requisito_nombre
				";
		
		$datos = guarani_db::consultar($sql);
		foreach ($datos as $key => $dato) {
			/**
			 * Se recorren los registros obtenidos para poder recuperar los nombres de los requisitos por 
			 * acción de tipo entidad. Para esto necesita recuperarse el subtipo de cada entidad y la 
			 * tabla correspondiente al mismo.
			*/
			if ($dato['tipo'] == requisito::comp_entidad_estado) {
				$datos[$key]['requisito_nombre'] = toba::consulta_php('co_g3entidades')->get_nombre_entidad($dato['requisito_id']);
			}
			if (isset($dato['entidad_subtipo'])) {
				$datos_entidad = guarani::co('co_g3entidades')->get_datos_subtipo($dato['entidad_subtipo']);
				$datos[$key]['grupo_nombre'] = $datos_entidad['subtipo_nombre'];
			} else {
				$datos_entidad = guarani::co('co_g3entidades')->get_datos_entidad($dato['entidad']);
				$datos[$key]['grupo_nombre'] = $datos_entidad['entidad_tipo_nombre'] ." - ". $datos_entidad['entidad_nombre'];
			}
		}
		
		return $datos;
	}

	/**
	 * Retorna la configuración de requisitos por operación a partir de los datos de un grupo y un requisito por acción.
	 * Tiene en cuenta sólo las configuraciones que difieren de la configuración por defecto: ESTRICTO, ACTIVO.
	 * 
	 * @param array $datos_grupo_requisito
	 * @param array $datos_requisito_accion
	 * @return array
	 */
	function get_requisitos_conf_x_oper_no_default($datos_grupo_requisito, $datos_requisito_accion)
	{
		$where = " AND sga_requisitos_grupos.accion = {$datos_grupo_requisito['accion']}";
		$where .= isset($datos_grupo_requisito['entidad_subtipo']) ? " AND sga_requisitos_grupos.entidad_subtipo = {$datos_grupo_requisito['entidad_subtipo']}" : '';
		$where .= isset($datos_grupo_requisito['entidad']) ? " AND sga_requisitos_grupos.entidad = {$datos_grupo_requisito['entidad']}" : '';
		$where .= " AND sga_requisitos_x_accion.operador_not = {$datos_requisito_accion['operador_not']}";
		$where .= ($datos_requisito_accion['tipo'] == requisito::comp_requisito) ? " AND sga_requisitos_x_accion.requisito = {$datos_requisito_accion['requisito']}" : " AND sga_requisitos_x_accion.entidad = {$datos_requisito_accion['entidad']} AND	sga_requisitos_x_accion.estado = '{$datos_requisito_accion['estado']}'";

		$sql = "SELECT	sga_requisitos_conf_x_oper.operacion,
						sga_requisitos_conf_x_oper.interfaz,
						sga_requisitos_conf_x_oper.actua_como,
						sga_requisitos_conf_x_oper.activo
				FROM	sga_requisitos_conf_x_oper
						JOIN sga_requisitos_x_accion ON sga_requisitos_conf_x_oper.requisito_accion = sga_requisitos_x_accion.requisito_accion
						JOIN sga_requisitos_grupos ON sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito
				WHERE	(sga_requisitos_conf_x_oper.actua_como <> '".guarani_control::modo_estricto."' OR
						sga_requisitos_conf_x_oper.activo = '".comunes::no."')
						$where
		";

		return guarani_db::consultar($sql);
	}

	//-----------------------------------------------------------------------------------
	//---- opciones de autocompletado - configuración de requisitos por operación -------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_completar()
	{
		return array(array('valor' => requisito::autocompletar_campo_comportamiento,	'descr' => 'Comportamiento'),
                     array('valor' => requisito::autocompletar_campo_estado,			'descr' => 'Estado'));
	}

	// Las opciones del autcompletado son para cada una de las Interfaces existentes agregando la opción Todas.
	function get_opciones_combo_para()
	{
		$interfaces = toba::consulta_php('co_acciones')->get_interfaces_acciones();
		array_push($interfaces, array('valor' => requisito::autocompletar_para_todos, 	'descr' => 'Todos'));
		return $interfaces;
	}
}