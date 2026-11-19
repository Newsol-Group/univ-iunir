<?php
namespace siu\modelo\_comun;

/**
 * Catálogo general
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_comunes()
 *
 * @package Catalogos
 * @subpackage Comunes
 */
class comunes
{
	// Tipos
	const si			= 'S';
	const si_desc			= 'Sí';
	const no			= 'N';
	const no_desc			= 'No';
	const todos			= 'T';
	const todas			= 'T';
	const todas_desc		= 'Todas';
	const algunas			= 'A';
	const algunas_desc		= 'Algunas';
	const aceptado_desc		= 'Aceptado';
	const estado_activo		= 'A';
	const estado_activo_desc 	= 'Activo';
	const estado_activo_nuevo	= 'N';
	const estado_nuevo		= 'N';
	const estado_nuevo_desc 	= 'Nuevo';
	const estado_activo_vigente	= 'A';
	const estado_activo_vigente_desc = 'Activo Vigente';
	const estado_activo_no_vigente	= 'V';
	const estado_activo_no_vigente_desc = 'Activo No Vigente';
	const estado_inactivo		= 'B';
	const estado_inactivo_desc 	= 'No Activo';
	const estado_baja 		= 'B';
	const estado_baja_desc 		= 'Baja';
	const estado_deshabilitado 	= 'D';
	const estado_deshabilitado_desc = 'Deshabilitado';
	
	const estado_abierto		= 'A';
	const estado_abierto_desc	= 'Abierto';
	const estado_cerrado		= 'C';
	const estado_cerrado_desc	= 'Cerrado';

	const formato_fecha_visual						= 'DD/MM/YYYY';
	const formato_fecha_visual_sin_anio				= 'DD/MM';
	const formato_fecha_hora_visual					= 'DD/MM/YYYY HH24:MI:SS';
	const formato_fecha_hora_visual_sin_segundos	= 'DD/MM/YYYY HH24:MI';
	const formato_hora_visual						= 'HH24:MI';
	const formato_fecha_bd							= 'YYYY-MM-DD';	
	const formato_fecha_orden						= 'YYYYMMDD';	
	const formato_fecha_hora_visual_milisegundos	= 'DD/MM/YYYY HH24:MI:SS.MS';
	
	const operador_not_defecto = 0;
	
	const chars_maximo_combo = 50;

	const error_critico = 'error';
	const error_advertencia = 'warning';
	const error_informacion = 'info';

	const mensaje_critico = 'error';
	const mensaje_informacion = 'info';
	
	const resultado_tipo_competencias = 'C';

	//Resultados de notas de las escalas de notas
	const resultado_nota_aprobado = 'A';
	const resultado_nota_ausente = 'U';
	const resultado_nota_desaprobado = 'R';
	
	// Días de la semana
	const lunes = 'Lunes';
	const martes = 'Martes';
	const miercoles = 'Miercoles';
	const jueves = 'Jueves';
	const viernes = 'Viernes';
	const sabado = 'Sabado';
	const domingo = 'Domingo';
	
	// Interfaces
	const interfaz_gestion		= 1;
	const interfaz_autogestion	= 2;

	const navegador_recomendado = 'Firefox';

	// Orígenes.
	const origen_regulares = 'R';
	const origen_promocionales = 'P';

	// Instancias.
	const instancia_regularidad = 1;
	const instancia_promocion = 2;

	//caracteres especiales
	const cadena_especiales = 'ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖØòóôõöøðÙÚÛÜùúûüÿýÝçÇÑñ';
	const cadena_basicos = 'AAAAAAaaaaaaEEEEeeeeIIIIiiiiOOOOOOoooooooUUUUuuuuyyYcCNn';

	static protected $instancia;

	private function __construct() {}

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new comunes();	
		}
		return self::$instancia;	
	}

	function get_opciones_combo($campo) {
		$metodo = 'get_opciones_combo_'.$campo;
		return toba::consulta_php('co_comunes')->$metodo();
	}

	function get_nuevo_nro_transaccion()
	{
		$sql = "SELECT f_nro_transaccion() as nro_transaccion;";

		$res = guarani_db::consultar_fila($sql);
		return $res['nro_transaccion'];
	}
	
	/**
	 * Permite filtrar un conjunto de filas por una condición de una columna de tipo cadena. Dado la matriz de arreglos
	 * asociativos, retornará una matriz conteniendo aquellas filas que cumplen con la condición ingresada.
	 * Se utiliza para aplicar un filtro de tipo cadena sobre un formulario_ml, una vez recuperadas las filas del DT
	 * para la configuración del mismo.
	 *
	 * @param array $filas			arreglo de arreglos asociativos representando las filas sobre las cuales se desea aplicar el filtro. 
	 * @param array $columna_filtro	datos del filtro para la columna y condición por la cual se quiere filtrar.
	 * @param str 	$campo_array	campo de los arreglos asociativos con el cual se compara la cadena ingresada.
	 * @return array arreglo de arreglos asociativos representando las filas dadas excluyendo aquellas que no verifican
	 * el filtro de tipo cadena con la condición y valor de $columna_filtro para el campo $campo.
	 */
	function filtrar_array_filas_por_cadena($filas, $columna_filtro, $campo)
	{
		$columna_filtro['valor'] = strtolower($columna_filtro['valor']);
		$columna_filtro['valor'] = guarani_texto::limpiar_acentos($columna_filtro['valor']);
		foreach ($filas as $key => $dato) {
			$dato[$campo] = strtolower($dato[$campo]);
			$dato[$campo] = guarani_texto::limpiar_acentos($dato[$campo]);
			switch ($columna_filtro['condicion']) {
				case  'contiene': 
					if (substr_count($dato[$campo], $columna_filtro['valor']) == 0) {
						unset($filas[$key]);
					}
					break;
				case  'no_contiene':
					if ( substr_count($dato[$campo], $columna_filtro['valor']) > 0) {
						unset($filas[$key]);
					}
					break;
				case  'comienza_con':
					if (strpos($dato[$campo], $columna_filtro['valor']) !== 0) {
						unset($filas[$key]); 
					}
					break;
				case  'termina_con':
					if (substr($dato[$campo], strlen($dato[$campo]) - strlen($columna_filtro['valor'])) != $columna_filtro['valor']) {
						unset($filas[$key]); 
					}
				break;
				case  'es_igual_a': 
					if ($dato[$campo] != $columna_filtro['valor']) {
						unset($filas[$key]); 
					}
					break;
				case  'es_distinto_de': 
					if ( $dato[$campo] == $columna_filtro['valor']) {
						unset($filas[$key]);
					}
					break;
			}
		}
		return $filas;
	}

	function es_navegador_recomendado()
	{
		return strpos($_SERVER['HTTP_USER_AGENT'], self::navegador_recomendado);
	}
}