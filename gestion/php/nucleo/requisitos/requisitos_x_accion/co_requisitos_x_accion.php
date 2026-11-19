<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Requisitos por Acción
 */
class co_requisitos_x_accion
{
	function get_listado($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_requisitos.requisito,
							sga_requisitos.nombre
				FROM		sga_requisitos_x_accion,
							sga_requisitos
				WHERE		sga_requisitos_x_accion.requisito = sga_requisitos.requisito
							$where
				GROUP BY	sga_requisitos.requisito,
							sga_requisitos.nombre
				ORDER BY	sga_requisitos.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna los Requisitos aplanados para una acción y un filtro dado. Es decir los requisitos propios de 
	 * esa entidad para esa acción, más los heredados que están asociados para la acción y los subtipos de entidades 
	 * superiores en la jerarquía (por ej. Propuesta de Grado, Responsable Académica, etc). Todo se obtiene de una 
	 * tabla donde se precompila esta información: sga_requisitos_aplanado.
    */
	function get_requisitos_aplanados_accion_entidad($accion, $plan_version=null)
	{
		$accion = toba::db()->quote($accion);
		$where = '';
		if (isset($plan_version)) {
			$plan_version = toba::db()->quote($plan_version);
			$where .= " AND sga_requisitos_aplanado.plan_version = $plan_version ";
		}
		
		$sql = "SELECT 		sga_requisitos_aplanado.requisito_accion,
							sga_requisitos_x_accion.tipo,
							sga_requisitos_x_accion.requisito,
							CASE sga_requisitos_x_accion.tipo
								WHEN 'R' THEN sga_requisitos.nombre
								WHEN 'E' THEN ' '
							END as requisito_nombre,
							sga_requisitos_x_accion.parametros,
							sga_requisitos_x_accion.entidad,
							sga_requisitos_x_accion.estado,
							CASE sga_requisitos_x_accion.tipo
								WHEN 'E' THEN sga_estados.nombre 
								WHEN 'R' THEN ' ' 
							END as estado_nombre,
							sga_requisitos_x_accion.operador_not,
							sga_requisitos_grupos.entidad as entidad_grupo,
							sga_requisitos_grupos.entidad_subtipo as entidad_subtipo_grupo
				FROM 		sga_requisitos_grupos,
							sga_requisitos_aplanado,
    	   					sga_requisitos_x_accion
       						LEFT JOIN sga_requisitos ON sga_requisitos.requisito = sga_requisitos_x_accion.requisito
        					LEFT JOIN sga_estados    ON sga_estados.estado       = sga_requisitos_x_accion.estado
				WHERE 		sga_requisitos_aplanado.requisito_accion = sga_requisitos_x_accion.requisito_accion AND
							sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito AND
							sga_requisitos_grupos.accion = $accion
							$where
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los Requisitos Activos que pueden asociarse a una acción, según el filtro dado como parámetro y los requisitos a 
	 * excluir. Estos están compuestos por el conjunto de requisitos de tipo presentables, que implícitamente son válidos 
	 * para todas las acciones, y por el conjunto de requisitos que se define como válidos para la acción 
	 * (sga_requisitos_validos). Como esta consulta se utiliza en general pasándole un conjunto de requisitos a
	 * excluir, como condicion NOT IN (los requisitos ya asociados a la acción), para no permitir que el usuario los 
	 * seleccione duplicados, se deben volver a unir al conjunto de respuesta los requisitos con parámetros 
	 * (ya que estos no se sabe si están o no duplicados hasta el momento de definir sus parámetros actuales, 
	 * con lo cual deben mostrarse para que puedan seleccionarse).
	 * En caso de que no se esté utilizando la consulta de esta forma, con ese filtro, la unión (sin repeticiones) de
	 * estos requisitos con parámetros no afecta en nada al conjunto respuesta.
	 * @param int $id_accion		Identificador de la acción para la cual se buscan los requisitos asociables.
	 * @param str $req_a_excluir	Condición Not In para restringir los requisitos a retornar.
	 * @param str $filtro			Filtro de requisitos para la búsqueda.
	 * @return array 				Retorna los requisitos asociables a la acción con sus respectivos datos, en formato recordset.
	*/
	function get_req_asociables_a_accion($id_accion, $requisitos_a_excluir='', $filtro='')
	{
		$id_accion = toba::db()->quote($id_accion);
		$tipo_presentable = toba::db()->quote(requisito::tipo_documentales);
		if ($filtro) {
			$filtro = ' AND '. $filtro;
		}
		if ($requisitos_a_excluir) {
			$requisitos_a_excluir = ' AND ' . $requisitos_a_excluir;
		}
		//Recupera los requisitos de tipo Presentado/NoPresentado, unidos a los requisitos definidos como válidos para la acción.
		$sql = "SELECT 	sga_requisitos.requisito,
						sga_requisitos.nombre,
						sga_requisitos.requisito_tipo,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos.regla,
						sga_requisitos.descripcion,
						sga_requisitos_tipos.nombre as nombre_tipo,
						sga_requisitos_tipos.descripcion as descripcion_tipo
				FROM 	sga_requisitos,
						sga_requisitos_tipos
				WHERE 	sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo AND
						sga_requisitos.requisito_tipo = $tipo_presentable AND
						sga_requisitos.activo = '" . comunes::si . "'
  				 		$requisitos_a_excluir
						$filtro
				UNION	
				SELECT	sga_requisitos.requisito,
						sga_requisitos.nombre,
						sga_requisitos.requisito_tipo,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos.regla,
						sga_requisitos.descripcion,
						sga_requisitos_tipos.nombre as nombre_tipo,
						sga_requisitos_tipos.descripcion as descripcion_tipo
				FROM	sga_requisitos_validos,
						sga_requisitos,
						sga_requisitos_tipos
				WHERE	sga_requisitos_validos.requisito = sga_requisitos.requisito AND
						sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo AND
						sga_requisitos_validos.accion = $id_accion AND
						sga_requisitos.activo = '" . comunes::si . "'
						$requisitos_a_excluir
						$filtro
				UNION
				SELECT	sga_requisitos.requisito,
						sga_requisitos.nombre,
						sga_requisitos.requisito_tipo,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos.regla,
						sga_requisitos.descripcion,
						sga_requisitos_tipos.nombre as nombre_tipo,
						sga_requisitos_tipos.descripcion as descripcion_tipo
				FROM	sga_requisitos_validos,
						sga_requisitos,
						sga_requisitos_tipos
				WHERE	sga_requisitos_validos.requisito = sga_requisitos.requisito AND
						sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo AND
						sga_requisitos_validos.accion = $id_accion AND
						sga_requisitos.requisito 
							IN (
                            	SELECT  sga_requisitos.requisito
                            	FROM    sga_requisitos,
                                    	sga_reglas,
                                    	sga_reglas_parametros
                            	WHERE   sga_requisitos.regla = sga_reglas.regla AND
                                	    sga_reglas.regla = sga_reglas_parametros.regla
                            	) AND
						sga_requisitos.activo = '" . comunes::si . "'
						$filtro
						
				;";
		
		return guarani_db::consultar($sql);
	}
		
	/**
	 * Retorna las opciones para la carga del combo sobre el Requisito por Acción que se desea agreagar.
    */
    function get_requisitos_a_agregar()
    {
		return array(array('valor'=>requisito::req_existente, 'descr'=>'Requisito'),
		             array('valor'=>requisito::ent_estado, 'descr'=>'Actividad/Módulo'),
		            );
    }
    
    /**
     * Retorna las opciones correspondientes a la aplicación del Requisito/Not Requisito.
     */
    function get_opciones_requisito_y_negacion()
	{
		return array(array('valor' => requisito::requisito, 	'descr' => 'Cumplir'),
		             array('valor' => requisito::not_requisito, 'descr' => 'No Cumplir')
		            );
	}

	function get_requisitos_x_accion($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_requisitos_x_accion.requisito_accion,
						sga_requisitos_grupos.entidad_subtipo,
						sga_requisitos_grupos.entidad,
				        CASE
				            WHEN sga_requisitos_grupos.entidad_subtipo IS NOT NULL THEN subtipo.entidad_tipo
				        	WHEN sga_requisitos_grupos.entidad IS NOT NULL THEN subtipo_x_entidad.entidad_tipo
				        END as entidad_tipo
				
				FROM	sga_requisitos_x_accion,
						sga_requisitos_grupos
				        LEFT OUTER JOIN sga_g3entidades_subtipos as subtipo ON sga_requisitos_grupos.entidad_subtipo = subtipo.entidad_subtipo
				        LEFT OUTER JOIN (sga_g3entidades
				        LEFT OUTER JOIN sga_g3entidades_subtipos as subtipo_x_entidad ON sga_g3entidades.entidad_subtipo = subtipo_x_entidad.entidad_subtipo) ON sga_requisitos_grupos.entidad = sga_g3entidades.entidad
				
				WHERE	sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito
						$where
		";
		return guarani_db::consultar($sql, null, false);
	}
	function get_requisitos_x_accion_distinct($accion)
	{
		
		
		$accion = toba::db()->quote($accion);
		
		$sql = "SELECT DISTINCT sga_requisitos.requisito, sga_requisitos.nombre 			
				FROM sga_requisitos_grupos 
				JOIN sga_requisitos_x_accion ON sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito 
				JOIN sga_requisitos  ON sga_requisitos.requisito = sga_requisitos_x_accion.requisito 
				WHERE sga_requisitos_grupos.accion = $accion
				ORDER BY sga_requisitos.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}
}
?>