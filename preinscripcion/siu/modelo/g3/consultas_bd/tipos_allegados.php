<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class tipos_allegados extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'tipo_allegado, descripcion',
		'from' => 'mdp_tipo_allegado',
		'where' => '',
		'order_by' => 'tipo_allegado',
		'clave' => 'tipo_allegado',
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