<?php
class ci_edi_bitacora extends guarani_pers_ci
{
	const chars_maximo_combo = 100;

	protected $datos_bitacora;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	protected $cod_persona; //cod_persona del usuario logueado

	function conf()
	{
		$datos_persona = guarani::co('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		if (isset($datos_persona['persona'])) {
			$this->cod_persona = $datos_persona['persona'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION BITACORA 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_bitacora -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_bitacora__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_comentario_bitacora($seleccion);
		$this->set_pantalla('edicion_bitacora');
	}

	function conf__cuadro_bitacora(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_bitacora();

		$etiqueta = guarani_etiquetas::get_tabla('sga_bitacora');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_bitacora__agregar()
	{
		$this->set_pantalla('edicion_bitacora');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION BITACORA 
	//-----------------------------------------------------------------------------------

	function conf__edicion_bitacora(toba_ei_pantalla $pantalla)
	{
		// Desactiva los eventos del CI principal (volver, eliminar y guardar)
		$this->controlador()->controlador()->evento('cancelar')->desactivar();

		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}

		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_comentario_bitacora_seleccionado()) {
			$this->evento('alta')->ocultar();

			$datos = $this->cn()->entidad()->get_datos_comentario_bitacora_seleccionado();

			if ($this->cod_persona != $datos['usuario_alta']) { //El usuario que no cargó el comentario_bitacora no puede editar ni eliminar
				$this->evento('baja')->ocultar();
				$this->evento('modificacion')->ocultar();
			}

			$etiqueta = guarani_etiquetas::get_tabla('sga_bitacora');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_bitacora ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_bitacora(toba_ei_formulario $form)
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);

		if (isset($this->datos_bitacora)) {
			$datos = $this->datos_bitacora;
		} else {
			$datos = $this->cn()->entidad()->get_datos_comentario_bitacora_seleccionado();

			if (empty($datos)) {
				if (isset($this->cod_persona)) {
					$datos['usuario_alta'] = $this->cod_persona;
				}

				$datos['fecha'] = guarani_fecha::get_hoy();
			}
		}
		$datos['usuario_alta_nombre'] = guarani::co('co_personas')->get_nombre_persona($datos['usuario_alta']);

		if ($datos['usuario_alta'] != $this->cod_persona) {
			$form->set_solo_lectura();
		}

		$form->set_datos($datos);
	}

	function evt__form_bitacora__modificacion($datos)
	{
		$this->datos_bitacora = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_comentario_bitacora($this->datos_bitacora);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_bitacora($this->datos_bitacora);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_comentario_bitacora_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_bitacora);
		$this->cn()->entidad()->deseleccionar_comentario_bitacora();
		$this->set_pantalla('seleccion_bitacora');
	}

}
?>