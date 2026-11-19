<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class cn_ent_optativas_de_actividad_generica extends cn_entidad
{
	protected $s__modo_optativas_temporales = true;

	//---------- Generales --------------------------------
	
	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios();
	}

	function modo_optativas_temporales()
	{
		return $this->s__modo_optativas_temporales;
	}

	//-----------------------------------------------------------------------------------
	//---- Carga de datos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function cargar($id)
	{
		$this->dep('datos')->cargar($id);

		$this->tabla('optativas_aux')->resetear();
		// Trae las optativas de la tabla temporal, con sus correspondientes atributos en los diferentes planes versioes, 
		// que estén en los planes versiones de la materia genérica. Debe cargarse aparte porque al no tener un campo que
		// indique la materia genérica, se cargaban las optativas de la genérica en todos los planes que se encontraran
		// configuradas temporalmente, aunque fueran planes de otras genéricas.
		$datos_generica = $this->get_datos_materia();
		$optativas_planes_temporales = toba::consulta_php('co_elementos')->get_atributos_optativas_temporales_en_planes_versiones_materia_generica($datos_generica['elemento_revision']);
		$this->tabla('optativas_aux')->cargar_con_datos($optativas_planes_temporales);
		// Recupera las modalidades definidas en las tablas temporales, asociadas a los elementos planes anteriores.
		$modalidades_optativas_temporales = toba::consulta_php('co_planes')->get_modalidades_elementos_planes_temporales($optativas_planes_temporales);
		$this->tabla('optativas_modalidad_aux')->cargar_con_datos($modalidades_optativas_temporales);

		$this->tabla('optativas')->resetear();
		$optativas_planes = toba::consulta_php('co_elementos')->get_atributos_optativas_en_planes_versiones_editables_materia_generica($datos_generica['elemento_revision']);
		$this->tabla('optativas')->cargar_con_datos($optativas_planes);
		$modalidades_optativas = toba::consulta_php('co_planes')->get_modalidades_elementos_planes($optativas_planes);
		$this->tabla('optativas_modalidad')->cargar_con_datos($modalidades_optativas);

		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	//---------- Materia Genérica -------------------------

	function get_datos_materia()
	{
		return $this->tabla('actividad_generica')->get();
	}
	
	function es_generica_por_puntaje()
	{
		$datos = $this->get_datos_materia();
		if (isset($datos['regla']) && ($datos['regla'] == regla::por_puntaje_aprobadas || $datos['regla'] == regla::por_puntaje_cursadas)) {
			return true;
		} else {
			return false;
		}
	}
	
	//---------- Componentes de la Genérica ---------------
	
	function get_optativas_generica()
	{
	    return $this->tabla('actividad_componentes_aux')->get_filas();
	}	

	function set_optativas_generica($datos)
	{
		$this->tabla('actividad_componentes_aux')->procesar_filas($datos);
	}
	
	// Setea el cursor en una de las optativas componentes de la materia genérica actual.
	function seleccionar_componente_optativa($actividad, $optativa_confirmada)
	{
		$this->s__modo_optativas_temporales = ($optativa_confirmada == comunes::si) ? false : true;
		$tabla_actividad_componentes = $this->s__modo_optativas_temporales ? $this->tabla('actividad_componentes_aux') : $this->tabla('actividad_componentes');
		$id = $tabla_actividad_componentes->get_id_fila_condicion(array('elemento_hijo' => $actividad));
		if (!empty($id)) {
			$tabla_actividad_componentes->set_cursor(current($id));
		}
	}
	
	function deseleccionar_componente_optativa()
	{
		$this->tabla('actividad_componentes_aux')->resetear_cursor();
	}
	
	function get_puntaje_componente_optativa_seleccionada()
	{
		if ($this->tabla('actividad_componentes_aux')->hay_cursor()) {
			$datos = $this->tabla('actividad_componentes_aux')->get();
			if (isset($datos['puntaje'])) {
				return $datos['puntaje'];
			}
		}
	}
	
	function set_puntaje_componente_optativa_seleccionada($puntaje)
	{
		if ($this->tabla('actividad_componentes_aux')->hay_cursor()) {
			$this->tabla('actividad_componentes_aux')->set($puntaje);
		}
	}
	
	//---------- Atributos Optativas ----------------------
	
	function get_datos_optativas()
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		return $tabla_optativas->get_filas();
	}

	// Setea el cursor en una de las configuraciones de atributos para un plan version dado de la optativa actual.
	function seleccionar_optativa_plan_version($actividad, $plan_version)
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		$id = $tabla_optativas->get_id_fila_condicion(array('elemento_revision' => $actividad, 'plan_version' => $plan_version));	
		if (!empty($id)) {
			$tabla_optativas->set_cursor(current($id));
		}
	}
	
	function get_datos_optativa_seleccionada()
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		if ($tabla_optativas->hay_cursor()) {
			return $tabla_optativas->get();
		}
	}
	
	function set_datos_optativa_seleccionada($datos)
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		if ($tabla_optativas->hay_cursor()) {
			$this->validar_datos_atributos_optativa_plan_version($datos);
			$tabla_optativas->set($datos);	
		}
	}
	
	function deseleccionar_optativa_plan_version()
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		$tabla_optativas->resetear_cursor();	
	}
	
	function hay_optativa_plan_version_seleccionado()
	{
		$tabla_optativas = $this->s__modo_optativas_temporales ? $this->tabla('optativas_aux') : $this->tabla('optativas');
		return $tabla_optativas->hay_cursor();	
	}
	
	function optativa_seleccionada_existe_en_plan()
	{
		$datos = $this->get_datos_optativa_seleccionada();
		if (!empty($datos) && ($datos['optativa_existe_en_plan'] == comunes::si)) {
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Si modificadas es True, la optativa ya se encuentran definida para el plan versión, asociada a
	 * otra genérica, por lo que debe traerse de la base para edición (de atributos) y no darse de alta como nueva fila.
	 */
	function agregar_atributos_optativa($atrib_optativa, $modificada=false)
	{
		if ($modificada) {
			/**
			 * Se fuerza estado de Update en lugar de Insert, porque la fila ya está en la BD para otra genérica. 
			 * Se anexan los datos al Dt.
			 */
			$this->tabla('optativas_aux')->anexar_datos(array($atrib_optativa));
		} else {
			 $this->tabla('optativas_aux')->nueva_fila($atrib_optativa);
		}
	}
	
	//---------- Modalidades Optativas --------------------
	
	function get_datos_optativas_modalidad()
	{
		$tabla_optativas_modalidad = $this->s__modo_optativas_temporales ? $this->tabla('optativas_modalidad_aux') : $this->tabla('optativas_modalidad');
		return $tabla_optativas_modalidad->get_filas();
	}

	function set_datos_optativas_modalidad($datos)
	{
		$tabla_optativas_modalidad = $this->s__modo_optativas_temporales ? $this->tabla('optativas_modalidad_aux') : $this->tabla('optativas_modalidad');
		$tabla_optativas_modalidad->procesar_filas($datos);
	}
	
	/**
	 * Si modificadas es True, las modalidades ya se encuentran definidas para la optativa en el plan versión, asociadas a
	 * otra genérica, por lo que deben traerse de la base para edición y no darse de alta como nuevas filas.
	 */
	function agregar_modalidad_optativa_plan_version($modalidad, $modificadas)
	{
		if ($modificadas) {
			/**
			 * Se fuerza estado de Update en lugar de Insert, porque la fila ya está en la BD para otra genérica. 
			 * Se anexan los datos al Dt.
			 */
			$this->tabla('optativas_modalidad_aux')->anexar_datos(array($modalidad));
		} else {
			$this->tabla('optativas_modalidad_aux')->nueva_fila($modalidad);
		}
	}
	
	//-----------------------------------------------------
	
	/**
	 * Elimina la asociación de la optativa a la materia genérica, junto con todos sus registros para cada plan 
	 * versión, configuración de atributos y modalidades en cascada.
	 * 
	 */
	function eliminar_optativa($actividad) 
	{
		$id = $this->tabla('actividad_componentes_aux')->get_id_fila_condicion(array('elemento_hijo' => $actividad['revision_materia_optativa']));
		if (!empty($id)) {
			$this->tabla('actividad_componentes_aux')->eliminar_fila(current($id));
		}
	}
	
	/**
	 * Se utiliza para saber desde el datos tabla de componentes, si al eliminar una optativa que compone a la genérica,
	 * deben eliminarse también el registro de sus atributos en un plan version dado. Esto dependerá de si la misma 
	 * optativa en ese plan versión, es parte de otra genérica y está siendo editada en las tablas temporales.
	 */
	function optativa_en_otra_generica($id_fila_optativa)
	{
		$datos_generica = $this->tabla('actividad_generica')->get();
		$datos_optativa = $this->tabla('optativas_aux')->get_fila($id_fila_optativa);
		$elem_rev_gen = toba::db()->quote($datos_generica['elemento_revision']);
		$where = "sga_elementos_comp_aux.elemento_padre <> $elem_rev_gen";
		return toba::consulta_php('co_elementos')->optativa_temporal_de_generica_esta_en_plan_version($datos_optativa['elemento_revision'], $datos_optativa['plan_version'], $where);
	}
	
	function agregar_optativa($actividad)
	{
		// Agrega la optativa como un componente de la materia genérica.
		$this->add_datos_comp($actividad);
		/**
		 * Agrega los registros correspondientes a la optativa en cada plan-versión donde la materia genérica se
		 * encuentre (sga_elementos_plan_aux). Los atributos de la materia optativa se registrarán como los atributos
		 * genéricos del elemento si la misma no se encuentra ya en el plan versión, y con los ya existentes en 
		 * caso contrario.
		 */
		// Se setea el cursor en la optativa asociada como componente de la materia genérica.
		$this->seleccionar_componente_optativa($actividad['elemento_revision'], comunes::no);
		$datos_materia_generica = $this->get_datos_materia();
		
		// Recupera todos los planes versiones en los que se encuentra definida la materia genérica.
		$planes_versiones_mat_gen = toba::consulta_php('co_planes')->get_planes_elemento($datos_materia_generica['elemento']);
		// Para cada plan versión en el que se encuentra definido la materia genérica, debe agregarse la materia optativa con
		// sus correspondientes atributos.
		foreach ($planes_versiones_mat_gen as $plan_version) {
			$this->agregar_optativa_plan_version($actividad['elemento_revision'], $datos_materia_generica['elemento'], $plan_version['plan_version']);
		}
		$this->deseleccionar_componente_optativa();
	}

	function add_datos_comp($seleccion)
	{
		$datos = $this->get_optativas_generica();
		$esta = false;
		foreach ( $datos as $key => $value ) {
			if ($value['elemento_hijo'] == $seleccion['elemento_revision']) {
				$esta = true;
				break;
			}
		}
	    
		if (!$esta) {
	        $dato_nuevo[0] = array(	'elemento_hijo' => $seleccion['elemento_revision'],
	                   				'puntaje' => 0,
	    			   				'x_dbr_clave' 	=> null,
	    							'apex_ei_analisis_fila' 	=> 'A'
	                               );
	        $this->set_optativas_generica($dato_nuevo);    
		}
	}
	
	/**
	 * Agrega el registro de la optativa en la tabla temporal de elementos-plan para la actividad genérica y el plan-versión dados.
	 * 
	 * Si la optativa ya está agregada de manera temporal (sga_elementos_plan_aux) para el plan-versión en alguna otra materia genérica,
	 * los atributos del elemento-plan se recuperan de dicho registro.
	 * En caso de editarse, al sincronizar los datos deben afectar también a los atributos de la optativa
	 * en el plan-versión para la otra materia genérica. 
	 * 
	 * En caso contrario y si la optativa ya está definida para ese plan versión en los datos definitivos (sga_elementos_plan),
	 * los atributos y modalidades asociadas a la misma se recuperan de ese registro y no se podrán modificar.
	 * 
	 * Sino, los atributos del elemento-plan serán los genéricos del elemento.
	 * 
	 * Es decir, la optativa en el plan-versión se considera única independientemente de la genérica a la que está asociada.
	 */
	function agregar_optativa_plan_version($actividad, $mat_gen, $plan_version)
	{
		// Se verifica si la optativa ya existe en el plan versión en los datos TEMPORALES (siendo editada para otra genérica).
		if (toba::consulta_php('co_elementos')->optativa_temporal_de_generica_esta_en_plan_version($actividad, $plan_version)) {
			$datos_optativa_plan_version_temporal = toba::consulta_php('co_elementos')->get_datos_temporales_optativa_en_plan_version($actividad, $plan_version);
			// La optativa se agrega al DT con los datos que se recuperan de la base. Luego la fila se marca como 
			// modificada, para que al sincronizar se sobreescriban los datos editados en el registro ya existente 
			// para el par (elemento_revision, plan_version) en la tabla sga_elementos_plan_aux.
			// Se procede de forma análoga para las modalidades asociadas a la actividad.
			$datos_optativa_plan_version_temporal['plan_version'] = $plan_version;
			// El elemento revisión se asigna por el cursor seteado en la tabla padre.
			$this->agregar_atributos_optativa($datos_optativa_plan_version_temporal, true);
			$this->seleccionar_optativa_plan_version($actividad, $plan_version);
			$this->agregar_modalidades_optativa_plan_version($datos_optativa_plan_version_temporal['elemento_plan'], true);
			$this->deseleccionar_optativa_plan_version();
		} else {
			// Se verifica si la optativa ya existe en el plan version en los datos CONFIRMADOS.
			$datos_optativa_plan_version = toba::consulta_php('co_elementos')->get_datos_actividad_en_plan_version($actividad, $plan_version);
			if (!empty($datos_optativa_plan_version)) {
				$datos_optativa_plan_version['plan_version'] = $plan_version;
				$datos_optativa_plan_version['optativa_existe_en_plan'] = comunes::si;
				// El elemento revisión se asigna por el cursor seteado en la tabla padre.
				$this->agregar_atributos_optativa($datos_optativa_plan_version);
				$this->seleccionar_optativa_plan_version($actividad, $plan_version);
				$this->agregar_modalidades_optativa_plan_version($datos_optativa_plan_version['elemento_plan']);
				$this->deseleccionar_optativa_plan_version();
			} else {
				// Se recuperan los atributos genéricos definidos para el elemento correspondiente a la optativa a agregar.
				// La optativa se agrega como una fila nueva ya que será un nuevo registro en sga_elementos_plan_aux.
				$atrib_optativa = toba::consulta_php('co_elementos')->get_datos_atributos_actividad($actividad);
				$atrib_optativa['plan_version'] = $plan_version;
				$atrib_optativa['optativa_existe_en_plan'] = comunes::no;
				// El elemento revisión se asigna por el cursor seteado en la tabla padre.
				$this->agregar_atributos_optativa($atrib_optativa);
			}
		}
	}
	
	/**
	 * Si una optativa dada ya existe en un plan versión, se configuran las mismas modalidades en la tabla temporal
	 * asociandola a la materia genérica. Si los datos son temporales, las filas se deben recuperar de la base para
	 * edición y no dar de alta como filas nuevas.
	 *
	 */
	function agregar_modalidades_optativa_plan_version($elemento_plan, $modificadas=false)
	{
		if (!$modificadas) {
			// Se recuperan las modalidades que tiene asociadas la optativa para ese plan-version.
			$modalidades = toba::consulta_php('co_planes')->get_modalidad_elemento_plan($elemento_plan);
		} else {
			// Se recuperan las modalidades que tiene asociadas la optativa para ese plan-version en las tablas temporales.
			$modalidades = toba::consulta_php('co_planes')->get_modalidad_elemento_plan_temporal($elemento_plan);
		}
		foreach ($modalidades as $modalidad) {
			// El elemento plan se asigna por el cursor seteado en la tabla padre.
			$this->agregar_modalidad_optativa_plan_version($modalidad, $modificadas);
		}
	}
	
	//---------- Validaciones -----------------------------
	
	function validar_datos_atributos_optativa_plan_version($datos)
	{
		$this->validar_creditos($datos);
		$this->validar_horas($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Verifica que los créditos, mínimo y máximo se hayan cargado de forma correcta. Esto es:
	 * Si se ingresa un mínimo, debe haber un máximo y viceversa. El mínimo debe ser menor o igual al máximo.
	 * Si se ingresan los créditos, los mismos deben estar entre el mínimo y el máximo.
	 */
	function validar_creditos($datos)
	{
		if ((isset($datos['creditos_min']) && !isset($datos['creditos_max'])) 
			|| (!isset($datos['creditos_min']) && isset($datos['creditos_max']))) {
				
			if (isset($datos['creditos_min'])) {
				$this->set_error('dato_faltante', array('Créditos Max'));
			} else {
				$this->set_error('dato_faltante', array('Créditos Min'));
			}
		} else {
			if (isset($datos['creditos_min'])) {
				if ($datos['creditos_min'] >= $datos['creditos_max']) {
					$this->set_error('800_valor_menor', array('Creditos Max', 'Creditos Min'));
				} else {
					if (isset($datos['creditos'])) {
						if (! (($datos['creditos'] >= $datos['creditos_min']) && ($datos['creditos'] <= $datos['creditos_max']))) {
							$this->set_error('800_valor_entre', array('Créditos', 'Créditos Min', 'Créditos Max'));
						}
					}
				}
			}
		}
	}
	
	/**
	 * Verifica que se hayan ingresado las horas semanales y totales correctamente. Esto es que la cantidad de horas
	 * semanales sea menor o igual a la cantidad de horas totales.
	 */
	function validar_horas($datos)
	{
		if (isset($datos['horas_totales']) && isset($datos['horas_semanales'])) {
			if ($datos['horas_totales'] < $datos['horas_semanales']) {
				$this->set_error('800_valor_mayor', array('Hs. Semanales', 'Hs. Totales'));
			}
		}
	} 
}
?>