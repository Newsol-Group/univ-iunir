<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_excepcion_insc_examen_pendientes extends ci_nav_sin_listado_inicial
{
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if (guarani_arreglos::count($this->cn()->get_insc_no_exitosas()) == 0) {
			$pantalla->eliminar_evento('confirmar');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		if (guarani_arreglos::count($this->s__seleccion) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_insc_examen');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}

		$this->cn()->controlar_inscripciones($this->s__seleccion);
		$this->set_pantalla('edicion');
	}

	function evt__volver()
	{
		unset($this->s__seleccion);
		$this->dep('editor')->resetear();
		parent::evt__cancelar();
	}

	function evt__confirmar()
	{
		try {
			$this->cn()->exceptuar_inscripciones($this->dep('editor')->get_inscripciones_a_exceptuar());
			$this->evt__volver();
			toba::notificacion()->agregar($this->get_mensaje('800_exceptuar_inscripciones_pendientes_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}
	}

}
?>