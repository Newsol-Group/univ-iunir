<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class ubicaciones extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'ubicacion, nombre',
		'from' => 'sga_ubicaciones',
		'where' => '',
		'order_by' => '',
		'clave' => 'ubicacion',
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