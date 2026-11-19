<?php
/**
 * Catálogo general
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_comunes()
 *
 * @package Catalogos
 * @subpackage Comunes
 */
class comunes_nucleo
{
	// Tipos
	const si				= 'S';
	const si_desc			= 'Sí';
	const no				= 'N';
	const no_desc			= 'No';
	const opcional			= 'O';
	const opcional_desc		= 'Opcional';
	const todos				= 'T';
	const todas				= 'T';
	const todas_desc		= 'Todas';
	const algunas			= 'A';
	const algunas_desc		= 'Algunas';
	const estado_activo		= 'A';
    const estado_aceptado	= 'A';
	const estado_pendiente	= 'P';
	const estado_inactivo	= 'B';
	const estado_baja 		= 'B';
	const estado_deshabilitado = 'D';
	const no_aplica_desc	= 'No aplica';
	
	const estado_abierto	= 'A';
	const estado_cerrado	= 'C';
	const estado_obtenido	= 'O';
	const estado_rechazado	= 'R';

	const estado_activo_desc = 'Activo';
	const estado_baja_desc = 'Baja';
	const estado_pendiente_desc = 'Pendiente';
	const estado_inactivo_desc = 'No Activo';
	const estado_deshabilitado_desc = 'Deshabilitado';
	const estado_aceptado_desc = 'Aceptado';
	const estado_rechazado_desc = 'Rechazado';

	const formato_fecha_visual						= 'DD/MM/YYYY';
	const formato_fecha_visual_sin_anio				= 'DD/MM';
	const formato_fecha_hora_visual					= 'DD/MM/YYYY HH24:MI:SS';
	const formato_fecha_hora_visual_sin_segundos	= 'DD/MM/YYYY HH24:MI';
	const formato_hora_visual						= 'HH24:MI';
	const formato_fecha_bd							= 'YYYY-MM-DD';	
	const formato_fecha_orden						= 'YYYYMMDD';	
	const formato_fecha_hora_visual_milisegundos	= 'DD/MM/YYYY HH24:MI:SS.MS';
	const formato_anio_4_digitos_visual				= 'YYYY';
	const formato_fecha_hora_rest = 'YYYY-MM-DD HH24:MI:SS';
	
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
        
        //resultados de inscripciones
        const resultado_inscripcion_aceptada = 'A';
        const resultado_inscripcion_rechazada = 'R';
        const resultado_inscripcion_pendiente = 'P';
	
	// Días de la semana
	const lunes = 'Lunes';
	const martes = 'Martes';
	const miercoles = 'Miercoles';
	const jueves = 'Jueves';
	const viernes = 'Viernes';
	const sabado = 'Sabado';
	const domingo = 'Domingo';
	
	// Meses del año
	const enero = 'Enero';
	const febrero = 'Febrero';
	const marzo = 'Marzo';
	const abril = 'Abril';
	const mayo = 'Mayo';
	const junio = 'Junio';
	const julio = 'Julio';
	const agosto = 'Agosto';
	const septiembre = 'Septiembre';
	const octubre = 'Octubre';
	const noviembre = 'Noviembre';
	const diciembre = 'Diciembre';
	
	// Interfaces
	const interfaz_todas		= 0;
	const interfaz_gestion		= 1;
	const interfaz_autogestion	= 2;
	
	const interfaz_consola		= 'cli';
	const usuario_consola		= 'consola';

	const navegador_recomendado = 'Firefox';

	const dimension_ug = 13;

	const pueblo_originario_otros = 27;

	const mayor = 'MAYOR';
	const mayor_descr = 'Mayor a';
	const menor = 'MENOR';
	const menor_descr = 'Menor a';
	const entre = 'ENTRE';
	const entre_descr = 'Entre';
	const es_igual_a = 'es_igual_a';
	const es_mayor_igual_que = 'es_mayor_igual_que';

	// Expresiones regulares para validación de EFs.
	const er_telefono_codigo_area = '/^\d+$/';
	const er_numero_entero = '/^[0-9]*$/';
	const er_numero_dos_decimales = '/^[0-9]+[.]?[0-9]{0,2}$/';
	const er_nota = '/^((\d){1,5})|((\d){1,5}[,\.]{1}\d{1,3})$/';
	const er_host = '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
	const er_token = '/^[a-f0-9]{32}$/';
	const er_email = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,20}$/i';
	const er_letra = '/[A-Za-z]/';
	const er_hora = '/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/';
	
	const consola_tipo_msg_error = '[*ERROR*]';
	const consola_tipo_msg_info = '[*INFO*]';
	const consola_tipo_msg_exito = '[*EXITO*]';
	const consola_tipo_msg_subtitulo = '[*SUBTITULO*]';
    
    const valores_falsos = ['0', 'no', 'false', 'off'];
    const valores_verdaderos = ['1', 'yes', 'true', 'on'];

    	const on = 'on';
    	const like = 'LIKE';
    	const ilike = 'ILIKE';

	// Versiones requeridas de los componentes para que Guaraní funcione correctamente

	const req_apache_ver = ['2.2', '2.4'];
	const req_php_ver = '7.4';
	const req_pg_ver = '11.6';
	const req_yarn_ver = '1.19.1';
	const req_yuicompressor_ver = '2.4.8';
	const req_php_ext = ['gd', 'pdo_pgsql', 'apcu', 'mbstring'];

        const ef_combo_seleccione = 'nopar';

	/** @var int $timeout_procesos_bk Timeout en milisegundos para preguntar sobre el estado del proceso en background. */
	static protected $timeout_procesos_bk = 1000; // 1 segundo
	/** @var int $timeout_resultados_procesos_bk Timeout en milisegundos para preguntar sobre el resultado del proceso en background. */
	static protected $timeout_resultados_procesos_bk = 3000; // 3 segundos
        
	static protected $instancia;

	private function __construct() {}

	static function instancia()
	{
		if (!isset(static::$instancia)) {
			static::$instancia = new comunes();	
		}
		return static::$instancia;	
	}

	static function get_opciones_combo($campo) {
		$metodo = 'get_opciones_combo_'.$campo;
		return toba::consulta_php('co_comunes')->$metodo();
	}

	static function get_nuevo_nro_transaccion()
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
	static function filtrar_array_filas_por_cadena($filas, $columna_filtro, $campo)
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

	static function es_navegador_recomendado()
	{
		return strpos($_SERVER['HTTP_USER_AGENT'], static::navegador_recomendado);
	}

	/**
 	 * @return int Timeout en milisegundos para preguntar sobre el estado del proceso en background.
 	 */
	static function get_timeout_procesos_bk()
	{
		return static::$timeout_procesos_bk;
	}

 	/**
 	 * @return int Timeout en milisegundos para preguntar sobre el resultado del proceso en background.
 	 */
	static function get_timeout_resultados_procesos_bk()
	{
		return static::$timeout_resultados_procesos_bk;
	}
    
    static function array_a_utf8($datos)
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

	function get_mayor_menor_entre($datos, $clave_desde, $clave_hasta)
	{
		if (!empty($datos[$clave_hasta])) {
			if (!empty($datos[$clave_desde])) {
				return comunes::entre;
			}
			return comunes::menor;
		}
		return comunes::mayor;
	}

	/**
	 * Devuelve la interfaz actual, ideal para usar en las clases de núcleo.
	 * @return integer 1 si la interfaz es Gestión, 2 si es Autogestión.
	 */
	static function get_interfaz_actual()
	{
		return isset($_SERVER['TOBA_DIR'])? static::interfaz_gestion : static::interfaz_autogestion;
	}

	/**
	 * @return boolean true si la interfaz es Gestión, false en otro caso.
	 */
	static function es_interfaz_gestion()
	{
		return (static::get_interfaz_actual() == static::interfaz_gestion);
	}

	/**
	 * @return boolean true si la interfaz es Autogestión, false en otro caso.
	 */
	static function es_interfaz_autogestion()
	{
		return (static::get_interfaz_actual() == static::interfaz_autogestion);
	}
        
        /**
         * @see https://stackoverflow.com/questions/39657564/check-if-anything-is-listening-in-a-port-in-php
	 * @return bool true si Jasper está corriendo, false en otro caso.
	 */
	public static function esta_jasper_corriendo()
	{
            $jasper_host = '';
            $jasper_port = '';
            if (static::es_interfaz_autogestion()) {
                $jasper_host = \SIU\Chulupi\kernel::proyecto()->get_jasper_host();
                $jasper_port = \SIU\Chulupi\kernel::proyecto()->get_jasper_port();
            } else {
                $jasper_host = guarani_instalacion_conf::get_parametro_instalacion('jasper_host', false, '127.0.0.1');
                $jasper_port = guarani_instalacion_conf::get_parametro_instalacion('jasper_port', false, '8081');
            }
            $connection = @fsockopen($jasper_host, $jasper_port);

            if (is_resource($connection)) {
                fclose($connection);
                return true;
            } else {
                return false;
            }
	}

       /**
        * Verifica si Jasper está corriendo, de no serlo lanza una excepción.
        * @throws \Exception
        */
	public static function verificar_jasper_corriendo()
	{
            if (!static::esta_jasper_corriendo()) {
                throw guarani::error(guarani_mensajes::get('800_error_jasper_no_corriendo'));
            }
	}
	
	/**
     * Devuelve los meses del año en un array del 1 al 12.
     * @return string[] Meses del año en un array del 1 al 12.
     */
	static function get_meses_anio()
	{
		$meses = [];
		$meses[1] = static::enero; 
		$meses[2] = static::febrero; 
		$meses[3] = static::marzo; 
		$meses[4] = static::abril; 
		$meses[5] = static::mayo; 
		$meses[6] = static::junio; 
		$meses[7] = static::julio; 
		$meses[8] = static::agosto; 
		$meses[9] = static::septiembre; 
		$meses[10] = static::octubre; 
		$meses[11] = static::noviembre; 
		$meses[12] = static::diciembre; 
		
		return $meses;
	}
	
	/**
	 * Reemplaza saltos de líneas por espacios en blanco.
	 * @see https://stackoverflow.com/questions/3760816/remove-new-lines-from-string-and-replace-with-one-empty-space
	 * @param string $mensaje	Mensaje a procesar
	 * @return string Mensaje procesado
	 */
	static function reemplazar_saltos_lineas_por_espacios($mensaje)
	{
		return trim(preg_replace('/\s+/', ' ', $mensaje));
	}

}