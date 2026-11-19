<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class orientacion_vocacional extends entidad_g3
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'orientacion_recibida, nombre, orden',
		'from' => 'mdp_orientacion_recibida',
		'where' => '',
		'order_by' => 'orden',
		'clave' => 'orientacion_recibida',
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