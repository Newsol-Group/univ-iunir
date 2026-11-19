<?php

/**
 *
 *	Clase utilizada para la configuracion y organizacion de los datos pasados a los componentes 
 *  para la impresion de comprobantes
 *
 *
 */

class guarani_datos_impresion_nucleo {
	
	static private $instancia;
	private $titulo;
	private $pie;
	private $conf;
	private $datos;
	private $ancho;
	private $alto;

	function __construct()
	{
		$this->conf=array();
		$this->titulo='';
		$this->datos=array();
		$this->pie='';
		$this->ancho = 210;
		$this->alto = 270;
	}
	
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new guarani_datos_impresion();
		}
		return self::$instancia;	
	}	
	
	/**
	 * Arma y devuelve un arreglo para pasar al Comprobante Generido para imprimir
	 */
	private function get_arreglo_datos_imp()
	{
		/*
		 *
		 * El formato del arreglo de datos es:
		 * datos (
		 * 			conf => 	( 	id => valor, id => valor, id => valor 	)
		 * 			titulo => 	( 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'			)
		 *			{
		 *
		 * 			datos => 	(
		 * 							0 =>	(
		 * 										'etiq' =>	('xxxxxxxxxxxxx')
		 * 										'valor' =>	(xxxxxxxxxxxx)
		 *									)
		 * 							1 =>	(
		 * 										'etiq' =>	('xxxxxxxxxxxxx')
		 * 										'valor' =>	(xxxxxxxxxxxx)
		 *									)
		 *							.....		 
		 *						)
		 *
		 *				|
		 *
		 * 			datos => 	( 
		 *							array ( 
		 * 									array (....) 
		 *									......
		 *								  )
		 *						)
		 *
		 *			}
		 * 			pie => 		( 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'			)
		 *		)
		 *
		*/ 
		
		$datos = array();

		$datos['ancho'] = $this->ancho;
		$datos['alto'] = $this->alto;
		$datos['conf'] = $this->conf;
		$datos['titulo'] = $this->titulo;
		$datos['datos'] = $this->datos;
		$datos['pie'] = $this->pie;
		
		return $datos;
	}

	/*
	 * Setea las dimensiones de la página en mm.
	 */
	function set_dim_pagina($ancho=null, $alto=null)
	{
		if ($ancho) {
			$this->ancho = $ancho;
		}
		if ($alto) {
			$this->alto = $alto;
		}
	}

	function set_titulo($titulo='') 
	{
		$this->titulo = $titulo;
	}

	function get_titulo() 
	{
		return $this->titulo;
	}

	function set_pie($pie='') 
	{
		$this->pie = $pie;
	}

	function get_pie() 
	{
		return $this->pie;
	}

	function get_ancho()
	{
		return isset($this->ancho) ? $this->ancho : 210;
	}

	function get_alto()
	{
		return isset($this->alto) ? $this->alto : 270;
	}

	function set_conf($config=array()) 
	{
		$this->conf = array_merge($this->conf, $config);
	}
	
	function get_conf($id=null) 
	{
		$val = null;
		if (isset($id)) {
			if (isset($this->conf['id'])) {
				$val = $this->conf['id'];
			}
		} else {
			$val = $this->conf;
		}
		return $val;
	}

	function get_datos($etiq=null)
	{
		$val = null;
		if (!is_null($etiq)) {
			if (isset($this->datos[$etiq])) {
				$val = $this->datos[$etiq];
			}
		} else {
			$val = $this->datos;
		}
		return $val;
	}
	
	/*
	 * Agrega una fila al arreglo de datos del tipo 'etiqueta' => 'valor'
	 * Usualmente usada para pasar datos a forms
	 */
	function nueva_fila_datos($etiq=null,$valor=null)
	{
		if (is_null($etiq)) {
			throw guarani_error('No se puede guardar la nueva fila de datos');
		}
		$this->datos[] = array('etiq' => $etiq, 'valor' => $valor);
	}

	/*
	 * Agrega un arreglo de datos 'crudo' a los datos del comprobante
	 * Usualmente usada para pasar datos a objetos que no son forms
	 */
	function set_datos($datos=array())
	{
		$this->datos = array_merge($this->datos, $datos);
	}

	/*
	 * Limpia e Inicializa los datos propios 
	 */
	function limpiar_datos()
	{
		$this->__construct();
	}

	/*
	 * 	Almaceno los datos en memoria para ser recuperarlos luego para la impresión
	 */	
	function guardar_datos_imp($sufijo='')
	{
		$dato = 'datos_impresion' . $sufijo;
		//Almaceno los datos en memoria para recuperarlos luego para la impresión de comprobante
		toba::memoria()->set_dato($dato, $this->get_arreglo_datos_imp());
	}
	
	/*
	 * Devuelve los datos almacenados en memoria y configura las variables locales con los datos obtenidos
	 */
	function recuperar_datos_imp($sufijo='')
	{
		$val = null;
		$dato = 'datos_impresion' . $sufijo;
		if (toba::memoria()->existe_dato($dato)) {
			$val = toba::memoria()->get_dato($dato);
		}
		
		if (!is_null($val)) {
			$this->conf = $val['conf'];
			$this->titulo = $val['titulo'];
			$this->datos = $val['datos'];
			$this->pie = $val['pie'];
			$this->alto = $val['alto'];
			$this->ancho = $val['ancho'];
		}
		
		return $val;
	}
	
}