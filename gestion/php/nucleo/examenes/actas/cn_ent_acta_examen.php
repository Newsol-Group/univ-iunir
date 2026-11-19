<?php
class cn_ent_acta_examen extends cn_entidad
{
	protected $s__llamado_mesa_entidad;
	protected $s__acta;
	protected $s__llamado_mesa;
	protected $s__mesa_examen;
	protected $s__evaluacion_auto;
	protected $s__alumnos_agregados; //Variable para mantener los alumnos a los cuales se les agregó detalle_eval_examen
	protected $id_acta_ficticia = acta::id_acta_ficticia;
	public $s__eliminado;
    protected $s__inscripto_instancia;
    protected $s__instancias_disponibles;
    
	function set_eliminado($alumno)
	{
		$this->s__eliminado = $alumno;
	}
	
	function get_eliminado()
	{
		return $this->s__eliminado;
	}
	
    function set_inscripto_instancia($instancia) 
    {
        $this->s__inscripto_instancia = $instancia;
    }
    
    function get_inscripto_instancia()
    {
        return $this->s__inscripto_instancia;
    }
    
    /*
     * Se valida si hay o no seleccion automatica de una instancia.
     */
    function hay_seleccion_automatica_de_instancia()
	{
		$sel_auto = guarani::parametros()->get_valor('exa_instancia_automatica');
		return ($sel_auto == comunes::si);
	}
    
	function cargar_atributos_parametros($mesa_examen)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}
	
	function entidad_cargada()
	{
		return isset($this->s__llamado_mesa_entidad);
	}

	function entidad($id = null)
	{
		return $this;
	}

	function guardar($datos_acta = null)
	{
		$this->sincronizar();
	}

	function sincronizar()
	{
		// Se levanta una excepción para que se haga un rollback de los insert/update que se hacen por fuera del DR
		if (!$this->hay_cambios()) {
			throw guarani::error(toba::mensajes()->get('800_no_hubo_cambios'));
		}
		$this->validar_editabilidad();
		$this->pre_sincronizar();
		//Devuelve el ID de la entidad ingresada en la base.
		$this->dep('datos')->sincronizar();
	}

	function hay_cambios()
	{
		return ($this->dep('datos')->hay_cambios() || $this->get_eliminado());
	}

	function get_listado($filtro)
	{
		// Para todas las operaciones de Actas de Examen, se listan los Llamados de Mesas de Examen que 
		// estén en Estado Activo (no Suspendido).
		$where = "sga_llamados_mesa.estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		$filtro = !empty($filtro) ? $filtro . ' AND ' . $where : $where;
		return toba::consulta_php('co_mesas_de_examen')->get_listado_con_cant_actas_sin_anular($filtro);
	}

	function cargar($id, $parametros = array())
	{
		parent::cargar($id);
		$this->s__llamado_mesa_entidad = $parametros['entidad'];
		$this->s__llamado_mesa = $parametros['llamado_mesa'];
		$this->s__mesa_examen = $parametros['mesa_examen'];
		$this->s__evaluacion_auto = $parametros['evaluacion'];
		if (isset($parametros['acta'])) {
			$this->s__acta = $parametros['acta']['id_acta'];
		}
	}

	function resetear()
	{
		parent::resetear();
		unset($this->s__llamado_mesa_entidad);
		unset($this->s__llamado_mesa);
		unset($this->s__mesa_examen);
		unset($this->s__acta);
		unset($this->s__evaluacion_auto);
		unset($this->s__alumnos_agregados);
		unset($this->s__inscripto_instancia);
		unset($this->s__instancias_disponibles);
	}

	//--------------------------------------------------------------------------
	//---- Acceso a la estructura de persistencia ------------------------------
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- DT eval_detalle_examenes --------------------------------------------
	//--------------------------------------------------------------------------

	function set_cursor_evaluacion_automatica()
	{
		$cursor = current($this->tabla('evaluaciones')->get_id_fila_condicion(array('evaluacion' => $this->s__evaluacion_auto)));
		$this->tabla('evaluaciones')->set_cursor($cursor);
	}

	function get_detalles($condiciones = null)
	{
		$datos = $this->tabla('eval_detalle_examenes')->get_filas($condiciones, false, false);
		return rs_ordenar_por_columna($datos, 'apellido_y_nombre');
	}

	function get_detalles_en_orden_parametro($condiciones = null)
	{
		$datos = $this->tabla('eval_detalle_examenes')->get_filas($condiciones, false, false);
		$param_orden = guarani::parametros()->get_valor('exa_orden_detalle_actas');
		if ($param_orden == 1) {
			// Ordena por legajo
			$campo_ordenamiento = 'legajo_order';
			array_walk($datos, function (&$alumno) {
				$alumno['legajo_order'] = guarani_texto::limpiar_acentos($alumno['legajo']);
			});
		} elseif ($param_orden == 2) {
			// Ordena por apellido y nombre
			$campo_ordenamiento = 'apellido_y_nombre_order';
			array_walk($datos, function (&$alumno) {
				$alumno['apellido_y_nombre_order'] = guarani_texto::limpiar_acentos($alumno['apellido_y_nombre']);
			});
		} elseif ($param_orden == 3) {
			// Ordena por tipo y número de documento
			$campo_ordenamiento = 'documento_principal';
		}
		
		return rs_ordenar_por_columna($datos, $campo_ordenamiento, SORT_STRING|SORT_FLAG_CASE);
	}

	function get_id_detalle($condiciones)
	{
		return current($this->tabla('eval_detalle_examenes')->get_id_fila_condicion($condiciones, false));
	}

	function get_ids_detalle($condiciones)
	{
		return $this->tabla('eval_detalle_examenes')->get_id_fila_condicion($condiciones, false);
	}

	function modificar_detalle($id, $datos)
	{
		$this->tabla('eval_detalle_examenes')->modificar_fila($id, $datos);
	}

	function procesar_detalles($datos)
	{
		$this->set_cursor_evaluacion_automatica();
		$this->tabla('eval_detalle_examenes')->procesar_filas($datos);
	}

	function modificar_fila_columna_detalle($id, $columna, $valor)
	{
		$this->tabla('eval_detalle_examenes')->set_fila_columna_valor($id, $columna, $valor);
	}

	function modificar_filas_columna_detalle($ids, $columna, $valor)
	{
		foreach ($ids as $id) {
			$this->modificar_fila_columna_detalle(array(apex_datos_clave_fila => $id), $columna, $valor);
		}
	}

	function modificar_columnas_detalle($columna, $valor)
	{
		$this->tabla('eval_detalle_examenes')->set_columna_valor($columna, $valor, false);
	}

	function eliminar_detalle($datos)
	{
		$this->tabla('eval_detalle_examenes')->eliminar_fila($datos);
	}

	function existe_detalle($condiciones)
	{
		return $this->tabla('eval_detalle_examenes')->existe_fila_condicion($condiciones, false);
	}

	function agregar_detalle($datos)
	{
		$this->set_cursor_evaluacion_automatica();
		$this->tabla('eval_detalle_examenes')->nueva_fila($datos);
	}

	function get_detalle($id)
	{
		$detalle = $this->tabla('eval_detalle_examenes')->get_fila($id);
		return $detalle;
	}

	//--------------------------------------------------------------------------
	//---- DT eval_detalle -----------------------------------------------------
	//--------------------------------------------------------------------------

	function procesar_detalles_eval_manual($datos)
	{
		$this->tabla('eval_detalle')->procesar_filas($datos);
	}

	function set_cursor_evaluacion_manual($evaluacion)
	{
		$cursor = current($this->tabla('evaluaciones')->get_id_fila_condicion(array('evaluacion' => $evaluacion)));
		$this->tabla('evaluaciones')->set_cursor($cursor);
	}

	function agregar_detalle_eval_manual($datos)
	{
		$this->set_cursor_evaluacion_manual($datos['evaluacion']);
		$this->tabla('eval_detalle')->nueva_fila($datos);
	}

	function eliminar_detalle_eval_manual($datos)
	{
		$this->tabla('eval_detalle')->eliminar_fila($datos);
	}

	function get_ids_detalle_eval_manual($condiciones)
	{
		return $this->tabla('eval_detalle')->get_id_fila_condicion($condiciones, false);
	}

	function modificar_detalle_eval_manual($id, $datos)
	{
		$this->tabla('eval_detalle')->modificar_fila($id, $datos);
	}

	//--------------------------------------------------------------------------
	//---- DT evaluaciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function get_detalles_evaluaciones_manuales($condiciones)
	{
		$datos = $this->tabla('eval_detalle')->get_filas($condiciones, false, false);
		return rs_ordenar_por_columna($datos, 'evaluacion');
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna los datos de las evaluaciones manuales de la el llamado mesa entidad seleccionado
	 */
	function get_datos_evaluaciones_manuales()
	{
		$where = "	sga_evaluaciones.entidad = $this->s__llamado_mesa_entidad 
					AND sga_evaluaciones_tipos.automatica = '" . comunes::no . "' 
					AND sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_mesa . "'";

		return toba::consulta_php('co_evaluaciones')->get_listado($where);
	}

	//--------------------------------------------------------------------------
	//---- API que usa el selector de alumnos ----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna la clase co, método y parámetros con el cual el selector de alumnos recupera los alumnos disponibles de la mesa
	 * 
	 * @return array
	 */
	function get_metodo_alumnos_mesa_disponibles()
	{
		$array['clase_co'] = 'co_evaluaciones';
		$array['metodo'] = 'get_detalle_evaluacion_examen';
		$array['parametros'] = array($this->get_evaluacion());
		$array['parametros']['filtro'] = $this->filtro_alumnos_disponibles();
		return $array;
	}

	/**
	 * Obtiene el filtro con el cual se va a ejecutar la consulta que retorna 
	 * los alumnos disponibles para una mesa de examen
	 *
	 * @return string
	 */
	function filtro_alumnos_disponibles()
	{
		// Se recuperan los alumnos del acta que se está editando.
		// Para el caso de la eliminación de un alumno del acta, éste deberá aparecer ahora en los alumnos disponibles para agregar,
		// ya que como el listado de alumnos agregados esta en un DT, este cambio de la eliminación no se refleja en la BD
		// hasta las sincronización, por lo tanto esta consulta no lo recupera. Esto se soluciona trayendo todos los alumnos
		// del acta también y el selector luego resta los que estan asociados al acta
		$acta = $this->get_acta();
		$filtro = " AND (sga_eval_detalle_examenes.id_acta IS NULL OR sga_eval_detalle_examenes.id_acta = $acta)";
		return $filtro;
	}

	/**
	 * Retorna el la clase co, método y parámetros con el cual el selector de alumnos recupera los otros alumnos disponibles
	 * (no tienen inscripciones , o la tienen pendiente para esa evaluación) y pertenecen a alguna propuesta para la cual esta 
	 * evaluación es definida)
	 *
	 * @return array
	 */
	function get_metodo_alumnos_otros_disponibles()
	{
		$array['clase_co'] = 'co_alumnos';
		$array['metodo'] = 'get_otros_alumnos_disponibles_acta_examen';
		$array['parametros'] = array($this->get_evaluacion());
		$array['parametros']['param_exa_rendir_regularizada_plan_anterior'] = guarani::parametros()->get_valor('exa_rendir_regularizada_plan_anterior');
        $array['parametros']['filtro'] = $this->filtro_alumnos_otros_disponibles();
		return $array;
	}

	/**
	 * Retorna el filtro con el cual se va a ejecutar la consulta que devuleve los 
	 * alumnos de las propuestas para las cuales el examen tiene habilitada inscripción 
	 * y que estos NO ESTAN INCLUIDOS en alguna otra acta de examen (no anulada) de la mesa.
	 * 
	 * @return string
	 */
	function filtro_alumnos_otros_disponibles()
	{
		// El dato $evaluacion se recupera en el método donde se usa este filtro.
		$filtro = "AND sga_alumnos.alumno NOT IN (
					-- Alumnos que no estén en actas cerradas (esto es no anuladas ya que las abiertas estan en eval_detalle)
					SELECT sga_actas_detalle.alumno
					  FROM sga_actas
					  JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta 
					 WHERE sga_actas.evaluacion			= [EVALUACION]
					   AND sga_actas.estado				= '" . acta::estado_cerrada . "'
					   AND sga_actas_detalle.rectificado	= '" . comunes::no . "'
					   AND sga_actas_detalle.estado		= '" . acta::detalle_estado_activo . "'
					UNION
					-- Y que no esten en actas abiertas de la mesa
					SELECT sga_eval_detalle_examenes.alumno
					  FROM sga_eval_detalle_examenes
					 WHERE sga_eval_detalle_examenes.evaluacion = [EVALUACION]
					   AND sga_eval_detalle_examenes.id_acta IS NOT NULL
					) 
				 AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
		return $filtro;
	}

	//--------------------------------------------------------------------------
	//---- Manejo de los Detalles de las evaluaciones --------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Agrega alumnos de la mesa, 
	 *
	 * @param array $alumno
	 */
	function agregar_alumno_mesa($alumno)
	{
		$id_detalle = $this->get_id_detalle($alumno);
		$alumno['agregado'] = true;
		$this->modificar_detalle($id_detalle, $alumno);
	}

	/**
	 * Agrega varios alumnos de la mesa
	 *
	 * @param array $alumnos
	 */
	function agregar_alumnos_mesa($alumnos)
	{
		if (!empty($alumnos)) {
			foreach ($alumnos as $alumno) {
				$this->agregar_alumno_mesa($alumno);
			}
		}
	}

	function agregar_todos_los_alumnos_de_la_mesa()
	{
		$filtro = 'true' . $this->filtro_alumnos_disponibles();
		$detalles = toba::consulta_php('co_evaluaciones')->get_detalle_evaluacion_examen($this->get_evaluacion(), $filtro);
		foreach ($detalles as $detalle) {
			$id_detalle = $this->get_id_detalle(array('alumno' => $detalle['alumno']));
			$this->modificar_fila_columna_detalle($id_detalle, 'agregado', true);
		}
	}

	/**
	 * Agrega alumnos que no estan disponibles para la mesa a la selección, para ser asociados al acta a generar.
	 * En caso de alumnos inscriptos a la mesa pero en estado pendiente, el control no dejará agregarlo al acta.
	 *
	 * @param $alumno
	 * @param $instancia
	 */
	function agregar_alumno_otros($alumno)
	{
		// Validaciones para agregar el alumno al acta.
		$this->validar_alumno($alumno);
		$this->insertar_detalle_de_eval_automatica($alumno);
		$this->insertar_detalle_de_eval_manuales($alumno);
		// Se mantienen los alumnos que se van asociando para luego en el caso de desasociarlos 
		// saber si hay que eliminarlos de eval_detalle o no.
		$this->s__alumnos_agregados[$alumno['alumno']] = true;
        unset($this->s__inscripto_instancia);
	}

	/**
	 * Inserta los detalles correspondientes a las evaluacines manuales
	 *
	 * @param <type> $alumno
	 */
	function insertar_detalle_de_eval_manuales($alumno)
	{
		$evaluaciones_manuales = $this->get_datos_evaluaciones_manuales();
		$detalle['alumno'] = $alumno['alumno'];
		$detalle['rectificado'] = comunes::no;
		foreach ($evaluaciones_manuales as $evaluacion) {
			$detalle['evaluacion'] = $evaluacion['evaluacion'];
			$detalle['escala_nota'] = $evaluacion['escala_nota'];
			$detalle['fecha'] = $evaluacion['fecha'];
			$detalle['resultado'] = null;
			$this->agregar_detalle_eval_manual($detalle);
		}
	}

	/**
	 * Inserta los detalles corresponientes a las evaluaciones automáticas
	 *
	 * @param $alumno
	 */
	function insertar_detalle_de_eval_automatica($alumno)
	{
		$detalle = $alumno;
		$detalle['agregado'] = true;
		$detalle['inscripto'] = comunes::no;
		$detalle['instancia'] = $this->get_inscripto_instancia();
		$detalle['escala_nota'] = $this->get_escala_nota_instancia($this->get_inscripto_instancia());
		$detalle['evaluacion'] = $this->get_evaluacion();
		$detalle['resultado'] = null;
		$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa_entidad($this->get_llamado_mesa_entidad());
		$detalle['fecha'] = $datos_llamado_mesa['fecha'];
		$this->agregar_detalle($detalle);
	}

	/**
	 * Elimina de la selección el alumno. Si fue agregado en este momento a eval_detalle_examen, 
	 * lo elimina de la tabla, sino solo desasocia seteando id_acta en null
	 */
	function eliminar_alumno($id_eval_detalle_axamen)
	{
		$detalle = $this->get_detalle($id_eval_detalle_axamen);
		if (isset($this->s__alumnos_agregados[$detalle['alumno']])) {
			// Si no se acaba de asociar, se lo elimina de eval_detalle_examen,
			$this->eliminar_detalle_de_eval_manuales($detalle['alumno']);
			$this->eliminar_detalle_de_eval_automatica($id_eval_detalle_axamen);
			unset($this->s__alumnos_agregados[$detalle['alumno']]);
		} else { 
			// Si no, sólo se modifica el detalle
			$this->modificar_fila_columna_detalle($id_eval_detalle_axamen, 'agregado', false);
		}
	}

	/**
	 * Elimina los detalles correspondientes a las evaluaciones manuales del alumno
	 *
	 * @param int $alumno
	 */
	function eliminar_detalle_de_eval_manuales($alumno)
	{
		$ids_eval_detalle = $this->get_ids_detalle_eval_manual(array('alumno' => $alumno));
		foreach ($ids_eval_detalle as $id) {
			$this->eliminar_detalle_eval_manual(array(apex_datos_clave_fila => $id));
		}
	}

	/**
	 * Elimina los detalles correspondientes a las evaluaciones automáticas del id recibido
	 *
	 * @param $id
	 */
	function eliminar_detalle_de_eval_automatica($id)
	{
		$this->eliminar_detalle($id);
	}

	/**
	 * Retorna la escala de notas de la instancia recibida para la mesa de examen actual.
	 */
	function get_escala_nota_instancia($instancia)
	{
		$datos = guarani::co('co_mesas_de_examen')->get_escala_nota_instancia($this->s__mesa_examen, $instancia);
		return $datos['escala_nota'];
	}

	/**
	 * Retorna las escalas de la mesa de examen actual
	 *
	 * @return array
	 */
	function get_escalas_notas_mesa($mesa_examen = null)
	{
		return guarani::co('co_mesas_de_examen')->get_escala_notas_mesa($this->s__mesa_examen);
	}

	/**
	 * Retorna las escalas de notas de todas las evaluaciones del llamado mesa entidad actual
	 *
	 * @return array
	 */
	function get_escalas_notas_de_evaluaciones_mesa()
	{
		return guarani::co('co_evaluaciones')->get_evaluaciones_relacionadas($this->get_llamado_mesa_entidad());
	}

	function get_alumnos_asociados()
	{
		$condiciones = array('agregado' => true);
		return $this->get_detalles($condiciones);
	}

	//--------------------------------------------------------------------------
	//---- Atributos -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_evaluacion()
	{
		return $this->s__evaluacion_auto;
	}

	function get_llamado_mesa_entidad()
	{
		return $this->s__llamado_mesa_entidad;
	}

	function get_llamado_mesa()
	{
		return $this->s__llamado_mesa;
	}

	function get_mesa_examen()
	{
		return $this->s__mesa_examen;
	}

	/**
	 * Retorna el acta seleccionada. En caso de operaciones que no seleccionan actas
	 * devuelve el valor del acta ficticia
	 */
	function get_acta()
	{
		return isset($this->s__acta) ? $this->s__acta : $this->id_acta_ficticia;
	}

	/**
	 * Genera un nuevo numero de acta
	 *
	 * @return int
	 */
	function generar_nro_acta($tipo_acta)
	{
		$parametros['origen'] = acta::origen_examen;
		$parametros['tipo_acta'] = $tipo_acta;
		$parametros['llamado_mesa'] = $this->get_llamado_mesa();

		try {
			return toba::consulta_php('co_actas')->generar_nro_acta($parametros);
		} catch (toba_error $e) {
			throw guarani::error(guarani_mensajes::get('800_error_generar_nro_acta'));
		}
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_alumno($alumno)
	{
		$mesa = $this->get_mesa_examen();
		$llamado_mesa = $this->get_llamado_mesa();
		$this->validar_inscripcion($alumno['alumno']);
        if ($this->hay_seleccion_automatica_de_instancia()) {
            $this->determinar_instancia(current($alumno));
            if (isset($this->s__inscripto_instancia)) {
                $datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno['alumno']));
                $nombre_instancia = $this->s__instancias_disponibles[$this->s__inscripto_instancia]['instancia_nombre'];                
                toba::notificacion()->info('Se agrego al alumno '.$datos_alumno['apellido_y_nombre']. ' en la instancia '. $nombre_instancia);
            }
        } else {        
            $this->validar_puntos_de_control_agregar_alumno(current($alumno), $this->get_inscripto_instancia(), $mesa, $llamado_mesa);
        }
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
    
    /*
	 * Determina automáticamente la instancia a inscribirse
	 */
	protected function determinar_instancia($alumno)
	{
		$mensajes = '';
        foreach ($this->get_instancias_mesa() as $key => $instancia) {
			try {
				$retorno = $this->validar_instancia($alumno, $instancia['instancia'], false);
			} catch (guarani_error $e) {
				$retorno = guarani_punto_de_control::rechazado;
				$mensajes .= '\n' . $e->get_mensaje();
			}
			if ($retorno != guarani_punto_de_control::rechazado) {
				$this->set_inscripto_instancia($instancia['instancia'], $instancia['instancia_nombre']);
				return;
			}
		}
		throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
	}
    
    /*
     * Determina cuales son las instancias de la mesa.
     */
    
    function get_instancias_mesa()
	{
		if (!isset($this->s__instancias_disponibles)) {
			$mesa = toba::db()->quote($this->get_mesa_examen());
			$filtro = "sga_mesas_examen.mesa_examen = $mesa";
			$instancias_disponibles = guarani::co('co_mesas_de_examen')->get_instancias_mesas_examen($filtro);
			$instancias_disponibles = rs_ordenar_por_columna($instancias_disponibles, 'instancia_orden');
            foreach($instancias_disponibles as $instancia) {
                $this->s__instancias_disponibles[$instancia['instancia']] = $instancia;
            }            
		}
		return $this->s__instancias_disponibles;
	}    
    
    /*
     * Se valida que la instancia pasada por parametro sea validada por los respectivos
     * puntos de control
     */    
    protected function validar_instancia($alumno, $instancia, $notificar = false)
	{
        $mesa = $this->get_mesa_examen();
		$llamado_mesa = $this->get_llamado_mesa();
        $retorno = $this->validar_puntos_de_control_agregar_alumno($alumno, $instancia, $mesa, $llamado_mesa);        
		if ($retorno == guarani_punto_de_control::rechazado && $notificar) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_inscripcion_fallo_ctrl'));
		}
		return $retorno;
        
	}

	/**
	 * Si el estado de la inscripción del alumno es pendiente no es posible agregar el alumno
	 */
	function validar_inscripcion($alumno)
	{
		$llamado_mesa = $this->get_llamado_mesa();
		$datos_inscripcion = toba::consulta_php('co_inscripciones_examenes')->get_datos_inscripcion_llamado($alumno, $llamado_mesa);
		if (isset($datos_inscripcion['estado']) && ($datos_inscripcion['estado'] == inscripcion_examen::estado_pendiente)) {
			$this->set_error('800_insc_pendiente_mesa');
		}
	}

	function validar_puntos_de_control_agregar_alumno($alumno, $instancia, $mesa, $llamado)
	{
		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::elegir_actividad_examen,
			guarani_punto_de_control::alumno_mesa_examen,
			guarani_punto_de_control::persona_propuesta,
			guarani_punto_de_control::alumno_anio_academico
		);
		$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);

		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));
		$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($mesa);
		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_alumno['persona'],
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $datos_alumno['plan_version'],
			'elemento' => $datos_mesa['elemento'],
			'llamado_mesa' => $llamado,
			'propuesta' => $datos_alumno['propuesta'],
			'anio_academico' => $datos_mesa['anio_academico']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_error_control_acta'));
		}
	}

}