<?php 

class regla_correlativas_de_cursada extends regla
{
	/**
	 * Se redefine el set de parámetros de contexto de regla_nucleo.php para considerar el caso especial de las
	 * operaciones de Equivalencias, donde se pasa un parámetro adicional, correspondiente a las Actividades 
	 * Otorgadas como aprobadas en el trámite, que no debe considerarse en el resto de las operaciones al 
	 * evaluar esta regla.
	 */
	function set_parametros_contexto($parametros_contexto=array())
	{
		if (!isset($parametros_contexto['equivalencias_otorgadas'])) {
			$parametros_contexto['equivalencias_otorgadas'] = array();
		}
		parent::set_parametros_contexto($parametros_contexto);
	}
	
	/**
	 * Verifica si en el parámetro de actividades otorgadas con resultado aprobado del trámite de equivalencia
	 * para el cual se están controlando correlativas, se encuentra el elemento deseado otorgado con instancia
	 * total (actividad aprobada).
	 * @param int $elemento actividad para la que se desea verificar si está aprobada
	 * @param array $activ_otorgadas parámetro de actividades otorgadas con resultado aprobado en el trámite 
	 * de equivalencias para el cual se están chequeando correlativas
	 * @return boolean true si la actividad está otorgada como aprobada (resultado aprobado e instancia total) 
	 * dentro de las actividades del trámite de equivalencias.
	 */
	function actividad_aprobada_en_otorgadas($elemento, $activ_otorgadas)
	{
		$aprobada = isset($activ_otorgadas[$elemento]) && $activ_otorgadas[$elemento] == equivalencia::equiv_instancia_total;
		return $aprobada;
	}
	
	/**
	 * Verifica si en el parámetro de actividades otorgadas con resultado aprobado del trámite de equivalencia
	 * para el cual se están controlando correlativas, se encuentra el elemento deseado otorgado con instancia
	 * regularidad (actividad cursada).
	 * @param int $elemento actividad para la que se desea verificar si está cursada
	 * @param array $activ_otorgadas parámetro de actividades otorgadas con resultado aprobado en el trámite 
	 * de equivalencias para el cual se están chequeando correlativas
	 * @return boolean true si la actividad está otorgada como cursada (resultado aprobado e instancia regularidad) 
	 * dentro de las actividades del trámite de equivalencias.
	 */
	function actividad_cursada_en_otorgadas($elemento, $activ_otorgadas)
	{
		$cursada = isset($activ_otorgadas[$elemento]) && $activ_otorgadas[$elemento] == equivalencia::equiv_instancia_regularidad;
		$cursada_para_correlativas = isset($activ_otorgadas[$elemento]) && $activ_otorgadas[$elemento] == equivalencia::equiv_instancia_regularidad_correlativas;
		$aprobada = isset($activ_otorgadas[$elemento]) && $activ_otorgadas[$elemento] == equivalencia::equiv_instancia_total;
		return $cursada || $cursada_para_correlativas || $aprobada;
	}
	
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$elemento = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');
		$activ_otorgadas = $this->get_parametro('equivalencias_otorgadas');

		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$persona = $datos_alumno['persona'];
		$propuesta = $datos_alumno['propuesta'];
		$plan_version = $this->get_parametro('plan_version');

		$grupos = guarani::condiciones()->get_opciones_elemento_plan_version_tipo($elemento, $plan_version, condicion::tipo_cursar);

		$grupo_cumplido = true;
		$cant_requisitos_no_cumplidos = 0;
		$txt_requisitos_no_cumplidos = ''; 
        
        $mensaje = '';
        
		foreach ($grupos as $grupo) {
			$requisitos = guarani::condiciones()->get_requisitos_opcion($grupo['grupo_condicion']);
			$grupo_cumplido = true;
			foreach ($requisitos as $requisito) {
				$req_valido  = false;
				/** Si la regla a controlar es Actividad Aprobada ó Actividad Cursada se verifica en primer 
				 *  instancia si la actividad se está dando como aprobada ó cursada, según corresponda, dentro 
				 *  de las actividades otorgadas en caso de Equivalencias.
				 */		
				if (!empty($activ_otorgadas)) {
					if ($requisito['regla'] == regla::regla_actividad_aprobada) {
						$req_valido = $this->actividad_aprobada_en_otorgadas($requisito['elemento'], $activ_otorgadas);
					} elseif ($requisito['regla'] == regla::regla_actividad_cursada) {
						$req_valido = $this->actividad_cursada_en_otorgadas($requisito['elemento'], $activ_otorgadas);
					}
				} 
				
				// Si se está controlando otra regla, o si la actividad no estaba cursada o aprobada dentro
				// de las mismas actividades otorgadas en el trámite de equivalencias, se valida la regla.
				if (!$req_valido) {                    
					$regla = guarani::regla($requisito['regla']);
					$regla->set_parametros($requisito['parametros']);
					$regla->set_operador_not($requisito['operador_not']);
					$regla->set_parametros_contexto(array('alumno' => $alumno,
														  'persona' => $persona,
														  'propuesta' => $propuesta,
														  'plan_version' => $plan_version,
														  'elemento' => $requisito['elemento'],
														  'elemento_inscripcion' => $elemento,  
														  'requisito' => $requisito['requisito'],
														  'fecha' => $fecha));
					
					// Valido el requisito.
					$req_valido = $regla->validar();
					
					// ---------------------------------------------------------------------------------------------
					// Si no se cumplio la regla y es la regla de tener la actividad regularizada (regla = 2)
					// Verifico si tiene una equivalencia de regularidad solo para correlativas (instancia 14)
					// ---------------------------------------------------------------------------------------------
					if (!$req_valido && $requisito['regla'] == 2 ) {
						$req_valido = toba::consulta_php('co_alumnos')->actividad_regularizada_en_equivalencia_para_correlativas($alumno, $requisito['elemento'], $fecha);
					}	
					
				}
				
				if (!$req_valido) {
					// La regla evaluada no se cumplió. Informo el mensaje de la regla.
					$grupo_cumplido = false;
					$cant_requisitos_no_cumplidos++;
					// Se detallan hasta 5 requisitos no cumplidos...
					if ($cant_requisitos_no_cumplidos <= 5) {
						if ($requisito['tipo'] == condicion::requisito_entidad) {
							$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($requisito['elemento'], $plan_version);
							
							$estado_actividad_desc = current(toba::consulta_php('co_g3entidades')->get_estados("estado = '" . $requisito['estado'] . "'"));
							$requisito_mensaje = $datos_basicos_elemento['nombre'] . " (" . $estado_actividad_desc['estado_nombre'] . ")";
						} else {                            
							// Obtengo el mensaje que informo al usuario dependiendo de la interfaz y si esta negado o no el requisito.
                            $requisito_mensaje = toba::consulta_php('co_reglas')->get_mensaje_usuario($requisito['regla'], $requisito['parametros']);                            
							if ($requisito_mensaje == '' || !$requisito_mensaje) {
                                $requisito_mensaje = $regla->get_mensaje_validacion()[1]['mensaje'];                                
                            }
						}
						$txt_requisitos_no_cumplidos .= ($cant_requisitos_no_cumplidos == 1) ? $requisito_mensaje : ", $requisito_mensaje";
					} else {
						$txt_requisitos_no_cumplidos .= ' y otras...';
						break;
					}
				}
			}
			if ($grupo_cumplido) {
				break;
			}
		}

		$resultado = $this->resultado_final($grupo_cumplido);

		if (!$resultado) {
			$nombre_alumno = $datos_alumno['apellido_y_nombre'];
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $txt_requisitos_no_cumplidos), true);
		}
		return $resultado;
	}
}