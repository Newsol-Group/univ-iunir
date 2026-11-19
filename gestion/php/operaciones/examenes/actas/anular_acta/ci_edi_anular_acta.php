<?php
class ci_edi_anular_acta extends ci_navegacion
{
	protected $s__datos_acta;
	protected $s__observaciones;

	function ini()
	{
		$this->s__id_acta = $this->controlador()->get_acta_seleccionada();
		$this->s__datos_acta = $this->controlador()->get_datos_acta();
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función invocada desde el ci de navegación para limpiar la memoria de las variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		unset($this->s__datos_acta);
		unset($this->s__observaciones);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// Seteo los datos del elemento visual
		$datos = $this->controlador()->get_datos_ev_acta();
		$this->controlador()->ev_info()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos(toba_ei_cuadro $cuadro)
	{
		// Seteo el tamaño de página del cuadro
		$lineas_folio = $this->s__datos_acta['renglones_folio'];
		$estado = $this->s__datos_acta['estado'];
		$cuadro->set_tamanio_pagina($lineas_folio);

		// Seteo los datos del cuadro
		$datos = $this->cn()->get_detalle_acta_abierta($this->s__id_acta);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_acta --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_acta__modificacion($datos)
	{
		if (isset($datos['observaciones'])) {
			$this->s__observaciones = $datos['observaciones'];
		}
	}

	function get_observaciones()
	{
		return $this->s__observaciones;
	}

}
?>