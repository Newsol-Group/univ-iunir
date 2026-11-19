<?php
/**
 *
 *
 * @package Unidad de Gestion
 * @subpackage Instituciones
 */
class institucion_nucleo
{
	static protected $instancia;
	static protected $co;
	static protected $datos_gestionante = array();
	
	// Nivel de colegios
	const colegio_nivel_inicial = 'I';
	const colegio_nivel_primario = 'P';
	const colegio_nivel_secundario = 'S';

	
	Private function construct() {}

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new institucion();	
		}
		return self::$instancia;
	}
	
	static function co() 
	{
		if (! isset(self::$co) ) {
			self::$co = toba::consulta_php('co_instituciones');
		}
		return self::$co;
	}
	
	static function get_datos_gestionante()
	{
		self::$datos_gestionante = current(static::co()->get_institucion_gestionante());
		return self::$datos_gestionante;
	}
	
	static function get_gestionante()
	{
		$datos = static::get_datos_gestionante();
		return $datos['institucion'];
	}
	
	static function get_entidad_gestionante()
	{
		$datos = static::get_datos_gestionante();
		return $datos['entidad'];
	}

}

?>
