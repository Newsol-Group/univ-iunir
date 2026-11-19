<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\consultas_bd\entidad_g3;


class continentes extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'continente, nombre',
		'from' => 'mug_continentes',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'continente',
		'campo_nombre' => 'nombre'
	);

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}

}
?>