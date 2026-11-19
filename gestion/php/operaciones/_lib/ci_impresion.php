<?php
class ci_impresion extends guarani_pers_ci
{
	
	 /*
	  * La idea de esta clase es que extiendan de la misma los objetos para realizar impresiones de forma tal
	  * que el comportamiento estandar ya esté modelado. Particularmente, esta basada en la impresion de los Reportes de Actas 
	  * 
	  */
	
	protected $s__datos_conf; //Datos de Configuración
	protected $s__ancho; 	// Ancho de la pagina en mm
	protected $s__alto; 	// Alto de la página en mm
	protected $s__titulo; 	// Titulo
	protected $s__datos;  	// Datos del Cuerpo a Imprimir
	protected $s__pie;		// Pie
	protected $s__datos_imp; //Datos de Impresion Completos (todos los anteriores)

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		
		if (method_exists($this->controlador, 'get_datos_impresion')) {
			$datos = $this->controlador->get_datos_impresion();
			/*
			 * ei_arbol($datos);
			 * El formato del arreglo de datos debe ser:
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
		}
		$this->s__datos_imp = (isset($datos)) ? $datos : array();
		$this->s__ancho = (isset($datos['ancho'])) ? $datos['ancho'] : 210;
		$this->s__alto = (isset($datos['alto'])) ? $datos['alto'] : 270;
		$this->s__datos_conf = (isset($datos['conf'])) ? $datos['conf'] : array();
		$this->s__titulo = (isset($datos['titulo'])) ? $datos['titulo'] : 'Reporte';
		$this->s__datos = (isset($datos['datos'])) ? $datos['datos'] : array();
		$this->s__pie = (isset($datos['pie'])) ? $datos['pie'] : '';
	}
	
	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_titulo($this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);
		$xml = $this->xml_get_tag_inicio();
		
		foreach ($this->get_dependencias() as $dep) {
			if (method_exists($dep, 'vista_xml')) {
				$xml .= $dep->vista_xml(false, $xmlns);
			}
		}
		
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
		
	function get_datos_imp()
	{
		return $this->s__datos_imp;
	}

	function set_datos_imp($datos)
	{
		$this->s__datos_imp = $datos;
	}
	
	function set_titulo_rep($titulo='') 
	{
		$this->s__titulo = $titulo;
	}

	function get_titulo() 
	{
		return $this->s__titulo;
	}

	function set_pie($pie='') 
	{
		$this->s__pie = $pie;
	}

	function get_pie() 
	{
		return $this->s__pie;
	}

	function set_conf($config=array()) 
	{
		$this->s__datos_conf = array_merge($this->s__datos_conf, $config);
	}
	
	function get_conf($id=null) 
	{
		$val = null;
		if (!is_null($id)) {
			if (isset($this->s__datos_conf[$id])) {
				$val = $this->s__datos_conf[$id];
			}
		} else {
			$val = $this->s__datos_conf;
		}
		return $val;
	}

	function get_datos($etiq=null)
	{
		$val = null;
		if (!is_null($etiq)) {
			if (isset($this->s__datos[$etiq])) {
				$val = $this->s__datos[$etiq];
			}
		} else {
			$val = $this->s__datos;
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
		$this->s__datos[] = array('etiq' => $etiq, 'valor' => $valor);
	}

	/*
	 * Agrega un arreglo de datos 'crudo' a los datos del comprobante
	 * Usualmente usada para pasar datos a objetos que no son forms
	 */
	function set_datos($datos=array())
	{
		$this->s__datos = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null,null,'vista_xslfo', null, [{$this->objeto_js}._id])); 
			return false;
		}
		";
	}

}
?>