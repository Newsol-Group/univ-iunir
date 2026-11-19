<?php
namespace siu\operaciones\datos_censales\_comun;

use SIU\Chulupi\kernel;
use siu\modelo\transacciones\becas;

class util_form_censo 
{
	static $campos_dc;
	static $campos_secciones;
	static $campos_descr;
	static $campos_id;
	
	static $campos_conf;
	static $secciones_configurables = [
            'datos_personales',
            'domicilio',
            'financiamiento',
            'datos_laborales',
            'tecnologia',
            'deportes',
            'idiomas',
            'datos_salud',
            'preferencias',
            'discapacidad'
        ];
	
	protected static function get_campos_conf()
	{

		$viene_de_convocatorias_becas = becas::viene_de_convocatorias_becas();
		$campos_convocatoria = [];
		$campos_dependientes_convocatoria = [];
		
		// Si viene de la operación "Convocatorias a Becas"
		if ($viene_de_convocatorias_becas) {

			// Botón "Modificar Datos" (reapertura)
			if (kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION)) {
				$obligatorio_nombre = 'obligatorio_reapertura';
			// Botón "Aplicar" (primera postulación)
			} else {
				$obligatorio_nombre = 'obligatorio_becas';
			}

			$campos_convocatoria = kernel::sesion()->get(becas::CLAVE_CAMPOS_DATOS_CENSALES_SESION);

			// Busco los datos censales dependientes de la convocatoria
			if (!empty($campos_convocatoria)) {
				$datos_censales_dependientes = \toba::consulta_php('co_datos_censales', true)->get_datos_censales_dependientes($campos_convocatoria);
				$campos_dependientes_convocatoria = array_column($datos_censales_dependientes, 'campo');
			}
		}

		kernel::log()->add_debug("=== viene_de_convocatorias_becas ===", $viene_de_convocatorias_becas);
		kernel::log()->add_debug("=== campos_convocatoria ===", $campos_convocatoria);
		kernel::log()->add_debug("=== campos_dependientes_convocatoria ===", $campos_dependientes_convocatoria);

		// Configuración de "Configurar Campos de Datos Censales (interfaz alumno)"
		$datos = \toba::consulta_php('co_datos_censales', true)->get_datos_config();
		$salida = [];
		foreach ($datos as $dato) {

			$campo = $dato['campo'];
			$salida[$campo]['visible'] = ($dato['visible'] == \comunes::si);

			// Si viene de la operación "Convocatorias a Becas"
			if ($viene_de_convocatorias_becas) {

				// Solo son obligatorios los campos de la convocatoria
				$salida[$campo]['obligatorio'] = false;

				// Pongo como visibles y obligatorios los campos de la convocatoria
				if (in_array($campo, $campos_convocatoria)) {
					$salida[$campo]['visible'] = true;
					$salida[$campo]['obligatorio'] = true;
					$salida[$campo][$obligatorio_nombre] = true;
				}

				// Pongo como visibles los campos dependientes de la convocatoria
				if (in_array($campo, $campos_dependientes_convocatoria)) {
					$salida[$campo]['visible'] = true;
				}
				
			// Si entró directamente a "Mis datos personales"
			} else {

				$salida[$campo]['obligatorio'] = ($dato['obligatorio'] == \comunes::si);

				// Ver ticket #46250 (https://redmine.siu.edu.ar/redmine/issues/46250)
				$campos_dis = ['psi_otros_apoyos', 'aud_otros_apoyos', 'vis_otros_apoyos', 'mot_otros_apoyos', 'otra_apoyo_vida_diaria'];

				// Si es uno de los campos "Especificar cuáles" de "Discapacidad" lo pongo como no obligatorio
				if (in_array($campo, $campos_dis)) {
					$salida[$campo]['obligatorio'] = false;
				}

			}
		}

		return $salida;
	}

	static function visible($campo) 
	{
		if (empty(static::$campos_conf)) {
			static::$campos_conf = static::get_campos_conf();
		}
		return isset(self::$campos_conf[$campo]['visible']) ? self::$campos_conf[$campo]['visible'] : true;
	}
	
	static function obligatorio($campo)
	{
		if (empty(static::$campos_conf)) {
			static::$campos_conf = static::get_campos_conf();
		}
		return isset(self::$campos_conf[$campo]['obligatorio']) ? self::$campos_conf[$campo]['obligatorio'] : false;
	}
	
	static function obligatorio_becas($campo)
	{
		if (empty(static::$campos_conf)) {
			static::$campos_conf = static::get_campos_conf();
		}
		return isset(self::$campos_conf[$campo]['obligatorio_becas']) ? self::$campos_conf[$campo]['obligatorio_becas'] : false;
	}

	static function obligatorio_reapertura($campo)
	{
		if (empty(static::$campos_conf)) {
			static::$campos_conf = static::get_campos_conf();
		}
		return isset(self::$campos_conf[$campo]['obligatorio_reapertura']) ? self::$campos_conf[$campo]['obligatorio_reapertura'] : false;
	}

	/**
	 * Verifica si existe al menos un campo en un agrupado de una sección que 
	 * esté definido como visible
	 * 
	 * @param string $seccion
	 * @param string $agrupado
	 * @return boolean
	 */
	static function hay_visibles_agrupado($seccion, $agrupado)
	{
		if (!in_array($seccion, self::$secciones_configurables)) {
			return true;
		}
		
		$campos_seccion = static::get_campos_seccion($seccion);
		$campos_agrupado = $campos_seccion[$agrupado];
		foreach($campos_agrupado as $campo) {
			if (static::visible($campo)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Verifica si existen campos visible en una sección de formulario
	 * 
	 * @param string $seccion
	 * @return boolean
	 */
	static function hay_visibles_seccion($seccion)
	{
		if (!in_array($seccion, self::$secciones_configurables)) {
			return true;
		}
		$campos_seccion = static::get_campos_seccion($seccion);
		foreach ($campos_seccion as $agrupado => $campos) {
			
			if (static::hay_visibles_agrupado($seccion, $agrupado)) {
				return true;
			}
		}
		return false;
	}
	
	//--------------------------------------------------------------------------
	
	static function get_campos_dc()
	{
		if (!isset(self::$campos_dc)) {
			self::$campos_dc = ['estado_civil', 'unido_hecho', 'situacion_padre', 'situacion_madre', 
				'cantidad_hijos', 'cantidad_familia', 'turno_preferido', 'cobertura_salud', 'tipo_vivienda', 
				'vive_con', 'periodo_lectivo_calle', 'periodo_lectivo_numero', 'periodo_lectivo_piso', 
				'periodo_lectivo_departamento',	'periodo_lectivo_unidad', 'periodo_lectivo_localidad', 
				'periodo_lectivo_barrio', 'periodo_lectivo_codigo_postal', 'procedencia_calle', 'procedencia_numero', 
				'procedencia_piso', 'procedencia_departamento', 'procedencia_unidad', 'procedencia_localidad', 
				'procedencia_barrio', 'procedencia_codigo_postal', 'tecnologia_pc_casa', 'tecnologia_pc_trabajo', 
				'tecnologia_pc_universidad', 'tecnologia_pc_otro_lugar', 'tecnologia_int_casa', 'tecnologia_int_trabajo', 
				'tecnologia_int_universidad', 'tecnologia_int_movil', 'tecnologia_int_otro_lugar', 
				'deportes', 'deportes_universidad', 'deportes_gimnasio', 'deportes_particular', 'deportes_otro_lugar', 
				'deportes_basquet', 'deportes_futbol', 'deportes_gimnasia', 'deportes_handball', 
				'deportes_natacion', 'deportes_tenis', 'deportes_voley', 'deportes_otros', 'nivel_idioma_ingles', 
				'nivel_idioma_aleman', 'nivel_idioma_frances', 'nivel_idioma_italiano', 'nivel_idioma_portugues', 'nivel_idioma_chino', 
				'idioma_otro', 'nivel_idioma_otro', 'costeo_estudios_familiar', 'costeo_estudios_plan_social', 'costeo_estudios_trabajo', 
				'costeo_estudios_beca', 'costeo_estudios_otro', 'costeo_estudios_descripcion', 'beca', 'beca_fuente_universidad', 
				'beca_fuente_nacional', 'beca_fuente_provincial', 'beca_fuente_municipal', 'beca_fuente_internacional', 'beca_fuente_otra', 
				'beca_tipo_economica', 'beca_tipo_servicios', 'beca_tipo_investigacion', 'beca_economica_transporte', 'beca_economica_efectivo', 
				'beca_economica_fotocopias', 'beca_economica_habitacional', 'beca_economica_comedor', 'trabajo_existe', 
				'trabajo_hace', 'trabajo_familiar', 'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago', 'trabajo_ocupacion', 
				'trabajo_hora_sem', 'trabajo_carrera', 'trabajo_tarea_descripcion', 'es_celiaco',
				'mot_economico', 'mot_prestigio', 'mot_difusion', 'mot_recomendacion_estudiantes', 'mot_recomendacion_amigos', 
				'mot_sistema_ingreso', 'mot_ubicacion', 'mot_otros_i', 'insc_otra_institucion', 'insc_otra_institucion_desc', 'como_conocio_institucion',
				'mot_vocacion', 'mot_sugerencia_familiar', 'mot_reconocimiento_social', 'mot_insercion_laboral', 
				'mot_perfil_profesional', 'mot_mejora_economica', 'mot_util_sociedad', 'mot_otros_p', 
				'condicion_discapacidad', 'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
				'aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos',
				'vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos',
				'mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos',
				'psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos',
				'otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria',
				'informacion_importante'
			];
		}
		return self::$campos_dc;
	}
	
	static function get_campos_secciones()
	{
		if (!isset(self::$campos_secciones)) {
			self::$campos_secciones = [];
			//------------------------------------------------------------------
			self::$campos_secciones['datos_personales'] = array();
			self::$campos_secciones['datos_personales']['situacion_familiar'] = array('estado_civil', 'unido_hecho', 'situacion_padre', 
				'situacion_madre', 'cantidad_hijos', 'cantidad_familia');
			self::$campos_secciones['datos_personales']['turno_preferido'] = array('turno_preferido');
			//------------------------------------------------------------------
			self::$campos_secciones['domicilio'] = array();
			self::$campos_secciones['domicilio']['periodo_lectivo'] = array('periodo_lectivo_calle', 'periodo_lectivo_numero', 
				'periodo_lectivo_piso', 'periodo_lectivo_departamento', 'periodo_lectivo_unidad', 'periodo_lectivo_localidad', 
				'periodo_lectivo_barrio', 'periodo_lectivo_codigo_postal', 'tipo_vivienda', 'vive_con');
			self::$campos_secciones['domicilio']['procedencia'] = array('procedencia_calle', 'procedencia_numero', 'procedencia_piso', 
				'procedencia_departamento', 'procedencia_unidad', 'procedencia_localidad', 'procedencia_barrio', 'procedencia_codigo_postal');
			//------------------------------------------------------------------
			self::$campos_secciones['financiamiento'] = array();
			self::$campos_secciones['financiamiento']['financ_fuente'] = array('costeo_estudios_familiar', 'costeo_estudios_plan_social', 
				'costeo_estudios_trabajo', 'costeo_estudios_beca', 'costeo_estudios_otro', 'costeo_estudios_descripcion');
			self::$campos_secciones['financiamiento']['financ_beca_fuente'] = array('beca_fuente_universidad', 'beca_fuente_nacional', 
				'beca_fuente_provincial', 'beca_fuente_municipal', 'beca_fuente_internacional', 'beca_fuente_otra');
			self::$campos_secciones['financiamiento']['financ_beca_tipo'] = array('beca_tipo_economica', 'beca_tipo_servicios', 
				'beca_tipo_investigacion');
			self::$campos_secciones['financiamiento']['financ_beca_tipo_econom'] = array('beca_economica_transporte', 'beca_economica_efectivo', 
				'beca_economica_fotocopias', 'beca_economica_habitacional', 'beca_economica_comedor', );
			//------------------------------------------------------------------
			self::$campos_secciones['datos_laborales'] = array();
			self::$campos_secciones['datos_laborales']['datos_laborales_sit'] = array('trabajo_existe', 'trabajo_hace', 'trabajo_ocupacion', 
				'trabajo_hora_sem', 'trabajo_carrera', 'trabajo_tarea_descripcion');
			self::$campos_secciones['datos_laborales']['datos_laborales_adic'] = array('trabajo_familiar', 
				'trabajo_con_descuentos_jubilatorios', 'trabajo_recibe_pago');
			//------------------------------------------------------------------
			self::$campos_secciones['tecnologia'] = array();
			self::$campos_secciones['tecnologia']['dispon_pc'] = array('tecnologia_pc_casa', 'tecnologia_pc_trabajo',
				'tecnologia_pc_universidad', 'tecnologia_pc_otro_lugar');
			self::$campos_secciones['tecnologia']['accede_internet_en'] = array('tecnologia_int_casa',
				'tecnologia_int_trabajo', 'tecnologia_int_universidad', 'tecnologia_int_movil', 'tecnologia_int_otro_lugar');
			//------------------------------------------------------------------
			self::$campos_secciones['deportes'] = array();
			self::$campos_secciones['deportes']['deportes'] = array('deportes');
			self::$campos_secciones['deportes']['listado_deportes_lugares'] = array('deportes_universidad', 'deportes_gimnasio', 
				'deportes_particular', 'deportes_otro_lugar');
			self::$campos_secciones['deportes']['listado_deportes'] = array('deportes_basquet', 'deportes_futbol', 
				'deportes_gimnasia', 'deportes_handball', 'deportes_natacion', 'deportes_tenis', 'deportes_voley', 'deportes_otros');
			//------------------------------------------------------------------
			self::$campos_secciones['idiomas'] = array();
			self::$campos_secciones['idiomas']['conoc_idiomas'] = array('nivel_idioma_ingles', 'nivel_idioma_aleman', 'nivel_idioma_frances', 
				'nivel_idioma_italiano', 'nivel_idioma_portugues', 'nivel_idioma_chino', 'idioma_otro', 'nivel_idioma_otro');
			//------------------------------------------------------------------
			self::$campos_secciones['datos_salud'] = array();
			self::$campos_secciones['datos_salud']['salud'] = array('cobertura_salud', 'es_celiaco');
			//------------------------------------------------------------------
			self::$campos_secciones['preferencias'] = array();
			self::$campos_secciones['preferencias']['conocim_institucion'] = array('como_conocio_institucion');
			self::$campos_secciones['preferencias']['otras_insc'] = array('insc_otra_institucion', 'insc_otra_institucion_desc');
			self::$campos_secciones['preferencias']['institucion'] = array('mot_economico', 'mot_prestigio', 'mot_difusion', 
				'mot_recomendacion_estudiantes', 'mot_recomendacion_amigos', 'mot_sistema_ingreso', 'mot_ubicacion', 'mot_otros_i');
			self::$campos_secciones['preferencias']['propuesta'] = array('mot_vocacion', 'mot_sugerencia_familiar', 
				'mot_reconocimiento_social', 'mot_insercion_laboral', 'mot_perfil_profesional', 'mot_mejora_economica', 
				'mot_util_sociedad', 'mot_otros_p');
			//------------------------------------------------------------------
			self::$campos_secciones['discapacidad'] = [];
			self::$campos_secciones['discapacidad']['cond_discapacidad'] = ['condicion_discapacidad', 'tiene_cud'];
			self::$campos_secciones['discapacidad']['listado_discapacidad_tipos'] = ['disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra'];
			self::$campos_secciones['discapacidad']['auditiva'] = ['aud_tipo', 'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_comunicacion_otra', 'aud_apoyo_vida_diaria', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos', 'aud_otros_apoyos'];
			self::$campos_secciones['discapacidad']['visual'] = ['vis_dificultad_para_ver', 'vis_apoyo_vida_diaria', 'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos', 'vis_otros_apoyos'];
			self::$campos_secciones['discapacidad']['motora'] = ['mot_apoyo_vida_diaria', 'mot_req_apoyo_aula', 'mot_apoyo_aula', 'mot_req_otros_apoyos', 'mot_otros_apoyos'];
			self::$campos_secciones['discapacidad']['psicosocial'] = ['psi_descripcion', 'psi_apoyo_vida_diaria', 'psi_req_otros_apoyos', 'psi_otros_apoyos'];
			self::$campos_secciones['discapacidad']['otras'] = ['otra_descripcion', 'otra_req_apoyo_vida_diaria', 'otra_apoyo_vida_diaria'];
			self::$campos_secciones['discapacidad']['importante'] = ['informacion_importante'];
		}
		return self::$campos_secciones;
	}
	
	static function get_campos_seccion($seccion)
	{
		$campos = static::get_campos_secciones();
		if (isset($campos[$seccion])) {
			return $campos[$seccion];
		}
		return [];
	}

        /**
         * Obtiene los campos de las secciones, sin la subdivisión de las subsecciones.
         *
         * @return array
         */
        static function get_campos_secciones_aplanado()
        {
            $campos_secciones = static::get_campos_secciones();
            $salida = [];
            foreach ($campos_secciones as $seccion_nombre => $subseccion) {
                $salida[$seccion_nombre] = [];
                foreach ($subseccion as $subseccion_campos) {
                    $salida[$seccion_nombre] = array_merge($salida[$seccion_nombre], $subseccion_campos);
                }
            }
            return $salida;
        }

        /**
         * Obtiene secciones de los campos dados.
         *
         * @param string[] $campos
         * @return string[] Secciones de los campos dados.
         */
        static function get_secciones_by_campos($campos)
	{
            $campos_secciones = static::get_campos_secciones_aplanado();

            $secciones = [];
            foreach ($campos_secciones as $seccion => $campos_seccion) {
                $interseccion = array_intersect($campos, $campos_seccion);
                // Si hay intersección es porque la sección corresponde a los campos
                if (!empty($interseccion)) {
                    $secciones[] = $seccion;
                }
            }
            
            return $secciones;
	}
	
	//--------------------------------------------------------------------------
	
	static function inicializar_mapeos_id_descripcion()
	{
		/**
		 * self::$campos_id
		 * 
		 * Mantiene un mapeo inverso al de $campos_descr: campo_id => campo_descr 
		 * para campos que almacenan internamente un id, pero se exhibe una 
		 * descripción en el formulario.
		 * 
		 * Ejemplo: 'periodo_lectivo_localidad' => 'periodo_lectivo_localidad_descr'
		 */
		if (!isset(self::$campos_id)) {
			self::$campos_id = array(
				'periodo_lectivo_localidad' => 'periodo_lectivo_localidad_descr',
				'procedencia_localidad' => 'procedencia_localidad_descr',
			);
		}
		
		/**
		 * self::$campos_descr
		 * 
		 * Mapeo inverso a self::$campos_id
		 */
		if (!isset(self::$campos_descr)) {
			self::$campos_descr = array();
			foreach (self::$campos_id as $id => $descr) {
				self::$campos_descr[$descr] = $id;
			}
		}
	}
	
	/**
	 * Obtiene el campo ID de un campo descriptivo
	 * Si no tiene, retorna null
	 */
	static function get_campo_id($campo_descr)
	{
		static::inicializar_mapeos_id_descripcion();
		$salida = isset(self::$campos_descr[$campo_descr]) ? self::$campos_descr[$campo_descr] : null;
		return $salida;
	}
	
	/**
	 * Obtiene el campo descriptivo a partir de un campo ID
	 * Si no tiene, retorna null
	 */
	static function get_campo_descr($campo_id)
	{
		static::inicializar_mapeos_id_descripcion();
		$salida = isset(self::$campos_id[$campo_id]) ? self::$campos_id[$campo_id] : null;
		return $salida;
	}
	
}