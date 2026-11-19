<?php

/**
 * Clase para generar, crear y obtener pdfs a traves de los servicios provistos por Toba.
 * @package Centrales  
 */
class guarani_pdf_nucleo
{
	protected $archivo;
	protected $objetos;
	
	/**
	 * Constructor de la clase
	 * @param string $nombre_archivo nombre del archivo q tendrá el pdf generado
	 * @param array  $objetos Arreglo de objetos utilizados para la creacion del pdf
	 */
	function __construct($objetos, $nombre_archivo = 'archivo.pdf')
	{
		$this->objetos = $objetos;
		$this->archivo = $nombre_archivo;
	}
	
	/**
	 * Genera el pdf a partir de los parametros indicados en la construccion del objeto
	 * Por compatibilidad, por defecto se llama a la salida FOP
	 * @return string (0 o nombre_archivo PDF)
	 */
	function generar($jasper=false)
	{
		$res = 0;

		if ($jasper) {
			$res = $this->generar_con_jasper();
		} else {
			$this->generar_con_fop();
		}

		return $res;
	}


	/**
	 * Genera el pdf a partir de los parametros indicados en la construccion del objeto con XSL-FO
	 */
	function generar_con_fop()
	{
		$fo = new toba_vista_xslfo();
		$fo->asignar_objetos($this->objetos);
		$fo->set_nombre_archivo($this->archivo);
		$fo->generar_salida();
	}


	/**
	 * Genera el pdf a partir de los parametros indicados en la construccion del objeto con JASPER
	 */
	function generar_con_jasper()
	{
		$fo = new toba_vista_jasperreports();
		$fo->asignar_objetos($this->objetos);
		$fo->generar_salida();
		return $fo->get_nombre_archivo_generado();
	}

}

?>
