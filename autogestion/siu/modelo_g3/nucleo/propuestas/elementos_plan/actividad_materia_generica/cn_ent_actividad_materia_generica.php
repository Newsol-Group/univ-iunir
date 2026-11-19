<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_ent_actividad_materia_generica extends cn_ent_elemento
{

	function get_g3entidad_subtipo() 
	{
		return elemento::subtipo_materia_generica;
	}
	
	//---------- paquete de INFO default de MATERIA -------------

	function set_datos_elementos($datos, $estoy_duplicando)
	{
		$datos['compartible'] = 'S';
		$datos['entidad_tipo'] = elemento::tipo_modulo;
		$datos['entidad_subtipo'] = elemento::subtipo_materia_generica;
		$this->validar_datos_actividades($datos, $estoy_duplicando);
		$this->tabla('elemento')->set($datos);
	}

	function get_datos_elementos()
	{
		return $this->tabla('elemento')->get();
	}

	function set_datos_materia($datos)
	{
		$this->tabla('materia_generica')->set($datos);
	}

	function get_datos_materia()
	{
		return $this->tabla('materia_generica')->get();
	}
	
	function get_datos_comp()
	{
        return $this->tabla('mat_gen_comp')->get_filas();
	}	

	function set_datos_comp($datos)
	{
		$this->tabla('mat_gen_comp')->procesar_filas($datos);
	}
	
	function get_datos_ua()
	{
        return $this->tabla('elementos_ua')->get_filas();
	}

	function set_datos_ua($datos)
	{
		$this->tabla('elementos_ua')->procesar_filas($datos);
	}

	function get_datos_atrib()
	{
        return $this->tabla('elementos_atrib')->get();
	}

	function set_datos_atrib($datos)
	{
		$this->tabla('elementos_atrib')->set($datos);
	}
	
	function add_datos_comp($seleccion)
	{
		$datos = $this->tabla('mat_gen_comp')->get_filas();
		$esta = false;
		foreach ( $datos as $key => $value ) {
			if ($value['elemento_hijo'] == $seleccion['elemento_revision']) {
				$esta = true;
				break;
			}
		}
	    
		if (!$esta) {
	        $dato_nuevo[0] = array(	'elemento_hijo' => $seleccion['elemento_revision'],
	                   				'puntaje' => 0,
	    			   				'x_dbr_clave' 	=> null,
	    							'apex_ei_analisis_fila' 	=> 'A'
	                               );
	        $this->tabla('mat_gen_comp')->procesar_filas($dato_nuevo);    
		}
	}

	//-----------------------------------------------------------------------------------
	
	function limpiar_campos_elemento()
	{
		$this->tabla('elemento')->set(array('codigo' => '', 'elemento' => NULL));
	}
	
	function duplicar()
	{
		$this->dep('datos')->forzar_insercion();
	}
	
	//-----------------------------------------------------------------------------------

	function validar_datos_actividades($datos)
	{
		$datos['elemento'] = $this->tabla('elemento')->get_columna('elemento');
		$this->validar_codigo_nombre_materia($datos['codigo'], $datos['nombre'], $datos['elemento']);
	}

	/**
	 * Valida que el nombre de materia no esté repetido
	 */
	function validar_codigo_nombre_materia($codigo, $nombre, $elemento)
	{
		if (!toba::consulta_php('co_elementos')->validar_codigo_nombre_materia($codigo, $nombre, $elemento)) {
			$this->set_error('dato_duplicado', array('código y nombre', 'materia genérica'));
			throw guarani::error($this->errores);
		}
	}
}
?>
