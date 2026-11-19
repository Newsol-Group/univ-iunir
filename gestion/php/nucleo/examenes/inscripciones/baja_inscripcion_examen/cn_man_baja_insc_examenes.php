<?php
/**
 *
 * 
 * @package		Exámenes
 * @subpackage	Inscripciones
 * 
 */
class cn_man_baja_insc_examenes extends cn_manejador_entidad
{
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_alumnos');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	function eliminar_inscripcion_examen($datos_inscripcion, $nro_transaccion_log)
	{
		$this->validar_puntos_de_control($datos_inscripcion);
		try {
			toba::db()->abrir_transaccion();

			// Se inserta el log de inscripción a examen y se elimina la inscripción.
			guarani::act('act_inscripcion_examenes')->insertar_insc_examen_log($datos_inscripcion['inscripcion'], inscripcion_examen::operacion_baja, $nro_transaccion_log);
			guarani::act('act_inscripcion_examenes')->eliminar_insc_examen($datos_inscripcion['inscripcion']);
			
			if( guarani::parametros()->get_valor(inscripcion_examen::exa_notificar_estado_inscripcion) == comunes::si ) { 
				$parametros['nombre_actividad'] = toba::consulta_php('co_elementos')->get_nombre_actividad($datos_inscripcion['elemento']);
				$fecha_hora = explode(' ', $datos_inscripcion['fecha_examen_visual']);
				$parametros['fecha_mesa'] = $fecha_hora[0];
				$parametros['persona'] = $datos_inscripcion['persona'];
				$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_inscripcion['persona']) );  
				$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($datos_inscripcion['persona']);  
				$parametros['nombre_alumno'] = $datos_persona['nombres'];
				guarani::notificador()->enviar(notificador::eliminar_inscripcion_examen, $parametros);
			}
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function validar_puntos_de_control($datos_inscripcion)
	{
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion
		);

		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();

		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_baja_inscripcion_fallo_ctrl'));
		}
	}

}
?>