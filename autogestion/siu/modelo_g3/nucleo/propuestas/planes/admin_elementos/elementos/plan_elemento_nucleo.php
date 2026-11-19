<?php
/**
 * Representa un elemento del plan de estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_nucleo /*implements interface_elemento_plan*/
{
	protected $tipo;
	protected $subtipo;
	protected $id;
	protected $controlador;
	protected $padres = array();			// Un elemento tiene mas de un padre
	protected $padres_eliminados = array();
	protected $elemento_visual;
	protected $es_nuevo;					// Indica si el elemento proviene de la base o no
	protected $es_copia = false;
	protected $es_recien_creado;			// Incida si esta creado desde la interface (los compartibles no son nuevos pero si recien creados)
	protected $esta_eliminado = false;
	protected $atributos_padre;
	protected $debug = false;
	protected $esta_guardado = false;

	function __construct($tipo, $subtipo, $id, $nuevo=true, $es_recien_creado=false)
	{
		$this->tipo = $tipo;
		$this->subtipo = $subtipo;
		$this->id = $id;
		$this->es_nuevo = $nuevo;
		$this->es_recien_creado = $es_recien_creado;
		$this->debug("Construir elemento: $id " . print_r($nuevo,true) . " recien creado: " . print_r($es_recien_creado,true));
	}

	function set_controlador($controlador)
	{
		$this->controlador = $controlador;
	}

	/**
	*	Este metodo es para eliminar la referencia al CN controlador
	*	antes de que se serialice el objeto... sino el proximo request
	*	el CN referenciado por este objeto no va a ser el mismo que el que
	*	construya toba.
	*/
	function desreferenciar_controlador()
	{
		unset($this->controlador);	
	}

	//-------------------------------------------------------------------

	function get_id()
	{
		return $this->id;
	}

	function get_tipo()
	{
		return $this->tipo;
	}

	function get_subtipo()
	{
		return $this->subtipo;
	}
		
	function es_nuevo()
	{
		return $this->es_nuevo;
	}

	function set_nuevo($nuevo=true)
	{
		$this->es_nuevo = $nuevo;	
	}

	function set_recien_creado($recien_creado=true)
	{
		$this->es_recien_creado = $recien_creado;
	}
	
	function es_recien_creado()
	{
		return $this->es_recien_creado;
	}

	function esta_eliminado()
	{
		return $this->esta_eliminado;
	}

	function set_eliminado($eliminado=true)
	{
		$this->esta_eliminado = $eliminado;	
	}

	function set_copia($copia=true)
	{
		$this->es_copia = true;
	}
	
	function es_copia()
	{
		return $this->es_copia;	
	}
	
	//--------------------------------------------------
	
	function get_clase_gui()
	{
		return guarani::catalogo_elementos_plan()->get_clase_gui($this->subtipo);		
	}

	function get_tiene_ancestro_compartible($padre)
	{
		return $this->atributos_padre[$padre]['tiene_ancestro_compartible'];
	}

	//----------------------------------------------------------------
	//-- Manejo de PADRES ----------------------------------------
	//----------------------------------------------------------------

	function set_padre($padre)
	{
		if (!is_object($padre)) {
			throw new toba_error("El padre debe ser un objeto");	
		}
		$id = $padre->get_id();
		// Si el elemento se esta re-asociando al padre, el padre puede estar marcado como eliminado
		foreach($this->padres_eliminados as $pos => $pos_padre) {
			if ($this->padres_eliminados[$pos]==$id) {
				unset($this->padres_eliminados[$pos]);	
				break;
			}
		}
		// Guardo referencia
		$this->padres[$id] = $padre;	
		$this->debug("SET padre: $id");
	}

	function eliminar_padre($id)
	{
		if (isset($this->padres[$id])) {
			$this->padres_eliminados[] = $id;
		}
	}
	
	function get_padre($id)
	{
		if(isset($this->padres[$id]) && (!in_array($id, $this->padres_eliminados))) {
			return $this->padres[$id];
		} else {
			throw new guarani_error('El padre solicitado no existe!');	
		}
	}
	
	function get_padres()
	{
		$padres = array();
		foreach(array_keys($this->padres) as $padre) {
			if (!in_array($padre, $this->padres_eliminados)) {
				$padres[$padre] = $this->padres[$padre];
			}
		}
		return $padres;	
	}
	
	function get_cantidad_padres()
	{
		return guarani_arreglos::count($this->get_padres());	
	}

	function notificar_hijo_nuevo_en_padre()
	{
		foreach($this->get_padres() as $padre) {
			$this->controlador->_agregar_relacion_hijo_sincronizado_padre($padre, $this);
		}
	}

	function set_atributo_padre($padre, $atributo, $valor)
	{
		$this->atributos_padre[$padre][$atributo] = $valor;
	}
	
	//----------------------------------------------------------------
	//--  Controles  -------------------------------------------------
	//----------------------------------------------------------------

	protected function control_edicion_no_bloqueada()
	{
		$this->controlador->control_edicion_no_bloqueada();
	}
	
	protected function control_eliminar()
	{
		if(!$this->puede_eliminar()) {
			throw new guarani_error("No es posible eliminar el módulo porque esta bloqueado");
		}
	}

	//----------------------------------------------------------------
	//-- VALIDACIONES ------------------------------------------------
	//----------------------------------------------------------------

	function validar_datos_elementos_plan($datos)
	{
		// Créditos
		if (isset($datos['creditos']) && $datos['creditos'] < 0) {
				throw new guarani_error("Debe ingresar los créditos Mayor o Igual a Cero");
		}

		if ( (isset($datos['creditos_min']) && ! isset($datos['creditos_max'])) || ( ! isset($datos['creditos_min']) && isset($datos['creditos_max'])) ) {
			if (isset($datos['creditos_min'])) {
				throw new guarani_error("Créditos - Debe ingresar también el Máximo");
			} else {
				throw new guarani_error("Créditos - Debe ingresar también el Mínimo");
			}
		} else {
			if (isset($datos['creditos_min'])) {
				if ($datos['creditos_min'] >= $datos['creditos_max']) {
						throw new guarani_error("Créditos - El Máximo debe ser mayor que el Mínimo");
				} else {
					if (isset($datos['creditos'])) {
						if (! guarani_validar::esta_entre($datos['creditos'],$datos['creditos_min'],$datos['creditos_max'])) {
							throw new guarani_error("Créditos - Los Créditos deben estar entre el Mínimo y el Máximo");
						}
					}
					if ($datos['creditos_min'] < 0 ) {
						throw new guarani_error("Créditos - Los Valores de Créditos , Máximo y Mínimo deben ser Mayores ó Igual a 0");
					}
				}
			}
		}

		// Coeficiente

		if (isset($datos['coeficiente']) && $datos['coeficiente'] < 0) {
				throw new guarani_error("Debe ingresar un Coeficiente Mayor o Igual a Cero");
		}

		// Horas Semanales
		if (isset($datos['horas_semanales'])) {
			if ((float)$datos['horas_semanales'] > 168 || $datos['horas_semanales'] < 0) {
				throw new guarani_error("Debe ingresar una cantidad de Horas semanales Válida.");
			}
		}

		
		if (isset($datos['horas_totales']) && isset($datos['horas_semanales'])) {
			if ((float)$datos['horas_totales'] < (float)$datos['horas_semanales']) {
				throw new guarani_error("Debe ingresar una cantidad de Horas semanales menor que las Horas Totales.");
			}
		}

		// vigencia
		if (isset($datos['vigencia'])) {
			if (! guarani_validar::es_entero($datos['vigencia']) || $datos['vigencia'] <= 0) {
				throw new guarani_error("Debe ingresar en Vigencia un valor entero mayor a Cero.");
			}
		}

		// Plazo
		if (isset($datos['plazo'])) {
			if (! guarani_validar::es_entero($datos['plazo']) || $datos['plazo'] <= 0) {
				throw new guarani_error("Debe ingresar en Plazo un valor entero mayor a Cero.");
			}
		}

		// Cantidad de Aplazos
		if (isset($datos['cantidad_de_aplazos'])) {
			if (! guarani_validar::es_entero($datos['cantidad_de_aplazos']) || $datos['cantidad_de_aplazos'] < 0) {
				throw new guarani_error("Debe ingresar en Cantidad de Aplazos un valor entero mayor o igual a Cero.");
			}
		}


	}
	
	function validar_datos_revision($datos)
	{
		// Origen de creditos
		if (isset($datos['origen_creditos'])) {
			if ($datos['origen_creditos'] == 'M') {
				if (! isset($datos['creditos']) or $datos['creditos'] <= 0) {
					throw new guarani_error("Debe ingresar la cantidad de créditos Mayor a Cero.");
				}
			}
		}
	}




	//----------------------------------------------------------------
	//--  INFO sobre EDICION  ----------------------------------------
	//----------------------------------------------------------------
	
	function puede_eliminar()
	{
		return true;
	}
	
	//----------------------------------------------------------------
	//-- DEBUG -------------------------------------------------------
	//----------------------------------------------------------------
	
	function debug($texto)
	{
		if ($this->debug) {
			//toba::logger()->info("NODO: {$this->get_id()} - " . $texto);
			echo "NODO: {$this->get_id()} - " . $texto . "<br>";		
		}
	}

}
?>