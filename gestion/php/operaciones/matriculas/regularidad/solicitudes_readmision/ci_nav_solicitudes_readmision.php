<?php
class ci_nav_solicitudes_readmision extends ci_nav_sin_listado_inicial
{
	protected $s__observaciones;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// El formulario de motivo de rechazo se muestra cuando hay datos en el cuadro
		if (empty($this->dep('cuadro')->get_datos())) {
			$pantalla->eliminar_dep('form_observaciones');
			$pantalla->eliminar_evento('aceptar');
			$pantalla->eliminar_evento('rechazar');
		} else {
			$pantalla->evento('rechazar')->set_msg_confirmacion(guarani_mensajes::get('800MAT_rechazar_readmisiones'));
			$pantalla->evento('aceptar')->set_msg_confirmacion(guarani_mensajes::get('800MAT_aceptar_readmisiones'));
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_motivo_rechazo ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_observaciones__modificacion($datos)
	{
		$this->s__observaciones = $datos;
	}

	function conf__form_observaciones(toba_ei_formulario $form)
	{
		if (isset($this->s__observaciones)) {
			$form->set_datos($this->s__observaciones);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);

		// Si se filtró, se colapsa el filtro para dejar como elementos centrales al formulario de observaciones y las solicitudes seleccionables.
		if (!empty($this->s__datos)) {
			$filtro->colapsar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__rechazar()
	{
		if (empty($this->s__seleccion)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_readmisiones_solicitud');
			throw guarani::error(guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una'))));
		}
		try {
			$this->cn()->rechazar_solicitudes($this->s__seleccion, $this->s__observaciones['observaciones']);
			unset($this->s__seleccion);
			unset($this->s__observaciones);
		} catch (toba_error $e) {
			unset($this->s__seleccion);
			throw $e;
		}
	}

	function evt__aceptar()
	{
		if (empty($this->s__seleccion)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_readmisiones_solicitud');
			throw guarani::error(guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una'))));
		}
		$this->cn()->aceptar_solicitudes($this->s__seleccion, $this->s__observaciones['observaciones']);
		unset($this->s__seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/*function extender_objeto_js()
	{
		$seleccion = $this->get_seleccion('nombre');
		$mensaje_confirmacion = guarani_mensajes::get('800MAT_conf_cierre_periodo', array($seleccion['nombre']));
		echo "
		//---- Eventos ---------------------------------------------				
		{$this->objeto_js}.evt__procesar = function()
		{
			resp = confirm('$mensaje_confirmacion');
			return resp;
		}
		";
	}*/
}
?>