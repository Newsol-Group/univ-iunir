<?php
/**
 * Manejador de Administración de Planes de Estudios
 *
 * @package		Propuestas
 * @subpackage	Plan
 * 
 */
class cn_man_administrar_planes extends cn_man_planes
{
	protected $s__usa_planes_personalizados;
	protected $s__datos_alumno_inscr;
	protected $s__datos_periodo;

	function resetear()
	{
		unset($this->s__datos_alumno_inscr);
		unset($this->s__datos_periodo);
		parent::resetear();
	}

	/**
	 * Setea el parámetro cuando lo consulta desde el ini del ci principal.
	 */
	function set_planes_personalizados($usa_planes_pers)
	{
		$this->s__usa_planes_personalizados = $usa_planes_pers;
	}

	function get_datos_alumno()
	{
		if (isset($this->s__datos_alumno_inscr)) {
			return $this->s__datos_alumno_inscr;
		}
	}

	/**
	 * Al dar de alta los datos del Plan en el DT, almacena en variables de sesión los datos del Alumno y Ubicación, para
	 * poder realizar el Alta del mismo y la Inscripción a Propuesta correspondiente, luego de sincronizar los datos 
	 * del plan y versión con la BD.
	 */
	function set_datos_alumno($datos_plan_pers)
	{
		try {
			if (isset($datos_plan_pers['persona']) && isset($datos_plan_pers['propuesta'])) {
				$this->validar_seleccion_persona($datos_plan_pers);
				$this->s__datos_alumno_inscr = $datos_plan_pers;
			} else {
				$msj = guarani_mensajes::get('800_dato_obligatorio', array('Persona y Propuesta'));
				throw new guarani_error($msj);
			}
		} catch (guarani_error $e) {
			throw new guarani_error($e->get_mensaje());
		}
	}

	function unset_datos_alumno()
	{
		unset($this->s__datos_alumno_inscr);
	}

	/**
	 * Al dar de alta los datos del Plan en el DT, almacena en variables de sesión los datos del Alumno y Ubicación, para
	 * poder realizar el Alta del mismo y la Inscripción a Propuesta correspondiente, luego de sincronizar los datos 
	 * del plan y versión con la BD.
	 */
	function set_datos_periodo($datos_plan_pers)
	{
		if (isset($datos_plan_pers['periodo_insc'])) {
			$this->s__datos_periodo = array('anio_academico' => $datos_plan_pers['anio_academico'], 'periodo_insc' => $datos_plan_pers['periodo_insc']);
		} else {
			$msj = guarani_mensajes::get('800_dato_obligatorio', array('Período de Inscripción'));
			throw new guarani_error($msj);
		}
	}

	function get_datos_periodo()
	{
		if (isset($this->s__datos_periodo)) {
			return $this->s__datos_periodo;
		}
	}

	function unset_datos_periodo()
	{
		unset($this->s__datos_periodo);
	}

	//-----------------------------------------------------------------------------------

	function validar_seleccion_persona($datos_persona)
	{
		$persona = $datos_persona['persona'];
		$propuesta = $datos_persona['propuesta'];
		// Se valida que la persona seleccionada no tenga inscripciones Pendientes o Aceptadas en la propuesta 
		// (sga_situacion_aspirante.resultado_asp = A o P).
		$filtro = "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_pendiente . "','" . propuesta::inscripcion_aceptada . "')";
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $propuesta, null, $filtro);
		if (isset($cant_inscripciones) && $cant_inscripciones != 0) {
			$mje = guarani_mensajes::get('800MAT_persona_inscripta');
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- SINCRONIZACIÓN ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Dispara la sincronizacion del DR para los datos básicos del Plan y sus Elementos, para luego dar 
	 * de Alta manualmente la Inscripción a Propuesta y el Alumno, la generación de Legajo y sus 
	 * registros correspondientes. Para esto se reusa la función de procesar inscripciones, que además 
	 * da de alta los requisitos de aspirante a propuesta relacionados, los evalúa para chequear si están
	 * cumplidos por otra propuesta y la inscripción puede quedar como aceptada y también realiza la
	 * inscripción en las propuestas vinculadas.
	 */
	function guardar()
	{
		$tipo_plan = $this->entidad()->get_tipo_plan();
		if ($this->s__usa_planes_personalizados && $this->entidad()->es_nueva() &&
			($tipo_plan == planes::tipo_convenio || $tipo_plan == planes::tipo_personalizado)) {
			try {
				// Se establece el Código Automático default para los Planes Personalizados.
				$cod = toba::db()->recuperar_nuevo_valor_secuencia('codigo_planes_personalizados_seq');
				$datos_plan['codigo'] = 'PP-' . $cod;
				$this->entidad()->set_datos_cabecera($datos_plan);

				// Para el Alta de un Plan Personalizado o de tipo Convenio.
				$this->desactivar_transacciones_anidadas();
				toba::db()->abrir_transaccion();
				if (isset($this->s__datos_periodo)) {
					parent::guardar();
					// Recupera el id de Plan Versión generado luego de sincronizar para realizar la Inscripción. 
					// El DR sigue cargado para su edición, ya que no se resetea al Guardar.
					$datos_version = $this->entidad()->get_datos_version();
					$plan_version = $datos_version['plan_version'];

					// Se obtiene el Período para el cual se realizará la Inscripción.
					$per_insc_fechas = $this->s__datos_periodo['periodo_insc'];
					$anio_academico = $this->s__datos_periodo['anio_academico'];
					$datos_periodo_fechas = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc_fechas($this->s__datos_periodo['periodo_insc']);
					$periodo_inscripcion = $datos_periodo_fechas['periodo_inscripcion'];

					// Dato del Convenio si el Plan Personalizado es de este Tipo.
					$convenio = null;
					if ($tipo_plan == planes::tipo_convenio) {
						$convenio = $this->entidad()->get_convenio_plan_pers();
					}
					// Proceso de Alta de Inscripción y del Alumno en el Plan Versión creado. 
					// Observar que en el procesar Alumno siempre ingresa por el caso de que
					// no existen inscripciones previas (rechazadas) ya que no sucede para Planes Pers.
					preinscripcion_nucleo::procesar_inscripcion($this->s__datos_alumno_inscr['persona'], $periodo_inscripcion, $per_insc_fechas, $anio_academico, $this->s__datos_alumno_inscr['propuesta'], $plan_version, $this->s__datos_alumno_inscr['modalidad'], $this->s__datos_alumno_inscr['ubicacion'], 'now()', null, $convenio, comunes::no, propuesta::situacion_asp_insc_aspirante_a_prop, null, $tipo_plan);
				} else {
					$msj = guarani_mensajes::get('800MAT_sin_pi_preinscripcion');
					throw new guarani_error($msj);
				}
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				$this->activar_transacciones_anidadas();
				throw $e;
			}
		} else {
			// Caso del Plan Estructurado, o Edicion de cualquier tipo de Plan, se sincronizan los datos de la manera usual.
			parent::guardar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- DUPLICACIÓN DE PLAN ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function duplicar_plan($plan_version_origen, $tipo_plan, $convenio = null)
	{
		// Debe generar también los datos de una Versión única asociada al Plan creado, con datos default.
		$nombre_persona = toba::consulta_php('co_personas')->get_nombre_persona($this->s__datos_alumno_inscr['persona']);
		$nombre_version_default = 'Versión única para el alumno ' . $nombre_persona;
		$nombre_plan_default = 'Plan personalizado para el alumno ' . $nombre_persona;
		$id_version_default = 'V1';

		try {
			/* Se invoca a la función de Copiar de Plan-Versión que generará un plan versión nuevo a partir del dado,
			 * copiando todos sus datos y estructura, salvo por los pedidos previamente al usuario.
			 */
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();

			$id_plan_version_nuevo = guarani::act('act_planes')->duplicar_plan_version($plan_version_origen, $id_version_default, $nombre_version_default, $nombre_plan_default, $this->s__datos_alumno_inscr['propuesta'], $tipo_plan, $this->s__datos_alumno_inscr['modalidad'], $convenio);

			// Se obtiene el Período para el cual se realizará la Inscripción.
			$per_insc_fechas = $this->s__datos_periodo['periodo_insc'];
			$anio_academico = $this->s__datos_periodo['anio_academico'];
			$datos_periodo_fechas = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc_fechas($this->s__datos_periodo['periodo_insc']);
			$periodo_inscripcion = $datos_periodo_fechas['periodo_inscripcion'];

			// Proceso de Alta de Inscripción y del Alumno en el Plan Versión creado. 
			preinscripcion_nucleo::procesar_inscripcion($this->s__datos_alumno_inscr['persona'], $periodo_inscripcion, $per_insc_fechas, $anio_academico, $this->s__datos_alumno_inscr['propuesta'], $id_plan_version_nuevo, $this->s__datos_alumno_inscr['modalidad'], $this->s__datos_alumno_inscr['ubicacion'], 'now()', null, $convenio, comunes::no, propuesta::situacion_asp_insc_aspirante_a_prop, null, $tipo_plan);
			toba::db()->cerrar_transaccion();

			return $id_plan_version_nuevo;
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- ELIMINACIÓN DE PLAN ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Dispara la eliminacion de plan cargado y corre el porceso depurador de elementos Huerfanos en
	 * la tabla sga_elementos. Borra las matrices de equivalencia con destino el Plan Versión que se
	 * elimina, en caso de que corresponda.
	 * Si el tipo de Plan es Personalizado o Convenio, debe verificarse primero si pueden eliminarse
	 * la Inscripción a Propuesta y el Alumno, generados como consecuencia del alta del Plan.
	 */
	// Actualmente este proceso pierde sentido, ya que sólo se permiten editar Planes Nuevos, con lo cual el Alumno
	// no tendrá historia académica.
	function eliminar($id = null)
	{
		/**
		 * Ticket #2031. Caso de Eliminación para un Plan de Estudios de Tipo Personalizado/Convenio
		 * Primero debe eliminarse la Insc. a Propuesta, ver si puede eliminarse el Alumno y el Plan o darlos de Baja.
		 */
		$indice = isset($id) ? $id : $this->s__cursor;
		$datos_plan = $this->entidad()->get_datos_plan();
		$plan_version = $this->entidad()->get_id_plan_version();

		if ($datos_plan['tipo_plan'] == planes::tipo_personalizado || $datos_plan['tipo_plan'] == planes::tipo_convenio) {
			try {
				/**
				 * Debe desactivar transacciones anidadas antes de la eliminación de la Insc. a Propuesta, y el
				 * Eliminar de la Entidad, ya que en ambas funciones se abrían transacciones aparte, y en este caso es
				 * necesario que todo el proceso se ejecute en una única. Se define un parámetro adicional en las funciones
				 * para que no generen otra transacción separada.
				 */
				$this->desactivar_transacciones_anidadas();
				$desactivar_trans = true;
				toba::db()->abrir_transaccion();
				$alumno = toba::consulta_php('co_alumnos')->get_alumno_de_plan_version($plan_version);
				$datos_prop = toba::consulta_php('co_inscripciones')->get_datos_insc_aceptada_o_pendiente_para_persona_planversion($alumno['persona'], $plan_version);
				try {
					// Msjes específicos de la Eliminación del Alumno y Propuesta, se capturan aparte de la eliminación del Plan.
					$eliminado = $this->eliminar_inscripcion_a_propuesta($datos_prop['propuesta_aspira'], $datos_prop['fecha_inscripcion'], $alumno['persona'], $datos_prop['propuesta'], $alumno['alumno'], $desactivar_trans);
				} catch (guarani_error $e) {
					$mje = guarani_mensajes::get('eliminar_error');
					throw new guarani_error($mje);
				} catch (toba_error_db $e) {
					//Msje de error de BD en la eliminación del Alumno, por existencia de fk's.
					$mje = ($e->get_sqlstate() == 'db_23503') ? guarani_mensajes::get('800MAT_baja_insc_actividad', array($datos_plan['propuesta_nombre'])) : guarani_mensajes::get('eliminar_error');
					throw new toba_error($mje);
				}
				if ($eliminado) {
					/** Para el caso de los Planes Estructurados o bien cuando del Plan Personalizado pudo eliminarse la 
					 *  Inscripción a Propuesta y Alumnos asociados, o pudieron modificarse los registros del Alumno para que 
					 *  no quede asociado al Plan versión sin eliminarlo porque había otra inscripción a la Propuesta ----->
					 *  puede ELIMINARSE EL PLAN, LA VERSIÓN Y TODA SU ESTRUCTURA.
					 */
					if ($desactivar_trans) {
						$this->entidad($indice)->desactivar_transaccion_al_eliminar();
					}
					parent::eliminar($indice);
				} else {
					/** 
					 * Si el Alumno ya tenía Historia Académica el Plan no puede eliminarse. 
					 * Se da de Baja y se pasa el Alumno al estado Pasivo.
					 */
					// Se pasa el Alumno a Pasivo.
					guarani::act('act_alumnos')->actualizar_calidad_alumno($alumno['alumno'], alumno::calidad_pasivo);
					// Se inserta el registro de Cambio de Calidad para el Alumno.
					guarani::act('act_alumnos')->insertar_cambio_calidad($alumno['alumno'], alumno::calidad_pasivo, alumno::motivo_calidad_egreso);
					// Se modifica el estado del Plan a dado de Baja.
					guarani::act('act_planes')->baja_de_plan($datos_plan['plan']);
					// Se modifica el estado de la Versión a dada de Baja.
					guarani::act('act_planes')->baja_de_version($plan_version);

					// Se resetea el cn man, antes de volver a la pantalla de selección.
					$this->resetear();
					$mje = guarani_mensajes::get('800PRO_baja_plan_personalizado');
					toba::notificacion()->agregar($mje, 'info');
				}
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				$this->activar_transacciones_anidadas();
				throw $e;
			}
		} else {
			parent::eliminar($indice);
		}
	}

}
?>