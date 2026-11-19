<?php
namespace siu\modelo\datos;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\db\db;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;

class util
{
	static function ejecutar_procedure($sql)
	{
		try {
			$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
			if($datos[0] < 0){
				$mensaje = static::mensaje($datos[1]);
				throw new error_guarani($mensaje);
			} else {
				if($datos[1] != 'OK') {
					return static::mensaje($datos[1]);
				} else {
					return $datos[1];
				}
			}
		} catch (\Exception $e) {
			
		}
	}

	static function mensaje($mensaje)
	{
		kernel::log()->add_debug('MENSAJE',$mensaje);
		$temp = explode(',',$mensaje);
		// optimización, no es necesario parsear el mensaje xq es el mismo que viene
		// dp de la coma. No es necesario ir a la base
		if ($temp[0] == '800000' || $temp[0] == '-746') {
			return $temp[1];
		} else {
			$param['mensaje'] = $mensaje;
			$mensaje = catalogo::consultar('sistema', 'mensaje', $param);		
			return $mensaje[0];
		}
	}
	
	static function formato_null($parametros)
	{
		foreach($parametros as $key => $parametro)
			if (empty($parametro) || $parametro == "''")
				$parametros[$key] = 'null';
		return $parametros;
	}
	
	static function parsear_parametros_mensajes($param_string)
	{
		$parametros = explode('$$$', $param_string);
		$nuevo = array();
		$i = 1;
		foreach($parametros as $param) {
			$nuevo['%'.$i.'%'] = $param;
			$i++;
		}
		return $nuevo;
	}
	
	
	
	
	
}
?>