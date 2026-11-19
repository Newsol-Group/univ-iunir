<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class cn_ent_conf_requisitos_x_operacion extends cn_entidad
{
	function cargar($id, $parametros=null)
	{
		$this->dep('datos')->tabla('requisitos_conf_x_oper')->persistidor()->cargar_con_sql(guarani::co('co_requisitos_x_operacion')->get_grupos_de_requisito($parametros,$id,true));
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('requisitos_conf_x_oper')->procesar_filas($datos);
	}

	function get_datos_grupo($requisito_accion)
	{
		$datos_requisito_grupo = guarani::co('co_requisitos_x_operacion')->get_datos_requisitos_grupo($requisito_accion);
		if (isset($datos_requisito_grupo['entidad_subtipo'])) {
			$datos_entidad = guarani::co('co_g3entidades')->get_datos_subtipo($datos_requisito_grupo['entidad_subtipo']);
			$datos['grupo_nombre']	= $datos_entidad['subtipo_nombre'];
		} else {
			$datos_entidad = guarani::co('co_g3entidades')->get_datos_entidad($datos_requisito_grupo['entidad']);
			$datos['grupo_nombre']	= $datos_entidad['entidad_tipo_nombre'] ." - ". $datos_entidad['entidad_nombre'];
		}
		$datos['grupo_requisito']	= $datos_requisito_grupo['grupo_requisito']; 
		return $datos;
	}
	
	function get_datos($condiciones=null)
	{
		$datos = $this->tabla('requisitos_conf_x_oper')->get_filas($condiciones);
		foreach ($datos as $key => $dato) {
			$datos_grupo = $this->get_datos_grupo($dato['requisito_accion']);
			$datos[$key]['grupo_requisito'] = $datos_grupo['grupo_requisito'];
			$datos[$key]['grupo_nombre'] = $datos_grupo['grupo_nombre'];	
		}
		return $datos;
	}
	
	/**
	 * Se redefine la función sincronizar del cn_entidad ya que este DR tiene una único DT, y no puede realizarse
	 * la sincronización de la realización por no tener un único registro en la tabla cabecera. Directamente se
	 * sincroniza el DT de configuración de requisitos.
	 *
	 */
	function sincronizar()
	{
		$this->tabla('requisitos_conf_x_oper')->sincronizar();
	}
}
?>