<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_reversion_rechazo_insc_propuesta extends ci_nav_sin_listado_inicial
{
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof($dep = null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiqueta = $this->cn()->etiquetas($dep)->get_enf('plural') . ' rechazadas';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$resultado = $this->cn()->revertir_rechazo_insc_propuesta($seleccion['propuesta_aspira'], $seleccion['propuesta']);

		if (isset($resultado['aceptadas']) && $resultado['aceptadas'] == 1) {
			$mensaje = guarani_mensajes::get('800MAT_insc_revertir_rechazo_ok', array($seleccion['propuesta_nombre_abreviado']));
			toba::notificacion()->info($mensaje);
		} elseif (isset($resultado['pendientes']) && $resultado['pendientes'] == 1) {
			$mensaje = guarani_mensajes::get('800MAT_insc_revertir_rechazo_pendiente', array($seleccion['propuesta_nombre_abreviado']));
			toba::notificacion()->warning($mensaje);
		} elseif (isset($resultado['rechazadas']) && $resultado['rechazadas'] == 1) {
			$mensaje = guarani_mensajes::get('800MAT_insc_revertir_rechazo_error', array($seleccion['propuesta_nombre_abreviado']));
			toba::notificacion()->error($mensaje);
		}
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$mensaje = guarani_mensajes::get('800MAT_Confirmar_Revertir', array($datos[$fila]['propuesta_nombre_abreviado'], $datos[$fila]['apellido_y_nombres']));
		$evento->set_msg_confirmacion($mensaje);
	}

}