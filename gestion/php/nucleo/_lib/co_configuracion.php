<?php
/**
 *
 *
 * @package		Administración
 * @subpackage	Configuración
 * 
 */
class co_configuracion
{
	/**
	 * Obtiene una lista de los parámetros de configuración del Sistema según el filtro recibido
	 */
	function get_listado($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}

		$sql = "	SELECT 	par_configuraciones.nombre,
							par_configuraciones.tipo_de_dato,
							par_configuraciones.valor,
							par_configuraciones.descripcion
					FROM 	par_configuraciones
							$where
					ORDER BY par_configuraciones.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de un parámetro de configuración del Sistema
	 */
	function get_datos_config($parametro, $dato = null)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$where = " upper(par_configuraciones.nombre) = $parametro ";
		$datos = current($this->get_listado($where));
		return isset($dato) ? $datos[$dato] : $datos;
	}

	/**
	 * Obtiene el nombre de un parámetro de configuración del Sistema
	 */
	function get_nombre_config($parametro)
	{
		return $this->get_datos_config($parametro, 'nombre');
	}

	/**
	 * Obtiene el valor de un parámetro de configuración del Sistema
	 */
	function get_valor_config($parametro)
	{
		return $this->get_datos_config($parametro, 'valor');
	}

	/**
	 * Obtiene el Tipo de Dato de un parámetro de configuración del Sistema
	 */
	function get_tipo_dato_config($parametro)
	{
		return $this->get_datos_config($parametro, 'tipo_de_dato');
	}

	/**
	 * Obtiene el Tipo de Dato de un parámetro de configuración del Sistema
	 */
	function get_descripcion_config($parametro)
	{
		return $this->get_datos_config($parametro, 'descripcion');
	}

	/**
	 * Obtiene las extensiones de archivos permitidos para RDI
	 */
	function get_rdi_extensiones_permitidas()
	{
		$sql = "SELECT extension FROM rdi_extensiones_permitidas ;";
		$extensiones = guarani_db::consultar($sql);
		$salida = array();
		foreach ($extensiones as $extension) {
			$salida[] = guarani_texto::a_minusculas($extension['extension']);
		}
		return $salida;
	}

	/**
	 * Evalúa si una extensión está permitida
	 */
	function es_rdi_extension_permitida($extension)
	{
		$extension = guarani_texto::a_minusculas($extension);
		return (in_array($extension, $this->get_rdi_extensiones_permitidas()));
	}

	/**
	 * Obtiene el tamaño máximo de un archivo para subir
	 */
	function get_tamanio_maximo_archivo()
	{
		$max_tamanio_archivo = preg_replace('/[^0-9\.]/', '', ini_get('upload_max_filesize'));
		$max_tamanio_post = preg_replace('/[^0-9\.]/', '', ini_get('post_max_size'));

		if ($max_tamanio_archivo < $max_tamanio_post) {
			return $max_tamanio_archivo;
		} else {
			return $max_tamanio_post;
		}
	}

	
	/**
	 * Obtiene una lista de las Plataformas de Educación Virtual
	 */
	function get_plataformas_educacion_virtual($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "	SELECT 	int_pv_plataformas.plataforma,
							int_pv_plataformas.nombre,
							int_pv_plataformas.url,
							int_pv_plataformas.host,
							int_pv_plataformas.token,
							int_pv_plataformas.rol_alumno,
							int_pv_plataformas.rol_alumno_curso,
							int_pv_plataformas.categoria_curso_default,
							int_pv_plataformas.activa
					FROM int_pv_plataformas
					$where
					ORDER BY int_pv_plataformas.nombre DESC, int_pv_plataformas.host DESC
				;";
		return guarani_db::consultar($sql, null, false);
	}
	
	/**
	 * Obtiene datos de una Plataforma de Educación Virtual
	 */
	function get_plataforma_educacion_virtual($plataforma)
	{
		$plataforma = toba::db()->quote($plataforma);
		$filtro = "int_pv_plataformas.plataforma = $plataforma";
		$datos = $this->get_plataformas_educacion_virtual($filtro);
		return current($datos);
	}
	
	/**
	 * Verifica si la plataforma tiene datos asociados (usuarios, cursos, etc.)
	 */
	function plataforma_tiene_datos_asociados($plataforma)
	{
		$plataforma = toba::db()->quote($plataforma);
		
		$sql = "SELECT EXISTS(
					SELECT 1 FROM int_pv_cursos WHERE plataforma = $plataforma
					UNION
					SELECT 1 FROM int_pv_usuarios WHERE plataforma = $plataforma
				) AS tiene_datos_asociados
				;";
		
		$row = guarani_db::consultar_fila($sql);
		return ($row['tiene_datos_asociados'] == 't');
	}
	
	/**
	 * Obtiene una lista de las RAs disponibles para una Plataforma de Educación Virtual
	 */
	function get_ras_disponibles_by_plataforma($plataforma)
	{
		$plataforma = toba::db()->quote($plataforma);
		
		$sql = "	SELECT	sga_responsables_academicas.responsable_academica AS valor,
							sga_responsables_academicas.nombre AS descr
					FROM int_pv_plataformas_ra
					JOIN sga_responsables_academicas ON (sga_responsables_academicas.responsable_academica = int_pv_plataformas_ra.responsable_academica)
					WHERE int_pv_plataformas_ra.plataforma = $plataforma

					UNION

					SELECT	sga_responsables_academicas.responsable_academica AS valor, 
							sga_responsables_academicas.nombre AS descr
					FROM sga_responsables_academicas
					WHERE sga_responsables_academicas.responsable_academica NOT IN (SELECT responsable_academica FROM int_pv_plataformas_ra)

					ORDER BY valor
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Averigua si las RAs ya están asignadas a otras Plataformas de Educación Virtual
	 */
	function get_ras_otras_plataformas($plataforma, $ras)
	{
		$plataforma = toba::db()->quote($plataforma);
		$ras = implode(', ', $ras);
		
		$sql = "	SELECT	sga_responsables_academicas.responsable_academica AS valor,
							sga_responsables_academicas.nombre AS descr
					FROM int_pv_plataformas_ra
					JOIN sga_responsables_academicas ON (sga_responsables_academicas.responsable_academica = int_pv_plataformas_ra.responsable_academica)
					WHERE int_pv_plataformas_ra.plataforma != $plataforma
					AND int_pv_plataformas_ra.responsable_academica IN ($ras)
				;";
		return guarani_db::consultar($sql);
	}
	
		
	/**
	 * Averigua si las RAs ya tienen cursos asociados en Moodle
	 */
	function get_ras_cant_cursos_asociados($ras)
	{
		$ras = implode(', ', $ras);
		
		$sql = "	SELECT	sga_responsables_academicas.responsable_academica	AS ra,
							sga_responsables_academicas.nombre					AS ra_nombre,
							COUNT(int_pv_cursos_comisiones.curso)				AS cant_cursos
					FROM sga_responsables_academicas
					LEFT JOIN sga_elementos_ra			ON (sga_elementos_ra.responsable_academica = sga_responsables_academicas.responsable_academica)
					LEFT JOIN sga_elementos				ON (sga_elementos.elemento = sga_elementos_ra.elemento)
					LEFT JOIN sga_comisiones			ON (sga_comisiones.elemento = sga_elementos.elemento)
					LEFT JOIN int_pv_cursos_comisiones	ON (sga_comisiones.comision = int_pv_cursos_comisiones.comision)
					WHERE sga_responsables_academicas.responsable_academica IN ($ras)
					GROUP BY sga_responsables_academicas.responsable_academica
					ORDER BY sga_responsables_academicas.responsable_academica
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene datos de un curso externo de una plataforma dada
	 */
	function get_datos_curso_externo($curso_externo, $plataforma)
	{
		$curso_externo = toba::db()->quote($curso_externo);
		$plataforma = toba::db()->quote($plataforma);
		
		$sql = "	SELECT	curso,
							id_curso_externo,
							nombre,
							plataforma
					FROM int_pv_cursos
					WHERE id_curso_externo = $curso_externo
					AND plataforma = $plataforma
				;";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene datos de todos los cursos externos de una plataforma dada
	 */
	function get_cursos_por_plataforma($plataforma)
	{
		$plataforma = toba::db()->quote($plataforma);
		
		$sql = "	SELECT	curso,
							id_curso_externo,
							nombre,
							plataforma
					FROM int_pv_cursos
					WHERE plataforma = $plataforma
				;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los roles de docentes en las cursadas o mesas de examen de una plataforma dada.
	 * 
	 * @param int $plataforma
	 * @param string $where
	 * @return array
	 */
	function get_roles_docentes_by_plataforma($plataforma, $where='')
	{
		$plataforma = toba::db()->quote($plataforma);
		if ($where) {
			$where = "AND $where";
		}
		$sql = "SELECT	int_pv_roles_docentes.plataforma, 
						int_pv_roles_docentes.rol_externo,
						int_pv_roles_docentes.responsabilidad, 
						int_pv_roles_docentes.rol
				FROM	int_pv_roles_docentes
				WHERE	int_pv_roles_docentes.plataforma = $plataforma
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos de la plataforma virtual vinculada a una responsable académica dada.
	 * 
	 * @param int $responsable_academica
	 * @return array
	 */
	function get_plataforma_responsable_academica($responsable_academica)
	{
		$responsable_academica = toba::db()->quote($responsable_academica);
		$sql = "SELECT 	int_pv_plataformas.plataforma,
						int_pv_plataformas.nombre,
						int_pv_plataformas.url,
						int_pv_plataformas.host,
						int_pv_plataformas.token,
						int_pv_plataformas.rol_alumno,
						int_pv_plataformas.rol_alumno_curso,
						int_pv_plataformas.categoria_curso_default,
						int_pv_plataformas.activa
				FROM	int_pv_plataformas
						JOIN int_pv_plataformas_ra ON int_pv_plataformas.plataforma = int_pv_plataformas_ra.plataforma
				WHERE	int_pv_plataformas_ra.responsable_academica = $responsable_academica AND
						int_pv_plataformas.activa = TRUE
				LIMIT	1
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los usuarios de una plataforma dada
	 */
	function get_usuarios_by_plataforma($plataforma)
	{
		
		$plataforma = toba::db()->quote($plataforma);
		
		$sql = "	SELECT	plataforma, 
							persona,
							id_usuario_externo
					FROM int_pv_usuarios
					WHERE plataforma = $plataforma
				;";
		
		return guarani_db::consultar($sql, null, false);
	}
	
	/**
	 * Obtiene los usuarios de una persona dada
	 */
	function get_usuarios_by_persona($persona)
	{
		
		$persona = toba::db()->quote($persona);
		
		$sql = "	SELECT 	int_pv_plataformas.plataforma,
							int_pv_plataformas.nombre,
							int_pv_plataformas.url,
							int_pv_plataformas.host,
							int_pv_plataformas.token,
							int_pv_plataformas.activa,
							int_pv_usuarios.persona,
							int_pv_usuarios.id_usuario_externo
					FROM int_pv_usuarios
					JOIN int_pv_plataformas ON (int_pv_usuarios.plataforma = int_pv_plataformas.plataforma)
					WHERE persona = $persona
				;";
		
		return guarani_db::consultar($sql);
	}
        function get_persona_idMoodle($idsMoodle)
        {
            	
		$idsMoodle = implode( ', ', $idsMoodle);
                
		$sql = " SELECT
                        int_pv_usuarios.id_usuario_externo
                        FROM int_pv_usuarios
        		WHERE int_pv_usuarios.id_usuario_externo in( $idsMoodle);";
		
		return guarani_db::consultar($sql);
                
        }	

	/**
	 * Retorna una lista de las responsables académicas disponibles en plataformas de educación virtual.
	 * 
	 * @return array
	 */
	function get_ras_disponibles_en_plataformas()
	{
		$sql = "SELECT		sga_responsables_academicas.responsable_academica as valor, 
							sga_responsables_academicas.nombre as descr
				FROM		int_pv_plataformas_ra
							JOIN sga_responsables_academicas ON int_pv_plataformas_ra.responsable_academica = sga_responsables_academicas.responsable_academica
				ORDER BY	sga_responsables_academicas.nombre
		";
		return guarani_db::consultar($sql);
	}
}