<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class abandono_estudio extends entidad_g3
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'razon_abandono, nombre, orden',
		'from' => 'mdp_estudio_abandono',
		'where' => '',
		'order_by' => 'orden',
		'clave' => 'razon_abandono',
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