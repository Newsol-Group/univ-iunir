<?php
class ci_edi_datos_anticipos_nsg extends ci_edicion_simple
{

	const chars_maximo_combo = 100;

	protected $datos_anticipo;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_becas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_anticipos__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_beca($seleccion);
		$this->set_pantalla('edicion_anticipo');
	}

	function conf__cuadro_anticipos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_anticipos();

		foreach ($datos as $clave => $valor) {
			$datos[$clave]['descr_anticipo'] = $valor['tipo_beneficio_nombre'];
			$datos[$clave]['alumno'] = $valor['alumno'];
		}
	
		$etiqueta = guarani_etiquetas::get_tabla('sga_datos_anticipos');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_anticipos__agregar()
	{
		$this->set_pantalla('edicion_anticipo');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION ANTICIPOS
	//-----------------------------------------------------------------------------------

	function conf__edicion_anticipo(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_anticipo_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('sga_datos_anticipos');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_anticipos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_anticipos(toba_ei_formulario $form)
	{
		if (isset($this->datos_anticipos)) {
			$form->set_datos($this->datos_anticipos);

		} else {
			$datos = $this->cn()->entidad()->get_datos_anticipo_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_anticipos__modificacion($datos)
	{
		$this->datos_anticipos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$this->cn()->entidad()->nuevo_anticipo($this->datos_anticipos, $alumno);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_anticipos($this->datos_anticipos);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_anticipo_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_anticipos);
		$this->cn()->entidad()->deseleccionar_anticipo();
		$this->set_pantalla('seleccion_anticipo');
	}

}
?>