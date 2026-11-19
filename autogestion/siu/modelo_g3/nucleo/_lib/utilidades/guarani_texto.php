<?php
/**
 * Manipulación de cadenas de texto
 * 
 * @package Centrales
 * @subpackage Utilidades
 */
class guarani_texto
{

        const COMODIN_ENIE_MAYUSCULA = '%enie_may%';
        const COMODIN_ENIE_MINUSCULA = '%enie_min%';

        /**
         * Limpia acentos.
         *
         * @param string $texto Texto a limpiar acentos.
         *
         * @throws Exception Si $texto no es un string
         *
         * @see https://www.postgresql.org/docs/current/unaccent.html unaccent.
         * 
         * @return string Devuelve texto sin los acentos.
         */
        static function limpiar_acentos($texto)
        {
            // Si $texto no es un string
            if (!is_string($texto)) {
                throw new \Exception('El parámetro $texto debe ser de tipo string, se le paso ' . gettype($texto));
            }

            // Si $texto es vacío
            if (empty(trim($texto))) {
                return $texto;
            }

            // Reemplazo la Ñ y ñ por comodines para que la función unaccent no las convierta a N y n respectivamente
            $search  = ["Ñ", "ñ"];
            $replace = [static::COMODIN_ENIE_MAYUSCULA, static::COMODIN_ENIE_MINUSCULA];
            $texto   = str_replace($search, $replace, $texto);

            $texto  = static::simplificar_caracteres($texto);

            // Vuelvo a convertir los comodines a Ñ y ñ
            $search  = [static::COMODIN_ENIE_MAYUSCULA, static::COMODIN_ENIE_MINUSCULA];
            $replace = ["Ñ", "ñ"];
            $texto   = str_replace($search, $replace, $texto);

            return $texto;
        }

        /**
         * Limpia acentos, reemplaza ñ y Ñ por n y N.
         *
         * @param string $texto Texto a limpiar acentos, ñ y Ñ.
         *
         * @throws Exception Si $texto no es un string
         *
         * @see https://www.postgresql.org/docs/current/unaccent.html unaccent.
         *
         * @return string Devuelve texto sin los acentos, ñ y Ñ.
         */
        static function simplificar_caracteres($texto)
	{
            // Si $texto no es un string
            if (!is_string($texto)) {
                throw new \Exception('El parámetro $texto debe ser de tipo string, se le paso ' . gettype($texto));
            }

            // Si $texto es vacío
            if (empty(trim($texto))) {
                return $texto;
            }

            $sql    = "SELECT unaccent($$".$texto."$$::text) AS texto";
            $result = guarani_db::consultar_fila($sql);
            return $result['texto'];
	}

	// Reemplaza caracteres especiales para guardar en un archivo xml (utilizado en los procesos).
	static function parsear_caracteres_xml($texto)
	{
		return str_replace('&#039;', '&apos;', htmlspecialchars($texto, ENT_QUOTES, 'ISO-8859-1'));
	}

	static function contiene_subcadena($cadena, $subcadena)
	{
		$pos = strpos($cadena, $subcadena);
		return ($pos !== false);
	}

	static function pasar_a_mayusculas($texto)
	{
		$texto = strtoupper($texto);
		$buscar = 'ñ';
		$reemplazar = 'Ñ';
		return strtr($texto, $buscar, $reemplazar);
	}
	
	static function a_minusculas($cadena)
	{
		$cadena = trim($cadena);
		return mb_convert_case($cadena, MB_CASE_LOWER, 'ISO-8859-1');
	}

	static function simplificar_espacios($texto)
	{
		$buscar = '  ';
		$reemplazar = ' ';
		while (strpos($texto, '  ') !== false) {
			$texto = str_replace($buscar, $reemplazar, $texto);
		}
		return $texto;
	}

	static function preparar_comparacion($texto)
	{
		return trim(static::simplificar_espacios(static::limpiar_acentos($texto)));
	}

	static function armar_lista($array, $separador = ', ', $separador_final = null)
	{
		if (!empty($array)) {
			if (guarani_arreglos::count($array) == 1 || !$separador_final)
				return implode($separador, $array);
			$todos_menos_el_ultimo = array_slice($array, 0, guarani_arreglos::count($array) - 1);
			$ultimo_elemento = implode('', array_slice($array, - 1, 1));
			if ($separador_final == ' y ' && (substr($ultimo_elemento, 0, 1) == 'i' || substr($ultimo_elemento, 0, 2) == 'hi')) {
				$separador_final = ' e ';
			}
			return implode($separador, $todos_menos_el_ultimo) . $separador_final . $ultimo_elemento;
		}
	}

	/**
	 * A partir de un string que es una concatenacion de diferentes substrings determinados por un separador dado, se retorna
	 * una lista compuesta por los diferentes substrings separados por coma. Si no se indica un separador se utiliza una barra
	 * vertical: |.
	 */
	static function string_armar_lista($string, $separador = '|')
	{
		//Si el string es nulo vacío se retorna null
		if (!$string || trim($string) == '') {
			return '';
		}
		$lista_substr = '(' . implode(', ', explode($separador, $string)) . ')';
		return $lista_substr;
	}

	static function extraer_subclave($claves, $orden = 0, $separador = ':')
	{
		if (stripos($claves, $separador) !== false) {
			$claves = explode($separador, $claves);
			if (is_array($claves))
				$subclave = $claves[$orden];
		} else {
			if ($orden > 0)
				return false;
			$subclave = $claves;
		}
		if ($subclave == '')
			$subclave = null;

		return $subclave;
	}

	/**
	 * Formatea un texto y lo limita a un ancho determinado para mostrarlo en pantalla.
	 * Coloca puntos suspensivos si el texto supera el ancho establecido.
	 */
	static function acortar_texto($texto, $limite = 50)
	{
		if (strlen($texto) <= $limite) {
			return $texto;
		} else {
			return substr($texto, 0, $limite - 3) . '...';
		}
		return $texto;
	}

	/**
	 * Trunca un texto y lo limita a un ancho determinado.
	 */
	static function truncar_texto($texto, $limite = 50)
	{
		return substr($texto, 0, $limite);
	}

	/**
	 * Limpia el texto de caracteres especiales. Utilziado para la impresión en JASPER
	 */
	static function sanear_string($string)
	{
		$string = rtrim($string);
		return $string;
	}
	
	/**
	 * Parsea el xml para escapear caracteres especiales. Utilizado para el archivo enviado a Jasper de ka vista
	 * xml de un cuadro.
	 * @param type $xml
	 */
	static function reemplazar_caracteres_especiales_xml($xml)
	{
		$reemplazos = array(
						'<'  => '-',
						'>'  => '-',
						'&'  => '-',
						'"'  => '-',
						"'"  => '-'
						);
		$cadena = str_replace(array_keys($reemplazos), array_values($reemplazos), $xml);
		return $cadena;
	}
	
	/**
	 * Parsea el xml para escapear caracteres especiales. Utilizado para el archivo enviado a FOP de ka vista
	 * xml de un cuadro.
	 * @param type $xml
	 */
	
	static function reemplazar_caracteres_especiales_xml_fop($xml)
	{
		$reemplazos = array(
						'&'  => '&amp;',
						'"'  => "'",
		//				"'"  => '-'
						);
		$cadena = str_replace(array_keys($reemplazos), array_values($reemplazos), $xml);
		return $cadena;
	}
}