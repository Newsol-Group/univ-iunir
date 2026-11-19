<?php

function klog($var)
{
	static $_log_count = 0;
	\SIU\Chulupi\kernel::log()->add_debug("LOG #$_log_count", $var);
	$_log_count++;
}

function klog2($nombre, $var)
{
	\SIU\Chulupi\kernel::log()->add_debug($nombre, $var);
}

function ml_altas($data)
{
	return array_filter($data, function($fila) {
		return $fila[analisis_columna] == analisis_alta;
	});
}

function ml_modif($data)
{
	return array_filter($data, function($fila) {
		return $fila[analisis_columna] == analisis_modificacion;
	});
}
function ml_baja($data)
{
	return array_filter($data, function($fila) {
		return $fila[analisis_columna] == analisis_baja;
	});
}

function trans($id,$params=array())
{
	return \SIU\Chulupi\kernel::traductor()->trans($id, $params);
}

/**
 * Alias de kernel::localizador()->invocacion_estatica. Se utiliza para realizar
 * una llamada estática a una clase que puede estar personalizada
 * @param string $clase
 * @param string $metodo
 * @param mixed $params
 * @return mixed
 */
function estatica($clase, $metodo, $params = array())
{
	return \SIU\Chulupi\kernel::localizador()->invocacion_estatica($clase, $metodo,$params);
}

function constante($clase, $nombre)
{
	return \SIU\Chulupi\kernel::localizador()->constante($clase, $nombre);
}

function convertir_fecha_formato_base($fecha)
{
	$date = \date_parse_from_format('d/m/Y', $fecha);
	return $date['year'].'-'.$date['month'].'-'.$date['day'];
}

function convertir_fecha_formato_frontend($fecha)
{
	$date = \date_parse_from_format('Y-m-d', $fecha);
	$dia = ($date['day'] < 10) ? '0'.$date['day'] : $date['day'];
	$mes = ($date['month'] < 10) ? '0'.$date['month'] : $date['month'];
	$anio = $date['year'];
	return $dia.'/'.$mes.'/'.$anio;
}

function fecha_es_menor($fecha1, $fecha2, $igual=false, $timestamp=false)
{
	if (!empty($fecha1) && !empty($fecha2)) {
		$fecha1 = \SIU\Chulupi\kernel::db()->quote($fecha1);
		$fecha2 = \SIU\Chulupi\kernel::db()->quote($fecha2);
		$tipo = $timestamp ? 'TIMESTAMP' : 'DATE';
		$igual ? $operador = ' <= ' : $operador = ' < ';
		$sql = "SELECT ($tipo $fecha1 $operador $tipo $fecha2) as es_menor";
		$rs = \SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_menor'];
	}
}

function fecha_es_menor_a_fecha_actual($fecha1, $igual=false)
{
	if (!empty($fecha1)) {
		$fecha1 = \SIU\Chulupi\kernel::db()->quote($fecha1);
		$igual ? $operador = ' <= ' : $operador = ' < ';
		$sql = "SELECT (DATE $fecha1 $operador CURRENT_DATE) as es_menor";
		$rs = \SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_menor'];
	}
}

function fecha_es_mayor_a_fecha_actual($fecha1, $igual=false)
{
	if (!empty($fecha1)) {
		$fecha1 = \SIU\Chulupi\kernel::db()->quote($fecha1);
		$igual ? $operador = ' >= ' : $operador = ' > ';
		$sql = "SELECT (DATE $fecha1 $operador CURRENT_DATE) as es_mayor";
		$rs = \SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_mayor'];
	}
}

/**
 * Obtiene una nueva fecha, a partir de $fecha desplazada en $cant, 
 * ya sean días, meses o años.
 * Por defecto, año.
 * 
 */
function get_fecha_desplazada($fecha, $cant, $parte='anio')
{
	$fecha = SIU\Chulupi\kernel::db()->quote($fecha);

	switch ($parte) {
		case 'dia' :
			$parte = 'day';
			break;
		case 'mes':
			$parte = 'month';
			break;
		case 'anio':
			$parte = 'year';
			break;
	}

	$sql = "SELECT ($fecha::date + interval '$cant $parte')::date as fecha";
	
	$rs = \SIU\Chulupi\kernel::db()->consultar_fila($sql);
	return $rs['fecha'];
}

function a_minusculas($cadena)
{
	$cadena = trim($cadena);
	return mb_convert_case($cadena, MB_CASE_LOWER, \SIU\Chulupi\kernel::proyecto()->get_encoding());
}

function capitalizar($cadena)
{
	return mb_convert_case($cadena, MB_CASE_TITLE, \SIU\Chulupi\kernel::proyecto()->get_encoding());
}

/** Transforma un json o arreglo en utf8 a un arreglo en latin1 */
function rest_decode($datos)
{
    if(is_string($datos) ){
        $datos = json_decode($datos, true);
    } //es un json ya decodificada guzzle->response->json
    return (! is_null($datos)) ?  array_a_latin1($datos) : array();
}

/** Transforma un arreglo en latin1 a un json en utf8 */
function rest_encode($datos)
{
    $array = array_a_utf8($datos);
    return json_encode($array);
}

function array_a_latin1($arreglo)
{
    $salida = array();
    foreach ($arreglo as $clave => $valor) {
        if (is_array($valor)) {
            $salida[$clave] = array_a_latin1($valor);
        } elseif (is_string($valor)) {
            $salida[$clave] = utf8_decode($valor);
        } else {
            $salida[$clave] = $valor;
        }
    }
    return $salida;
}

function array_a_utf8($datos){
    if (is_string($datos)) {
        return utf8_encode($datos);
    }
    if (!is_array($datos)) {
        return $datos;
    }
    $ret = array();
    foreach ($datos as $i => $d) {
        $ret[$i] = array_a_utf8($d);
    }
    return $ret;
}

function pasar_a_unica_linea($string)
{
	return  preg_replace("/\r\n|\n/", "\\n", $string);
}

function convertir_hora_formato_frontend($hora)
{
	$datos_hora = explode(':', $hora);
	return $datos_hora[0] . ':' . $datos_hora[1];
}

/*
 * @see http://php.net/manual/es/function.checkdate.php
 */
function validar_fecha($fecha, $formato = 'd/m/Y')
{
    $d = DateTime::createFromFormat($formato, $fecha);
    return $d && $d->format($formato) == $fecha;
}

function validar_fecha_inicio_fin($fecha_inicio, $fecha_fin, $formato = 'd/m/Y')
{
	$validacion_ok = true;

	$fecha_inicio_obj = DateTime::createFromFormat("!{$formato}", $fecha_inicio);
	$fecha_fin_obj = DateTime::createFromFormat("!{$formato}", $fecha_fin);

	// Si la "fecha inicio" es mayor a la "fecha fin"
	if ($fecha_inicio_obj > $fecha_fin_obj) {
		$validacion_ok = false;
	}

	return $validacion_ok;
}

function validar_fecha_inicio_fin_con_hoy($fecha_inicio, $fecha_fin, $formato = 'd/m/Y')
{
	$validacion_ok = false;

	$hoy = new DateTime();
	$hoy->setTime(0, 0, 0);
	$fecha_inicio_obj = DateTime::createFromFormat("!{$formato}", $fecha_inicio);
	$fecha_fin_obj = DateTime::createFromFormat("!{$formato}", $fecha_fin);

	// Hoy debe estan entre "fecha inicio" y "fecha fin"
	if (($hoy >= $fecha_inicio_obj) && ($hoy <= $fecha_fin_obj)) {
		$validacion_ok = true;
	}

	return $validacion_ok;
}

function fecha_es_menor_a_fecha_actual_php($fecha, $formato = 'd/m/Y')
{
	$fecha_actual = new \DateTime('today');
        $fecha_obj = \DateTime::createFromFormat("!{$formato}", $fecha);
        return ($fecha_obj < $fecha_actual);
}