<?php
/**
 *
 *
 * @package Administración
 */
class ci_configuracion extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- form_contrasenia -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_contrasenia__guardar($datos)
	{
		$this->cn()->actualizar_clave($datos);
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

	//-----------------------------------------------------------------------------------
	//---- form_perfil ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_perfil(toba_ei_formulario $form)
	{
		$form->set_datos(array('usuario_grupo_acc' => toba::consulta_php('co_usuarios')->get_perfil_funcional_defecto_gestion(toba::usuario()->get_id())));
	}

	function evt__form_perfil__guardar($datos)
	{
		$this->cn()->actualizar_perfil_funcional_defecto($datos);
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

	//-----------------------------------------------------------------------------------

	function get_grupos_acceso_usuario()
	{
		$filtro = "mdp_personas_grupo_acc.tipo_usuario = '".usuario::tipo_gestion."'";
		return toba::consulta_php('co_usuarios')->get_grupos_acceso_usuario(toba::usuario()->get_id(), $filtro);
	}
}
?>