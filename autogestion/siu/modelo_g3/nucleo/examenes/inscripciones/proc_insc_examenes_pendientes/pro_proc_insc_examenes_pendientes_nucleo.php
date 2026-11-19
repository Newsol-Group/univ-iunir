<?php
/**
 *
 * @todo 		Manejo de errores en controles configurables.
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */
class pro_proc_insc_examenes_pendientes_nucleo extends guarani_procesos_bk
{
	protected $inscripciones;

	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;

	const reporte_aceptadas = 1;
	const reporte_a_rechazar = 2;
	const reporte_con_observaciones = 3;
	const reporte_resultados = 4;

	const nombre = 'pro_proc_insc_examenes_pendientes';

	function set_datos($datos=array())
	{
		$this->inscripciones = $datos['inscripciones'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->inscripciones);
		$cant_aceptadas = 0;
		$cant_a_rechazar = 0;
		$cant_con_observaciones = 0;

		$filtro = guarani_sql::armar_condicion_in($this->inscripciones, 'sga_insc_examen', 'inscripcion');
		$datos_inscripciones = guarani::co('co_inscripciones_examenes')->get_listado_inscripciones($filtro);
		$datos_inscripciones = rs_convertir_asociativo_matriz($datos_inscripciones, array('inscripcion'));

		$exa_notificar_estado_inscripcion = guarani::parametros()->get_valor('exa_notificar_estado_inscripcion', null, false);
		
		foreach ($datos_inscripciones as $inscripcion) {
            // Si desde la aplicación se mandó señal de corte, no se continúa con 
			// el procesamiento (lo procesado hasta el momento no se revierte).
			
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
                    
			$retorno = guarani_punto_de_control::autorizado;

			if (inscripcion_examen::ejecutar_controles($inscripcion['propuesta'])) {
				$retorno = $this->validar_puntos_de_control_inscripcion_alumno($inscripcion);
				$mensajes = $retorno['mensajes'];
				$requisitos = $retorno['requisitos'];
				$retorno = $retorno['retorno'];
			}

			switch ($retorno) {
				case guarani_punto_de_control::autorizado:
					try {
						guarani::act('act_inscripcion_examenes')->aceptar_inscripciones($inscripcion['inscripcion']);
						$this->log->set($inscripcion, array(self::reporte_aceptadas));
						$cant_aceptadas++;
						
						if($exa_notificar_estado_inscripcion == comunes::si) {
							$parametros['aceptada'] = true;
							$parametros['alumno'] = $inscripcion['alumno'];
							$parametros['fecha'] = $inscripcion['fecha_examen_visual'];
							$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_actividad($inscripcion['elemento']);
							$parametros['actividad'] = $datos_elemento['nombre'];
							guarani::notificador()->enviar(notificador::inscripciones_pendientes_a_examenes, $parametros);
						}
						
					} catch (toba_error $e) {
						$this->log_estado->set_error();
						$inscripcion['observaciones'] = $e->get_mensaje();
						$this->log->set($inscripcion, array(self::reporte_con_observaciones));
						$cant_a_rechazar++;
						
						if($exa_notificar_estado_inscripcion == comunes::si) {
							$parametros['aceptada'] = false;
							$parametros['alumno'] = $inscripcion['alumno'];
							$parametros['fecha'] = $inscripcion['fecha_examen_visual'];
							$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_actividad($inscripcion['elemento']);
							$parametros['actividad'] = $datos_elemento['nombre'];
							guarani::notificador()->enviar(notificador::inscripciones_pendientes_a_examenes, $parametros);
						}
					}
					break;
				case guarani_punto_de_control::advertido:
					$inscripcion['observaciones'] = $mensajes;
					$this->log->set($inscripcion, array(self::reporte_con_observaciones));
					$cant_con_observaciones++;
					break;
				case guarani_punto_de_control::rechazado:
					$inscripcion['observaciones'] = $mensajes;
					$inscripcion['requisitos'] = $requisitos;
					$this->log->set($inscripcion, array(self::reporte_a_rechazar));
					$cant_a_rechazar++;
					break;
			}

			$this->log->guardar();
			if (!$this->abortar) {
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		$mensaje_resultado['aceptadas'] = $cant_aceptadas;
		$mensaje_resultado['con_observaciones'] = $cant_con_observaciones;
		$mensaje_resultado['a_rechazar'] = $cant_a_rechazar;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function validar_puntos_de_control_inscripcion_alumno($datos_inscripcion)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$parametros = array('fecha'			=>	guarani_fecha::get_hoy(),
							'persona'		=>	$datos_inscripcion['persona'],
							'alumno'		=>	$datos_inscripcion['alumno'],
							'plan_version'	=>	$datos_inscripcion['plan_version'],
							'elemento'		=>	$datos_inscripcion['elemento'],
							'llamado_mesa'	=>	$datos_inscripcion['llamado_mesa'],
							'propuesta'		=>	$datos_inscripcion['propuesta']);

		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($datos_inscripcion['instancia']);
		$puntos_de_control = array(	guarani_punto_de_control::alumno_entidad,
									guarani_punto_de_control::persona_operacion,
									guarani_punto_de_control::alumno_operacion,
									guarani_punto_de_control::elegir_actividad_examen,
									guarani_punto_de_control::alumno_mesa_examen,
									guarani_punto_de_control::persona_propuesta);
		$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);

		$requisitos = implode('||', aplanar_matriz($mensajes, 'requisito'));
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		
		return array('retorno' => $retorno, 'mensajes' => $mensajes, 'requisitos'=>$requisitos);
	}
}
?>