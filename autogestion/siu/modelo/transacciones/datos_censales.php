<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo\_comun\abm;
use siu\operaciones\datos_censales\_comun\form_censo;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\entidades\parametro;

class datos_censales extends transaccion
{
	protected $operacion = 'mat00022';
	protected $evento = '2';
	protected $dato_censal;

	function get_id_dato_censal()
	{
		if (empty($this->dato_censal)) {
			$datos = \toba::consulta_php('co_datos_censales')->get_id_dato_censal($this->persona);
			if (!empty($datos['dato_censal'])) {
				$this->dato_censal = $datos['dato_censal'];
			} else {
				$parametros = array('persona' => $this->persona);
				$this->dato_censal = catalogo::consultar('datos_censales', 'insertar_dato_censal', $parametros);
			}
		}
		return $this->dato_censal;
	}

	/*
	 * Controla si existen registros en las tablas de datos censales de la persona. 
	 * Si no existen, los crea.
	 */
	function controlar_tablas()
	{
		$parametros = array('dato_censal' => $this->get_id_dato_censal());
		catalogo::consultar('datos_censales', 'controlar_tablas', $parametros);
	}
	
	function datos_vigentes()
	{
		$parametros = array(
			'persona' => $this->persona,
			'fecha' => \guarani_fecha::get_hoy(),
			'cant_meses' => parametro::get_valor('cant_meses_actualizar_datos_censales')
		);
		return catalogo::consultar('datos_censales', 'tiene_datos_censales_actualizados', $parametros);
	}

	//--------------------------------------------------------------------------
	//---- Control de faltantes ------------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos_faltantes()
	{
		$datos = $this->info__datos_censales();
		$forms = util_form_censo::$secciones_configurables;
		$campos_pagelets = util_form_censo::get_campos_secciones();

		$datos_faltantes = array();
		foreach ($forms as $form) {
			$campos_pagelet = $campos_pagelets[$form];
			$hay_faltantes = false;
			foreach ($campos_pagelet as $campos_secciones) {
				foreach ($campos_secciones as $campo) {
					if (array_key_exists($campo, $datos) && (is_null($datos[$campo])) && (util_form_censo::visible($campo)) && (util_form_censo::obligatorio($campo))) {
						if (!$hay_faltantes) {
							$datos_faltantes[$form] = array();
						}
						$datos_faltantes[$form][] = $campo;
						$hay_faltantes = true;
					}

				}
			}
		}

		return $datos_faltantes;
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__confirmar()
	{
		$parametros = array('dato_censal' => $this->get_id_dato_censal());
		catalogo::consultar('datos_censales', 'actualizar_fecha_modificacion', $parametros);
		
		// Invalidar caché
		$parametros = array(
			'persona' => $this->persona,
			'fecha' => \guarani_fecha::get_hoy(),
			'cant_meses' => parametro::get_valor('cant_meses_actualizar_datos_censales')
		);
		catalogo::limpiar_cache('datos_censales', 'tiene_datos_censales_actualizados', $parametros);
	}

	function evt__guardar($seccion, $datos)
	{
		
		if ($seccion == 'datos_personales') {
			// Datos que van a mdp_personas			
			if (($datos['pertenece_pueblo_originario'] == 'N' || $datos['pertenece_pueblo_originario'] == 'X')) {
				$pertenece_pueblo_originario = $datos['pertenece_pueblo_originario'];
				$pueblo_originario = null;
				$pueblo_originario_otro = null;
			} else {					
				$pertenece_pueblo_originario = ($datos['pertenece_pueblo_originario'] == 'null') ? null : $datos['pertenece_pueblo_originario'];
				$pueblo_originario = ($datos['pueblo_originario'] == 'null') ? null : $datos['pueblo_originario'];
				$pueblo_originario_otro = ($datos['pueblo_originario_otro'] == 'null') ? null : $datos['pueblo_originario_otro'];
			}                        
			$datos_personales = array(
                //identidad y diversidad de genero
				'identidad_genero'	=>($datos['identidad_genero'] == 'null') ? null : $datos['identidad_genero'],
				'identidad_genero_otro'	=> ($datos['identidad_genero_otro'] == 'null') ? null : $datos['identidad_genero_otro'],
				'apellido_elegido'	=> ($datos['apellido_elegido'] == 'null') ? null : $datos['apellido_elegido'],
				'nombres_elegido'	=> ($datos['nombres_elegido'] == 'null') ? null : $datos['nombres_elegido'],
				//pertenece_pueblo_originario
				'pertenece_pueblo_originario'	=> $pertenece_pueblo_originario,
				'pueblo_originario'				=> $pueblo_originario,
				'pueblo_originario_otro'		=> $pueblo_originario_otro
			);

			$cuil = kernel::persona()->get_cuil();
                        // Si se envia CUIL y no tiene uno ya asignado en la tabla 'mdp_personas_documentos' entonces lo guardo
			if(!empty($datos['cuil']) && !isset($cuil['nro_documento'])) {
				// Se inserta documento principal
				$datos_documento = array(
					'persona' => $this->persona,
					'pais_documento' => \persona::pais_argentina,
					'tipo_documento' => \persona::doc_CUIL,
					'nro_documento' => $datos['cuil'],
				);
				abm::alta('mdp_personas_documentos', $datos_documento);
			}
			abm::modificacion('mdp_personas', $datos_personales, array('persona' => $this->persona));
		}
		$datos = $this->filtrar_datos_censales($datos);
		$function = 'guardar_' . $seccion;
		$this->$function($datos);
		
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_censales', array('persona' => $this->persona));
	}

	function evt__guardar_datos_salud($datos)
	{
		$datos = $this->filtrar_datos_censales($datos);
		$this->guardar_datos_personales($datos);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_censales', array('persona' => $this->persona));
	}

	function evt__guardar_preferencias($datos)
	{
		$this->controlar_tablas();

		$datos = $this->filtrar_datos_censales($datos);
		$clave = array('dato_censal' => $this->get_id_dato_censal());

		$pref_institucion = array(
			'mot_economico' => $datos['mot_economico'],
			'mot_prestigio' => $datos['mot_prestigio'],
			'mot_difusion' => $datos['mot_difusion'],
			'mot_recomendacion_estudiantes' => $datos['mot_recomendacion_estudiantes'],
			'mot_recomendacion_amigos' => $datos['mot_recomendacion_amigos'],
			'mot_sistema_ingreso' => $datos['mot_sistema_ingreso'],
			'mot_ubicacion' => $datos['mot_ubicacion'],
			'mot_otros_i' => $datos['mot_otros_i'],
			'como_conocio_institucion' => $datos['como_conocio_institucion'],
			'insc_otra_institucion' => $datos['insc_otra_institucion'],
			'insc_otra_institucion_desc' => $datos['insc_otra_institucion_desc'],
		);
		abm::modificacion('mdp_eleccion_institucion', $pref_institucion, $clave);

		$pref_propuesta = array(
			'mot_vocacion' => $datos['mot_vocacion'],
			'mot_sugerencia_familiar' => $datos['mot_sugerencia_familiar'],
			'mot_reconocimiento_social' => $datos['mot_reconocimiento_social'],
			'mot_insercion_laboral' => $datos['mot_insercion_laboral'],
			'mot_perfil_profesional' => $datos['mot_perfil_profesional'],
			'mot_mejora_economica' => $datos['mot_mejora_economica'],
			'mot_util_sociedad' => $datos['mot_util_sociedad'],
			'mot_otros_p' => $datos['mot_otros_p'],
		);
		abm::modificacion('mdp_eleccion_propuesta', $pref_propuesta, $clave);
		
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_censales', array('persona' => $this->persona));
	}

	protected function guardar_datos_personales($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_personales', $datos, $clave);
	}

	protected function guardar_domicilio($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_personales', $datos, $clave);
	}

	protected function guardar_financiamiento($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_economicos', $datos, $clave);
	}

	protected function guardar_datos_laborales($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_economicos', $datos, $clave);
	}

	protected function guardar_tecnologia($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_actividades', $datos, $clave);
	}

	protected function guardar_deportes($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_actividades', $datos, $clave);
	}

	protected function guardar_idiomas($datos)
	{
		$clave = array('dato_censal' => $this->get_id_dato_censal());
		abm::modificacion('mdp_datos_actividades', $datos, $clave);
	}

	//--------------------------------------------------------------------------
	//---- Guardado en ABM's
	//--------------------------------------------------------------------------

	function evt__guardar_edicion($seccion, $datos)
	{
		$function = 'guardar_' . $seccion;
		$datos = $this->filtrar_datos_comunes($datos, 'fecha_desde', 'fecha_hasta');
		$this->$function($datos);
	}

	//--------------------------------------------------------------------------
	
	protected function guardar_actuacion_docente($datos)
	{
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			$datos['persona'] = $this->persona;
			abm::alta('mdp_antecedentes_docentes', $datos);
		} else {
			$id = $datos[catalogo::id];
			$antecedentes = $this->info__antecedentes_docentes();
			$antecedente = $antecedentes[$id];
			$clave = array('antecedente_docente' => $antecedente['antecedente_docente']);
			unset($datos[catalogo::id]);
			abm::modificacion('mdp_antecedentes_docentes', $datos, $clave);
		}
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_antecedentes_docentes', array('persona' => $this->persona));
	}

	protected function guardar_actuacion_profesional($datos)
	{
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			$datos['persona'] = $this->persona;
			abm::alta('mdp_antecedentes_profesionales', $datos);
		} else {
			$id = $datos[catalogo::id];
			$antecedentes = $this->info__antecedentes_profesionales();
			$antecedente = $antecedentes[$id];
			$clave = array('antecedente_profesional' => $antecedente['antecedente_profesional']);
			unset($datos[catalogo::id]);
			abm::modificacion('mdp_antecedentes_profesionales', $datos, $clave);
		}
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_antecedentes_profesionales', array('persona' => $this->persona));
	}

	protected function guardar_datos_contacto($datos)
	{          
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			$datos['persona'] = $this->persona;
			abm::alta('mdp_personas_contactos', $datos);
		} else {
			$id = $datos[catalogo::id];
			$antecedentes = $this->info__datos_contacto();
			$antecedente = $antecedentes[$id];
			$clave = array('persona_contacto' => $antecedente['persona_contacto']);
			unset($datos[catalogo::id]);
			abm::modificacion('mdp_personas_contactos', $datos,$clave);
		}
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_contactos', array('persona' => $this->persona));
	}

	protected function guardar_allegados($datos)
	{
		unset($datos['pais']);
		unset($datos['localidad_descr']);
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			$datos['dato_censal'] = $this->get_id_dato_censal();
			abm::alta('mdp_personas_allegados', $datos);
		} else {
			$id = $datos[catalogo::id];
			$antecedentes = $this->info__allegados();
			$antecedente = $antecedentes[$id];
			$clave = array('dato_allegado' => $antecedente['dato_allegado']);
			unset($datos[catalogo::id]);
			abm::modificacion('mdp_personas_allegados', $datos, $clave);
		}
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_allegados', array('persona' => $this->persona));
	}

	protected function guardar_datos_estudios($datos)
	{
		$datos = $this->filtrar_datos_estudios($datos);

		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			$datos['persona'] = $this->persona;
			abm::alta('mdp_datos_estudios', $datos);
		} else {
			$id = $datos[catalogo::id];
			$antecedentes = $this->info__datos_estudios();
			$antecedente = $antecedentes[$id];
			$clave = array('dato_estudio' => $antecedente['dato_estudio']);
			unset($datos[catalogo::id]);
			abm::modificacion('mdp_datos_estudios', $datos, $clave);
		}
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_estudios', array('persona' => $this->persona));
	}
	
	protected function guardar_discapacidad($datos)
	{
		$this->limpiar_discapacidad($datos);
		$clave = ['dato_censal' => $this->get_id_dato_censal()];
		abm::modificacion('mdp_datos_discapacidad', $datos, $clave);
	}
	
	protected function limpiar_discapacidad(&$datos)
	{
		if ($datos['condicion_discapacidad'] == \comunes::no) {
			$datos['tiene_cud'] = \comunes::no;
			$datos['disc_auditiva'] = \comunes::no;
			$datos['disc_visual'] = \comunes::no;
			$datos['disc_motora'] = \comunes::no;
			$datos['disc_cond_psicosocial'] = \comunes::no;
			$datos['disc_otra'] = \comunes::no;
			$datos['informacion_importante'] = "";
		}
		
		$this->limpiar_discapacidad_auditiva($datos);
		$this->limpiar_discapacidad_visual($datos);
		$this->limpiar_discapacidad_motora($datos);
		$this->limpiar_discapacidad_psicosocial($datos);
		$this->limpiar_discapacidad_otras($datos);
	}
	
	protected function limpiar_discapacidad_auditiva(&$datos)
	{
		if ($datos['disc_auditiva'] == \comunes::no) {
			$datos['aud_tipo'] = \persona::NO_HAY_DIFICULTAD_VAL;
			$datos['aud_lenguaje_senias'] = \comunes::no;
			$datos['aud_lenguaje_labial'] = \comunes::no;
			$datos['aud_comunicacion_otra'] = "";
			$datos['aud_apoyo_vida_diaria'] = "";
			$datos['aud_req_interprete_lengua_senias'] = \comunes::no;
			$datos['aud_req_aro_magnetico'] = \comunes::no;
			$datos['aud_req_otros_apoyos'] = \comunes::no;
			$datos['aud_otros_apoyos'] = "";
		}
		
		if ($datos['aud_req_otros_apoyos'] == \comunes::no) {
			$datos['aud_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_visual(&$datos)
	{
		if ($datos['disc_visual'] == \comunes::no) {
			$datos['vis_dificultad_para_ver'] = \persona::NO_HAY_DIFICULTAD_VAL;
			$datos['vis_apoyo_vida_diaria'] = "";
			$datos['vis_req_archivo_audio'] = \comunes::no;
			$datos['vis_req_texto_digital'] = \comunes::no;
			$datos['vis_req_texto_braile'] = \comunes::no;
			$datos['vis_req_otros_apoyos'] = \comunes::no;
			$datos['vis_otros_apoyos'] = "";
		}
		
		if ($datos['vis_req_otros_apoyos'] == \comunes::no) {
			$datos['vis_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_motora(&$datos)
	{
		if ($datos['disc_motora'] == \comunes::no) {
			$datos['mot_apoyo_vida_diaria'] = "";
			$datos['mot_req_apoyo_aula'] = \comunes::no;
			$datos['mot_apoyo_aula'] = "";
			$datos['mot_req_otros_apoyos'] = \comunes::no;
			$datos['mot_otros_apoyos'] = "";
		}
		
		if ($datos['mot_req_apoyo_aula'] == \comunes::no) {
			$datos['mot_apoyo_aula'] = "";
		}
		
		if ($datos['mot_req_otros_apoyos'] == \comunes::no) {
			$datos['mot_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_psicosocial(&$datos)
	{
		if ($datos['disc_cond_psicosocial'] == \comunes::no) {
			$datos['psi_descripcion'] = "";
			$datos['psi_apoyo_vida_diaria'] = "";
			$datos['psi_req_otros_apoyos'] = \comunes::no;
			$datos['psi_otros_apoyos'] = "";
		}
		
		if ($datos['psi_req_otros_apoyos'] == \comunes::no) {
			$datos['psi_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_otras(&$datos)
	{
		if ($datos['disc_otra'] == \comunes::no) {
			$datos['otra_descripcion'] = "";
			$datos['otra_req_apoyo_vida_diaria'] = \comunes::no;
			$datos['otra_apoyo_vida_diaria'] = "";
		}
		
		if ($datos['otra_req_apoyo_vida_diaria'] == \comunes::no) {
			$datos['otra_apoyo_vida_diaria'] = "";
		}
	}
	
	//--------------------------------------------------------------------------
	
		protected function filtrar_datos_estudios($datos)
	{
		if ((empty($datos['institucion']) && (!empty($datos['institucion_descr'])))) {
			$datos['institucion_otra'] = $datos['institucion_descr'];
		} else {
			$datos['institucion_otra'] = null;
		}
		unset($datos['institucion_descr']);

		if ((empty($datos['colegio']) && (!empty($datos['colegio_descr'])))) {
			$datos['colegio_otro'] = $datos['colegio_descr'];
		} else {
			$datos['colegio_otro'] = null;
		}
		unset($datos['colegio_descr']);

		if ((empty($datos['titulo']) && (!empty($datos['titulo_descr'])))) {
			$datos['titulo_otro'] = $datos['titulo_descr'];
		} else {
			$datos['titulo_otro'] = null;
		}
		unset($datos['titulo_descr']);

		return $this->filtrar_datos_comunes($datos, 'fecha_ingreso', 'fecha_egreso');
	}

	//--------------------------------------------------------------------------
	//---- Eliminación en ABM's
	//--------------------------------------------------------------------------

	function evt__eliminar($seccion, $seleccion)
	{
		$function = 'eliminar_' . $seccion;
		$this->$function($seleccion);
	}

	protected function eliminar_actuacion_docente($seleccion)
	{
		$antecedentes = $this->info__antecedentes_docentes();
		$antecedente = $antecedentes[$seleccion];
		$clave = array('antecedente_docente' => $antecedente['antecedente_docente']);
		abm::baja('mdp_antecedentes_docentes', $clave);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_antecedentes_docentes', array('persona' => $this->persona));
	}

	protected function eliminar_actuacion_profesional($seleccion)
	{
		$antecedentes = $this->info__antecedentes_profesionales();
		$antecedente = $antecedentes[$seleccion];
		$clave = array('antecedente_profesional' => $antecedente['antecedente_profesional']);
		abm::baja('mdp_antecedentes_profesionales', $clave);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_antecedentes_profesionales', array('persona' => $this->persona));
	}

	protected function eliminar_datos_contacto($seleccion)
	{
		$contactos = $this->info__datos_contacto();
		$contacto = $contactos[$seleccion];
		$clave = array('persona_contacto' => $contacto['persona_contacto']);
		abm::baja('mdp_personas_contactos', $clave);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_contactos', array('persona' => $this->persona));
	}

	protected function eliminar_allegados($seleccion)
	{
		$allegados = $this->info__allegados();
		$allegado = $allegados[$seleccion];
		$clave = array('dato_allegado' => $allegado['dato_allegado']);
		abm::baja('mdp_personas_allegados', $clave);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_allegados', array('persona' => $this->persona));
	}

	protected function eliminar_datos_estudios($seleccion)
	{
		$datos = $this->info__datos_estudios();
		$dato = $datos[$seleccion];
		$clave = array('dato_estudio' => $dato['dato_estudio']);
		abm::baja('mdp_datos_estudios', $clave);
		// Invalidar caché
		catalogo::limpiar_cache('datos_censales', 'get_datos_estudios', array('persona' => $this->persona));
	}

	//--------------------------------------------------------------------------

	protected function filtrar_datos_censales($datos)
	{
		//---- Filtrado de datos -----------------------------------------------
		$campos = util_form_censo::get_campos_dc();
		$salida = [];
		$no_capitalizar = ['como_conocio_institucion', 'insc_otra_institucion_desc'];

		foreach ($datos as $campo => $dato) {
			if (in_array($campo, $campos)) {
				if (is_null($dato) || ($dato == form_censo::NO_SELECCION)) {
					$dato = null;
				}
				
				$dato = a_minusculas($dato);
				if (!in_array($campo, $no_capitalizar)) {
					// Se capitaliza la primera letra de cada palabra
					$dato = capitalizar($dato);
				}
				$salida[$campo] = $dato;
			}
		}

		return $salida;
	}

	protected function filtrar_datos_comunes($datos, $campo_fecha_desde = '', $campo_fecha_hasta = '')
	{
		//---- Filtrado de datos -----------------------------------------------
		$salida = [];
		$no_capitalizar = [catalogo::id, 'institucion_otra', 'materia', 'empresa', 'contacto_tipo', 'email', 'observaciones',
			'pertenece_pueblo_originario', 'pueblo_originario', 'razon_abandono', 'razon_abandono_otros','otros_contactos'];

		foreach ($datos as $campo => $dato) {
			if (is_null($dato) || ($dato == form_censo::NO_SELECCION)) {
				$dato = null;
			}
			
			if (!in_array($campo, $no_capitalizar)) {
				$dato = a_minusculas($dato);
				// Se capitaliza la primera letra de cada palabra
				$dato = capitalizar($dato);
			}
			$salida[$campo] = $dato;
		}

		if ((!empty($campo_fecha_desde)) && (!empty($datos[$campo_fecha_desde]))) {
			$salida[$campo_fecha_desde] = convertir_fecha_formato_base($datos[$campo_fecha_desde]);
		}
		if ((!empty($campo_fecha_hasta)) && (!empty($datos[$campo_fecha_hasta]))) {
			$salida[$campo_fecha_hasta] = convertir_fecha_formato_base($datos[$campo_fecha_hasta]);
		}

		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//----	INFO ---------------------------------------------------------------
	//--------------------------------------------------------------------------

	function info__fecha_acutalizacion_dc()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_ultima_actualizacion_dc', $parametros);
	}

	function info__datos_censales()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_datos_censales', $parametros);
	}
        function opciones__tipos_documentos()
	{
		$datos = \toba::consulta_php('co_personas')->get_tipos_documentos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	function opciones__generos()
	{
		$datos = \toba::consulta_php('co_personas')->get_sexos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	function info__descr_localidad($localidad)
	{
		$parametros = array('localidad' => $localidad);
		return catalogo::consultar('datos_censales', 'get_descr_localidad', $parametros);
	}

	function info__antecedentes_docentes()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_antecedentes_docentes', $parametros);
	}

	function info__antecedentes_profesionales()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_antecedentes_profesionales', $parametros);
	}

	function info__datos_contacto()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_datos_contactos', $parametros);
	}

	function info__allegados()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_allegados', $parametros);
	}

	function info__datos_estudios()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('datos_censales', 'get_datos_estudios', $parametros);
	}

	//--------------------------------------------------------------------------
	//---- Opciones ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function armar_combo_opciones($datos, $campo_clave, $campo_valor, $con_no_seleccion = true, $omitir_no_seleccion_opcion_unica = true)
	{
		$combo = array();
		if (!empty($datos)) {
			if (($con_no_seleccion) && ((\guarani_arreglos::count($datos) > 1) || ((\guarani_arreglos::count($datos) <= 1) && !$omitir_no_seleccion_opcion_unica) )) {
				$combo[form_censo::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
			}
			foreach ($datos as $dato) {
				$combo[$dato[$campo_clave]] = $dato[$campo_valor];
			}
		}
		return $combo;
	}

	//--------------------------------------------------------------------------

	function opciones__estados_civiles()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_estados_civiles();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
    function opciones__identidad_genero()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_identidades_genero();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__cantidades_hijos()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_cantidades_hijos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__cantidades_familia()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_cantidades_familia();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__situacion_padres()
	{
		$datos = \toba::consulta_php('co_comunes', true)->get_vive_novive_desconoce();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__si_no_no_desea_contestar()
	{
		$datos = \toba::consulta_php('co_comunes', true)->get_si_no_no_desea_responder();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__pueblos_originarios()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_opciones_pueblos_originarios();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__turnos()
	{
		$datos = \toba::consulta_php('co_comisiones', true)->get_listado_turnos_cursadas();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__cobertura_salud()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_coberturas_salud();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__localidades_autocomplete($localidad_nombre = '', $pais = null)
	{
		$parametros = array('localidad_nombre' => $localidad_nombre, 'pais' => $pais);
		return catalogo::consultar('datos_censales', 'get_localidades_autocomplete', $parametros);
	}

	function opciones__paises()
	{
		$datos = \toba::consulta_php('co_mug', true)->get_paises();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}

	function opciones__provincias($pais)
	{
		$datos = \toba::consulta_php('co_mug', true)->get_provincias_pais($pais);
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__departamentos($provincia)
	{
		$datos = \toba::consulta_php('co_mug', true)->get_departamentos_provincia($provincia);
		return $this->armar_combo_opciones($datos, 'dpto_partido', 'nombre', true, false);
	}

	function opciones__localidades($departamento)
	{
		$datos = \toba::consulta_php('co_mug', true)->get_localidades_departamento($departamento);
		return $this->armar_combo_opciones($datos, 'localidad', 'nombre', true, false);
	}

	function opciones__cod_postales($localidad)
	{
		$datos = \toba::consulta_php('co_mug', true)->get_codigos_postales($localidad);
		return $this->armar_combo_opciones($datos, 'codigo_postal', 'codigo_postal', true, true);
	}

	function opciones__tipos_vivienda()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_tipos_viviendas();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__vive_con()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_vive_con();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__regularidad_acceso_internet()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_regularidades_internet();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__niveles_idiomas()
	{
		$datos = \toba::consulta_php('co_datos_censales', true)->get_niveles_idioma();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__trabajo_existe()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_trabajos_existen();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__trabajo_hace()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_trabajos_hacen();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__trabajo_ocupacion()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_trabajos_ocupaciones();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__trabajo_hora_sem()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_trabajos_horas_semanales();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__trabajo_carrera()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_trabajos_carreras();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__instituciones()
	{
		$datos = \toba::consulta_php('co_instituciones', true)->get_instituciones();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__tipos_allegado()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_tipos_allegados();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__estudio_alcanzado()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_estudios_alcanzados();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__niveles_estudio()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_niveles_de_estudio();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__instituciones_autocomplete($nombre = '')
	{
		$parametros = array('nombre' => $nombre);
		return catalogo::consultar('datos_censales', 'get_instituciones_autocomplete', $parametros);
	}

	function opciones__colegios_autocomplete($nombre = '')
	{
		$parametros = array('nombre' => $nombre);
		return catalogo::consultar('datos_censales', 'get_colegios_autocomplete', $parametros);
	}

	function opciones__titulos_autocomplete($nombre = '', $tipo)
	{
		$parametros = array('nombre' => $nombre, 'tipo' => $tipo);
		return catalogo::consultar('datos_censales', 'get_titulos_autocomplete', $parametros);
	}

	function opciones__estados_estudio()
	{
		$datos = \toba::consulta_php('co_comunes', true)->get_estado_finalizado_abandonado();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__razones_abandono_estudio()
	{
		$datos = \toba::consulta_php('co_personas', true)->get_razones_abandono_estudios();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	function opciones__anios_egreso()
	{
		$datos = \toba::consulta_php('co_comunes', true)->get_anios();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}
	
	function opciones__condicion_discapacidad()
	{
		$datos = \toba::consulta_php('co_capacidades_especiales', true)->get_condicion_discapacidad();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', false, false);
	}
	
	function opciones__dificultades()
	{
		$datos = \toba::consulta_php('co_capacidades_especiales', true)->get_dificultades();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', false, false);
	}

}
