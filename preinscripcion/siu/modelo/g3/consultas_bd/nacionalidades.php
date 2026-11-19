<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class nacionalidades extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'nacionalidad, descripcion',
		'from' => 'mdp_nacionalidades',
		'where' => '',
		'order_by' => 'nacionalidad',
		'clave' => 'nacionalidad',
		'campo_nombre' => 'descripcion'
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