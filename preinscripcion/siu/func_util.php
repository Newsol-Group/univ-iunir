<?php
/**
 * Toma una matriz y lo aplana a una sola dimension, si no se especifica un campo, se elige el primero
 * Util para aplanar recordset de consultas de un solo campo
 * Ej: array(0 => array('campo' => 'cero'), 1 => array('campo' => 'uno'))  --->  array('cero', 'uno')
 * 
 */
function aplanar_matriz($matriz, $campo = null)
{
	$aplanado = array();
	foreach ($matriz as $clave => $arreglo) {
		if ($campo === null) {
			$aplanado[$clave] = current($arreglo);
		} elseif (isset($arreglo[$campo])) {
			$aplanado[$clave] = $arreglo[$campo];
		}
	}
	return $aplanado;
}

//------------------------------------------------------------------------------
//---- Funciones de rest -------------------------------------------------------
//------------------------------------------------------------------------------

/** 
 * Transforma un json o arreglo en utf8 a un arreglo en latin1 
 */
function rest_decode($datos)
{
	if (is_string($datos)) {
		$datos = json_decode($datos, true);
	} //es un json ya decodificada guzzle->response->json
	return array_a_latin1($datos);
}

/** 
 * Transforma un arreglo en latin1 a un json en utf8 
 */
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

function array_a_utf8($datos)
{
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

//------------------------------------------------------------------------------
//---- Funciones de log --------------------------------------------------------
//------------------------------------------------------------------------------

function klog($var)
{
	static $_log_count = 0;
	SIU\Chulupi\kernel::log()->add_debug("LOG #$_log_count", $var);
	$_log_count++;
}

function klog2($nombre, $var)
{
	SIU\Chulupi\kernel::log()->add_debug($nombre, $var);
}

//------------------------------------------------------------------------------
//---- Funciones de fechas -----------------------------------------------------
//------------------------------------------------------------------------------

function convertir_fecha_formato_base($fecha)
{
	$date = \date_parse_from_format('d/m/Y', $fecha);
	return $date['year'] . '-' . $date['month'] . '-' . $date['day'];
}

function convertir_fecha_formato_frontend($fecha)
{
	$date = \date_parse_from_format('Y-m-d', $fecha);
	return $date['day'] . '/' . $date['month'] . '/' . $date['year'];
}

/**
 * Obtiene una nueva fecha, a partir de $fecha desplazada en $cant, 
 * ya sean días, meses o años.
 * Por defecto, año.
 * 
 */
function get_fecha_desplazada($fecha, $cant, $parte = 'anio')
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

	$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
	return $rs['fecha'];
}

/**
 * Obtiene una nueva fecha, a partir de $fecha desplazada en $cant, 
 * ya sean horas, días, meses o años.
 * Por defecto, año.
 * 
 */
function get_fecha_hora_desplazada($fecha, $cant, $parte = 'anio')
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
                case 'hora':
			$parte = 'hours';
			break;
	}

	$sql = "SELECT ($fecha::TIMESTAMP + interval '$cant $parte')::TIMESTAMP as fecha";

	$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
	return $rs['fecha'];
}

function fecha_es_menor($fecha1, $fecha2, $igual = false, $timestamp = false)
{
	if (!empty($fecha1) && !empty($fecha2)) {
		$fecha1 = SIU\Chulupi\kernel::db()->quote($fecha1);
		$fecha2 = SIU\Chulupi\kernel::db()->quote($fecha2);
		$tipo = $timestamp ? 'TIMESTAMP' : 'DATE';
		$igual ? $operador = ' <= ' : $operador = ' < ';
		$sql = "SELECT ($tipo $fecha1 $operador $tipo $fecha2) as es_menor";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_menor'];
	}
}

function fecha_es_menor_a_fecha_actual($fecha1, $igual = false)
{
	if (!empty($fecha1)) {
		$fecha1 = SIU\Chulupi\kernel::db()->quote($fecha1);
		$igual ? $operador = ' <= ' : $operador = ' < ';
		$sql = "SELECT (DATE $fecha1 $operador CURRENT_DATE) as es_menor";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_menor'];
	}
}

function fecha_es_mayor_a_fecha_actual($fecha1, $igual = false)
{
	if (!empty($fecha1)) {
		$fecha1 = SIU\Chulupi\kernel::db()->quote($fecha1);
		$igual ? $operador = ' >= ' : $operador = ' > ';
		$sql = "SELECT (DATE $fecha1 $operador CURRENT_DATE) as es_mayor";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_mayor'];
	}
}

function fecha_siguiente($fecha)
{
	if (!empty($fecha)) {
		$fecha = SIU\Chulupi\kernel::db()->quote($fecha);
		$sql = "SELECT CAST((DATE $fecha + interval '1 DAYS') AS DATE) as result";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['result'];
	}
}

function dia_de_la_semana($fecha)
{
	if (!empty($fecha)) {
		$fecha = SIU\Chulupi\kernel::db()->quote($fecha);
		$sql = "SELECT EXTRACT (DOW FROM DATE $fecha) AS result";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['result'];
	}
}

/**
 * Valida fecha en formato 'd/m/Y'.
 *
 * @param string $fecha     Fecha en el formato 'd/m/Y'.
 *
 * @return boolean TRUE si la fecha es válida, FALSE en caso contrario.
 */
function es_fecha_valida($fecha)
{
    $dateTime = \DateTime::createFromFormat(\siu\modelo\comunes::formato_fecha_visual_php, $fecha);
    // Si la fecha es inválida
    if ($dateTime === false) {
        return false;
    }

    $fragmentos = explode("/", $fecha);
    $day = $fragmentos[0];
    $month = $fragmentos[1];
    $year = $fragmentos[2];
    // Si la fecha es inválida, ej: 30/02/2023 es inválida, tiene en cuenta años bisiestos
    if (!checkdate($month, $day, $year)) {
        return false;
    }

    return true;
}
//------------------------------------------------------------------------------
//---- Funciones de hora -------------------------------------------------------
//------------------------------------------------------------------------------

function hora_es_menor($hora1, $hora2, $igual = false)
{
	if (!empty($hora1) && !empty($hora2)) {
		$hora1 = SIU\Chulupi\kernel::db()->quote($hora1);
		$hora2 = SIU\Chulupi\kernel::db()->quote($hora2);
		$tipo = 'TIME';
		$igual ? $operador = ' <= ' : $operador = ' < ';
		$sql = "SELECT ($tipo $hora1 $operador $tipo $hora2) as es_menor";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['es_menor'];
	}
}

function minutos_entre_horas($hora1, $hora2)
{
	if (!empty($hora1) && !empty($hora2)) {
		$hora1 = SIU\Chulupi\kernel::db()->quote($hora1);
		$hora2 = SIU\Chulupi\kernel::db()->quote($hora2);
		$sql = "SELECT EXTRACT (EPOCH FROM (TIME $hora1 - TIME $hora2) ) / 60 AS minutos";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['minutos'];
	}
}

function sumar_minutos_a_hora($hora1, $minutos)
{
	if (!empty($hora1)) {
		$hora1 = SIU\Chulupi\kernel::db()->quote($hora1);
		$sql = "SELECT (TIME $hora1 + interval '$minutos MINUTES') as result";
		$rs = SIU\Chulupi\kernel::db()->consultar_fila($sql);
		return $rs['result'];
	}
}

function convertir_hora_formato_frontend($hora)
{
	$datos_hora = explode(':', $hora);
	return $datos_hora[0] . ':' . $datos_hora[1];
}

//------------------------------------------------------------------------------
//---- Funciones de cadenas ----------------------------------------------------
//------------------------------------------------------------------------------

function a_minusculas($cadena)
{
	$cadena = trim($cadena);
	return mb_convert_case($cadena, MB_CASE_LOWER, SIU\Chulupi\kernel::proyecto()->get_encoding());
}

function a_mayusculas($cadena)
{
	$cadena = trim($cadena);
	return mb_convert_case($cadena, MB_CASE_UPPER, SIU\Chulupi\kernel::proyecto()->get_encoding());
}

function capitalizar($cadena)
{
	return mb_convert_case(a_minusculas($cadena), MB_CASE_TITLE, SIU\Chulupi\kernel::proyecto()->get_encoding());
}

function formatear($cadena)
{
	$formateo = SIU\Chulupi\kernel::proyecto()->get_formateo_campos();
	switch ($formateo) {
		case 'mayusculas':
			$salida = a_mayusculas($cadena);
			break;
		case 'capitalizar':
			$salida = capitalizar($cadena);
			break;
		case 'libre':
		default:
			$salida = $cadena;
			break;
	}
	return $salida;
}

//------------------------------------------------------------------------------
//---- Funciones de imágenes ---------------------------------------------------
//------------------------------------------------------------------------------

/**
 * Determina si un archivo en un path consignado es una imagen
 * 
 * @param string $path
 * @return boolean
 */
function es_imagen($path)
{
	return (@is_array(getimagesize($path)));
}