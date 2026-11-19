<?php
/**
 * Validación de Puntos de Control
 *
 * @package		Centrales
 * @subpackage	ArquitecturaBase
 * 
 */
class validador_puntos_de_control_nucleo
{
	static protected $usar_cache = true;
	static protected $controles_activos = true;
	
	protected $puntos = array();
	protected $parametros;
	protected $hay_errores = false;
	protected $resultados = array();
	protected $mensajes = array();
	protected $resultado = guarani_punto_de_control::autorizado;
	protected $detener_controles_en_rechazo = false;
	protected $notificador;		// Mantiene el objeto que implementa las notificaciones al usuario
	protected $interfaz;		// Interfaz de usuario para la cual se evaluaran los controles
	protected $operacion;
	protected $excepciones;		// Lista de excepciones a requisitos vigentes para el alumno que está dentro de los parámetros.
	protected $advertencia_como_estricto = false;   // Define si se debe tratar un requisito definido que actue como "advertencia" como "estricto"
	protected $registrar_error = false;	 // Define si se debe mantener un registro de la fallas de controles
	protected $registro = array();
	protected $admin_cache = null;

	function activar_controles($activado = true)
	{
		self::$controles_activos = $activado;
	}

	function activar_cache($activado = true)
	{
		self::$usar_cache = $activado;
	}

	/**
	 * Constructor del Validador
	 * 
	 * @param $notificador	Objeto que implementa la notificacion
	 * @param $puntos		Puntos de Control a cargar en el validador
	 * @param $parametros	Parametros necesarios para ejecutar los puntos de control
	 * @param $interfaz
	 * @param $operacion
	 */
	function __construct($notificador, $puntos, $parametros, $interfaz, $operacion)
	{
		if (!is_array($puntos)) {
			$puntos = array($puntos);
		}
		$this->parametros = $parametros;
		$this->notificador = $notificador;
		$this->interfaz = $interfaz;
		$this->puntos = $puntos;
		$this->operacion = $operacion;
		$this->get_excepciones();
	}

	function set_continuar_en_rechazo($continuar = true)
	{
		$this->detener_controles_en_rechazo = !$continuar;
	}

	function set_detener_en_rechazo($detener = true)
	{
		$this->detener_controles_en_rechazo = $detener;
	}

	/**
	 * Setea si los requisitos que actuan como advertencias deben tenerse en cuenta como estrictos
	 * 
	 * @param boolean $valor 
	 */
	function set_advertencia_actua_como_estricto($valor = true)
	{
		$this->advertencia_como_estricto = $valor;
	}

	/**
	 * Define si se debe registrar el error (útil para controles en procesos)
	 * 
	 * @param boolean $valor 
	 */
	function set_registrar_error($valor = true)
	{
		$this->registrar_error = $valor;
	}

	function set_mensaje($punto, $mensajes)
	{
		$this->mensajes = array_merge($this->mensajes, $mensajes); 
	}

	function set_registro_error($datos)
	{
		$this->registro = array_merge($this->registro, $datos); //'Punto '.$punto
	}

	function set_mensaje_y_registro_error($punto)
	{
		$this->set_mensaje($punto->get_id(), $punto->get_mensajes());
		if ($this->registrar_error) {
			$this->set_registro_error($punto->get_registro_error());
		}
	}

	function get_parametro_alumno()
	{
		if (isset($this->parametros['alumno'])) {
			return $this->parametros['alumno'];
		} elseif (isset($this->parametros['persona']) && isset($this->parametros['propuesta'])) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_alumno($this->parametros['persona'], $this->parametros['propuesta']);
			return empty($datos_alumno) ? null : $datos_alumno['alumno'];
		} else {
			return null;
		}
	}

	function get_excepciones()
	{
		if (!isset($this->excepciones)) {
			$alumno = $this->get_parametro_alumno();
			if (empty($alumno)) {
				$this->excepciones = array();
			} else {
				$filtro = " sga_requisitos_excepciones.alumno = $alumno AND CURRENT_DATE BETWEEN sga_requisitos_excepciones.fecha_desde AND COALESCE(sga_requisitos_excepciones.fecha_hasta, CURRENT_DATE) ";
				$excepciones = toba::consulta_php('co_requisitos')->get_requisitos_excepciones($filtro);
				$valores = array();
				$claves = array('requisito', 'accion', 'elemento');
				foreach ($excepciones as $excepcion) {
					$valores_clave = array();
					foreach($claves as $clave) {
						if (isset($excepcion[$clave])) {
							$valores_clave[] = $excepcion[$clave];
						}
					}

					if (isset($excepcion['excepcion'])) {
						$valores[implode(apex_qs_separador, $valores_clave)] = $excepcion['excepcion'];
					}
				}
				$this->excepciones = $valores;
			}
		}
		toba::logger()->debug('Excepciones = ' . var_export($this->excepciones, true));
		return $this->excepciones;
	}

	function crear_punto($punto)
	{
		$obj_punto = new guarani_punto_de_control($punto, $this->parametros, $this->interfaz, $this->operacion, $this->detener_controles_en_rechazo, $this->excepciones);
		$obj_punto->set_registrar_error($this->registrar_error);
		$obj_punto->set_advertencia_como_estricto($this->advertencia_como_estricto);
		return $obj_punto;
	}

	/**
	 * Realiza el proceso de control de los puntos de definidos, 
	 * 
	 * @return char(1)  El peor de los siguientes resultados Posibles
	 * 					Resultados posibles de un Punto de Control:
	 * 					  autorizado = 'A';			// Todos los controles que se ejecutaron tuvieron resultados satisfactorios.
	 *                    const rechazado = 'R';	// Alguno de los controles que se ejecutaron no cumplió lo requerido. 
	 * 					  const advertido = 'P';    // Alguno de los controles que se ejecutaron no cumplió con lo requerido, pero estaba configurado como ADVERTENCIA.
	 */
	function controlar()
	{
		$resultado = array();
		$continuar = true;

		foreach ($this->puntos as $punto) {
			$error = false;
			$obj_punto = $this->crear_punto($punto);
			$resultado = $obj_punto->controlar();
			if ($obj_punto->hay_errores()) {
				$this->hay_errores = true;
				$this->set_mensaje_y_registro_error($obj_punto);
			}
			//Según definición ver qué se hace con el retorno
			switch ($resultado) {
				case guarani_punto_de_control::rechazado:
					$this->resultado = $resultado;
					// Excepción, se interrumpe la ejecución de controles
					if ($this->detener_controles_en_rechazo) {
						$continuar = false;
					}
					break;
				case guarani_punto_de_control::advertido:
					if (!($this->resultado == guarani_punto_de_control::rechazado)) {
						$this->resultado = guarani_punto_de_control::advertido;
					}
					break;
				case guarani_punto_de_control::autorizado:
					break;
			}

			if (!$continuar) {
				break;
			}
		}
		return $this->resultado;
	}

	function get_mensajes($interfaz_mensaje = null)
	{
		if (!isset($interfaz_mensaje)) {
			$interfaz_mensaje = $this->interfaz;
		}
		$retorno = array();
		foreach ($this->mensajes as $key => $mensaje) {
			$retorno[$key]['mensaje'] = $mensaje['mensaje'][$interfaz_mensaje]['mensaje'];
			$retorno[$key]['tipo'] = $mensaje['tipo'];
			$retorno[$key]['requisito'] = $mensaje['requisito'];
		}
		return $retorno;
	}

	function get_registro_error()
	{
		return $this->registro;
	}

	/**
	 * Agrega al $notificador los mensajes pasados como parámetro
	 * 
	 * @param $notificador
	 * @param array $mensajes 
	 */
	static function agregar_al_notificador($notificador, $mensajes)
	{
		foreach ($mensajes as $mensaje) {
			switch ($mensaje['tipo']) {
				case comunes::error_informacion:
					$notificador->info($mensaje['mensaje']);
					break;
				case comunes::error_advertencia:
					$notificador->warning($mensaje['mensaje']);
					break;
				case comunes::error_critico:
					$notificador->error($mensaje['mensaje']);
					break;
			}
		}
	}

	/**
	 *  Agrega al notificador los mensajes que se fueron acumulando durante el control de los puntos
	 */
	function notificar($interfaz_mensaje = null)
	{
		if (!isset($interfaz_mensaje)) {
			$interfaz_mensaje = $this->interfaz;
		}
		$mensajes = $this->get_mensajes($interfaz_mensaje);
		static::agregar_al_notificador($this->notificador, $mensajes);
	}

}
?>
