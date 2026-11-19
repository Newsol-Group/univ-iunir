<?php
/**
 *
 * @package		Propuestas Formativas
 * @subpackage	Actividades
 */
class ci_nav_actividad_excepciones_comunes extends ci_nav_sin_listado_inicial
{
	function conf__seleccion($pantalla)
	{
		$pantalla->set_descripcion(guarani_mensajes::get('800_warning_actividades_varios_plan_estudios'), 'warning');
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
	}

	function get_seleccion()
	{
		return $this->s__seleccion;
	}

	//------ Cuadro ----------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('edicion');
	}

	function evt__guardar()
	{
		$ok = $this->cn()->guardar_excepciones($this->s__seleccion['elemento'], $this->s__seleccion['plan']);
		if ($ok) {
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} else {
			throw guarani::error(guarani_mensajes::get('guardar_error'));
		}
	}
}