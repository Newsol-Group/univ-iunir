<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class preinscripcion_nucleo
{
	// Origen de preinscripciones
    const origen_ambos = 'Ambas';    
	const origen_preinscripcion = 'Pr';
	const origen_autogestion = '3w';
	
	//para actualizar en la base
	const origen_preinscripcion_g3 = 'G3';
	const origen_preinscripcion_pre = 'PRE';
    
    const origen_ambos_desc = 'Todos (Preinscripción y Autogestión)';
	const origen_preinscripcion_desc = 'Preinscripción';
	const origen_autogestion_desc = 'Autogestión';
	
	// Estados de una preinscripción.
	const estado_pendiente = 'P';
	const estado_activado = 'A';
	const estado_preparado = 'C';
	const estado_inscripto = 'I';
	
	// Estados de una inscripción a propuesta.
	const insc_pendiente = 'P';
	const insc_procesada = 'I';
	const insc_copiada_g3 = 'C';
	const insc_nueva = 'N';
	const insc_solicitud_informacion = 'S';
	
	// Constantes para estados del reporte de preinscriptos
	const lista_para_procesar = 'LP';
	const con_informacion_solicitada = 'IS';
	const sin_finalizar_por_el_aspirante = 'SF';
	const procesada = 'P';
	const todas = 'T';
	const lista_para_procesar_desc = 'Listas para procesar';
	const con_informacion_solicitada_desc = 'Con información solicitada';
	const sin_finalizar_por_el_aspirante_desc = 'Sin finalizar por el aspirante';
	const procesada_desc = 'Procesada';
	const todas_desc = 'Todas';
		
	// Sexo.
	const sexo_masculino = 1;
	const sexo_femenino = 2;
	const sexo_no_binario = 3;
	
	// Tipos de visa.
	const visa_temporal = 'TEMPORAL';
	const visa_permanente = 'PERMANENTE';
	
	// Cupo de residencia.
	const residencia_cupo = 'C';
	const residencia_sin_cupo = 'R';
	
	// Niveles de estudio.
	const estudio_nivel_universitario = 1;
	const estudio_nivel_superior = 2;
	
	// Estados de estudio.
	const estudio_estado_termino = 1;
	const estudio_estado_abandono = 2;
	const estudio_estado_continua = 3;
	
	const estudio_estado_completos = 'C';
	const estudio_estado_incompletos = 'I';
	const estudio_estado_en_curso = 'E';
	
	// Niveles de estudio
	const est_niv_no_realizo_estudios = 1;
	const est_niv_primario_inc = 2;
	const est_niv_primario_comp = 3;
	const est_niv_sec_inc = 4;
	const est_niv_sec_comp = 5;
	const est_niv_sup_inc = 8;
	const est_niv_sup_comp = 9;
	const est_niv_univ_inc = 10;
	const est_niv_univ_comp = 11;
	const est_niv_postgrado = 12;
	const est_niv_desconoce = 13;
	
	// Turnos Preferidos de Cursada.
	const turno_maniana = 'M';
	const turno_tarde = 'T';
	const turno_noche = 'N';
    
    const turno_maniana_desc = 'Mañana';
    const turno_tarde_desc = 'Tarde';
    const turno_noche_desc = 'Noche';
    
    //---- Pueblos originarios -------------------------------------------------
	const pueblo_originario_OTRO = '27';
	
	// Trabajos.
	// Se refiere al trabajo que hace el aspirante, no confundir con el trabajo de los allegados padres
	const trabajo_hace_desconoce = 4;
	
	// Modalidades de cursada
	const mod_presencial = 1;
	const mod_distancia = 2;
	
	// Estado de requisitos digitales
	const est_req_dig_pendiente = 'P';
	const est_req_dig_aceptado = 'A';
	const est_req_dig_rechazado = 'R';

    //--------------------------------------------------------------------------
	//---- Reporte -------------------------------------------------------------
	//--------------------------------------------------------------------------
	const rep_ficha_aspirante	= 'Ficha del aspirante: ';
    const rep_ficha_nro         = 'Ficha de inscripción nro.: ';
    const rep_fecha_ult_modif	= 'Fecha de última modificación: ';
    const rep_nro_lu            = 'Nro. de Libreta Universitaria: ';
    const rep_nro_lu_leyenda	= '(a completar por la Dir. de Alumnos y Graduados)';
    const rep_propuestas_a_insc	= 'PROPUESTA(S) A INSCRIBIRSE:';
    const ubicacion             = 'Ubicación';

    //---- Turno de presentación ----------------------------------------------
    const rep_turno_mensaje     = 'Se deberá presentar la documentación solicitada en el siguiente turno:';

    //---- CBC ----------------------------------------------------------------
    const rep_titulo_cbc		= 'SITUACIÓN ACADÉMICA CORRESPONDIENTE AL C.B.C.';
    const cbc_codigo            = 'COD.';
    const cbc_asignaturas		= 'ASIGNATURA';
    const cbc_calificacion      = 'CALIFICACIÓN';
    const cbc_fecha             = 'FECHA';
    const cbc_leyenda_cuadro	= 'Consigná si la materia fue realizada en CBC, UBA XXI, CNBA, ILSE o CPEL';
    //-------------------------------------------------------------------------

    const rep_firma             = 'FIRMA';
    const rep_titulo_credencial	= 'CREDENCIAL PROVISORIA';
    const rep_apellido_y_nombre	= 'Apellido y nombres';
    const rep_identificacion	= 'Identificación';
    const rep_telefono          = 'TE';
    const rep_propuestas		= 'Propuesta(s)';
    
    const turno_ra              = 'Responsables Académicas';
    const turno_fecha           = 'Inicio';
    const turno_hora            = 'Hora';
    const turno_operador        = 'Ventanilla';
    
    //---- Secciones -----------------------------------------------------------
    
    const seccion_datos_adicionales = 'Datos adicionales';
    const seccion_datos_principales = 'Datos principales';
    const seccion_datos_personales = 'Datos personales';
    const seccion_financiamiento = 'Financiamiento de estudios';
    const seccion_datos_laborales = 'Situación laboral';
    const seccion_datos_familiares = 'Situación familiar';
    const seccion_datos_estudios = 'Estudios';
    const seccion_tecnologia = 'Tecnología';
    const seccion_deportes = 'Deportes';
    const seccion_idiomas = 'Idiomas';
    const seccion_datos_salud = 'Datos de salud';
    const seccion_datos_discapacidad = 'Discapacidad';

    //---- Agrupados -----------------------------------------------------------
    
    const agrupado_turno_preferido = 'Turno preferido';
    const agrupado_como_conocio_institucion = '¿Cómo conociste la Institución?';
    const agrupado_otras_inscripciones = 'Otras inscripciones';
    const agrupado_motivo_eleccion_inst = 'Motivos por los que elegiste esta Institución educativa';
    const agrupado_motivo_eleccion_prop = '¿Cuál de los siguientes motivos tuvo mayor peso en tu elección de propuesta?';
    const agrupado_datos_principales_pers = 'Datos personales';
    const agrupado_datos_principales_nacim = 'Datos de nacimiento';
    const agrupado_datos_principales_ci = 'Cédula de Identidad Argentina';
    const agrupado_datos_principales_pasap = 'Pasaporte';
    const agrupado_datos_principales_visa = 'Visa';
    const agrupado_datos_principales_res = 'Residencia';
    const agrupado_pueblos_originarios = 'Pueblos originarios';
    const agrupado_domicilio_per_clases = 'Domicilio durante el período de clases';
    const agrupado_domicilio_procedencia = 'Domicilio de procedencia (donde vivís fuera del período de clases)';
    const agrupado_persona_allegada = 'Datos de una persona allegada';
    const agrupado_financ_fuente = '¿Cómo costeás tus estudios?';
    const agrupado_financ_beca_fuente = 'Fuente de la beca';
    const agrupado_financ_beca_tipo = 'Tipo de beca';
    const agrupado_financ_beca_tipo_econom = 'Tipo de ayuda económica';
    const agrupado_datos_laborales_sit = 'Situación laboral';
    const agrupado_datos_laborales_adic = 'Datos adicionales';
    const agrupado_situacion_familiar = 'Situación familiar';
    const agrupado_datos_padre = 'Datos de tu padre';
    const agrupado_datos_madre = 'Datos de tu madre';
    const agrupado_orientacion_vocacional = 'Orientacion Vocacional';
    const agrupado_datos_estudios_sec = 'Nivel secundario / Polimodal';
    const agrupado_datos_estudios_sup = 'Otros estudios superiores';
    const agrupado_dispon_pc = 'Disponés de PC';
    const agrupado_accede_internet_en = 'Accedés a Internet';
    const agrupado_deportes = 'Deportes';
    const agrupado_listado_deportes_lugares = '¿Dónde prácticas?';
    const agrupado_listado_deportes = '¿Qué prácticas?';
    const agrupado_conoc_idiomas = 'Conocimiento de idiomas';
    const agrupado_salud = 'Datos de salud';
    const agrupado_cond_discapacidad = 'Condición de Discapacidad';
    const agrupado_listado_discapacidad_tipos = 'Discapacidad';
    const agrupado_auditiva = 'Auditiva';
    const agrupado_visual = 'Visual';
    const agrupado_motora = 'Motora';
    const agrupado_psicosocial = 'Condición Psicosocial';
    const agrupado_otras = 'Otras';
    const agrupado_importante = 'Alguna otra información que consideres importante comunicar';

    
    //Parametros impresion
    const modo_comp_RA = 'ra';
	const modo_comp_PROPUESTA = 'propuesta';
	const modo_comp_UNICO = 'unico';
    
	static protected $fuente = null;

	static function set_fuente($fuente)
	{
		self::$fuente = $fuente;
	}

    static function get_constante($constante)
    {
        if (defined("self::$constante")) {
            return constant("self::$constante");
        }
    }
    
	//-----------------------------------------------------------------------------------
	//---- Fuentes de datos de preinscripción.
	//-----------------------------------------------------------------------------------

	static function actualizar_fuentes_datos()
	{
		$fuentes_preinscripcion = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();

		$proyecto = toba::proyecto()->get_id();
		$fuentes_instancia = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_proyecto($proyecto);
		$fuentes_instancia = rs_convertir_asociativo($fuentes_instancia, array('fuente_datos'), 'fuente_datos');

		foreach ($fuentes_preinscripcion as $fuente) {
			if (!isset($fuentes_instancia[$fuente['fuente_de_datos']])) {
				guarani::act('act_preinscripciones')->insertar_fuente_instancia($proyecto, $fuente['fuente_de_datos'], $fuente['nombre']);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Origen de preinscripciones
	//-----------------------------------------------------------------------------------
	
	static function get_origenes_preinscripcion()
	{
		return array(
			array('valor' => self::origen_preinscripcion, 'descr' => 'Módulo de Preinscripción (nuevos alumnos)'),
			array('valor' => self::origen_autogestion, 'descr' => 'Módulo de Autogestión (alumnos existentes)'),
		);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Importación y actualización de una persona proveniente de preinscripción.
	//-----------------------------------------------------------------------------------

	static function procesar_persona($datos)
	{
		$persona = toba::consulta_php('co_personas')->validar_documento($datos['tipo_documento'], $datos['nro_documento'], $datos['pais_documento']);
		if (empty($persona)) {
			// Se deben IMPORTAR todos los datos de la persona
			$persona = static::importar_persona($datos);
		} else {
			// Se deben ACTUALIZAR los datos de la persona
			static::actualizar_persona($datos, $persona);
		}
		return $persona;
	}

	static function importar_persona($datos)
	{
		$persona = static::insertar_datos_persona($datos);
		static::insertar_documento_principal($datos, $persona);
		static::actualizar_persona($datos, $persona);
		static::actualizar_usuario_contrasenia($persona);

		// Se retorna el id de persona insertada.
		return $persona;
	}

	static function actualizar_persona($datos, $persona)
	{
		static::actualizar_apellido_nombres_elegido($datos, $persona);
		static::actualizar_identidad_genero($datos, $persona);
		static::actualizar_pueblos_originarios($datos, $persona);
		static::actualizar_documentos($datos, $persona);
		static::actualizar_datos_persona_extranjera($datos, $persona);
		static::actualizar_contactos($datos, $persona);
		static::actualizar_estudios($datos, $persona);		
		static::actualizar_datos_censales($datos, $persona);
		static::actualizar_antecedentes_docentes($datos, $persona);
		static::actualizar_antecedentes_profesionales($datos, $persona);
		static::actualizar_requisitos_digitales($datos, $persona);
	}

	//--------------------------------------------------------------------------------------

	static function insertar_datos_persona($datos)
	{
		// Inserción en mdp_personas
		//   * Conversión dato: sexo
		if ($datos['sexo'] == self::sexo_masculino) {
			$sexo = persona::sexo_masculino;
		}	
		if ($datos['sexo'] == self::sexo_femenino) { 
			$sexo = persona::sexo_femenino; 
		}	
		if ($datos['sexo'] == self::sexo_no_binario) {
			$sexo = persona::sexo_no_binario;
		}	
		$persona = guarani::act('act_personas')->insertar_persona($datos['apellido'], $datos['nombres'], $sexo, 
				$datos['fecha_nacimiento'], $datos['loc_nacimiento'], $datos['nacionalidad'], $datos['pais_nacionalidad']);
		return $persona;
	}

	static function insertar_documento_principal($datos, $persona)
	{
		// Inserción en mdp_personas_documentos
		//Chequeo la no sea nulo o vacio validado_con_renaper
		$datos['validado_con_renaper'] = $datos['validado_con_renaper'] ?? persona_nucleo::no_validado_con_renaper;
		//   - Documento principal:		
		guarani::act('act_personas')->insertar_documento($persona, $datos['pais_documento'], $datos['tipo_documento'], $datos['nro_documento'], $datos['validado_con_renaper']);
	}

	static function actualizar_usuario_contrasenia($persona)
	{
		$par_generar_clave = guarani::parametros()->get_valor('mat_forma_generar_usuario_clave');
		if ($par_generar_clave == 'A') {
			$filtro = 'mdp_personas.persona = ' . $persona;
			$persona_datos = current(toba::consulta_php('co_personas')->get_listado($filtro));
			$pais_doc = $persona_datos['pais_doc'];
			$tipo_doc = $persona_datos['tipo_doc'];
			$nro_doc = $persona_datos['nro_documento'];
			$user = usuario::get_usuario_default($pais_doc, $tipo_doc, $nro_doc);
			$pass = 'null';
			guarani::act('act_personas')->actualizar_user_pass($persona, $user, $pass);
		}
	}

	static function actualizar_apellido_nombres_elegido($datos, $persona)
	{
		guarani::act('act_personas')->actualizar_apellido_nombres_elegido($persona, $datos['apellido_elegido'], $datos['nombres_elegido']);
	}

	static function actualizar_identidad_genero($datos, $persona)
	{
		guarani::act('act_personas')->actualizar_identidad_genero($persona, $datos['identidad_genero'], $datos['identidad_genero_otro']);
	}

	static function actualizar_pueblos_originarios($datos, $persona)
	{
		guarani::act('act_personas')->actualizar_pueblos_originarios($persona, $datos['pertenece_pueblo_originario'], 
			$datos['pueblo_originario'], $datos['pueblo_originario_otro']);
	}

	static function actualizar_documentos($datos, $persona)
	{
		// Inserción en mdp_personas_documentos
		//   - CUIL:
		if (!is_null($datos['numero_cuil']) && !toba::consulta_php('co_preinscripciones')->existe_documento_persona($persona, persona::doc_CUIL, $datos['pais_documento'])) {
			guarani::act('act_personas')->insertar_documento($persona, $datos['pais_documento'], persona::doc_CUIL, $datos['numero_cuil']);
		}
		//   - Cédula de identidad:
		if (!is_null($datos['nro_ci']) && !toba::consulta_php('co_preinscripciones')->existe_documento_persona($persona, persona::doc_cedula, $datos['pais_documento'])) {
			guarani::act('act_personas')->insertar_documento($persona, $datos['pais_documento'], persona::doc_cedula, $datos['nro_ci']);
		}
		//   - Pasaporte:
		if (!is_null($datos['nro_pasap']) && !is_null($datos['pais_emisor_pasap']) && !toba::consulta_php('co_preinscripciones')->existe_documento_persona($persona, persona::doc_pasaporte, $datos['pais_emisor_pasap'])) {
			guarani::act('act_personas')->insertar_documento($persona, $datos['pais_emisor_pasap'], persona::doc_pasaporte, $datos['nro_pasap']);
		}
	}

	static function actualizar_datos_persona_extranjera($datos, $persona)
	{
		// Inserción en mdp_personas_extranjeros
		if ($datos['nacionalidad'] == persona::nac_extranjero && !toba::consulta_php('co_preinscripciones')->existe_registro_persona_extranjera($persona)) {
			//   * Conversión dato: tipo_visa
			$tipo_visa = null;
			if (!is_null($datos['tipo_visa'])) {
				$tipo_visa = ($datos['tipo_visa'] == preinscripcion::visa_temporal) ? persona::visa_temporal : persona::visa_permanente;
			}
			//   * Conversión dato: residencia_cupo
			$residencia_cupo = null;
			if (!is_null($datos['tipo_residencia'])) {
				$residencia_cupo = ($datos['tipo_residencia'] == preinscripcion::residencia_cupo) ? comunes::si : comunes::no;
			}
			guarani::act('act_personas')->insertar_extranjero($persona, $tipo_visa, $datos['otorgamiento_visa'], $datos['vencimiento_visa'], $datos['residencia'], $residencia_cupo, $datos['fecha_respre_otorg'], $datos['fecha_respre_venc']);
		}
	}

	static function actualizar_contactos($datos, $persona)
	{
		// Inserción en mdp_personas_contactos
		//   * Celular:
		if (!is_null($datos['celular_numero'])) {
			$datos_cel = explode('-', $datos['celular_numero']);
			$telefono_numero = empty($datos_cel[3]) ? null : trim($datos_cel[3]);
			if (!empty($telefono_numero)) {
				$telefono_codigo_area = trim($datos_cel[1]);
				$parametros = array(
					array('campo' => 'telefono_codigo_area', 'valor' => $telefono_codigo_area),
					array('campo' => 'telefono_numero', 'valor' => $telefono_numero)
				);

				if (toba::consulta_php('co_preinscripciones')->existe_registro_contacto_persona($persona, persona::tel_celular)) {
					guarani::act('act_personas')->actualizar_contacto($persona, persona::tel_celular, $parametros);
				} else {
					guarani::act('act_personas')->insertar_contacto($persona, persona::tel_celular, $parametros);
				}
			}
		}
		//   * Mail:
		if (!is_null($datos['e_mail'])) {
			$parametros = array(array('campo' => 'email', 'valor' => $datos['e_mail']));
			if (toba::consulta_php('co_preinscripciones')->existe_registro_contacto_persona($persona, persona::mail_primario)) {
				guarani::act('act_personas')->actualizar_contacto($persona, persona::mail_primario, $parametros);
			} else {
				guarani::act('act_personas')->insertar_contacto($persona, persona::mail_primario, $parametros);
			}
		}
	}

	static function actualizar_estudios($datos, $persona)
	{
		// Inserción en mdp_datos_estudios
		//   * Colegio Secundario:
		if (!is_null($datos['colegio_secundario']) || !is_null($datos['titulo_secundario']) || !is_null($datos['anio_egreso_sec'])) {
			$parametros = array(
				array('campo' => 'colegio', 'valor' => $datos['colegio_secundario']),
				array('campo' => 'titulo', 'valor' => $datos['titulo_secundario']),
				array('campo' => 'anio_egreso', 'valor' => $datos['anio_egreso_sec']),
				array('campo' => 'orientacion_recibida', 'valor' => $datos['orientacion_recibida']),
				array('campo' => 'adeuda_materias_secundario', 'valor' => $datos['adeuda_materias'])
			);
			if (toba::consulta_php('co_preinscripciones')->existe_registro_estudio_institucion($persona, persona::nivel_secundario)) {
				guarani::act('act_personas')->actualizar_datos_estudios($persona, $parametros, persona::nivel_secundario);
			} else {
				guarani::act('act_personas')->insertar_datos_estudios($persona, persona::nivel_secundario, $parametros);
			}
		}
		//   * Otros estudios superiores
		if (!is_null($datos['alu_otestsup_uni'])) {
			$parametros = array(array('campo' => 'institucion_otra', 'valor' => $datos['alu_otestsup_uni']));
			//   * Conversión dato: nivel_estudio
			$nivel = persona::nivel_desconoce;
			if (!is_null($datos['alu_otestsup_niv'])) {
				$nivel = ($datos['alu_otestsup_niv'] == preinscripcion::estudio_nivel_universitario) ? persona::nivel_universitario : persona::nivel_superior;
			}
			if (!is_null($datos['alu_otestsup_egre'])) {
				$parametros[] = array('campo' => 'anio_egreso', 'valor' => $datos['alu_otestsup_egre']);
			}
			if (!is_null($datos['alu_otestsup_esta'])) {
				switch ($datos['alu_otestsup_esta']) {
					case preinscripcion::estudio_estado_termino:
						$estado = persona::estudio_estado_termino;
						break;
					case preinscripcion::estudio_estado_abandono:
						$estado = persona::estudio_estado_abandono;
						break;
					case preinscripcion::estudio_estado_continua:
						$estado = persona::estudio_estado_continua;
						break;
				}
				$parametros[] = array('campo' => 'estado', 'valor' => $estado);
			}
			if (!is_null($datos['alu_otestsup_carr'])) {
				$parametros[] = array('campo' => 'titulo_otro', 'valor' => $datos['alu_otestsup_carr']);
			}
			if (!is_null($datos['alu_otestsup_razon_abandono'])) {
				$parametros[] = array('campo' => 'razon_abandono', 'valor' => $datos['alu_otestsup_razon_abandono']);
			}
			if (!is_null($datos['alu_otestsup_razon_abandono_otros'])) {
				$parametros[] = array('campo' => 'razon_abandono_otros', 'valor' => $datos['alu_otestsup_razon_abandono_otros']);
			}
			if (toba::consulta_php('co_preinscripciones')->existe_registro_estudio_institucion($persona, $nivel, $datos['alu_otestsup_uni'])) {
				guarani::act('act_personas')->actualizar_datos_estudios($persona, $parametros, $nivel, $datos['alu_otestsup_uni']);
			} else {
				guarani::act('act_personas')->insertar_datos_estudios($persona, $nivel, $parametros);
			}
		}

		/**
		 * Registros de formación académica solicitada a preinscriptos a carreras de postgrado
		 */
		$fuente = (!empty(self::$fuente)) ? self::$fuente : null;
		$antecedentes = toba::consulta_php('co_preinscripciones')->get_formacion_academica($datos['id_preinscripcion'], $fuente);
		if (!empty($antecedentes)) {
			foreach ($antecedentes as $antecedente) {
				$parametros = array(
					array('campo' => 'titulo', 'valor' => $antecedente['titulo_superior']),
					array('campo' => 'titulo_otro', 'valor' => $antecedente['titulo_sup_prein']),
					array('campo' => 'institucion', 'valor' => $antecedente['institucion']),
					array('campo' => 'institucion_otra', 'valor' => $antecedente['institucion_prein']),
					array('campo' => 'fecha_ingreso', 'valor' => $antecedente['fecha_ingreso']),
					array('campo' => 'fecha_egreso', 'valor' => $antecedente['fecha_egreso']),
					array('campo' => 'titulo_tesis', 'valor' => $antecedente['titulo_tesis']),
					array('campo' => 'director_tesis', 'valor' => $antecedente['director_tesis']),
					array('campo' => 'estado', 'valor' => $antecedente['estado'])
				);

				if (!toba::consulta_php('co_preinscripciones')->existe_registro_formacion_academica($persona, $antecedente['nivel_estudio'], $parametros)) {
					guarani::act('act_personas')->insertar_datos_estudios($persona, $antecedente['nivel_estudio'], $parametros);
				}
			}
		}
	}

	/**
	 * Conversión del estado de estudio para el módulo de Preinscripción al estado de estudio para G3. 
	 *
	 * @param integer $estado_preinscripcion
	 * @return char
	 */
	static function convertir_estado_estudio($estado_preinscripcion)
	{
		if (!is_null($estado_preinscripcion)) {
			switch ($estado_preinscripcion) {
				case preinscripcion::estudio_estado_completos:
					$estado = persona::estudio_estado_termino;
					break;
				case preinscripcion::estudio_estado_incompletos:
					$estado = persona::estudio_estado_abandono;
					break;
				case preinscripcion::estudio_estado_en_curso:
					$estado = persona::estudio_estado_continua;
					break;
			}
			return $estado;
		}
		return $estado_preinscripcion;
	}

	static function actualizar_allegados($datos, $dato_censal)
	{
		// Inserción en mdp_personas_allegados
		//   * Padre:
		if (!is_null($datos['apellido_padre'])) {
			$padre_trabajo_hace = ($datos['padre_trab_hace'] == preinscripcion::trabajo_hace_desconoce) ? null : $datos['padre_trab_hace'];
			$parametros = array(
				array('campo' => 'apellido', 'valor' => $datos['apellido_padre']),
				array('campo' => 'nombres', 'valor' => $datos['nombres_padre']),
				array('campo' => 'estudio_alcanzado', 'valor' => $datos['ult_est_cur_padre']),
				array('campo' => 'trabajo_hace', 'valor' => $padre_trabajo_hace),
				array('campo' => 'trabajo_ocupacion', 'valor' => $datos['padre_trab_ocup']),
				array('campo' => 'trabajo_tarea', 'valor' => $datos['padre_trab_tarea']),
				array('campo' => 'trabajo_existe', 'valor' => $datos['sit_laboral_padre']),
				array('campo' => 'trabajo_otro', 'valor' => $datos['padre_trab_otro'])
			);
			if (toba::consulta_php('co_preinscripciones')->existe_registro_allegado($dato_censal, persona::allegado_padre)) {
				guarani::act('act_personas')->actualizar_persona_allegada($dato_censal, persona::allegado_padre, $parametros);
			} else {
				guarani::act('act_personas')->insertar_persona_allegada($dato_censal, persona::allegado_padre, $parametros);
			}
		}
		//   * Madre:
		if (!is_null($datos['apellido_madre'])) {
			$madre_trabajo_hace = ($datos['madre_trab_hace'] == preinscripcion::trabajo_hace_desconoce) ? null : $datos['madre_trab_hace'];
			$parametros = array(
				array('campo' => 'apellido', 'valor' => $datos['apellido_madre']),
				array('campo' => 'nombres', 'valor' => $datos['nombres_madre']),
				array('campo' => 'estudio_alcanzado', 'valor' => $datos['ult_est_cur_madre']),
				array('campo' => 'trabajo_hace', 'valor' => $madre_trabajo_hace),
				array('campo' => 'trabajo_ocupacion', 'valor' => $datos['madre_trab_ocup']),
				array('campo' => 'trabajo_tarea', 'valor' => $datos['madre_trab_tarea']),
				array('campo' => 'trabajo_existe', 'valor' => $datos['sit_laboral_madre']),
				array('campo' => 'trabajo_otro', 'valor' => $datos['madre_trab_otro'])
			);
			if (toba::consulta_php('co_preinscripciones')->existe_registro_allegado($dato_censal, persona::allegado_madre)) {
				guarani::act('act_personas')->actualizar_persona_allegada($dato_censal, persona::allegado_madre, $parametros);
			} else {
				guarani::act('act_personas')->insertar_persona_allegada($dato_censal, persona::allegado_madre, $parametros);
			}
		}
		//   * Otro:
		if (!is_null($datos['nombre_pers_alleg'])) {
			$parametros = array(
				array('campo' => 'apellido', 'valor' => $datos['apellido_pers_alleg']),
				array('campo' => 'nombres', 'valor' => $datos['nombre_pers_alleg']),
				array('campo' => 'calle', 'valor' => $datos['calle_pers_alleg']),
				array('campo' => 'numero', 'valor' => $datos['nro_pers_alleg']),
				array('campo' => 'piso', 'valor' => $datos['piso_pers_alleg']),
				array('campo' => 'departamento', 'valor' => $datos['dpto_pers_alleg']),
				array('campo' => 'unidad', 'valor' => $datos['unidad_pers_alleg']),
				array('campo' => 'localidad', 'valor' => $datos['loc_pers_alleg']),
				array('campo' => 'barrio', 'valor' => $datos['barrio_alleg']),
				array('campo' => 'codigo_postal', 'valor' => $datos['cp_pers_alleg']),
				array('campo' => 'telefono', 'valor' => $datos['te_pers_alleg'])
			);
			$tipo_allegado = (empty($datos['tipo_allegado'])) ? persona::allegado_otro : $datos['tipo_allegado'];
			if (toba::consulta_php('co_preinscripciones')->existe_registro_allegado($dato_censal, $tipo_allegado)) {
				guarani::act('act_personas')->actualizar_persona_allegada($dato_censal, $tipo_allegado, $parametros);
			} else {
				guarani::act('act_personas')->insertar_persona_allegada($dato_censal, $tipo_allegado, $parametros);
			}
		}
	}

	static function actualizar_datos_censales($datos, $persona)
	{
		$id_dato_censal = toba::consulta_php('co_datos_censales')->get_id_dato_censal($persona);

		if (empty($id_dato_censal['dato_censal'])) {
			$dato_censal = guarani::act('act_personas')->insertar_dato_censal($persona);
		} else {
			$dato_censal = $id_dato_censal['dato_censal'];
			guarani::act('act_personas')->actualizar_dato_censal($dato_censal);
		}

		static::actualizar_allegados($datos, $dato_censal);
		static::actualizar_datos_economicos($datos, $dato_censal);
		static::actualizar_datos_actividades($datos, $dato_censal);
		static::actualizar_datos_personales($datos, $dato_censal);
		static::actualizar_eleccion_propuesta($datos, $dato_censal);
		static::actualizar_eleccion_institucion($datos, $dato_censal);
		static::actualizar_discapacidad($datos, $dato_censal);
	}

	static function actualizar_datos_economicos($datos, $dato_censal)
	{
		$parametros = array(
			array('campo' => 'costeo_estudios_familiar', 'valor' => $datos['alu_cos_est_ap_fam']),
			array('campo' => 'costeo_estudios_plan_social', 'valor' => $datos['alu_cos_est_plsoc']),
			array('campo' => 'costeo_estudios_trabajo', 'valor' => $datos['alu_cos_est_trab']),
			array('campo' => 'costeo_estudios_beca', 'valor' => $datos['alu_cos_est_beca']),
			array('campo' => 'costeo_estudios_otro', 'valor' => $datos['alu_cos_est_otra']),
			array('campo' => 'costeo_estudios_descripcion', 'valor' => $datos['alu_cos_est_espec']),
			array('campo' => 'beca', 'valor' => $datos['tiene_beca']),
			array('campo' => 'beca_fuente_provincial', 'valor' => $datos['alu_beca_prov']),
			array('campo' => 'beca_fuente_municipal', 'valor' => $datos['alu_beca_muni']),
			array('campo' => 'beca_fuente_otra', 'valor' => $datos['alu_beca_otra']),
			array('campo' => 'beca_fuente_universidad', 'valor' => $datos['tiene_beca_univ']),
			array('campo' => 'beca_fuente_nacional', 'valor' => $datos['tiene_beca_nacio']),
			array('campo' => 'beca_fuente_internacional', 'valor' => $datos['tiene_beca_inter']),
			array('campo' => 'beca_tipo_economica', 'valor' => $datos['alu_beca_tipo_eco']),
			array('campo' => 'beca_tipo_servicios', 'valor' => $datos['alu_beca_tipo_ser']),
			array('campo' => 'beca_tipo_investigacion', 'valor' => $datos['alu_beca_tipo_inv']),
			array('campo' => 'beca_economica_transporte', 'valor' => $datos['alu_beca_eco_tran']),
			array('campo' => 'beca_economica_efectivo', 'valor' => $datos['alu_beca_eco_efec']),
			array('campo' => 'beca_economica_fotocopias', 'valor' => $datos['alu_beca_eco_foto']),
			array('campo' => 'beca_economica_habitacional', 'valor' => $datos['alu_beca_eco_habi']),
			array('campo' => 'beca_economica_comedor', 'valor' => $datos['alu_beca_eco_come']),
			array('campo' => 'trabajo_existe', 'valor' => $datos['existe_trab_alum']),
			array('campo' => 'trabajo_hace', 'valor' => $datos['alu_trab_hace']),
			array('campo' => 'trabajo_familiar', 'valor' => $datos['alu_trab_fami']),
			array('campo' => 'trabajo_con_descuentos_jubilatorios', 'valor' => $datos['alu_trab_desjub']),
			array('campo' => 'trabajo_ocupacion', 'valor' => $datos['alu_trab_ocup']),
			array('campo' => 'trabajo_hora_sem', 'valor' => $datos['hora_sem_trab_alum']),
			array('campo' => 'trabajo_carrera', 'valor' => $datos['rel_trab_propuesta']),
			array('campo' => 'trabajo_tarea_descripcion', 'valor' => $datos['alu_trab_tarea']),
			array('campo' => 'trabajo_recibe_pago', 'valor' => $datos['remuneracion'])
		);

		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_datos_economicos')) {
			guarani::act('act_personas')->actualizar_datos_economicos($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_datos_economicos($dato_censal, $parametros);
		}
	}

	static function actualizar_datos_actividades($datos, $dato_censal)
	{
		$parametros = array(
			array('campo' => 'tecnologia_pc_casa', 'valor' => $datos['alu_tec_pc_casa']),
			array('campo' => 'tecnologia_pc_trabajo', 'valor' => $datos['alu_tec_pc_trab']),
			array('campo' => 'tecnologia_pc_universidad', 'valor' => $datos['alu_tec_pc_univ']),
			array('campo' => 'tecnologia_pc_otro_lugar', 'valor' => $datos['alu_tec_pc_otro']),
			array('campo' => 'tecnologia_int_casa', 'valor' => $datos['alu_tec_int_casa']),
			array('campo' => 'tecnologia_int_trabajo', 'valor' => $datos['alu_tec_int_trab']),
			array('campo' => 'tecnologia_int_universidad', 'valor' => $datos['alu_tec_int_univ']),
			array('campo' => 'tecnologia_int_otro_lugar', 'valor' => $datos['alu_tec_int_otro']),
			array('campo' => 'tecnologia_int_movil', 'valor' => $datos['alu_tec_int_movil']),
			array('campo' => 'deportes', 'valor' => $datos['practica_deportes']),
			array('campo' => 'deportes_universidad', 'valor' => $datos['hace_dep_univ']),
			array('campo' => 'deportes_gimnasio', 'valor' => $datos['hace_dep_gim_priv']),
			array('campo' => 'deportes_particular', 'valor' => $datos['hace_dep_partic']),
			array('campo' => 'deportes_otro_lugar', 'valor' => $datos['hace_dep_otros']),
			array('campo' => 'deportes_basquet', 'valor' => $datos['prac_dep_basquet']),
			array('campo' => 'deportes_futbol', 'valor' => $datos['prac_dep_futbol']),
			array('campo' => 'deportes_gimnasia', 'valor' => $datos['prac_dep_gimnasia']),
			array('campo' => 'deportes_handball', 'valor' => $datos['prac_dep_handball']),
			array('campo' => 'deportes_natacion', 'valor' => $datos['prac_dep_natacion']),
			array('campo' => 'deportes_tenis', 'valor' => $datos['prac_dep_tenis']),
			array('campo' => 'deportes_voley', 'valor' => $datos['prac_dep_voley']),
			array('campo' => 'deportes_otros', 'valor' => $datos['prac_dep_otros']),
			array('campo' => 'nivel_idioma_ingles', 'valor' => $datos['alu_idioma_ingl']),
			array('campo' => 'nivel_idioma_aleman', 'valor' => $datos['alu_idioma_alem']),
			array('campo' => 'nivel_idioma_frances', 'valor' => $datos['alu_idioma_fran']),
			array('campo' => 'nivel_idioma_italiano', 'valor' => $datos['alu_idioma_ital']),
			array('campo' => 'nivel_idioma_portugues', 'valor' => $datos['alu_idioma_port']),
			array('campo' => 'nivel_idioma_chino', 'valor' => $datos['alu_idioma_chino']),
			array('campo' => 'idioma_otro', 'valor' => $datos['alu_idioma_otro']),
			array('campo' => 'nivel_idioma_otro', 'valor' => $datos['alu_idioma_otro_nivel'])
		);

		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_datos_actividades')) {
			guarani::act('act_personas')->actualizar_datos_actividades($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_datos_actividades($dato_censal, $parametros);
		}
	}

	static function actualizar_datos_personales($datos, $dato_censal)
	{
		$situacion_padre = null;
		if (!is_null($datos['padre_vive'])) {
			$situacion_padre = ($datos['padre_vive'] == comunes::si) ? persona::padre_madre_vive : $datos['padre_vive'];
		}
		$situacion_madre = null;
		if (!is_null($datos['madre_vive'])) {
			$situacion_madre = ($datos['madre_vive'] == comunes::si) ? persona::padre_madre_vive : $datos['madre_vive'];
		}

		$parametros = array(
			array('campo' => 'estado_civil', 'valor' => $datos['estado_civil']),
			array('campo' => 'situacion_padre', 'valor' => $situacion_padre),
			array('campo' => 'situacion_madre', 'valor' => $situacion_madre),
			array('campo' => 'cantidad_hijos', 'valor' => $datos['cant_hijos_alum']),
			array('campo' => 'cantidad_familia', 'valor' => $datos['cant_fami_cargo']),
			array('campo' => 'turno_preferido', 'valor' => static::convertir_turno_preferido($datos['turno_preferido'])),
			array('campo' => 'tipo_vivienda', 'valor' => $datos['tipo_res_per_lect']),
			array('campo' => 'vive_con', 'valor' => $datos['vive_actual_con']),
			array('campo' => 'unido_hecho', 'valor' => $datos['alu_est_civil_uh']),
			array('campo' => 'cobertura_salud', 'valor' => $datos['obra_social_alu']),
			array('campo' => 'periodo_lectivo_calle', 'valor' => $datos['calle_per_lect']),
			array('campo' => 'periodo_lectivo_numero', 'valor' => $datos['numero_per_lect']),
			array('campo' => 'periodo_lectivo_piso', 'valor' => $datos['piso_per_lect']),
			array('campo' => 'periodo_lectivo_departamento', 'valor' => $datos['dpto_per_lect']),
			array('campo' => 'periodo_lectivo_unidad', 'valor' => $datos['unidad_per_lect']),
			array('campo' => 'periodo_lectivo_localidad', 'valor' => $datos['loc_per_lect']),
			array('campo' => 'periodo_lectivo_barrio', 'valor' => $datos['barrio_per_lec']),
			array('campo' => 'periodo_lectivo_codigo_postal', 'valor' => $datos['cp_per_lect']),
			array('campo' => 'procedencia_calle', 'valor' => $datos['calle_proc']),
			array('campo' => 'procedencia_numero', 'valor' => $datos['numero_proc']),
			array('campo' => 'procedencia_piso', 'valor' => $datos['piso_proc']),
			array('campo' => 'procedencia_departamento', 'valor' => $datos['dpto_proc']),
			array('campo' => 'procedencia_unidad', 'valor' => $datos['unidad_proc']),
			array('campo' => 'procedencia_localidad', 'valor' => $datos['loc_proc']),
			array('campo' => 'procedencia_barrio', 'valor' => $datos['barrio_proc']),
			array('campo' => 'procedencia_codigo_postal', 'valor' => $datos['cp_proc']),
			array('campo' => 'es_celiaco', 'valor' => $datos['es_celiaco']),
		);

		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_datos_personales')) {
			guarani::act('act_personas')->actualizar_datos_personales($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_datos_personales($dato_censal, $parametros);
		}
	}

	static function actualizar_eleccion_propuesta($datos, $dato_censal)
	{
		$parametros = array(
			array('campo' => 'mot_vocacion', 'valor' => $datos['mot_prop_vocacion']),
			array('campo' => 'mot_sugerencia_familiar', 'valor' => $datos['mot_prop_sug_fam']),
			array('campo' => 'mot_reconocimiento_social', 'valor' => $datos['mot_prop_rec_social']),
			array('campo' => 'mot_insercion_laboral', 'valor' => $datos['mot_prop_ins_lab']),
			array('campo' => 'mot_perfil_profesional', 'valor' => $datos['mot_prop_perf_prof']),
			array('campo' => 'mot_mejora_economica', 'valor' => $datos['mot_prop_mejora_ec']),
			array('campo' => 'mot_util_sociedad', 'valor' => $datos['mot_prop_util_soc']),
			array('campo' => 'mot_otros_p', 'valor' => $datos['mot_prop_otros']),
		);
		
		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_eleccion_propuesta')) {
			guarani::act('act_personas')->actualizar_eleccion_propuesta($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_eleccion_propuesta($dato_censal, $parametros);
		}
	}
	
	static function actualizar_eleccion_institucion($datos, $dato_censal)
	{
		$parametros = array(
			array('campo' => 'mot_economico', 'valor' => $datos['mot_inst_econom']),
			array('campo' => 'mot_prestigio', 'valor' => $datos['mot_inst_prestigio']),
			array('campo' => 'mot_difusion', 'valor' => $datos['mot_inst_difusion']),
			array('campo' => 'mot_recomendacion_estudiantes', 'valor' => $datos['mot_inst_rec_estud']),
			array('campo' => 'mot_recomendacion_amigos', 'valor' => $datos['mot_inst_rec_amigos']),
			array('campo' => 'mot_sistema_ingreso', 'valor' => $datos['mot_inst_sist_ing']),
			array('campo' => 'mot_ubicacion', 'valor' => $datos['mot_inst_ubicacion']),
			array('campo' => 'mot_otros_i', 'valor' => $datos['mot_inst_otros']),
			array('campo' => 'insc_otra_institucion', 'valor' => $datos['insc_otra_inst']),
			array('campo' => 'insc_otra_institucion_desc', 'valor' => $datos['insc_otra_inst_descr']),
			array('campo' => 'como_conocio_institucion', 'valor' => $datos['como_conocio_institucion']),
		);

		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_eleccion_institucion')) {
			guarani::act('act_personas')->actualizar_eleccion_institucion($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_eleccion_institucion($dato_censal, $parametros);
		}
	}

	static function actualizar_discapacidad($datos, $dato_censal)
	{
		
		$campos = ['condicion_discapacidad', 'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
			'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
			'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
			'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
			'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
			'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
			'informacion_importante'];
		
		$parametros = [];
		foreach ($campos as $campo) {
			$parametros[] = ['campo' => $campo, 'valor' => $datos[$campo]];
		}

		if (toba::consulta_php('co_datos_censales')->existe_registro_dc($dato_censal, 'mdp_datos_discapacidad')) {
			guarani::act('act_personas')->actualizar_discapacidad($dato_censal, $parametros);
		} else {
			guarani::act('act_personas')->insertar_discapacidad($dato_censal, $parametros);
		}
	}
	
	/**
	 * Conversión del turno de cursada preferido para el módulo de Preinscripción al turno para G3. 
	 *
	 * @param char $turno_preferido
	 * @return integer
	 */
	static function convertir_turno_preferido($turno_preferido)
	{
		if (!is_null($turno_preferido)) {
			switch ($turno_preferido) {
				case self::turno_maniana:
					$turno = persona::turno_maniana;
					break;
				case self::turno_tarde:
					$turno = persona::turno_tarde;
					break;
				case self::turno_noche:
					$turno = persona::turno_noche;
					break;
			}
			return $turno;
		}
		return $turno_preferido;
	}

	static function actualizar_antecedentes_docentes($datos, $persona)
	{
		$fuente = (!empty(self::$fuente)) ? self::$fuente : null;
		$antecedentes = toba::consulta_php('co_preinscripciones')->get_antecedentes_docentes($datos['id_preinscripcion'], $fuente);
		if (!empty($antecedentes)) {
			foreach ($antecedentes as $antecedente) {
				$parametros = array(
					array('campo' => 'institucion', 'valor' => $antecedente['institucion']),
					array('campo' => 'institucion_otra', 'valor' => $antecedente['institucion_prein']),
					array('campo' => 'cargo', 'valor' => $antecedente['cargo']),
					array('campo' => 'materia', 'valor' => $antecedente['materia']),
					array('campo' => 'trabajo_hora_sem', 'valor' => $antecedente['hora_sem_trab']),
					array('campo' => 'fecha_desde', 'valor' => $antecedente['fecha_desde']),
					array('campo' => 'fecha_hasta', 'valor' => $antecedente['fecha_hasta'])
				);

				if (!toba::consulta_php('co_preinscripciones')->existe_registro_antecedente_docente($persona, $parametros)) {
					guarani::act('act_personas')->insertar_antecedentes_docentes($persona, $parametros);
				}
			}
		}
	}

	static function actualizar_antecedentes_profesionales($datos, $persona)
	{
		$fuente = (!empty(self::$fuente)) ? self::$fuente : null;
		$antecedentes = toba::consulta_php('co_preinscripciones')->get_antecedentes_profesionales($datos['id_preinscripcion'], $fuente);
		if (!empty($antecedentes)) {
			foreach ($antecedentes as $antecedente) {
				$parametros = array(
					array('campo' => 'empresa', 'valor' => $antecedente['empresa']),
					array('campo' => 'cargo', 'valor' => $antecedente['cargo']),
					array('campo' => 'area', 'valor' => $antecedente['area']),
					array('campo' => 'trabajo_hora_sem', 'valor' => $antecedente['hora_sem_trab']),
					array('campo' => 'fecha_desde', 'valor' => $antecedente['fecha_desde']),
					array('campo' => 'fecha_hasta', 'valor' => $antecedente['fecha_hasta'])
				);

				if (!toba::consulta_php('co_preinscripciones')->existe_registro_antecedente_profesional($persona, $parametros)) {
					guarani::act('act_personas')->insertar_antecedentes_profesionales($persona, $parametros);
				}
			}
		}
	}

	static function actualizar_requisitos_digitales($datos, $persona)
	{
		$fuente = (!empty(self::$fuente)) ? self::$fuente : null;
		$requisitos = toba::consulta_php('co_preinscripciones')->get_requisitos_digitales($datos['id_preinscripcion'], $fuente);
		if (!empty($requisitos)) {
			foreach ($requisitos as $requisito) {
				$parametros = array(
					array('campo' => 'requisito', 'valor' => $requisito['requisito']),
					array('campo' => 'id_documento', 'valor' => $requisito['id_documento']),
				);
				if (!toba::consulta_php('co_requisitos')->existe_draft_requisito_digital($requisito['requisito'], $persona)) {
					guarani::act('act_personas')->insertar_draft_requisito_digital($persona, $parametros);
				}
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento de una inscripción.
	//-----------------------------------------------------------------------------------

	static function procesar_inscripcion($persona, $periodo_inscripcion, $periodo_insc, $anio_academico, $propuesta, $plan_version, $modalidad, $ubicacion, $fecha_inscripcion = 'now()', $tipo_ingreso = null, $convenio = null, $insc_fuera_de_termino = comunes::no, $situacion_asp = propuesta::situacion_asp_insc_aspirante_a_prop, $observaciones = null, $tipo_plan = planes::tipo_estructurado)
	{
		// Se agrega la inscripción a propuesta.
		$propuesta_aspira = guarani::act('act_inscripciones')->inscribir_a_propuesta($persona, $periodo_insc, $anio_academico, $propuesta, $plan_version, $modalidad, $ubicacion, $fecha_inscripcion, $tipo_ingreso, $convenio, $insc_fuera_de_termino, $situacion_asp, $observaciones);
		// Se insertan los registros correspondientes a los requisitos de aspirante (sga_requisitos_aspirante).
		static::insertar_requisitos_aspirante($persona, $periodo_inscripcion, $plan_version, $propuesta_aspira, $tipo_plan);

		// Si la persona presentó todos los requisitos de ingreso obligatorios, se acepta la inscripción a propuesta.
		$situacion_aspirante = static::actualizar_estado_inscripcion($propuesta_aspira);

		// Si no existen inscripciones previas (rechazadas) se inserta un nuevo alumno, sino se actualizan sus datos.
		static::procesar_alumno($persona, $propuesta, $plan_version, $modalidad, $ubicacion, $situacion_aspirante);

		$propuestas_aspira_vinculadas = static::inscribir_a_propuestas_vinculadas($persona, $periodo_inscripcion, $periodo_insc, $anio_academico, $propuesta, $plan_version, $modalidad, $ubicacion, $fecha_inscripcion, $tipo_ingreso, $convenio, $insc_fuera_de_termino, $situacion_asp, $observaciones);

		return array_merge(array($propuesta_aspira), $propuestas_aspira_vinculadas);
	}

	static function insertar_requisitos_aspirante($persona, $periodo_inscripcion, $plan_version, $propuesta_aspira, $tipo_plan)
	{
		if ($tipo_plan == planes::tipo_personalizado || $tipo_plan == planes::tipo_convenio) {
			$requisitos = toba::consulta_php('co_requisitos')->get_requisitos_ingreso_plan_nuevo_pres($plan_version, $persona, $periodo_inscripcion);
		} else {
			$requisitos = toba::consulta_php('co_requisitos')->get_requisito_apl_pres($plan_version, $persona, $periodo_inscripcion);
		}
		if (!empty($requisitos)) {
			foreach ($requisitos as $requisito) {
				guarani::act('act_requisitos_aspirante')->insertar_requisito_aspirante($propuesta_aspira, $requisito['requisito'], $requisito['restrictivo'], $requisito['parametros']);
			}
		}
	}

	static function actualizar_estado_inscripcion($propuesta_aspira)
	{
		$situacion_aspirante = propuesta::situacion_asp_insc_aspirante_a_prop;
		if (inscripcion::cumple_requisitos_ingreso($propuesta_aspira)) {
			$situacion_aspirante = propuesta::situacion_asp_insc_prop_aceptada;
			guarani::act('act_inscripciones')->actualizar_situacion_propuesta_aspira($propuesta_aspira, $situacion_aspirante);
		}

                // Decide si encola en Redis para notificar a SQ las inscripciones a Propuesta vía Rest-Hooks
                cobro::notificar_inscripcion_propuesta_SQ($propuesta_aspira);
		
		return $situacion_aspirante;
	}

	static function procesar_alumno($persona, $propuesta, $plan_version, $modalidad, $ubicacion, $situacion_aspirante, $fecha_inscripcion = 'now()')
	{	
		try {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_alumno($persona, $propuesta);
			if (!empty($datos_alumno)) { 
				// Si existe una inscripción previa (rechazada), se realizan actualizaciones sobre el alumno.
				$alumno = $datos_alumno['alumno'];

				// Se actualizan: cantidad de readmisiones = 0, regular = S.
				guarani::act('act_alumnos')->actualizar_alumno($alumno, 0, comunes::si);

				// Se actualiza la calidad.
				if ($datos_alumno['calidad'] != alumno::calidad_activo) {
					$calidad_alumno = toba::consulta_php('co_alumnos')->get_ultimo_cambio_calidad($datos_alumno['alumno']);
					// Si el último cambio de calidad del alumno es posterior a la fecha de inscripción...
					if (guarani_fecha::es_mayor($calidad_alumno['fecha'], $fecha_inscripcion)) {
						throw guarani::error(guarani_mensajes::get('800MAT_alumno_fecha_menor_ultimo_cambio_calidad'));
					} elseif (guarani_fecha::es_igual($calidad_alumno['fecha'], $fecha_inscripcion)) { // Si ese último cambio es igual a la fecha de inscripción...
						$fecha_cambio_calidad = guarani_fecha::get_fecha_hora_desplazada($calidad_alumno['fecha'], 1, 'minuto');
					} else { // Si ese último cambio es anterior a la fecha de inscripción...
						$fecha_cambio_calidad = ($fecha_inscripcion == guarani_fecha::get_hoy()) ? guarani_fecha::get_ahora() : $fecha_inscripcion;
					}
					guarani::act('act_alumnos')->actualizar_calidad_alumno($alumno, alumno::calidad_activo);
					guarani::act('act_alumnos')->insertar_cambio_calidad($alumno, alumno::calidad_activo, alumno::motivo_calidad_alta, $fecha_cambio_calidad);
				}
				// Se actualiza el plan_version.
				if ($datos_alumno['plan_version'] != $plan_version) {
					$plan_alumno = toba::consulta_php('co_alumnos')->get_ultimo_cambio_plan($datos_alumno['alumno']);
					// Si el último cambio de plan del alumno es posterior a la fecha de inscripción...
					if (guarani_fecha::es_mayor($plan_alumno['fecha'], $fecha_inscripcion)) {
						throw guarani::error(guarani_mensajes::get('800MAT_alumno_fecha_menor_ultimo_cambio_plan'));
					} elseif (guarani_fecha::es_igual($plan_alumno['fecha'], $fecha_inscripcion)) { // Si ese último cambio es igual a la fecha de inscripción...
						$fecha_cambio_plan = guarani_fecha::get_fecha_hora_desplazada($plan_alumno['fecha'], 1, 'minuto');
					} else { // Si ese último cambio es anterior a la fecha de inscripción...
						$fecha_cambio_plan = ($fecha_inscripcion == guarani_fecha::get_hoy()) ? guarani_fecha::get_ahora() : $fecha_inscripcion;
					}
					guarani::act('act_alumnos')->actualizar_plan_alumno($alumno, $plan_version);
					guarani::act('act_alumnos')->insertar_cambio_plan($alumno, $plan_version, alumno::motivo_plan_alta, $fecha_cambio_plan);
				}
				// Se actualiza la ubicación.
				if ($datos_alumno['ubicacion'] != $ubicacion) {
					$ubicacion_alumno = toba::consulta_php('co_alumnos')->get_ultimo_cambio_ubicacion($datos_alumno['alumno']);
					// Si el último cambio de ubicación del alumno es posterior a la fecha de inscripción...
					if (guarani_fecha::es_mayor($ubicacion_alumno['fecha'], $fecha_inscripcion)) {
						throw guarani::error(guarani_mensajes::get('800MAT_alumno_fecha_menor_ultimo_cambio_ubicacion'));
					} elseif (guarani_fecha::es_igual($ubicacion_alumno['fecha'], $fecha_inscripcion)) { // Si ese último cambio es igual a la fecha de inscripción...
						$fecha_cambio_ubicacion = guarani_fecha::get_fecha_hora_desplazada($ubicacion_alumno['fecha'], 1, 'minuto');
					} else { // Si ese último cambio es anterior a la fecha de inscripción...
						$fecha_cambio_ubicacion = ($fecha_inscripcion == guarani_fecha::get_hoy()) ? guarani_fecha::get_ahora() : $fecha_inscripcion;
					}
					guarani::act('act_alumnos')->actualizar_ubicacion_alumno($alumno, $ubicacion);
					guarani::act('act_alumnos')->insertar_cambio_ubicacion($alumno, $ubicacion, alumno::motivo_ubicacion_alta, $fecha_cambio_ubicacion);
				}
				// Se actualiza la modalidad.
				if ($datos_alumno['modalidad'] != $modalidad) {
					$modalidad_alumno = toba::consulta_php('co_alumnos')->get_ultimo_cambio_modalidad($datos_alumno['alumno']);
					// Si el último cambio de modalidad del alumno es posterior a la fecha de inscripción...
					if (guarani_fecha::es_mayor($modalidad_alumno['fecha'], $fecha_inscripcion)) {
						throw guarani::error(guarani_mensajes::get('800MAT_alumno_fecha_menor_ultimo_cambio_modalidad'));
					} elseif (guarani_fecha::es_igual($modalidad_alumno['fecha'], $fecha_inscripcion)) { // Si ese último cambio es igual a la fecha de inscripción...
						$fecha_cambio_modalidad = guarani_fecha::get_fecha_hora_desplazada($modalidad_alumno['fecha'], 1, 'minuto');
					} else { // Si ese último cambio es anterior a la fecha de inscripción...
						$fecha_cambio_modalidad = ($fecha_inscripcion == guarani_fecha::get_hoy()) ? guarani_fecha::get_ahora() : $fecha_inscripcion;
					}
					guarani::act('act_alumnos')->actualizar_modalidad_alumno($alumno, $modalidad);
					guarani::act('act_alumnos')->insertar_cambio_modalidad($alumno, $modalidad, $plan_version, alumno::motivo_modalidad_alta, $fecha_cambio_modalidad);
				}
			} else {
				// Si no existen inscripciones previas (rechazadas), se agrega un nuevo alumno.
				$alumno = guarani::act('act_alumnos')->insertar_alumno($persona, $propuesta, $plan_version, $ubicacion, alumno::calidad_activo, $modalidad);

				// Se insertan registros en tablas históricas.
				guarani::act('act_alumnos')->insertar_cambio_calidad($alumno, alumno::calidad_activo, alumno::motivo_calidad_alta, $fecha_inscripcion);
				guarani::act('act_alumnos')->insertar_cambio_plan($alumno, $plan_version, alumno::motivo_plan_alta, $fecha_inscripcion);
				guarani::act('act_alumnos')->insertar_cambio_modalidad($alumno, $modalidad, $plan_version, alumno::motivo_modalidad_alta, $fecha_inscripcion);
				guarani::act('act_alumnos')->insertar_cambio_ubicacion($alumno, $ubicacion, alumno::motivo_ubicacion_alta, $fecha_inscripcion);

				// En caso de que corresponda, se setea el legajo.
				$datos = array();
				$datos['propuesta'] = $propuesta;
				$datos['persona'] = $persona;
				$datos['ubicacion'] = $ubicacion;
				$mje = static::setear_legajo($alumno, $situacion_aspirante, $datos);
				if (!empty($mje)) {
					toba::notificacion()->agregar($mje, 'warning');
				}
			}
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	static function setear_legajo($alumno, $situacion_aspirante, $datos)
	{
		$mje = '';
		$atributos = array($datos['propuesta']);
		if (guarani::parametros()->get_valor('mat_utiliza_legajo', $atributos, false) == comunes::si) {
			// Si el parametro 'mat_inscripciones_a_generar_legajo' es 'A' o 'R' y cumplio con los requisitos restrictivos sigo...
			$param_mat_insc_gen_legajo = guarani::parametros()->get_valor('mat_inscripciones_a_generar_legajo', $atributos, false);
			if ($param_mat_insc_gen_legajo == 'T' || $situacion_aspirante == propuesta::situacion_asp_insc_prop_aceptada) {
				if (guarani::parametros()->get_valor('mat_genera_legajo_inscripcion', $atributos, false) == comunes::si) {
					$mat_legajo_alumno_generacion = guarani::parametros()->get_valor('mat_legajo_alumno_generacion', $atributos, false);
					if ($mat_legajo_alumno_generacion == legajos::generacion_manual) {
						$mje = guarani_mensajes::get('800MAT_error_legajo_manual');
					} else {
						$mat_legajo_alumno_unico = guarani::parametros()->get_valor('mat_legajo_alumno_unico', null, false);
						$legajo = legajos::get_nuevo_nro_legajo($mat_legajo_alumno_unico, 'NULL', $datos['persona'], $datos['propuesta'], $datos['ubicacion']);
						guarani::act('act_alumnos')->actualizar_legajo($alumno, $legajo);
					}
				}
			}
		}
		return $mje;
	}

	static function inscribir_a_propuestas_vinculadas($persona, $periodo_inscripcion, $periodo_insc, $anio_academico, $propuesta, $plan_version, $modalidad, $ubicacion, $fecha_inscripcion = 'now()', $tipo_ingreso = null, $convenio = null, $insc_fuera_de_termino = comunes::no, $situacion_asp = propuesta::situacion_asp_insc_aspirante_a_prop, $observaciones = propuesta::obs_inscripcion_automatica)
	{
		$plan = toba::consulta_php('co_planes')->get_plan_de_plan_version($plan_version, false);

		$filtro = "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_pendiente . "','" . propuesta::inscripcion_aceptada . "')";

		$propuestas_aspira = array();
		$relaciones = toba::consulta_php('co_propuestas_relaciones')->get_relaciones_por_anio_academico_propuesta_plan($anio_academico, $propuesta, $plan);
		foreach ($relaciones as $relacion) {
			if (toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $relacion['propuesta'], null, $filtro) == 0) {
				$relacion_plan_version = toba::consulta_php('co_planes')->get_version_actual($relacion['plan']);
				if (!empty($relacion_plan_version)) {
					$propuestas_aspira = array_merge($propuestas_aspira, static::procesar_inscripcion($persona, $periodo_inscripcion, $periodo_insc, $anio_academico, $relacion['propuesta'], $relacion_plan_version, $modalidad, $ubicacion, $fecha_inscripcion, $tipo_ingreso, $convenio, $insc_fuera_de_termino, $situacion_asp, $observaciones));
				}
			}
		}

		return $propuestas_aspira;
	}
    
    //Retorno los datos de una preinscripcion de todas las fuentes de datos.
    static function get_preinscripto_rest($usuario, $pais, $tipo_documento, $numero_documento)
    {
        $fuentes = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();
        $aux = array();
        foreach ($fuentes as $fuente) {
            $datos_fuente = toba::consulta_php('co_preinscripciones')->get_preinscripto_rest($fuente['fuente_de_datos'], $usuario, $pais, $tipo_documento, $numero_documento);
            $aux = array_merge($aux, $datos_fuente);
        }
        return $aux;
    }
    
    static function get_preinscriptos()
    {
        //Fuentes de datos
        $fuentes = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();
        
    }
    
    static function get_turnos_preferidos()
    {
        return array(			
			self::turno_maniana => self::turno_maniana_desc,
			self::turno_tarde => self::turno_tarde_desc,
			self::turno_noche => self::turno_noche_desc,
        );
    }
}