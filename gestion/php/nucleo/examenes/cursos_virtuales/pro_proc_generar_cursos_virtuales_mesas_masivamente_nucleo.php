<?php
class pro_proc_generar_cursos_virtuales_mesas_masivamente_nucleo extends guarani_procesos_bk
{
	protected $cant_curso;
	protected $cant_curso_error;
	protected $alumnos_sincronizados;
	protected $alumnos_sincronizados_error;
	protected $docentes_sincronizados;
	protected $docentes_sincronizados_error;

	protected $nombre_curso;
	protected $elemento_codigo;
	protected $elemento_nombre;
	protected $datos_curso_virtual;
	protected $personas_para_crear = array();

	protected $alumnos;
	protected $alumnos_eliminados;
	protected $alumnos_curso_virtual_guarani;
	protected $alumnos_curso_virtual_moodle;
	protected $alumnos_para_matricular = array();
	protected $alumnos_para_desmatricular = array();

	protected $docentes;
	protected $roles_docentes;
	protected $docentes_curso_virtual_guarani;
	protected $docentes_curso_virtual_moodle;
	protected $docentes_para_matricular = array();
	protected $docentes_para_desmatricular = array();

	protected $datos;
	protected $datos_formulario;
	protected $datos_plataforma;
	protected $moodle_obj = null;

	const rep_curso = 1;
	const rep_docentes = 2;
	const rep_alumnos = 3;
	const reporte_resultados = 4;
	const nombre = 'pro_proc_generar_cursos_virtuales_mesas_masivamente';
	const cant_personas_lote = 1000;

	function set_datos($datos)
	{
		$this->datos = $datos['datos'];
		$this->datos_formulario = $datos['datos_formulario'];
		$this->datos_plataforma = $datos['datos_plataforma'];

		$this->cant_curso = 0;
		$this->cant_curso_error = 0;
		$this->alumnos_sincronizados = 0;
		$this->alumnos_sincronizados_error = 0;
		$this->docentes_sincronizados = 0;
		$this->docentes_sincronizados_error = 0;
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->datos);

		foreach ($this->datos as $curso) {
			$this->docentes = toba::consulta_php('co_mesas_de_examen')->get_docentes_llamado_mesa_moodle($curso['llamado_mesa'], $this->datos_plataforma['plataforma']);
			$this->roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->datos_plataforma['plataforma'], 'int_pv_roles_docentes.rol IS NOT NULL');

			$estado = $this->get_estado();
			if (isset($estado['abortar']) && $estado['abortar']) {
				$this->abortar = true;
				break;
			}

			try {
				if ($curso['curso_creado'] == comunes::no_desc) {
					$fecha_inicio = DateTime::createFromFormat('Y-m-d', $curso['turno_examen_fecha_inicio']);
					$fecha_fin = DateTime::createFromFormat('Y-m-d', $curso['turno_examen_fecha_fin']);
					$nombre_curso = "{$curso['turno_examen_nombre']} - {$curso['llamado_turno_nombre']} - ({$curso['elemento_codigo']}) {$curso['elemento_nombre']} - {$curso['mesa_examen_nombre']}";
					$this->nombre_curso = substr($nombre_curso, 0, moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH);
					$nombre_curso_abreviado = substr($nombre_curso, 0, moodle::NOMBRE_CORTO_CURSO_MAXLENGTH);
					$this->elemento_codigo = $curso['elemento_codigo'];
					$this->elemento_nombre = $curso['elemento_nombre'];
					list($crear_curso_ok, $messageMoodle) = $this->crear_curso_moodle($curso['llamado_mesa'], $nombre_curso, $nombre_curso_abreviado, $this->datos_formulario['categoria'], '', 1, $fecha_inicio->getTimestamp(), $fecha_fin->getTimestamp());
					if ($crear_curso_ok) {
						toba::notificacion()->info(guarani_mensajes::get('800_creacion_exitosa_curso_moodle'));
						$this->datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($curso['llamado_mesa']);
						$this->alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($curso['llamado_mesa']);
						$this->alumnos_eliminados = array();
						$this->alumnos_curso_virtual_guarani = array();
						$this->alumnos_curso_virtual_moodle = array();
						$this->docentes_curso_virtual_guarani = array();
						$this->docentes_curso_virtual_moodle = array();
						$this->cant_curso++;
						$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => '');
						$this->log->set($reporte_curso, array(self::rep_curso));
						if ($this->datos_formulario['act_matricula_docentes']) {
							$this->actualizar_docentes_moodle();
						}
						if ($this->datos_formulario['act_matricula_alumnos']) {
							$this->actualizar_alumnos_moodle();
						}
					} else {
						$this->cant_curso_error++;
						$error = strip_tags(guarani_mensajes::get('800_fallo_creacion_curso_moodle', array($messageMoodle)));
						$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => $error);
						$this->log->set($reporte_curso, array(self::rep_curso));
					}
				} else {
					$this->datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($curso['llamado_mesa']);
					$this->alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($curso['llamado_mesa']);
					$this->alumnos_eliminados = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($curso['llamado_mesa'], 'alumnos_eliminados.usuario_moodle IS NOT NULL');
					$this->alumnos_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_curso_moodle($curso['llamado_mesa'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
					$this->docentes_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_docentes_curso_moodle($this->datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
					$this->nombre_curso = "{$curso['turno_examen_nombre']} - {$curso['llamado_turno_nombre']} - ({$curso['elemento_codigo']}) {$curso['elemento_nombre']} - {$curso['mesa_examen_nombre']}";
					$this->elemento_codigo = $curso['elemento_codigo'];
					$this->elemento_nombre = $curso['elemento_nombre'];
					if ($this->datos_formulario['act_matricula_docentes']) {
						$this->actualizar_docentes_moodle();
					}
					if ($this->datos_formulario['act_matricula_alumnos']) {
						$this->actualizar_alumnos_moodle();
					}
				}
			} catch (toba_error $e) {
				$this->log_estado->set_error();
				$curso['observaciones'] = $e->get_mensaje();
			}

			$this->log->guardar();
			if (!$this->abortar) {
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		$mensaje_resultado['cursos_creados_vinculados'] = $this->cant_curso;
		$mensaje_resultado['cursos_creados_vinculados_error'] = $this->cant_curso_error;
		$mensaje_resultado['alumnos_sincronizados'] = $this->alumnos_sincronizados;
		$mensaje_resultado['alumnos_sincronizados_error'] = $this->alumnos_sincronizados_error;
		$mensaje_resultado['docentes_sincronizados'] = $this->docentes_sincronizados;
		$mensaje_resultado['docentes_sincronizados_error'] = $this->docentes_sincronizados_error;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_moodle_obj()
	{
		if (!isset($this->moodle_obj)) {
			$this->moodle_obj = new moodle($this->datos_plataforma['host'], $this->datos_plataforma['url'], $this->datos_plataforma['token']);
		}
		return $this->moodle_obj;
	}

	function get_alumnos_curso_virtual()
	{
		$curso_externo = $this->datos_curso_virtual['id_curso_externo'];
		$rol_alumno = $this->datos_plataforma['rol_alumno'];
		$rol_curso = $this->datos_plataforma['rol_alumno_curso'];
		return $this->get_moodle_obj()->getAlumnosByCurso($curso_externo, $rol_alumno, $rol_curso);
	}

	function get_docentes_curso_virtual()
	{
		$curso_externo = $this->datos_curso_virtual['id_curso_externo'];
		$roles_docentes = array_unique(array_column($this->roles_docentes, 'rol_externo'));
		return $this->get_moodle_obj()->getDocentesByCurso($curso_externo, $roles_docentes);
	}

	function crear_curso_moodle($llamado_mesa, $fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null)
	{
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->crearAsociarCursoLlamadoMesa(
				$this->datos_plataforma['plataforma'],
				$llamado_mesa,
				$fullname,
				$shortname,
				$categoryid,
				$summary,
				$visible,
				$startdate,
				$enddate,
				true, 
				false
		);
		if ($creacion_ok) {
			return array(true, '-');
		} else {
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_creacion_curso_moodle', [$messageMoodle]));
			return array(false, $messageMoodle);
		}
	}

	function set_log_reporte($alumnos, $accion, $apellido_nombres, $identificacion, $usuario, $rol, $error='')
	{
		$datos = array(
			'elemento_codigo' => $this->elemento_codigo,
			'actividad' => $this->elemento_nombre,
			'curso_virtual' => $this->nombre_curso,
			'accion' => $accion,
			'apellido_nombre' => $apellido_nombres,
			'identificacion' => $identificacion,
			'usuario' => $usuario,
			'rol' => $rol,
			'error' => $error);
		$reporte = $alumnos ? self::rep_alumnos : self::rep_docentes;
		$this->log->set($datos, array($reporte));
	}

	function set_sincronizados($alumnos, $cantidad)
	{
		if ($alumnos) {
			$this->alumnos_sincronizados = $this->alumnos_sincronizados + $cantidad;
		} else {
			$this->docentes_sincronizados = $this->docentes_sincronizados + $cantidad;
		}
	}

	function set_sincronizados_error($alumnos, $cantidad)
	{
		if ($alumnos) {
			$this->alumnos_sincronizados_error = $this->alumnos_sincronizados_error + $cantidad;
		} else {
			$this->docentes_sincronizados_error = $this->docentes_sincronizados_error + $cantidad;
		}
	}

	function crear_personas_moodle($alumnos)
	{
		if (!$alumnos) {
			$roles = rs_convertir_asociativo(toba::consulta_php('co_tribunal_roles')->get_listado(), array('rol'), 'nombre');
		}
		$personas_ids = array_column($this->personas_para_crear, 'persona');
		$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);
		$usuarios = array();
		$personas_usuarios = array();
		$personas_sin_email = array();
		foreach ($this->personas_para_crear as $clave => $persona) {
			if (empty($emails_personas[$persona['persona']]['email'])) {
				$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
				if ($alumnos) {
					$role_moodle = ($persona['propuesta_tipo'] == propuesta::tipo_curso) ? 'Alumno curso' : 'Alumno';
				} else {
					$role_moodle = $roles[$persona['rol_docente']];
				}
				$error = strip_tags(guarani_mensajes::get('800_personas_falta_email_matriculacion_moodle', ['alumnos/s', "{$persona['apellido']} {$persona['nombres']}"]));
				$this->set_log_reporte($alumnos, 'Crear en Moodle', "{$persona['apellido']} {$persona['nombres']}", "{$persona['desc_tipo_documento']} N° {$persona['nro_documento']}", $persona['usuario'], $role_moodle, $error);
				continue;
			}
			$usuarios[$clave]['username'] = $persona['usuario'];
			$usuarios[$clave]['firstname'] = $persona['nombres'];
			$usuarios[$clave]['lastname'] = $persona['apellido'];
			$usuarios[$clave]['email'] = $emails_personas[$persona['persona']]['email'];
			$usuarios[$clave]['idnumber'] = $persona['persona'];
			$personas_usuarios[$persona['usuario']] = $persona;
		}
		if (!empty($personas_sin_email)) {
			toba::logger()->error(guarani_mensajes::get('800_personas_falta_email_matriculacion_moodle', ['alumnos/s', implode(', ', $personas_sin_email)]));
			$this->set_sincronizados_error($alumnos, guarani_arreglos::count($personas_sin_email));
			return false;
		}
		$lote_usuarios = array_chunk($usuarios, static::cant_personas_lote, true);
		foreach ($lote_usuarios as $usuarios) {
			try {
				$usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);
				foreach ($usuarios as $usuario) {
					$this->set_log_reporte($alumnos, 'Crear en Moodle', "{$usuario['lastname']} {$usuario['firstname']}", $usuario['email'], $usuario['username'], '');
				}
			} catch (Exception $e) {
				$mensaje = $alumnos ? '800_fallo_actualizacion_alumnos_moodle' : '800_fallo_actualizacion_docentes_moodle';
				$error = strip_tags(guarani_mensajes::get($mensaje, array($e->getMessage())));
				foreach ($usuarios as $usuario) {
					$this->set_log_reporte($alumnos, 'Crear en Moodle', "{$usuario['lastname']} {$usuario['firstname']}", $usuario['email'], $usuario['username'], '', $error);
				}
				$this->set_sincronizados_error($alumnos, guarani_arreglos::count($usuarios));
				throw $e;
			}
			if (!empty($usuarios_moodle)) {
				$usuarios_pv = array();
				foreach ($usuarios_moodle as $usuario_moodle) {
					$usuarios_pv[] = array($this->datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']);
					$personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
					$this->alumnos_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];
				}
				guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);
			}
		}
		return true;
	}

	function matricular_alumnos_docentes_moodle($alumnos, $alumnos_docentes_para_matricular)
	{
		if (!$alumnos) {
			$roles = rs_convertir_asociativo(toba::consulta_php('co_tribunal_roles')->get_listado(), array('rol'), 'nombre');
		}
		$matriculaciones = array();
		foreach ($alumnos_docentes_para_matricular as $clave => $alumno_docente) {
			if ($alumnos) {
				$role_moodle = ($alumno_docente['propuesta_tipo'] == propuesta::tipo_curso) ? $this->datos_plataforma['rol_alumno_curso'] : $this->datos_plataforma['rol_alumno'];
			} else {
				$role_moodle = $alumno_docente['rol_moodle'];
			}
			$matriculaciones[$clave]['roleid'] = $role_moodle;
			$matriculaciones[$clave]['userid'] = $alumno_docente['usuario_moodle'];
			$matriculaciones[$clave]['courseid'] = $this->datos_curso_virtual['id_curso_externo'];
		}
		$lote_matriculaciones = array_chunk($matriculaciones, static::cant_personas_lote, true);
		foreach ($lote_matriculaciones as $matriculaciones) {
			try {
				$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
				foreach ($matriculaciones as $clave => $matriculacion) {
					if ($alumnos) {
						$role_moodle = ($alumnos_docentes_para_matricular[$clave]['propuesta_tipo'] == propuesta::tipo_curso) ? 'Alumno curso' : 'Alumno';
					} else {
						$role_moodle = $roles[$alumnos_docentes_para_matricular[$clave]['rol_docente']];
					}
					$this->set_log_reporte($alumnos, 'Matricular en Moodle', "{$alumnos_docentes_para_matricular[$clave]['apellido']} {$alumnos_docentes_para_matricular[$clave]['nombres']}", "{$alumnos_docentes_para_matricular[$clave]['desc_tipo_documento']} N° {$alumnos_docentes_para_matricular[$clave]['nro_documento']}", $alumnos_docentes_para_matricular[$clave]['usuario'], $role_moodle);
				}
				$this->set_sincronizados($alumnos, guarani_arreglos::count($matriculaciones));
			} catch (Exception $e) {
				$mensaje = $alumnos ? '800_fallo_actualizacion_alumnos_moodle' : '800_fallo_actualizacion_docentes_moodle';
				$error = strip_tags(guarani_mensajes::get($mensaje, array($e->getMessage())));
				foreach ($matriculaciones as $clave => $matriculacion) {
					if ($alumnos) {
						$role_moodle = ($alumnos_docentes_para_matricular[$clave]['propuesta_tipo'] == propuesta::tipo_curso) ? 'Alumno curso' : 'Alumno';
					} else {
						$role_moodle = $roles[$alumnos_docentes_para_matricular[$clave]['rol_docente']];
					}
					$this->set_log_reporte($alumnos, 'Matricular en Moodle', "{$alumnos_docentes_para_matricular[$clave]['apellido']} {$alumnos_docentes_para_matricular[$clave]['nombres']}", "{$alumnos_docentes_para_matricular[$clave]['desc_tipo_documento']} N° {$alumnos_docentes_para_matricular[$clave]['nro_documento']}", $alumnos_docentes_para_matricular[$clave]['usuario'], $role_moodle, $error);
				}
				$this->set_sincronizados_error($alumnos, guarani_arreglos::count($matriculaciones));
				throw $e;
			}
		}
	}

	function desmatricular_alumnos_docentes_moodle($alumnos, $alumnos_docentes_para_desmatricular)
	{
		$desmatriculaciones = array();
		if ($alumnos) {
			$personas_desmatricular = array();
			foreach ($this->alumnos_eliminados as $alumno_eliminado) {
				$personas_desmatricular[$alumno_eliminado['usuario_moodle']] = $alumno_eliminado;
			}
		}
		foreach ($alumnos_docentes_para_desmatricular as $clave => $alumno_docente_para_desmatricular) {
			$desmatriculaciones[$clave]['userid'] = $alumno_docente_para_desmatricular;
			$desmatriculaciones[$clave]['courseid'] = $this->datos_curso_virtual['id_curso_externo'];
		}
		$lote_desmatriculaciones = array_chunk($desmatriculaciones, static::cant_personas_lote, true);
		foreach ($lote_desmatriculaciones as $desmatriculaciones) { 
			try {
				$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
				foreach ($desmatriculaciones as $desmatriculacion) {
					$apellido_nombres = $alumnos ? "{$personas_desmatricular[$desmatriculacion['userid']]['apellido']} {$personas_desmatricular[$desmatriculacion['userid']]['nombres']}" : '';
					$identificacion = $alumnos ? "{$personas_desmatricular[$desmatriculacion['userid']]['desc_tipo_documento']} N° {$personas_desmatricular[$desmatriculacion['userid']]['nro_documento']}" : '';
					$usuario = $alumnos ? $personas_desmatricular[$desmatriculacion['userid']]['usuario'] : $desmatriculacion['userid'];
					$rol = $alumnos ? 'Alumno' : $desmatriculacion['roleid'];
					$this->set_log_reporte($alumnos, 'Desmatricular en Moodle', $apellido_nombres, $identificacion, $usuario, $rol);
				}
				$this->set_sincronizados($alumnos, guarani_arreglos::count($desmatriculaciones));
			} catch (Exception $e) {
				$mensaje = $alumnos ? '800_fallo_actualizacion_alumnos_moodle' : '800_fallo_actualizacion_docentes_moodle';
				$error = strip_tags(guarani_mensajes::get($mensaje, array($e->getMessage())));
				foreach ($desmatriculaciones as $desmatriculacion) {
					$apellido_nombres = $alumnos ? "{$personas_desmatricular[$desmatriculacion['userid']]['apellido']} {$personas_desmatricular[$desmatriculacion['userid']]['nombres']}" : '';
					$identificacion = $alumnos ? "{$personas_desmatricular[$desmatriculacion['userid']]['desc_tipo_documento']} N° {$personas_desmatricular[$desmatriculacion['userid']]['nro_documento']}" : '';
					$usuario = $alumnos ? $personas_desmatricular[$desmatriculacion['userid']]['usuario'] : $desmatriculacion['userid'];
					$rol = $alumnos ? 'Alumno' : $desmatriculacion['roleid'];
					$this->set_log_reporte($alumnos, 'Desmatricular en Moodle', $apellido_nombres, $identificacion, $usuario, $rol, $error);
				}
				$this->set_sincronizados_error($alumnos, guarani_arreglos::count($desmatriculaciones));
				throw $e;
			}
		}
	}

	function coinciden_alumnos_moodle()
	{
		list(	$coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle($this->alumnos, $this->alumnos_eliminados, $this->alumnos_curso_virtual_guarani, $this->alumnos_curso_virtual_moodle);
		return $coinciden_alumnos_moodle;
	}

	function actualizar_alumnos_moodle()
	{
		try {
			$coinciden_alumnos_moodle = $this->coinciden_alumnos_moodle();
			if ($coinciden_alumnos_moodle) {
				return true;
			}
			if (!empty($this->personas_para_crear)) {
				if (!$this->crear_personas_moodle(true)) {
					return false;
				}
			}
			if (!empty($this->alumnos_para_matricular)) {
				$this->matricular_alumnos_docentes_moodle(true, $this->alumnos_para_matricular);
			}
			if (!empty($this->alumnos_para_desmatricular)) {
				$this->desmatricular_alumnos_docentes_moodle(true, $this->alumnos_para_desmatricular);
			}
			return true;
		} catch (Exception $e) {
			toba::logger()->error(guarani_mensajes::get('800_fallo_actualizacion_alumnos_moodle', array($e->getMessage())));
			return false;
		}
	}

	function coinciden_docentes_moodle()
	{
		list(	$coinciden_docentes_moodle,
				$this->personas_para_crear,
				$this->docentes_para_matricular,
				$this->docentes_para_desmatricular) = moodle::coincidenDocentesMoodle($this->docentes, $this->docentes_curso_virtual_guarani, $this->docentes_curso_virtual_moodle);
		return $coinciden_docentes_moodle;
	}

	function actualizar_docentes_moodle()
	{
		try {
			$coinciden_docentes_moodle = $this->coinciden_docentes_moodle();
			if ($coinciden_docentes_moodle) {
				return true;
			}
			if (!empty($this->personas_para_crear)) {
				if (!$this->crear_personas_moodle(false)) {
					return false;
				}
			}
			if (!empty($this->docentes_para_matricular)) {
				$this->matricular_alumnos_docentes_moodle(false, $this->docentes_para_matricular);
			}
			if (!empty($this->docentes_para_desmatricular)) {
				$this->desmatricular_alumnos_docentes_moodle(false, $this->docentes_para_desmatricular);
			}
			return true;
		} catch (Exception $e) {
			toba::logger()->error(guarani_mensajes::get('800_fallo_actualizacion_docentes_moodle', array($e->getMessage())));
			return false;
		}
	}
}
?>