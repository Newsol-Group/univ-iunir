<?php
/**
 * Catalogo de campos definidos con sus tipos de datos asociados.
 *
 * @package Campos
 * @subpackage Campos
 */
class campo_nucleo
{
	// Tipos de Campos
	const tipo_fecha 		= 'D';
	const tipo_entero 		= 'E';
	const tipo_decimal 		= 'F';
	const tipo_texto		= 'L';
	const tipo_cadena 		= 'S';
	const tipo_hora 		= 'T';
	const tipo_fecha_hora	= 'X';

	static function validar_campo_valor($campo, $valor)
	{	
		// Se verifica que exista el campo dado asociado al parámetro a validar, y se recuperan sus datos.
		$datos_campo = current(toba::consulta_php('co_campos')->get_datos_campo($campo));
		// Se valida el valor del campo de acuerdo al tipo de datos del mismo.
		if (!empty($datos_campo)) {
			switch($datos_campo['tipo_de_dato']) {
				case self::tipo_fecha:			//"Fecha";"yyyy-mm-dd";"Date"
					static::validar_campo_tipo_fecha($datos_campo['etiqueta'], $valor);
					break;
				case self::tipo_entero:		//"Entero";"##########";"Integer - Bigint - smallint"
					static::validar_campo_tipo_entero($datos_campo['etiqueta'], $valor);
					break;
				case self::tipo_decimal:		//"Decimal - Punto Flotante - Real";"#########.####";"Real - Float - Decimal"
					static::validar_campo_tipo_decimal($datos_campo['etiqueta'], $valor);
					break;
				case self::tipo_texto:			//"Cadena de Caracteres";"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";"Char - Varchar"
					// No hay validación.
					break;
				case self::tipo_cadena:		//"Cadena de Caracteres";"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";"Char - Varchar"
					// No hay validación.
					break;
				case self::tipo_hora:			//"Hora";"hh:mm";"Time"
					static::validar_campo_tipo_hora($datos_campo['etiqueta'], $valor);
					break;
				case self::tipo_fecha_hora:	//"Fecha y Hora";"yyyy-mm-dd hh:mm";"Datetime"
					static::validar_campo_tipo_fecha_hora($datos_campo['etiqueta'], $valor);	
					break;
			}
		
		} else {
			throw guarani::error('El campo <b>' . $campo . '</b> no está definido en la base');
		}
	}
	
	static function validar_campo_tipo_fecha($campo, $valor)
	{
		if (!guarani_validar::es_fecha($valor)) {
			throw guarani::error('El valor del parámetro <B>'. $campo .'</B> debe ser una fecha válida (AAAA-MM-DD).');
		}
	}
	
	static function validar_campo_tipo_entero($campo, $valor)
	{
		if (!guarani_validar::es_entero($valor)) {
			throw guarani::error('El valor del parámetro <B>'. $campo .'</B> debe ser un entero.');
		}	
	}
	
	static function validar_campo_tipo_decimal($campo, $valor)
	{
		if (!guarani_validar::es_decimal($valor)) {
			throw guarani::error('El valor ('.$valor.') del parámetro <B>'. $campo . '</B> debe ser un entero o un decimal.');
		}	
	}
	
	static function validar_campo_tipo_hora($campo, $valor)
	{
		if (!guarani_validar::es_hora_minutos($valor)) {
			throw guarani::error('El valor del parámetro <B>'. $campo . '</B> debe ser una hora válida (hh:mm:ss).');
		}	
	}
	
	static function validar_campo_tipo_fecha_hora($campo, $valor)
	{
		if (!guarani_validar::es_fecha_hora_minutos($valor)) {
			throw guarani::error('El valor del parámetro <B>'. $campo .'</B> debe ser una fecha y hora válida (DD/MM/YYYY hh:mm:ss).');
		}
	}
}

?>