<?php
/**
 * Utilidades propias de la lógica particular del formulario de preinscripción
 * y sus posibilidades de configuración de visibilidad, obligatoriedad e impresión.
 * 
 * Maneja además las relaciones de campos descriptivos con ID's internos de la BD.
 * 
 */
class util_formulario_preinscripcion_nucleo
{
	protected $parametros_campos;
	protected $campos_secciones;
	protected $campos_descr;
	protected $campos_id;
	protected $campos_preinscripcion;
	protected $id;
	protected $fuente;
	protected $origen;

	public function __construct($id, $fuente, $origen)
	{
		$this->id = $id;
		$this->fuente = $fuente;
		$this->origen = $origen;
		$this->inicializar_mapeos_id_descripcion();
	}

	/**
	 * Configuración de campos de formulario para un aspirante, en función de sus
	 * propuestas seleccionadas
	 * 
	 * =============================================
	 * Antes llamada get_parametros_campos_form
	 * =============================================
	 * 
	 * @return array
	 */
	function get_config_campos_aspirante()
	{
		if (!isset($this->parametros_campos)) {
			//$this->parametros_campos = kernel::persona()->datos()->get_config_campos_aspirante();

			$propuestas = toba::consulta_php('co_preinscripciones')->get_propuestas_preinscripcion($this->id, $this->fuente);

			//Busco el tipo de propuesta de esas propuestas y armo un in para buscar luego los campos
			$condicion_propuestas = guarani_sql::armar_condicion_in($propuestas, 'sga_propuestas', 'propuesta');
			$datos = toba::consulta_php('co_propuestas')->get_listado($condicion_propuestas);
			$propuestas_tipo = array_column($datos, 'propuesta_tipo');

			$condicion_tipo_propuestas = guarani_sql::armar_condicion_in($propuestas_tipo, 'sga_campos_conf', 'propuesta_tipo');
			$datos = toba::consulta_php('co_preinscripciones')->get_parametros_campos_form($condicion_tipo_propuestas, $this->fuente);
			foreach ($datos as $dato) {
				$parametros_campos[$dato['columna']] = array('visible' => $dato['visible'], 'obligatorio' => $dato['obligatorio'], 'se_imprime' => $dato['se_imprime']);
			}
			$this->parametros_campos = $parametros_campos;
		}
		return $this->parametros_campos;
	}

	function invalidar_config_campos_conf()
	{
		
	}

	/**
	 * Determina si un campo es visible para el aspirante y sus propuestas 
	 * seleccionadas, en función de la configuración de formulario establecida
	 * 
	 * @param string $campo
	 * @return boolean
	 */
	function visible($campo)
	{
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$campos = $this->get_config_campos_aspirante();
			if (isset($campos[$campo])) {
				return $campos[$campo]['visible'];
			} elseif (($campo == 'celular_caracteristica') || ($campo == 'celular_valor')) {
				// Caso especial: celular_numero
				return $campos['celular_numero']['visible'];
			} else {
				// Si el campo es descripción de un id interno, se asocia a la visibilidad de ese campo.
				$campo_id = $this->get_campo_id($campo);
				if (!is_null($campo_id)) {
					return $campos[$campo_id]['visible'];
				}
			}
		}
		return true;
	}

	/**
	 * Determina si un campo es obligatorio para el aspirante y sus propuestas 
	 * seleccionadas, en función de la configuración de formulario establecida
	 * 
	 * @param string $campo
	 * @return boolean
	 */
	function obligatorio($campo)
	{
		$campos = $this->get_config_campos_aspirante();
		if (isset($campos[$campo])) {
			return $campos[$campo]['obligatorio'];
		} elseif (($campo == 'celular_caracteristica') || ($campo == 'celular_valor')) {
			// Caso especial: celular_numero
			return $campos['celular_numero']['obligatorio'];
		} else {
			// Si el campo es descripción de un id interno, se asocia a la obligatoriedad de ese campo.
			$campo_id = $this->get_campo_id($campo);
			if (!is_null($campo_id)) {
				return $campos[$campo_id]['obligatorio'];
			}
		}
		return false;
	}

	/**
	 * Determina si un campo debe aparecer en la salida PDF para el aspirante y 
	 * sus propuestas seleccionadas, en función de la configuración de formulario
	 * 
	 * @param string $campo
	 * @return boolean
	 */
	function se_imprime($campo)
	{
		// Primero se controlan campos que no se imprimen nunca en su sección
		// (porque se muestran en otros lugares)
		$no_se_imprimen = array('apellido', 'nombres',
			// Campos que sirven de descripción de opción "Otra"
			'alu_otestsup_razon_abandono_otros', 'pueblo_originario_otro'
		);
		if (in_array($campo, $no_se_imprimen)) {
			return false;
		}

		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$campos = $this->get_config_campos_aspirante();
			if (isset($campos[$campo])) {
				$campo_descr = $this->get_campo_descr($campo);
				if (empty($campo_descr)) {
					// No es un ID interno
					return $campos[$campo]['se_imprime'];
				}
			} else {
				// Si el campo es descripción de un id interno, se asocia a la configuración
				// de impresión de ese campo.
				$campo_id = $this->get_campo_id($campo);
				if (!is_null($campo_id)) {
					return $campos[$campo_id]['se_imprime'];
				}
				}
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	/**
	 * Verifica si existe al menos un campo en un agrupado de una sección que 
	 * esté definido como visible
	 * 
	 * @param string $seccion
	 * @param string $agrupado
	 * @return boolean
	 */
	function hay_visibles_agrupado($seccion, $agrupado)
	{
		$campos_seccion = $this->get_campos_seccion($seccion);
		$campos_agrupado = $campos_seccion[$agrupado];

		foreach ($campos_agrupado as $campo) {
			if ($this->origen == preinscripcion::origen_autogestion || ($this->visible($campo) && $this->origen == preinscripcion::origen_preinscripcion)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifica si existe al menos un campo en un agrupado de una sección que 
	 * esté definido como imprimible
	 * 
	 * @param string $seccion
	 * @param string $agrupado
	 * @return boolean
	 */
	function hay_imprimibles_agrupado($seccion, $agrupado)
	{
		$campos_seccion = $this->get_campos_seccion($seccion);
		$campos_agrupado = $campos_seccion[$agrupado];

		foreach ($campos_agrupado as $campo) {
			//Si se imprime y si el origen en preinscripcion
			if ($this->origen == preinscripcion::origen_autogestion || ($this->se_imprime($campo) && $this->origen == preinscripcion::origen_preinscripcion)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifica si existen campos imprimibles en una sección de formulario
	 * 
	 * @param string $seccion
	 * @return boolean
	 */
	function hay_visibles_seccion($seccion)
	{
		$campos_seccion = $this->get_campos_seccion($seccion);
		foreach ($campos_seccion as $agrupado => $campos) {
			if ($this->hay_visibles_agrupado($seccion, $agrupado)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifica si existen campos visibles en una sección de formulario
	 * 
	 * @param string $seccion
	 * @return boolean
	 */
	function hay_imprimibles_seccion($seccion)
	{
		$campos_seccion = $this->get_campos_seccion($seccion);
		foreach ($campos_seccion as $agrupado => $campos) {
			if ($this->hay_imprimibles_agrupado($seccion, $agrupado)) {
				return true;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	/**
	 * Mantiene mapeos campo_id => campo_descr 
	 * para campos que almacenan internamente un id, pero se exhibe una 
	 * descripción en el formulario (generalmente se utilizan selectores)
	 * 
	 * Ejemplo: 'titulo_secundario' => 'titulo_secundario_descr'
	 */
	protected function inicializar_mapeos_id_descripcion()
	{
		// Mapeo campo_id => campo_descr 
		if (!isset($this->campos_id)) {
			$this->campos_id = array(
				'loc_per_lect' => 'loc_per_lect_descr',
				'loc_proc' => 'loc_proc_descr',
				'loc_pers_alleg' => 'loc_pers_alleg_descr',
				'colegio_secundario' => 'colegio_secundario_descr',
				'titulo_secundario' => 'titulo_secundario_descr',
			);
		}


		if ($this->origen == preinscripcion::origen_autogestion) {
			$datos = toba::consulta_php('co_datos_censales')->get_tablas_datos_config();
			$campos_form = [];
			foreach ($datos as $campo) {
				$campos_form[$campo['campo']] = $campo['etiqueta'];
			}


			//Voy agregar estos campos que no están en la config de datos censales.
			$campos_form['adeuda_materias'] = '¿Adeudás materias?';
			$campos_form['alu_otestsup_area'] = 'Áreas';
			$campos_form['alu_otestsup_carr'] = 'Otros Estudios - Propuesta';
			$campos_form['alu_otestsup_egre'] = 'Fecha de egreso';
			$campos_form['alu_otestsup_esta'] = 'Otros Estudios - Estado';
			$campos_form['alu_otestsup_niv'] = 'Nivel';
			$campos_form['alu_otestsup_razon_abandono'] = 'Razón de abandono';
			$campos_form['alu_otestsup_tipo'] = 'Tipo';
			$campos_form['alu_otestsup_uni'] = 'Universidad/Institución';
			$campos_form['anio_egreso_sec'] = 'Año de egreso del colegio secundario';
			$campos_form['apellido_madre'] = 'Apellido de la madre';
			$campos_form['apellido_padre'] = 'Apellido del padre';
			$campos_form['celular_numero'] = 'Número Celular';
			$campos_form['colegio_secundario'] = 'Colegio secundario';
			$campos_form['e_mail'] = 'Correo electrónico';
			$campos_form['empresa'] = 'Empresa';
			$campos_form['empresa_otra'] = 'Otra Empresa';
			$campos_form['ent_emisora_ci'] = 'Entidad emisora de la célula de identidad';
			$campos_form['es_mayor_25_sin_secundario'] = 'Es mayor de 25 años y no tiene estudios secundarios';
			$campos_form['facilidad_est_emp'] = 'Facilidad de estudio en la empresa';
			$campos_form['facilidad_est_inst'] = 'Facilidad de estudio en la institución';
			$campos_form['fecha_nacimiento'] = 'Fecha de nacimiento';
			$campos_form['fecha_respre_otorg'] = 'Fecha de otorgamiento de la residencia';
			$campos_form['fecha_respre_venc'] = 'Fecha de vencimiento de la residencia';
			$campos_form['insc_otra_inst'] = '¿Estós inscripto en otra institucióón?';
			$campos_form['insc_otra_inst_desc'] = '¿En cuál?';
			$campos_form['madre_trab_hace'] = 'Madre - En ese trabajo es';
			$campos_form['madre_trab_ocup'] = 'Madre - Esa ocupación es';
			$campos_form['madre_trab_otro'] = 'Si no trabaja y no busca trabajo';
			$campos_form['madre_trab_tarea'] = 'Madre - Describa la tarea que realizar';
			$campos_form['madre_vive'] = '¿Madre Vive?';
			$campos_form['nombres_madre'] = 'Nombres de la madre';
			$campos_form['nombres_padre'] = 'Nombres del padre';
			$campos_form['nro_ci'] = 'Numero de cedula de identidad';
			$campos_form['nro_documento'] = 'Numero de documento';
			$campos_form['nro_pasap'] = 'Número de pasaporte';
			$campos_form['numero_cuil'] = 'Número de C.U.I.L.';
			$campos_form['orientacion_recibida'] = 'Orientación vocacional recibida';
			$campos_form['otorgamiento_visa'] = 'Fecha de otorgamiento de la visa';
			$campos_form['padre_trab_hace'] = 'Padre - En ese trabajo es';
			$campos_form['padre_trab_ocup'] = 'Padre - Esa ocupación es';
			$campos_form['padre_trab_otro'] = 'Si no trabaja y no busca trabajo';
			$campos_form['padre_trab_tarea'] = 'Padre - Describa la tarea que realizar';
			$campos_form['padre_vive'] = '¿Padre Vive?';
			$campos_form['pais_emisor_pasap'] = 'País emisor del pasaporte';
			$campos_form['pertenece_pueblo_originario'] = '¿Pertenecés a algún pueblo originario?';
			$campos_form['pueblo_originario'] = '¿A cuál?';
			$campos_form['residencia'] = 'Tipo de residencia - Extranjeros';
			$campos_form['sexo_valor'] = 'Género';
			$campos_form['sit_laboral_madre'] = 'Situación laboral de la madre';
			$campos_form['sit_laboral_padre'] = 'Situación laboral del padre';
			$campos_form['tipo_visa'] = 'Tipo de visa';
			$campos_form['titulo_secundario'] = 'Titulo secundario';
			$campos_form['ult_est_cur_madre'] = 'Últimos estudios cursados por la madre';
			$campos_form['ult_est_cur_padre'] = 'Últimos estudios cursados por el padre';
			$campos_form['vencimiento_visa'] = 'Vencimiento de la visa';

			$campos_form['apellido_elegido'] = 'Apellido elegido';
			$campos_form['nombres_elegido'] = 'nombre elegido';
			$campos_form['pais_documento_desc'] = 'País emisor del documento';
			$campos_form['pais_documento'] = 'País emisor del documento';
			$campos_form['tipo_documento'] = 'Tipo de Documento';
			$campos_form['tipo_documento_desc'] = 'Tipo de Documento';
			$campos_form['nacionalidad'] = 'Nacionalidad';
			$campos_form['nacionalidad_desc'] = 'Nacionalidad';
			$campos_form['localidad_valor'] = 'Localidad';
			$campos_form['colegio_secundario_descr'] = 'Colegio Secundario';
			$campos_form['titulo_secundario_descr'] = 'Título Secundario';
			//Fede
			$campos_form['localidad_periodo_nombre'] = 'Localidad';
			$campos_form['tipo_res_per_lect'] = 'Tipo de residencia';
			$campos_form['localidad_procedencia_nombre'] = 'Localidad';
			$campos_form['tipo_residencia_descripcion'] = 'Tipo de residencia - Extranjeros';

			//Allegados
			$campos_form['apellido_pers_alleg'] = 'Apellidos';
			$campos_form['nombre_pers_alleg'] = 'Nombres';
			$campos_form['tipo_allegado'] = 'Tipo';
			$campos_form['calle_pers_alleg'] = 'Calle';
			$campos_form['nro_pers_alleg'] = 'Número';
			$campos_form['piso_pers_alleg'] = 'Piso';
			$campos_form['dpto_pers_alleg'] = 'Departamento';
			$campos_form['unidad_pers_alleg'] = 'Unidad';
			$campos_form['loc_pers_alleg'] = 'Localidad';
			//$campos_form['loc_pers_alleg_descr'] = 'Localidad bis';
			$campos_form['cp_pers_alleg'] = 'Código postal';
			$campos_form['barrio_alleg'] = 'Barrio';
			$campos_form['te_pers_alleg'] = 'Teléfono';

			//secundario
			$campos_form['titulo_sec'] = 'Título secundario';
			$campos_form['anio_egreso'] = 'Año de egresado';
			$campos_form['nombre_colegio'] = 'Nombre del la institución';
		} elseif ($this->origen == preinscripcion::origen_preinscripcion) {
			//Traer campos de la tabla sga_campos_form
			$datos = toba::consulta_php('co_preinscripciones')->get_campos_form($this->fuente);
			foreach ($datos as $dato) {
				$campos_form[$dato['columna']] = $dato['descripcion'];
			}
		}

		if (!isset($this->campos_descr)) {
		$this->campos_descr = $campos_form;
		  }

		// Mapeo inverso: campo_descr => campo_id
		/* if (!isset($this->campos_descr)) {
		  $this->campos_descr = [];
		  foreach ($this->campos_id as $id => $descr) {
		  $this->campos_descr[$descr] = $id;
		  }
		  } */
	}

	/**
	 * Obtiene el campo ID de un campo descriptivo
	 * Si no tiene, retorna null
	 */
	function get_campo_id($campo_descr)
	{
		$key = array_search($campo_descr, $this->campos_id);
		$salida = isset($key) ? $key : null;
		return $salida;
	}

	/**
	 * Obtiene el campo descriptivo a partir de un campo ID
	 * Si no tiene, retorna null
	 */
	function get_campo_descr($campo_id)
	{
		$salida = isset($this->campos_id[$campo_id]) ? $this->campos_id[$campo_id] : null;
		return $salida;
	}

	function get_etiqueta($campo)
	{
		$etiqueta = '';
		$campo_id = $this->get_campo_id($campo);
			if (!is_null($campo_id) && !empty($campo_id)) {	
				$campo = $campo_id;
			}
		if (isset($this->campos_descr[$campo])) {
			$etiqueta = $this->campos_descr[$campo];
		} else {
			//Etiquetas que no están en autogestión.            
			toba::logger()->crit("'$campo',");
			//siguiendo la logica de mensajes de 3w  si no tiene etiqueta muestro el campo para visulizar el error
			$etiqueta = $campo;
		}
		return $etiqueta;
	}

	/**
	 * Campos del formulario de Preinscripción
	 * 
	 * @return array
	 */
	function get_campos_preinscripcion()
	{
		if (empty($this->campos_preinscripcion)) {
			$this->campos_preinscripcion = util::consultas()->get_campos_formulario_preinscripcion();
		}
		return $this->campos_preinscripcion;
	}

	/**
	 * Obtiene una estructura de organización de secciones y campos
	 * 
	 * @return array
	 */
	function get_campos_secciones()
	{
		if (!isset($this->campos_secciones)) {
			$this->campos_secciones = [];
			$this->campos_secciones['datos_adicionales'] = [];
			$this->campos_secciones['datos_adicionales']['turno_preferido'] = array('turno_preferido');
			$this->campos_secciones['datos_adicionales']['como_conocio_institucion'] = array('como_conocio_institucion');
			$this->campos_secciones['datos_adicionales']['otras_inscripciones'] = array('insc_otra_inst', 'insc_otra_inst_descr');
			$this->campos_secciones['datos_adicionales']['motivo_eleccion_inst'] = array('mot_inst_econom', 'mot_inst_prestigio', 'mot_inst_difusion',
				'mot_inst_rec_estud', 'mot_inst_rec_amigos', 'mot_inst_sist_ing', 'mot_inst_ubicacion', 'mot_inst_otros');
			$this->campos_secciones['datos_adicionales']['motivo_eleccion_prop'] = array('mot_prop_vocacion', 'mot_prop_sug_fam',
				'mot_prop_rec_social', 'mot_prop_ins_lab', 'mot_prop_perf_prof', 'mot_prop_mejora_ec', 'mot_prop_util_soc', 'mot_prop_otros',);

			//------------------------------------------------------------------
			$this->campos_secciones['datos_principales'] = [];
			$this->campos_secciones['datos_principales']['datos_principales_pers'] = array(
				'apellido', 'nombres', 'apellido_elegido', 'nombres_elegido', 'pais_documento', 'tipo_documento', 'nro_documento',
				'nacionalidad', 'sexo', 'numero_cuil', 'e_mail', 'celular_caracteristica', 'celular_valor',
				'celular_numero');
			$this->campos_secciones['datos_principales']['datos_principales_nacim'] = array('fecha_nacimiento', 'loc_nacimiento');
			$this->campos_secciones['datos_principales']['datos_principales_ci'] = array('ent_emisora_ci', 'nro_ci');
			$this->campos_secciones['datos_principales']['datos_principales_pasap'] = array('pais_emisor_pasap', 'nro_pasap');
			$this->campos_secciones['datos_principales']['datos_principales_visa'] = array('tipo_visa', 'otorgamiento_visa', 'vencimiento_visa');
			$this->campos_secciones['datos_principales']['datos_principales_res'] = array('residencia', 'fecha_respre_otorg', 'fecha_respre_venc');
			$this->campos_secciones['datos_principales']['pueblos_originarios'] = array('pertenece_pueblo_originario', 'pueblo_originario', 'pueblo_originario_otro');
			//------------------------------------------------------------------
			$this->campos_secciones['datos_personales'] = [];
			$this->campos_secciones['datos_personales']['domicilio_per_clases'] = array('calle_per_lect',
				'numero_per_lect', 'piso_per_lect', 'dpto_per_lect', 'unidad_per_lect', 'loc_per_lect',
				'loc_per_lect_descr', 'cp_per_lect', 'barrio_per_lec', 'te_per_lect', 'tipo_res_per_lect', 'vive_actual_con');
			$this->campos_secciones['datos_personales']['domicilio_procedencia'] = array('calle_proc',
				'numero_proc', 'piso_proc', 'dpto_proc', 'unidad_proc', 'loc_proc', 'loc_proc_descr', 'cp_proc', 'barrio_proc', 'te_proc');
			$this->campos_secciones['datos_personales']['persona_allegada'] = array('apellido_pers_alleg', 'nombre_pers_alleg', 'tipo_allegado',
				'calle_pers_alleg', 'nro_pers_alleg', 'piso_pers_alleg', 'dpto_pers_alleg', 'unidad_pers_alleg',
				'loc_pers_alleg', 'loc_pers_alleg_descr', 'cp_pers_alleg', 'barrio_alleg', 'te_pers_alleg');
			//------------------------------------------------------------------ 
			$this->campos_secciones['financiamiento'] = [];
			$this->campos_secciones['financiamiento']['financ_fuente'] = array('alu_cos_est_ap_fam',
				'alu_cos_est_plsoc', 'alu_cos_est_trab', 'alu_cos_est_beca', 'alu_cos_est_otra', 'alu_cos_est_espec');
			$this->campos_secciones['financiamiento']['financ_beca_fuente'] = array('tiene_beca_univ',
				'tiene_beca_inter', 'tiene_beca_nacio', 'alu_beca_prov', 'alu_beca_muni', 'alu_beca_otra');
			$this->campos_secciones['financiamiento']['financ_beca_tipo'] = array('alu_beca_tipo_eco',
				'alu_beca_tipo_inv', 'alu_beca_tipo_ser');
			$this->campos_secciones['financiamiento']['financ_beca_tipo_econom'] = array('alu_beca_eco_tran',
				'alu_beca_eco_efec', 'alu_beca_eco_foto', 'alu_beca_eco_habi', 'alu_beca_eco_come');
			//------------------------------------------------------------------ 
			$this->campos_secciones['datos_laborales'] = [];
			$this->campos_secciones['datos_laborales']['datos_laborales_sit'] = array('existe_trab_alum',
				'alu_trab_hace', 'alu_trab_ocup', 'hora_sem_trab_alum', 'rel_trab_propuesta', 'alu_trab_tarea',
				'empresa', 'empresa_otra', 'facilidad_est_emp', 'facilidad_est_inst');
			$this->campos_secciones['datos_laborales']['datos_laborales_adic'] = array('alu_trab_desjub',
				'alu_trab_fami', 'remuneracion');
			//------------------------------------------------------------------ 
			$this->campos_secciones['datos_familiares'] = [];
			$this->campos_secciones['datos_familiares']['situacion_familiar'] = array('estado_civil',
				'alu_est_civil_uh', 'cant_hijos_alum', 'cant_fami_cargo');
			$this->campos_secciones['datos_familiares']['datos_padre'] = array('apellido_padre', 'nombres_padre',
				'situacion_padre', 'ult_est_cur_padre', 'sit_laboral_padre', 'padre_trab_hace', 'padre_trab_ocup',
				'padre_trab_tarea', 'padre_trab_otro');

			$this->campos_secciones['datos_familiares']['datos_madre'] = array('apellido_madre', 'nombres_madre',
				'situacion_madre', 'ult_est_cur_madre', 'sit_laboral_madre', 'madre_trab_hace', 'madre_trab_ocup',
				'madre_trab_tarea', 'madre_trab_otro');
			//------------------------------------------------------------------ 
			$this->campos_secciones['datos_estudios'] = [];
			$this->campos_secciones['datos_estudios']['orientacion_vocacional'] = array('orientacion_recibida');
			$this->campos_secciones['datos_estudios']['datos_estudios_sec'] = array('es_mayor_25_sin_secundario', 'anio_egreso_sec', 'adeuda_materias',
				'colegio_secundario', 'colegio_secundario_descr', 'titulo_secundario', 'titulo_secundario_descr');
			$this->campos_secciones['datos_estudios']['datos_estudios_sup'] = array('alu_otestsup_uni',
				'alu_otestsup_tipo', 'alu_otestsup_carr', 'alu_otestsup_niv', 'alu_otestsup_area',
				'alu_otestsup_esta', 'alu_otestsup_egre', 'alu_otestsup_razon_abandono', 'alu_otestsup_razon_abandono_otros');
			//------------------------------------------------------------------
			$this->campos_secciones['tecnologia'] = [];
			$this->campos_secciones['tecnologia']['dispon_pc'] = array('alu_tec_pc_casa',
				'alu_tec_pc_trab', 'alu_tec_pc_univ', 'alu_tec_pc_otro');
			$this->campos_secciones['tecnologia']['accede_internet_en'] = array('alu_tec_int_movil',
				'alu_tec_int_casa', 'alu_tec_int_trab', 'alu_tec_int_univ', 'alu_tec_int_otro');
			//------------------------------------------------------------------
			$this->campos_secciones['deportes'] = [];
			$this->campos_secciones['deportes']['deportes'] = array('practica_deportes');
			$this->campos_secciones['deportes']['listado_deportes_lugares'] = array('hace_dep_gim_priv',
				'hace_dep_univ', 'hace_dep_partic', 'hace_dep_otros');
			$this->campos_secciones['deportes']['listado_deportes'] = array('prac_dep_futbol',
				'prac_dep_basquet', 'prac_dep_voley', 'prac_dep_gimnasia', 'prac_dep_tenis',
				'prac_dep_natacion', 'prac_dep_handball', 'prac_dep_otros');
			//------------------------------------------------------------------
			$this->campos_secciones['idiomas'] = [];
			$this->campos_secciones['idiomas']['conoc_idiomas'] = array('alu_idioma_ingl',
				'alu_idioma_alem', 'alu_idioma_fran', 'alu_idioma_ital', 'alu_idioma_port',
				'alu_idioma_chino', 'alu_idioma_otro', 'alu_idioma_otro_nivel');
			//------------------------------------------------------------------
			$this->campos_secciones['datos_salud'] = [];
			$this->campos_secciones['datos_salud']['salud'] = array('obra_social_alu', 'es_celiaco');
			//------------------------------------------------------------------
			$this->campos_secciones['discapacidad'] = [];
			$this->campos_secciones['discapacidad']['cond_discapacidad'] = ['condicion_discapacidad', 'tiene_cud'];
			$this->campos_secciones['discapacidad']['listado_discapacidad_tipos'] = ['disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra'];
			$this->campos_secciones['discapacidad']['auditiva'] = ['aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos'];
			$this->campos_secciones['discapacidad']['visual'] = ['vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos'];
			$this->campos_secciones['discapacidad']['motora'] = ['mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos'];
			$this->campos_secciones['discapacidad']['psicosocial'] = ['psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos'];
			$this->campos_secciones['discapacidad']['otras'] = ['otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria'];
			$this->campos_secciones['discapacidad']['importante'] = ['informacion_importante'];
		}

		return $this->campos_secciones;
	}

	function get_campos_seccion_3w()
	{
		if (!isset($this->campos_secciones)) {
			$this->campos_secciones = [];
			$this->campos_secciones['datos_adicionales'] = [];
			$this->campos_secciones['datos_adicionales']['turno_preferido'] = array('turno_preferido');
			$this->campos_secciones['datos_adicionales']['como_conocio_institucion'] = array('como_conocio_institucion');
			$this->campos_secciones['datos_adicionales']['otras_inscripciones'] = array('insc_otra_institucion', 'insc_otra_institucion_desc');

			//ESTOS CAMBIARON
			$this->campos_secciones['datos_adicionales']['motivo_eleccion_inst'] = array('mot_economico', 'mot_prestigio', 'mot_difusion',
				'mot_recomendacion_estudiantes', 'mot_recomendacion_amigos', 'mot_sistema_ingreso', 'mot_ubicacion', 'mot_otros_i');
			//ESTOS TMB            
			$this->campos_secciones['datos_adicionales']['motivo_eleccion_prop'] = array('mot_vocacion', 'mot_sugerencia_familiar',
				'mot_reconocimiento_social', 'mot_insercion_laboral', 'mot_perfil_profesional', 'mot_mejora_economica', 'mot_util_sociedad', 'mot_otros_p');
			//------------------------------------------------------------------

			$this->campos_secciones['datos_personales'] = [];
			$this->campos_secciones['datos_personales']['domicilio_per_clases'] = array('periodo_lectivo_calle',
				'periodo_lectivo_numero', 'periodo_lectivo_piso', 'periodo_lectivo_departamento', 'periodo_lectivo_unidad', 'localidad_periodo_nombre',
				'periodo_lectivo_codigo_postal', 'periodo_lectivo_barrio', 'tipo_res_per_lect', 'vive_con');
			$this->campos_secciones['datos_personales']['domicilio_procedencia'] = array('procedencia_calle',
				'procedencia_numero', 'procedencia_piso', 'procedencia_departamento', 'procedencia_unidad', 'localidad_procedencia_nombre', 'procedencia_codigo_postal',
				'procedencia_barrio');
			$this->campos_secciones['datos_personales']['persona_allegada'] = array('apellido_pers_alleg', 'nombre_pers_alleg', 'tipo_allegado',
				'calle_pers_alleg', 'nro_pers_alleg', 'piso_pers_alleg', 'dpto_pers_alleg', 'unidad_pers_alleg',
				'loc_pers_alleg', 'cp_pers_alleg', 'barrio_alleg', 'te_pers_alleg');

			//------------------------------------------------------------------    
			//ESTOS CAMPOS DEBERIAN ESTAR EN mdp_personas o mdp_personas documentos
			$this->campos_secciones['datos_principales'] = [];
			$this->campos_secciones['datos_principales']['datos_principales_pers'] = array(
				'apellido', 'nombres', 'apellido_elegido', 'nombres_elegido', 'pais_documento_desc', 'tipo_documento_desc', 'nro_documento',
				'nacionalidad_desc', 'sexo_valor', 'numero_cuil', 'e_mail', 'celular_numero');
			$this->campos_secciones['datos_principales']['datos_principales_nacim'] = array('fecha_nacimiento', 'localidad_valor');
			$this->campos_secciones['datos_principales']['datos_principales_ci'] = array('ent_emisora_ci', 'nro_ci');
			$this->campos_secciones['datos_principales']['datos_principales_pasap'] = array('pais_emisor_pasap', 'nro_pasap');
			$this->campos_secciones['datos_principales']['datos_principales_visa'] = array('tipo_visa', 'otorgamiento_visa', 'vencimiento_visa');
			$this->campos_secciones['datos_principales']['datos_principales_res'] = array('tipo_residencia_descripcion', 'fecha_respre_otorg', 'fecha_respre_venc');
			$this->campos_secciones['datos_principales']['pueblos_originarios'] = array('pertenece_pueblo_originario', 'pueblo_originario', 'pueblo_originario_otro');

			//------------------------------------------------------------------
			//SALE DE DATOS CENSALES  mdp_datos_economicos 
			$this->campos_secciones['financiamiento'] = [];
			$this->campos_secciones['financiamiento']['financ_fuente'] = array('costeo_estudios_familiar',
				'costeo_estudios_plan_social', 'costeo_estudios_trabajo', 'costeo_estudios_beca', 'costeo_estudios_otro', 'costeo_estudios_descripcion');
			$this->campos_secciones['financiamiento']['financ_beca_fuente'] = array('beca_fuente_universidad', 'beca_fuente_nacional',
				'beca_fuente_internacional', 'beca_fuente_provincial', 'beca_fuente_municipal', 'beca_fuente_otra');
			$this->campos_secciones['financiamiento']['financ_beca_tipo'] = array('beca_tipo_economica',
				'beca_tipo_investigacion', 'beca_tipo_servicios');
			$this->campos_secciones['financiamiento']['financ_beca_tipo_econom'] = array('beca_economica_transporte',
				'beca_economica_efectivo', 'beca_economica_habitacional', 'beca_economica_fotocopias', 'beca_economica_comedor');

			//------------------------------------------------------------------
			$this->campos_secciones['datos_laborales'] = [];
			$this->campos_secciones['datos_laborales']['datos_laborales_sit'] = array('trabajo_existe',
				'trabajo_hace', 'trabajo_ocupacion', 'trabajo_hora_sem', 'trabajo_carrera', 'trabajo_tarea_descripcion');

//                'empresa', 'empresa_otra', 'sector', 'subsector', 'facilidad_est_emp', 'facilidad_est_inst'

			$this->campos_secciones['datos_laborales']['datos_laborales_adic'] = array('trabajo_con_descuentos_jubilatorios',
				'trabajo_familiar', 'trabajo_recibe_pago');

			//------------------------------------------------------------------ 
			//ESTOS CAMPOS LO SACO DE mdp_personas_allegados tienen otros nombres 
			//los campos y hay que buscar por parentezco
			$this->campos_secciones['datos_familiares'] = [];
			$this->campos_secciones['datos_familiares']['situacion_familiar'] = array('estado_civil',
				'unido_hecho', 'cantidad_hijos', 'cantidad_familia');
			$this->campos_secciones['datos_familiares']['datos_padre'] = array('apellido_padre', 'nombres_padre',
				'padre_vive', 'ult_est_cur_padre', 'sit_laboral_padre', 'padre_trab_hace', 'padre_trab_ocup',
				'padre_trab_tarea', 'padre_trab_otro');

			$this->campos_secciones['datos_familiares']['datos_madre'] = array('apellido_madre', 'nombres_madre',
				'madre_vive', 'ult_est_cur_madre', 'sit_laboral_madre', 'madre_trab_hace', 'madre_trab_ocup',
				'madre_trab_tarea', 'madre_trab_otro');

			//------------------------------------------------------------------ 
			$this->campos_secciones['datos_estudios'] = [];
			$this->campos_secciones['datos_estudios']['orientacion_vocacional'] = array('orientacion_recibida');
			$this->campos_secciones['datos_estudios']['datos_estudios_sec'] = array('anio_egreso_sec', 'adeuda_materias',
				'nombre_colegio', 'titulo_sec');

			//Estps valores verficar de donde lo saca
			$this->campos_secciones['datos_estudios']['datos_estudios_sup'] = array('alu_otestsup_uni',
				'alu_otestsup_carr', 'alu_otestsup_niv',
				'alu_otestsup_esta', 'alu_otestsup_egre', 'alu_otestsup_razon_abandono');

			//------------------------------------------------------------------
			$this->campos_secciones['tecnologia'] = [];
			$this->campos_secciones['tecnologia']['dispon_pc'] = array('tecnologia_pc_casa',
				'tecnologia_pc_trabajo', 'tecnologia_pc_universidad', 'tecnologia_pc_otro_lugar');
			$this->campos_secciones['tecnologia']['accede_internet_en'] = array('tecnologia_int_movil',
				'tecnologia_int_casa', 'tecnologia_int_trabajo', 'tecnologia_int_universidad', 'tecnologia_int_otro_lugar');
			//------------------------------------------------------------------
			$this->campos_secciones['deportes'] = [];
			$this->campos_secciones['deportes']['deportes'] = array('deportes');
			$this->campos_secciones['deportes']['listado_deportes_lugares'] = array('deportes_gimnasio',
				'deportes_universidad', 'deportes_particular', 'deportes_otro_lugar');
			$this->campos_secciones['deportes']['listado_deportes'] = array('deportes_futbol',
				'deportes_basquet', 'deportes_voley', 'deportes_gimnasia', 'deportes_tenis',
				'deportes_natacion', 'deportes_handball', 'deportes_otros');

			//------------------------------------------------------------------
			$this->campos_secciones['idiomas'] = [];
			$this->campos_secciones['idiomas']['conoc_idiomas'] = array('nivel_idioma_ingles',
				'nivel_idioma_aleman', 'nivel_idioma_frances', 'nivel_idioma_italiano', 'nivel_idioma_portugues',
				'nivel_idioma_chino', 'idioma_otro', 'nivel_idioma_otro');

			//------------------------------------------------------------------
			$this->campos_secciones['datos_salud'] = [];
			$this->campos_secciones['datos_salud']['salud'] = array('cobertura_salud', 'es_celiaco');
			//------------------------------------------------------------------

			$this->campos_secciones['discapacidad'] = [];
			$this->campos_secciones['discapacidad']['cond_discapacidad'] = ['condicion_discapacidad', 'tiene_cud'];
			$this->campos_secciones['discapacidad']['listado_discapacidad_tipos'] = ['disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra'];
			$this->campos_secciones['discapacidad']['auditiva'] = ['aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos'];
			$this->campos_secciones['discapacidad']['visual'] = ['vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos'];
			$this->campos_secciones['discapacidad']['motora'] = ['mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos'];
			$this->campos_secciones['discapacidad']['psicosocial'] = ['psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos'];
			$this->campos_secciones['discapacidad']['otras'] = ['otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria'];
			$this->campos_secciones['discapacidad']['importante'] = ['informacion_importante'];
		}
		return $this->campos_secciones;
	}

	/**
	 * Obtiene los campos de las secciones, sin la subdivisión de las subsecciones.
	 * 
	 * @return array
	 */
	function get_campos_secciones_aplanado()
	{
		$campos_secciones = $this->get_campos_secciones();
		$salida = [];
		foreach ($campos_secciones as $seccion_nombre => $subseccion) {
			$salida[$seccion_nombre] = [];
			foreach ($subseccion as $subseccion_campos) {
				$salida[$seccion_nombre] = array_merge($salida[$seccion_nombre], $subseccion_campos);
			}
		}
		return $salida;
	}

	function get_campos_seccion($seccion)
	{
		if ($this->origen == preinscripcion::origen_preinscripcion) {
			$datos = $this->get_campos_secciones();
		} else {
			$datos = $this->get_campos_seccion_3w();
		}

		if (isset($datos[$seccion])) {
			return $datos[$seccion];
		}
		return [];
	}

	function get_campos_configurables()
	{
		$campos_preinsc = $this->get_campos_preinscripcion();
		$campos_no_config = ['id_preinscripcion', 'usuario', 'clave',
			'codigo_validacion', 'email_valido', 'facebook', 'google',
			'fecha_registro', 'fecha_ult_modif', 'version_modificacion',
			'version_impresa', 'estado', 'apellido', 'nombres'];
		return array_diff($campos_preinsc, $campos_no_config);
	}

	function get_campos_no_ocultables_obligatorios()
	{
		$campos = ['pais_documento', 'pais_nacionalidad', 'tipo_documento',
			'apellido', 'nombres', 'fecha_nacimiento', 'nacionalidad',
			'nro_documento', 'sede', 'sexo', 'unidad_academica', 'fecha_respre_venc', 'e_mail', 'es_mayor_25_sin_secundario',
			'condicion_discapacidad', 'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
			'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
			'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
			'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
			'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
			'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
			'informacion_importante'];
		return $campos;
	}

}