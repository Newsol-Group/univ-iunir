<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\util;

class identidad_genero extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'mdp_identidad_genero.identidad_genero as valor,
	   						mdp_identidad_genero.nombre as descr',
		'from' => 'mdp_identidad_genero',
		'where' => '',
		'clave' => 'valor',
		'campo_nombre' => 'descr'
	);

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	/**
	 * Listado de opciones.
	 * 
	 * @param string $condiciones
	 * @param boolean $con_no_seleccion
	 */
	function get_opciones($condiciones = null, $con_no_seleccion = false)
	{
		$sql = $this->armar_sql($condiciones);
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, $this->sql['clave'], $this->sql['campo_nombre'], $con_no_seleccion);
	}
}