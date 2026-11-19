<?php

/**
 * Administra los elementos que componen un plan de estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_ent_plan_elementos extends guarani_pers_cn
{
	protected $s__id_plan_version;
	protected $elementos;					// Acceso a los elementos por indice
	protected $s__elemento_raiz = null;		// Nodo raiz del arbol de elementos
	protected $s__proximo_id = 100000;
	protected $s__indice_elementos_cargados = array();
	protected $s__indice_elementos_eliminados = array();
	protected $manejador_correlativas = null;		// Manejador de las correlativas del plan
	protected $manejador_certificados = null;		// Manejador de los certificados del plan
	protected $s__bloqueo_compartibles = false;
	//Antes de grabar, almacena las entidades que tienen correlativas para evitar llamar al eliminardor de correlativas
	protected $entidades_con_condiciones_correlativas = null;
	protected $s__ver_optativas;

	/**
	 * @ignore
	 */
	function ini()
	{
		if (isset($this->s__elemento_raiz)) {
			$this->_inicializar_elementos($this->s__elemento_raiz);
		}
	}

	function destruir()
	{
		if (isset($this->s__elemento_raiz)) {
			$this->_finalizar_elementos($this->s__elemento_raiz);
		}
		parent::destruir();
	}

	/**
	 * 	Devuelve una referencia al manejador de restricciones de este plan
	 */
	function manejador_correlativas()
	{
		if (!isset($this->manejador_correlativas)) {
			if (isset($this->s__id_plan_version)) {
				$this->manejador_correlativas = toba::cn('manejador_correlativas');
				$this->manejador_correlativas->cargar($this->s__id_plan_version, false);
				$this->manejador_correlativas->desactivar_transacciones_anidadas();
			} else {
				throw new guarani_error('No es posible instanciar el manejador de correlativas porque el plan es nuevo!');
			}
		}
		return $this->manejador_correlativas;
	}

	/**
	 * 	Devuelve una referencia al manejador de certificados de este plan versión
	 */
	function manejador_certificados()
	{
		if (!isset($this->manejador_certificados)) {
			if (isset($this->s__id_plan_version)) {
				$this->manejador_certificados = toba::cn('manejador_certificados_plan');
				$this->manejador_certificados->cargar($this->s__id_plan_version, false);
				$this->manejador_certificados->desactivar_transacciones_anidadas();
			} else {
				throw new guarani_error('No es posible instanciar el manejador de certificados porque el plan es nuevo!');
			}
		}
		return $this->manejador_certificados;
	}
	
	function set_bloqueo_compartibles($bloquear = true)
	{
		$this->s__bloqueo_compartibles = $bloquear;
	}

	function ver_optativas()
	{
		if (!isset($this->s__ver_optativas)) {
			$this->s__ver_optativas = (guarani::parametros()->get_valor('pro_ver_optativas_en_estructura_plan') == comunes::si) ? true : false;
		}
		return $this->s__ver_optativas;
	}

	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------
	//-- API alto nivel (sobre el total de los elementos)
	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------

	/**
	 * Carga el grafo de elementos que componen el plan
	 */
	function cargar($id_plan_version)
	{
		$this->resetear();
		toba::logger()->debug("%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% Carga de ELEMENTOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");
		$this->s__elemento_raiz = $this->_construir_plan($id_plan_version);
		$this->s__id_plan_version = $id_plan_version;
	}

	/**
	 * Inicializa el grafo de elementos de un plan nuevo
	 */
	function inicializar_estado()
	{
		$this->resetear();
		$this->s__elemento_raiz = $this->crear_modulo_raiz();
	}

	/**
	 * @ignore
	 */
	protected function crear_modulo_raiz()
	{
		$e = $this->_modulo_crear(elemento::subtipo_raiz);
		$e->set_nombre('Raíz del plan');
		return $e;
	}

	//-------------------------------------------------------------------------------

	function hay_cambios()
	{
		$cambios_internos = guarani_arreglos::count($this->s__indice_elementos_eliminados) > 0;
		return $cambios_internos || $this->dep('elementos')->hay_cambios();
	}

	/**
	 * Recorre las filas de elementos del plan a sincronizar, verificando si se les ha seteado a los mismos 
	 * su atributo 'cobrable' como 'S' o 'N', para informarlo a Sanavirón-Quilmes:
	 * Si se dio de alta el elemento en el plan y se marcó como cobrable (POST),
	 * si se editó el elememento en el plan, se verifica si ya estaba informada a SQ. Si es así se realiza un PUT
	 * actualizando la información del atributo cobrable (puede que no se haya modificado, pero se informa el estado S
	 * o N de todas formas). 
	 * si se editó el elemento en el plan, seteando la actividad como cobrable y la misma no estaba informada a SQ se
	 * realiza el POST, invocando en cada caso al servicio que corresponde.
	 * @param int $propuesta id de la propuesta asociada al plan versión que se está editando/dando de alta.
	 * @param string $cod_prop código de la propuesta asociada al plan versión que se está editando/dando de alta.
	 * @param string $nombre_prop nombre de la propuesta asociada al plan versión que se está editando/dando de alta.
	 * @param array $ids_insert conjunto de filas que se agregaron a los elementos del plan.
	 * @param array $ids_update conjunto de filas que fueron modificadas, dentro de los elementos del plan.
	 * @param array $ids_elementos_delete conjunto de elementos que fueron eliminados, dentro de los elementos del plan.
	 */
	function marcar_actividades_plan_cobrables_sq($propuesta, $cod_prop, $nombre_prop, $ids_insert, $ids_update, $ids_elementos_delete)
	{		
		$msjs_errores_sq = '';

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras_descripcion($propuesta);
		$contextos = [];
		// Si existen Responsables Académicas para la propuesta las paso como 'contextos'
		if (!empty($ras)) {

			$contextos = array_map(function($ra) {
				return [
					"codigo"		=> $ra["responsable_academica"],
					"descripcion"	=> $ra["nombre"]
				];
			}, $ras);
			
		}

		$areas_tematicas = toba::consulta_php('co_propuestas')->get_areas_tematicas_propuesta($propuesta);
		$tags = [];
		// Si existen Áreas Temáticas para la propuesta las paso como 'tags'
		if (!empty($areas_tematicas)) {

			$tags = array_map(function($area_tematica) {
				return [
					"descripcion"	=> $area_tematica["nombre"],
				];
			}, $areas_tematicas);
			
		}

		if (!empty($ids_insert)) {
			// Recorro cada elemento agregado al plan, y si es 'cobrable' informo a SQ.
			foreach ($ids_insert as $id_fila_elem_plan) {		
				if ($this->es_elemento_plan_cobrable($id_fila_elem_plan)) {
					$elemento_atrib = $this->get_elemento_de_elemento_plan($id_fila_elem_plan);
					
					// Cuando se agrega y elimina un elemento del plan (las dos acciones antes de guardar) viene la "Raíz del plan" en lugar del elemento agregado
					if ($elemento_atrib['entidad_subtipo'] == g3entidad::subtipo_raiz) {
						continue;
					}
					
					$elem_plan_atrib = $this->get_elemento_plan_atributos($id_fila_elem_plan);
					$nombre_modulo = $this->get_nombre_modulos($elemento_atrib['elemento'], $elem_plan_atrib['plan_version']);

					$id = "{$propuesta}|{$elemento_atrib['elemento']}";
					$params = [
						'codigo' => "{$cod_prop}|{$elemento_atrib['codigo']}",
						'descripcion' => "{$nombre_prop}|{$elem_plan_atrib['nombre_abreviado']}",						
						'tags' => $tags,
						'contextos' => $contextos,
						'tipo' => cobro::TIPO_CURSO_EXTENSION,
						'estado' => cobro::ESTADO_SERVICIO_ACTIVO
					];

					// Se verifica si la actividad fue informada en SQ.
					$datos_sq_actividad = cobro::obtener_actividad_cobrable($id);
					// Si la Actividad no estaba informada a SQ, se retorna el arreglo vacío, si no el arreglo con sus datos.
					if (!empty($datos_sq_actividad)) {
						// Si la actividad ya está en sanaviron, actualizo la info		
						try {
							$resp = cobro::actualizar_actividad_cobrable($id, $params);
						} catch (guarani_error $e) {
							$msjs_errores_sq .= $e->get_mensaje();
						}
					} else {
						// Si la actividad no existe en sanaviron entonces la creo	
						$params['id'] = $id;
						try {
							$resp = cobro::crear_servicio($params);
						} catch (guarani_error $e) {
							$msjs_errores_sq .= $e->get_mensaje();
						}
					}
				}
			}
		}
		
		/**
		 * Para cada fila correspondiente a elementos para que se modificaron sus atributos en el Plan, 
		 * se verifica si ya estaba informada o no a SQ, y en cada caso si el atributo cobrable se seteó en S o N.
		*/		
		if (!empty($ids_update)) {
			// Recorro cada elemento modificado en el plan.
			foreach ($ids_update as $id_fila_elem_plan) {
				$elemento_atrib = $this->get_elemento_de_elemento_plan($id_fila_elem_plan);
				
				// Cuando se actualiza y elimina un elemento del plan (las dos acciones antes de guardar) viene la "Raíz del plan" en lugar del elemento actualizado
				if ($elemento_atrib['entidad_subtipo'] == g3entidad::subtipo_raiz) {
					continue;
				}
				
				$elem_plan_atrib = $this->get_elemento_plan_atributos($id_fila_elem_plan);
				$nombre_modulo = $this->get_nombre_modulos($elemento_atrib['elemento'], $elem_plan_atrib['plan_version']);

				$id = "{$propuesta}|{$elemento_atrib['elemento']}";
				$params = [
					'codigo' => "{$cod_prop}|{$elemento_atrib['codigo']}",
					'descripcion' => "{$nombre_prop}|{$elem_plan_atrib['nombre_abreviado']}",
					'tags' => $tags,
					'contextos' => $contextos,
					'tipo' => cobro::TIPO_CURSO_EXTENSION,
				];

				if ($elem_plan_atrib['cobrable'] == comunes::si) {
					$params['estado'] = cobro::ESTADO_SERVICIO_ACTIVO;
				} else {
					//Se la actualiza con el estado inactivo
					$params['estado'] = cobro::ESTADO_SERVICIO_INACTIVO;
				}
				
				// Se verifica si la actividad fue informada en SQ.
				$datos_sq_actividad = cobro::obtener_actividad_cobrable($id);
				// Si la Actividad no estaba informada a SQ, se retorna el arreglo vacío, si no el arreglo con sus datos.
				if (!empty($datos_sq_actividad)) {
					// Si la actividad ya está en sanaviron, actualizo la info					
					try {
						// Aunque no se haya modificado el atributo cobrable, se actualiza la info, ya que no se
						// cuenta con la data de si se modificó este campo luego de la sincronización. No afecta 
						// aunque en algún caso pueda ser redundante.
						$resp = cobro::actualizar_actividad_cobrable($id, $params);
					} catch (guarani_error $e) {
						$msjs_errores_sq .= $e->get_mensaje();
					}
				} else if ($elem_plan_atrib['cobrable'] == comunes::si) {
					// Si la Actividad no estaba informada en SQ y se seteó el elemento como Cobrable, se informa a SQ.
					$params['id'] = $id;
					try {
						$resp = cobro::crear_servicio($params);
					} catch (guarani_error $e) {
						$msjs_errores_sq .= $e->get_mensaje();
					}
				}
			}
		}
		
		if (!empty($ids_elementos_delete)) {
			// Recorro cada elemento eliminado del plan.
			foreach ($ids_elementos_delete as $id_elemento_delete) {
				
				$id = "{$propuesta}|{$id_elemento_delete}";
				
				// Se verifica si la actividad fue informada en SQ.
				$datos_sq_actividad = cobro::obtener_actividad_cobrable($id);
				// Si la Actividad no estaba informada a SQ, se retorna el arreglo vacío, si no el arreglo con sus datos.
				if (!empty($datos_sq_actividad)) {
					// Si la actividad ya está en sanaviron la borro
					try {
						$resp = cobro::eliminar_servicio($id);
					} catch (guarani_error $e) {
						$msjs_errores_sq .= $e->get_mensaje();
					}
				}
			}
		}
		
		return $msjs_errores_sq;
	}

	/**
	 * Retorna los ids de las filas de la tabla de elementos asociados al plan, con sus atributos, que deben
	 * sincronizarse, ya sea por alta, baja o modificaciòn.
	 * @param str $abm tipo de operaciòn asociada a las filas que se desea recuperar ('i': alta, 'd': baja, 'u': modificaciòn)
	 * @return array arreglo que contiene los ids de las filas de esta tabla a sincronizar, segùn la condiciòn abm.
	 */
	function get_id_filas_a_sincronizar_elementos_plan($abm)
	{
		return $this->dep('elementos')->tabla('elementos_plan')->get_id_filas_a_sincronizar(array($abm));
	}
	
	/**
	 * 
	 * @param type $id_fila
	 */
	function get_cambios_fila_modificada_elemento_plan($id_fila)
	{
		return $this->dep('elementos')->tabla('elementos_plan')->get_cambios_fila($id_fila);
	}

	/**
	 * Verifica el valor del atributo 'cobrable' del elemento asociado al Plan.
	 * @param integer $id_fila id interno de la fila de elementos_plan, para el cual se desea recuperar el campo cobrable.
	 * @return boolean true/false segùn si 'cobrable' = 'S'/'N'
	 */
	function es_elemento_plan_cobrable($id_fila)
	{
		$cobrable = $this->dep('elementos')->tabla('elementos_plan')->get_fila_columna($id_fila, 'cobrable');
		return ($cobrable == comunes::si);
	}

	/**
	 * Retorna todos los atributos configurados para la asociaciòn del Elemento correspondiente al Plan seleccionado.
	 * @param type $id_fila id interno de la fila de elementos_plan, para el cual se desea recuperar el atributo cobrable.
	 * @return array atributos correspondientes a la fila de la tabla sga_elementos_plan.
	 */
	function get_elemento_plan_atributos($id_fila)
	{
		return $this->dep('elementos')->tabla('elementos_plan')->get_fila($id_fila);
	}

	/**
	 * Retorna el elemento y los atributos bàsicos del mismo, correspondiente a la fila de elementos-plan dada.
	 * @param integer $id_fila id de fila interna correspondiente a la tabla elementos_plan.
	 * @return array atributos del elemento asociado al elemento-revisiòn y elemento-plan con los atributos dados.
	 */
	function get_elemento_de_elemento_plan($id_fila)
	{
		$elem_revision = $this->dep('elementos')->tabla('elementos_plan')->get_id_fila_padre('revision', $id_fila);
		$elem = $this->dep('elementos')->tabla('revision')->get_id_fila_padre('elemento', $elem_revision);
		return $this->dep('elementos')->tabla('elemento')->get_fila($elem);
	}

	/**
	 * Guarda en la base el estado el grafo de elementos que componen el plan
	 */
	function sincronizar($id_plan_version)
	{
		toba::logger()->debug("##############------------------ Inicio sincronizar ELEMENTOS PLAN ------------------------###############");
		//Seteo el plan_version en todos los elementos
		$this->dep('elementos')->tabla('elementos_plan')->set_columna_valor('plan_version', $id_plan_version);
		//Disparo la grabacion recursiva
		$this->_guardar();
		$clave_db_elemento_raiz = $this->s__elemento_raiz->get_clave_elemento();
		$revision_elemento_raiz = $this->s__elemento_raiz->get_clave_revision();
		toba::logger()->debug("##############------------------ Fin sincronizar elementos PLAN (revision elemento raiz: $revision_elemento_raiz) --------------------###############");
		$this->s__indice_elementos_eliminados = array();
		$this->s__indice_elementos_cargados = array();
		$this->regenerar_indice_elementos_cargados($this->s__elemento_raiz);
		return $revision_elemento_raiz;
	}

	/**
	 * Elimina los elementos del plan
	 */
	function eliminar()
	{
		$this->s__elemento_raiz->eliminar();
		$this->_guardar();
	}

	/**
	 * Dispara la Sincronizacion Recursiva del grafo de elementos que componen el plan
	 */
	private function _guardar()
	{
		if (isset($this->s__id_plan_version)) {
			// Se guardan las entidades con condiciones correlativas para despues no tener que recorrer cada una. en la eliminacion del plan
			$this->entidades_con_condiciones_correlativas = toba::consulta_php('co_correlativas')->get_entidades_tienen_condiciones($this->s__id_plan_version);
		}
		$this->s__elemento_raiz->guardar();
	}

	/**
	 *  Retorna las entidades que tienen correlativas, este atributo se setea solo cuando se llama al guardar y solo se usa desde
	 *  los elementos_plan cuando se estan sincronizando
	 *
	 * @return <type>
	 */
	function _get_entidades_con_correlativas()
	{
		return $this->entidades_con_condiciones_correlativas;
	}

	/**
	 * @ignore
	 */
	function desactivar_transacciones_anidadas()
	{
		$this->dep('elementos')->get_persistidor()->desactivar_transaccion(true);
	}

	/**
	 * @ignore
	 */
	function resetear()
	{
		toba::logger()->debug("%%%%%%% RESETEAR elementos %%%%%%%%%");
		unset($this->elementos);
		unset($this->s__id_plan_version);
		$this->indice_elementos_activos = array();
		$this->s__indice_elementos_eliminados = array();
		unset($this->s__elemento_raiz);
		$this->s__indice_elementos_cargados = array();
		$this->dep('elementos')->resetear();
	}

	/**
	 * Busca el ID de elemento a partir de un $id de objeto cargado
	 * Si el objeto es nuevo (no fue cargado desde la DB) retorna null
	 */
	protected function get_id_elemento_cargado($id_interno)
	{
		foreach ($this->s__indice_elementos_cargados as $id_elemento => $id_objeto) {
			if ($id_objeto == $id_interno) {
				return $id_elemento;
			}
		}
		return null;
	}

	protected function regenerar_indice_elementos_cargados($nodo)
	{
		$id_elemento = $nodo->get_clave_elemento();
		$id = $nodo->get_id();
		$this->s__indice_elementos_cargados[$id_elemento] = $id;
		if (!$nodo->es_hoja()) {
			foreach ($nodo->get_hijos() as $hijo) {
				$this->regenerar_indice_elementos_cargados($hijo);
			}
		}
	}

	//-------------------------------------------------------------------------------
	//-- Proveer info a elementos
	//-------------------------------------------------------------------------------

	/**
	 * @ignore
	 */
	function get_responsable_academica()
	{
		return $this->controlador->get_responsable_academica();
	}

	function get_plan_version()
	{
		return $this->s__id_plan_version;
	}

	function get_plan()
	{
		$plan = $this->controlador->dep('datos')->tabla('plan')->get_columna('plan');
		return $plan;
	}

	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------
	//-- API para el CI
	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------

	/**
	 * Retorna el arbol de edicion que muestra el CI editor
	 * @return plan_elemento_modulo_raiz
	 */
	function arbol_edicion()
	{
		return $this->s__elemento_raiz->get_elemento_gui();
	}

	/**
	 * Retorna el elemento raiz del plan de estudios
	 * @return plan_elemento_modulo_raiz
	 */
	function elemento_raiz()
	{
		if (!isset($this->s__elemento_raiz)) {
			throw new guarani_error('Elementos PLAN - ERROR: No existe el modulo raiz.');
		}
		return $this->s__elemento_raiz;
	}

	function elemento($id)
	{
		if ($this->existe_elemento($id)) {
			return $this->elementos[$id];
		} else {
			throw new guarani_error('No existe el elemento del plan solicitado (' . $id . ')');
		}
	}

	function existe_elemento($id)
	{
		return isset($this->elementos[$id]);
	}

	function bloqueo_compartibles()
	{
		return $this->s__bloqueo_compartibles;
	}

	/**
	 * Retorna todos los elementos que tienen como correlativo al elemento (id_elemento)
	 *
	 * @param <type> $id_elemento
	 * @return <type>
	 */
	function tienen_como_correlativa_a($id_elemento)
	{
		$datos = Array();
		if (!$this->elemento($id_elemento)->es_nuevo()) {
			$elemento = $this->elemento($id_elemento)->get_clave_elemento();
			$correlativas_plan = $this->manejador_correlativas();
			$datos = $correlativas_plan->tienen_como_correlativa_a($elemento);
		}
		return $datos;
	}
	
	/**
	 * Retorna todos los Certificados del Plan Versión a los que el elemento dado (id_elemento) está asociados.
	 *
	 * @param integer $id_elemento id del elemento que se va a eliminar, para el cual se recuperan los
	 * Certificados del Plan Versión asociados.
	 * @return array 
	 */
	function certificados_asociados_a($id_elemento)
	{
		$datos = array();
		if (!$this->elemento($id_elemento)->es_nuevo()) {
			$elemento = $this->elemento($id_elemento)->get_clave_elemento();
			$certificados_plan = $this->manejador_certificados();
			$datos = $certificados_plan->certificados_asociados_a($elemento);
		}
		return $datos;
	}

	//-------------------------------------------------------------------------------
	//-- API de alteracion publica del arbol de elemento
	//-------------------------------------------------------------------------------

	/**
	 * 
	 * @param type $cantidad
	 * @param type $nombre
	 */
	function validar_orientacion($cantidad, $nombre)
	{
		$msj_error = "No es posible crear la Orientación '{$nombre}'.";
		// Verifica que la cantidad sea un número y sea menor a 100.
		if (!is_numeric($cantidad) || ($cantidad >= 100)) {
			throw new guarani_error($msj_error . " La Cantidad debe ser un número menor a 100");
		} 
		// Verifica que no exista en el plan un modulo agrupador de orientaciones con el mismo nombre.
		// Recorre los elementos asociados al CN, verificando que no sea un elemento eliminado en la sesión,
		// cuyo tipo sea agrupador de orientaciones y que coincida en el nombre.
		foreach ($this->elementos as $elem) {
			if (!$elem->esta_eliminado()
				&& ($elem->get_subtipo() == elemento::subtipo_agrupador_orientaciones)
				&& ($elem->get_nombre() == $nombre)) {
				throw new guarani_error($msj_error . " El nombre ya existe para otro módulo Agrupador de Orientaciones del Plan");
			}
		}
	}
	
	/**
	 * Agrega un modulo al plan
	 * @return plan_elemento_modulo Modulo creado
	 * @param int $padre ID del modulo padre
	 * @param int $tipo ID del tipo de modulo que se desea crear
	 * @param string $nombre Nombre del modulo
	 */
	function modulo_crear($padre, $subtipo, $nombre)
	{
		$this->control_edicion_no_bloqueada($subtipo);
		//$this->control_existe_elemento($padre);
		if (!($padre instanceof plan_elemento)) {
			$padre = $this->elemento($padre);
		}
		if (!elemento::puede_tener_hijos($padre->get_subtipo())) {
			throw new guarani_error('El elemento padre seleccionado no puede tener hijos');
		}
		if (!$padre->es_tipo_hijo_valido($subtipo)) {
			throw new guarani_error('No es posible crear el modulo porque el modulo padre no acepta el tipo');
		}
		$e = $this->_modulo_crear($subtipo, $padre);
		$e->set_nombre($nombre);
		return $e;
	}

	/**
	 * Asocia un conjunto de elementos a un modulo
	 * @param int $padre ID del modulo padre
	 * @param array $elemento ID de los ELEMENTOS que se desean asociar
	 */
	function elementos_asociar($padre, $elementos)
	{
		$this->control_edicion_no_bloqueada();
		//$this->control_existe_elemento($padre);

		if (!is_array($elementos)) {
			throw new guarani_error('Plan elementos: error indicando los elementos a asociar.');
		}
		foreach ($elementos as $elemento) {
			try {
				$this->elemento_asociar($padre, $elemento);
			} catch (guarani_error $e) {
				toba::notificacion()->agregar($e->getMessage(), 'info');
			}
		}
	}
	
	/**
	 * Asocia un elemento a un modulo
	 * @return plan_elemento_actividad Actividad asociada
	 * @param int $padre ID del modulo padre
	 * @param int $elemento ID del ELEMENTO que se desea asociar
	 */
	function elemento_asociar($padre, $elemento)
	{
		$this->control_edicion_no_bloqueada();
		//$this->control_existe_elemento($padre);
		if (!($padre instanceof plan_elemento)) {
			$padre = $this->elemento($padre);
		}
		if (!elemento::puede_tener_hijos($padre->get_subtipo())) {
			throw new guarani_error('El elemento padre seleccionado no puede tener hijos');
		}
		$info = toba::consulta_php('co_elementos')->get_info_elemento($elemento);
		if (!$padre->es_tipo_hijo_valido($info['entidad_subtipo'])) {
			//if (! $padre->es_tipo_hijo_valido($info['entidad_tipo'],$info['entidad_subtipo'])) {
			throw new guarani_error("No es posible asociar el elemento '{$info['nombre']}'. El módulo padre no acepta hijos del tipo: '{$info['entidad_subtipo_nombre']}'");
		}
		if ($padre->esta_bloqueado()) {
			throw new guarani_error("No es posible asociar el elemento '{$info['nombre']}'. El módulo padre esta bloqueado");
		}
		$e = $this->_elemento_asociar($elemento, $padre);
		return $e;
	}
	
	/**
	 * Asocia un conjunto de módulor RTF al Plan, en un nodo seleccionado. Los módulos RTF a asociar son sólo cáscaras,
	 * por lo cual no pueden asociarse directamente al plan, si no que deben crearse módulos nuevos (en sga_elementos),
	 * con los datos de las cáscaras seleccionadas, para luego sí poder asociarlos al plan, con el nodo dado como padre.
	 * @param type $padre id del nodo padre donde se asociarán los módulos rtf
	 * @param type $elementos módulos rtf a crear en el Plan.
	 */
	function agregar_modulos_rtf($padre, $modulos_rtf)
	{
		$this->control_edicion_no_bloqueada();
		
		if (!is_array($modulos_rtf)) {
			throw new guarani_error('Plan elementos: error indicando los elementos a asociar.');
		}
		foreach ($modulos_rtf as $modulo) {
			try {
				$this->modulo_rtf_crear($padre, $modulo);
			} catch (guarani_error $e) {
				toba::notificacion()->agregar($e->getMessage(), 'info');
			}
		}
	}

	/**
	 * Agrega un modulo RTF al plan
	 * @return plan_elemento_modulo_rtf Modulo RTF creado
	 * @param int $padre ID del modulo padre
	 * @param str $modulo_rtf Código del módulo RTF a agregar (para copiar los datos de su cáscara).
	 */
	function modulo_rtf_crear($padre, $modulo_rtf)
	{
		$subtipo = elemento::subtipo_modulo_rtf;
		$this->control_edicion_no_bloqueada($subtipo);
		
		if (!($padre instanceof plan_elemento)) {
			$padre = $this->elemento($padre);
		}
		if (!elemento::puede_tener_hijos($padre->get_subtipo())) {
			throw new guarani_error('El elemento padre seleccionado no puede tener hijos');
		}
		if (!$padre->es_tipo_hijo_valido($subtipo)) {
			throw new guarani_error('No es posible crear el modulo porque el modulo padre no acepta el tipo');
		}
		$datos_modulo = toba::consulta_php('co_elementos')->get_datos_modulo_rtf($modulo_rtf);
		$e = $this->_modulo_rtf_crear($padre, $datos_modulo);
		return $e;	
	}
	
	/**
	 * ELIMINA un ELEMENTO de un padre
	 * @param int $padre ID del modulo padre
	 * @param array $elemento ID de los ELEMENTOS que se desean asociar
	 */
	function elemento_eliminar($nodo, $padre)
	{
		$subtipo = $this->elemento($nodo)->get_subtipo();
		$this->control_edicion_no_bloqueada($subtipo);
		// Si el elemento tiene correlativas o certificados asociados no se permite su eliminación de la 
		// estructura del Plan (primero deberá desasociarse).
		if ($this->s__id_plan_version) {
			$this->control_elemento_sin_correlativas_ni_certificados_asociados($nodo);
		}
		if ($this->controlador()->es_version_activa()) {
			/** Si la versión está activa/vigente y se pueden eliminar elementos (porque el plan es
			 *	de tipo personalizado, para administrar cursos o corresponde a una propuesta vocacional),
			 *  deben realizarse validaciones sobre el nodo a eliminar.
			 */
			$this->control_eliminar_elemento_plan_activo($nodo);
		}
		//$this->control_existe_elemento($padre);
		//$this->control_existe_elemento($nodo);
		if (!($padre instanceof plan_elemento_modulo)) {
			$padre = $this->elemento($padre);
			if (!($padre instanceof plan_elemento_modulo)) {
				throw new guarani_error('El elemento padre seleccionado no es un modulo');
			}
		}
		$this->_elemento_eliminar($padre, $nodo);
	}
	
	//-------------------------------------------------------------------------------
	//--- CONTROLES de EDICION
	//-------------------------------------------------------------------------------

	function control_edicion_no_bloqueada($subtipo = null)
	{
		if ($this->controlador->edicion_version_bloqueada()) {
			throw new guarani_error('ERROR: No es posible editar');
		}
		if ($this->controlador->edicion_plan_bloqueada() && ($subtipo == elemento::subtipo_agrupador_orientaciones || $subtipo == elemento::subtipo_orientacion)) {
			throw new guarani_error('ERROR: No es posible editar');
		}
	}

	function control_existe_elemento($elemento)
	{
		if (!$this->existe_elemento($elemento)) {
			throw new guarani_error('ERROR: El elemento seleccionado no existe');
		}
	}

	/**
	 * Controla si es posible eliminar las modalidades pasadas como parámetro de todos los elementos del plan
	 *
	 * @param <type> $modalidades_a_modificar
	 */
	private function control_eliminar_modalidad_plan($modalidades_a_modificar)
	{
		$modalidades_de_elementos = $this->dep('elementos')->tabla('modalidad')->get_filas(null, false, false);
		$elementos = Array();
		foreach ($modalidades_de_elementos as $key => $modalidad_elemento) {
			$modalidad_elemento['id_padre'] = $this->dep('elementos')->tabla('modalidad')->get_id_fila_padre('elementos_plan', $modalidad_elemento['x_dbr_clave']);
			$modalidades_de_elementos[$key]['id_padre'] = $modalidad_elemento['id_padre'];
			$elementos[] = $modalidad_elemento['id_padre'];
			foreach ($modalidades_a_modificar as $modalidad) {
				if ($modalidad[apex_ei_analisis_fila] == 'B' && $modalidad_elemento['modalidad'] == $modalidad['modalidad']) {
					unset($modalidades_de_elementos[$key]);
				}
			}
		}
		$elementos_restantes = aplanar_matriz($modalidades_de_elementos, 'id_padre');
		foreach ($elementos as $elemento) {
			if (!in_array($elemento, $elementos_restantes)) {
				throw new guarani_error('No se puede quitar la modalidad del plan ya que está asignada a actividades del mismo');
			}
		}
	}

	/**
	 * Valida y si es posible elimina las modalidades de los elementos según se haya modificado la modalidad del Plan
	 *
	 *
	 * @param $modalidades
	 *
	 */
	function eliminar_modalidad_elementos($modalidades_a_modificar)
	{
		$this->control_eliminar_modalidad_plan($modalidades_a_modificar);
		$modalidades_de_elementos = $this->dep('elementos')->tabla('modalidad')->get_filas(null, false, false);
		$modalidades_a_procesar = Array();
		foreach ($modalidades_a_modificar as $modalidad) {
			if ($modalidad[apex_ei_analisis_fila] == 'B') {
				foreach ($modalidades_de_elementos as $key => $modalidad_elementos) {
					if ($modalidad['modalidad'] == $modalidad_elementos['modalidad']) {
						$modalidades_a_procesar[$key] = $modalidad_elementos;
						$modalidades_a_procesar[$key]['apex_ei_analisis_fila'] = 'B';
					}
				}
			}
		}
		$this->dep('elementos')->tabla('modalidad')->procesar_filas($modalidades_a_procesar);
	}

	//-------------------------------------------------------------------------------
	//--- CONTROLES de ELIMINACIÓN
	//-------------------------------------------------------------------------------

	/**
	 * Si el elemento a Eliminar está asociado como Correlativa de otros elementos del Plan, o está
	 * asociado a Certificados de Plan, no se permite eliminarlo de la estructura sin antes desasociarlo.
	 * @param int $nodo
	 */
	function control_elemento_sin_correlativas_ni_certificados_asociados($nodo)
	{
		$e = $this->elemento($nodo);
		$datos_elemento = $e->get_datos();
		$mensaje_correlativas = '';
		$mensaje_certificados = '';
		/** Chequea si el elemento que se está por eliminar es correlativa de otros para alertar
		*  al usario antes de confirmar la eliminación.
		*/
		$elementos = $this->tienen_como_correlativa_a($nodo);
		foreach ($elementos as $elemento) {
			$mensaje_correlativas .= "(" . $elemento['codigo'] . ") " . $elemento['nombre'] . ",\n";
		}
		/** Chequea si el elemento que se está por eliminar está asociado a Certificados del Plan Versión 
		*  para alertar al usario antes de confirmar la eliminación.
		*/
		$certificados = $this->certificados_asociados_a($nodo);
		foreach ($certificados as $certificado) {
			$mensaje_certificados .= "(" . $certificado['codigo'] . ") " . $certificado['nombre'] . ",\n";
		}
	
		$mensaje = '';
		if ($mensaje_correlativas != '') {
			$mensaje_correlativas = substr($mensaje_correlativas, 0, strlen($mensaje_correlativas) - 2);
			$mensaje = 'ATENCION!\\n\\n' . $mensaje_correlativas . '\\n tienen como correlativa a ' . $datos_elemento['nombre'] . ' \\n\\n';
		}
		if ($mensaje_certificados != '') {
			$mensaje_certificados = substr($mensaje_certificados, 0, strlen($mensaje_certificados) - 2);
			if ($mensaje != '') {
				$mensaje = $mensaje . 'Y \\n\\n' . $datos_elemento['nombre'] . ' está asociada a los Certificados ' . '\\n' . $mensaje_certificados . ' \\n\\n ';
			} else {
				$mensaje = 'ATENCION!\\n\\n' . $datos_elemento['nombre'] . ' está asociada a los Certificados ' . '\\n' . $mensaje_certificados . ' \\n\\n ';
			}
		}
	
		if ($mensaje != '') {
			$msj_error = 'No es posible eliminar ' . $datos_elemento['nombre'] . ' sin desasociarla.';
			throw new guarani_error($mensaje . '</br>' . $msj_error);
		}
	}
	
	/**
	 * Si el elemento a eliminar es de tipo actividad y el plan es de tipo personalizado,
	 * gestiona cursos o corresponde a una propuesta vocacional (es decir que su estructura
	 * puede modificarse estando activo) debe verificarse, en el primer caso para el alumno
	 * asociado, en el segundo y tercero que no exista ningún alumno asociado al´plan-versión, que:
	 * El alumno asociado al plan no tenga la Actividad Cursada (vw_regularidades),
	 * no tenga la actividad aprobada o desaprobada (vw_hist_academica),
	 * no esté inscripto para Cursar la Actividad (sga_insc_cursadas)
	 * no esté inscripto para Rendir la Actividad (sga_insc_examenes).
	 * También se verifica que la Actividad no este asociada a un Certificado del Plan.
	 * Si el elemento es de tipo Módulo, deben verificarse las mismas condiciones para cada uno 
	 * de sus hijos, para que dicho módulo raíz pueda eliminarse.
	 * @param int $nodo
	 */
	function control_eliminar_elemento_plan_activo($nodo)
	{
		$e = $this->elemento($nodo);
		$tipo = $e->get_tipo();
		$datos_elemento = $e->get_datos();
		/**
		 * Si el elemento a eliminar no tiene seteado el dato 'elemento', significa que aún no
		 * fue sincronizado a la base (recién se agregó al DR y se intenta eliminar). En este 
		 * caso no es necesario ejecutar las validaciones ya que como el elemento aún no existe
		 * en la base no puede tener asociación en el Plan Versión a ningún alumno ni certificado
		 * (este caso puede darse cuando se agregan módulos creándolos desde cero).
		 */
		if (isset($datos_elemento['elemento'])) {
			if ($tipo == elemento::tipo_actividad) {
				$this->validar_eliminar_actividad_plan_activo($datos_elemento['elemento'], $datos_elemento['nombre']);
			} elseif ($tipo == elemento::tipo_modulo) {
				$hijos = $e->get_info_hijos();
				$this->validar_eliminar_modulo_plan_activo($datos_elemento['elemento'], $datos_elemento['nombre'], $hijos);
			}
		}
	}

	function validar_eliminar_modulo_plan_activo($id_elemento, $nombre, $hijos)
	{
		/**
		 * Primero se verifica que el módulo no esté asociado a un Certificado del Plan Versión. 
		 * Luego si los hijos componentes del módulo son Activades, y se trata de un Plan Personalizado, 
		 * se ejecutarán las mismas validaciones que para las actividades asociadas de manera
		 * directa a la raíz del Plan. Si, a su vez, son módulos, deben validarse sus hijos.
		 */
		$plan_version = $this->controlador->get_id_plan_version();
		if ($this->controlador()->es_plan_personalizado()) {
			$tipo_plan = planes::tipo_personalizado;
		} elseif ($this->controlador->es_plan_para_administrar_cursos()) {
			$tipo_plan = planes::tipo_estruct_curso_descr;
		} elseif ($this->controlador()->es_propuesta_vocacional()) {
			$tipo_plan = planes::tipo_vocacional_descr;
		}
		if (toba::consulta_php('co_planes')->actividad_asociada_a_certificado_plan_version($plan_version, $id_elemento)) {
			$descr = ' El módulo ' . $nombre;
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_elemento_en_certificado', array($descr, $tipo_plan));
			throw new guarani_error($msj);
		}
		try {
			foreach ($hijos as $hijo) {
				$this->control_eliminar_elemento_plan_activo($hijo['id']);
			}
		} catch (guarani_error $e) {
			$msj_mod = guarani_mensajes::get('800PRO_elim_plan_activo_modulo');
			$msj_activ = $e->get_mensaje();
			throw new guarani_error($msj_mod . '</br>' . $msj_activ);
		}
	}

	function validar_eliminar_actividad_plan_activo($id_elemento, $nombre)
	{
		//Planes modicables cuando están activos: Personalizados y Estructurados que Administran Cursos.
		$plan_version = $this->controlador()->get_id_plan_version();
		if ($this->controlador()->es_plan_personalizado()) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_alumno_de_plan_version($plan_version);
			$alumno = $datos_alumno['alumno'];
			$tipo_plan = planes::tipo_personalizado;
			$desc_alumno = " el alumno para el que el plan fue creado ";
		} else {
			$alumno = null;
			$tipo_plan = $this->controlador()->es_plan_para_administrar_cursos() ? planes::tipo_estruct_curso_descr : planes::tipo_vocacional_descr;
			$desc_alumno = " existe al menos un alumno que";
		}

		if (toba::consulta_php('co_alumnos')->tiene_inscripcion_a_cursada_actividad_plan_version($plan_version, $id_elemento, $alumno)) {
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_actividad_insc_cursada', array($nombre, $tipo_plan, $desc_alumno));
			throw new guarani_error($msj);
		}
		if (toba::consulta_php('co_alumnos')->tiene_inscripcion_a_examen_actividad_plan_version($plan_version, $id_elemento, $alumno)) {
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_actividad_insc_examen', array($nombre, $tipo_plan, $desc_alumno));
			throw new guarani_error($msj);
		}
		if (toba::consulta_php('co_planes')->actividad_asociada_a_certificado_plan_version($plan_version, $id_elemento)) {
			$descr = 'La actividad ' . $nombre;
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_elemento_en_certificado', array($descr, $tipo_plan));
			throw new guarani_error($msj);
		}
		if (toba::consulta_php('co_alumnos')->tiene_actividad_en_evaluaciones_plan_version($plan_version, $id_elemento, $alumno)) {
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_actividad_en_evaluacion', array($nombre, $tipo_plan, $desc_alumno));
			throw new guarani_error($msj);
		}
		if ((toba::consulta_php('co_alumnos')->tiene_actividad_en_historia_academica_plan_version($plan_version, $id_elemento, $alumno)) || (toba::consulta_php('co_alumnos')->tiene_actividad_en_regularidades_plan_version($plan_version, $id_elemento, $alumno))) {
			$msj = guarani_mensajes::get('800PRO_elim_plan_activo_actividad_hist_alumno', array($nombre, $tipo_plan, $desc_alumno));
			throw new guarani_error($msj);
		}
	}

	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------
	//--- Primitivas INTERNAS: mapeo [elementos grafo] <---> [objetos persistencia] -
	//-------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------

	/**
	 * Modulos nuevos
	 * @ignore
	 */
	protected function _modulo_crear($subtipo, $padre = null)
	{
		$this->debug("Evento: MODULO CREAR");
		$tipo = elemento::tipo_modulo;
		$clase = guarani::catalogo_elementos_plan()->get_clase_edicion($subtipo);
		$e = $this->_construir_elemento($clase, $tipo, $subtipo, true, true);
		toba::logger()->debug(">>>>>>--------->> inicio NUEVO MODULO '$clase' --> " . $e->get_id());
		// Creo filas en los DTs
		$id_elemento = $this->dep('elementos')->tabla('elemento')->nueva_fila();
		$this->dep('elementos')->tabla('elemento')->set_cursor($id_elemento);

		$id_revision = $this->dep('elementos')->tabla('revision')->nueva_fila();
		$this->dep('elementos')->tabla('revision')->set_cursor($id_revision);

		$id_elementos_plan = $this->dep('elementos')->tabla('elementos_plan')->nueva_fila();
		// Asigno las filas al objeto que las maneje
		$e->set_cursores($id_elemento, $id_revision, $id_elementos_plan);
		$e->ini();
		if (isset($padre)) {
			if (elemento::puede_tener_hijos($padre->get_subtipo())) {
				$this->_relacionar_padre_hijo($padre, $e, true);
			} else {
				throw new guarani_error('plan_elementos: Error agregando modulo. El padre no es un modulo valido');
			}
		}
		toba::logger()->debug(">>>>>--------- FIN NUEVO MODULO '$clase' --> " . $e->get_id());
		return $e;
	}

	function _modulo_rtf_crear($padre, $datos_modulo_rtf)
	{
		$this->debug("Evento: MODULO RTF CREAR");
		$tipo = elemento::tipo_modulo;
		$subtipo = elemento::subtipo_modulo_rtf;
		$clase = guarani::catalogo_elementos_plan()->get_clase_edicion($subtipo);
		$e = $this->_construir_elemento($clase, $tipo, $subtipo, true, true);
		toba::logger()->debug(">>>>>>--------->> inicio NUEVO MODULO RTF '$clase' --> " . $e->get_id());
		// Creo filas en los DTs
		$id_elemento = $this->dep('elementos')->tabla('elemento')->nueva_fila();
		$this->dep('elementos')->tabla('elemento')->set_cursor($id_elemento);
		
		$id_revision = $this->dep('elementos')->tabla('revision')->nueva_fila();
		$this->dep('elementos')->tabla('revision')->set_cursor($id_revision);
		
		$id_elementos_plan = $this->dep('elementos')->tabla('elementos_plan')->nueva_fila();
		
		// Asigno las filas al objeto que las maneje
		$e->set_cursores($id_elemento, $id_revision, $id_elementos_plan);
		
		/** En el ini se setean algunos valores default adicionales, más allá de los copiados de la cáscara de
		 *  sga_modulos_rtf.
		 */
		$e->ini($datos_modulo_rtf);
		if (isset($padre)) {
			if (elemento::puede_tener_hijos($padre->get_subtipo())) {
				$this->_relacionar_padre_hijo($padre, $e, true);
			} else {
				throw new guarani_error('plan_elementos: Error agregando modulo. El padre no es un modulo valido');
			}
		}
		toba::logger()->debug(">>>>>--------- FIN NUEVO MODULO RTF '$clase' --> " . $e->get_id());
		return $e;
 	}
	
	/**
	 * Agrega un elemento al plan
	 * 
	 * @param $elemento id del elemento a asociar
	 * @param $padre instancia del padre a la que se quiere asociar el $elemento
	 * @param $nuevo_en_padre 
	 *         
	 * @return unknown_type
	 * 
	 * //TODO: En el caso de los modulos hay que controlar que no tengan submodulos ya instanciados en el plan.
	 * Si los tienen, no hay que cargar a esos en las estructuras de persistencia...	
	 * 
	 */
	protected function _elemento_asociar($elemento, $padre, $nuevo_en_padre = true)
	{ //TODO
		$this->debug("Evento: ELEMENTO ASOCIAR");
		if (!elemento::puede_tener_hijos($padre->get_subtipo())) {
			throw new guarani_error('plan_elementos: Error asociando elemento. El padre no es válido');
		}
		if (isset($this->s__indice_elementos_cargados[$elemento])) {
			// El elemento ya fue cargado desde la DB
			$this->debug("El elemento ya fue cargado");
			$id = $this->s__indice_elementos_cargados[$elemento]; //Busco el ID del objeto
			if (isset($this->s__indice_elementos_eliminados[$elemento])) {
				// El elemento fue eliminado, recuperar
				$this->debug("El elemento fue eliminado en la sesion: recuperar");
				unset($this->s__indice_elementos_eliminados[$elemento]);
				if ($padre->posee_elemento_eliminado($id)) {
					// Se recupera el elemento adentro del mismo padre
					$this->debug("Recuperar hijo sobre el mismo padre: " . $padre->get_id());
					$padre->recuperar_hijo($id);
					$e = $this->elemento($id);
				} else {
					// Se recupera el elemento en un nuevo padre
					$this->debug("Recuperar hijo en un padre nuevo: " . $padre->get_id());
					$e = $this->elemento($id);
					$e->recuperar();
					$this->_relacionar_padre_hijo($padre, $e, $nuevo_en_padre);
				}
				return $e;
			} else {
				// El elemento no fue eliminado
				$e = $this->elemento($id);
				$nombre = $e->get_nombre();
				if ($padre->posee_elemento_asociado($id)) {
					throw new guarani_error("El elemento '$nombre' ya se encuentra instanciado en el módulo");
				} else {
					//if(elemento::puede_tener_hijos($e->get_subtipo())) {	//ATENCION: Codigo eliminado
					$this->_relacionar_padre_hijo($padre, $e, $nuevo_en_padre);
					//}
				}
				return $e;
			}
		} else {
			// El elemento no esta cargado, lo cargo desde la DB
			$this->debug("Cargo el elemento desde la DB");
			$sub_elementos = $this->cargar_elemento_db($elemento);
			$padres = array();
			foreach ($sub_elementos as $e) {
				$nueva_en_plan = true;
				$es_recien_creado = true;
				// Cuando el parámetro 'pro_ver_optativas_en_estructura_plan' tiene el valor 'N',
				// las optativas son parte del plan (sga_elementos_plan) pero no están cargadas.
				if (!$this->ver_optativas() &&
					isset($this->s__id_plan_version) &&
					$this->existe_elemento_en_plan_version($this->s__id_plan_version, $e['elemento'])) {
					$nueva_en_plan = false;
					$es_recien_creado = false;
				}
				$sub_elemento = $this->_construir_elemento_db($e, $nueva_en_plan, $es_recien_creado);

				$padres[$e['elemento_revision']] = $sub_elemento;
				if (isset($e['elemento_revision_padre'])) {
					$this->_relacionar_padre_hijo($padres[$e['elemento_revision_padre']], $sub_elemento, false);
				} else {
					$nodo_raiz = $sub_elemento;
				}
			}
			//-- 4) Asocio el elemento cargado con el elemento padre
			$this->_relacionar_padre_hijo($padre, $nodo_raiz, true);
			//-- 5) Si el elemento no es compartible, hay que crear una copia (se marcan las filas implicadas del datos_relacion como INSERT)
			if ($nodo_raiz->no_es_compartible($elemento)) {
				$nodo_raiz->copiar();
			}
			return $nodo_raiz;
		}
	}

	protected function _registrar_eliminacion_hijos($padre)
	{
		$hijos = $padre->get_info_hijos();
		foreach ($hijos as $hijo) {
			$id_elemento_hijo = $this->get_id_elemento_cargado($hijo['id']);
			if ($id_elemento_hijo && isset($this->s__indice_elementos_cargados[$id_elemento_hijo])) {
				$this->s__indice_elementos_eliminados[$id_elemento_hijo] = $hijo['id'];
			}
			$e = $this->elemento($hijo['id']);
			if ($e instanceof plan_elemento_modulo) {
				$this->_registrar_eliminacion_hijos($e);
			}
		}
	}

	protected function _elemento_eliminar($padre, $id_hijo)
	{
		$this->debug("Evento: ELEMENTO ELIMINAR");
		$id_elemento = $this->get_id_elemento_cargado($id_hijo);

		// si es modulo agrego a eliminados los hijos del modulo
		$e = $this->elemento($id_hijo);
		if ($e instanceof plan_elemento_modulo) {
			$this->_registrar_eliminacion_hijos($e);
		}
		$padre->eliminar_hijo($id_hijo);
		// Si el elemento fue cargado lo guardo en la lista de los eliminados

		if ($id_elemento && isset($this->s__indice_elementos_cargados[$id_elemento])) {
			$this->s__indice_elementos_eliminados[$id_elemento] = $id_hijo;
		}
	}

	/**
	 * 	Construye un plan desde la DB
	 * @ignore
	 */
	protected function _construir_plan($id_plan_version)
	{
		$elementos = $this->cargar_plan_db($id_plan_version);
		// Creo la estructura de objetos
		// Como la composicion viene ordenada del procedure, los padres se crean antes que sus hijos
		$padres = array();
		foreach ($elementos as $e) {
			$elemento = $this->_construir_elemento_db($e, false, false);
			if (elemento::puede_tener_hijos($e['entidad_subtipo'])) {
				//Indice para recuperar padres
				$padres[$e['elemento']] = $elemento;
			}
			if (isset($e['elemento_padre'])) {
				$this->_relacionar_padre_hijo($padres[$e['elemento_padre']], $elemento, false);
			} else {
				$nodo_raiz = $elemento;
			}
		}
		return $nodo_raiz;
	}

	/**
	 * @ignore
	 */
	function _relacionar_padre_hijo($padre, $hijo, $hijo_nuevo_en_padre = true)
	{
		//$this->debug("Relacion padre-hijo: Mapear cursores");
		if ($hijo_nuevo_en_padre) {
			$cursor = $this->_get_cursor_relacion_padre_hijo_nueva($padre, $hijo);
		} else {
			$cursor = $this->_get_cursor_relacion_padre_hijo_db($padre, $hijo);
		}
		$padre->agregar_hijo($hijo, $cursor, $hijo_nuevo_en_padre);
		toba::logger()->debug("-- Se asocio el ELEMENTO " . $hijo->get_id() . " - (" . $hijo->get_nombre() .
				")  Al padre " . $padre->get_id() . " - (" . $padre->get_nombre() .
				")  a travez del cursor " . $cursor . " de la tabla COMPOSICION");

		// Seteo el estado compartible. ATENCION: sacar el mecanismo este de set_atributo padre, oculta el api y se gana poco
		if ($this->bloqueo_compartibles()) {
			if ($padre->es_compartible() || $padre->tiene_ancestro_compartible()) {
				$hijo->set_atributo_padre($padre->get_id(), 'tiene_ancestro_compartible', true);
			} else {
				$hijo->set_atributo_padre($padre->get_id(), 'tiene_ancestro_compartible', false);
			}
		}
	}

	//--------------------------------------------------------------------------
	//-- Instanciacion
	//--------------------------------------------------------------------------

	/**
	 * Instancia un elemento y lo mapea con la informacion que le corresponde en el DATOS_RELACION de modulos
	 * 	Requiere que el datos_relacion haya sido cargado (Viene desde _construir_plan() o _elemento_asociar())
	 * @ignore
	 */
	protected function _construir_elemento_db($datos_elemento, $nueva_en_plan, $es_recien_creado)
	{
		if (isset($this->s__indice_elementos_cargados[$datos_elemento['elemento']])) {
			$id = $this->s__indice_elementos_cargados[$datos_elemento['elemento']];
			$e = $this->elemento($id);
			if (isset($this->s__indice_elementos_eliminados[$datos_elemento['elemento']])) {
				unset($this->s__indice_elementos_eliminados[$datos_elemento['elemento']]);
				$e->recuperar();
			}
		} else {
			//1)- Instancio el elemento
			$tipo = $datos_elemento['entidad_tipo'];
			$subtipo = $datos_elemento['entidad_subtipo'];
			$clase = guarani::catalogo_elementos_plan()->get_clase_edicion($subtipo);
			$e = $this->_construir_elemento($clase, $tipo, $subtipo, false, $es_recien_creado);
			//2) - Busco los CURSORES que le corresponden
			$this->_mapear_elemento_persistencia($e, $datos_elemento, $nueva_en_plan);
			//Agrego el elemento al indice de cargados
			//4)- Registro el elemento en la lista de cargados
			$this->s__indice_elementos_cargados[$datos_elemento['elemento']] = $e->get_id();
			toba::logger()->debug(">>>>-------- Construir elemento DB '$clase' [e={$datos_elemento['elemento']} r={$datos_elemento['elemento_revision']}] --> " . $e->get_id());
		}
		return $e;
	}

	/**
	 * @ignore
	 */
	protected function _construir_elemento($clase, $tipo, $subtipo, $nuevo = true, $es_recien_creado = false)
	{
		// Instancio el elemento
		$id = $this->_get_proximo_id_elemento();
		$e = new $clase($tipo, $subtipo, $id, $nuevo, $es_recien_creado);
		$e->set_controlador($this);
		$this->elementos[$id] = $e;
		return $e;
	}

	/**
	 * @ignore
	 */
	protected function _get_proximo_id_elemento()
	{
		return ++$this->s__proximo_id;
	}

	/**
	 * 	Regenera el indice de elementos y se asigna el controlador
	 * @ignore
	 */
	protected function _inicializar_elementos($nodo)
	{
		$id = $nodo->get_id();
		if (isset($this->elementos[$id]) && ($this->elementos[$id] !== $nodo)) {
			throw new guarani_error("Error: existen dos elementos con el ID " . $nodo->get_id());
		}
		//Guardo el ID del nodo en el indice
		$this->elementos[$id] = $nodo;
		$nodo->set_controlador($this);
		if (!$nodo->es_hoja()) {
			foreach ($nodo->get_hijos() as $hijo) {
				$this->_inicializar_elementos($hijo);
			}
		}
	}

	/**
	 * @ignore
	 * 	Elimina la referencia que los elementos tienen con este CN.
	 * 	Es para que no conserven instancias viejas de los elementos de toba en el futuro request (por la serializacion de php)
	 */
	protected function _finalizar_elementos($nodo)
	{
		$nodo->desreferenciar_controlador();
		if (!$nodo->es_hoja()) {
			foreach ($nodo->get_hijos() as $hijo) {
				$this->_finalizar_elementos($hijo);
			}
		}
	}

	//-------------------------------------------------------------------------------
	//-- RECUPERAR info de la DB ----------------------------------------------------
	//-------------------------------------------------------------------------------
	// Levantan informacion de la DB y cargan las estructuras de persistencia

	protected function cargar_plan_db($id_plan_version)
	{
		toba::logger()->info("%%%%%%%%%%%%%%%%%%%%%%%%%--*** INICIO cargar_plan_db: $id_plan_version --%%%%%%%%%%%%%%%%%%%%%%%%%");
		$parametros = array();
		if (!$this->ver_optativas()) {
			$parametros = array('incluir_componentes_genericas' => false);
		}
		$elementos = toba::consulta_php('co_planes')->get_contenido_plan_version($id_plan_version, $parametros);
		//print_r($elementos);
		//-- 1) Busco las claves de los elementos que tengo que cargar y la info
		$elementos_claves = array();
		$revisiones = array();
		foreach ($elementos as $e) {
			$elementos_claves[] = $e['elemento'];
			$revisiones[] = $e['elemento_revision'];
		}

		//-- 2) Armo los SQLs & Cargo las estructuras de datos
		if (guarani_arreglos::count($elementos) == 0) {
			throw new guarani_error("ELEMENTOS PLAN: Error en la construccion del: no se cargaron modulos");
		} else {
			$where_modulos['elemento'] = "( elemento IN (" . implode(',', $elementos_claves) . "))";
			$where_modulos['revision'] = "( elemento_revision IN (" . implode(',', $revisiones) . "))";
			$where_modulos['elementos_plan'] = "( plan_version = $id_plan_version )";
			$this->dep('elementos')->persistidor()->cargar_con_wheres($where_modulos);
		}

		//-- 3) Controlo que la estructura cargada en las estructuras de persistencia sea este bien  ----------------------
		$ok = true;

		$ele_dt = $this->dep('elementos')->tabla('elemento')->get_cantidad_filas(false);
		$ele_sql = guarani_arreglos::count(array_unique($elementos_claves));
		if ($ele_dt != $ele_sql) {
			toba::logger()->error("La cantidad de modulos de la consulta ($ele_sql) y del DT ($ele_dt) no coinciden");
			$ok = false;
		}
		$rev_dt = $this->dep('elementos')->tabla('revision')->get_cantidad_filas(false);
		$rev_sql = guarani_arreglos::count(array_unique($revisiones));
		if ($rev_dt != $rev_sql) {
			toba::logger()->error("La cantidad de revisiones de la consulta ($rev_sql) y del DT ($rev_dt) no coinciden");
			$ok = false;
		}
		$rp_dt = $this->dep('elementos')->tabla('elementos_plan')->get_cantidad_filas(false);
		if ($rev_dt != $rp_dt) {
			toba::logger()->error("La cantidad de revisiones ($rev_dt) y de asignaciones al plan ($rp_dt) no coinciden");
			$ok = false;
		}
		if (($ele_dt != $rev_dt) || ($ele_dt != $rev_dt) || ($ele_dt != $rp_dt)) {
			toba::logger()->error('No coincide la cantidad de registros de modulos, revisiones y rev_plan');
			$ok = false;
		}
		if (!$ok) {
			throw new guarani_error("Error de integridad cargando el plan de estudios");
		}
		toba::logger()->info("%%%%%%%%%%%%%%%%%%%%%%%%%-- FIN cargar_plan_db: $id_plan_version --%%%%%%%%%%%%%%%%%%%%%%%%%");
		return $elementos;
	}

	//-------------------------------------------------------------------------------

	protected function cargar_elemento_db($elemento)
	{
		$id_elemento_revision = toba::consulta_php('co_elementos')->get_ultima_revision_elemento($elemento);
		toba::logger()->info("%%%%%%%%%%%%%%%%%%%%%%%%%--*** INICIO cargar_elemento_db REVISION: $id_elemento_revision --%%%%%%%%%%%%%%%%%%%%%%%%%");
		$sub_elementos = toba::consulta_php('co_planes')->get_contenido_elemento($id_elemento_revision, $this->s__id_plan_version, true, true);
		//-- 1) Busco las claves de los elementos que tengo que cargar, separo los subelementos que ya estan cargados para no volver a cargarlos
		$elementos = array();
		$revisiones = array();
		$elementos_plan = array();
		foreach ($sub_elementos as $e) {
			if (!isset($this->s__indice_elementos_cargados[$e['elemento']])) {
				$elementos[] = $e['elemento'];
			}
			$revisiones[] = $e['elemento_revision'];
			// Cuando el parámetro 'pro_ver_optativas_en_estructura_plan' tiene el valor 'N',
			// las optativas son parte del plan (sga_elementos_plan) pero no están cargadas.
			if (!$this->ver_optativas() &&
				isset($this->s__id_plan_version) &&
				$this->existe_elemento_en_plan_version($this->s__id_plan_version, $e['elemento'])) {
				$elementos_plan[] = $e['elemento_revision'];
			}
		}
		//-- 2) Cargo los ELEMENTOS ----------------------------------------------
		$where_elementos['elemento'] = "( elemento IN (" . implode(',', $elementos) . "))";
		$where_elementos['revision'] = "( elemento_revision IN (" . implode(',', $revisiones) . "))";
		// En el caso normal la tabla 'sga_elementos_plan' no se tiene que cargar, porque se está incorporando el elemento a un nuevo plan.
		$where_elementos['elementos_plan'] = empty($elementos_plan) ? '(1 = 2)' : "(plan_Version = $this->s__id_plan_version AND elemento_revision IN (".implode(',', $elementos_plan).'))';
		$this->dep('elementos')->tabla('elemento')->resetear_cursor();
		$this->dep('elementos')->tabla('revision')->resetear_cursor();
		$this->dep('elementos')->persistidor()->cargar_con_wheres($where_elementos, false, true, false);
		toba::logger()->info("%%%%%%%%%%%%%%%%%%%%%%%%%-- FIN cargar_elemento_db REVISION: $id_elemento_revision --%%%%%%%%%%%%%%%%%%%%%%%%%");
		return $sub_elementos;
	}

	//--------------------------------------------------------------------------
	//-- MAPEO elemento/datos relacion
	//--------------------------------------------------------------------------
	// TODO: Analizar si no conviene meter esto en el clase raiz elemento

	protected function _mapear_elemento_persistencia($elemento, $datos_elemento, $nueva_en_plan)
	{
		$tabla_e = $this->dep('elementos')->tabla('elemento');
		$temp_fila = $tabla_e->get_id_fila_condicion(array('elemento' => $datos_elemento['elemento']), false, false);
		$cursores = guarani_arreglos::count($temp_fila);
		if ($cursores != 1) {
			throw new guarani_error("PLAN ELEMENTOS: Error cargando datos basicos del MODULO [{$datos_elemento['elemento']}]. Debe existir una unica ocurrencia para el ID. Cantidad: $cursores");
		}
		$id_elemento = $temp_fila[0];
		//- Revision
		$tabla_r = $this->dep('elementos')->tabla('revision');
		$temp_fila = $tabla_r->get_id_fila_condicion(array('elemento_revision' => $datos_elemento['elemento_revision']), false, false);
		$cursores = guarani_arreglos::count($temp_fila);
		if ($cursores != 1) {
			//print_r($tabla_r->get_filas());
			throw new guarani_error("PLAN ELEMENTOS: Error cargando datos de la REVISION [{$datos_elemento['elemento_revision']}]. Debe existir una unica ocurrencia para el ID. Cantidad: $cursores");
		}
		$id_revision = $temp_fila[0];
		if (!$nueva_en_plan) {  // El modulo ya existe en el plan
			//- Revision - PLAN
			$tabla_rp = $this->dep('elementos')->tabla('elementos_plan');
			$temp_fila = $tabla_rp->get_id_fila_condicion(array('elemento_revision' => $datos_elemento['elemento_revision']), false, false);
			$cursores = guarani_arreglos::count($temp_fila);
			if ($cursores != 1) {
				throw new guarani_error("PLAN ELEMENTOS: Error cargando datos de la ELEMENTOS-PLAN [{$datos_elemento['elemento_revision']}]. Debe existir una unica ocurrencia para el ID. Cantidad: $cursores");
			}
			$id_elementos_plan = $temp_fila[0];
		} else {	 // El modulo es nuevo en el plan, necesita un registro en elementos_plan
			$tabla_r->set_cursor($id_revision);
			$id_elementos_plan = $this->dep('elementos')->tabla('elementos_plan')->nueva_fila();
			$this->dep('elementos')->tabla('elementos_plan')->set_cursor($id_elementos_plan);
			// Agrego los datos por defecto de elementos_plan
			$elemento->setear_datos_basicos($datos_elemento['elemento']);
		}
		//3)- Asocio los cursores al objeto
		$elemento->set_cursores($id_elemento, $id_revision, $id_elementos_plan);
	}

	/**
	 * @ignore
	 */
	protected function _get_cursor_relacion_padre_hijo_db($padre, $hijo)
	{
		$clave_fila = array('elemento_padre' => $padre->get_clave_revision(),
			'elemento_hijo' => $hijo->get_clave_revision());
		//Busco el cursor correspondiente a la clave
		$tabla_r = $this->dep('elementos')->tabla('composicion');
		$temp_fila = $tabla_r->get_id_fila_condicion($clave_fila, false, false);
		$cursores = guarani_arreglos::count($temp_fila);
		if ($cursores != 1) {
			throw new guarani_error("ELEMENTO_MODULO: Error asociando HIJO con tabla composicion [" . implode(',', $clave_fila) .
			"]. Debe existir una unica ocurrencia para el ID. Cantidad: $cursores");
		}
		$cursor = $temp_fila[0];
		return $cursor;
	}

	/**
	 * @ignore
	 */
	protected function _get_cursor_relacion_padre_hijo_nueva($padre, $hijo)
	{
		$datos_composicion = array();
		// Si el hijo ya existia en la DB, agrego en el padre su ID
		if (!$hijo->es_nuevo()) {
			$datos_composicion['elemento_hijo'] = $hijo->get_clave_revision();
		}
		$datos_composicion['orden'] = $padre->get_orden_proximo_valor();
		$datos_composicion['puntaje'] = 1;
		$cursor = $padre->tabla_composicion()->nueva_fila($datos_composicion);
		return $cursor;
	}

	/**
	 * 	Despues de grabar un elemento, hay que guardar su ID en la composicion del padre
	 * 	Esta funcion actualmente se invoca desde el elemento hijo cuando se guarda
	 * 	@ignore
	 */
	function _agregar_relacion_hijo_sincronizado_padre($padre, $hijo)
	{
		$this->debug("Registrar ID hijo en padre:" . $padre->get_id() . " - hijo:" . $hijo->get_id());
		$tabla = $padre->tabla_composicion_cursor_hijo($hijo);
		$tabla->set(array('elemento_hijo' => $hijo->get_clave_revision()));
	}

	//----------------------------------------------------------------
	//-- DEBUG -------------------------------------------------------
	//----------------------------------------------------------------

	protected $utilizar_debug = false;
	protected $debug_inicio = true;

	function debug($texto)
	{
		if ($this->utilizar_debug) {
			//toba::logger()->info("PLAN:" . $texto);		
			if ($this->debug_inicio)
				echo "<br><br><br><br>";
			$this->debug_inicio = false;
			echo "PLAN:" . $texto . "<br>";
		}
	}
	
	function get_nombre_modulos($elemento, $plan_version)
	{
		return guarani::co('co_planes')->get_nombre_modulos($elemento, $plan_version);
	}
	
	function existe_elemento_en_plan_version($plan_version, $elemento)
	{
		return guarani::co('co_planes')->existe_elemento_en_plan_version($plan_version, $elemento);
	}
	
	function get_indice_elementos_eliminados()
	{
		return $this->s__indice_elementos_eliminados;
	}
}