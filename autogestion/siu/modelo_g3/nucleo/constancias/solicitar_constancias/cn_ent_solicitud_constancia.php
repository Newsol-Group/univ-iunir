<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class cn_ent_solicitud_constancia extends cn_entidad
{	
	protected $s__cant_copias_obligatorio = true;
	
    function set_copias_obligatorio($valor)
    {
        $this->s__cant_copias_obligatorio = (bool)$valor;
    }
            
	function get_datos_solicitud()
	{
		return $this->tabla('solicitud')->get();
	}

	function set_datos_solicitud($datos)
	{
		$this->tabla('solicitud')->set($datos);
	}
	
	//-------- Parámetros -----------------------------------------------
	
	function get_datos_params()
	{
		return $this->tabla('parametros')->get_filas();
	}
	
	/**
	 * Se recibe un arreglo asociativo campo=>valor retornados por el ci externo asociado a la constancia para la carga de 
	 * parámetros en su solicitud.
	 * Se recorren estas filas para ver qué parámetros deben agregarse, eliminarse y cuáles deben modificarse.
	 * Es decir que el procesamiento de filas se realiza manualmente debido a que los datos no están en un form_ml, si no 
	 * en un formulario contenido en un ci externo al item.
	 * 
	 * @param array $datos	arreglo asociativo campo=>valor conteniendo los valores de los parámetros de solicitud de la constancia.
	 */
	function set_datos_params($datos)
	{
		$parametros = $this->get_datos_params();
		// Se obtienen los campos actualmente asociados a la solicitud de constancia en formato de arreglo asociativo.
		$parametros_valor		= rs_convertir_asociativo($parametros, array('campo'), 'valor');
		$parametros_a_borrar	= array_diff($parametros_valor, $datos);
		$parametros_a_insertar	= array_diff($datos, $parametros_valor);
		$parametros_a_modificar = array_intersect($parametros_valor, $datos);
		// Se obtienen los campos actuales junto a su id interno de fila en formato de arreglo asociativo.
		$parametros_id_filas	= rs_convertir_asociativo($parametros, array('campo'), apex_datos_clave_fila);
		
		foreach ($parametros_a_borrar as $campo=>$valor) {
			$this->tabla('parametros')->eliminar_fila($parametros_id_filas[$campo]);
		}

		foreach ($parametros_a_insertar as $campo=>$valor) {
			$this->tabla('parametros')->nueva_fila(array('campo'=>$campo, 'valor'=>$valor));
		}
		
		foreach ($parametros_a_modificar as $campo=>$valor) {
			$this->tabla('parametros')->modificar_fila($parametros_id_filas[$campo], array('campo'=>$campo, 'valor'=>$valor));
		}
	}
	
	//-------------------------------------------------------------------
	
	/**
	 *	Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 *	Se controla que no se superen la cantidad máxima de copias permitidas de la solicitud permitidas para este tipo de
	 *  constancia, y la cantidad de solicitudes permitidas para una misma constancia.
	 */
	function pre_sincronizar()
	{
		$datos_solicitud = $this->get_datos_solicitud();
		$datos_constancia = toba::consulta_php('co_constancias')->get_datos_constancia($datos_solicitud['constancia']);
		/**
		 * Se verifica que se hayan completado los campos obligatorios Cantidad de Copias Solicitadas y 
		 * Presentar_a, ya que no pueden setearse como obligatorios desde el Editor.
		 */        
        if ($this->s__cant_copias_obligatorio){        
            if (!isset($datos_solicitud['cant_copias_solicitadas'])) {
				$this->set_error('dato_faltante', array('Cantidad de Copias'));
			}
			// Se verifica que la cantidad de copias solicitadas no supere el máximo valor establecido para la constancia de la solicitud.
			$copias_solicitadas = $datos_solicitud['cant_copias_solicitadas'];
			$max_copias = $datos_constancia['max_cant_copias'];
			if ($copias_solicitadas > $max_copias) {
				$this->set_error('800CON_limite_copias_sobrepasado', array($copias_solicitadas, $max_copias));
			}
		}
		if (!isset($datos_solicitud['presentar_a'])) {
			$this->set_error('dato_faltante', array('Presentar ante'));
		}
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
}
?>