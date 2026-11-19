<?php
/**
 * Ejecución de controles
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */

class guarani_punto_de_control_nucleo
{
	protected $id;
	protected $definicion;
	protected $parametros;
	protected $controles;
	protected $excepciones;
	protected $hay_errores=false;
	protected $mensajes=array();
	protected $operacion;
	
	protected $registro=array();
	protected $registrar_error=true;
	
	protected $resultado=self::autorizado;
	protected $detener_controles_en_rechazo=false;
	protected $advertencia_como_estricto=false;			// Define si se debe tratar un requisito definido que actue como "advertencia" como "estricto"
	protected $admin_cache=null;

	// Puntos de Control.
	const alumno_entidad = 0;
	const persona_operacion = 1;
	const alumno_operacion = 2;

	const alumno_actividad = 3;
	const alumno_instancia_regular = 4;
	const alumno_instancia_promocion = 5;
	const alumno_comision = 6;
	const alumno_subcomision = 7;
	const alumno_anio_academico = 8;

	const alumno_mesa_examen = 9;
	const alumno_examen_regular = 10;
	const alumno_examen_libre = 11;

	const cerrar_acta_regulares = 14;
	const cerrar_acta_promocion = 15;
	const cerrar_acta_examen = 16;

	const elegir_actividad_examen = 17;

	const persona_propuesta = 18;
	
	const actividad_equivalencia = 20;
	const equivalencia_instancia_total = 21;
	const equivalencia_instancia_regularidad = 22;
	const equivalencia_instancia_parcial = 23;
	
	const alumno_constancia_certificado = 24;
	const egreso = 27;

	// Tipos de Resultados posibles de un Punto de Control
	const autorizado = 'A';		// Todos los controles que se ejecutaron tuvieron resultados satisfactorios.
	const rechazado = 'R';		// Alguno de los controles que se ejecutaron no cumplió lo requerido, la operación debe mostrar el control que falló estaba configurado como ESTRICTO; quedarse en esa ventana para que el usuario decida adonde ir.
	const advertido = 'P';		// Alguno de los controles que se ejecutaron no cumplió con lo requerido, pero estaba configurado como ADVERTENCIA y el usuario optó por continuar.

	// Acciones.
	const accion_insc_propuesta = 3;

	function __construct($id, $parametros, $interfaz, $operacion, $detener_rechazo, $excepciones)
	{
		$this->id = $id;
		$this->parametros = $parametros;
		$this->operacion = $operacion;
		$this->get_controles($interfaz);
		//$this->validar_parametros();
		$this->detener_controles_en_rechazo = $detener_rechazo;
		$this->excepciones = $excepciones;
	}

	function set_advertencia_como_estricto($valor=true)
	{
		$this->advertencia_como_estricto = $valor;
	}

	function set_mensaje($mensaje, $requisito, $tipo=comunes::error_informacion)
	{
		$this->mensajes[] = array('mensaje' => $mensaje, 'tipo' => $tipo, 'requisito'=> $requisito);
	}
	
	/**
	 * Registra los datos que produjeron que falle el control
	 * 
	 * @param type $datos 
	 */
	function set_registro_error($datos)
	{
		if (isset($datos)) {
			$this->registro[] = $datos;
		}
		
	}
	
	/**
	 * Define si se debe registrar el error (util para controles en procesos)
	 * 
	 * @param type $valor 
	 */
	function set_registrar_error($valor=true)
	{
		$this->registrar_error = $valor;
	}
	

	function get_id()
	{
		return $this->id;
	}

	function get_mensajes()
	{
		return $this->mensajes;
	}
	
	function get_registro_error()
	{
		return $this->registro;
	}

	function hay_errores()
	{
		return $this->hay_errores;
	}

	/**
	 * Obtener info del Punto de Control
	 */
	function get_datos_pto_control()
	{
		if (! isset($this->definicion)) {
			$this->definicion = toba::consulta_php('co_puntos_de_control', true)->get_datos_pto_control($this->id);
		}
		return $this->definicion;
	}


	/**
	 * Obtener los Controles a ejecutar del Punto de Control
	 * para el plan versión y la operación en ejecución
	 */
	function get_controles($interfaz)
	{
		if (! isset($this->controles)) {
			$this->controles = toba::consulta_php('co_puntos_de_control', true)->get_controles($this->id, $this->parametros['plan_version'], $interfaz, $this->operacion);
		}
		toba::logger()->debug('Controles del Punto de control '.$this->get_id() ." = " .var_export($this->controles, true) );
		return $this->controles;
	}
	
	function set_mensaje_y_registro_error($control, $requisito)
	{
		$this->set_mensaje($control->get_mensaje('mensaje'),  $requisito, $control->get_mensaje('tipo'));
		if ($this->registrar_error){
			$this->set_registro_error($control->get_registro_error());
		}
	}
	
	function crear_control($control)
	{
		$obj_control = new guarani_control($control, $this->parametros);
		$obj_control->set_registrar_error($this->registrar_error);
		$obj_control->set_advertencia_como_estricto($this->advertencia_como_estricto);
		return $obj_control;
	}

	function control_exceptuado($control)
	{
		if (isset($control['accion'])) {
			$clave = $control['requisito'] . apex_qs_separador . $control['accion'];
			if (isset($this->parametros['elemento'])) {
				$claveconelem = $clave . apex_qs_separador . $this->parametros['elemento'];
				return isset($this->excepciones[$clave]) || isset($this->excepciones[$claveconelem]);
			} else {
				return isset($this->excepciones[$clave]);
			}
		}
		return false;
	}

	function controlar()
	{
		toba::logger()->debug('Controlando Punto de Control: '.$this->get_id());		
		$continuar = true;
		foreach ($this->controles as $control) {
			if (! $this->control_exceptuado($control)) {
				if (isset($control['regla'])) {
					$obj_control = $this->crear_control($control);
					$resultado_control = $obj_control->validar();

					if (!$resultado_control) {
						$this->hay_errores = true;
						$this->set_mensaje_y_registro_error($obj_control, $control['requisito']);
						//Segun definicion ver que se hace con el retorno
						switch ($obj_control->get_modo()) {
							case guarani_control::modo_estricto: 
								// Excepción, se interrumpe la ejecución de controles
								$this->resultado = self::rechazado;
								if ($this->detener_controles_en_rechazo) {
									$continuar = false;
								}
								break;
							case guarani_control::modo_advertencia: 
								if ($this->advertencia_como_estricto) {
									$this->resultado = self::rechazado;
									if ($this->detener_controles_en_rechazo) {
										$continuar = false;
									}
									break;
								} else {
									// Se debería enviar un mensaje al usuario como 'Sucede xxxx, desea continuar?'
									// y se actúa de acuerdo a la respuesta (continuar o no)
									//Duda: se siguen ejecutando los demás controles ?
									/*if ($respuesta == 'N') {
										$continuar = false;
									}*/
									if (! ($this->resultado == self::rechazado)) {
										$this->resultado = self::advertido;
									}
								}
								break;
							case guarani_control::modo_mensaje: // Se agrega una notificación a la cola
								break;
						}
					}
				} else {
					toba::logger()->debug("Contol sin regla asociada: {$control['nombre']}.");
				}
			} else {
				toba::logger()->debug("Control exceptuado: {$control['nombre']}.");
			}

			if (!$continuar) {
				break;
			}
		}
		toba::logger()->debug('Resultado del Punto de Control: '.$this->get_id() ." = " .$this->resultado );
		return $this->resultado;
	}

	
}
?>