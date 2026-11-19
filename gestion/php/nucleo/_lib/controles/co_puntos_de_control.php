<?php
/**
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */


class co_puntos_de_control
{
	/**
        Retorna el listado de Puntos de Control según el filtro recibido.
    */
    function get_listado($where='')
    {
    	if ($where) {
	    	$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT	par_puntos_control.pto_control,
						par_puntos_control.nombre,
						par_puntos_control.descripcion
				FROM	par_puntos_control	
				$where
				ORDER BY par_puntos_control.pto_control;
		";
		return guarani_db::consultar($sql);
    }
    
    /**
        Retorna los datos de un punto de control dado.
    */
	function get_datos_pto_control($pto_control=null)
	{
		if (!is_null($pto_control)) {
	   		$pto_control = toba::db()->quote($pto_control);
	   		$filtro = "par_puntos_control.pto_control = $pto_control";
	   		$result = $this->get_listado($filtro);
	   		if (!empty($result)) {
	   			return $result[0];	
	   		}
		} else {
			return array();
		}
	}

	/**
	 * Devuelve los controles (requisitos) a validar para un punto de control dado
	 * dentro de un plan versión dado y para la operación en ejecución y la interfaz (gestion, autogestion web o celular) pasada como paremetro
	 */
		
	function get_controles($punto_de_control, $plan_version, $interfaz, $operacion)
	{

		$operacion = toba::db()->quote($operacion);

		if (!isset($plan_version))  {
			throw new guarani_error('Falta pasar como parámetro el plan version');
		}
		$plan_version = toba::db()->quote($plan_version);

        // Solo los requisitos definidos en el Punto de Control y configurados en la operación y version de plan de estudios pasado por parámetro.
		
 	    // Recupera REQUISITOS con sus reglas de cumplimiento y parámetros
		// Recupera ENTIDADES con sus reglas de cumplimiento y parámetros, por el momento no se buscan las reglas en sga_g3entidades_estados
	
		if ($punto_de_control == guarani_punto_de_control::alumno_entidad) {
		
			$sql = 	"SELECT	DISTINCT
						sga_requisitos_x_accion.tipo,
						sga_requisitos_x_accion.operador_not,
						cast(null as integer) as requisito,
						sga_requisitos_x_accion.entidad,
						sga_requisitos_x_accion.estado,
						f_nombre_entidad(sga_requisitos_x_accion.entidad) as nombre,
						cast(null as integer) as requisito_subordinado_de,
						sga_requisitos_conf_x_oper.actua_como,
						sga_requisitos_conf_x_oper.interfaz,
						COALESCE(sga_requisitos_x_accion.regla, sga_g3entidades_subtipos_estados.regla) as regla,
						COALESCE(sga_requisitos_x_accion.parametros, '') as parametros,
						1 as orden
				FROM	sga_acciones_operacion
						JOIN sga_requisitos_grupos				ON	(sga_requisitos_grupos.accion						= sga_acciones_operacion.accion)
						JOIN sga_requisitos_x_accion			ON	(sga_requisitos_x_accion.grupo_requisito			= sga_requisitos_grupos.grupo_requisito)
						JOIN sga_requisitos_aplanado			ON	(sga_requisitos_aplanado.requisito_accion			= sga_requisitos_x_accion.requisito_accion)					
				        JOIN sga_requisitos_conf_x_oper			ON	(sga_requisitos_conf_x_oper.requisito_accion		= sga_requisitos_x_accion.requisito_accion)
						JOIN sga_g3entidades					ON	(sga_g3entidades.entidad							= sga_requisitos_x_accion.entidad)
						JOIN sga_g3entidades_subtipos_estados	ON	(sga_g3entidades_subtipos_estados.entidad_subtipo	= sga_g3entidades.entidad_subtipo
																	AND sga_g3entidades_subtipos_estados.estado				= sga_requisitos_x_accion.estado)
				
				WHERE	sga_requisitos_aplanado.plan_version		= $plan_version 
				AND		sga_acciones_operacion.operacion			=  $operacion
				AND		sga_requisitos_x_accion.tipo				= '".requisito::comp_entidad."'
				AND		sga_requisitos_conf_x_oper.activo			= '".comunes::si."'
				AND		sga_requisitos_conf_x_oper.operacion		= $operacion
				AND		sga_requisitos_conf_x_oper.interfaz			= $interfaz
				
				
				ORDER BY 12";
			
		} else {
			
		$punto_de_control = toba::db()->quote($punto_de_control);
		
	    $sql = "
				SELECT	DISTINCT
						sga_requisitos_x_accion.tipo,
						sga_requisitos_x_accion.operador_not,
						sga_requisitos.requisito,
						sga_acciones_operacion.accion,
						cast(null as integer) as entidad,
						sga_requisitos_x_accion.estado,
						sga_requisitos.nombre,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos_conf_x_oper.actua_como,
						sga_requisitos_conf_x_oper.interfaz,
						COALESCE(sga_requisitos_x_accion.regla, sga_requisitos.regla) as regla,
						COALESCE(sga_requisitos_x_accion.parametros, '') as parametros,
						sga_requisitos_x_punto_control.orden as orden
				FROM	sga_acciones_operacion
						JOIN sga_requisitos_grupos			ON	(sga_requisitos_grupos.accion					= sga_acciones_operacion.accion)
						JOIN sga_requisitos_x_accion		ON  (sga_requisitos_x_accion.grupo_requisito		= sga_requisitos_grupos.grupo_requisito)
						JOIN sga_requisitos_x_punto_control ON	(sga_requisitos_x_punto_control.requisito		= sga_requisitos_x_accion.requisito)
						JOIN sga_requisitos_conf_x_oper		ON  (sga_requisitos_conf_x_oper.requisito_accion	= sga_requisitos_x_accion.requisito_accion)
						JOIN sga_requisitos					ON  (sga_requisitos.requisito						= sga_requisitos_x_accion.requisito)
						JOIN sga_requisitos_aplanado		ON	(sga_requisitos_aplanado.requisito_accion		= sga_requisitos_x_accion.requisito_accion)					
		
				WHERE	sga_requisitos_aplanado.plan_version		= $plan_version 
				AND		sga_requisitos_x_punto_control.pto_control	= $punto_de_control
				AND		sga_acciones_operacion.operacion			= $operacion
				AND		sga_requisitos_x_accion.tipo				= '".requisito::comp_requisito."'
				AND		sga_requisitos_conf_x_oper.activo			= '".comunes::si."'
				AND		sga_requisitos_conf_x_oper.operacion		= $operacion
				AND		sga_requisitos_conf_x_oper.interfaz			= $interfaz
				
				ORDER BY 12
						;";
		}
		
		return guarani_db::consultar($sql);
	}

    function get_valores_actua_como()
	{
		return array(array('valor' => guarani_control::modo_mensaje,		'desc' => 'Mensaje'),
					 array('valor' => guarani_control::modo_estricto,		'desc' => 'Estricto'),
					 array('valor' => guarani_control::modo_advertencia,	'desc' => 'Advertencia'));
	}

	/**
	 * Retorna los puntos de control a validar para la instancia recibida.
	 * 
	 * @param int $instancia
	 * @return array
	 */
	function get_puntos_de_control_instancia($instancia)
	{
		if (is_array($instancia)) {
			$where = guarani_sql::armar_condicion_in($instancia, 'sga_instancias_puntos_ctrl', 'instancia');
		} else {
			$instancia = toba::db()->quote($instancia);
			$where = "sga_instancias_puntos_ctrl.instancia = $instancia";
		}
		$sql = "SELECT	sga_instancias_puntos_ctrl.pto_control
				FROM	sga_instancias_puntos_ctrl
				WHERE	$where
		";
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'pto_control');
	}

	/**
	 * Retorna una matriz indexada por instancia con el conjunto de controles asociados.
	 *
	 * @param array $instancias
	 * @return array
	 */
	function get_puntos_de_control_instancias($instancias)
	{
		$where = guarani_sql::armar_condicion_in($instancias, 'sga_instancias_puntos_ctrl', 'instancia');
		$sql = "SELECT	sga_instancias_puntos_ctrl.instancia,
						sga_instancias_puntos_ctrl.pto_control
				FROM	sga_instancias_puntos_ctrl
				WHERE	$where
		";
		$rs = guarani_db::consultar($sql);
		$puntos_de_control = array();
		foreach ($rs as $r) {
			$puntos_de_control[$r['instancia']][] = $r['pto_control'];
		}
		return $puntos_de_control;
	}
}
?>