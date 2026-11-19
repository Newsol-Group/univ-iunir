<?php
class guarani_sesion extends toba_sesion
{
	function conf__inicial($datos=null)
	{
		$tipo_usuario = is_null(toba::memoria()->get_dato('prop_g3w')) ? 'Gestion' : 'Alumno';
		$filtro = 'mdp_personas_grupo_acc.tipo_usuario = '.toba::db()->quote($tipo_usuario);
		$perfiles_funcionales = toba::consulta_php('co_usuarios')->get_grupos_acceso_usuario(toba::usuario()->get_id(), $filtro);
		if (guarani_arreglos::count($perfiles_funcionales) > 0) {
			$perfil_funcional_activo = reset($perfiles_funcionales);
			toba::manejador_sesiones()->set_perfiles_funcionales_activos(array($perfil_funcional_activo['grupo_acceso']));
			toba::memoria()->set_dato('usuario_perfil_funcional_seleccionado', $perfil_funcional_activo['grupo_acceso']);
		} else {
			throw new toba_error('El usuario no tiene perfiles funcionales asociados.');
		}
	}
}
?>