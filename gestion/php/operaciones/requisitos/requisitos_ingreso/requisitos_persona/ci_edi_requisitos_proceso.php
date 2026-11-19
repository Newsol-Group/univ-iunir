<?php
class ci_edi_requisitos_proceso extends guarani_pers_ci
{
	protected $datos_temp;
	protected $datos_temp_excepcion;
	protected $s__requisito_seleccionado;

	function get_requisito_seleccionado()
	{
		$salida = isset($this->s__requisito_seleccionado) ? $this->s__requisito_seleccionado : null;
		return $salida;
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: pant_edicion
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- requisitos ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__requisitos($form_ml)
	{
		if (isset($this->datos_temp)) {
			$datos = $this->datos_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_tipo_proceso();
		}
		$form_ml->set_datos($datos);
	}

	function evt__requisitos__exceptuar($seleccion)
	{
		$datos = $this->cn()->entidad()->get_datos_tipo_proceso();
		$this->s__requisito_seleccionado = $datos[$seleccion];
		$this->set_pantalla('pant_exceptuar');
	}

	/**
	 * Se permite exceptuar requisitos no cumplidos ni ya exceptuados (sincronizados o no)
	 */
	function conf_evt__requisitos__exceptuar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->cn()->entidad()->get_datos_tipo_proceso();
		if ($datos[$fila]['cumplido'] || $this->cn()->entidad()->requisito_exceptuado($datos[$fila])) {
			$evento->anular();
		}
	}

	/**
	 * Cancela una excepción no sincronizada
	 */
	function evt__requisitos__cancelar_excep($seleccion)
	{
		$datos = $this->cn()->entidad()->get_datos_tipo_proceso();
		$this->cn()->entidad()->eliminar_excepcion_tipo_proceso($datos[$seleccion]);
	}

	/**
	 * Sólo se permite exceptuar requisitos no cumplidos (ni con excepciones ya
	 * sincronizadas)
	 */
	function conf_evt__requisitos__cancelar_excep(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->cn()->entidad()->get_datos_tipo_proceso();
		if (empty($datos[$fila]['exceptuado_ahora'])) {
			$evento->anular();
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: pant_exceptuar
	//--------------------------------------------------------------------------

	function conf__pant_exceptuar()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$this->controlador()->pantalla()->tab('pant_documentales')->desactivar();
	}

	//--------------------------------------------------------------------------
	//---- form_ml_exceptuar ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_ml_exceptuar($form_ml)
	{
		if (isset($this->datos_temp_excepcion)) {
			$datos = $this->datos_temp_excepcion;
		} else {
			$datos = array($this->s__requisito_seleccionado);
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_exceptuar__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->procesar_excepcion_req_proceso($datos);
			$this->controlador()->controlador()->set_hubo_cambios();
		} catch (guarani_error $e) {
			$this->datos_temp_excepcion = $datos;
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__requisito_seleccionado);
		$this->set_pantalla('pant_edicion');
	}

	function evt__aceptar()
	{
		unset($this->s__requisito_seleccionado);
		$this->set_pantalla('pant_edicion');
	}

}
?>