<?php
/**
 * Representa una version del plan de estudios
 *
 * @package		Propuestas
 * @subpackage	Plan
 * 
 */
class cn_ent_plan extends cn_entidad
{
	protected $s__id_plan;
	protected $s__id_plan_version;
	protected $s__usar_elementos = false;
	protected $s__controlar_bloqueo = true;
	protected $manejador_certificados = null; // Manejador de los certificados del plan
	protected $desactivar_transaccion_eliminar = false;

	function cargar_atributos_parametros()
	{
		$this->resetear_atributos_parametros();
		$operacion = guarani::get_id_operacion();
		$propuesta = $this->tabla('plan')->get_columna('propuesta');
		$tipo_plan = $this->tabla('plan')->get_columna('tipo_plan');
		
		if (!empty($propuesta)) {
			guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));

			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
			guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		}
		
		if ((guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::no) || 
			((!empty($tipo_plan) && ($tipo_plan == planes::tipo_estructurado)))) {
			// Si no se utilizan planes personalizados, o si el tipo de plan está definido como estructurado
			// se marcan como no utilizados los parámetros de legajo
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_utiliza_legajo');
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_inscripciones_a_generar_legajo');
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_genera_legajo_inscripcion');
			guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_legajo_alumno_generacion');
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Acceso a subpartes  -------------------------------------------------
	//--------------------------------------------------------------------------

	function get_id_plan()
	{
		if (!isset($this->s__id_plan)) {
			$this->s__id_plan = $this->tabla('plan')->get_fila_columna(0, 'plan');
		}
		return $this->s__id_plan;
	}

	/**
	 * Retorna el conjunto de ELEMENTOS contenidos en el plan
	 * @return cn_ent_plan_elementos
	 */
	function elementos()
	{
		return $this->dep('elementos');
	}

	function get_id_plan_version()
	{
		return $this->s__id_plan_version;
	}

	function es_recien_creado()
	{
		return isset($this->s__id_plan_version) ? true : false;
	}

	//--------------------------------------------------------------------------
	//---- INFORMACIÓN ---------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Los Planes Estructurados que no Administran Cursos no pueden editarse salvo que sean Nuevos 
	 * (ni datos básicos ni estructura).
	 * Los Planes Estructurados que Administran Cursos pueden editarse a nivel de estructura cuando son
	 * Nuevos y cuando ya están Activos, mientras no estén dados de baja. Sus datos principales pueden 
	 * editarse sólo cuando son Nuevos. Lo mismo para planes de propuestas vocacionales.
	 * Los Planes Personalizados pueden editarse (datos y estructura) mientras no están dados de baja, salvo 
	 * algunos datos específicos que se setean de sólo lectura en edición, ya que no pueden editarse una vez 
	 * creado el Plan, en ningún estado.
	 */
	function edicion_plan_bloqueada()
	{
		$bloq_cursos = $this->es_plan_para_administrar_cursos() && $this->es_plan_dado_de_baja();
		$bloq_estr = !$this->es_plan_personalizado() && !$this->es_plan_para_administrar_cursos() && !$this->es_propuesta_vocacional() && !$this->es_plan_nuevo();
		$bloq_pers = $this->es_plan_personalizado() && $this->es_plan_dado_de_baja();
		$bloq_voc = $this->es_propuesta_vocacional() && $this->es_plan_dado_de_baja();
		return ($bloq_cursos || $bloq_estr || $bloq_pers || $bloq_voc);
	}

	function edicion_version_bloqueada()
	{
		/**
		 * Por el momento no es necesario el control para Planes Pers ya que actualmente para estos planes los 
		 * datos de la Versión son seteados por default y no se editan (sólo los del Plan). Se deja la 
		 * sentencia de control por uniformidad a futuro.
		 * Análogamente se realiza el control para los planes de Tipo Estructurado que Administran Cursos.
		 */
		$bloq_cursos = $this->es_plan_para_administrar_cursos() && $this->es_version_dada_de_baja();
		$bloq_estr = !$this->es_plan_personalizado() && !$this->es_plan_para_administrar_cursos() && !$this->es_propuesta_vocacional() && !$this->es_nueva_version();
		$bloq_pers = $this->es_plan_personalizado() && $this->es_version_dada_de_baja();
		$bloq_voc = $this->es_propuesta_vocacional() && $this->es_version_dada_de_baja();
		return ($bloq_cursos || $bloq_estr || $bloq_pers || $bloq_voc);
	}

	/**
	 * Indica si el plan se encuentra ACTIVO
	 * @return boolean
	 */
	function esta_activo()
	{
		return (in_array($this->get_estado(), array(planes::estado_activo, planes::estado_vigente)));
	}

	function existe_version_nueva()
	{
		if ($this->es_nueva_version()) {
			return true;
		}
		return $this->co()->plan_posee_version_nueva($this->get_id_plan());
	}

	/**
	 * Indica si la version tiene certificados asociados
	 * @return boolean
	 */
	function tiene_certificados()
	{
		if ($this->s__id_plan_version) {
			$certificados = $this->get_certificados();
			if (!$certificados) {
				return false;
			}
			return ($certificados != array());
		} else {
			return false;
		}
	}

	/**
	 * Devuelve los certificados asociados al plan_version
	 */
	function get_certificados()
	{
		if ($this->s__id_plan_version) {
			$where = " sga_certificados.estado = '" . comunes::estado_activo . "'";
			$certificados = toba::consulta_php('co_certificados')->get_certificados_plan_version($this->s__id_plan_version, $where);
			return $certificados;
		} else {
			return false;
		}
	}

	/**
	 * Indica si la version del cargado es nueva
	 * @return boolean
	 */
	function es_nueva_version()
	{
		return ($this->es_version_nueva());
	}

	/**
	 * Retorna el nombre del plan
	 * @return string
	 */
	function get_nombre()
	{
		return $this->tabla('plan')->get_fila_columna(0, 'nombre');
	}

	/**
	 * Retorna el estado del plan
	 * @return string
	 */
	function get_estado()
	{
		return $this->tabla('plan')->get_fila_columna(0, 'estado');
	}

	function get_responsable_academica()
	{
		if ($this->esta_cargado()) {
			return $this->tabla('plan')->get_fila_columna(0, 'responsable_academica');
		} else {
			//Este metodo tambien se llama desde la creacion
			$datos_plan = $this->get_datos_plan();
			$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($datos_plan['propuesta']);
			return $datos_propuesta['responsable_academica'];
		}
	}

	function get_modalidades_plan()
	{
		return $this->tabla('modalidad')->get_filas();
	}

	function get_modalidad_plan_pers()
	{
		$filas = $this->tabla('modalidad')->get_filas();
		if (!empty($filas)) {
			return current($filas);
		}
	}

	function get_modalidades_cursadas($modalidades = null)
	{
		return toba::consulta_php('co_planes')->get_modalidades_cursadas($modalidades);
	}

	/**
	 *  Analiza los datos que vienen del formulario y devuelve un array con los datos necesarios para procesar
	 *  las filas del datos tabla modalidad
	 *
	 * @param $datos
	 * @return Array
	 */
	protected function set_datos_modalidad($datos)
	{
		$modalidades = $this->tabla('modalidad')->get_filas();
		$modalidades_aplanadas = aplanar_matriz($modalidades, 'modalidad');
		$modalidades_a_borrar = array_diff($modalidades_aplanadas, $datos);
		$modalidades_a_insertar = array_diff($datos, $modalidades_aplanadas);
		$modalidades_asociativas = rs_convertir_asociativo($modalidades, array('modalidad'), apex_datos_clave_fila);

		foreach ($modalidades_a_borrar as $modalidad) {
			$this->tabla('modalidad')->eliminar_fila($modalidades_asociativas[$modalidad]);
		}

		foreach ($modalidades_a_insertar as $modalidad) {
			$this->tabla('modalidad')->nueva_fila(array('modalidad' => $modalidad));
		}
	}

	/**
	 * En el caso de Plan Personalizado se permite seleccionar una única modalidad.
	 */
	protected function set_datos_modalidad_pers($datos)
	{
		// Si se está editando la modalidad existente, se selecciona la fila para modificar su valor.
		$modalidad = $this->tabla('modalidad')->get_filas();
		if (!empty($modalidad)) {
			$ids_filas = $this->tabla('modalidad')->get_id_filas();
			$this->tabla('modalidad')->set_cursor(current($ids_filas));
			$this->tabla('modalidad')->set($datos);
		} else {
			// Alta de la modalidad única.
			$this->tabla('modalidad')->nueva_fila($datos);
		}
	}

	function es_plan_nuevo()
	{
		return ($this->get_estado() == planes::estado_nuevo);
	}

	function es_plan_activo()
	{
		return ($this->get_estado() == planes::estado_activo || $this->get_estado() == planes::estado_vigente);
	}

	function es_plan_dado_de_baja()
	{
		return ($this->get_estado() == planes::estado_baja);
	}

	function es_plan_personalizado()
	{
		$tipo = $this->get_tipo_plan();
		return ($tipo == planes::tipo_personalizado || $tipo == planes::tipo_convenio);
	}

	function es_plan_para_administrar_cursos()
	{
		$tipo = $this->get_tipo_plan();
		$adm_cursos = $this->get_administra_cursos();
		return ($tipo == planes::tipo_estructurado && $adm_cursos == comunes::si);
	}

	function es_propuesta_vocacional()
	{
		$tipo = $this->get_tipo_propuesta();
		return ($tipo == propuesta::tipo_vocacional);
	}

	function es_version_nueva()
	{
		return ($this->tabla('version')->get_fila_columna(0, 'estado') == planes::estado_nuevo);
	}

	function es_version_activa()
	{
		$estado_version = $this->get_estado_version();
		return ($estado_version == planes::estado_activo || $estado_version == planes::estado_vigente);
	}

	function es_version_dada_de_baja()
	{
		return ($this->tabla('version')->get_fila_columna(0, 'estado') == planes::estado_baja);
	}

	function get_version_vigente()
	{
		return $this->tabla('plan')->get_fila_columna(0, 'version_actual');
	}

	function get_vigencia_version_vigente()
	{
		$datos_version = toba::consulta_php('co_planes')->get_datos_plan_version($this->get_version_vigente());
		return $datos_version[0]['fecha_entrada_vigencia'];
	}

	function get_nombre_version_vigente()
	{
		return $this->tabla('plan')->get_fila_columna(0, 'version_nombre');
	}

	function get_nombre_version()
	{
		return $this->tabla('version')->get_fila_columna(0, 'nombre');
	}

	function get_codigo_version()
	{
		return $this->tabla('version')->get_fila_columna(0, 'version');
	}

	function get_estado_version()
	{
		return $this->tabla('version')->get_fila_columna(0, 'estado');
	}

	//--------------------------------------------------------------------------
	//---- Alteracion granular -------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Activa una versión del plan, la cual pasa a estar vigente
	 * (Este método se llama desde el proceso de activación de planes)
	 */
	function activar_version()
	{
		$datos_version = $this->get_datos_version();

		// Ver que el plan version sea nuevo
		if (!$this->es_version_nueva()) {
			throw new guarani_error('Sólo pueden activarse versiones nuevas.');
		}
		// Ver que el plan version tenga los datos de documento y fecha activacion
		if (!$datos_version['documento_alta']) {
			throw new guarani_error('La versión no tiene cargado el Documento de alta.');
		}
		if (!$datos_version['fecha_entrada_vigencia']) {
			throw new guarani_error('La versión no tiene cargada la fecha de entrada en vigencia.');
		}

		$this->set_version_vigente();

		// Si el plan es nuevo lo paso a Activo, sino lo dejo como estaba (Vigente o Activo)
		// y si es nuevo le paso los valores de documento y fecha de vigencia de la versión
		if ($this->es_plan_nuevo()) {
			$datos['estado'] = planes::estado_vigente;
			$datos['documento_alta'] = $datos_version['documento_alta'];
			$datos['fecha_entrada_vigencia'] = $datos_version['fecha_entrada_vigencia'];
			$this->set_datos_cabecera($datos);
			$this->set_plan_vigente();
		}

		// Se actualiza la versión actual del Plan
		$this->tabla('plan')->set_fila_columna_valor(0, 'version_actual', $datos_version['plan_version']);
	}

	/**
	 * Versiona una versión del plan, la cual pasa a estar en estado Nuevo
	 * (Este método se llama desde el proceso versionador de planes)
	 */
	function versionar_version($nueva_version, $nuevo_nombre_version)
	{
		$datos_version = $this->get_datos_version();

		// Ver que el plan version no sea nuevo
		if ($this->es_nueva_version()) {
			throw new guarani_error('No se puede versionar una versión nueva.');
		}

		// Ver que no exista una versión nueva existente en el plan
		if ($this->existe_version_nueva()) {
			throw new guarani_error('Ya existe una versión nueva en el plan.');
		}

		$id_plan_version_nueva = guarani::act('act_planes')->crear_version_plan($this->get_id_plan_version(), $nueva_version, $nuevo_nombre_version);

		return $id_plan_version_nueva;
	}

	/**
	 * Setea el plan como activa vigente
	 * 
	 */
	function set_plan_vigente()
	{
		$datos['estado'] = planes::estado_vigente;
		$this->tabla('plan')->set($datos);
	}

	/**
	 * Setea el plan como activa no vigente
	 * 
	 */
	function set_plan_no_vigente()
	{
		$datos['estado'] = planes::estado_activo;
		$this->tabla('plan')->set($datos);
	}

	/**
	 * Setea la versión del plan como activa no vigente
	 * 
	 */
	function set_version_no_vigente()
	{
		$datos['estado'] = planes::estado_activo;
		$this->tabla('version')->set($datos);
	}

	/**
	 * Setea la versión del plan como activa vigente
	 * 
	 */
	function set_version_vigente()
	{
		$datos['estado'] = planes::estado_vigente;
		$this->tabla('version')->set($datos);
	}

	//--------------------------------------------------------------------------
	//---- ALTERACION de BLOQUES de datos --------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna los datos de cabecera del plan
	 * @return recorset
	 */
	function get_datos_plan()
	{
		return $this->tabla('plan')->get();
	}

	function get_tipo_plan()
	{
		$datos = $this->get_datos_plan();
		if (isset($datos['tipo_plan'])) {
			return $datos['tipo_plan'];
		}
	}

	function get_administra_cursos()
	{
		$datos = $this->get_datos_plan();
		if (isset($datos['administra_cursos'])) {
			return $datos['administra_cursos'];
		}
	}

	function get_convenio_plan_pers()
	{
		$datos = $this->get_datos_plan();
		if (isset($datos['convenio'])) {
			return $datos['convenio'];
		}
	}

	function get_tipo_propuesta()
	{
		$datos_plan = $this->get_datos_plan();
		if (isset($datos_plan['propuesta_tipo'])) {
			return $datos_plan['propuesta_tipo'];
		}
	}

	/**
	 * Establece los datos de la cabecera del plan
	 * @param $datos datos de la cabecera del plan
	 */
	function set_datos_cabecera($datos)
	{
		$this->validar_datos_plan($datos);
		$this->tabla('plan')->set($datos);
	}

	/**
	 * Establece los datos de la cabecera del plan y la modalidad
	 * @param $datos datos de la cabecera y la modalidad del plan
	 */
	function set_datos_cabecera_y_modalidad($datos)
	{
		// Si no pasa la validación no setea ni la cabecera ni la modalidad...
		$this->validar_datos_plan($datos);
		if (isset($datos['modalidad'])) {
			$tipo_plan = $this->get_tipo_plan();
			if (isset($tipo_plan) && $tipo_plan != planes::tipo_estructurado) {
				$this->set_datos_modalidad_pers($datos);
			} else {
				$this->set_datos_modalidad($datos['modalidad']);
			}
		}

		// Unset de datos que no pertenecen a la tabla 'sga_planes'.
		unset($datos['estado']);
		unset($datos['modalidad']);
		unset($datos['creditos_tit']);
		unset($datos['duracion_tit']);
		$this->tabla('plan')->set($datos);
		
		$this->cargar_atributos_parametros();
	}

	/**
	 * Resetea todo el datos relación con los datos del Plan, Versión y Modalidades.
	 */
	function reset_datos_principales_plan()
	{
		$this->dep('datos')->resetear();
		// Inicializa nuevamente el estado de los datos default.
		$this->tabla('plan')->set(array('estado' => planes::estado_nuevo, 'inscripcion_habilitada' => comunes::si));
		$this->tabla('version')->set(array('estado' => planes::estado_nuevo));
	}

	/**
	 * Retorna los datos de la version del plan
	 * @return recorset
	 */
	function get_datos_version()
	{
		return $this->tabla('version')->get();
	}

	/**
	 * Establece los datos de la version del plan
	 * @param recordset
	 */
	function set_datos_version($datos)
	{
		unset($datos['estado']);
		$this->validar_datos_version($datos);
		$this->validar_fecha_activacion($datos);
		$this->tabla('version')->set($datos);
	}

	/**
	 * @ignore
	 */
	function tabla($tabla)
	{
		return $this->dep('datos')->tabla($tabla);
	}
	/**
	 * Establece si  cobrales cambio
	 * @param recordset
	 */
	function set_datos_cobrable($datos)
	{
		if ($this->tabla('plan')->get_columna('cobrable') != $datos['cobrable']) {
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($datos['propuesta']);
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron',$ras);
			$cobrable = $datos['cobrable'];
			$sq_sincronizado = $this->tabla('plan')->get_columna('sq_sincronizado');
			//si cambia a cobrable y distinto no_sincronizado_sq(2), coloca en no_sincronizado_sq para que se sincronise y cree el servicio.
			//si cambia a cobrable y es igual a no_sincronizado_sq coloca a sincronizado_sq(1) ya que no necesita volver a crear el sercio por que no se borro.  
			//si cambia a no cobrable y distinto no_sincronizado_sq(2) coloca en no_sincronizado_sq para que se sincronise, eliminando el servicio.
			//si cambia a no cobrable y igual a no_aplica_sincronizado_sq(0) ya que no es necesario eliminar el servicio, por que nunca se creo. 
			if ($usa_sq == comunes::si && !$this->es_plan_para_administrar_cursos()) {				
				if ($cobrable == comunes::si && $sq_sincronizado != planes::no_sincronizado_sq) {
					$this->tabla('plan')->set_columna_valor('sq_sincronizado', planes::no_sincronizado_sq);
				} else if ($cobrable == comunes::si && $sq_sincronizado == planes::no_sincronizado_sq) {
					$this->tabla('plan')->set_columna_valor('sq_sincronizado', planes::sincronizado_sq);
				} else if ($cobrable == comunes::no && $sq_sincronizado != planes::no_sincronizado_sq) {
					$this->tabla('plan')->set_columna_valor('sq_sincronizado', planes::no_sincronizado_sq);
				} else {
					$this->tabla('plan')->set_columna_valor('sq_sincronizado', planes::no_aplica_sincronizado_sq);
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Validacion de datos -------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_datos_plan($datos)
	{
		if (!isset($datos['codigo']))
			return;
		$propuesta = isset($datos['propuesta']) ? $datos['propuesta'] : $this->tabla('plan')->get_columna('propuesta');
		$plan = $this->tabla('plan')->get_columna('plan');
		$this->validar_codigo_plan($datos['codigo'], $propuesta, $plan);
	}

	protected function validar_codigo_plan($codigo, $propuesta = null, $plan = null)
	{
		if (!toba::consulta_php('co_planes')->es_codigo_plan_valido($codigo, $propuesta, $plan)) {
			throw new guarani_error('ERROR: El código de plan ingresado ya está en uso.');
		}
	}

	function validar_fecha_activacion($datos)
	{
		/**
		 * Ticket #1427: 
		 * se deberá chequear que la fecha de activación no sea menor al 01/01/1900
		 */
		if (!empty($datos['fecha_entrada_vigencia'])) {
			$fecha_limite = '1900-01-01';
			if (guarani_fecha::es_menor($datos['fecha_entrada_vigencia'], $fecha_limite)) {
				$mensaje = guarani_mensajes::get('800_dato_invalido', array('fecha de entrada en vigencia', 'la fecha no puede ser anterior al 01/01/1900'));
				throw new guarani_error($mensaje);
			}
		}
	}

	function validar_datos_version($datos)
	{
		if (isset($datos['version'])) {
			$plan = $this->tabla('plan')->get_columna('plan');
			if (isset($plan)) {
				$plan_version = $this->tabla('version')->get_columna('plan_version');
				$this->validar_codigo_version($datos['version'], $plan, $plan_version);
			}
		}
		$estado_version = $this->get_estado_version();
		if ($estado_version == planes::estado_nuevo && isset($datos['fecha_entrada_vigencia'])) {
			$this->validar_fecha_vigencia($datos['fecha_entrada_vigencia']);
		}
		if ($estado_version == planes::estado_activo || $estado_version == planes::estado_vigente) {
			if (isset($datos['documento_baja']) || isset($datos['fecha_baja'])) {
				$this->validar_datos_baja($datos['documento_baja'], $datos['fecha_baja']);
			}
			if (isset($datos['fecha_baja'])) {
				$this->validar_fecha_baja($datos['fecha_baja'], $datos['fecha_entrada_vigencia']);
			}
		}
	}

	protected function validar_codigo_version($codigo, $plan = null, $plan_version = null)
	{
		if (!toba::consulta_php('co_planes')->es_codigo_plan_version_valido($codigo, $plan, $plan_version)) {
			throw new guarani_error('ERROR: El código de versión ingresado ya está en uso.');
		}
	}

	protected function validar_fecha_vigencia($f_vigen)
	{
		$hoy = guarani_fecha::get_hoy();

		if (guarani_fecha::es_mayor($f_vigen, $hoy)) {
			throw new guarani_error('La fecha de entrada en vigencia no puede ser posterior al día de hoy.');
		}

		if ($this->es_plan_activo()) {
			$ultima_vigencia = $this->get_vigencia_version_vigente();
			if (!guarani_fecha::es_mayor($f_vigen, $ultima_vigencia)) {
				$formateo = new toba_formateo('html');
				throw new guarani_error('La fecha de entrada en vigencia debe ser posterior a la de la versión actual (' . $formateo->formato_fecha($ultima_vigencia) . ').');
			}
		}
	}

	protected function validar_datos_baja($documento_baja, $fecha_baja)
	{
		if ((isset($documento_baja) && !isset($fecha_baja)) || (!isset($documento_baja) && isset($fecha_baja))) {
			$msj_datos_baja = guarani_mensajes::get('800PRO_error_datos_baja');
			throw new guarani_error($msj_datos_baja);
		}
		$plan_version = $this->tabla('version')->get_columna('plan_version');
		if (toba::consulta_php('co_planes')->plan_version_posee_alumnos_activos($plan_version)) {
			$msj_baja_alumnos_activos = guarani_mensajes::get('800PRO_error_baja_alumnos_activos');
			throw new guarani_error($msj_baja_alumnos_activos);
		}
	}

	protected function validar_fecha_baja($fecha_baja, $fecha_entrada_vigencia)
	{
		$hoy = guarani_fecha::get_hoy();
		if (guarani_fecha::es_mayor($fecha_baja, $hoy)) {
			$msj_fecha_baja_hoy = guarani_mensajes::get('800_control_fechas', array('de baja', 'mayor', 'actual'));
			throw new guarani_error($msj_fecha_baja_hoy);
		}
		if (guarani_fecha::es_menor($fecha_baja, $fecha_entrada_vigencia)) {
			$msj_fecha_baja_vigencia = guarani_mensajes::get('800_control_fechas', array('de baja', 'menor', 'de entrada en vigencia'));
			throw new guarani_error($msj_fecha_baja_vigencia);
		}
	}

	//--------------------------------------------------------------------------
	//---- Primitivas de ESTADO ------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Inicializa un nuevo plan
	 */
	function inicializar_estado()
	{
		unset($this->s__id_plan);
		unset($this->s__id_plan_version);
		$this->s__usar_elementos = true;
		$this->dep('elementos')->set_bloqueo_compartibles();
		$this->dep('datos')->resetear();
		$this->tabla('plan')->set(array('estado' => planes::estado_nuevo, 'inscripcion_habilitada' => comunes::si));
		$this->tabla('version')->set(array('estado' => planes::estado_nuevo));
		$this->dep('elementos')->inicializar_estado();
	}

	/**
	 * Carga un plan de estudios
	 */
	function cargar($id_plan_version, $usar_elementos = false)
	{
		toba::logger()->debug("\n###################################################################################################\n" .
				"########################--------- Inicio cargar PLAN [$id_plan_version] -----------------#########################\n" .
				"###################################################################################################\n");
		$this->s__id_plan_version = $id_plan_version;
		$this->s__usar_elementos = $usar_elementos;
		$this->dep('elementos')->set_bloqueo_compartibles();

		// Cargo la informacion del plan
		$wheres['plan'] = "plan = (	SELECT plan
									FROM sga_planes_versiones
									WHERE plan_version = $id_plan_version)";
		$wheres['version'] = "(plan_version = $id_plan_version)";
		$this->dep('datos')->persistidor()->cargar_con_wheres($wheres);
		// Cargo la informacion de elementos
		if ($this->s__usar_elementos) {
			$this->dep('elementos')->cargar($this->s__id_plan_version);
		}
		toba::logger()->debug("\n###################################################################################################\n" .
				"##########################---------- Fin cargar PLAN -------------------###########################\n" .
				"###################################################################################################\n");
	}

	/**
	 * @ignore
	 */
	function esta_cargado()
	{
		return isset($this->s__id_plan_version);
	}

	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios() || $this->dep('elementos')->hay_cambios();
	}

	function pre_sincronizar()
	{
		$datos_version = $this->get_datos_version();
		$version_activa = ($datos_version['estado'] == planes::estado_activo || $datos_version['estado'] == planes::estado_vigente) ? true : false;
		if ($version_activa && isset($datos_version['documento_baja']) && isset($datos_version['fecha_baja'])) { // Se debe dar de baja la Versión?
			$this->desactivar_control_bloqueo();
			$this->tabla('version')->set_columna_valor('estado', planes::estado_baja);

			// Si todas las versiones están dadas de baja => se da de baja el plan.
			$datos_plan = $this->get_datos_plan();
			$filtro = "sga_planes_versiones.plan_version <> {$datos_version['plan_version']}";
			if (!toba::consulta_php('co_planes')->plan_posee_version_no_dada_de_baja($datos_plan['plan'], $filtro)) {
				$datos_plan['estado'] = planes::estado_baja;
				$datos_plan['documento_baja'] = $datos_version['documento_baja'];
				$datos_plan['fecha_baja'] = $datos_version['fecha_baja'];
				$this->tabla('plan')->set($datos_plan);
			}
		}
	}

	/**
	 * Guarda la informacion del plan
	 */
	function sincronizar()
	{
		$this->pre_sincronizar();
		$this->control_edicion_no_bloqueada();
		$this->desactivar_transacciones_anidadas();
		try {
			toba::logger()->debug("\n###################################################################################################\n" .
					"########################--------- Inicio sincronizar PLAN ----------------#########################\n" .
					"###################################################################################################\n");
			toba::db()->abrir_transaccion();
			// Se guarda la cabecera del plan
			$this->dep('datos')->sincronizar();
			$filas = $this->tabla('version')->get_id_filas();
			$id_plan_version = $this->tabla('version')->get_clave_valor($filas[0]);
			$propuesta = $this->tabla('plan')->get_columna('propuesta');
			$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($propuesta);
			$prop_cod = $datos_propuesta['codigo'];
			$prop_nombre_ab = $datos_propuesta['nombre_abreviado'];		
			//Se guardan los registros que se van a sincronizar para hacer el rest 
			//con SQ ya que luego de guardar se pierde cuales eran U o I
			$ids_insert = $this->dep('elementos')->get_id_filas_a_sincronizar_elementos_plan('i');
			$ids_update = $this->dep('elementos')->get_id_filas_a_sincronizar_elementos_plan('u');
			// Se guardan los elementos
			if ($this->s__usar_elementos) {
				// Obtengo los elementos eliminados del plan
				$elementos_eliminados = $this->dep('elementos')->get_indice_elementos_eliminados();
				$ids_delete = array_keys($elementos_eliminados);

				$modulo_raiz = $this->dep('elementos')->sincronizar($id_plan_version['plan_version']);
				// Se guarda el módulo raíz.
				guarani::act('act_planes')->actualizar_modulo_raiz($id_plan_version['plan_version'], $modulo_raiz);

				$e_optativas_huerfanas = array();
				foreach ($ids_delete as $key => $elemento_delete) {
					// Si el elemento estaba dos o más veces en el plan, no se da de baja en SQ.
					if ($this->dep('elementos')->existe_elemento_en_plan_version($id_plan_version['plan_version'], $elemento_delete)) {
						unset($ids_delete[$key]);
					} else {
						// Si se trata de una materia genérica y el parámetro 'pro_ver_optativas_en_estructura_plan' tiene el valor 'N',
						// se deben eliminar las optativas que correspondan del plan (no estaban cargadas).
						if (!$this->dep('elementos')->ver_optativas() &&
							toba::consulta_php('co_elementos')->es_materia_generica($elemento_delete)) {
							$optativas_huerfanas = toba::consulta_php('co_elementos')->get_optativas_huerfanas_de_generica_plan_version($elemento_delete, $id_plan_version['plan_version']);
							$this->eliminar_correlativas_de_optativas_huerfanas($optativas_huerfanas);
							guarani::act('act_planes')->eliminar_elementos_plan(aplanar_matriz($optativas_huerfanas, 'elemento_revision'), $id_plan_version['plan_version']);
							$e_optativas_huerfanas = array_merge($e_optativas_huerfanas, aplanar_matriz($optativas_huerfanas, 'elemento'));
						}
					}
				}
				$ids_delete = array_merge($ids_delete, $e_optativas_huerfanas);

				// Luego de sincronizar los elementos del Plan, se verifica si deben informarse las Actividades que se 
				// setearon como cobrables a Sanavirón-Quilmes.
				$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron');
				if ($usa_sq == comunes::si && $this->es_plan_para_administrar_cursos()) {					
					$msj_error_sq = $this->dep('elementos')->marcar_actividades_plan_cobrables_sq($propuesta, $prop_cod, $prop_nombre_ab, $ids_insert, $ids_update, $ids_delete);
					if (!empty($msj_error_sq)) {
						toba::notificacion()->agregar($msj_error_sq);
					}
				}
			}
			toba::db()->cerrar_transaccion();
			toba::logger()->debug("\n###################################################################################################\n" .
					"########################--------- Fin sincronizar PLAN [version: {$id_plan_version['plan_version']}]  -------#########################\n" .
					"###################################################################################################\n");
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
		$this->s__id_plan_version = $id_plan_version['plan_version'];
		// Se resetea el plan y lo vuelvo a cargar
		// (Esto elimina una rama enorme de testeo: se puede suponer que la situacion post sincro es igual a la inicial)
		$id_plan_version = $this->s__id_plan_version;
		$usar_elementos = $this->s__usar_elementos;
		$this->resetear();
		$this->cargar($id_plan_version, $usar_elementos);
	}

	function eliminar_correlativas_de_optativas_huerfanas($optativas_huerfanas)
	{
		$manejador_correlativas = $this->dep('elementos')->manejador_correlativas();
		$entidades_con_correlativas = $this->dep('elementos')->_get_entidades_con_correlativas();
		foreach ($optativas_huerfanas as $optativa) {
			if (in_array($optativa['entidad'], $entidades_con_correlativas)) {
				$manejador_correlativas->eliminar_correlativas($optativa['entidad']);
			}
		}
	}

	/**
	 * Eliminación de un Plan Version
	 */
	function eliminar()
	{
		$this->control_edicion_no_bloqueada();
		$this->control_eliminacion();
		$this->desactivar_transacciones_anidadas();

		try {
			toba::logger()->debug("\n###################################################################################################\n" .
					"########################--------- Inicio eliminacion PLAN ----------------#########################\n" .
					"###################################################################################################\n");
			if (!$this->desactivar_transaccion_eliminar) {
				toba::db()->abrir_transaccion();
			}
			toba::db()->retrazar_constraints();
			// Se elimina la referencia al modulo raiz
			$this->tabla('version')->set_columna_valor('elemento_revision', null);
			$this->tabla('version')->sincronizar();
			$this->disociar_certificados_asignados_al_plan();
			$this->eliminar_elementos();
			$this->eliminar_plan_version();
			if (!$this->desactivar_transaccion_eliminar) {
				toba::db()->cerrar_transaccion();
			}
			toba::logger()->debug("\n###################################################################################################\n" .
					"########################--------- Fin eliminacion PLAN   -------#########################\n" .
					"###################################################################################################\n");
		} catch (toba_error $e) {
			if (!$this->desactivar_transaccion_eliminar) {
				toba::db()->abortar_transaccion();
			}
			throw $e;
		}
	}

	private function eliminar_elementos()
	{
		// Si el parámetro 'pro_ver_optativas_en_estructura_plan' tiene el valor 'N',
		// se deben eliminar las optativas "huérfanas" del plan (no estaban cargadas en el DT).
		$actividades_genericas = array();
		if (!$this->dep('elementos')->ver_optativas()) {
			$actividades_genericas = toba::consulta_php('co_planes')->get_genericas_plan_version($this->s__id_plan_version);
		}
		$this->dep('elementos')->eliminar();
		foreach ($actividades_genericas as $actividad_generica) {
			$optativas_huerfanas = toba::consulta_php('co_elementos')->get_optativas_huerfanas_de_generica_plan_version($actividad_generica['elemento'], $this->s__id_plan_version);
			$this->eliminar_correlativas_de_optativas_huerfanas($optativas_huerfanas);
			guarani::act('act_planes')->eliminar_elementos_plan(aplanar_matriz($optativas_huerfanas, 'elemento_revision'), $this->s__id_plan_version);
		}
	}

	private function eliminar_plan_version()
	{
		/**
		 * Si el plan es nuevo elimino todo ya que no tiene otras versiones.
		 * Si el plan es Personalizado siempre se elimina todo, ya que esté en estado Nuevo o no, 
		 * sólo puede tener una única Versión.
		 */
		if ($this->es_plan_nuevo() || $this->es_plan_personalizado()) {
			$this->dep('datos')->eliminar_todo();
		} else {
			$this->tabla('version')->eliminar_filas();
			$this->dep('datos')->sincronizar();
		}
	}

	/**
	 * 	Devuelve una referencia al manejador de restricciones de este plan
	 */
	function manejador_certificados()
	{
		if (!isset($this->manejador_certificados)) {
			if (isset($this->s__id_plan_version)) {
				$this->manejador_certificados = toba::cn('manejador_certificados_plan');
				$this->manejador_certificados->cargar($this->s__id_plan_version, false);
			} else {
				throw new guarani_error('No es posible instanciar el manejador de certificados porque el plan es nuevo!');
			}
		}
		return $this->manejador_certificados;
	}

	function disociar_certificados_asignados_al_plan()
	{
		$manejador_certificados = $this->manejador_certificados();
		$certificados = toba::consulta_php('co_certificados')->get_certificados_plan_version($this->s__id_plan_version);
		$param = Array('plan_version' => $this->s__id_plan_version,
			'plan_version_editable' => true
		);
		$manejador_certificados->desactivar_transacciones_anidadas();
		foreach ($certificados as $certificado) {
			$param['entidad'] = $certificado['entidad'];
			$param['certificado'] = $certificado['certificado'];
			$manejador_certificados->disociar_certificado($param);
		}
	}

	/**
	 * @ignore
	 */
	function desactivar_transacciones_anidadas()
	{
		parent::desactivar_transacciones_anidadas();
		if ($this->s__usar_elementos) {
			$this->dep('elementos')->desactivar_transacciones_anidadas();
		}
	}

	/**
	 * @ignore
	 */
	function resetear()
	{
		unset($this->s__id_plan_version);
		$this->dep('datos')->resetear();
		if ($this->s__usar_elementos) {
			$this->dep('elementos')->resetear();
		}
		unset($this->s__usar_elementos);
		$this->activar_control_bloqueo();
		toba::logger()->debug("RESETEAR PLAN");
	}

	protected function control_edicion_no_bloqueada()
	{
		if ($this->s__controlar_bloqueo && $this->edicion_version_bloqueada()) {
			throw new guarani_error('ERROR: No es posible editar');
		}
	}

	function activar_control_bloqueo()
	{
		$this->s__controlar_bloqueo = true;
	}

	function desactivar_control_bloqueo()
	{
		$this->s__controlar_bloqueo = false;
	}

	function control_eliminacion()
	{
		$cantidad = toba::consulta_php('co_planes')->get_cantidad_casos_test_plan($this->s__id_plan_version);
		if (!($cantidad == 0)) {
			throw new guarani_error('No es posible eliminar el plan porque existen CASOS de PRUEBA creados para el mismo.');
		}
	}

	function desactivar_transaccion_al_eliminar()
	{
		$this->desactivar_transaccion_eliminar = true;
	}

}