<?php

class guarani_mensajes_nucleo
{
	static function get($indice, $parametros=null, $escapear=true)
	{
		$rs = static::get_msg_info($indice, $parametros, $escapear);
		return ($rs === null) ? 'Falta el mensaje para el índice: '.$indice : $rs['mensaje'];
	}

	static function get_msg_info($indice, $parametros=null, $escapear=true)
	{

		$from = 'apex_msg';
		if(defined("SCHEMA_TOBA")) $from = SCHEMA_TOBA.".".$from;

		$sql = "SELECT	COALESCE(apex_msg.mensaje_customizable, apex_msg.mensaje_a) as mensaje,
						msg_tipo as tipo
				FROM 	$from
				WHERE	UPPER(trim(apex_msg.indice)) = UPPER('$indice')
				AND 	apex_msg.proyecto = 'guarani'
						;";
		$rs = toba_instancia::instancia()->get_db()->consultar_fila($sql);
		if (empty($rs['mensaje'])) {
			return null;
		}
		$msj = toba::mensajes()->parsear_parametros($rs['mensaje'], $parametros);		
		return array('mensaje' => $msj, 'tipo' => $rs['tipo']);
	}
}

?>