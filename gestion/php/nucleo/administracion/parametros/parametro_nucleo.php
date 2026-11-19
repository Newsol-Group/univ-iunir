<?php
/**
 *
 *  Catálogo de datos relacionados con parámetros
 *
 * @package		Administración
 * @subpackage	Parámetros
 * 
 */
class parametro_nucleo
{
	// Niveles
	const nivel_default = 1;
	const nivel_ra = 2;
	const nivel_propuesta_tipo = 3;
	const nivel_propuesta = 4;
	const nivel_plan = 5;
	
	// Descripción de niveles
	const nivel_default_descr = 'Toda la Institución';
	const nivel_ra_descr = 'Responsable Académica';
	const nivel_propuesta_tipo_descr = 'Tipo de Propuesta';
	const nivel_propuesta_descr = 'Propuesta';
	const nivel_plan_descr = 'Plan';
	
	// Tipos de Parámetros
	const tipo_param_enumerado = 'E';
	const tipo_param_rango = 'R';
	const tipo_param_tabla = 'T';
	const tipo_param_libre = 'L';
	
	// Tipos de Datos
	const tipo_dato_entero = 'E';
	const tipo_dato_fecha = 'D';
	const tipo_dato_punto_flot = 'F';
	const tipo_dato_cadena = 'S';
	const tipo_dato_hora = 'T';
	const tipo_dato_fecha_y_hr = 'X';
	
	// Tipos de Niveles
	const tipo_subtipo = 'S';
	const tipo_entidad = 'E';
	
	// Tipos de niveles de parámetros
	const nivel_tipo_subtipo = 'S';
	const nivel_tipo_entidad = 'E';

	//--------------------------------------------------------------------------
	//---- Descripciones de nivel ----------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene la descripción de un nivel de definición de parámetros.
	 * 
	 * @param int		$nivel		nivel de definición
	 * @param boolean	$enfasis	¿resaltar en negrita?
	 * @return string
	 */
	static function get_descripcion_nivel($nivel, $enfasis = true)
	{
		
		$salida = '';
		
		$tag_inicio = ($enfasis) ? '<strong>' : '';
		$tag_cierre = ($enfasis) ? '</strong>' : '';

		switch ($nivel) {
			case self::nivel_default :
				$salida = $tag_inicio . self::nivel_default_descr . $tag_cierre;
				break;
			case self::nivel_ra :
				$salida = $tag_inicio . self::nivel_ra_descr . $tag_cierre;
				break;
			case self::nivel_propuesta_tipo :
				$salida = $tag_inicio . self::nivel_propuesta_tipo_descr . $tag_cierre;
				break;
			case self::nivel_propuesta :
				$salida = $tag_inicio . self::nivel_propuesta_descr . $tag_cierre;
				break;
			case self::nivel_plan :
				$salida = $tag_inicio . self::nivel_plan_descr . $tag_cierre;
				break;
		}

		return $salida;
	}

	/**
	 * Obtiene la descripción de un nivel de definición de parámetros y los eventuales
	 * atributos (RA's, tipos de propuetas, propuestas, planes).
	 * 
	 * @param int		$nivel
	 * @param array		$atributos
	 * @param boolean	$enfasis	¿resaltar en negrita?
	 * @return string
	 */
	static function get_descripcion_nivel_atributos($nivel, $atributos = null, $enfasis = true)
	{
		$tag_inicio = ($enfasis) ? '<strong>' : '';
		$tag_cierre = ($enfasis) ? '</strong>' : '';

		switch ($nivel) {
			case self::nivel_default :
				$salida = $tag_inicio . self::nivel_default_descr . $tag_cierre;
				break;
			case self::nivel_ra :
				$descripciones = array();
				foreach ($atributos as $atributo) {
					$descripciones[] = toba::consulta_php('co_responsables_academicas')->get_descripcion_ra($atributo);
				}
				$descripcion_atributo = implode(', ', $descripciones);
				$salida = $tag_inicio . self::nivel_ra_descr . $tag_cierre . ': ' . $descripcion_atributo;
				break;
			case self::nivel_propuesta_tipo :
				$descripciones = array();
				foreach ($atributos as $atributo) {
					$descripciones[] = toba::consulta_php('co_propuestas')->get_propuesta_tipo_descr($atributo);
				}
				$descripcion_atributo = implode(', ', $descripciones);
				$salida = $tag_inicio . self::nivel_propuesta_tipo_descr . $tag_cierre . ': ' . $descripcion_atributo;
				break;
			case self::nivel_propuesta :
				$descripciones = array();
				foreach ($atributos as $atributo) {
					$descripciones[] = toba::consulta_php('co_propuestas')->get_propuesta_descr($atributo);
				}
				$descripcion_atributo = implode(', ', $descripciones);
				$salida = $tag_inicio . self::nivel_propuesta_descr . $tag_cierre . ': ' . $descripcion_atributo;
				break;
			case self::nivel_plan :
				$descripciones = array();
				foreach ($atributos as $atributo) {
					$descripciones[] = toba::consulta_php('co_planes')->get_descripcion_plan($atributo);
				}
				$descripcion_atributo = implode(', ', $descripciones);
				$salida = $tag_inicio . self::nivel_plan_descr . $tag_cierre . ': ' . $descripcion_atributo;
				break;
		}
		return $salida;
	}

}