<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Reglas
 */
class co_reglas
{

	function get_lista_reglas($where='')
	{
		if ($where) $where = " AND $where ";
	
        $sql = "SELECT 		sga_reglas.regla,
	                       	sga_reglas.nombre,
	                       	sga_reglas.descripcion,
	                       	sga_reglas.regla_tipo,
	                       	sga_reglas.mensaje_usuario,
	                       	sga_reglas.php_clase,
	                       	sga_reglas_tipos.nombre AS regla_tipo_nombre,
	                       	sga_reglas.nombre || ' (' || sga_reglas.regla || ' - ' || sga_reglas.php_clase || ')' AS regla_descr
  				FROM 		sga_reglas,
  							sga_reglas_tipos
  				WHERE 		sga_reglas_tipos.regla_tipo = sga_reglas.regla_tipo
  							$where
        		ORDER BY 	sga_reglas.nombre
  				";
        return guarani_db::consultar($sql);
	}
	
	/**
     * Retorna los Tipos de Reglas
     */
	function get_lista_reglas_tipos($where='')
	{
		if ($where) {			
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_reglas_tipos.regla_tipo as valor,
						sga_reglas_tipos.nombre as descr
				FROM 	sga_reglas_tipos
				$where
				ORDER BY 2
				;";

		return guarani_db::consultar($sql);
	}
	
	function get_info_regla($regla)
	{
		$regla = toba::db()->quote($regla);
		$clausula = "sga_reglas.regla = $regla";
		$datos = $this->get_lista_reglas($clausula);
		if (is_array($datos)) {
			$info_regla = current($datos);
			$mensajes_validacion = $this->get_regla_mensajes_validacion($regla);
			foreach($mensajes_validacion as $key => $mensaje) {
				$info_regla['mensajes_validacion'][$mensaje['interfaz']]['interfaz'] = $mensaje['interfaz'];
				$info_regla['mensajes_validacion'][$mensaje['interfaz']]['mensaje'] = $mensaje['mensaje_validacion'];
				$info_regla['mensajes_validacion'][$mensaje['interfaz']]['negado'] = $mensaje['mensaje_validacion_negado'];
			}
			return $info_regla;
		} else {
			return array();
		}	 
	}
	
	
	function get_regla_mensajes_validacion($regla)
	{
		$sql = "SELECT 	sga_reglas_mensajes.interfaz,
						sga_reglas_mensajes.mensaje_validacion,
						sga_reglas_mensajes.mensaje_validacion_not as mensaje_validacion_negado
  				FROM 	sga_reglas_mensajes
  				WHERE   sga_reglas_mensajes.regla = $regla 
        		
  						;";
        return guarani_db::consultar($sql);
	}
	
	function get_regla_params_def($regla=null)
	{
		if (!is_null($regla)) {
		
			$regla = toba::db()->quote($regla);
			$sql = "SELECT	   	sga_reglas.regla,
			                    sga_reglas_parametros.parametro,
		                       	sga_reglas_parametros.tipo_de_dato,
		                       	sga_reglas_parametros.orden,
		                       	sga_reglas_parametros.descripcion,
		                       	sga_reglas_parametros.etiqueta,
		                       	sga_reglas_parametros.valor_desde,
		                       	sga_reglas_parametros.valor_hasta 
	                  FROM     	sga_reglas,
								sga_reglas_parametros
	                  WHERE     sga_reglas.regla = $regla 
	                  AND     	sga_reglas_parametros.regla = sga_reglas.regla 
	                  ORDER BY 	sga_reglas_parametros.orden
	  				";
	       
	        return guarani_db::consultar($sql);
		} else {
			return array();
		}
	}	

	function get_regla_params_contexto_def($regla)
	{
		$regla = toba::db()->quote($regla);

        $sql = "SELECT	   	sga_reglas.regla,
		                    sga_reglas_param_contexto.parametro,
	                       	par_puntos_control_parametros.tipo_de_dato,
	                       	par_puntos_control_parametros.descripcion
                  FROM     	sga_reglas,
							sga_reglas_param_contexto,
							par_puntos_control_parametros
                  WHERE     sga_reglas.regla = $regla
                  AND     	sga_reglas_param_contexto.regla = sga_reglas.regla
				  AND		par_puntos_control_parametros.parametro = sga_reglas_param_contexto.parametro
							;";

        return guarani_db::consultar($sql);
	}

	//TODO Revisar utilidad
	function get_lista_reglas_pars($where='')
	{
		if ($where) $where = ' and '.$where.' ';
		
        $sql = "SELECT	   	sga_reglas.regla,
	                       	sga_reglas.nombre,
	                       	sga_reglas.descripcion,
	                       	sga_reglas.mensaje_usuario,
	                       	sga_reglas.php_clase,
		                    sga_reglas_parametros.parametro,
	                       	sga_reglas_parametros.tipo_de_dato,
	                       	sga_reglas_parametros.orden
                  FROM     	sga_reglas,
							sga_reglas_parametros
                  WHERE     sga_reglas.regla_tipo = " . regla::tipo_cumplimiento_modulos  . " 
                  AND     	sga_reglas_parametros.regla = sga_reglas.regla $where
                  ORDER BY 	sga_reglas.regla, sga_reglas_parametros.orden
  				";
       
        return guarani_db::consultar($sql);		
	}
    	
    
	/**
	* Retorna las opciones de formas de aprobacion
     *
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                )        	
    */
    function get_formas_aprob()
    {
        $sql = "SELECT 	sga_reglas.regla as valor,
                       	sga_reglas.nombre as descr 
  				FROM  	sga_reglas
  						;";
       
        return guarani_db::consultar($sql);
    }
    
	function get_reglas_evaluacion_modulos()
    {
        $sql = "SELECT 	sga_reglas.regla as valor,
                       	sga_reglas.nombre as descr 
  				FROM  	sga_reglas
  				WHERE 	sga_reglas.regla_tipo = ".regla::tipo_cumplimiento_modulos."
  						;";
       
        return guarani_db::consultar($sql);
    }

    /**
        Obtiene el valor y descripcion de una regla a partir del id dado para el ef_popup de Regla.
    */
    function get_regla($id=null)
    {
		if (! isset($id) || trim($id) == '') {
			return array();
		}    	
		
	    $id = toba::db()->quote($id);

				
		$sql = "SELECT	sga_reglas.regla as valor, 
					  	sga_reglas.nombre as descr
				FROM	sga_reglas
				WHERE	sga_reglas.regla = $id
				";				
			
		$result = guarani_db::consultar($sql);
		
		if (!empty($result)) {
			return $result[0]['descr'];
		}
    }
    
    /**
 	  * Determina si una regla dada tiene o no parámetros.
 	  * @param  integer $regla	id de la regla de la que se desea conocer si tiene o no parámetros.
 	  * @return boolean	true si la regla tiene parámetros formales definidos y false en caso contrario.
    */
    function tiene_parametros($regla)
    {
    	$parametros = $this->get_regla_params_def($regla);
    	if (!empty($parametros)) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /*
	 *	Retorna el listado de Reglas filtradas de acuerdo a un Tipo de Regla que viene dado por parametro
	 */
	function get_reglas_por_tipo($regla_tipo='')
	{
		$where = '';
		if (isset($regla_tipo)) {
			$regla_tipo = toba::db()->quote($regla_tipo);
			$where = ' sga_reglas.regla_tipo = '.$regla_tipo;
		}
		return $this->get_lista_reglas($where);
	}
	
	/**
	 * Retorna las reglas de tipo Proceso
	 */
	function get_reglas_tipo_proceso()
	{
		return $this->get_reglas_por_tipo(regla::tipo_procesos);
	}
	
	/**
        Obtiene la descripción de una regla
    */
    function get_descripcion($regla)
    {
		$regla = toba::db()->quote($regla);
		$sql = "SELECT	sga_reglas.descripcion as descripcion_regla
               	FROM 	sga_reglas 
               	WHERE 	sga_reglas.regla = $regla
				";
		
		$result = guarani_db::consultar($sql);
		
		if (!empty($result)) {
			return $result[0]['descripcion_regla'];
		}
    }

	/**
	 * Retorna el nombre del tipo asociado a una regla en particular.
	 * 
	 * @param integer $regla
	 * @return string
	 */
    function get_regla_tipo_nombre($regla)
    {
		$regla = toba::db()->quote($regla);
		$sql = "SELECT	sga_reglas_tipos.nombre as regla_tipo_nombre
				FROM	sga_reglas,
						sga_reglas_tipos
				WHERE	sga_reglas.regla_tipo = sga_reglas_tipos.regla_tipo AND
						sga_reglas.regla = $regla
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['regla_tipo_nombre'];
    }

    function get_mensaje_usuario($regla, $parametros)
    {
    	$regla = toba::db()->quote($regla);
    	$parametros= toba::db()->quote($parametros);
    	$sql = "select get_regla_mensaje($regla, $parametros) as mensaje";
    	$result = guarani_db::consultar($sql);
		
		if (!empty($result)) {
			return $result[0]['mensaje'];
		}
    }

    /**
	 * Obtiene los ids y descripciones de todas las Reglas cuyos nombres contengan al string ingresado como
	 * filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre de las Reglas ingresadas por el usuario.
	 */
	function get_descripciones_reglas($filtro='')
	{
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = toba::db()->quote("%$filtro%");
			$filtro = "sga_reglas.nombre ILIKE $filtro OR sga_reglas.php_clase ILIKE $filtro";
			return $this->get_lista_reglas($filtro);
		}
	}
	
	/**
	 * Obtiene el nombre de una Regla cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $regla id de la Regla para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_regla($regla=null)
	{
		if (isset($regla)) {
			$regla = toba::db()->quote($regla);
			$result = toba::consulta_php('co_reglas')->get_lista_reglas("sga_reglas.regla = $regla");
			return empty($result) ? '' : $result[0]['regla_descr'];
		}
		return '';
	}
	
}
?>