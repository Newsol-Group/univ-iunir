<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_nav_anular_equivalencia extends ci_nav_sin_listado_inicial
{
	//------ Auxiliares ------------------
	
	function get_tramite_equiv_seleccionado()
	{
		return current($this->s__seleccion);
	}

	//------ Configuraciones -------------

	function conf()
	{
		parent::conf();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'pant_anulacion') {
			// Se setea el mensaje de confirmación para el evento de Cierre del Trámite de Equivalencia.
			$msj = guarani_mensajes::get('800EQU_confirmar_anulacion');
			$this->evento('anular_tramite_equivalencia')->set_msg_confirmacion($msj);
		}
	}
	
	//------ Pantalla de Selección -------
	
	//------ Cuadro ----------------------

	// Se redefine la selección del cuadro ya que no existe un DR a cargar.
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_anulacion');
	}

	//------ Pantalla de Anulación --------
	
	//------ Eventos ----------------------

	function evt__anular_tramite_equivalencia()
	{
		// Se realizan todas las acciones para cerrar el trámite de equivalencia seleccionado.
		$id_tramite = $this->get_tramite_equiv_seleccionado();
		// Se recuperan las observaciones de la anulación caragadas en la pantalla de edición.
		$observ = $this->dependencia('ci_edi_anular_equivalencia')->get_observ_anulacion();
		$this->cn()->anular_tramite_equivalencia($id_tramite, $observ);

		// Se informa el éxito de la anulación del Trámite de Equivalencia.
		$mje = guarani_mensajes::get('800EQU_anulacion_ok');
		toba::notificacion()->agregar($mje, comunes::error_informacion);

		// Se resetean las variables de sesión del ci de edición antes de volver a la pantalla inicial.
		$this->dependencia('ci_edi_anular_equivalencia')->resetear_vars();
		// Una vez cerrada la equivalencia, se vuelve a la pantalla inicial.
		$this->set_pantalla('seleccion');
	}
}
?>