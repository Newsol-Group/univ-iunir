<?php
class pro_emitir_constancia_cursos_nucleo extends guarani_procesos_bk
{
	protected $comision;
	protected $alumnos;
	protected $constancia;
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	protected $plan_version;
	protected $asunto;
	protected $mensaje;
	protected $usuario;
	protected $estado_solicitud;
	protected $personas;

	const nombre = 'pro_emitir_constancia_cursos_nucleo';
	const reporte_emitidos = 1;
	const reporte_no_pasaron = 2;
	const reporte_comision = 3;
	const reporte_resultados = 4;

	function set_datos($datos = array())
	{
		$this->comision = $datos['comision'];
		$this->alumnos = $datos['alumnos'];
		$this->operacion = $datos['operacion'];
		$this->plan_version = $datos['plan_version'];
		$this->constancia = $datos['constancia'];
		$this->asunto = 'Emisión de Constancia';
		if ($datos['constancia']['permite_descargar'] == comunes::si) {
			$this->estado_solicitud = constancias::estado_solicitud_online;
			$this->mensaje = 'Te informamos que la constancia del curso  ' . $this->comision['actividad_nombre']
					. ' ya ha sido emitido y podés descargarlo desde el portal de alumnos '
					. 'ingresando con tu usuario y contraseña.';
		} else {
			$this->estado_solicitud = constancias::estado_solicitud_pendiente;
			$this->mensaje = ' Te informamos que la constancia del curso ' . $this->comision['actividad_nombre']
					. ' ya ha sido emitida y podés acercarte a retirarla. ';
		}
		$this->usuario = $datos['usuario'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->alumnos);

		$cant_emitidos = 0;
		$cant_no_pasaron = 0;

		$filtro = guarani_sql::armar_condicion_in($this->alumnos, 'sga_alumnos', 'alumno');
		$datos_alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision($this->comision['comision'], true, $filtro);

                $ids_solicitudes = array();
                
		foreach ($datos_alumnos as $alumno) {
			// Emitir los certificados
			$retorno = $this->validar_ptos_control_alumno($alumno);
			$mensajes = $retorno['mensajes'];
			$retorno = $retorno['retorno'];

			switch ($retorno) {
				case guarani_punto_de_control::autorizado:
					$this->log->set($alumno, array(self::reporte_emitidos));
					// Se guardan las personas para luego, si es necesario, enviarles el mail
					$this->personas[] = $alumno['persona'];
					$id_solicitud = guarani::act('act_constancias')->insertar_solicitud($alumno['alumno'], $this->constancia['constancia'], quote($this->usuario), quote($this->estado_solicitud), $this->comision['comision']);
                                        $ids_solicitudes[] = $id_solicitud;
					$cant_emitidos++;
					break;
				case guarani_punto_de_control::advertido:
				case guarani_punto_de_control::rechazado:
					$alumno['observaciones'] = $mensajes;
					$this->log->set($alumno, array(self::reporte_no_pasaron));
					$cant_no_pasaron++;
					break;
			}

			$this->log->guardar();
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			} else {
				$this->log_estado->incrementar_progreso($paso);
			}
		}
		$this->log->set($this->comision, array(self::reporte_comision));
		$this->log->guardar();
		$mensaje_resultado['emitidos'] = $cant_emitidos;
		$mensaje_resultado['no_pasaron'] = $cant_no_pasaron;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
                
		// Notifico a los alumnos que se emitió una constancia de curso
		$parametros = ['ids_solicitudes' => $ids_solicitudes];
		guarani::notificador()->enviar(notificador::emision_certificados_cursos, $parametros);
	}

	function validar_ptos_control_alumno($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'alumno' => $alumno['alumno'],
			'comision' => $this->comision['comision'],
			'elemento' => $this->comision['elemento'],
			'plan_version' => $this->plan_version,
		);
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision
		);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

}
?>