<?php
/**
 *
 *
 * @package Administración
 */
class cn_man_configuracion extends cn_manejador_entidad
{
	function actualizar_clave($datos)
	{
		$usuario = toba::usuario()->get_id();

		if (!usuario::validar_clave_permitida($datos['clave'])) {
			$longitud_minima = guarani::parametros()->get_valor('login_clave_longitud_minima');
			$mensaje = guarani_mensajes::get('800_clave_longitud_minima', array($longitud_minima));
			throw guarani::error($mensaje);
		}

		// Se verifica la contraseña actual.
		$datos_usuario = toba::consulta_php('co_usuarios')->get_datos_autenticacion_usuario($usuario);
		if (!guarani_usuario::chequear_clave($datos['clave_actual'], $datos_usuario['clave'])) {
			$mensaje = guarani_mensajes::get('800_clave_actual_incorrecta');
			throw guarani::error($mensaje);
		}

		// Se verifica que la nueva contraseña no coincida con la actual.
		if (guarani_usuario::chequear_clave($datos['clave'], $datos_usuario['clave'])) {
			$mensaje = guarani_mensajes::get('800_clave_repetida');
			throw guarani::error($mensaje);
		}

		$nueva_clave = guarani::encriptar($datos['clave']);
		guarani::act('act_personas')->actualizar_clave($usuario, $nueva_clave);
	}

	function actualizar_perfil_funcional_defecto($datos)
	{
		guarani::act('act_personas')->actualizar_perfil_funcional_defecto(toba::usuario()->get_id(), $datos['usuario_grupo_acc']);
	}
}
?>