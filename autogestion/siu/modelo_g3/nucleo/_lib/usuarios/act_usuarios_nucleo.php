<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * 
 * @package 	Centrales
 * @subpackage 	Arquitectura Base
 */
class act_usuarios_nucleo
{
	function bloquear_usuario($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$sql = "UPDATE	mdp_personas
				SET		bloqueado = 1
				WHERE	usuario = $usuario
		";
		guarani_db::ejecutar($sql);
	}
}
?>