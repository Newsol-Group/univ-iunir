<?php
/**
 * Datos de las operaciones
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */


class co_operaciones
{

	/**
     *  Retorna los datos de las operaciones del proyecto cuyo parametro_a no es nulo y estan incluidas en el menu
     */
	function get_operaciones_menu($filtro='')
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro;
		}
		
		$sql = "SELECT 	apex_item.item,
						apex_item.nombre,
						apex_item.descripcion,
						apex_item_padre.padre,
						apex_item_padre.nombre as padre_nombre,
						apex_item.parametro_a as operacion,
						'(' || apex_item.parametro_a || ') ' || apex_item.nombre as descr
				FROM 	apex_item,
						apex_item as apex_item_padre
				WHERE 	apex_item.proyecto = '".toba::proyecto()->get_id()."'
				AND 	apex_item.parametro_a IS NOT NULL
				AND 	apex_item.menu = 1
				AND		apex_item.item != apex_item.padre	--no es raiz
				AND		apex_item_padre.proyecto = apex_item.padre_proyecto
				AND		apex_item_padre.item = apex_item.padre
				AND		apex_item_padre.menu = 1
						$filtro
				ORDER BY apex_item_padre.nombre,
						 '(' || apex_item.parametro_a || ') ' || apex_item.nombre
						;";

		return toba::instancia()->get_db()->consultar($sql);
	}

	function get_operaciones_en_accion()
	{
		$sql = "SELECT 
				  	operacion
				FROM 
					sga_acciones_operacion;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retora la lista de las últimas 10 operaciones accedidas por el usuario logueado en el proyecto.
	 *
	 * @return array
	 */
	function get_operaciones_recientes()
	{
		$schema_logs = toba::instancia()->get_db()->get_schema().'_logs';
		$usuario = toba::db()->quote(toba::usuario()->get_id());
		$proyecto = toba::db()->quote(toba_proyecto::get_id());

		$sql = "SELECT		i.item,
							i.nombre,
							CASE
								WHEN i.imagen is null THEN null
								ELSE i.imagen_recurso_origen
							END as origen,
							i.imagen,
							MAX(so.momento) as momento
				FROM		$schema_logs.apex_sesion_browser se,
							$schema_logs.apex_solicitud_browser sb,
							$schema_logs.apex_solicitud so,
							apex_item i
				WHERE		se.sesion_browser = sb.sesion_browser AND
							se.proyecto = sb.proyecto AND
							sb.solicitud_browser = so.solicitud AND
							sb.solicitud_proyecto = so.proyecto AND
							so.item = i.item AND
							so.item_proyecto = i.proyecto AND
							se.usuario = $usuario AND
							se.proyecto = $proyecto AND
							i.menu = 1 AND
							i.nombre <> 'Inicio'
				GROUP BY	i.item,
							i.nombre,
							origen,
							i.imagen
				ORDER BY	momento DESC
				LIMIT		10
		";
		return toba::instancia()->get_db()->consultar($sql);
	}
}
?>