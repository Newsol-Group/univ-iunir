<?php
/**
 * Representa una Comisión de una actividad
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class cn_ent_comision extends cn_entidad
{
	// Modela la relación entre bandas horarias y asignaciones.
	protected $s__mapeo_filas = array();
	protected $s__acepta_planes_personalizados = comunes::no;

	function inicializar_estado()
	{
		parent::inicializar_estado();
		$this->s__acepta_planes_personalizados = comunes::no;
	}

	function cargar($id)
	{
		$this->tabla('docentes')->persistidor()->activar_perfil_de_datos();

		$this->dep('datos')->cargar($id);
		$this->s__acepta_planes_personalizados = $this->tabla('comisiones')->get_columna('acepta_planes_personalizados');

		$this->dep('asignaciones')->resetear();
		$filtro = "sga_comisiones_bh.comision = {$id['comision']}";
		$asignaciones = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones($filtro);
		$this->dep('asignaciones')->cargar_con_datos($asignaciones);

		// Mapear bandas horarias con asignaciones.
		$bandas_horarias = $this->get_datos_bandas_horarias();
		foreach ($bandas_horarias as $banda) {
			$this->s__mapeo_filas[$banda[apex_datos_clave_fila]] = $this->get_id_asignacion(array('asignacion' => $banda['asignacion']));
		}

		$this->cargar_etiquetas();
		$this->verificar_editabilidad();
	}

	function eliminar()
	{
		$this->validar_editabilidad();
		//Se valida si la asignacion es usada por otra banda horaria.
		$this->verificar_asignacion();
		$this->desactivar_transacciones_anidadas();
		toba::db()->abrir_transaccion();
		try {
			$this->dep('datos')->eliminar_todo();
			$this->dep('asignaciones')->eliminar_todo();
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->resetear();
			throw new guarani_error($e->get_mensaje());
		}
	}
	
	/**
	 * Chequea si el campo 'Cobrable' es editable, de acuerdo a si ya existen alumnos
	 * inscriptos a la comision.
	 */
	function puede_editar_cobrable()
	{
		$id_comision = $this->tabla('comisiones')->get_columna('comision');
		$inscriptos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision($id_comision, true, '', false);
		return empty($inscriptos);
	}

	function verificar_editabilidad()
	{
		// Verifica si la entidad Comisión actualmente cargada puede ser editada, de otro 
		// modo llama a la función de la clase padre que bloquea la edición de la misma.
		$id_comision = $this->tabla('comisiones')->get_columna('comision');
		// No es editable si existen actas ya cerradas para la misma.
		$filtro = " sga_actas.estado = '" . acta::estado_cerrada . "'";
		$actas_cerradas = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_comision($id_comision, $filtro);
		if ($actas_cerradas > 0) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function marcar_comision_cobrable_sq($propuesta, $datos_comision, $cambios)
	{
		$id_externo = $datos_comision['comision'];
		// Estado: Si la comision es Activa, se envía C, si es baja se envía I y si es pendiente se envía A
		switch ($datos_comision['estado']) {
			case comunes::estado_activo :
				$estado = cobro::ESTADO_SERVICIO_CONFIRMADO;
				break;
			case comunes::estado_inactivo :
				$estado = cobro::ESTADO_SERVICIO_INACTIVO;
				break;
			case comunes::estado_pendiente :
				$estado = cobro::ESTADO_SERVICIO_ACTIVO;
				break;
		}
		$elemento = $datos_comision['elemento'];
		$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_actividad($elemento);
		$comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($id_externo));
		$docentes_comision = isset($comision['comision_docentes']) ? $comision['comision_docentes'] : "Sin docentes";

		$params = array(
			'codigo' => $id_externo,
			'descripcion' => $datos_comision['nombre'] . ' | ' . $datos_elemento['nombre'] . ' | ' . $docentes_comision,
			'servicio' => $propuesta['propuesta'] . '|' . $elemento,
			'estado' => $estado
		);
		
		// Si la comisión era NO cobrable y se pasa a cobrable 
		// o bien es la creacion de la misma y se pone como cobrable.
		if (((isset($cambios['cobrable']) && ($cambios['cobrable']['actual'] == comunes::si))) || (empty($cambios))) {
			// Se realiza un GET  para verificar si la Comision ya estaba informada:
			// Se obtiene a partir de la columna, porque en el caso de un Alta, la pk aun no tendra dato guardado en la tabla.
			$resp = cobro::obtener_comision_cobrable($id_externo);
			if (!empty($resp)) {
				// La comisión ya estaba informada en SQ. Se modifica su estado y condicion de cobrable.
				unset($params['codigo']);
				unset($params['descripcion']);
				unset($params['servicio']);
				cobro::actualizar_comision_cobrable($id_externo, $params);
			} else {
				$params['id'] = $id_externo;
				// La comision no estaba informada en SQ, se esta creando o poniendo por primera vez como cobrable.
				// Se notifica con un POST como cobrable y con su estado actual asociado.
				try {
					// La primera vez que se informa no se pasa estado (por defecto toma estado = 'A').
					unset($params['estado']);
					cobro::informar_comision_cobrable($params);
				} catch (guarani_error $e) {
					// Se definió que si se da el error de que la Actividad asociada a la comisión, 
					// no fue previamente informada, se tira ese msj de error al usuario y no se 
					// permite sincronizar. En cualquier otro caso, sólo se emite una notificación.
					$msj = guarani_mensajes::get('800CUR_comision_actividad_no_informada_sq');
					if ($e->get_mensaje() == $msj) {
						throw $e;
					}
				}
			}
		} elseif (isset($cambios['estado']) && $datos_comision['cobrable'] == comunes::si) {
			// Si el estado cobrable de la Comisión no se actualizó, pero si se modificó
			// su estado. Se realiza un PUT alterando el mismo al nuevo valor.
			// La comisión ya estaba informada en SQ. Se modifica su estado.
			unset($params['codigo']);
			unset($params['descripcion']);
			unset($params['servicio']);
			cobro::actualizar_comision_cobrable($id_externo, $params);
		}
	}

	/**
	 * Verifica si la Comisión debe ser informada al sistema de cobros sanavirón-quilmes, y en
	 * caso de que así sea, invoca al servicio correspondiente.
	 * 
	 * @param array $cambios_comision arreglo correspondiente a los cambios que se realizaron
	 * sobre los datos de la comisión, obtenido partir del DT en caso de Edición y vacío en 
	 * caso del Alta.
	 */
	function verificar_informar_sanaviron($cambios_comision = array())
	{
		/*
		 * Para cada Comisión, se verifica si la misma es cobrable. En este caso, no puede existir 
		 * mas de una propuesta de tipo Curso asociada a la Comision que use SQ (sq_usa_sanaviron). 
		 * De ser asi dara error. En caso contrario, 
		 * y si la Comisión se generó como cobrable, o si era no cobrable y se paso a cobrable, 	
		 * o en caso de haber modificado su estado, tomar la propuesta tipo Curso, de todas las 
		 * asociadas a la misma, para informar a SQ, utilizando el servicio correspondiente, 
		 * según el caso.
		 */
		if ($this->es_comision_cobrable()) {
			$propuesta_curso = $this->get_propuesta_curso_asociada();
			// Se definió que si la comisión es cobrable, pero no se encuentra ninguna propuesta
			// de tipo Curso que use SQ asociada, no se hace nada.
			if (!is_null($propuesta_curso)) {
				$datos_comision = $this->get_datos_generales();
				// @TODO Debe suspender la sincronizacion?
				// De decidir mas adelante que no sea asi, capturar las excepciones de fallo conexion en msjs de notificacion.
				$this->marcar_comision_cobrable_sq($propuesta_curso, $datos_comision, $cambios_comision);
			}
		}
	}

	function sincronizar()
	{
		if (!$this->dep('datos')->hay_cambios() && !$this->dep('asignaciones')->hay_cambios()) {
			return;
		}
		$this->validar_editabilidad();

		$this->controlar_cant_filas_min_propuestas();

		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			// Guarda los cambios realizados antes de sincronizar, para utilizarlos
			// al verificar los campos que implican comunicacion con SQ.
			if (!$this->es_nueva()) {
				$cambios_comision = $this->get_cambios_comision();
			} else {
				$cambios_comision = array();
			}

			$this->dep('asignaciones')->persistidor()->sincronizar_insertados();
			$this->dep('asignaciones')->persistidor()->sincronizar_actualizados();

			foreach ($this->s__mapeo_filas as $clave => $valor) {
				$this->modificar_columna_banda_horaria($clave, 'asignacion', $this->get_clave_asignacion($valor));
			}
			$this->dep('datos')->sincronizar();

			$this->dep('asignaciones')->persistidor()->sincronizar_eliminados();
			$this->dep('asignaciones')->notificar_fin_sincronizacion();

			// Se verifica si la comisión debe ser informada a Sanavirón-Quilmes.
			$this->verificar_informar_sanaviron($cambios_comision);

			// Se actualiza el cupo por propuesta.
			$datos_comision = $this->get_datos_generales();
			guarani::act('act_comisiones')->actualizar_cupo_por_propuesta_comision($datos_comision['comision']);

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	function resetear()
	{
		parent::resetear();
		$this->dep('asignaciones')->resetear();
		$this->limpiar_memoria();
	}

	function desactivar_transacciones_anidadas()
	{
		$this->dep('asignaciones')->persistidor()->desactivar_transaccion();
		parent::desactivar_transacciones_anidadas();
	}

	function activar_transacciones_anidadas()
	{
		$this->dep('asignaciones')->persistidor()->activar_transaccion();
		parent::activar_transacciones_anidadas();
	}

	function hay_cambios()
	{
		return ($this->dep('datos')->hay_cambios() || $this->dep('asignaciones')->hay_cambios());
	}

	//--------------------------------------------------------------------------

	function get_datos_generales()
	{
		return $this->tabla('comisiones')->get();
	}

	function get_columna_comision($columna)
	{
		return $this->tabla('comisiones')->get_columna($columna);
	}

	function set_datos_generales($datos)
	{
		$this->validar_datos_generales($datos);
		$this->tabla('comisiones')->set($datos);
	}
	
	/**
	 * Se agregan a la tabla cabecera los datos de asistenca obtenidos de la 
	 * parametrización del sistema
	 */
	function set_datos_asistencia()
	{
		$datos = $this->tabla('comisiones')->get();
		$datos['asistencia_por_horas'] = guarani::parametros()->get_valor('asist_computa_inasis_por_horas');
		$datos['asistencia_cantidad_inasist'] = guarani::parametros()->get_valor('asist_indicar_cantidad_inasistencias');
		$this->tabla('comisiones')->set($datos);
	}

	function comision_acepta_planes_pers()
	{
		return ($this->tabla('comisiones')->get_columna('acepta_planes_personalizados') == comunes::si) ? true : false;
	}

	function es_comision_cobrable()
	{
		return ($this->tabla('comisiones')->get_columna('cobrable') == comunes::si) ? true : false;
	}

	function es_comision_nueva()
	{
		return (empty($this->tabla('comisiones')->get_columna('comision'))) ? true : false;
	}
	
	/**
	 * Retorna el conjunto de cambios realizados sobre el datos tabla comision, sobre su unica fila,
	 * al momento de sincronizar
	 * 
	 * @return array arreglo asociativo conteniendo cada uno de los datos generales de la comision 
	 * editados, con su valor anterior y actual.
	 */
	function get_cambios_comision()
	{
		$id_fila = $this->tabla('comisiones')->get_cursor();
		return $this->tabla('comisiones')->get_cambios_fila($id_fila);
	}

	//--------------------------------------------------------------------------

	function get_datos_propuestas($solo_planes_estructurados = true)
	{
		if ($solo_planes_estructurados) {
			return $this->tabla('propuestas')->get_filas(array('tipo_plan' => planes::tipo_estructurado));
		} else {
			return $this->tabla('propuestas')->get_filas();
		}
	}

	/**
	 * Obtiene la propuesta asociada la comision que se utiliza para facturar la misma. Esta es
	 * aquella de tipo Curso y tal que el parametro 'sq_usa_sanaviron' = S para la misma. Si no
	 * encuentra ninguna, retorna null. Si hay mas de una, lanza un error.
	 * 
	 * @return array propuesta: Arreglo asociativo los datos de la fila de sga_comisiones_propuestas encontrada. 
	 * @throws guarani_error si hay multiples propuestas de tipo curso y que usan sanaviron para la comision cobrable.
	 */
	function get_propuesta_curso_asociada()
	{
		// Recupera sólo las propuestas de planes estructurados, ya que todas las que se asocian
		// por defecto si la comisión acepta planes personalizados, no pueden ser de tipo Curso.
		$propuestas_comision = $this->get_datos_propuestas();
		$propuesta_curso = null;
		$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron');
		foreach ($propuestas_comision as $propuesta) {
			if ($propuesta['propuesta_tipo'] == propuesta::tipo_curso) {
				
				// Si la propuesta es de Tipo Curso y esta definida como que usa sanaviron,
				// sera la utilizada para facturar la Comisión (no puede haber más de una).
				if ($usa_sq == comunes::si) {
					if (is_null($propuesta_curso)) {
						$propuesta_curso = $propuesta;
					} else {
						$msj = guarani_mensajes::get('800CUR_error_multiples_propuestas');
						throw new guarani_error($msj);
					}
				}
			}
		}

		// Si no se encontró ninguna propuesta de tipo Curso y que use sanaviron asociada a la
		// comisión, se retorna null. En caso contrario, retorna la única asociada.
		return $propuesta_curso;
	}

	function set_datos_propuestas($datos)
	{
		$propuestas_a_vincular = array();
		if ($this->comision_acepta_planes_pers()) {
			$actividad = $this->tabla('comisiones')->get_columna('elemento');
			$propuestas_a_vincular = toba::consulta_php('co_comisiones')->get_propuestas_a_vincular($actividad);
			$this->validar_propuestas_planes_pers($propuestas_a_vincular, $datos);
		}
		$this->tabla('propuestas')->procesar_filas($datos);
		
		$this->actualizar_propuestas_planes_personalizados($propuestas_a_vincular);
		
		// Se obtienen los atributos (propuestas) para resolver los parámetros del sistema
		$propuestas = array();
		$planes = array();
		foreach ($datos as $propuesta) {
			if (($propuesta['requerido']) && (!in_array($propuesta['propuesta'], $propuestas))) {
				$propuestas[] = $propuesta['propuesta'];
			}
			if (($propuesta['requerido']) && (!in_array($propuesta['plan'], $planes))) {
				$planes[] = $propuesta['plan'];
			}
		}
		foreach ($propuestas_a_vincular as $pv) {
			if (!in_array($pv['propuesta'], $planes['propuesta'])) {
				$propuestas[] = $pv['propuesta'];
			}
			if (!in_array($pv['plan'], $planes)) {
				$planes[] = $pv['plan'];
			}
		}
		
		$this->controlador()->cargar_atributos_parametros($propuestas, $planes);		
		// Ahora que se pueden resolver los parámetros, se obtienen los parámetros de asistencia
		$this->set_datos_asistencia();
	}

	function controlar_cant_filas_min_propuestas()
	{
		//Si la comisión acepta planes personalizados, la propuesta no es requerida
		if ($this->comision_acepta_planes_pers()) {
			$this->set_cant_filas_min_propuestas(0);
		}
	}

	function set_cant_filas_min_propuestas($cant)
	{
		$this->tabla('propuestas')->set_tope_min_filas($cant);
	}

	/**
	 * Si la comisión acepta planes personalizados, y no tiene asociada ninguna propuesta
	 * de un plan estructurado, debe verificarse que ya existan Planes Personalizados creados,
	 * ya que en otro caso, la Comisión se generaría para ninguna Propuesta. En dicho caso,
	 * se emite un msj de error informando al usuario.
	 * $propuestas_pers es el array de propuestas asociadas a Planes Pers de la actividad, que deberían
	 * vincularse a la comisión.
	 * $prop_estruct es el array de los datos del form_ml de propuestas para Planes estructuradas,
	 * previo a su procesamiento.
	 */
	function validar_propuestas_planes_pers($propuestas_pers, $prop_estruct)
	{
		if ($this->comision_acepta_planes_pers() && empty($propuestas_pers)) {
			/** Se asoció la comisión a planes personalizados, pero no existen actualmente
			 * 	Planes de este tipo para la Actividad seleccionada. Debe verificarse si se asoció 
			 * 	la comisión a ninguna Propuesta-Plan para estructurados. De otra forma la misma 
			 *  quedaría sin ninguna propuesta asociada. Se emite error.
			 */
			//VER FUNCIÓN PARA VER SI HAY ALGUNA FILA CON REQUERIDO EN 1.
			$tiene_estruct = false;
			foreach ($prop_estruct as $id => $fila) {
				if ($fila['requerido']) {
					$tiene_estruct = true;
					break;
				}
			}
			if (!$tiene_estruct) {
				$this->set_error('800CUR_comision_sin_planes');
			}
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/*
	 * En caso de que la comisión haya cambiado la condición de aceptar planes
	 * personalizados (de sí a no o viceversa), se agregan o se quitan según corresponda
	 * las propuestas asociadas a planes que no son estructurados.
	 * Si la comisión acepta planes personalizados, agrega las propuestas asociadas
	 * a la actividad de la comisión de los planes personalizados o tipo convenio
	 * Si la comisión no acepta planes personalizados, quita las propuestas
	 * que se vinculan con planes personalizados o de tipo convenio.
	 */
	function actualizar_propuestas_planes_personalizados($propuestas_a_vincular)
	{
		$ahora_acepta_planes_pers = $this->tabla('comisiones')->get_columna('acepta_planes_personalizados');
		if ($ahora_acepta_planes_pers != $this->s__acepta_planes_personalizados) {
			$this->s__acepta_planes_personalizados = $ahora_acepta_planes_pers;
			if ($ahora_acepta_planes_pers == comunes::si) {
				if (!empty($propuestas_a_vincular)) {
					foreach ($propuestas_a_vincular as $propuesta) {
						$this->tabla('propuestas')->nueva_fila($propuesta);
					}
				}
			} else {
				$ids_filas_a_eliminar_plan_pers = $this->tabla('propuestas')->get_id_fila_condicion(array('tipo_plan' => planes::tipo_personalizado));
				$ids_filas_a_eliminar_plan_convenio = $this->tabla('propuestas')->get_id_fila_condicion(array('tipo_plan' => planes::tipo_convenio));
				$ids_filas_a_eliminar = array_merge($ids_filas_a_eliminar_plan_convenio, $ids_filas_a_eliminar_plan_pers);

				foreach ($ids_filas_a_eliminar as $id_fila) {
					$this->tabla('propuestas')->eliminar_fila($id_fila);
				}
			}
		}
	}

	function get_escala_notas_defecto($instancia)
	{
		$elemento = $this->tabla('comisiones')->get_columna('elemento');
		$propuestas = $this->get_datos_propuestas(false); //con false recupera las escalas de notas de las propuestas asociadas a todos los planes, no sólo de los estructurados
		$columna = ($instancia == comision::inst_cursar) ? 'escala_nota_cursada_regular' : 'escala_nota_cursada_promocion';
		if (!empty($propuestas)) {
			$escalas = toba::consulta_php('co_elementos')->get_escalas_notas_elemento_planes($elemento, $propuestas, $columna);
			if (guarani_arreglos::count($escalas) == 1) {
				return $escalas[0][$columna];
			}
		}
		$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($elemento);
		return $datos_elemento[$columna];
	}

	function eliminar_propuestas()
	{
		$this->tabla('propuestas')->eliminar_filas();
	}

	//--------------------------------------------------------------------------

	function get_datos_modalidades()
	{
		return $this->tabla('modalidades')->get_filas();
	}

	function set_datos_modalidades($datos)
	{
		$this->tabla('modalidades')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_docentes_asociados()
	{
		return $this->tabla('docentes')->get_filas();
	}

	function set_datos_docentes_asociados($datos)
	{
		$this->validar_datos_docentes($datos);
		$this->tabla('docentes')->procesar_filas($datos);
	}

	function eliminar_docente_asociado($id_fila_docente)
	{
		// Se elimina el docente seleccionado, y consecuentemente todas sus dedicaciones horarias en la tabla hija.
		if (!is_null($id_fila_docente)) {
			$this->tabla('docentes')->eliminar_fila($id_fila_docente);
		}
	}

	/**
	 * Se crea un nuevo docente asociado a la comisión a partir del array recibido con el id del docente.
	 */
	function agregar_docente_asociado($datos_docente)
	{
		/**
		 * Cada nuevo docente asociado se crea con fechas desde y hasta por defecto para su asignación, tomando los valores 
		 * de las fechas inicio y fin del período lectivo seleccionado (en los datos generales de la comisión). Si la fecha 
		 * de inicio es menor a la actual, entonces tomar como fecha desde la fecha actual.
		 */
		$datos_comision = $this->get_datos_generales();
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);
		$fecha_actual = guarani_fecha::get_hoy();
		if (guarani_fecha::es_menor($datos_periodo['fecha_inicio_dictado'], $fecha_actual)) {
			$datos_docente['fecha_desde'] = $fecha_actual;
		} else {
			$datos_docente['fecha_desde'] = $datos_periodo['fecha_inicio_dictado'];
		}
		$datos_docente['fecha_hasta'] = $datos_periodo['fecha_fin_dictado'];
		$this->tabla('docentes')->nueva_fila($datos_docente);
	}

	function set_cursor_docentes($id_fila)
	{
		$this->tabla('docentes')->set_cursor($id_fila);
	}

	function unset_cursor_docentes()
	{
		$this->tabla('docentes')->resetear_cursor();
	}

	function get_id_fila_docente($id_docente)
	{
		$filas = $this->tabla('docentes')->get_id_fila_condicion(array('docente' => $id_docente));
		if (!empty($filas)) {
			return current($filas);
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_dedicaciones_horarias()
	{
		// Se traen todas las filas de la tabla de dedicaciones horarias de los 
		// docentes sin utilizar cursores de las tablas padres, ya que la tabla 
		// de docentes no tiene ningún cursor posicionado.
		return $this->tabla('doc_dedicacion')->get_filas(null, false, false);
	}

	function set_datos_dedicaciones_horarias($datos)
	{
		/**
		 * Procesa los cambios masivos de las filas. Los datos de dedicaciones horarias se setean para diferentes docentes
		 * en un ef combo en cada fila del formulario ml. Cada fila debe procesarse de manera análoga al procesar filas del
		 * datos tabla, pero el ef docente debe utilizarse para setear el cursor en la tabla de docentes, padre de 
		 * dedicaciones horarias.
		 * 
		 * Para procesar es necesario indicar el estado de cada fila utilizando una columna referenciada con la constante 
		 * 'apex_ei_analisis_fila' los valores pueden ser:
		 *  - 'A': Alta
		 *  - 'B': Baja
		 *  - 'M': Modificacion
		 *
		 * @param array $datos Filas en formato RecordSet, cada registro debe contener un valor para la constante 
		 * apex_ei_analisis_fila.
		 */
		foreach ($datos as $id => $fila) {
			$accion = $fila[apex_ei_analisis_fila];
			unset($fila[apex_ei_analisis_fila]);
			switch ($accion) {
				case 'B':
					$this->tabla('doc_dedicacion')->eliminar_fila($id);
					break;
				case 'M':
					$this->tabla('doc_dedicacion')->modificar_fila($id, $fila);
					break;
				case 'A':
					/**
					 * En principio debe recuperarse el id de la fila del docente al cual está asociada la dedicación horaria que 
					 * se está dando de alta o modificando, para poder setear el cursor en la tabla padre de docentes.
					 */
					$id_fila_padre = $this->get_id_fila_docente($fila['docente']);
					$this->set_cursor_docentes($id_fila_padre);
					$this->tabla('doc_dedicacion')->nueva_fila($fila);
					$this->unset_cursor_docentes();
			}
		}
	}

	//--------------------------------------------------------------------------

	function get_datos_bandas_horarias()
	{
		return $this->tabla('bandas_horarias')->get_filas();
	}

	function get_datos_bandas_horarias_asignaciones()
	{
		$bandas_horarias = $this->tabla('bandas_horarias')->get_filas();
		$datos = array();
		foreach ($bandas_horarias as $banda_horaria) {
			$datos_asignacion = $this->get_asignacion($this->s__mapeo_filas[$banda_horaria[apex_datos_clave_fila]]);
			unset($datos_asignacion[apex_datos_clave_fila]);
			$aux = array_merge($banda_horaria, $datos_asignacion);
            switch ($aux['dia_semana']) {
                case comunes::lunes:
                    $aux['id_dia'] = 1;
                break;
                case comunes::martes:
                    $aux['id_dia'] = 2;
                break;
                case comunes::miercoles:
                    $aux['id_dia'] = 3;
                break;
                case comunes::jueves:
                    $aux['id_dia'] = 4;
                break;
                case comunes::viernes:
                    $aux['id_dia'] = 5;
                break;
                case comunes::sabado:
                    $aux['id_dia'] = 6;
                break;
                case comunes::domingo:
                    $aux['id_dia'] = 7;
                break;
            }
            $datos[] = $aux;
		}
		$datos = rs_ordenar_por_columnas($datos, ['id_dia', 'hora_inicio']);        
		return $datos;
	}

	function set_datos_banda_horaria($datos)
	{
		if ($this->hay_banda_horaria_seleccionada()) {
			$this->tabla('bandas_horarias')->set($datos);
		}
	}

	function modificar_columna_banda_horaria($id, $columna, $valor)
	{
		$this->tabla('bandas_horarias')->set_fila_columna_valor($id, $columna, $valor);
	}

	function seleccionar_banda_horaria($id_fila)
	{
		$this->tabla('bandas_horarias')->set_cursor($id_fila);
	}

	function deseleccionar_banda_horaria()
	{
		$this->tabla('bandas_horarias')->resetear_cursor();
	}

	function get_datos_banda_horaria_seleccionada()
	{
		if ($this->hay_banda_horaria_seleccionada()) {
			return $this->tabla('bandas_horarias')->get();
		}
	}

	function hay_banda_horaria_seleccionada()
	{
		return $this->tabla('bandas_horarias')->hay_cursor();
	}

	function banda_horaria_es_nueva()
	{
		$altas = $this->tabla('bandas_horarias')->get_id_filas_a_sincronizar(array('i'));
		return in_array($this->tabla('bandas_horarias')->get_cursor(), $altas);
	}

	function eliminar_banda_horaria_seleccionada()
	{
		if ($this->hay_banda_horaria_seleccionada()) {
			$id_fila = $this->tabla('bandas_horarias')->get_cursor();
			$this->tabla('bandas_horarias')->eliminar_fila($id_fila);
			unset($this->s__mapeo_filas[$id_fila]);
		}
	}

	function nueva_banda_horaria($datos)
	{
		$id = $this->tabla('bandas_horarias')->nueva_fila($datos);
		$this->s__mapeo_filas[$id] = $this->dep('asignaciones')->get_cursor();
	}

	function compartir_banda_horaria($datos)
	{
        try {
			$datos_banda_horaria = array('asignacion' => $datos['banda_horaria'][1],
			'tipo_clase' => $datos['tipo_clase'],
			'compartida' => 1);
            $id = $this->tabla('bandas_horarias')->nueva_fila($datos_banda_horaria);

            $filtro = "sga_asignaciones.asignacion = {$datos['banda_horaria'][1]}";
            $datos_asignacion = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones($filtro);
            $this->anexar_asignacion($datos_asignacion);

            $this->s__mapeo_filas[$id] = $this->get_id_asignacion(array('asignacion' => $datos['banda_horaria'][1]));
		} catch (toba_error $e) {
			throw new guarani_error($e->get_mensaje());
		}		
	}

	function duplicar_banda_horaria($id_fila)
	{
		// Se obtienen los datos de la Banda Horaria a duplicar
		$this->tabla('bandas_horarias')->set_cursor($id_fila);
		$datos_banda_horaria = $this->get_datos_banda_horaria_seleccionada();
		$id_fila = $this->s__mapeo_filas[$datos_banda_horaria[apex_datos_clave_fila]];
		$this->dep('asignaciones')->set_cursor($id_fila);
		$datos_asignacion = $this->dep('asignaciones')->get();
		$this->deseleccionar_asignacion();
		$this->deseleccionar_banda_horaria();

		// Se crea la nueva Banda Horaria con los datos obtenidos
		unset($datos_asignacion['dia_semana']);
		unset($datos_banda_horaria['dia_semana']);

		$id_asignacion = $this->dep('asignaciones')->nueva_fila($datos_asignacion);
		$this->dep('asignaciones')->set_cursor($id_asignacion);

		$id_banda = $this->tabla('bandas_horarias')->nueva_fila($datos_banda_horaria);
		$this->tabla('bandas_horarias')->set_cursor($id_banda);
		$this->s__mapeo_filas[$id_banda] = $this->dep('asignaciones')->get_cursor();
	}

	//--------------------------------------------------------------------------

	function get_datos_asignaciones($condiciones = null)
	{
		return $this->dep('asignaciones')->get_filas($condiciones);
	}

	function set_datos_asignacion($datos)
	{
		if ($this->hay_asignacion_seleccionada()) {
			$this->validar_datos_asignacion($datos);
			$this->dep('asignaciones')->set($datos);
		}
	}

	function get_id_asignacion($condiciones)
	{
		return current($this->dep('asignaciones')->get_id_fila_condicion($condiciones));
	}

	function get_clave_asignacion($id_fila)
	{
		$clave = $this->dep('asignaciones')->get_clave_valor($id_fila);
		return $clave['asignacion'];
	}

	function seleccionar_asignacion()
	{
		$banda_horaria = $this->get_datos_banda_horaria_seleccionada();
		$id_fila = $this->s__mapeo_filas[$banda_horaria[apex_datos_clave_fila]];
		$this->dep('asignaciones')->set_cursor($id_fila);
	}

	function deseleccionar_asignacion()
	{
		$this->dep('asignaciones')->resetear_cursor();
	}

	function get_datos_asignacion_seleccionada()
	{
		if ($this->hay_asignacion_seleccionada()) {
			return $this->dep('asignaciones')->get();
		}
	}

	function get_asignacion($id_fila)
	{
		return $this->dep('asignaciones')->get_fila($id_fila);
	}

	function hay_asignacion_seleccionada()
	{
		return $this->dep('asignaciones')->hay_cursor();
	}

	function eliminar_asignacion_seleccionada()
	{
		if ($this->hay_asignacion_seleccionada()) {
			$banda_horaria = $this->get_datos_banda_horaria_seleccionada();

			$comision = isset($banda_horaria['comision']) ? $banda_horaria['comision'] : null;
			$asignacion = isset($banda_horaria['asignacion']) ? $banda_horaria['asignacion'] : null;
			$asignacion_compartida = toba::consulta_php('co_bandas_horarias')->asignacion_compartida($comision, $asignacion);
			$asignacion_compartida = empty($asignacion_compartida) ? false : $asignacion_compartida[0]['compartida'];

			if (!$asignacion_compartida) {
				$id_fila = $this->dep('asignaciones')->get_cursor();
				$this->dep('asignaciones')->eliminar_fila($id_fila);
			}
		}
	}

	function nueva_asignacion($datos)
	{
		$this->validar_datos_asignacion($datos);
		$datos['asignacion_tipo'] = comision::asignacion_tipo_comision;
		$id = $this->dep('asignaciones')->nueva_fila($datos);
		$this->dep('asignaciones')->set_cursor($id);
	}

	function anexar_asignacion($datos)
	{
		$this->dep('asignaciones')->anexar_datos($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_instancias()
	{
		return $this->tabla('instancias')->get_filas();
	}

	function set_datos_instancias($datos)
	{
		$this->validar_datos_instancias($datos);
		$this->tabla('instancias')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_periodos_insc_excep()
	{
		return $this->tabla('periodos')->get_filas();
	}

	/**
	 * Retorna los datos de la excepción al período de inscripción fecha seleccionado actualmente.
	 */
	function get_datos_periodo_insc_excep_seleccionado()
	{
		if ($this->tabla('periodos')->hay_cursor()) {
			return $this->tabla('periodos')->get();
		}
	}

	/**
	 * Setea los datos de la excepción al periodo de inscripción fecha apuntado actualmente por el cursor.
	 */
	function set_datos_periodo_insc_excep($datos)
	{
		if ($this->tabla('periodos')->hay_cursor()) {
			$datos_comision = $this->get_datos_generales();
			$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);
			$this->validar_datos_periodo_insc_excep($datos, $datos_periodo);
			$this->tabla('periodos')->set($datos);
		}
	}

	function get_cursor_per_insc_excep()
	{
		return $this->tabla('periodos')->get_cursor();
	}

	function set_cursor_per_insc_excep($excep)
	{
		$this->tabla('periodos')->set_cursor($excep);
	}

	function unset_cursor_per_insc_excep()
	{
		$this->tabla('periodos')->resetear_cursor();
	}

	/**
	 * Busca una fila de la tabla de excepciones de períodos asociadas a la comisión, cuyo periodo_insc_fecha sea igual al 
	 * dado. Si esta existe retorna el id interno de esta fila, caso contrario se retorna null. 
	 *
	 * @param integer $per_fecha id del periodo_inscripcion_fecha a buscar.
	 */
	function hay_per_insc_excep($per_fecha)
	{
		$id_periodo = $this->tabla('periodos')->get_id_fila_condicion(array('periodo_insc' => $per_fecha));
		if (!empty($id_periodo)) {
			return current($id_periodo);
		} else {
			return null;
		}
	}

	/**
	 * Crea una nueva fila para las excepciones a periodos de inscripción fechas asociados a la comisión, con el periodo 
	 * fecha dado como parámetro y posiciona el cursor en la misma.
	 *
	 * @param integer $per_fecha_selec id del periodo de inscripcion fecha para el cual se va a definir una excepción 
	 * para asociar a la comisión.
	 */
	function agregar_per_insc_excep($per_fecha_selec)
	{
		$id = $this->tabla('periodos')->nueva_fila(array('periodo_insc' => $per_fecha_selec));
		$this->set_cursor_per_insc_excep($id);
	}

	/**
	 * Elimina la excepción al periodo de inscripción fecha asociada a la comisión, actualmente apuntada por el cursor.
	 */
	function eliminar_per_inscr_excep()
	{
		$id_per = $this->get_cursor_per_insc_excep();
		if (!is_null($id_per)) {
			$this->tabla('periodos')->eliminar_fila($id_per);
		}
	}

	function get_datos_periodos_inscripcion()
	{
		$datos_generales = $this->get_datos_generales();
		$propuestas_planes = $this->get_datos_propuestas(false); //con false recupera todas las propuestas, no sólo las vinculadas a planes estructurados
		$modalidades_comision = $this->get_datos_modalidades();
		if (!empty($datos_generales) && !empty($propuestas_planes) && !empty($modalidades_comision)) {
			// Para el caso en que se está en un alta se controla que ya se hayan completado los datos principales.
			$periodos = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_per_insc_planes_ubicacion_modalidad($datos_generales['periodo_lectivo'], $propuestas_planes, $datos_generales['ubicacion'], $modalidades_comision);

                        // Formateo de fecha / hora: quitamos los segundos y el TimeZone
			foreach ($periodos as $clave => $valor) {
				$aux = explode(' ', $valor['fecha_inicio']);
				$aux[1] = substr($aux[1], 0, 5);
				$periodos[$clave]['fecha_inicio'] = implode(' ', $aux);

				$aux = explode(' ', $valor['fecha_fin']);
				$aux[1] = substr($aux[1], 0, 5);
				$periodos[$clave]['fecha_fin'] = implode(' ', $aux);

				$aux = explode(' ', $valor['fecha_tope_bajas']);
				$aux[1] = substr($aux[1], 0, 5);
				$periodos[$clave]['fecha_tope_bajas'] = implode(' ', $aux);
                                 if($periodos[$clave]['habilitado_interfaz']!=='0'){
                                                    $nombre_interfaz = toba::consulta_php('co_comunes')->get_nombres_interfaces($periodos[$clave]['habilitado_interfaz']);
                                                }else {
                                                    $nombre_interfaz[0]['interfaz_nombre'] = 'Todas las interfaces';
                                                }
                                 $periodos[$clave]['habilitado_interfaz'] = $nombre_interfaz[0]['interfaz_nombre'] ?? null;
                                 $periodos[$clave]['tiene_excepcion'] = comunes::no_desc;
                                        
                                }
			/**
			 * Si se está editando una Comisión existente, o una nueva pero a la cual ya se le asociaron excepciones, se deben 
			 * asociar a los períodos obtenidos, los datos de las excepciones sobre los mismos definidas para la misma.
			 */
			$per_exc_asociadas = $this->get_datos_periodos_insc_excep();
			if (!empty($per_exc_asociadas)) {
				foreach ($periodos as $clave => $valor) {
					$per_insc = $this->es_periodo_asociado($periodos[$clave]['periodo_insc'], $per_exc_asociadas);
					if (isset($per_insc['habilitado_interfaz']) &&isset($per_insc['fecha_inicio']) && isset($per_insc['fecha_fin']) && isset($per_insc['fecha_tope_bajas'])) {
						/**
						 * Si la excepción no se agregó recién como consecuencia de la selección del período se agregan los 
						 * datos en el cuadro. Si está recién agregada no tiene las fechas cargadas, solo el periodo de 
						 * inscripcion fecha asociado.
						 */
						
                                                $periodos[$clave]['tiene_excepcion'] = comunes::si_desc;
                                        }
				}
			}

			return $periodos;
		} else {
			return array();
		}
	}

			/**
	 * Verifica si un periodo de inscripcion fecha dado tiene una excepción asociada a la comisión.
	 * Si es así, retorna los datos de esa excepción en formato Recordset. En caso contrario retorna un array vacío.
	 */
	protected function es_periodo_asociado($per, $excep_asociadas)
	{
		foreach ($excep_asociadas as $excep) {
			if ($excep['periodo_insc'] == $per) {
				return $excep;
			}
		}
		return array();
	}

	//--------------------------------------------------------------------------
	//---- Excepciones ---------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos_excep_perlect()
	{
		return $this->tabla('excep_perlect')->get();
	}

	function set_datos_excep_perlect($datos)
	{
		$this->validar_excep_perlect($datos);
		$this->tabla('excep_perlect')->set($datos);
	}

	function eliminar_datos_excep_perlect()
	{
		$datos = $this->tabla('excep_perlect')->get();
		$this->tabla('excep_perlect')->eliminar_fila($datos['x_dbr_clave']);
	}

	function validar_excep_perlect($datos)
	{
		// Que la fecha nuevo inicio dictado sea menor que la fecha de nuevo fin de dictado. 
		if (guarani_fecha::es_mayor($datos['fecha_inicio_dictado'], $datos['fecha_fin_dictado'])) {
			$this->set_error('800_control_fechas', array('de nuevo inicio dictado', 'mayor', 'de nuevo fin de dictado'));
			throw guarani::error($this->get_mensaje_error());
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_generales($datos)
	{
		// Si se está editando una comisión se obtiene el id de la misma, si se está agregando este será null.
		$datos['comision'] = $this->tabla('comisiones')->get_columna('comision');
		if (isset($datos['nombre']) && isset($datos['periodo_lectivo']) && isset($datos['ubicacion'])) {
			$this->validar_nombre_comision($datos['nombre'], $datos['elemento'], $datos['periodo_lectivo'], $datos['ubicacion'], $datos['comision']);
		}
		
		// Se verifica que se hayan ingresado bien los valores para el rango de letras de la comisión.
		$this->validar_ingreso_letras_rango($datos);

		// Se verifica si el rango de letras ingresados para cubrir por la comisión es válido.
		if (!empty($datos['cupo_minimo']) && (!empty($datos['cupo']))) {
			$this->validar_cupos_comision($datos['cupo_minimo'], $datos['cupo']);
		}

		// Se verifica si el rango de letras ingresados para cubrir por la comisión es válido.
		if (isset($datos['letra_desde']) && isset($datos['letra_hasta'])) {
			$this->validar_rango_letras_comision($datos['letra_desde'], $datos['letra_hasta']);
			/**
			 * Si el rango de letras para la Comisión es válido, se verifica además que al editarlo no se esté 
			 * invalidando alguno de los rangos de las Subcomisiones asociadas a la misma, si es que existen.
			 * Esto es que ninguno de los rangos para las Subcomisiones quede fuera del rango de la Comisión padre.
			 * Sólo tendrá sentido cuando la comisión se esté editando (al dar de alta no tendrá subcomisiones asociadas
			 * y el id $datos['comision'] será nulo).
			 */
			if (!is_null($datos['comision'])) {
				$subcomisiones = toba::consulta_php('co_comisiones')->get_subcomisiones_comision($datos['comision']);
				$this->validar_rango_subcomisiones_comision($datos['letra_desde'], $datos['letra_hasta'], $subcomisiones);
			}
		}
		if (isset($datos['cupo']) && !is_null($datos['comision'])) {
			/**
			 * Análogamente al rango se valida que los cupos de las subcomisiones no sean mayores que el editado para 
			 * la comisión.
			 */
			if (!isset($subcomisiones)) {
				$subcomisiones = toba::consulta_php('co_comisiones')->get_subcomisiones_comision($datos['comision']);
			}
			$this->validar_cupo_subcomisiones_comision($datos['cupo'], $subcomisiones);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determinar si el nombre de la Comisión ya existe para esa Actividad, en esa ubicación y ese Período Lectivo.
	 * 
	 * @param string 	nombre		es el nombre que debe verificarse si ya existe.
	 * @param integer	elem		id de la actividad a la que está asociada la comisión.
	 * @param integer	per_lec		id del período lectivo para el cual se definió la comisión.
	 * @param integer	ubicacion	id de la ubicación para la cual se definió la comisión.
	 * @param integer	comision	id de la comisión que se excluye del control (para el caso de la edición de la entidad).
	 */
	function validar_nombre_comision($nombre, $elem, $per_lec, $ubicacion, $comision)
	{
		if (!toba::consulta_php('co_comisiones')->validar_nombre_comision($nombre, $elem, $per_lec, $ubicacion, $comision)) {
			$this->set_error('dato_existente', array('nombre', 'comision asociada a la Actividad, Ubicación y Período Lectivo'));
		}
	}

	/**
	 * Verifica que se hayan ingresado ambas letras del rango de la comisión o ninguna de ellas
	 */
	function validar_ingreso_letras_rango($datos)
	{
		if ((isset($datos['letra_desde']) && !isset($datos['letra_hasta'])) ||
				!isset($datos['letra_desde']) && isset($datos['letra_hasta'])) {
			$this->set_error('800CUR_completar_letras', array('Comisión'));
		}
	}

	/**
	 * Si fueron definidos cupos mínimos y máximos para la comisión, se valida 
	 * que $cupo_minimo <= $cupo_maximo
	 * 
	 * @param int $cupo_minimo
	 * @param int $cupo_maximo
	 */
	function validar_cupos_comision($cupo_minimo, $cupo_maximo)
	{
		if ($cupo_minimo > $cupo_maximo) {
			$this->set_error('800CUR_error_cupos');
		}
	}

	/**
	 * Verifica si el rango de letras ingresado es válido.
	 * 
	 * @param string 	desde	letra inicial del rango.
	 * @param string	hasta	letra final del rango.
	 */
	function validar_rango_letras_comision($desde, $hasta)
	{
		$letra_desde = strtolower($desde);
		$letra_hasta = strtolower($hasta);
		if ($letra_desde > $letra_hasta) {
			$this->set_error('800CUR_error_letras');
		}
	}

	/**
	 * Verifica si el rango de las subcomisiones de la comisión es válido de acuerdo 
	 * al rango establecido para esta última.
	 * 
	 * @param string 	desde			letra inicial del rango de la comisión.
	 * @param string	hasta			letra final del rango de la comisión.
	 * @param array		subcomisiones	subcomisiones asociadas a la comisión en edición.
	 */
	function validar_rango_subcomisiones_comision($desde, $hasta, $subcomisiones)
	{
		$letra_desde = strtolower($desde);
		$letra_hasta = strtolower($hasta);
		foreach ($subcomisiones as $subcomision) {
			if (isset($subcomision['letra_desde']) && isset($subcomision['letra_hasta'])) {
				$letra_desde_sub = strtolower($subcomision['letra_desde']);
				$letra_hasta_sub = strtolower($subcomision['letra_hasta']);
				if (!(($letra_desde <= $letra_desde_sub) && ($letra_hasta_sub <= $letra_hasta))) {
					$this->set_error('800CUR_subco_fuera_rango');
					break;
				}
			}
		}
	}

	/**
	 * Verifica si el cupo de las subcomisiones de la comisión es válido de acuerdo al cupo establecido para esta última.
	 * 
	 * @param integer	cupo			cupo seteado para la comisión.
	 * @param array		subcomisiones	subcomisiones asociadas a la comisión en edición.
	 */
	function validar_cupo_subcomisiones_comision($cupo, $subcomisiones)
	{
		foreach ($subcomisiones as $subcomision) {
			if (isset($subcomision['cupo'])) {
				if ($subcomision['cupo'] > $cupo) {
					$this->set_error('800CUR_subco_excede_cupo');
					break;
				}
			}
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_docentes($datos)
	{
		// Se verifican los datos para cada docente que se asoció a la comisión.
		foreach ($datos as $docente) {
			$this->validar_existencia_fechas($docente);
			if (isset($docente['fecha_desde']) && isset($docente['fecha_hasta'])) {
				$this->validar_consistencia_fechas($docente['fecha_desde'], $docente['fecha_hasta'], $docente['nombre_persona']);
			}
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_existencia_fechas($docente)
	{
		if ((isset($docente['fecha_desde']) && (!isset($docente['fecha_hasta']))) || (isset($docente['fecha_hasta']) && (!isset($docente['fecha_desde'])))) {
			$this->set_error('800CUR_completar_fechas', array($docente['nombre_persona']));
		}
	}

	function validar_consistencia_fechas($fecha_desde, $fecha_hasta, $nombre_fila_docente)
	{
		$fechas_consistentes = guarani_fecha::es_menor($fecha_desde, $fecha_hasta, false, true);
		if (!$fechas_consistentes) {
			$this->set_error('800_control_fechas', array('Hasta', 'menor o igual', 'Desde para el docente ' . $nombre_fila_docente));
		}
	}

	//---------------------------------------------------------------------

	function validar_datos_periodo_insc_excep($datos, $datos_periodo)
	{
		// Se verifican los datos para cada docente que se asoció a la comisión.
		$this->validar_fechas_inicio_fin_excep($datos['fecha_inicio'], $datos['fecha_fin']);
		$this->validar_fecha_tope_bajas_fin_excep($datos['fecha_tope_bajas'], $datos['fecha_fin']);
		$this->validar_fecha_tope_bajas_inicio_excep($datos['fecha_tope_bajas'], $datos['fecha_inicio']);
		$this->validar_fecha_fin_per_lect($datos['fecha_fin'], $datos_periodo['periodo_fecha_fin']);
		$this->validar_fecha_tope_bajas_per_lect($datos['fecha_tope_bajas'], $datos_periodo['periodo_fecha_fin']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_fechas_inicio_fin_excep($fecha_inicio, $fecha_fin)
	{
		if (guarani_fecha::es_menor_timestamp($fecha_fin, $fecha_inicio)) {
			$this->set_error('800_control_fechas', array('fin', 'menor', 'inicio'));
		}
	}

	function validar_fecha_tope_bajas_fin_excep($fecha_fin, $fecha_tope_bajas)
	{
		if (guarani_fecha::es_menor_timestamp($fecha_tope_bajas, $fecha_fin)) {
			$this->set_error('800_control_fechas', array('tope de bajas', 'mayor', 'fin'));
		}
	}
	
	function validar_fecha_tope_bajas_inicio_excep($fecha_inicio, $fecha_tope_bajas)
	{
		if (guarani_fecha::es_mayor_timestamp($fecha_tope_bajas, $fecha_inicio)) {
			$this->set_error('800_control_fechas', array('tope de bajas', 'menor', 'inicio'));
		}
	}

	function validar_fecha_fin_per_lect($fecha_fin, $fecha_fin_periodo_lectivo)
	{
		if (guarani_fecha::es_mayor($fecha_fin, $fecha_fin_periodo_lectivo)) {
			$this->set_error('800_control_fechas', array('fin', 'mayor', 'fin del período lectivo'));
		}
	}

	function validar_fecha_tope_bajas_per_lect($fecha_tope_bajas, $fecha_fin_periodo_lectivo)
	{
		if (guarani_fecha::es_mayor($fecha_tope_bajas, $fecha_fin_periodo_lectivo)) {
			$this->set_error('800_control_fechas', array('tope de bajas', 'mayor', 'fin del período lectivo'));
		}
	}

	//--------------------------------------------------------------------------
	
	/**
	 * Validar que todas las instancias asociadas a la comisión tengan escala de notas y porcentaje de asistencia.
	 */
	function validar_datos_instancias($datos)
	{
		$this->validar_escala_notas_instancias($datos);
		$this->validar_porcentaje_asistencia_instancias($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_escala_notas_instancias($datos)
	{
		foreach ($datos as $instancia) {
			if ($instancia[apex_ei_analisis_fila] != 'B' && !isset($instancia['escala_nota'])) {
				$this->set_error('800CUR_error_escala_faltante', array($instancia['instancia_nombre']));
				break;
			}
		}
	}

	function validar_porcentaje_asistencia_instancias($datos)
	{
		foreach ($datos as $instancia) {
			if ($instancia[apex_ei_analisis_fila] != 'B' && !isset($instancia['porc_asistencia'])) {
				$this->set_error('800CUR_error_porc_asistencia_faltante', array($instancia['instancia_nombre']));
				break;
			}
		}
	}

	//--------------------------------------------------------------------------

	function validar_eliminacion_banda_horaria()
	{
		$datos_banda_horaria = $this->get_datos_banda_horaria_seleccionada();

		if (isset($datos_banda_horaria['banda_horaria'])) {
			// Si las clases asociadas a la banda horaria tienen asistencia no se puede borrar.
			$clases_asistencia = toba::consulta_php('co_clases')->get_clases_con_asistencia_bh($datos_banda_horaria['banda_horaria']);
			if (!empty($clases_asistencia)) {
				$this->set_error('800CUR_error_eliminar_bh');
			}

			// Si las clases asociadas a la banda horaria tienen temas planificados no se puede borrar.
			$clases_temas = toba::consulta_php('co_clases')->get_clases_con_temas_planificados_bh($datos_banda_horaria['banda_horaria']);
			if (!empty($clases_temas)) {
				$this->set_error('800CUR_error_eliminar_bh_tema');
			}

			// Si la banda horaria está asociada a una subcomisión no se puede borrar.
			$subcomisiones = toba::consulta_php('co_bandas_horarias')->get_subcomisiones_bh($datos_banda_horaria['banda_horaria']);
			if (!empty($subcomisiones)) {
				$this->set_error('800CUR_error_eliminar_bh_subcomision');
			}
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_asignacion($datos)
	{
		$this->validar_fechas_desde_hasta_asignacion($datos['fecha_desde'], $datos['fecha_hasta']);
		$this->validar_horas_inicio_finalizacion_asignacion($datos['hora_inicio'], $datos['hora_finalizacion']);
		
		$datos_asignacion = $this->get_datos_asignacion_seleccionada();
		$asignacion = isset($datos_asignacion['asignacion']) ? $datos_asignacion['asignacion'] : null;
		$this->validar_rango_horario_asignacion($datos['dia_semana'], $datos['periodicidad'], $datos['hora_inicio'], $datos['hora_finalizacion'], $datos['fecha_desde'], $datos['fecha_hasta'], $asignacion);

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_fechas_desde_hasta_asignacion($fecha_desde, $fecha_hasta)
	{
		$datos_banda_horaria = $this->get_datos_banda_horaria_seleccionada();

		if (isset($datos_banda_horaria['banda_horaria'])) {
			/**
			 * Las fechas desde y hasta sólo se pueden modificar si las clases que quedan fuera de ese rango (para la o 
			 * las comisiones en el caso de una banda horaria compartida) no tienen asistencia cargada (para alumnos o 
			 * docentes) ni temas planificados.
			 */
			$datos_asignacion = $this->get_datos_asignacion_seleccionada();

			if ($datos_asignacion['fecha_desde'] < $fecha_desde || $fecha_hasta < $datos_asignacion['fecha_hasta']) {
				$filtro = ($datos_asignacion['fecha_desde'] < $fecha_desde) ? "sga_clases.fecha < '$fecha_desde'" : "sga_clases.fecha > '$fecha_hasta'";

				$clases_asistencia = toba::consulta_php('co_clases')->get_clases_con_asistencia_bh($datos_banda_horaria['banda_horaria'], $filtro);
				if (!empty($clases_asistencia)) {
					$this->set_error('800CUR_error_modificar_bh');
				}

				$clases_temas = toba::consulta_php('co_clases')->get_clases_con_temas_planificados_bh($datos_banda_horaria['banda_horaria'], $filtro);
				if (!empty($clases_temas)) {
					$this->set_error('800CUR_error_modificar_bh_tema');
				}
			}
		}

		if (guarani_fecha::es_mayor($fecha_desde, $fecha_hasta)) {
			$this->set_error('800_control_fechas', array('Desde', 'mayor', 'Hasta'));
		}
	}

	function validar_horas_inicio_finalizacion_asignacion($hora_inicio, $hora_finalizacion)
	{
		if (guarani_hora::es_mayor_igual($hora_inicio, $hora_finalizacion)) {
			$this->set_error('800_error_rango_horas');
		}
	}

	/**
	 * Valida que el rango horario (día, hora_inicio, hora_finalización) de una asignación
	 * no se solape on el rango horario de alguna otra asignación para el mismo aula (en rangos de fechas superpuestos). 
	 */
	function validar_rango_horario_asignacion($dia_semana, $periodicidad, $hora_inicio, $hora_finalizacion, $fecha_desde, $fecha_hasta, $asignacion = null)
	{
		// Se valida contra las asignaciones de la comisión actual (memoria).
		// Para las asignaciones de la misma comisión se chequea que no se superpongan, 
		// para el mismo día de semana, las fechas y horas (rangos solapados).
		// Si se encuentra alguna que lo haga, se calculan las clases de esta y las de la bh que se está
		// insertando/editando, para verificar superposición de fecha calendario (por periodicidad).
		$id_fila = $this->dep('asignaciones')->get_cursor();
		// Calculo de las fechas de clases de la asignación que se está insertando/editando.
		$clases_nueva_asig = toba::consulta_php('co_bandas_horarias')->get_clases_asignacion_bh_no_sincronizada($fecha_desde, $fecha_hasta, $periodicidad, $dia_semana);
		$asignaciones_dt = $this->get_datos_asignaciones(array('dia_semana' => $dia_semana));
		foreach ($asignaciones_dt as $a) {
			if (guarani_fecha::rango_solapado_en_rango($a['fecha_desde'], $a['fecha_hasta'], $fecha_desde, $fecha_hasta) &&
				guarani_hora::rango_solapado_en_rango($a['hora_inicio'], $a['hora_finalizacion'], $hora_inicio, $hora_finalizacion) &&
				(is_null($id_fila) || $id_fila != $a[apex_datos_clave_fila])) {
				// Si se encontró una asignación actual de la comisión que se superpone en 
				// rango de fechas y horario, se recuperan las clases de la misma tales que sus
				// fechas estén entre las de la asignación que se está intentando insertar/editar.
				$filtro_fechas_in = guarani_sql::armar_condicion_in($clases_nueva_asig, 'fecha', 'fecha');
				$clases_superpuestas = toba::consulta_php('co_bandas_horarias')->get_clases_asignacion_bh_no_sincronizada($a['fecha_desde'], $a['fecha_hasta'], $a['periodicidad'], $a['dia_semana'], $filtro_fechas_in);
				if (!empty($clases_superpuestas)) {
					$this->set_notificacion('800CUR_horario_comision_superpuesto');
					break;
				}
			}
		}

	}

	//--------------------------------------------------------------------------
	
	/**
	 * Determina si existen asignaciones docentes activas asociadas a la cátedra actual. Es decir si la comisión tiene
	 * una cátedra asignada y hay docentes pertenecientes a la misma que se asociaron a la comisión con una fecha de 
	 * finalización posterior a la actual.
	 */
	function verificar_asignaciones_cambio_catedra()
	{
		$datos_comision = $this->get_datos_generales();
		$fecha_actual = guarani_fecha::get_hoy();
		$hay_docente_activo_catedra = false;
		if (isset($datos_comision['catedra'])) {
			// Se recuperan los docentes asociados a la comisión.
			$docentes_comision = $this->get_datos_docentes_asociados();
			foreach ($docentes_comision as $asignacion_doc) {
				// Si el docente asociado a la comisión tiene todavía una asignación activa, se chequea si es de la cátedra.
				if (guarani_fecha::es_mayor($asignacion_doc['fecha_hasta'], $fecha_actual)) {
					$hay_docente_activo_catedra = toba::consulta_php('co_catedras')->es_docente_catedra($datos_comision['catedra'], $asignacion_doc['docente']);
					if ($hay_docente_activo_catedra) {
						break;
					}
				}
			}
		}
		return $hay_docente_activo_catedra;
	}

	/**
	 * Determina si existen dedicacion horarias cargadas para el docente dado como argumento. Si es así se retorna true, en
	 * caso contrario false. Se utiliza para pedir confirmación al momento de eliminar el docente dado.
	 */
	function verificar_dedicacion_docente($id_fila_docente)
	{
		// Se setea el cursor en la fila interna del docente seleccionado para eliminar en el form ml.
		$this->tabla('docentes')->set_cursor($id_fila_docente);
		// Se recuperan las dedicaciones horarias para ese docente.
		$dedicacion_docente = $this->tabla('doc_dedicacion')->get_filas();
		if (!empty($dedicacion_docente)) {
			// El docente seleccionado para eliminar tiene alguna dedicación horaria asociada.
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Valida si las asignaciones de la comisión son compartidas
	 */
	function verificar_asignacion()
	{	
		$asignaciones = $this->dep('asignaciones')->get_filas();
		foreach ($asignaciones as $reg) {
			$id_comision = $this->tabla('comisiones')->get_columna('comision');
			$compartido = current(guarani::co('co_bandas_horarias')->asignacion_compartida($id_comision, $reg['asignacion']));
			if ($compartido['compartida'] == 1) {			
				throw new guarani_error(guarani_mensajes::get('800CUR_error_bh_compartida'));
			}
		}
	}

	/**
	 * Se desvincula el Curso Virtual de la Comisión.
	 */
	function desvincular_curso_virtual()
	{
		// Traigo datos de la comisión
		$datos_generales = $this->get_datos_generales();
		
		// Si se trata de una comisión nueva salgo
		if (!isset($datos_generales['comision'])) return 0;
		
		$comision = $datos_generales['comision'];
		
		return guarani::act('act_comisiones')->desvincular_curso_virtual_by_comision($comision);
	}
	
}