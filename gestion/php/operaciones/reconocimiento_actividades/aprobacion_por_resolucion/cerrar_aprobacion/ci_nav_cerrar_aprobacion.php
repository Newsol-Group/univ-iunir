<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class ci_nav_cerrar_aprobacion extends ci_nav_sin_listado_inicial
{
	// Configuración en caso de que la operación haya sido invocada desde otra con un trámite específico.
	function ini__operacion()
	{
		$nro_tramite = toba::memoria()->get_parametro('nro_tramite');
		if (!is_null($nro_tramite)) {
			$this->s__seleccion = array('nro_tramite' => $nro_tramite);
			if (!empty($this->s__seleccion)) {
				$this->set_pantalla('pant_cierre');
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- SELECCION --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf()
	{
		parent::conf();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'pant_cierre') {
			
			//Se setea el mensaje de confirmación para el evento de Cierre del Trámite de Aprobación
			$msj = guarani_mensajes::get('800EQU_confirmar_cierre');
			$this->evento('cerrar_tramite_aprobacion')->set_msg_confirmacion($msj);
		}
	}

	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('pant_cierre');
	}

	//---- Eventos ----------------------------------------------------------------------
	
	function evt__cerrar_tramite_aprobacion()
	{
		//Se realizan todas las acciones para cerrar el trámite de aprobación seleccionado
		$nro_tramite = $this->get_tramite_aprobacion_seleccionado();
		$this->cn()->cerrar_tramite_aprobacion($nro_tramite);

		//Se informa el éxito del cierre del Trámite de Aprobación y se redirecciona de pantalla
		$mje = guarani_mensajes::get('800EQU_cierre_ok');
		toba::notificacion()->agregar($mje, comunes::error_informacion);
		$this->set_pantalla('seleccion');
	}
	
	//---- Auxiliares -------------------------------------------------------------------
	
	function get_tramite_aprobacion_seleccionado()
	{
		return current($this->s__seleccion);
	}

}
?>