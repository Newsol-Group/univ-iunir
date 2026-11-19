<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class turnos_cursada extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'turno, nombre',
		'from' => 'sga_turnos_cursadas',
		'where' => '',
		'order_by' => 'turno',
		'clave' => 'turno',
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