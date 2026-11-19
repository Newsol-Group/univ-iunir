<?php
namespace siu\operaciones\_comun\util;
use siu\errores\error_guarani;

class forms 
{
	const SELECT_ID_NULO = '';
	
	static function rs_to_select($datos, $opcion_nula='')
	{
		if(!isset($datos[0]['id'])||!isset($datos[0]['desc'])) {
			throw new error_guarani('RS to SELECT: falta campo id o desc');
		}
		if($opcion_nula) {
			$array_resultado = array(self::SELECT_ID_NULO => $opcion_nula);
		}
		$cant = \guarani_arreglos::count($datos);
		for($i = 0; $i < $cant; $i++){
			$array_resultado[$datos[$i]['id']] = $datos[$i]['desc'];
		}
		return $array_resultado;	
	}
}

?>