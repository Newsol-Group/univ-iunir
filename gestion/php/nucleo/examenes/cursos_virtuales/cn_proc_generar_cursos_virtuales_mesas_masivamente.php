<?php
class cn_proc_generar_cursos_virtuales_mesas_masivamente extends cn_procesos_bk
{
	protected $s__proceso;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_proc_generar_cursos_virtuales_mesas_masivamente::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_mesas_examen');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$datos = toba::consulta_php('co_mesas_de_examen')->get_llamados_mesa_curso_virtual($filtro);
		foreach ($datos as $clave => $valor) {
			if ($valor['curso_creado']) {
				$datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($valor['llamado_mesa']);
				if (!empty($datos_curso_virtual)) {
					$moodle_obj = new moodle($datos_curso_virtual['plataforma_host'], $datos_curso_virtual['plataforma_url'], $datos_curso_virtual['plataforma_token']);
					if ($moodle_obj->verificarConexion()) {
						$funciones_faltantes_moodle = $moodle_obj->verificarFunciones();
						if (!empty($funciones_faltantes_moodle)) {
							throw new toba_error_servicio_web('Es necesario que habilite las siguientes funciones en Moodle: <em>' . implode(',', $funciones_faltantes_moodle) . '</em>.');
						}
					} else {
						throw new toba_error_servicio_web(guarani_mensajes::get('800_error_comunicacion_moodle'));
					}
					$existe_curso_moodle = $moodle_obj->getInfoCurso($valor['id_curso_externo']);
					if (!is_null($existe_curso_moodle)) {
						$datos[$clave]['alumnos_sincronizados'] = $this->coinciden_alumnos_moodle($valor['llamado_mesa'], $datos_curso_virtual, $moodle_obj);
						$datos[$clave]['docentes_sincronizados'] = $this->coinciden_docentes_moodle($valor['llamado_mesa'], $datos_curso_virtual, $moodle_obj);
					} else {
						guarani::act('act_mesas_de_examen')->desvincular_curso_virtual_llamado_mesa($valor['llamado_mesa']);
						$datos[$clave]['curso_creado'] = 0;
					}
				}
			}
			$datos[$clave]['curso_creado'] = $datos[$clave]['curso_creado'] ? comunes::si_desc : comunes::no_desc;
			$datos[$clave]['alumnos_sincronizados'] = ($datos[$clave]['alumnos_sincronizados'] === true) ? comunes::si_desc : comunes::no_desc;
			$datos[$clave]['docentes_sincronizados'] = ($datos[$clave]['docentes_sincronizados'] === true) ? comunes::si_desc : comunes::no_desc;
		}
		return $datos;
	}

	function coinciden_alumnos_moodle($llamado_mesa, $datos_curso_virtual, $moodle_obj)
	{
		$alumnos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($llamado_mesa);
		$alumnos_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_curso_moodle($datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$alumnos_eliminados_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($llamado_mesa, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
		$alumnos_curso_virtual_moodle = $moodle_obj->getAlumnosByCurso($datos_curso_virtual['id_curso_externo'], $datos_curso_virtual['plataforma_rol_alumno'], $datos_curso_virtual['plataforma_rol_alumno_curso']);
		list(	$coinciden_alumnos_moodle,
				$personas_para_crear,
				$alumnos_para_matricular,
				$alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle(
						$alumnos_llamado_mesa, $alumnos_eliminados_llamado_mesa, $alumnos_curso_virtual_guarani, $alumnos_curso_virtual_moodle);
		return $coinciden_alumnos_moodle;
	}

	function coinciden_docentes_moodle($llamado_mesa, $datos_curso_virtual, $moodle_obj)
	{
		$roles_docentes_plataforma = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($datos_curso_virtual['plataforma'], 'int_pv_roles_docentes.rol IS NOT NULL');
		$docentes_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_docentes_llamado_mesa_moodle($llamado_mesa);
		$docentes_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_docentes_curso_moodle($datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$roles_docentes = array_unique(array_column($roles_docentes_plataforma, 'rol_externo'));
		$docentes_curso_virtual_moodle = $moodle_obj->getDocentesByCurso($datos_curso_virtual['id_curso_externo'], $roles_docentes);
		list( $coinciden_docentes_moodle,
			$personas_para_crear,
			$docentes_para_matricular,
			$docentes_para_desmatricular) = moodle::coincidenDocentesMoodle(
					$docentes_llamado_mesa, $docentes_curso_virtual_guarani, $docentes_curso_virtual_moodle);
		return $coinciden_docentes_moodle;
	}
}
?>