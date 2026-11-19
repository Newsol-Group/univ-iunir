<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_extensiones;

class extensiones extends _consultas_bd implements interface_extensiones
{
	protected static $instancia;

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}

	//--------------------------------------------------------------------------
	//---- Implementación interface_extensiones --------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene las extensiones aceptadas para los archivos RDI
	 * 
	 * @return array
	 */
	function get_extensiones_permitidas()
	{
		$sql = "SELECT
					LOWER(extension) AS extension
				FROM 
					rdi_extensiones_permitidas
				";
		
		return $this->consultar($sql);
	}

	function test()
	{
		// Se testean las funciones particualres
		try {
			$datos = array();
			$datos['extensiones'] = $this->get_extensiones_permitidas();
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}

}
?>