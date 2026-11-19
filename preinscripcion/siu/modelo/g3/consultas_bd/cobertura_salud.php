<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class cobertura_salud extends entidad_g3
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'cobertura_salud, descripcion',
		'from' => 'mdp_cobertura_salud',
		'where' => '',
		'order_by' => '',
		'clave' => 'cobertura_salud',
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