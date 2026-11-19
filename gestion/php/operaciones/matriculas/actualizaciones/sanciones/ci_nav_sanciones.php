<?php
class ci_nav_sanciones extends ci_nav_sin_listado_inicial
{
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			if (!isset($this->s__seleccion)) {
				// Alta de sanción. 
				$datos = $this->cn()->entidad()->get_datos();
				$datos_persona = current(toba::consulta_php('co_personas')->get_datos_persona_mail($datos['persona']));
				if (!empty(trim($datos_persona['email']))) {
					$this->enviar_mail(notificador::notificar_sancion_alumno, $datos_persona['persona'], $datos_persona['email'], $datos_persona['nombre_completo'], $datos['fecha_desde'], $datos['fecha_hasta']);
				}
				$docentes = toba::consulta_php('co_docentes')->get_docentes_comision_mesas($datos['persona'], $datos['propuesta']);
				foreach ($docentes as $docente) {
					if (!empty($docente['email'])) {
						$this->enviar_mail(notificador::notificar_sancion_docente, $docente['persona'], $docente['email'], $docente['apellido_nombres_elegido'], $datos['fecha_desde'], $datos['fecha_hasta'], $datos_persona['nombre_completo']);
					}
				}
				toba::notificacion()->agregar(guarani_mensajes::get('800_envio_email_exitoso'), comunes::error_informacion);
			}
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), comunes::error_informacion);
		} catch (guarani_error $e) {
			throw $e;
		} catch (toba_error $e) {
			//Falla el envio del email
			toba::logger()->error($e->get_mensaje());
			$mensaje = guarani_mensajes::get('800_error_envio_mensajes');
			throw guarani::error($mensaje);
		}
	}

	function enviar_mail($alcance, $persona, $email, $nombre, $fecha_desde, $fecha_hasta=null, $nombre_alumno=null)
	{
		$parametros['persona'] = $persona;
		$parametros['email'] = [$persona => ['email' => $email]];
		$parametros['nombres_elegido'] = $nombre;
		$parametros['fecha_desde'] = guarani_fecha::convertir_fecha_formato_frontend($fecha_desde);
		$parametros['fecha_hasta'] = empty($fecha_hasta) ? '' : ' al '.guarani_fecha::convertir_fecha_formato_frontend($fecha_hasta);
		$parametros['nombres_elegido_alumno'] = empty($nombre_alumno) ? '' : $nombre_alumno;
		guarani::notificador()->enviar($alcance, $parametros);
	}
}