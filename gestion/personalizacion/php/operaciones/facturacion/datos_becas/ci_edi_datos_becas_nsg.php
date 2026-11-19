<?php
class ci_edi_datos_becas_nsg extends ci_edicion_simple
{

	const chars_maximo_combo = 100;

	protected $datos_beca;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_becas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_becas__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_beca($seleccion);
		$this->set_pantalla('edicion_beca');
	}

	function conf__cuadro_becas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_becas();

		foreach ($datos as $clave => $valor) {
			$datos[$clave]['descr_beneficio'] = $valor['tipo_beneficio_nombre'];
			$datos[$clave]['alumno'] = $valor['alumno'];
		}
	
		$etiqueta = guarani_etiquetas::get_tabla('sga_datos_beneficios');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_becas__agregar()
	{
		$this->set_pantalla('edicion_beca');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION BECAS
	//-----------------------------------------------------------------------------------

	function conf__edicion_beca(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_beca_seleccionada()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('sga_datos_beneficios');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_becas ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_becas(toba_ei_formulario $form)
	{
		if (isset($this->datos_becas)) {
			$form->set_datos($this->datos_becas);

		} else {
			$datos = $this->cn()->entidad()->get_datos_beca_seleccionada();
			$form->set_datos($datos);
		}
	}

	function evt__form_becas__modificacion($datos)
	{
		$this->datos_becas = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$alumno = $this->controlador()->get_alumno_seleccionado();
		$this->cn()->entidad()->nueva_beca($this->datos_becas, $alumno);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_becas($this->datos_becas);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_beca_seleccionada();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_becas);
		$this->cn()->entidad()->deseleccionar_beca();
		$this->set_pantalla('seleccion_beca');
	}

}
?>