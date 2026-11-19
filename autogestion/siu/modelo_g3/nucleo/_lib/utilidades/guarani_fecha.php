<?php
/**
 * Manipulación de fechas.
 * 
 * @package Centrales
 * @subpackage Utilidades
 */
class guarani_fecha
{
	/**
	 * Obtiene la fecha actual de la base.
	 * Opcionalmente puede recibir un parámetro indicando qué
	 * se desea formatearla para mostrarla en forma visual.
	 *
	 * @param boolean $formato_visual Determina si se desea o no recibir la fecha formateada.
	 */
	static function get_hoy($formato_visual = false)
	{
		if (!$formato_visual) {
			$sql = 'SELECT ' . guarani_sql::fecha_actual . ' as fecha';
		} else {
			$sql = 'SELECT to_char(' . guarani_sql::fecha_actual . ", '" . comunes::formato_fecha_visual . "')  as fecha";
		}
		$result = guarani_db::consultar_fila($sql);
		return $result['fecha'];
	}

	/**
	 * Obtiene una parte de la fecha actual de la base.
	 *
	 * @param str $parte Determina qué parte de la fecha se desea.
	 * 					  Los valores pueden ser: 'dia', 'hora', 'anio', 'hora', 'minuto' o 'segundo'.
	 */
	static function get_hoy_parte($parte)
	{
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
				$parte = 'hour';
				break;
			case 'minuto':
				$parte = 'minute';
				break;
			case 'segundo':
				$parte = 'second';
				break;
		}
		$sql = "SELECT EXTRACT($parte FROM " . guarani_sql::fecha_hora_actual . ")::integer as parte";

		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['parte'];
	}

	/**
	 * Obtiene la fecha y hora actual de la base.
	 * Opcionalmente puede recibir un parámetro indicando qué
	 * se desea formatearla para mostrarla en forma visual.
	 *
	 * @param boolean $formato_visual Determina si se desea o no recibir la fecha y hora formateadas.
	 */
	static function get_ahora($formato_visual = false)
	{
		if (!$formato_visual) {
			$sql = 'SELECT ' . guarani_sql::fecha_hora_actual . ' as ahora';
		} else {
			$sql = "SELECT to_char(" . guarani_sql::fecha_hora_actual . ", '" . comunes::formato_fecha_hora_visual . "') as ahora";
		}
		$result = guarani_db::consultar_fila($sql);
		return $result['ahora'];
	}

	/**
	 *  Retorna el String necesario para crear un objeto de tipo Date en JS con la fecha actual
	 * @return <type>
	 */
	static function get_hoy_js()
	{
		$dia = static::get_hoy_parte('dia');
		$mes = static::get_hoy_parte('mes') - 1;
		$anio = static::get_hoy_parte('anio');
		return "Date($anio, $mes, $dia)";
	}

	static function es_mayor($fecha1, $fecha2, $igual = false, $timestamp = false)
	{
		if (!empty($fecha1) && !empty($fecha2)) {
			$fecha1 = toba::db()->quote($fecha1);
			$fecha2 = toba::db()->quote($fecha2);
			$tipo = $timestamp ? 'TIMESTAMP' : 'DATE';
			$igual ? $operador = ' >= ' : $operador = ' > ';
			$sql = "SELECT ($tipo $fecha1 $operador $tipo $fecha2) as es_mayor";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_mayor'];
		}
	}

	static function es_mayor_igual($fecha1, $fecha2)
	{
		return static::es_mayor($fecha1, $fecha2, true);
	}

	static function es_mayor_timestamp($fecha1, $fecha2)
	{
		return static::es_mayor($fecha1, $fecha2, false, true);
	}

	static function es_mayor_igual_timestamp($fecha1, $fecha2)
	{
		return static::es_mayor($fecha1, $fecha2, true, true);
	}

	static function es_menor($fecha1, $fecha2, $igual = false, $timestamp = false)
	{
		if (!empty($fecha1) && !empty($fecha2)) {
			$fecha1 = toba::db()->quote($fecha1);
			$fecha2 = toba::db()->quote($fecha2);
			$tipo = $timestamp ? 'TIMESTAMP' : 'DATE';
			$igual ? $operador = ' <= ' : $operador = ' < ';
			$sql = "SELECT ($tipo $fecha1 $operador $tipo $fecha2) as es_menor";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_menor'];
		}
	}

	static function es_menor_a_fecha_actual($fecha1, $igual = false)
	{
		if (!empty($fecha1)) {
			$fecha1 = toba::db()->quote($fecha1);
			$igual ? $operador = ' <= ' : $operador = ' < ';
			$sql = "SELECT (DATE $fecha1 $operador " . guarani_sql::fecha_actual . ") as es_menor";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_menor'];
		}
	}
	
	static function es_mayor_a_fecha_actual($fecha1, $igual = false)
	{
		if (!empty($fecha1)) {
			$fecha1 = toba::db()->quote($fecha1);
			$igual ? $operador = ' >= ' : $operador = ' > ';
			$sql = "SELECT (DATE $fecha1 $operador " . guarani_sql::fecha_actual . ") as es_mayor";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_mayor'];
		}
	}

	static function es_menor_igual($fecha1, $fecha2)
	{
		return static::es_menor($fecha1, $fecha2, true);
	}

	static function es_menor_igual_a_fecha_actual($fecha1)
	{
		return static::es_menor_a_fecha_actual($fecha1, true);
	}
	
	static function es_mayor_igual_a_fecha_actual($fecha1)
	{
		return static::es_mayor_a_fecha_actual($fecha1, true);
	}

	static function es_menor_timestamp($fecha1, $fecha2)
	{
		return static::es_menor($fecha1, $fecha2, false, true);
	}

	static function es_igual($fecha1, $fecha2)
	{
		if (!empty($fecha1) && !empty($fecha2)) {
			$fecha1 = toba::db()->quote($fecha1);
			$fecha2 = toba::db()->quote($fecha2);

			$sql = "SELECT (DATE $fecha1 = DATE $fecha2) as es_igual";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_igual'];
		}
	}

	static function esta_entre_fechas($fecha, $fecha_desde, $fecha_hasta)
	{
		if (!empty($fecha) && !empty($fecha_desde) && !empty($fecha_hasta)) {
			$fecha = toba::db()->quote($fecha);
			$fecha_desde = toba::db()->quote($fecha_desde);
			$fecha_hasta = toba::db()->quote($fecha_hasta);
			$sql = "SELECT (DATE $fecha BETWEEN DATE $fecha_desde AND DATE $fecha_hasta) as esta_entre";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['esta_entre'];
		}
	}

	/**
	 * Determina si el TimeStamp fecha_hora se encuentra dentro del rango de los otros dos TimeStamps fecha_hora_desde y 
	 * fecha_hora_hasta. La comparación se realiza sin considerar los segundos (sólo fecha-hora-minutos).
	 *
	 */
	static function esta_entre_fechas_horas($fecha_hora, $fecha_hora_desde, $fecha_hora_hasta)
	{
		if (!empty($fecha_hora) && !empty($fecha_hora_desde) && !empty($fecha_hora_hasta)) {
			$fecha_hora = toba::db()->quote($fecha_hora);
			$fecha_hora_desde = toba::db()->quote($fecha_hora_desde);
			$fecha_hora_hasta = toba::db()->quote($fecha_hora_hasta);
			$sql = "SELECT (date_trunc('MINUTE', TIMESTAMP $fecha_hora) BETWEEN date_trunc('MINUTE', TIMESTAMP $fecha_hora_desde) AND date_trunc('MINUTE', TIMESTAMP $fecha_hora_hasta)) as esta_entre";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['esta_entre'];
		}
	}

	/**
	 * Obtiene una nueva fecha, a partir de $fecha desplazada en $cant, 
	 * ya sean días, meses o años.
	 * Por defecto, días.
	 */
	static function get_fecha_desplazada($fecha, $cant, $parte = 'dia')
	{
		$fecha = toba::db()->quote($fecha);

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
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['fecha'];
	}

	static function get_fecha_hora_desplazada($fecha_hora, $cant, $parte = 'minuto')
	{
		$fecha_hora = toba::db()->quote($fecha_hora);
		switch ($parte) {
			case 'minuto':
				$parte = 'minute';
				break;
		}
		$sql = "SELECT ($fecha_hora::timestamp + interval '$cant $parte')::timestamp as fecha_hora";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['fecha_hora'];
	}

	/**
	 * Por defecto devuelve la fecha de la base.
	 * Opcionalmente se le puede pedir que parte de la fecha se necesita.
	 */
	static function get_parte($fecha, $parte)
	{
		$fecha = toba::db()->quote($fecha);

		switch ($parte) {
			case 'dia' :
				$parte = 'day';
				break;
			case 'mes':
				$parte = 'month';
				break;
			case 'año';
			case 'anio':
				$parte = 'year';
				break;
		}

		$sql = "SELECT EXTRACT($parte FROM DATE $fecha)::integer as parte";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['parte'];
	}

	/**
	 * Dada una fecha, retorna la edad en años a la fecha actual.
	 */
	static function get_edad($fecha)
	{
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT EXTRACT(YEAR FROM age(DATE $fecha))::integer as edad";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['edad'];
	}

	/**
	 *  Valida si el rango [Desde 1, Hasta 1] se solapa con el rango [Desde 2, Hasta 2]
	 */
	static function rango_solapado_en_rango($fecha_desde1, $fecha_hasta1, $fecha_desde2, $fecha_hasta2)
	{
		$fecha_desde1 = toba::db()->quote($fecha_desde1);
		$fecha_desde2 = toba::db()->quote($fecha_desde2);
		if (!empty($fecha_hasta1)) {
			$fecha_hasta1 = toba::db()->quote($fecha_hasta1);
		}

		if (empty($fecha_hasta1) && empty($fecha_hasta2)) {
			return true;
		} elseif (empty($fecha_hasta1)) {
			$sql = "SELECT DATE $fecha_desde1 <= DATE $fecha_desde2";
		} elseif (empty($fecha_hasta2)) {
			$sql = "SELECT DATE $fecha_hasta1 >= DATE $fecha_desde2";
		} else {
			$fecha_hasta2 = toba::db()->quote($fecha_hasta2);
			$sql = "SELECT
						($fecha_desde2 BETWEEN DATE $fecha_desde1 AND DATE $fecha_hasta1)
						OR
						($fecha_hasta2 BETWEEN DATE $fecha_desde1 AND DATE $fecha_hasta1)
						OR
						(DATE $fecha_desde2 <= DATE $fecha_desde1 AND DATE $fecha_hasta2 >= DATE $fecha_hasta1)
			";
		}
		$sql .= ' as solapa_rango';
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['solapa_rango'];
	}

	/**
	 * Valida si el rango [Desde 1, Hasta 1] está incluído dentro del rango [Desde 2, Hasta 2], considerando que alguna
	 * de las fechas del primer rango pueden ser nulas.
	 */
	static function rango_incluido_en_rango($fecha_desde1, $fecha_hasta1, $fecha_desde2, $fecha_hasta2)
	{
		$fecha_desde2 = toba::db()->quote($fecha_desde2);
		$fecha_hasta2 = toba::db()->quote($fecha_hasta2);
		$fecha_desde1 = toba::db()->quote($fecha_desde1);
		$fecha_hasta1 = toba::db()->quote($fecha_hasta1);
		$sql = "SELECT DATE $fecha_desde2 <= DATE $fecha_desde1 AND DATE $fecha_hasta2 >= DATE $fecha_hasta1 as incluye_rango";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['incluye_rango'];
	}

	static function separar_fecha_hora($timestamp)
	{
		$timestamp = toba::db()->quote($timestamp);
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);
		$formato_fecha_bd = toba::db()->quote(comunes::formato_fecha_bd);

		$sql = "SELECT	TO_CHAR(TIMESTAMP $timestamp, $formato_fecha_bd) as fecha,
						TO_CHAR(TIMESTAMP $timestamp, $formato_hora_visual) as hora
		";
		$rs = guarani_db::consultar_fila($sql, null, false);

		return array($rs['fecha'], $rs['hora']);
	}

	static function get_timestamp_zone_de_fecha($fecha)
	{
		$fecha = toba::db()->quote($fecha);

		$sql = "SELECT TIMESTAMP WITH TIME ZONE $fecha as fecha_hora";
		$rs = guarani_db::consultar_fila($sql, null, false);

		return $rs['fecha_hora'];
	}

	/**
	 * Día de la semana de correspondiente a una fecha
	 * 
	 * @param string $fecha
	 * @return int 0: domingo, 1: lunes, ... 6: sábado
	 */
	static function dia_de_la_semana($fecha)
	{
		if (!empty($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$sql = "SELECT EXTRACT (DOW FROM DATE $fecha) AS result";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['result'];
		}
	}
	
	static function convertir_fecha_formato_base($fecha) 
	{
		$date = date_parse_from_format('d/m/Y', $fecha);
		return $date['year'] . '-' . $date['month'] . '-' . $date['day'];
	}
	
	static function convertir_fecha_formato_frontend($fecha) 
	{
		$date = date_parse_from_format('Y-m-d', $fecha);
		return $date['day'] . '/' . $date['month'] . '/' . $date['year'];
	}

	/*
	 * @see http://php.net/manual/es/function.checkdate.php
	 */
	static function validar_fecha($fecha, $formato = 'd/m/Y')
	{
		$d = DateTime::createFromFormat($formato, $fecha);
		return $d && $d->format($formato) == $fecha;
	}
	
	static function validar_fecha_inicio_fin($fecha_inicio, $fecha_fin, $formato = 'd/m/Y')
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
}