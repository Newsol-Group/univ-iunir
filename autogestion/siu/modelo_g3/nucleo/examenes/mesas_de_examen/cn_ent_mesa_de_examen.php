<?php
/**
 * Representa una Mesa de Exámen definida para un Turno y una Actividad.
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class cn_ent_mesa_de_examen extends cn_entidad
{
	protected $s__turno_asociado;
	protected $s__acepta_planes_personalizados = comunes::no;

	function inicializar_estado()
	{
		parent::inicializar_estado();
		$this->s__acepta_planes_personalizados = comunes::no;
	}

	function cargar($id)
	{
		parent::cargar($id);
		$this->s__acepta_planes_personalizados = $this->tabla('mesas_examen')->get_columna('acepta_planes_personalizados');
	}

	/**
	 * Se setea la variable de sesión que mantiene el turno para el cual se define la mesa, desde el cn manejador.
	 */
	function set_turno_asociado($turno)
	{
		$this->s__turno_asociado = $turno;
	}

	function verificar_editabilidad()
	{
		
	}

	//--------------------------------------------------------------------------

	function get_datos_generales()
	{
		return $this->tabla('mesas_examen')->get();
	}

	function set_datos_generales($datos)
	{
		$this->validar_datos_generales($datos);
		$this->tabla('mesas_examen')->set($datos);
	}

	function mesa_acepta_planes_personalizados()
	{
		return ($this->tabla('mesas_examen')->get_columna('acepta_planes_personalizados') == comunes::si) ? true : false;
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

	function set_datos_propuestas($datos)
	{
		$propuestas_a_vincular = array();
		if ($this->mesa_acepta_planes_personalizados()) {
			$actividad = $this->tabla('mesas_examen')->get_columna('elemento');
			$propuestas_a_vincular = toba::consulta_php('co_mesas_de_examen')->get_propuestas_a_vincular($actividad);
			$this->validar_propuestas_planes_personalizados($propuestas_a_vincular, $datos);
		}
		$this->tabla('propuestas')->procesar_filas($datos);
		$this->actualizar_propuestas_planes_personalizados($propuestas_a_vincular);
	}

	function controlar_cant_filas_min_propuestas()
	{
		if ($this->mesa_acepta_planes_personalizados()) {
			$this->set_cant_filas_min_propuestas(0);
		}
	}

	function set_cant_filas_min_propuestas($cant)
	{
		$this->tabla('propuestas')->set_tope_min_filas($cant);
	}

	/*
	 * En caso de que la mesa de exámen haya cambiado la condición de aceptar planes
	 * personalizados (de sí a no o viceversa), se agregan o se quitan según corresponda
	 * las propuestas asociadas a planes que no son estructurados.
	 * Si la mesa de exámen acepta planes personalizados, agrega las propuestas asociadas
	 * a la actividad de la comisión de los planes personalizados o tipo convenio
	 * Si la mesa de exámen no acepta planes personalizados, quita las propuestas
	 * que se vinculan con planes personalizados o de tipo convenio
	 */
	function actualizar_propuestas_planes_personalizados($propuestas_a_vincular)
	{
		$ahora_acepta_planes_pers = $this->tabla('mesas_examen')->get_columna('acepta_planes_personalizados');

		if ($ahora_acepta_planes_pers != $this->s__acepta_planes_personalizados) {
			$this->s__acepta_planes_personalizados = $ahora_acepta_planes_pers;

			if ($ahora_acepta_planes_pers == comunes::si) {
				foreach ($propuestas_a_vincular as $propuesta) {
					$this->tabla('propuestas')->nueva_fila($propuesta);
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

	function get_datos_llamados()
	{
		return $this->tabla('llamados')->get_filas();
	}

	function set_datos_llamados($datos)
	{
		$this->validar_datos_llamados($datos);
		$this->tabla('llamados')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------

	function get_datos_instancias()
	{
		return $this->tabla('instancias')->get_filas();
	}

	function set_datos_instancias($datos)
	{
		$this->tabla('instancias')->procesar_filas($datos);
	}

	function eliminar_instancia_mesa($id_fila_instancia)
	{
		if (!is_null($id_fila_instancia)) {
			$instancia = $this->tabla('instancias')->get_fila_columna($id_fila_instancia, 'instancia');
			$mesa = $this->tabla('mesas_examen')->get_columna('mesa_examen');
			if (!empty($mesa)) { 
				// Si la mesa NO es nueva se eliminan las instancias que estén asociadas a evaluaciones automáticas y la asociación a la mesa.
				guarani::act('act_actas')->eliminar_instancias_de_evaluaciones_manuales($instancia, $mesa);
			}
			$this->tabla('instancias')->eliminar_fila($id_fila_instancia);
		}
	}

	/**
	 * Se crea una nueva instancia asociada a la mesa de examen a partir del array recibido con el id de la instancia.
	 * La escala de notas se asigna con el valor por defecto cargado en el parámetro de sistema correspondiente.
	 */
	function agregar_instancia_mesa($instancia)
	{
		$instancia['escala_nota'] = $this->get_escala_notas_defecto();
		$this->tabla('instancias')->nueva_fila($instancia);
	}

	/**
	 * Elimina las instancias libres de la mesa
	 */
	function eliminar_instancia_libre_mesa()
	{
		$filas = $this->tabla('instancias')->get_id_fila_condicion(array('instancia' => mesa_de_examen::inst_exam_libre));
		if (!empty($filas)) {
			$this->eliminar_instancia_mesa(current($filas));
		}
	}

	function get_escala_notas_defecto()
	{
		$elemento = $this->tabla('mesas_examen')->get_columna('elemento');
		//con false recupera las escalas de notas de las propuestas asociadas a todos los planes, no sólo de los estructurados
		$propuestas = $this->get_datos_propuestas(false); 
		$columna = 'escala_nota_examen';
		if (!empty($propuestas)) {
			$escalas = toba::consulta_php('co_elementos')->get_escalas_notas_elemento_planes($elemento, $propuestas, $columna);
			if (guarani_arreglos::count($escalas) == 1) {
				return $escalas[0][$columna];
			}
		}
		$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($elemento);
		return $datos_elemento[$columna];
	}

	//--------------------------------------------------------------------------

	function validar_datos_generales($datos)
	{
		// Si se está editando una mesa de exámen se obtiene el id de la misma, si se está agregando este será null.
		$datos['mesa_examen'] = $this->tabla('mesas_examen')->get_columna('mesa_examen');
		if (isset($datos['nombre']) && isset($datos['turno_examen'])) {
			$this->validar_nombre_mesa($datos['nombre'], $datos['elemento'], $datos['turno_examen'], $datos['ubicacion'], $datos['mesa_examen']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determinar si el nombre de la Mesa de Exámen ya existe para esa Actividad y ese Turno de Exámen.
	 * 
	 * @param string 	nombre			es el nombre que debe verificarse si ya existe.
	 * @param integer	elem			id de la actividad a la que está asociada la mesa.
	 * @param integer	turno_examen	id del turno de exámen para el cual se definió la mesa.
	 * @param integer	ubicacion		id de la ubicación para la cual se definió la mesa.
	 * @param integer	mesa			id de la mesa que se excluye del control (para el caso de la edición de la entidad).
	 */
	function validar_nombre_mesa($nombre, $elem, $turno_examen, $ubicacion, $mesa)
	{
		if (!toba::consulta_php('co_mesas_de_examen')->validar_nombre_mesa($nombre, $elem, $turno_examen, $ubicacion, $mesa)) {
			$this->set_error('dato_existente', array('nombre', 'Mesa de Exámen asociada a la Actividad, Ubicación y Turno de Exámen'));
		}
	}

	//--------------------------------------------------------------------------

	function validar_propuestas_planes_personalizados($propuestas_personalizadas, $propuestas_estructuradas)
	{
		if ($this->mesa_acepta_planes_personalizados() && empty($propuestas_personalizadas)) {
			$tiene_estructuradas = false;
			foreach ($propuestas_estructuradas as $propuesta) {
				if ($propuesta['requerido']) {
					$tiene_estructuradas = true;
					break;
				}
			}
			if (!$tiene_estructuradas) {
				$this->set_error('800EXA_mesa_sin_planes');
			}
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_instancias($datos)
	{
		// Al momento de guardar los datos se deberá verificar que siempre esté seleccionada al menos una instancia.
		$this->validar_cant_minima_instancias($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determinar si se asociaron la cantidad mínima de instancias (al menos una) a la mesa de exámen en edición o agregada.
	 * 
	 * @param array 	datos		matriz de arreglos asociativos conteniendo las instancias a asociar a la mesa.
	 */
	function validar_cant_minima_instancias($datos)
	{
		if (empty($datos)) {
			// Si no se seleccionó ninguna instancia deberá mostrarse el mensaje de error correspondiente.
			$etiqueta = guarani_etiquetas::get_tabla('sga_mesas_examen_instancias');
			$msj_param = $etiqueta->get('un', true);
			$this->set_error('800_no_seleccion', array($msj_param));
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_llamados($datos)
	{
		// Si el llamado está asociado a la mesa, se verifica que la hora de inicio esté seteada
		// y la fecha de examen esté seteada y entre las fechas de inicio y fin del período.
		$this->validar_fecha_mesa_llamados($datos);
		$this->validar_horas_inicio_fin_llamados($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Determina si los Llamados asociados a la Mesa tienen seteada la fecha obligatoria, y si las mismas son válidas.
	 * 	@param array 	datos		matriz de arreglos asociativos conteniendo los llamados a asociar o no a la mesa.
	 */
	function validar_fecha_mesa_llamados($datos)
	{
		foreach ($datos as $fila => $llamado) {
			if ($llamado[apex_ei_analisis_fila] != 'B') {
				// Si el llamado está asociado a la mesa, la fecha de exámen debe estar seteada.
				if (!isset($llamado['fecha'])) {
					$this->set_error('800_dato_dep_obligatorio', array('fecha de exámen', "asociación del llamado {$llamado['nombre']}."));
					break;
				}

				// La fecha del examen debería estar dentro del rango de inicio y fin del llamado (período asociado).
				$tope_inferior_valido = guarani_fecha::es_mayor_igual($llamado['fecha'], $llamado['fecha_inicio']);
				$tope_superior_valido = guarani_fecha::es_menor_igual($llamado['fecha'], $llamado['fecha_fin']);
				if (!$tope_inferior_valido || !$tope_superior_valido) {
					$this->set_notificacion('800EXA_examen_fuera_rango', array($llamado['nombre']));
				}

				// La fecha de examen no puede coincidir con un día no laboral, teniendo en cuenta la ubicación de la mesa.
				$ubicacion = $this->tabla('mesas_examen')->get_columna('ubicacion');
				$es_dia_no_laboral = toba::consulta_php('co_dias_no_laborables')->es_dia_no_laborable_ubicacion($llamado['fecha'], $ubicacion);
				if ($es_dia_no_laboral) {
					$this->set_notificacion('800_dia_no_laboral');
				}
			}
		}
	}

	/**
	 * Determina para los Llamados asociados a la Mesa, que la hora de inicio esté seteada y en caso de que tenga seteada la hora de fin,
	 * que se verifique que la hora de inicio sea menor a la hora de finalización.
	 * 
	 * @param array 	datos		matriz de arreglos asociativos conteniendo los llamados a asociar o no a la mesa.
	 */
	function validar_horas_inicio_fin_llamados($datos)
	{
		foreach ($datos as $fila => $llamado) {
			if ($llamado[apex_ei_analisis_fila] != 'B') {
				// La hora de inicio es de ingreso obligatorio.
				if (!isset($llamado['hora_inicio'])) {
					$this->set_error('800_dato_dep_obligatorio', array('hora de inicio', "asociación del llamado {$llamado['nombre']}."));
					break;
				}
				// Se valida que la hora de inicio no sea mayor que la hora de finalización.
				if (isset($llamado['hora_finalizacion'])) {
					if ($llamado['hora_inicio'] >= $llamado['hora_finalizacion']) {
						$this->set_error('800EXA_rango_horas_llamado', $llamado['nombre']);
						break;
					}
				}
			}
		}
	}

	function sincronizar()
	{
		$this->controlar_cant_filas_min_propuestas();
		parent::sincronizar();
	}

}
?>