<?php
class ci_edi_moodle extends guarani_pers_ci
{
	
	protected $datos_usuario;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION MOODLE 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_moodle -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_moodle__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_usuario_moodle($seleccion);
		$this->set_pantalla('edicion_moodle');
	}

	function conf__cuadro_moodle(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_usuarios_moodle();
		$cuadro->set_datos($datos);
		
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array("usuarios de Moodle vinculados a esta persona"));
		$cuadro->set_eof_mensaje($eof_mensaje);
	}

	function evt__cuadro_moodle__agregar()
	{
		$this->set_pantalla('edicion_moodle');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION MOODLE 
	//-----------------------------------------------------------------------------------

	function conf__edicion_moodle(toba_ei_pantalla $pantalla)
	{	
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_usuario_moodle_seleccionado()) {
			$this->evento('alta')->ocultar();
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array("la vinculación con este usuario de Moodle")));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_moodle ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_moodle(toba_ei_formulario $form)
	{
		$this->pantalla()->set_descripcion('Se debe haber creado Plataformas de Educación Virtual en la operación <b>Administrar Configuración del Sistema</b>. Además, se debe habilitar la función <b>core_user_get_users</b> en Moodle.');
		
		if (isset($this->datos_usuario)) {
			$datos = $this->datos_usuario;
		} else {
			$datos = $this->cn()->entidad()->get_datos_usuario_moodle_seleccionado();
		}
		$form->set_datos($datos);
	}

	function evt__form_moodle__modificacion($datos)
	{
		$this->datos_usuario = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_usuario_moodle($this->datos_usuario);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_usuario_moodle($this->datos_usuario);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_usuario_moodle_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_usuario);
		$this->cn()->entidad()->deseleccionar_usuario_moodle();
		$this->set_pantalla('seleccion_moodle');
	}
	
	
	function info__plataformas_disponibles()
	{
		// Si hay un usuario seleccionado
		if ($this->cn()->entidad()->hay_usuario_moodle_seleccionado()) {
			$usuario_seleccionado = $this->cn()->entidad()->get_datos_usuario_moodle_seleccionado();
			$plataforma_excepcion = $usuario_seleccionado['plataforma'];
		} else { // sino
			$plataforma_excepcion = null;
		}
		
		return $this->cn()->entidad()->get_plataformas_disponibles($plataforma_excepcion);
	}
	
	function info__usuarios_moodle($filtro = null, $plataforma = null)
	{
		
		if ($filtro == '%' || $filtro == '*') {
			$filtro = '';
		}
		
		return $this->cn()->entidad()->get_usuarios_moodle($filtro, $plataforma);
	}
	
	public static function info__usuario_moodle($id = null)
	{
		return toba::cn('persona')->get_usuario_moodle($id);
	}
	
}