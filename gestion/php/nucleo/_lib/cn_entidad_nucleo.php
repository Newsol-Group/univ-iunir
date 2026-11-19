<?php
/**
 * Entidades Generica
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */
class cn_entidad_nucleo extends guarani_pers_cn //implements i_entidad
{
	protected $id;
	protected $hay_errores = false;
	protected $errores;
	protected $etiquetas = array();
	protected $datos_obligatorios = array();
	protected $s__edicion_bloqueada = false;
	protected $s__eliminacion_bloqueada = false;
	
	/**
	 * Implementa la inicializacion de la entidad
	 */
	function inicializar_estado()
	{
		$this->habilitar_edicion();
		$this->habilitar_eliminacion();
	}
	
	/**
	 * Implementa la carga de una entidad
	 */
	function cargar($id)
	{
		$this->dep('datos')->cargar($id);
		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	/**
	 * Configura la habilitación para edición de la entidad cargada
	 */
	function verificar_editabilidad() {}

	function bloquear_edicion()
	{
		$this->s__edicion_bloqueada = true;
	}

	function habilitar_edicion()
	{
		$this->s__edicion_bloqueada = false;
	}

	function validar_editabilidad()
	{
		if (!$this->es_editable()) {
			throw guarani::error(toba::mensajes()->get('800_entidad_no_editable'));
		}
	}

	function es_editable()
	{
		return (! $this->s__edicion_bloqueada);
	}
	
	/**
	 * Configura la habilitación para eliminacion de la entidad cargada
	 */
	
	function validar_eliminacion()
	{
		if (!$this->es_editable() || !$this->se_puede_eliminar() ) {
			throw guarani::error(toba::mensajes()->get('800_entidad_no_editable'));
		}
	}
	
	function se_puede_eliminar()
	{
		return (!$this->s__eliminacion_bloqueada);
	}
	
	function habilitar_eliminacion()
	{
		$this->s__eliminacion_bloqueada = false;
	}
	
	function bloquear_eliminacion()
	{
		$this->s__eliminacion_bloqueada = true;
	}
	
	

	/**
	 * Setea las etiquetas que pudieran necesitarse para armar mensajes referidos a la entidad o carga todas las etiquetas referidas a
	 * las tablas de la dependencia si esta se pasa como parámetro
	 * @param <type> $dep
	 * @return <type>
	 */
	function cargar_etiquetas($dep=null)
	{
		if (!isset($dep)) {
			$dep = $this->get_tabla_raiz();
		}
		if (isset($this->etiquetas[$dep])) {
			return;
		}
		if ($this->dep('datos')->existe_dependencia($dep)) {
			$tabla = $this->tabla($dep)->get_tabla();
			$this->etiquetas[$dep] = guarani_etiquetas::get_tabla($tabla);
		} else {
			$this->etiquetas[$dep] = guarani_etiquetas::get_concepto($dep);
		}
		
	}

	/**
	 * Valida los datos
	*/
	function validar_datos($datos)
	{
		return true;
	}	

	/**
	 * Valida que los datos obligatorios tengan datos
	*/
	function validar_datos_obligatorios($datos=array())
	{
		foreach ($this->datos_obligatorios as $dato_obligatorio => $etiqueta) {
			if (!isset($datos[$dato_obligatorio])) {
				$this->set_error('dato_faltante', array($etiqueta));
			}
		}
	}

	/**
	 * Setea los datos
	*/
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_editabilidad();
		if ($validar) {
			$method = 'validar_datos';
			if (method_exists($this, $method.'_'.$tabla)) {
				$method .= '_'.$tabla;
			}
			$this->$method($datos);
			if ($this->hay_errores) {
				throw guarani::error($this->errores);
			}
		}
		$method = "set_datos_$tabla";
		if (method_exists($this, $method)) {
			$this->$method($datos);
			return;
		} 
		$this->tabla($tabla)->set($datos);
	}

	/*
	 * Setea cuáles son los datos que son obligatorios
	 * @param <array> $datos_obligatorios  array asociativo id => etiqueta
	 */
	function set_datos_obligatorios($datos_obligatorios=array())
	{
		$this->datos_obligatorios = array_merge($this->datos_obligatorios, $datos_obligatorios);
	}

	/**
	 * Obtiene los datos de una tabla
	*/
	function get_datos($tabla=null)
	{
		if (!isset($tabla)) {
			$tabla = $this->get_tabla_raiz();
		}
		return $this->tabla($tabla)->get();
	}

	/**
	 * Obtiene la tabla raíz
	*/
	function get_tabla_raiz()
	{
		$tablas_raiz = $this->dep('datos')->get_tablas_raiz();
		return current($tablas_raiz);
	}

	/**
 	  *	Se utiliza para aplicar a los campos que no son editables por el usuario en un caso determinado, es decir no pueden tener
 	  * un valor asignado en dicho caso (y por lo tanto se setean como ocultos). En caso de que alguno de esos campos tenga valor 
 	  * se le asigna null, y además se lanza una excepción alertando al usuario de la situación. 
      *	@param	array	datos	son los datos sobre los cuales se realiza el chequeo.
      * @param	array	claves	son los claves correspondientes a los campos del formulario que son no editables. 
    */
	function limpiar_campos_no_editables($datos, $claves)
	{
		$hay_campos_invalidos = false;
		foreach ($datos as $clave => $valor) {
			if (in_array($clave, $claves) && !is_null($datos[$clave])) {	//si es uno de los efs no editables y tiene asignado un valor
				$datos[$clave] = null;
				$hay_campos_invalidos = true;
			}
		}
		if ($hay_campos_invalidos) {
			$this->set_error('800_dato_no_editable');
			throw guarani::error($this->get_mensaje_error());
		}
		return $datos;
	}	
	
	
	/**
	 * Ventana de ejecución previa a la sincronización
	 * Si se quiere evitar la sincronización, entonces
	 * hay que tirar una excepción
	 */
	function pre_sincronizar()
	{
	}

	/**
	 * Implementa la carga de una entidad
	 * @return mixed Clave de la entidad sincronizada	 
	 */
	function sincronizar()
	{
		if (! $this->dep('datos')->hay_cambios()) {
			return;
		}
		$this->validar_editabilidad();
		$this->pre_sincronizar();
		//Devuelve el ID de la entidad ingresada en la base.
		$this->dep('datos')->sincronizar();
		return $this->recuperar_clave_db();
	}
	
	/**
		Supone que la entidad tiene una tabla cabecera, y unico registro en ella.
	*/
	function recuperar_clave_db()
	{
		$tablas = $this->dep('datos')->get_tablas_raiz();
		if ( guarani_arreglos::count($tablas) != 1 ) {
			throw guarani::error('La entidad debe poseer una única tabla cabecera');
		}
		$filas = $this->dep('datos')->tabla(current($tablas))->get_id_filas();
		if ( guarani_arreglos::count($filas) > 1 ) {
			throw guarani::error('La tabla cabecera de la entidad debe poseer un único registro');
		}
		$clave = $this->dep('datos')->tabla(current($tablas))->get_clave_valor($filas[0]);
		return $clave;
	}

	/**
	 * Implementa la eliminacion de la entidad
	 */
	function eliminar()
	{
		$this->validar_editabilidad();
		$this->dep('datos')->eliminar_todo();
	}

	/**
	 * Implementa el reseteo del estado interno de la entidad
	 */
	function resetear()
	{
		toba::logger()->info('ENTIDAD: Resetear');
		$this->dep('datos')->resetear();
	}

	/**
	 * Retorna un elemento de persistencia de la entidad
	 * 
	 * @param string $id
	 * @return guarani_datos_tabla
	 */
	function tabla($id)
	{
		return $this->dep('datos')->tabla($id);	
	}

	function es_nueva()
	{
		return ! $this->dep('datos')->esta_cargada();	
	}

	/**
	 * Acumula mensajes a enviar a la pantalla
	 */
	function set_notificacion($descripcion_corta, $parms=null, $tipo=comunes::error_advertencia)
	{
		$mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
		//Si no está el mensaje definido en la base
		if (!$mensaje) {
			$mensaje = array('mensaje' => $descripcion_corta);
		}
		toba::notificacion()->agregar($mensaje['mensaje'], $tipo);
	}

	/**
	 * Acumula mensajes a enviar a la pantalla
	 */	
	function set_error($descripcion_corta, $parms=null, $tipo=null)
	{
		$mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
		//Si no está el mensaje definido en la base
		if (!$mensaje) {
			$mensaje = array('mensaje' => $descripcion_corta, 'tipo' => comunes::error_critico);
		}
		//Si me definió un tratamiento para el mensaje
		if ($tipo) {
			$mensaje['tipo'] = $tipo;
		}
		$this->hay_errores = true;
		$this->errores[] = $mensaje;
	}
	
	function get_mensaje_error($separador="\n")
	{
		return implode($separador, aplanar_matriz($this->errores, 'mensaje'));
	}

	function get_errores()
	{
		return $this->errores;
	}

	function hay_errores()
	{
		return $this->hay_errores;	
	}
	
	function resetear_errores()
	{
		$this->hay_errores = false;
		unset($this->errores);
	}
	
	/**
	 * Retorna una referencia a las etiquetas de la entidad o las etiquetas asociadas a las tablas de la dependencia
	 * si esta se paso como parámetro.
	 * @param <type> $tabla
	 * @return <type>
	 */
	function etiquetas($dep=null)
	{
		$this->cargar_etiquetas($dep);
		if (!$dep) {
			return current($this->etiquetas);
		}
		return $this->etiquetas[$dep];
	}

	/**
	 * Devuelve una referencia a la clase de consulta definida en el manejador
	 */
	function co()
	{
		return $this->controlador->co();
	}

	/**
	 * @ignore
	 */
	function desactivar_transacciones_anidadas()
	{
		$this->dep('datos')->get_persistidor()->desactivar_transaccion(true);
	}

	/**
	 * @ignore
	 */
	function activar_transacciones_anidadas()
	{
		$this->dep('datos')->get_persistidor()->activar_transaccion();
	}

}
?>