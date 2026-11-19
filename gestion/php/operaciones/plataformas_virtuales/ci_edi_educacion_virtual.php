<?php
/**
 *
 *
 * @package		Plataformas Virtuales
 */
class ci_edi_educacion_virtual extends guarani_pers_ci
{
	// Datos relacionados a Moodle.
	protected $s__alumnos;
	protected $s__alumnos_eliminados;
	protected $s__alumnos_curso_virtual_guarani;
	protected $s__alumnos_curso_virtual_moodle;
	protected $s__docentes;
	protected $s__docentes_curso_virtual_guarani;
	protected $s__docentes_curso_virtual_moodle;
	protected $s__datos_plataforma;
	protected $s__plataforma_seleccionada = null;
	protected $s__roles_docentes;
	protected $s__funciones_faltantes_moodle;
	/**
	 * @var moodle $moodle_obj Objeto de la clase moodle
	 */
	protected $moodle_obj = null;
	protected $s__datos_curso_virtual;
	protected $info_curso_moodle = null;

	protected $personas_para_crear = [];
	protected $alumnos_para_matricular = [];
	protected $alumnos_para_desmatricular = [];
	protected $docentes_para_matricular = [];
	protected $docentes_para_desmatricular = [];

	protected $crear_curso = false;	
	protected $crear_grupo_curso = false;
	protected $vincular_curso = false;

	protected $datos_form_crear;
	protected $datos_form_vincular;

	function resetear_vars()
	{
		unset($this->s__plataforma_seleccionada);
		unset($this->s__datos_plataforma);
	}

	/**
	 * @return moodle Objeto de la clase moodle.
	 */
	function get_moodle_obj()
	{
		if (!isset($this->moodle_obj)) {
			$this->moodle_obj = new moodle($this->s__datos_plataforma['host'], $this->s__datos_plataforma['url'], $this->s__datos_plataforma['token']);
		}

		return $this->moodle_obj;
	}

	function get_info_curso_virtual()
	{
		if (!isset($this->info_curso_moodle)) {
			$this->info_curso_moodle = $this->get_moodle_obj()->getInfoCurso($this->s__datos_curso_virtual['id_curso_externo']);
		}
		
		return $this->info_curso_moodle;
	}

	function get_alumnos_curso_virtual()
	{
		$curso_externo	= $this->s__datos_curso_virtual['id_curso_externo'];
		$rol_alumno		= $this->s__datos_plataforma['rol_alumno'];
		$rol_curso		= $this->s__datos_plataforma['rol_alumno_curso'];

		return $this->get_moodle_obj()->getAlumnosByCurso($curso_externo, $rol_alumno, $rol_curso);
	}

	function get_docentes_curso_virtual()
	{
		$curso_externo = $this->s__datos_curso_virtual['id_curso_externo'];
		$roles_docentes = array_unique(array_column($this->s__roles_docentes, 'rol_externo'));

		return $this->get_moodle_obj()->getDocentesByCurso($curso_externo, $roles_docentes);
	}

	function get_categorias_cursos_moodle()
	{
		return $this->get_moodle_obj()->getCategoriasCursos();
	}

	function get_cursos_moodle()
	{
		return $this->get_moodle_obj()->getCursos();
	}

	function get_cursos_moodle_de_docentes()
	{
		// Obtengo los docentes ya creados en Moodle
		$docentes_moodle = array_filter($this->s__docentes, function($docente) {
			return !is_null($docente['usuario_moodle']);
		});

		$docentes_moodle = array_column($docentes_moodle, 'usuario_moodle');

		return $this->get_moodle_obj()->getCursosByUsuarios($docentes_moodle);
	}

	function coinciden_alumnos_moodle($es_desvinculacion = false)
	{
		list(	$coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle(
							$this->s__alumnos,
							$this->s__alumnos_eliminados,
							$this->s__alumnos_curso_virtual_guarani,
							$this->s__alumnos_curso_virtual_moodle,
							$es_desvinculacion);

		return $coinciden_alumnos_moodle;
	}

	function actualizar_alumnos_moodle($es_desvinculacion = false)
	{
		//toba::db()->abrir_transaccion();

		try {
			
			// Si no hay que actualizar en Moodle
			if ($this->coinciden_alumnos_moodle($es_desvinculacion)) {

				return true;

			} else { // sino

				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {

						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							continue;
						}

						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado muestro mensaje y salgo
					if (!empty($personas_sin_email)) {
						toba::notificacion()->error(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['alumnos/s', implode(', ', $personas_sin_email)]));
						return false;
					}
					$lote_usuarios = array_chunk($usuarios,1000, true);
                                        
                                        foreach ($lote_usuarios as $key => $usuarios) { 
                                       
					     $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);
					
                                            // Si hay usuarios a guardar en la DB
                                            if (!empty($usuarios_moodle)) {

                                                    $usuarios_pv = [];
                                                    foreach ($usuarios_moodle as $key => $usuario_moodle) {

                                                            // Agrego los usuarios para matricular y luego los guardo en la DB
                                                            $usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
                                                            $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
                                                            $this->alumnos_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

                                                    }

                                                    guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);

                                            }
                                        }


				}

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->alumnos_para_matricular)) {

					$matriculaciones = [];
					$matriculaciones_grupo = [];
                                        
					foreach ($this->alumnos_para_matricular as $key => $alumno_para_matricular) {

						// Si se trata de una propuesta tipo curso
						if ($alumno_para_matricular['propuesta_tipo'] == propuesta::tipo_curso) {
							$role_moodle = $this->s__datos_plataforma['rol_alumno_curso'];
						} else { // sino
							$role_moodle = $this->s__datos_plataforma['rol_alumno'];
						}

						$matriculaciones[$key]['roleid'] = $role_moodle;
						$matriculaciones[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
						$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
						$matriculaciones_grupo[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
					}
					$lote_matriculaciones = array_chunk($matriculaciones,1000, true);
					foreach ($lote_matriculaciones as $key => $matriculaciones) {
						$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
					}
					$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo,1000, true);
					foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
						if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
							$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
						}
					}
                }
				
				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->alumnos_para_desmatricular)) {

					$desmatriculaciones = [];

					foreach ($this->alumnos_para_desmatricular as $key => $alumno_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $alumno_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					}
                                        $lote_desmatriculaciones = array_chunk($desmatriculaciones,1000, true);
                                        foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) {
							$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
                                        }
                                        
				}

			}

			//toba::db()->cerrar_transaccion();

			return true;

		} catch (Exception $e) {

			//toba::db()->abortar_transaccion();
			
			toba::logger()->error("Ocurrió un error al actualizar alumnos en Moodle: ".$e->getMessage());

			toba::notificacion()->error(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
			return false;

		}

	}

	function coinciden_docentes_moodle()
	{
		list(	$coinciden_docentes_moodle,
				$this->personas_para_crear,
				$this->docentes_para_matricular,
				$this->docentes_para_desmatricular) = moodle::coincidenDocentesMoodle(
							$this->s__docentes,
							$this->s__docentes_curso_virtual_guarani,
							$this->s__docentes_curso_virtual_moodle);

		return $coinciden_docentes_moodle;
	}

	function actualizar_docentes_moodle()
	{
		//toba::db()->abrir_transaccion();

		try {
			
			// Si no hay que actualizar en Moodle
			if ($this->coinciden_docentes_moodle()) {

				return true;

			} else { // sino

				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {

						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							continue;
						}

						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado muestro mensaje y salgo
					if (!empty($personas_sin_email)) {
						toba::notificacion()->error(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['docente/s', implode(', ', $personas_sin_email)]));
						return false;
					}
                                        $lote_usuarios = array_chunk($usuarios,1000, true);
                                        
                                        foreach ($lote_usuarios as $key => $usuarios) { 
                                       
                                            $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

                                            // Si hay usuarios a guardar en la DB
                                            if (!empty($usuarios_moodle)) {

                                                    $usuarios_pv = [];
                                                    foreach ($usuarios_moodle as $key => $usuario_moodle) {

                                                            // Agrego los usuarios para matricular y luego los guardo en la DB
                                                            $usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
                                                            $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
                                                            $this->docentes_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

                                                    }

                                                    guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);

                                            }

                                        }
                                
                                }

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->docentes_para_matricular)) {

					$matriculaciones = [];
					$matriculaciones_grupo = [];
                                        
					foreach ($this->docentes_para_matricular as $key => $docente_para_matricular) {
						$matriculaciones[$key]['roleid'] = $docente_para_matricular['rol_moodle'];
						$matriculaciones[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
						$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
						$matriculaciones_grupo[$key]['userid'] = $docente_para_matricular['usuario_moodle'];

					}
                    $lote_matriculaciones = array_chunk($matriculaciones, 1000, true);
                    foreach ($lote_matriculaciones as $key => $matriculaciones) { 
						$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
					}
					$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo,1000, true);
					foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
						toba::logger()->debug("TRAIGO DOCENTES GRUPO DEL CURSO");
						toba::logger()->var_dump($this->s__datos_curso_virtual['grupo']);
						if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
							$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
						}
					}
				}

				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->docentes_para_desmatricular)) {

					$desmatriculaciones = [];

					foreach ($this->docentes_para_desmatricular as $key => $docente_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $docente_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					}
					$lote_desmatriculaciones = array_chunk($desmatriculaciones, 1000, true);
					foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) { 
						$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
					}

				}

			}

			//toba::db()->cerrar_transaccion();

			return true;

		} catch (Exception $e) {

			//toba::db()->abortar_transaccion();
			
			toba::logger()->error("Ocurrió un error al actualizar docentes en Moodle: ".$e->getMessage());

			toba::notificacion()->error(guarani_mensajes::get("800_fallo_actualizacion_docentes_moodle", [$e->getMessage()]));
			return false;

		}

	}

	function get_funciones_faltantes_moodle()
	{
		return $this->s__funciones_faltantes_moodle;
	}

	function validar_fechas($fecha_inicio, $fecha_fin)
	{
		$formato = 'Y-m-d';
		$validacion_ok = true;

		// Si ingresó "Fecha Fin" sin ingresar "Fecha Inicio"
		if (is_null($fecha_inicio) && !is_null($fecha_fin)) {
			toba::notificacion()->error(guarani_mensajes::get("800_fecha_fin_sin_fecha_inicio"));
			$validacion_ok = false;
		}

		// Si ingresó "Fecha Inicio" me fijo que sea valida
		if (!is_null($fecha_inicio) && !guarani_fecha::validar_fecha($fecha_inicio, $formato)) {
			toba::notificacion()->error(guarani_mensajes::get("800_fecha_inicio_invalida"));
			$validacion_ok = false;
		}

		// Si ingresó "Fecha Fin" me fijo que sea valida
		if (!is_null($fecha_fin) && !guarani_fecha::validar_fecha($fecha_fin, $formato)) {
			toba::notificacion()->error(guarani_mensajes::get("800_fecha_fin_invalida"));
			$validacion_ok = false;
		}

		// Me fijo que la "Fecha Inicio" no sea mayor a la "Fecha Fin"
		if ((!is_null($fecha_inicio) && guarani_fecha::validar_fecha($fecha_inicio, $formato)) && (!is_null($fecha_fin) && guarani_fecha::validar_fecha($fecha_fin, $formato))) {

			if (!guarani_fecha::validar_fecha_inicio_fin($fecha_inicio, $fecha_fin, $formato)) {
				toba::notificacion()->error(guarani_mensajes::get("800_fecha_inicio_mayor_fecha_fin"));
				$validacion_ok = false;
			}
		}

		return $validacion_ok;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__crear()
	{
		$this->crear_curso = true;
	}

	function evt__vincular()
	{
		$this->vincular_curso = true;
	}

	function evt__actualizar_alumnos()
	{
		$actualizar_alumnos_ok = $this->actualizar_alumnos_moodle();

		// Si los alumnos se actualizaron bien
		if ($actualizar_alumnos_ok) {
			toba::notificacion()->info(guarani_mensajes::get("800_exito_actualizacion_alumnos_moodle"));
		}
	}

	function evt__actualizar_docentes()
	{
		$actualizar_docentes_ok = $this->actualizar_docentes_moodle();

		// Si los docentes se actualizaron bien
		if ($actualizar_docentes_ok) {
			toba::notificacion()->info(guarani_mensajes::get("800_exito_actualizacion_docentes_moodle"));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_vincular ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_vincular(guarani_pers_ei_formulario $form)
	{
		if (!empty($this->datos_form_vincular)) {
			$form->set_datos($this->datos_form_vincular);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_plataforma --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_plataforma__seleccionar($datos)
	{
		$this->s__plataforma_seleccionada = $datos['plataforma'];
	}

	function ajax__validar_curso_grupo($params, toba_ajax_respuesta $respuesta)
	{
		//base de datos con id de curso retornar si tiene grupos 
		
		$curso_id = $params;
		$curso_base = toba::consulta_php('co_comisiones')->get_cursos_grupos_curso_virtual($curso_id);
		
		$respuesta->set($curso_base);
	}
}