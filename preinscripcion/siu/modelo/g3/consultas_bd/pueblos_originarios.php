<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class pueblos_originarios extends entidad_g3
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'pueblo_originario, nombre',
		'from' => 'mdp_pueblos_originarios',
		'where' => '',
		'order_by' => '',
		'clave' => 'pueblo_originario',
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