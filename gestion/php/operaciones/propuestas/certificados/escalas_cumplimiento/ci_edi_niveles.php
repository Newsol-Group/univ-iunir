<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_niveles extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	protected $datos_nivel;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN NIVEL
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_niveles();
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_escalas_cumplimiento_det');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_nivel($seleccion);
		$this->set_pantalla('edicion_nivel');
	}

	function evt__cuadro__agregar()
	{
		$this->set_pantalla('edicion_nivel');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN NIVEL
	//-----------------------------------------------------------------------------------

	function conf__edicion_nivel(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_nivel_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('sga_escalas_cumplimiento_det');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_nivel)) {
			$form->set_datos($this->datos_nivel);
		} else {
			$datos = $this->cn()->entidad()->get_datos_nivel_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->datos_nivel = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_nivel($this->datos_nivel);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_nivel($this->datos_nivel);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_nivel_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_nivel);
		$this->cn()->entidad()->deseleccionar_nivel();
		$this->set_pantalla('seleccion_nivel');
	}
}
?>