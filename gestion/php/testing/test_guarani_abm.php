<?php
/**
*	Caso de test del SIU-Guarani
*/
class test_guarani_abm extends test_guarani
{
	//-- Metadata Caso
	protected $cn_manejador;		//Identificador del manejador de la entidad
	protected $api_entidad;			//Sufijo de un elemento del api. Supone un set_ y un get!
	protected $datos_alta;			//Datos con los que cargar cada elemento de la entidad
	protected $datos_modificacion;	//Datos con los que se va a realizar la modificacion
	protected $datos_obligatorios;	//Datos obligatorios
	protected $columnas_busqueda;	//Columnas que se utilizan para buscar registros
	protected $columna_clave_db;
	//-- Info temporal
	protected $cantidad_registros;
	protected $id_entidad;
	

	function get_datos_alta($elemento_api)
	{
		//Busco los datos en un metodo (para casos en los que los valores son dinamicos)
		$metodo = 'get_datos_' . $elemento_api;
		if(method_exists($metodo,$this)){
			return $this->$metodo;	
		}else{
			//Busco los datos en una propiedad
			if(isset($this->datos_alta[$elemento_api])){
				return $this->datos_alta[$elemento_api];
			}
		}
		throw new guarani_error('No hay datos de ALTA disponibles para el elemento del API: ' . $elemento_api);
	}

	function get_datos_modificacion($elemento_api)
	{
		//Busco los datos en un metodo (para casos en los que los valores son dinamicos)
		$metodo = 'get_datos_' . $elemento_api;
		if(method_exists($metodo,$this)){
			return $this->$metodo;	
		}else{
			//Busco los datos en una propiedad
			if(isset($this->datos_modificacion[$elemento_api])){
				return $this->datos_modificacion[$elemento_api];
			}
		}
		throw new guarani_error('No hay datos de MODIFICACION disponibles para el elemento del API: ' . $elemento_api);
	}
	
	//-----------------------------------------------
	//--- TEST --------------------------------------
	//-----------------------------------------------

	function test_get_listado()
	{
		$manejador = toba::cn($this->manejador);
		$datos = $manejador->get_listado();
		$this->cantidad_registros = guarani_arreglos::count($datos);
		$this->pass();
	}
	
	/**
	*	Crea una entidad
	*/
	function test_alta()
	{
		try {
			$manejador = toba::cn($this->manejador);
			$entidad = $manejador->nueva();
			foreach( $this->api_entidad as $elemento_api ) {
				$metodo_a_invocar = 'set_'.$elemento_api;
				$datos = $this->get_datos_alta($elemento_api);
				$entidad->$metodo_a_invocar($datos);
			}
			$this->id_entidad = $entidad->sincronizar();
			$manejador->resetear();
			$this->pass();
		} catch (guarani_error $e ) {
			$this->fail($e->getMessage());
		}
	}	

	function test_busqueda()
	{
		
	}

	function test_modificacion()
	{
		try {
			$manejador = toba::cn($this->manejador);
			$manejador->cargar($this->id_entidad[$this->columna_clave_db]);
			$entidad = $manejador->entidad();
			foreach( $this->api_entidad as $elemento_api ) {
				$metodo_a_invocar = 'set_'.$elemento_api;
				$datos = $this->get_datos_modificacion($elemento_api);
				$entidad->$metodo_a_invocar($datos);
			}
			$entidad->sincronizar();
			$manejador->resetear();
			$this->pass();
		} catch (guarani_error $e ) {
			$this->fail($e->getMessage());
		}		
	}	

	function test_baja()
	{
		try {
			$manejador = toba::cn($this->manejador);
			$manejador->cargar($this->id_entidad[$this->columna_clave_db]);
			$manejador->eliminar();
			$this->pass();
		} catch (guarani_error $e ) {
			$this->fail($e->getMessage());
		}		
	}

	function test_controlar_cantidad_registros_final()
	{
		$manejador = toba::cn($this->manejador);
		$datos = $manejador->get_listado();
		$cantidad_registros = guarani_arreglos::count($datos);
		$this->AssertEqual($this->cantidad_registros, $cantidad_registros );
	}
}

?>