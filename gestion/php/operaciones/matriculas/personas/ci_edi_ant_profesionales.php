<?php
class ci_edi_ant_profesionales extends guarani_pers_ci
{
	protected $datos_ant_profesional;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ANTECEDENTES PROFESIONALES 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_ant_docentes ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_ant_prof__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_ant_profesional($seleccion);
		$this->set_pantalla('edicion_antecedente');
	}

	function conf__cuadro_ant_prof(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('mdp_antecedentes_profesionales');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($this->cn()->entidad()->get_datos_ant_profesionales());
	}

	function evt__cuadro_ant_prof__agregar()
	{
		$this->set_pantalla('edicion_antecedente');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION ANTECEDENTES PROFESIONALES 
	//-----------------------------------------------------------------------------------

	function conf__edicion_antecedente(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_ant_profesional_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('mdp_antecedentes_profesionales');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ant_docentes ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ant_prof(toba_ei_formulario $form)
	{
		if (isset($this->datos_ant_profesional)) {
			$form->set_datos($this->datos_ant_profesional);
		} else {
			$datos = $this->cn()->entidad()->get_datos_ant_profesional_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_ant_prof__modificacion($datos)
	{
		$this->datos_ant_profesional = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_ant_profesional($this->datos_ant_profesional);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_ant_profesional($this->datos_ant_profesional);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_ant_profesional_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_ant_profesional);
		$this->cn()->entidad()->deseleccionar_ant_profesional();
		$this->set_pantalla('seleccion_antecedente');
	}

}
?>