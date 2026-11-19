<?php
class cn_proc_generar_cursos_virtuales_comisiones_masivamente extends cn_procesos_bk
{
	//-----------------------------------------------------------------------------------
	//---- Funciones de manejo del proceso background -----------------------------------
	//-----------------------------------------------------------------------------------
	protected $s__proceso;
	protected $s__datos_curso_virtual;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_generar_cursos_virtuales_comisiones_masivamente::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_comisiones');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$datos = toba::consulta_php('co_comisiones')->get_listado_comisiones_curso_virtual($filtro);

		foreach ($datos as $key => $dato) {
			$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($dato['comision']);
			if ($key == 0) {
				$funciones = $this->get_moodle_obj()->verificarFunciones();
				if (!$this->get_moodle_obj()->verificarConexion() && !empty($funciones)) {

					if (!$this->get_moodle_obj()->verificarConexion()) {
						throw new toba_error_servicio_web('Error de conexion con  Moodle ');
					}
					if (!empty($funciones)) {
						throw new toba_error_servicio_web('Es necesario que habilite las siguientes funciones en Moodle: <em>' . implode(',', $funciones) . '</em>.');
					}
				}
			}
			//si el curso sigue vinculado en guarani compruebo qque exista
			if ($dato['curso_creado'] == 1) {
				$existe_curso_moodle = $this->get_moodle_obj()->getInfoCurso($dato['id_curso_externo']);

				if (!is_null($existe_curso_moodle)) {
					$datos[$key]['alumnos_sincronizados'] = $this->coinciden_alumnos_moodle($dato['comision'], $dato['curso']);
					$datos[$key]['docentes_sincronizados'] = $this->coinciden_docentes_moodle($dato['comision'], $dato['curso']);
				} else {
					//desvinculo de guarani si el curso no existe
					guarani::act('act_comisiones')->desvincular_curso_virtual_by_comision($dato['comision']);
					$datos[$key]['curso_creado'] = 0;
				}
			}

			if ($datos[$key]['alumnos_sincronizados'] === true) {
				$datos[$key]['alumnos_sincronizados'] = comunes::si_desc;
			} else {
				$datos[$key]['alumnos_sincronizados'] = comunes::no_desc;
			}
			if ($datos[$key]['docentes_sincronizados'] === true) {
				$datos[$key]['docentes_sincronizados'] = comunes::si_desc;
			} else {
				$datos[$key]['docentes_sincronizados'] = comunes::no_desc;
			}
			if ($datos[$key]['curso_creado'] == true) {
				$datos[$key]['curso_creado'] = comunes::si_desc;
			} else {
				$datos[$key]['curso_creado'] = comunes::no_desc;
			}
		}

		return $datos;
	}

	function coinciden_alumnos_moodle($comision, $datos_curso_virtual)
	{
		$alumnos_comision = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($comision);
		$alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($datos_curso_virtual, 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$alumnos_eliminados_comision = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($comision, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
		$alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
		list( $coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle(
						$alumnos_comision, $alumnos_eliminados_comision, $alumnos_curso_virtual_guarani, $alumnos_curso_virtual_moodle);
		return $coinciden_alumnos_moodle;
	}

	function get_alumnos_curso_virtual()
	{
		$curso_externo = $this->s__datos_curso_virtual['id_curso_externo'];
		$rol_alumno = $this->s__datos_curso_virtual['plataforma_rol_alumno'];
		$rol_curso = $this->s__datos_curso_virtual['plataforma_rol_alumno_curso'];

		return $this->get_moodle_obj()->getAlumnosByCurso($curso_externo, $rol_alumno, $rol_curso);
	}

	function coinciden_docentes_moodle($comision, $datos_curso_virtual)
	{
		$roles_docentes_plataforma = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->s__datos_curso_virtual['plataforma'], 'int_pv_roles_docentes.responsabilidad IS NOT NULL');
		$docentes_comision = toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($comision);
		$docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($datos_curso_virtual, 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$roles_docentes = array_unique(array_column($roles_docentes_plataforma, 'rol_externo'));
		$docentes_curso_virtual_moodle = $this->get_moodle_obj()->getDocentesByCurso($this->s__datos_curso_virtual['id_curso_externo'], $roles_docentes);
		list( $coinciden_docentes_moodle,
				$this->personas_para_crear,
				$this->docentes_para_matricular,
				$this->docentes_para_desmatricular) = moodle::coincidenDocentesMoodle(
						$docentes_comision, $docentes_curso_virtual_guarani, $docentes_curso_virtual_moodle);

		return $coinciden_docentes_moodle;
	}

	/**
	 * @return moodle Objeto de la clase moodle.
	 */
	function get_moodle_obj()
	{

		if (!isset($this->moodle_obj)) {
			$host	= $this->s__datos_curso_virtual['plataforma_host'] ?? null; 
			$url	= $this->s__datos_curso_virtual['plataforma_url'] ?? null; 
			$token	= $this->s__datos_curso_virtual['plataforma_token'] ?? null;
			$this->moodle_obj = new moodle($host, $url, $token);
		}

		return $this->moodle_obj;
	}

}