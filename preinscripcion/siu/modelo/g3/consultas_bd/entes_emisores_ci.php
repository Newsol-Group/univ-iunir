<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class entes_emisores_ci extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'entidad, nombre',
		'from' => 'mdp_entes_emisores_ci',
		'where' => '',
		'order_by' => 'entidad',
		'clave' => 'entidad',
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