<?php
/**
 * Representa un elemento del plan de estudios de tipo MODULO
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_modulo_nucleo extends plan_elemento 
{
	// Cursores
	protected $cursor_elemento;
	protected $cursor_revision;
	protected $cursor_elemento_plan; 
	// Arbol
	protected $hijos = array();
	protected $hijos_eliminados = array();
	protected $tiene_hijos_cargados = false;
	// Orden
	protected $orden_valor_ultima_posicion = 0;
	protected $compartible;

	//----------------------------------------------------------------
	//--  Recuperacion del elemento VISUAL --------------------------------
	//----------------------------------------------------------------

	function get_elemento_gui($id_padre=null)
	{
		$clase_gui = $this->get_clase_gui();
		$datos = Array('tipo'=>$this->tipo, 'subtipo'=>$this->subtipo);
		$nombre = $this->get_nombre_elemento_plan();
		// Se agrega el código antepuesto al nombre del módulo para mostrarlo en el árbol
		$datos_elemento = $this->get_datos_elemento();
		if (isset($datos_elemento['codigo'])) {
			$nombre = "(".$datos_elemento['codigo'].") " . $nombre;
		}
		if ($this->debug) $nombre .= ' [' . $this->get_id() .']';
		$e_gui = new $clase_gui($this->get_id(), $nombre,$datos ,$id_padre,$this->get_id());

		//$e_gui = new $clase_gui($this->get_id(), $this->tipo, $this->subtipo, $this->get_nombre(), $this->get_id(), $id_padre);
		$e_gui->set_ancestro_compartible($this->tiene_ancestro_compartible());
		$e_gui->set_controlador($this);
		foreach ( $this->get_orden_hijos() as $id ) {
			$hijo = $this->hijos[$id];
			if(!$hijo['eliminado']) {
				$e = $hijo['instancia'];
				$e_hijo_gui = $e->get_elemento_gui($this->get_id());
				$e_gui->agregar_hijo($e_hijo_gui);
			}
		}
		return $e_gui;	
	}

	//----------------------------------------------------------------
	//-- Persistencia
	//----------------------------------------------------------------

	function eliminar($padre=null)
	{
		$this->control_eliminar();
		$this->eliminar_padre($padre);
		$datos_elemento = $this->get_datos_elemento();
		$plan_version = $this->controlador->get_plan_version();
		if ($this->get_cantidad_padres() == 0 && ($this->controlador->ver_optativas() || isset($plan_version) && !guarani::co('co_elementos')->es_optativa_de_generica_en_plan_version($datos_elemento['elemento'], $plan_version))) {
			$this->debug('Eliminar.');
			$this->set_eliminado();
			$this->eliminar_hijos();
		} else {
			$this->debug('No se dispara la eliminación.');
		}
	}


	function aplicar_eliminacion()
	{
		$this->debug("APLICAR ELIMINACION");
		if ($this->no_es_compartible()){
			$this->tabla_elementos_plan()->eliminar_fila($this->cursor_elemento_plan);
			$this->tabla_revision()->eliminar_fila($this->cursor_revision);
			// TO-DO el elemento se deberia eliminar desde un purgador (tener en cuanta las revisiones generadas al versionar el plan)
			// $this->tabla_elemento()->eliminar_fila($this->cursor_elemento);
		}
	}

	function guardar()
	{
		if ($this->esta_guardado) return;
		$nombre = $this->get_nombre();
		toba::logger()->info("######---------## INI SYNC MODULO [nombre: '$nombre'] --------##########");
		$relacion = $this->controlador->dep('elementos');
		// Sincronizacion de hijos
		foreach($this->hijos as $hijo) {
			$e = $hijo['instancia'];
			if ($hijo['eliminado']) {	//-- Hijo eliminado!
				$this->aplicar_eliminacion_hijo($e->get_id());
				//if( !$e->es_recien_creado() && !$e->es_nuevo() && $this->no_es_compartible() ){ 
				if( !$e->es_recien_creado() && $this->no_es_compartible() ){ 
					// Elimino las filas en la base, si es recien creado no sincronizo porque con la aplicacion alcanza
					$relacion->tabla('composicion')->sincronizar_filas(array($hijo['cursor']));
					$this->debug("SINCRONIZAR eliminacion HIJO");
				} else {
					$this->debug("**NO SINCRONIZAR * eliminacion HIJO");
				}
				$e->guardar();
			} else {					//-- Hijo nuevo o modificado
				if($e->es_nuevo()){
					$e->guardar();
					$e->notificar_hijo_nuevo_en_padre();
				} else {
					$e->guardar();
				}
			}
		}
		// Sincronizacion del elemento
		if ($this->esta_eliminado()) {
			// Se impacta la eliminacion en el datos tabla
			$entidad = $this->tabla_elemento()->get_columna('entidad');
			$this->aplicar_eliminacion();
			if(!$this->es_nuevo() && !$this->es_recien_creado()) {
				if (in_array($entidad, $this->controlador->_get_entidades_con_correlativas())) {
					$this->eliminar_correlativas($entidad);
				}
				$this->sincronizar_eliminacion();
			}
		} else {
			$this->sincronizar_actualizacion();
		}
		//Elimino la referencia a los hijos eliminados
		foreach($this->hijos_eliminados as $hijo) {
			unset($this->hijos[$hijo]);
		}
		$this->hijos_eliminados = array();
		$this->esta_guardado = true;
		toba::logger()->info("######---------## FIN SYNC MODULO [nombre: '$nombre'] --------##########");
	}

	function eliminar_correlativas($entidad)
	{
		$correlativas_plan = $this->controlador->manejador_correlativas();
		$correlativas_plan->eliminar_correlativas($entidad);
	}

	function sincronizar_eliminacion()
	{
		$relacion = $this->controlador->dep('elementos');
		$revisiones_elemento = toba::consulta_php('co_elementos')->get_cantidad_revisiones_elemento($this->get_clave_elemento());
		$relacion->tabla('elementos_plan')->sincronizar_filas(array($this->cursor_elemento_plan));
		if ($this->no_es_compartible()) {
			$relacion->tabla('revision')->sincronizar_filas(array($this->cursor_revision));
			if ($revisiones_elemento == 1) {
				$relacion->tabla('elemento')->sincronizar_filas(array($this->cursor_elemento));
			}
		}
	}

	function sincronizar_actualizacion()
	{
		$this->debug("sincronizar ACTUALIZACION: " .$this->get_nombre());
		$relacion = $this->controlador->dep('elementos');
		//Seteo cursores
		$relacion->tabla('elemento')->set_cursor($this->cursor_elemento);
		$relacion->tabla('composicion')->resetear_cursor();
		$relacion->tabla('revision')->set_cursor($this->cursor_revision);
		$relacion->tabla('modalidad')->resetear_cursor();
		$relacion->tabla('elementos_plan')->set_cursor($this->cursor_elemento_plan);
		$relacion->sincronizar(true);
		$this->set_nuevo(false);		
		$this->set_recien_creado(false);		
	}

	function get_clave_elemento()
	{
		if(!$this->es_nuevo()) {
			$relacion = $this->controlador->dep('elementos');
			$clave = $relacion->tabla('elemento')->get_clave_valor($this->cursor_elemento);
			return $clave['elemento'];
		} else {
			throw new guarani_error('No es posible recuperar el ID del elemento porque el elemento no esta sincronizado.');
		}
	}
	
	function get_clave_revision()
	{
		if(!$this->es_nuevo()) {
			$relacion = $this->controlador->dep('elementos');
			$clave = $relacion->tabla('revision')->get_clave_valor($this->cursor_revision);
			return $clave['elemento_revision'];
		} else {
			//throw new toba_error('No es posible recuperar el ID de la revision porque el elemento no esta sincronizado.');
			throw new guarani_error('No es posible recuperar el ID de la revision porque el elemento no esta sincronizado.');
		}
	}

	function copiar()
	{
		toba::logger()->info("##### copiando elemento " . $this->get_nombre() . " ###########################" );
		$this->set_nuevo();
		$this->tabla_elemento()->forzar_insercion(true);
		$this->tabla_revision()->forzar_insercion(true);
		$this->tabla_elementos_plan()->forzar_insercion(true);
		//$this->tabla_composicion()->forzar_insercion(false);
		$this->tabla_modalidad()->forzar_insercion(true);
		$this->tabla_elementos_ra()->forzar_insercion(true);
		//No disparar la recursion si un hijo es compartible
		$tabla_composicion = $this->controlador->dep('elementos')->tabla('composicion');
		foreach($this->hijos as $id => $hijo) {
			$tabla_composicion->forzar_insercion(null,array($hijo['cursor']));
			$e = $hijo['instancia'];
			if ($e->no_es_compartible()) {
				$e->copiar();
			}
		}
	}


	/**
	*	Recuperar el elemento (borrar y agregar sin transaccionar con la DB)
	*/
	function recuperar()
	{
		if ($this->esta_eliminado()) {
			$this->debug("RECUPERAR");
			$this->set_eliminado(false);
			foreach($this->hijos as $id => $hijo) {
				$this->recuperar_hijo($id);
			}
		} else {
			$this->debug("** NO RECUPERAR ** El elemento no esta marcado como eliminado");
		}
	}

	//----------------------------------------------------------------
	//-- Manejo de HIJOS ----------------------------------------
	//----------------------------------------------------------------

	function agregar_hijo($hijo, $cursor)
	{
		$this->control_agregar_hijo($hijo);
		$this->hijos[$hijo->get_id()]['cursor'] = $cursor;	//Fila correspondiente al hijo en la tabla composicion
		$this->hijos[$hijo->get_id()]['instancia'] = $hijo;
		$this->hijos[$hijo->get_id()]['eliminado'] = false;
		//Guardo localmente el orden
		$datos = $this->tabla_composicion_cursor_hijo($hijo)->get();
		$this->hijos[$hijo->get_id()]['orden'] = $datos['orden'];
		if ($datos['orden'] > $this->orden_valor_ultima_posicion) {
			$this->orden_valor_ultima_posicion = $datos['orden'];
		}
		$hijo->set_padre($this);
		$this->tiene_hijos_cargados = true;
	}
	
	function eliminar_hijo($id_hijo)
	{
		$this->debug("Eliminar HIJO: $id_hijo");
		//- Controles
		if (!isset($this->hijos[$id_hijo])) {
			throw new guarani_error("El hijo indicado no existe.");			
		}
		$this->control_eliminar_hijo();
		$hijo = $this->hijos[$id_hijo]['instancia'];
		$hijo->control_eliminar();	

		//- Registro la eliminacion del HIJO aca
		$this->hijos_eliminados[$id_hijo] = $id_hijo;
		$this->hijos[$id_hijo]['eliminado'] = true;

		//- Disparo eliminacion
		$hijo->eliminar($this->get_id());
		
		// Refresco el estado del modulo respecto de sus hijos
		if( $this->get_cantidad_hijos_activos()==0) {
			$this->tiene_hijos_cargados = false;
			$this->es_hoja = true;
		}
	}

	function recuperar_hijo($id_hijo)
	{
		if (!isset($this->hijos[$id_hijo])) {
			throw new toba_error("No es posible recuperar el hijo porque no existe.");	
		}
		if ($this->hijos[$id_hijo]['eliminado']) {
			$this->debug("RECUPERAR hijo: $id_hijo");
			unset($this->hijos_eliminados[$id_hijo]);
			$this->hijos[$id_hijo]['eliminado'] = false;
			$this->hijos[$id_hijo]['instancia']->set_padre($this);
			$this->hijos[$id_hijo]['instancia']->recuperar();
			// Refresco el estado del modulo respecto de sus hijos
			if( $this->get_cantidad_hijos_activos()>0) {
				$this->tiene_hijos_cargados = true;
				$this->es_hoja = false;
			}
		} else {
			$this->debug("** NO RECUPERAR HIJO ** El hijo no esta marcado como eliminado");
		}
	}

	/**
	*	Recuperar el elemento (borrar y agregar sin transaccionar con la DB)
	*/	
	function aplicar_eliminacion_hijo($id_hijo)
	{
		//- Marco al hijo como eliminado en la composicion de este modulo
		$cursor = $this->hijos[$id_hijo]['cursor'];
		$this->debug("APLICAR eliminacion HIJO [cursor: ". $cursor ."]");
		$this->tabla_composicion()->set_cursor($cursor);
		//ei_arbol($this->tabla_composicion()->get());
		$this->tabla_composicion()->eliminar_fila($cursor);	
	}
	
	protected function eliminar_hijos()
	{
		foreach($this->hijos as $id => $hijo) {
			if (!$hijo['eliminado']) {
				$this->eliminar_hijo($id);
			}
		}			
	}

	//-- INFO HIJOS ----------------------------------------

	function get_hijos()
	{
		$hijos = array();
		foreach($this->hijos as $hijo) {
			$hijos[] = $hijo['instancia'];
		}
		return $hijos;
	}
	
	function get_cantidad_hijos()
	{
		return guarani_arreglos::count($this->hijos);
	}

	function posee_elemento_asociado($id)
	{
		return isset($this->hijos[$id]) && ($this->hijos[$id]['eliminado'] == false);	
	}

	function posee_elemento_eliminado($id)
	{
		return isset($this->hijos[$id]) && ($this->hijos[$id]['eliminado'] == true);	
	}

	/**
	*	Retorna un array con los IDs de los hijos segun su orden
	*/
	function get_orden_hijos()
	{
		$posiciones = array();
		foreach($this->hijos as $id => $hijo) {
			if (!$hijo['eliminado']) {
				$posiciones[$id] = $hijo['orden'];
			}
		}
		asort($posiciones);
		return array_keys($posiciones);
	}
	
	function get_orden_proximo_valor()
	{
		return ++$this->orden_valor_ultima_posicion;
	}

	//-- Hijos no eliminados ----------------------------------------------

	function get_hijos_activos()
	{
		$hijos = array();
		foreach($this->hijos as $hijo) {
			if (!$hijo['eliminado']) {
				$hijos[] = $hijo['instancia'];
			}
		}
		return $hijos;
	}
	
	function get_cantidad_hijos_activos()
	{
		$hijos = 0;
		foreach($this->hijos as $hijo) {
			if (!$hijo['eliminado']) $hijos++;
		}
		return $hijos;
	}

	function es_hoja()
	{
		return false;
	}
	
	function es_raiz()
	{
		return (guarani_arreglos::count($this->padres) == 0);
	}
	
	//----------------------------------------------------------------
	//-- DATOS del MODULO ---------------------------------
	//----------------------------------------------------------------

	//-- Inicializacion -----------------------------------------------------

	function ini()
	{
		// Datos del MODULO
		//$elemento['entidad_tipo'] = $this->get_tipo();
		$elemento['entidad_subtipo'] = $this->get_subtipo();
		$elemento['estado'] = elemento::estado_activo;
		$elemento['nombre'] = '';	
		$elemento['nombre_abreviado'] = '';
		$elemento['compartible'] = 'N';
		// Datos de la Revision
		$revision['origen_creditos'] = elemento::creditos_no_informa;
		$revision['regla'] = elemento::regla_default;
		$revision['parametros'] = elemento::regla_parametros_default;
		$this->set_datos_elemento($elemento);
		$this->set_datos_revision($revision);		
	}

	function setear_datos_basicos($elemento)
	{
		$this->control_edicion_no_bloqueada();
		$datos = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($elemento);
		$this->controlador->dep('elementos')->tabla('elementos_plan')->set($datos);
	}

	//-- API granular -----------------------------------------------------

	function set_nombre($nombre)
	{
		$this->control_edicion_no_bloqueada();
		$datos['nombre'] = $nombre;
		$datos['nombre_abreviado'] = substr($nombre, 0, 40);
		$this->set_datos_elementos_plan($datos);
		if($this->es_nuevo()) {
			$this->set_datos_elemento($datos);
		}
	}

	function get_nombre()
	{
		//$datos = $this->get_datos_revision();
		//$datos = $this->get_datos();
		$datos = $this->get_datos_elemento();
		return $datos['nombre'];
	}
	
	function get_nombre_elemento_plan()
	{
		$datos = $this->get_datos_elementos_plan();
		return $datos['nombre'];
	}
	
	//-- Modificacion en bloque ----------------------------------------------

	protected function set_datos_elemento($datos)
	{
		$this->control_edicion_no_bloqueada();
		$this->tabla_elemento()->set($datos);
	}
	
	protected function get_datos_elemento()
	{
		return $this->tabla_elemento()->get();
	}
	
	function set_datos_revision($datos)
	{
		$this->control_edicion_no_bloqueada();
		$this->validar_datos_revision($datos);
		$this->tabla_revision()->set($datos);
	}
	
	protected function set_datos_elementos_plan($datos)
	{
		$this->control_edicion_no_bloqueada();
		$this->validar_datos_elementos_plan($datos);
		$this->tabla_elementos_plan()->set($datos);
	}
	
	protected function get_datos_elementos_plan()
	{
		return $this->tabla_elementos_plan()->get();
	}
	
	protected function get_datos_revision()
	{
		return $this->tabla_revision()->get();
	}

	function get_datos($id_padre=null)
	{
		$datos_elementos_plan	=	$this->get_datos_elementos_plan();
		$datos_revision 		=	$this->get_datos_revision();
		if (isset($datos_elementos_plan)) {
			$datos  = array_merge($datos_elementos_plan, $datos_revision);
		} else {
			$datos  = $datos_revision;
		}
		/*
		//Busco los datos el modulo en la composicion del padre
		if(isset($id_padre)) {
			$padre = $this->get_padre($id_padre);
			$tabla_comp_padre = $padre->tabla_composicion_cursor_hijo($this);
			$datos_en_padre = $tabla_comp_padre->get();
			$datos['puntaje_en_padre'] = $datos_en_padre['puntaje'];
		}*/
		return $datos;
	}
	
	function set_datos($datos, $id_padre=null)
	{
		$this->control_edicion_no_bloqueada();
		//TODO setear datos en la revisión que se han editado en este form (regla, parametros, origen_creditos)
		$this->set_datos_revision($datos);
		$this->set_datos_elementos_plan($datos);
		// SI el elemento es nuevo, los datos basicos tambien van a la tabla elemento
		if($this->es_nuevo()) {
			if (isset($datos['nombre']) && isset($datos['nombre_abreviado'])) {
				$datos_e['nombre'] = $datos['nombre'];
				$datos_e['nombre_abreviado'] = $datos['nombre_abreviado'];
				$this->set_datos_elemento($datos_e);
			}
		}			
		// Seteo el puntaje del modulo en la composicion del padre
		/*
		if(isset($id_padre)) {
			$padre = $this->get_padre($id_padre);
			$tabla_comp_padre = $padre->tabla_composicion_cursor_hijo($this);
			$datos_comp['puntaje'] = $puntaje_en_padre;
			$tabla_comp_padre->set($datos_comp);
		}
		*/
	}

	function set_datos_hijos_hijos($datos_hijos)
	{
		foreach($datos_hijos as $hijo => $dato) {
			if(!isset($this->hijos[$hijo])) {
				throw new guarani_error("Error indicando el orden del hijo: el hijo no existe.");	
			}
			$this->hijos[$hijo]['orden'] = $dato['orden'];
			$this->validar_datos_hijos($dato);
			$tabla = $this->tabla_composicion();
			$tabla->set_cursor($this->hijos[$hijo]['cursor']);
			$tabla->set($dato);
		}
	}
	
	//---------------------------------------------------------------------------
	//-- Referencias a las filas de DTs que contienen los datos del modulo	
	//---------------------------------------------------------------------------

	function set_cursores($elemento, $revision, $elemento_plan)
	{
		$this->cursor_elemento = $elemento;
		$this->cursor_revision = $revision;
		$this->cursor_elemento_plan = $elemento_plan;
		toba::logger()->debug("-- Se asocio el ELEMENTO " . $this->get_id() . " con el cursor " . $this->cursor_elemento . " de la tabla ELEMENTOS");
		toba::logger()->debug("-- Se asocio el ELEMENTO " . $this->get_id() . " con el cursor " . $this->cursor_revision . " de la tabla REVISION");
		toba::logger()->debug("-- Se asocio el ELEMENTO " . $this->get_id() . " con el cursor " . $this->cursor_elemento_plan . " de la tabla ELEMENTO_PLAN");
	}

	/**
	*	Provee una referencia a la tabla [elementos] con el cursor seteado en
	*		en la fila correspondiente al elemento $this
	*	return toba_datos_tabla
	*/
	function tabla_elemento()
	{
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('elemento')->set_cursor($this->cursor_elemento);
		return $relacion->tabla('elemento');
	}

	/**
	*	Provee una referencia a la tabla [revision] con el cursor seteado en
	*		en la fila correspondiente al elemento $this
	*/
	function tabla_revision()
	{
		$relacion = $this->controlador->dep('elementos');
		//$relacion->tabla('elemento')->resetear();
		$relacion->tabla('revision')->set_cursor($this->cursor_revision);
		return $relacion->tabla('revision');
	}

	/**
	*	Provee una referencia a la tabla [elementos_plan] con el cursor seteado en
	*		en la fila correspondiente al elemento $this
	*/
	function tabla_elementos_plan()
	{
		$this->tabla_revision(); // se invoca para setear los cursores del padre
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('elementos_plan')->set_cursor($this->cursor_elemento_plan);
		return $relacion->tabla('elementos_plan');
	}
	
	/**
	*	Provee una referencia a la tabla [modalidad] con los cursores seteados en
	*		en la filas correspondiente al elemento $this
	*/
	function tabla_modalidad()
	{
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('elementos_plan')->set_cursor($this->cursor_elemento_plan);
		return $relacion->tabla('modalidad');
	}
	
	/**
	*	Provee una referencia a la tabla [elementos_ra] con los cursores seteados en
	*		en la filas correspondiente al elemento $this
	*/
	function tabla_elementos_ra()
	{
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('elemento')->set_cursor($this->cursor_elemento);
		return $relacion->tabla('elementos_ra');
	}
	
	
	/**
	*	Provee una referencia a la tabla [comsposicion] con los cursores seteados en
	*		en la filas correspondiente al elemento $this
	*/
	function tabla_composicion()
	{
		$relacion = $this->controlador->dep('elementos');
		$relacion->tabla('revision')->set_cursor($this->cursor_revision);
		return $relacion->tabla('composicion');
	}
	
	/**
	*	Provee una referencia a la tabla [comsposicion] con el cursor seteado en
	*		en la fila correspondiente al HIJO pasado como parametro
	* @param plan_elemento $hijo Referencia al hijo del que se desea la fila que representa la inclusion del mismo en el padre
	*/	
	function tabla_composicion_cursor_hijo(plan_elemento $hijo)
	{
		$tabla = $this->tabla_composicion();
		$cursor = $this->hijos[$hijo->get_id()]['cursor'];
		$tabla->set_cursor($cursor);
		return $tabla;
	}

	//----------------------------------------------------------------
	//-- VALIDACION de datos del modulo
	//----------------------------------------------------------------

	function es_compartible()
	{
		if(!isset($this->compartible)) {
			$datos = $this->tabla_elemento()->get();
			$this->compartible = ($datos['compartible'] == 'S');
		}
		return $this->compartible;
	}
	
	function no_es_compartible()
	{
		return (! $this->es_compartible());
	}

	function tiene_ancestro_compartible()
	{
		//ei_arbol($this->padres);

		foreach ($this->padres as $padre) {
			if ($padre->es_compartible()) {
				return true;
			} else {
				if ($padre->tiene_ancestro_compartible()) {
					return true;
				}
			}
		}
		return false;
	}

	
	function validar_datos_hijos($dato)
	{
		// puntaje
		if (isset($dato['puntaje'])) {
			if ($dato['puntaje'] <= 0) {
				throw new guarani_error("Los puntajes de los componentes deben ser mayor a Cero");
			}
		}
	}

	//----------------------------------------------------------------
	//--  Controles  -------------------------------------------------
	//----------------------------------------------------------------

	protected function control_eliminar()
	{
		parent::control_eliminar();	
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible eliminar el modulo porque esta bloqueado");
		}
	}
	
	protected function control_modificar_datos_elemento()
	{
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible modificar el modulo porque esta bloqueado");
		}
	}
	
	protected function control_modificar_datos_revision()
	{
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible modificar el modulo porque esta bloqueado");
		}
	}

	protected function control_guardar()
	{
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible guardar el modulo porque esta bloqueado");
		}		
	}

    function puede_eliminar()
	{
		return (! $this->esta_bloqueado());
	}

	protected function validar_descendencia_ciclica($hijo,$rama='')
	{
        $control = 0;
		$ancestro = $this;
		do {
            $control ++;
			if ($ancestro->get_id() == $hijo->get_id()) {
				$rama = '<B>'.$ancestro->get_nombre().'</B>'.'->'.$rama;
				throw new guarani_error("'".$hijo->get_nombre()."' no puede agregarse porque sería descendiente de sí mismo ($rama)");
			}
			$rama = $ancestro->get_nombre().'->'.$rama;
			$ancestros = $ancestro->get_padres();
			foreach ($ancestros as $ancestro) {
				$ancestro->validar_descendencia_ciclica($hijo,$rama);
			}
            if ($ancestro->es_raiz()) {
               break;
            }
            if ($control == 5000) {
                throw new guarani_error("Error al controlar descendencia cíclica. Probablemente el plan tenga un error en su estructura.");
            }
		} while (true);
	}
	
	protected function control_agregar_hijo($hijo)
	{
		if(! $this->es_tipo_hijo_valido($hijo->get_subtipo())){
			toba::logger()->error("plan_modulo: no es posible agregar un elemento '".$hijo->get_tipo()
									."' - '".$hijo->get_subtipo()."' en un padre '".$this->get_tipo()."' - '".
									$this->get_subtipo()."'");
			throw new guarani_error("No es posible agregar el elemento '".$hijo->get_nombre()."' en el módulo '".$this->get_nombre()."'.");
		}
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible agregar hijos al modulo porque esta bloqueado");
		}
       	//Valido si no estoy asociando un elemento como descendiente de él mismo dentro del plan
		$this->validar_descendencia_ciclica($hijo,'<B>'.$hijo->get_nombre().'</B>');
	}

	function es_tipo_hijo_valido($hijo_subtipo)
	{
		return guarani::catalogo_elementos_plan()->es_hijo_valido($this->subtipo, $hijo_subtipo);
	}

	protected function control_eliminar_hijo()
	{
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible eliminar hijos del modulo porque esta bloqueado");
		}
	}
	
	protected function control_ordenar_hijo()
	{
		if($this->esta_bloqueado()){
			throw new guarani_error("No es posible ordenar los hijos del modulo porque esta bloqueado");
		}
	}

	//-------------------------------------------------------------
	//-- BLOQUEOS
	//-------------------------------------------------------------
	
	function esta_bloqueado()
	{	
		return false;
        //Si tiene un ancestro compartible
        return $this->tiene_ancestro_compartible();
		/*
		SELECT 	COUNT(*) 
		FROM 	sga_modulo_revision_plan c1_mrpv, 
			sga_planes_versiones c1_pv 
		WHERE 	c1_mrpv.plan_version = c1_pv.plan_version
		AND	c1_mrpv.modulo_revision = 9
		AND	c1_pv.plan_version <> 3
		--AND	c1_pv
		*/
	}

	//----------------------------------------------------------------
	//-- DEBUG -------------------------------------------------------
	//----------------------------------------------------------------

	function get_info_hijos()
	{
		$datos = array();
		$id = 0;
		foreach($this->hijos as $hijo) {
			if (!$hijo['eliminado']) {
				$h = $hijo['instancia'];
				$datos[$id]['id'] = $h->get_id();
				$datos[$id]['entidad_tipo'] = $h->get_tipo();
				$datos[$id]['entidad_subtipo'] = $h->get_subtipo();
				//Levanto datos del hijo en padre
				$tabla = $this->tabla_composicion_cursor_hijo($h);
				$datos_composicion = $tabla->get();
				// Datos del elemento (hijo) en el Plan
				$tabla_elem_plan = $h->tabla_elementos_plan();
				$datos_elementos_plan = $tabla_elem_plan->get();
				$datos[$id]['nombre'] = $datos_elementos_plan['nombre'];
				// Datos del elemento (hijo)
				$tabla_elem = $h->tabla_elemento();
				$datos_elementos = $tabla_elem->get();
				if(isset($datos_elementos['codigo'])) {
					$datos[$id]['codigo'] = $datos_elementos['codigo'];
				}
				$datos[$id]['orden'] = $datos_composicion['orden'];
				$datos[$id]['puntaje'] = $datos_composicion['puntaje'];
				if (isset($datos_elementos_plan['creditos'])) {
					$datos[$id]['creditos'] = $datos_elementos_plan['creditos'];
				}
				$id++;
			}
		}
		return $datos;
	}

}
?>
