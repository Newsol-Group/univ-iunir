<?php
/**
 * Usuarios del sistema.
 * 
 * 
 * @package		Centrales
 * @subpackage	Arquitectura Base
 */
class co_usuarios
{
	/**
	 * Retorna los datos básicos de un usuario dado.
	 *
	 * @param string $usuario
	 * @return array
	 */
	function get_datos_basicos_usuario($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$sql = "SELECT	mdp_personas.usuario as id,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombre,
						mdp_personas.parametro_a as parametro_a
				FROM 	mdp_personas
				WHERE	mdp_personas.usuario = $usuario
		";
		return guarani_db::consultar_fila($sql, null, false);
	}

	/**
	 * Retorna los datos necesarios para la autentificación de un usuario dado.
	 *
	 * @param string $usuario
	 * @return array
	 */
	function get_datos_autenticacion_usuario($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$sql = "SELECT	mdp_personas.clave,
						mdp_personas.autenticacion
				FROM	mdp_personas
				WHERE	mdp_personas.usuario = $usuario
		";
		return guarani_db::consultar_fila($sql, null, false);
	}

	/**
	 * Retorna el listado de grupos de acceso asociados a un usuario dado.
	 * Se exceptúa el grupo 'docente' puesto que al momento los docentes no tienen acceso a gestión.
	 *
	 * @param string $usuario
	 * @return array
	 */
	function get_grupos_acceso_usuario($usuario, $where='')
	{
		$usuario = toba::db()->quote($usuario);
		if ($where) {
			$where = " AND $where ";
		}
		$sql = "SELECT		mdp_personas_grupo_acc.usuario_grupo_acc as	grupo_acceso,
							mdp_personas_grupo_acc.tipo_usuario
				FROM 		mdp_personas_grupo_acc,
							mdp_personas
				WHERE		mdp_personas_grupo_acc.persona = mdp_personas.persona AND
							mdp_personas.usuario = $usuario AND
							mdp_personas_grupo_acc.usuario_grupo_acc <> 'docente'
							$where
				ORDER BY	mdp_personas_grupo_acc.grupo_acceso_default DESC,
							mdp_personas_grupo_acc.usuario_grupo_acc ASC
		";
		$grupos_acceso_usuario = guarani_db::consultar($sql, null, false);
		$grupos_acceso = rs_convertir_asociativo($this->get_grupos_acceso(), array('usuario_grupo_acc'), 'nombre');
		foreach ($grupos_acceso_usuario as $clave => $valor) {
			$grupos_acceso_usuario[$clave]['nombre'] = isset($grupos_acceso[$valor['grupo_acceso']]) ? $grupos_acceso[$valor['grupo_acceso']] : '';
		}
		return $grupos_acceso_usuario;
	}

	/**
	 * Determina si una persona tiene asociado un tipo de usuario determinado. 
	 * Si no se recibe un tipo de usuario, se determina si la persona tiene asociado
	 * algún perfil.
	 * 
	 * @param int $persona
	 * @param string $tipo_usuario
	 * @return boolean
	 */
	function persona_tiene_tipo_usuario($persona, $tipo_usuario=null)
	{
		$persona = toba::db()->quote($persona);
		
		if ($tipo_usuario != null) {
			$tipo_usuario = toba::db()->quote($tipo_usuario);
			$where = "AND tipo_usuario = $tipo_usuario";
		} else {
			$where = '';
		}
		
		$sql = "SELECT 
					count(1) AS cant
				FROM
					mdp_personas_grupo_acc 
				WHERE
					persona = $persona
					$where";
		
		$res = guarani_db::consultar_fila($sql, null, false);
		return $res['cant'] > 0;
	}

	/**
	 * Retorna los perfiles de datos correspondientes a un usuario dado.
	 *
	 * @param string $usuario
	 * @return array
	 */
	function get_perfiles_datos_usuario($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$sql = "SELECT	mdp_personas_perfil_datos.usuario_perfil_datos as perfil_datos
				FROM 	mdp_personas,
						mdp_personas_perfil_datos
				WHERE	mdp_personas.persona = mdp_personas_perfil_datos.persona AND
						mdp_personas.usuario = $usuario
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna la cantidad de grupos de accesos de los que es miembro un grupo de acceso dado.
	 *
	 * @param string $grupo_acceso
	 * @return integer
	 */
	function get_cant_membresias_grupo_acceso($grupo_acceso)
	{
		$grupo_acceso = toba::db()->quote($grupo_acceso);
		$sql = "SELECT	COUNT(*) as cant
				FROM	apex_usuario_grupo_acc_miembros
				WHERE	apex_usuario_grupo_acc_miembros.usuario_grupo_acc = $grupo_acceso
		";
		$rs = toba::instancia()->get_db()->consultar_fila($sql, null, false);
		return $rs['cant'];
	}

	/**
	 * Determina si un un usuario dado está bloqueado.
	 *
	 * @param string $usuario
	 * @return boolean
	 */
	function es_usuario_bloqueado($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$sql = "SELECT	mdp_personas.bloqueado
				FROM	mdp_personas
				WHERE	mdp_personas.usuario = $usuario
		";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return (isset($rs['bloqueado']) && $rs['bloqueado'] == 1);
	}

	/**
	 * Retorna el listado de tipos de usuario según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_tipos_usuario($where='')
	{
		if ($where) {
			$where = "AND $where";
		}
		
		$alumno = usuario::tipo_alumno;
		$docente = usuario::tipo_docente;
		$cursos = usuario::tipo_cursos;
		
		$sql = "SELECT		acc_tipos_usuarios.tipo_usuario,
							acc_tipos_usuarios.nombre
				FROM		acc_tipos_usuarios
				WHERE		acc_tipos_usuarios.tipo_usuario NOT IN ('$alumno', '$docente', '$cursos')
							$where
				ORDER BY	acc_tipos_usuarios.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a un tipo de usuario en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
 	  * 
      *	@param integer/array código de tipo de usuario o arreglo de código/s de tipo/s de usuario.
    */
	function get_datos_tipo_usuario($tipo_usuario)
	{
		if (is_array($tipo_usuario)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_usuario, 'acc_tipos_usuarios', 'tipo_usuario');
		} else {
			$tipo_usuario = toba::db()->quote($tipo_usuario);
			$filtro = "acc_tipos_usuarios.tipo_usuario = $tipo_usuario";
		}
		return $this->get_tipos_usuario($filtro);
	}

	/**
	 * Retorna el listado de grupos de acceso asociados al tipo de usuario "Gestión".
	 *
	 * @return array
	 */
	function get_grupos_acceso_gestion()
	{
		$proyecto = toba::db()->quote(toba::proyecto()->get_id());
		$perfil_alumno = toba::db()->quote(usuario::id_perfil_alumno);
		$sql = "SELECT	apex_usuario_grupo_acc.usuario_grupo_acc as grupo_acceso,
						apex_usuario_grupo_acc.nombre
				FROM	apex_usuario_grupo_acc
				WHERE	apex_usuario_grupo_acc.proyecto = $proyecto AND
						apex_usuario_grupo_acc.usuario_grupo_acc <> $perfil_alumno
		";
		return toba::instancia()->get_db()->consultar($sql);
	}

	/**
	 * Retorna el listado de grupos de acceso según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_grupos_acceso($where='')
	{
		$proyecto = toba::db()->quote(toba::proyecto()->get_id());
		if ($where) {
			$where = 'AND '.$where;
		}
		$sql = "SELECT 		apex_usuario_grupo_acc.usuario_grupo_acc,
							apex_usuario_grupo_acc.nombre
				FROM 		apex_usuario_grupo_acc 
				WHERE		apex_usuario_grupo_acc.proyecto = $proyecto
							$where
				ORDER BY	apex_usuario_grupo_acc.nombre
		";
		return toba::instancia()->get_db()->consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a un grupo de acceso en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
 	  * 
      *	@param integer/array código de grupo de acceso o arreglo de código/s de grupo/s de acceso.
    */
	function get_datos_grupo_acceso($usuario_grupo_acc)
	{
		if (is_array($usuario_grupo_acc)) {
			$filtro = guarani_sql::armar_condicion_in($usuario_grupo_acc, 'apex_usuario_grupo_acc', 'usuario_grupo_acc');
		} else {
			$usuario_grupo_acc = toba::db()->quote($usuario_grupo_acc);
			$filtro = "apex_usuario_grupo_acc.usuario_grupo_acc = $usuario_grupo_acc";
		}
		return $this->get_grupos_acceso($filtro);
	}

	/**
	 * Retorna el listado de perfiles de datos del proyecto según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_perfiles_datos($where='')
	{
		if ($where) {
			$where = "AND $where";
		}
		$sql = "SELECT	apex_usuario_perfil_datos.usuario_perfil_datos,
						apex_usuario_perfil_datos.nombre
				FROM	apex_usuario_perfil_datos
				WHERE	apex_usuario_perfil_datos.proyecto = '".toba::proyecto()->get_id()."'
						$where
		";
		return toba::instancia()->get_db()->consultar($sql);
	}

	/**
	 * Valida un nombre de usuario.
	 *
	 * @param string $nombre_usuario
	 * @param integer $persona
	 * @return boolean
	 */
	function validar_nombre_usuario_unico($nombre_usuario, $persona)
	{
		if (!isset($nombre_usuario)) {
			return false;
		}
		$nombre_usuario = toba::db()->quote($nombre_usuario);
		$sql = 'SELECT	count(mdp_personas.persona) as cant
                FROM 	mdp_personas
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mdp_personas.usuario', $nombre_usuario).'
		';
		if (!is_null($persona)) {
			$persona = toba::db()->quote($persona);
			$sql .= " AND mdp_personas.persona <> $persona ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna el conjunto de perfiles de datos asociados a un usuario y un perfil funcional dados.
	 * 
	 * @param string $usuario
	 * @param string $perfil_funcional
	 * @return array
	 */
	function get_perfiles_datos_usuario_perfil_funcional($usuario, $perfil_funcional)
	{
		$usuario = toba::db()->quote($usuario);
		$perfil_funcional = toba::db()->quote($perfil_funcional);
		$sql = "SELECT	mdp_personas_perfil_datos.usuario_perfil_datos
				FROM	mdp_personas,
						mdp_personas_perfil_datos
				WHERE	mdp_personas.persona = mdp_personas_perfil_datos.persona AND
						mdp_personas.usuario = $usuario AND
						mdp_personas_perfil_datos.usuario_grupo_acc = $perfil_funcional
		";
		$rs = guarani_db::consultar($sql, null, false);
		return aplanar_matriz($rs, 'usuario_perfil_datos');
	}

	/**
	 * Retorna el perfil funcional configurado como "por defecto" para acceder al sistema de gestión.
	 * Si no existe ninguno retorna vacío.
	 * Si existe más de uno retorna el primero ordenado alfabéticamente por identificador de perfil.
	 * 
	 * @param string $usuario
	 * @return string
	 */
	function get_perfil_funcional_defecto_gestion($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		$tipo_usuario_gestion = toba::db()->quote(usuario::tipo_gestion);
		$sql = "SELECT		mdp_personas_grupo_acc.usuario_grupo_acc
				FROM		mdp_personas,
							mdp_personas_grupo_acc
				WHERE		mdp_personas.persona = mdp_personas_grupo_acc.persona AND
							mdp_personas.usuario = $usuario AND
							mdp_personas_grupo_acc.tipo_usuario = $tipo_usuario_gestion AND
							mdp_personas_grupo_acc.grupo_acceso_default = '".comunes::si."'
				ORDER BY	mdp_personas_grupo_acc.usuario_grupo_acc ASC
		";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['usuario_grupo_acc'];
	}
    
    /**
     * Retorna los usuarios de gestion con sus perfiles funcionales y perfiles de datos
     */
    function get_usuarios_gestion($where='')
    {
        if ($where) {
			$where = "AND $where";
		}
        $tipo_usuario_gestion = toba::db()->quote(usuario::tipo_gestion);
		$sql = "SELECT p.apellido_nombres,p.persona, pf.tipo_usuario ,pf.usuario_grupo_acc , pf.usuario_perfil_datos,
					p.tipo_nro_documento as identificacion 
					FROM vw_personas as p,
					mdp_personas_perfil_datos as pf
					WHERE	p.persona = pf.persona AND
                            pf.tipo_usuario = $tipo_usuario_gestion                 
                $where
                --GROUP BY 1,2,3,4,5
				ORDER BY	apellido,pf.usuario_grupo_acc ASC
						
		";
		return guarani_db::consultar($sql);
    }
    
}
