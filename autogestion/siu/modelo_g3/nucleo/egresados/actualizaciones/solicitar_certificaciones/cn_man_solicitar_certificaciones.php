<?php
/**
 *
 *
 * @package		Egresados
 * @subpackage	Actualizaciones
 * 
 */
class cn_man_solicitar_certificaciones extends cn_manejador_entidad
{
	protected $s__observaciones;
    protected $s__config_ha;
    
    function set_config_ha($config_ha)
	{
		$this->s__config_ha = $config_ha;
	}

	function get_config_ha()
	{
		return $this->s__config_ha;
	}
	
	function cargar_atributos_parametros($propuesta)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
	}
	
	function get_indice_instancia()
	{
		return 'solicitar_certificaciones';
	}

	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_inscriptos_certificado($filtro);
	}

	function get_listado_tramites_certificado($alumno)
    {
        $filtro = "	sga_certificados_otorg.alumno = $alumno 
					-- Trámites no anulados.
					AND sga_certificados_otorg.anulado = 0 
					AND
					(	-- Trámites sin estado (recien grabados)
						sga_certificados_otorg.estado IS NULL
						OR
							-- Trámites que no estén en estado final.
							EXISTS (SELECT	mce_caminos.camino
									FROM	mce_caminos
									WHERE	mce_caminos.estado_inicial = sga_certificados_otorg.estado AND
											mce_caminos.estado_final <> sga_certificados_otorg.estado	)
					)";

        // Obtengo los Trámites de Certificación
        $tramites = toba::consulta_php('co_tramites_certificacion')->get_listado($filtro);

        foreach ($tramites as $key => $tramite) {
            // Obtiene la descripción del Trámite de Certificación desde SUDOCU.
            $tramites[$key]['nro_expediente'] = sudocu::getDescripcionTramiteCertificacion($tramite);
        }

        return $tramites;
    }

    function get_orientaciones_cumplidas($alumno, $certificado, $plan_version)
	{
		$orientaciones = toba::consulta_php('co_certificados')->get_orientaciones_certificado($certificado, $plan_version);
	$orientaciones_cumplidas = array();
		foreach ($orientaciones as $orientacion) {
			$regla = guarani::regla($orientacion['regla']);
			$regla->set_parametros($orientacion['parametros']);
			$regla->set_parametros_contexto(array('elemento' => $orientacion['elemento'], 'alumno' => $alumno, 'plan_version' => $plan_version));
			if ($regla->validar()) {
				$orientaciones_cumplidas[] = $orientacion['elemento'];
			}
		}
		return $orientaciones_cumplidas;
	}


	function generar_solicitud()
	{
		try {
			toba::db()->abrir_transaccion();
			$tramite = $this->entidad()->get_datos();	

			$proceso_deteccion_egresado = toba::cn('cn_deteccion_egresado');
			$nro_solicitud = false;
			$estado_requisitos = tramite_certificacion::estado_requisitos_pendiente;
			if ($proceso_deteccion_egresado->validar($tramite['alumno'], $tramite['certificado'])) {
				// Se completan la fecha de egreso y los promedios.
				$tramite['fecha_egreso'] = toba::consulta_php('co_alumnos')->get_fecha_egreso($tramite['alumno'], null, $tramite['certificado']);
				// La fecha de egreso puede ser vacía cuando el certificado se obtiene sólo con requisitos (sin actividades/módulos).
				if (!empty($tramite['fecha_egreso'])) {
					$tramite['duracion_real_en_meses'] = toba::consulta_php('co_alumnos')->get_duracion_real_en_meses($tramite['alumno'], $tramite['fecha_egreso']);
				}
				$tramite['promedio'] = toba::consulta_php('co_alumnos')->get_promedio($tramite['alumno'], null, $tramite['certificado']);
				$tramite['promedio_sin_aplazos'] = toba::consulta_php('co_alumnos')->get_promedio($tramite['alumno'], null, $tramite['certificado'], comunes::no);

				$puntos_de_control =[guarani_punto_de_control::egreso];
				$parametros = array('alumno' => $tramite['alumno'],
							'plan_version' => $tramite['plan_version'],
							'certificado' => $tramite['certificado'],
							'fecha' =>$tramite['fecha_inicio_tramite']);
                $retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
				guarani::validador_puntos_de_control()->notificar();

				if ($retorno == guarani_punto_de_control::rechazado) {
					throw new guarani_error(null, null, guarani_mensajes::get('800CON_error_control_solicitud'));
				}

				$this->entidad()->set_datos($tramite);
				$this->guardar();
				$nro_solicitud = $this->entidad()->get_columna('nro_solicitud');
                /*
                 * Verifico el parametro para ver si tengo que quitar actividades excluidas
                 */                
                if (guarani::parametros()->get_valor('egr_configurar_ha') == comunes::si) {
                    $this->set_config_ha($proceso_deteccion_egresado->get_config_ha());
                    /**
                     * Se obtienen las actividades a excluir de la Hist. Acad. (si las hay)
                     */
                    $config_ha = toba::memoria()->get_dato('__config_ha__');
                    $excluidas = array();
                    foreach ($config_ha as $id => $actividad) {
                        if (isset($actividad['incluido_ha']) && (!$actividad['incluido_ha'])) {
                            $excluidas[] = $id;
                        }
                    }
                    $this->registrar_orientaciones_config_ha($nro_solicitud, $tramite['alumno'], $tramite['certificado'], $tramite['plan_version'], $excluidas);
                } else {
                    $this->registar_orientaciones_ha($nro_solicitud, $tramite['alumno'], $tramite['certificado'], $tramite['plan_version']);
                }

				$this->validar_pasar_a_pasivo($tramite['alumno'], $tramite['certificado']);

				//Se va a generar el suplemente según el parametro egr_generar_suplemento_automaticamente
				$this->generar_suplemento(['nro_solicitud' => $nro_solicitud, 'alumno' => $tramite['alumno']]);

				$this->insertar_requisitos_certificado($nro_solicitud, $tramite['plan_version'], $tramite['persona']);
				$estado_requisitos = $this->actualizar_estado_requisitos($nro_solicitud);
			} else {
				$this->s__observaciones = $proceso_deteccion_egresado->get_observaciones();
                $this->set_config_ha($proceso_deteccion_egresado->get_config_ha());
			}			
			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}

		return array($nro_solicitud, $estado_requisitos);		
	}
	
	/*
	 * Se verifica el parametro, si esta en si se genera el suplemento.
	 */
	function generar_suplemento($datos)
	{
		//Si esta en si se genera el suplemento
		if (guarani::parametros()->get_valor('egr_generar_suplemento_automaticamente') == comunes::si) {
			alumno::generar_suplementos($datos);
		}
	}
	
    /*
     * Verifica si el nivel del titulo es final.
     */
	function es_titulo_final($certificado)
	{
		$datos = toba::consulta_php('co_certificados')->get_certificado($certificado);
		return $datos['titulo_nivel'] == tramite_certificacion::nivel_titulo_final;
	}
	
	function get_observaciones()
	{
		return $this->s__observaciones;
	}
    
    /*
     * Verifica el parametro de pasar a pasivo cuando se egresa al alumno
     */
    function validar_pasar_a_pasivo($alumno, $certificado)
    {
        if (guarani::parametros()->get_valor('egr_pasar_a_pasivo') == comunes::si && $this->es_titulo_final($certificado)) {
            // Se cambia la calidad del alumno a "PASIVO".
            guarani::act('act_alumnos')->actualizar_calidad_alumno($alumno, alumno::calidad_pasivo);
            guarani::act('act_alumnos')->insertar_cambio_calidad($alumno, alumno::calidad_pasivo, alumno::motivo_calidad_egreso);
        }
    }
    
    /*
     * Registra la orientacion y la historia academica cumplida por el alumno.
     * cuando no se configura la HA.
     */
    function registar_orientaciones_ha($nro_solicitud, $alumno, $certificado, $plan_version)
    {
        // Se registran las orientaciones que el alumno cumplió.
        $orientaciones_cumplidas = $this->get_orientaciones_cumplidas($alumno, $certificado, $plan_version);
        guarani::act('act_tramites_certificacion')->registrar_orientaciones_cumplidas($nro_solicitud, $orientaciones_cumplidas);

        // Se registra una "foto" de la historia académica del alumno.
        guarani::act('act_tramites_certificacion')->registrar_historia_academica($alumno, $nro_solicitud);
    }
    
    /*
     * Registra la orientacion y la historia academica cumplida por el alumno.
     * cuando se configura la HA.
     */
    function registrar_orientaciones_config_ha($nro_solicitud, $alumno, $certificado, $plan_version, $excluidas)
    {
        //if (isset($this->s__seleccion)) {
        if ($this->entidad()->es_nueva()) {
            // Si se trata de una edición, se eliminan las orientaciones que el alumno cumplió.
            guarani::act('act_tramites_certificacion')->eliminar_orientaciones_cumplidas($nro_solicitud);
        }
        $orientaciones_cumplidas = $this->get_orientaciones_cumplidas($alumno, $certificado, $plan_version);
        // Se registran las orientaciones que el alumno cumplió.
        guarani::act('act_tramites_certificacion')->registrar_orientaciones_cumplidas($nro_solicitud, $orientaciones_cumplidas);

        // Se registra una "foto" de la historia académica del alumno.
        guarani::act('act_tramites_certificacion')->registrar_historia_academica($alumno, $nro_solicitud, $excluidas);

        // Si hay un trámite seleccionado => 
        //if (isset($this->s__seleccion)) {
        if ($this->entidad()->es_nueva()) {
            // Si se trata de una edición, se elimina la configuración de H.A. anterior
            guarani::act('act_tramites_certificacion')->eliminar_configuracion_ha($nro_solicitud);
        }

        // Si hay actividades a excluir de la H.A., se las registra
        if (!empty($excluidas)) {
            $certificado_ha = guarani::act('act_tramites_certificacion')->insertar_configuracion_ha($nro_solicitud);
            foreach ($excluidas as $actividad_excluida) {
                guarani::act('act_tramites_certificacion')->insertar_actividad_excluida_ha($certificado_ha, $actividad_excluida);
            }
        }
    }

	function insertar_requisitos_certificado($nro_solicitud, $plan_version, $persona)
	{
		$requisitos = toba::consulta_php('co_requisitos')->get_requisitos_egreso_persona_plan_version($persona, $plan_version);
		if (!empty($requisitos)) {
			foreach ($requisitos as $requisito) {
				guarani::act('act_requisitos_ingreso')->insertar_requisito_certificado($nro_solicitud, $requisito['requisito']);
			}
		}
	}

	function actualizar_estado_requisitos($nro_solicitud)
	{
		$estado_requisitos = tramite_certificacion::estado_requisitos_pendiente;
		if (!toba::consulta_php('co_tramites_certificacion')->solicitud_tiene_requisitos_egreso($nro_solicitud)) {
			$estado_requisitos = tramite_certificacion::estado_requisitos_aceptado;
			guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($nro_solicitud, $estado_requisitos);
		}
		return $estado_requisitos;
	}
}