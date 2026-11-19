<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class cn_man_rechazar_inscripciones_cursada extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_insc_cursada');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$order = ' apellido_y_nombre, ubicacion, actividad, comision';
		return guarani::co('co_inscripciones_cursadas')->get_listado($filtro, false, $order);
	}

	function rechazar_inscripciones($inscripciones, $motivo_rechazo)
	{
		$contador_ok = 0;
		$contador_error = 0;
		$lista_error = '';
		foreach ($inscripciones as $inscripcion) {
			try {
				toba::db()->abrir_transaccion();
				if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si) {
					$datos_inscripcion = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones('inscripcion = ' . $inscripcion['inscripcion']);
				}

				// Se elimina la inscripción a cursada junto a los registros de las tablas relacionadas.
				$result = guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_func($inscripcion['inscripcion']);
				if ($result['res'] < 1) {
					$mje = guarani_mensajes::get($result['msg_indice'], array($result['pars']));
					throw guarani::error($mje);
				} else {
					toba::db()->cerrar_transaccion();
					$contador_ok++;
					if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si ) {
						$parametros['estado'] = comunes::resultado_inscripcion_rechazada;
						$parametros['nombre_actividad'] = $datos_inscripcion[0]['actividad_nombre'];
						$parametros['nombre_comision'] = $datos_inscripcion[0]['comision_nombre'];
						$parametros['persona'] = $datos_inscripcion[0]['persona'];
						$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_inscripcion[0]['persona']) ); 
						$parametros['nombre_alumno'] = $datos_inscripcion[0]['nombres']; 
						guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
					}
				}
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				$contador_error++;
				$lista_error .= '<BR>' . $inscripcion['apellido_y_nombre'];
				toba::logger()->error($e->getMessage());
			}
		}

		$msg = guarani_mensajes::get('800_ok_rechazo_masivo_insc', array($contador_ok, $contador_error, $lista_error));
		$nivel = ($contador_error > 0) ? comunes::error_critico : comunes::error_informacion;
		toba::notificacion()->agregar($msg, $nivel);
	}
}