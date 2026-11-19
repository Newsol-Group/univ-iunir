<?php

/**
 * Métodos relacionados al armado de queries
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_sql
{
	
	const fecha_actual = 'CURRENT_DATE';
	const fecha_hora_actual = 'CURRENT_TIMESTAMP';
	const hora_actual = 'CURRENT_TIME';

	/**
	 * Dasdo un array asociativo ó array de arrays asociativos y una $clave, formatea para poder aplicarle un implode en armar_condicion_in
	 * (Sólo para ser usado desde armar_condicion_in)
	 * @param <type> $array
	 * @param <type> $clave
	 * @return <type>
	 */

	static private function _formatear_array($array, $clave)
	{
		if (is_array(current($array))) {
				if (array_key_exists($clave,current($array)))	{
					$array = aplanar_matriz($array, $clave);
				} else {
					throw new guarani_error("la clave no pertenece a los índices del array - armar_condicion_in");
				}
			} else {
				if (array_key_exists($clave,$array))	{
					$array = array($array[$clave]);
				} else {
					throw new guarani_error("la clave no pertenece a los índices del array - armar_condicion_in");
				}
			}
		return guarani_arreglos::matriz_sin_nulos($array);
	}

	/**
     *  Arma una condición de IN quoteada a partir de un array plano o
	 *	de un array asociativo que contiene los valores de un campo en
	 *	una clave asociativa.
	 *	
    */
	static function armar_condicion_in($array, $tabla='', $campo='', $clave='', $es_not=false)
	{
		// En caso que el array es vacio la condición es Verdadera
		if (!is_array($array)) {
			$array = array($array);
		}
		if (empty($array)) {
			return ($es_not ? ' true ' : ' false');
		}
		// Si el array no tiene formato de Carga Masiva de Columnas Externas de DT fomrateo el array
		if (!(is_int(key($array)) && !is_array(current($array))))  {
			//Si no tengo la clave, entonces utilizo 'campo' como 'clave'
			if ($clave == '') {
				$clave = $campo;
			}
			$array = static::_formatear_array($array,$clave);
			//Se verifica nuevamente que el arreglo no sea vacío, ya que puede haber quedado vacío por aplanarlo sin
			//considerar campos nulos. En este caso la condicion siempre es Verdadera
			if (empty($array)) {
				return ($es_not ? ' true ' : ' false');
			}
		} else {
			// en caso que tenga formato de carga masiva elimino los nulos
			$array = guarani_arreglos::matriz_sin_nulos($array);
			if (empty($array)) {
				return ($es_not ? ' true ' : ' false');
			}
		}
		$condicion = ' ';
		if ($tabla != '') {
			$condicion .= $tabla.'.';
		}
		$condicion .= $campo;
		if ($es_not) {
			$condicion .= ' NOT ';
		}
		$condicion .= ' IN ('.implode(',', toba::db()->quote($array)).') ';
		return $condicion;
	}
	
	/**
     *  Arma una condición de IN quoteada a partir de dos matrices de arrays asociativos (o de dos arrays) planos que contienen los valores 
     *  de pares de campos en dos claves asociativas (ninguno de estos campos puede ser nulo).
	 *	
     */
	static function armar_condicion_in_dupla($array1, $tabla1='', $campo1='', $clave1='', $array2, $tabla2='', $campo2='', $clave2='', $es_not=false)
	{
		$condicion = '';
		if (!empty($array1) && !empty($array2)) {
			if ($clave1 != '') {
				if (is_array(current($array1))) {
					$array1 = guarani_arreglos::aplanar_matriz_sin_nulos($array1, $clave1);
				} else {
					$array1 = array($array1[$clave1]);
				}
			}
			
			if ($clave2 != '') {
				if (is_array(current($array2))) {
					$array2 = guarani_arreglos::aplanar_matriz_sin_nulos($array2, $clave2);
				} else {
					$array2 = array($array2[$clave2]);
				}
			}
			
			$condicion .= ' ROW (';
			if ($tabla1 != '') {
				$condicion .= $tabla1.'.';
			}
			$condicion .= $campo1 . ',';
			if ($tabla2 != '') {
				$condicion .= $tabla2.'.';
			}
			$condicion .= $campo2 . ')';
			if ($es_not) {
				$condicion .= ' NOT ';
			}
			
			$array1 = toba::db()->quote($array1);
			$array2 = toba::db()->quote($array2);
			$condicion .= 'IN (';
			
			//Ambos arreglos tienen la misma cantidad de elementos porque ninguno de los campos puede ser nulo (y no son vacíos).
			while (current($array1) !== false) {
			 	$condicion .= '('.current($array1).','.current($array2).')';
				$condicion .= next($array1) !== false ? ',' : ')';
				next($array2);
			}
		}
		return $condicion;
	}
	
	
	/**
        Arma una condición de NOT IN quoteada a partir de un array asociativo
        que contiene los valores de un campo en una clave asociativa
    */
	static function armar_condicion_not_in($array, $tabla='', $campo='', $clave='')
	{
		return static::armar_condicion_in($array,$tabla,$campo,$clave,true);
	}

	/**
        Arma una condición de NOT IN quoteada a partir de un array asociativo
        que contiene los valores de dos campos en dos claves asociativas
    */
	static function armar_condicion_not_in_dupla($array1, $tabla1='', $campo1='', $clave1='', $array2, $tabla2='', $campo2='', $clave2='')
	{
		return static::armar_condicion_in_dupla($array1, $tabla1, $campo1, $clave1, $array2, $tabla2, $campo2, $clave2, true);
	}
	
	
	/**
        Arma una condición de ILIKE para comparar un campo de la base
        con un texto que se quiere ingresar 
        Lo que hace es quitarle los tildes a ambos y simplificar los espacios
    */
	static function armar_condicion_compara_cadenas($campo,$valor)
	{
		return static::preparar_comparacion($campo).' ILIKE '.guarani_texto::preparar_comparacion($valor);
	}
	
	static function limpiar_acentos($texto)
	{
		$buscar = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûüÿ';
		$reemplazar = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuy';
		return "translate($texto,'$buscar','$reemplazar')";
	}
	
	static function simplificar_espacios($texto)
	{
		return "regexp_replace($texto, '[ ]{2,}', ' ','g')";
	}
	
	static function preparar_comparacion($campo)
	{
		return 'trim('.static::simplificar_espacios(static::limpiar_acentos($campo)).')';
	}

	/*
	* Arma un CASE para insertar en una sentenca SELECT de sql obteniendo los datos del campo en la clase comunes.
	*/
	static function get_case($tabla,$campo,$alias=null,$opciones=array())
	{
		if (!$alias) $alias = "$campo ";
		$case = ' CASE ';
		if ($opciones == array()) {
			$opciones = comunes::get_opciones_combo($campo);
		}	
		foreach ($opciones as $opcion) {
			$case .= "WHEN $tabla.$campo = '{$opcion['valor']}' THEN '{$opcion['descr']}' ";
		}
		$case .= "END as $alias";
		
		return $case;
	}
	
	/*
	* Arma un CASE para insertar en una sentenca SELECT de sql para campos que tienen sólo los valores 'S' (Sí) y 'N' (No).
	*/
	static function get_case_si_no($tabla,$campo,$alias=null)
	{
		return static::get_case($tabla,$campo,$alias,toba::consulta_php('co_comunes')->get_si_no());
	}
	
}