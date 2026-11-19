<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class dias_no_laborables extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'fecha',
		'from' => 'sga_dias_no_laborables',
		'where' => '',
		'order_by' => 'fecha',
		'clave' => 'fecha',
		'campo_nombre' => 'fecha'
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