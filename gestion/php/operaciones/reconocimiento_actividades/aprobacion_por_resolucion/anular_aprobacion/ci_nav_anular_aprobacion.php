<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class ci_nav_anular_aprobacion extends ci_nav_sin_listado_inicial
{
	//Observaciones para la anulación del trámite de aprobación
	protected $observaciones;
	
	//-----------------------------------------------------------------------------------
	//---- SELECCION --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf()
	{
		parent::conf();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'pant_anular') {
			
			//Se setea el mensaje de confirmación para el evento de Anular Trámite de Aprobación
			$msj = guarani_mensajes::get('800EQU_confirmar_anulacion');
			$this->evento('anular_tramite_aprobacion')->set_msg_confirmacion($msj);
		}
	}

	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_anular');
	}

	//---- Eventos ----------------------------------------------------------------------
	
	function evt__anular_tramite_aprobacion()
	{
		//Se realizan todas las acciones para anular el trámite de aprobación seleccionado
		$nro_tramite = $this->get_tramite_aprobacion_seleccionado();
		$this->cn()->anular_tramite_aprobacion($nro_tramite, $this->get_observaciones());

		//Se informa el éxito de la anulación del Trámite de Aprobación y se redirecciona de pantalla
		$mje = guarani_mensajes::get('800EQU_anulacion_ok');
		toba::notificacion()->agregar($mje, comunes::error_informacion);
		$this->set_pantalla('seleccion');
	}
	
	//---- Auxiliares -------------------------------------------------------------------
	
	function get_tramite_aprobacion_seleccionado()
	{
		return current($this->s__seleccion);
	}
	
	function get_observaciones()
	{
		return current($this->observaciones);
	}
	
	function set_observaciones($observaciones)
	{
		$this->observaciones = $observaciones;
	}
	
}
?>