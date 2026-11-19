<?php
class ci_edi_datos_bonif_nsg extends ci_edicion_simple
{

	const chars_maximo_combo = 100;

	protected $datos_bonif;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_bonif -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_bonif__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_bonif($seleccion);
		$this->set_pantalla('edicion_bonif');
	}

	function conf__cuadro_bonif(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_bonif();

		foreach ($datos as $clave => $valor) {
			$datos[$clave]['descr_beneficio'] = $valor['tipo_beneficio_nombre'];
			$datos[$clave]['alumno'] = $valor['alumno'];
		}

		$etiqueta = guarani_etiquetas::get_tabla('sga_datos_beneficios');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_bonif__agregar()
	{
		$this->set_pantalla('edicion_bonif');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION bonifS
	//-----------------------------------------------------------------------------------

	function conf__edicion_bonif(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_bonif_seleccionada()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('sga_datos_beneficios');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_bonif ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_bonif(toba_ei_formulario $form)
	{
		if (isset($this->datos_bonif)) {
			$form->set_datos($this->datos_bonif);

		} else {
			$datos = $this->cn()->entidad()->get_datos_bonif_seleccionada();
			$form->set_datos($datos);

		}
	}

	function evt__form_bonif__modificacion($datos)
	{
		$this->datos_bonif = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$this->cn()->entidad()->nueva_bonif($this->datos_bonif, $alumno);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_bonif($this->datos_bonif);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_bonif_seleccionada();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_bonif);
		$this->cn()->entidad()->deseleccionar_bonif();
		$this->set_pantalla('seleccion_bonif');
	}

}
?>