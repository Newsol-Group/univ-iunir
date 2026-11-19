<?php
/**
 * Usuarios del sistema.
 * 
 * 
 * @package		Centrales
 * @subpackage	Arquitectura Base
 */
class usuario_nucleo
{
	// Tipos de usuario.
	const tipo_gestion = 'Gestion';
	const tipo_gestion_nombre = 'Usuario de Gestión';
	const tipo_docente = 'Docente';
	const tipo_alumno  = 'Alumno';
	const tipo_cursos  = 'Cursos';
	const tipo_admin_3w = 'Administrador3w';

	const id_perfil_alumno  = 'alumno';
	const id_perfil_cursos  = 'curso';
	const id_perfil_docente = 'docente';
	const id_perfil_admin   = 'admin';
	
	static function validar_id_usuario_permitido($id_usuario)
	{
		// Personalizar.
		$pers = toba::consulta_php('co_personas')->get_persona_x_usuario($id_usuario);
		if (empty($pers)) {
			return true;
		} else {
			return false;
		}
	}
	
	static function validar_clave_permitida($clave)
	{
		// Se controla la longitud mínima de la contraseña.
		// Se realiza el pedido anulando el uso de caché, debido a que este parámetro a nivel Institución
		// es consumido también desde Autogestión
		$longitud_minima = guarani::parametros()->get_valor('login_clave_longitud_minima', null, false);
		return (strlen($clave) >= $longitud_minima);
	}
	
	static function get_clave_default($pais_doc, $tipo_doc, $nro_doc)
	{
		// Personalizar.
		return $nro_doc;
	}

	static function get_usuario_default($pais_doc, $tipo_doc, $nro_doc)
	{
		// Personalizar.
		return $nro_doc;
	}

	static function tiene_perfil_por_dominio($dominio)
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$perfil_datos_restricciones = current(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos));
			if ($perfil_datos_restricciones) { // Desde Toba-Usuarios se está permitiendo definir perfiles de datos sin asociar ninguna restricción.
				$dominio_unidad_gestion = toba::consulta_php('co_unidades_gestion')->get_dominio_unidad_gestion($perfil_datos_restricciones['clave']);
				return ($dominio_unidad_gestion == $dominio);
			}
		}
		return false;
	}

	static function tiene_perfil_por_propuesta()
	{
		return static::tiene_perfil_por_dominio(unidades_gestion::dominio_prop);
	}

	static function tiene_perfil_por_tipo_propuesta()
	{
		return static::tiene_perfil_por_dominio(unidades_gestion::dominio_tipo_prop);
	}
}
?>