<?php

include_once('toba_varios.php');

class g3w_autoload_adaptador
{
	static function existe_clase($nombre)
	{
		return isset(self::$clases[$nombre]);
	}

	static function cargar($nombre)
	{
		if (static::existe_clase($nombre)) { require_once(dirname(__FILE__) .'/'. self::$clases[$nombre]); }
	}

	static $clases = array(
		'toba' => 'toba.php',
		'toba_perfil_datos' => 'toba_perfil_datos.php',
		'toba_consulta' => 'toba_consulta.php',
		'toba_logger' => 'toba_logger.php',
		'toba_error' => 'toba_error.php',
		'notificacion_controles' => 'notificacion_controles.php',
		'toba_instancia' => 'toba_instancia.php',
		'toba_mensajes' => 'toba_mensajes.php',
		'toba_js' => 'toba_js.php',
		'toba_manejador_sesiones' => 'toba_manejador_sesiones.php',
		'toba_vista_jasperreports' => 'toba_vista_jasperreports.php',
		'toba_memoria' => 'toba_memoria.php',
	);
}