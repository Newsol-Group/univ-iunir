<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class tipos_vivienda extends entidad_g3
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'tipo_vivienda, descripcion, orden',
		'from' => 'mdp_tipo_vivienda',
		'where' => '',
		'order_by' => 'orden',
		'clave' => 'tipo_vivienda',
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