<?php
class ci_edi_educacion_virtual_llamado_mesa extends ci_edi_educacion_virtual
{
	protected $s__llamado_mesa;

	function crear_curso_moodle($fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null)
	{
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->crearAsociarCursoLlamadoMesa(
				$this->s__datos_plataforma['plataforma'],
				$this->s__llamado_mesa,
				$fullname,
				$shortname,
				$categoryid,
				$summary,
				$visible,
				$startdate,
				$enddate,
				true
		);
		if ($creacion_ok) {
			return true;
		} else {
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_creacion_curso_moodle', [$messageMoodle]));
			return false;
		}
	}

	function get_mensaje_problemas_conexion()
	{
		return guarani_mensajes::get('800_problemas_conexion_pev_mesa');
	}

	function get_mensaje_nuevo_curso_virtual()
	{
		return guarani_mensajes::get('800_leyenda_nuevo_curso_virtual_mesa');
	}

	function get_mensaje_docentes_coinciden_moodle()
	{
		return guarani_mensajes::get('800_docentes_mesa_coinciden_moodle');
	}

	function get_mensaje_docentes_no_coinciden_moodle()
	{
		return guarani_mensajes::get('800_docentes_mesa_no_coinciden_moodle');
	}

	function get_mensaje_alumnos_coinciden_moodle()
	{
		return guarani_mensajes::get('800_alumnos_mesa_coinciden_moodle');
	}

	function get_mensaje_alumnos_no_coinciden_moodle()
	{
		return guarani_mensajes::get('800_alumnos_mesa_no_coinciden_moodle');
	}

	function get_mensaje_actas_cerrada_virtual()
	{
		return guarani_mensajes::get('800_actas_examen_cerrada_virtual');
	}

	function get_mensaje_multiples_pev()
	{
		return guarani_mensajes::get('800_multiples_pev_mesa');
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{

                /* Combo de cursos al vincular e importar contenido de otro curso al crear.
                 * El largo máximo es de 254 caracteres.
                 */
                toba_ef_combo::set_maximo_descripcion(moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH);

		$datos_llamado_mesa = $this->cn()->entidad()->get_datos_llamado();

		$this->s__llamado_mesa = $datos_llamado_mesa['llamado_mesa'];
		$this->s__datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($this->s__llamado_mesa);
		if (empty($this->s__datos_curso_virtual)) {
			$plataformas = toba::consulta_php('co_mesas_de_examen')->get_plataformas_virtuales_llamado_mesa($this->s__llamado_mesa);
			if (guarani_arreglos::count($plataformas) > 1) {
				if (empty($this->s__plataforma_seleccionada)) {
					$this->set_pantalla('pant_seleccion_plataforma');
					return;
				} else {
					$this->s__datos_plataforma = toba::consulta_php('co_configuracion')->get_plataforma_educacion_virtual($this->s__plataforma_seleccionada);
				}
			} else {
				$this->s__datos_plataforma = current($plataformas);
			}
		} else {
			$this->s__datos_plataforma['plataforma'] = $this->s__datos_curso_virtual['plataforma'];
			$this->s__datos_plataforma['nombre'] = $this->s__datos_curso_virtual['plataforma_nombre'];
			$this->s__datos_plataforma['url'] = $this->s__datos_curso_virtual['plataforma_url'];
			$this->s__datos_plataforma['host'] = $this->s__datos_curso_virtual['plataforma_host'];
			$this->s__datos_plataforma['token'] = $this->s__datos_curso_virtual['plataforma_token'];
			$this->s__datos_plataforma['rol_alumno'] = $this->s__datos_curso_virtual['plataforma_rol_alumno'];
			$this->s__datos_plataforma['rol_alumno_curso'] = $this->s__datos_curso_virtual['plataforma_rol_alumno_curso'];
			$this->s__datos_plataforma['categoria_curso_default'] = $this->s__datos_curso_virtual['plataforma_categoria_curso_default'];
		}

		$plataforma_ok = false;
		$faltan_funciones = false;
		$tiene_actas_cerradas = false;

		if (! empty($this->s__datos_plataforma)) {
			if ($this->get_moodle_obj()->verificarConexion()) {
				$this->s__funciones_faltantes_moodle = $this->get_moodle_obj()->verificarFunciones();
				if (empty($this->s__funciones_faltantes_moodle)) {
					$plataforma_ok = true;
					if (toba::consulta_php('co_mesas_de_examen')->llamado_mesa_tiene_acta_cerrada($this->s__llamado_mesa)) { 
						$plataforma_ok = false;
						$tiene_actas_cerradas = true;
					}
				} else {
					$faltan_funciones = true;
				}
			}
		}

		if ($plataforma_ok) {
			$this->s__docentes = toba::consulta_php('co_mesas_de_examen')->get_docentes_llamado_mesa_moodle($this->s__llamado_mesa, $this->s__datos_plataforma['plataforma']);
			$this->s__roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->s__datos_plataforma['plataforma'], 'int_pv_roles_docentes.rol IS NOT NULL');

			if (empty($this->s__datos_curso_virtual)) {
				if ($this->crear_curso) {
					$this->set_pantalla('pant_form_crear');
				} elseif ($this->vincular_curso) {
					$this->set_pantalla('pant_form_vincular');
				} else {
					$this->set_pantalla('pant_sin_curso_asociado');
				}
			} else {
				$info_curso = $this->get_info_curso_virtual();

				if (is_null($info_curso)) {
					guarani::act('act_plataformas_virtuales')->borrar_curso_llamado_mesa_pv($this->s__datos_curso_virtual['curso']);
					$this->set_pantalla('pant_sin_curso_asociado');
				} else {
					$this->s__alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($this->s__llamado_mesa);
					$this->s__alumnos_eliminados = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($this->s__llamado_mesa, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
					$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
					$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
					$this->set_pantalla('pant_curso_asociado');
				}
			}
		} elseif ($faltan_funciones) {
			$this->set_pantalla('pant_faltan_funciones');
		} elseif ($tiene_actas_cerradas) {
			$this->set_pantalla('pant_actas_cerradas');
		} else {
			$this->set_pantalla('pant_sin_plataforma');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_crear -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_crear(guarani_pers_ei_formulario $form)
	{
		$datos_llamado_mesa = $this->cn()->entidad()->get_datos_llamado();
		$datos_llamado_mesa = current(toba::consulta_php('co_mesas_de_examen')->get_llamados_mesa_curso_virtual("sga_llamados_mesa.llamado_mesa = {$datos_llamado_mesa['llamado_mesa']}"));
                if (!empty($this->s__datos_plataforma['categoria_curso_default'])) {
			$datos['categoria'] = $this->s__datos_plataforma['categoria_curso_default'];
		}

                // Si la fecha de la mesa es anterior a la fecha actual entonces $datos_llamado_mesa viene vacío
                if (!empty($datos_llamado_mesa)) {
                    $nombre_curso = "{$datos_llamado_mesa['turno_examen_nombre']} - {$datos_llamado_mesa['llamado_turno_nombre']} - ({$datos_llamado_mesa['elemento_codigo']}) {$datos_llamado_mesa['elemento_nombre']} - {$datos_llamado_mesa['mesa_examen_nombre']}";
                    $datos['nombre'] = $nombre_curso;
                    $datos['nombre_abrev'] = $nombre_curso;
                    $datos['fecha_inicio'] = $datos_llamado_mesa['turno_examen_fecha_inicio'];
                    $datos['fecha_fin'] = $datos_llamado_mesa['turno_examen_fecha_fin'];
                    $form->set_datos_defecto($datos);
                }

		if (!empty($this->datos_form_crear)) {
			$form->set_datos($this->datos_form_crear);
		}

                $nombre_maxleght = moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH;
                $nombre_abrev_maxleght = moodle::NOMBRE_CORTO_CURSO_MAXLENGTH;
                $msj_nombre_maxleght = guarani_mensajes::get('800_no_debe_exceder_cant_caracteres', [$nombre_maxleght]);
                $msj_nombre_abrev_maxleght = guarani_mensajes::get('800_no_debe_exceder_cant_caracteres', [$nombre_abrev_maxleght]);
                $form->ef('nombre')->set_descripcion($msj_nombre_maxleght);
                $form->ef('nombre_abrev')->set_descripcion($msj_nombre_abrev_maxleght);
	}

	function evt__form_crear__modificacion($datos)
	{
		$visible = ($datos['visible'] == comunes::si)? 1 : 0;
		$validacion_fechas_ok = $this->validar_fechas($datos['fecha_inicio'], $datos['fecha_fin']);
		if ($validacion_fechas_ok) {
			$startdate = null;
			$enddate = null;
			if (!is_null($datos['fecha_inicio'])) {
				$fecha_inicio_obj = DateTime::createFromFormat('Y-m-d', $datos['fecha_inicio']);
				$startdate = $fecha_inicio_obj->getTimestamp();
				if (!is_null($datos['fecha_fin'])) {
					$fecha_fin_obj = DateTime::createFromFormat('Y-m-d', $datos['fecha_fin']);
					$enddate = $fecha_fin_obj->getTimestamp();
				}
			}
			$crear_curso_ok = $this->crear_curso_moodle(
					$datos['nombre'],
					$datos['nombre_abrev'],
					$datos['categoria'],
					nl2br($datos['descripcion']),
					$visible,
					$startdate,
					$enddate
				);
		} else {
			$crear_curso_ok = false;
		}
		if ($crear_curso_ok) {
			toba::notificacion()->info(guarani_mensajes::get('800_creacion_exitosa_curso_moodle'));
			$this->s__datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($this->s__llamado_mesa);
			$this->s__alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($this->s__llamado_mesa);
			$this->s__alumnos_eliminados = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($this->s__llamado_mesa, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
			$this->s__alumnos_curso_virtual_guarani = array();
			$this->s__alumnos_curso_virtual_moodle = array();
			$this->s__docentes_curso_virtual_guarani = array();
			$this->s__docentes_curso_virtual_moodle = array();
			if ($datos['importar']) {
				try {
					$this->get_moodle_obj()->importarContenidoCurso($datos['copiar_de'], $this->s__datos_curso_virtual['id_curso_externo'], true);
				} catch (Exception $e) {
					toba::notificacion()->warning(guarani_mensajes::get('800_fallo_importacion_curso_moodle', [$e->getMessage()]));
				}
			}
			$this->actualizar_docentes_moodle();
			if ($datos['matricular']) {
				$this->actualizar_alumnos_moodle();
			}
		} else {
			$this->datos_form_crear = $datos;
			$this->crear_curso = true;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_vincular ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_vincular__modificacion($datos)
	{
		try {
			toba::db()->abrir_transaccion();
			$curso_externo = toba::consulta_php('co_configuracion')->get_datos_curso_externo($datos['curso'], $this->s__datos_plataforma['plataforma']);
			if (empty($curso_externo)) {
				$curso_id = guarani::act('act_plataformas_virtuales')->insertar_curso_pv($this->s__datos_plataforma['plataforma'], $datos['curso']);
			} else {
				$curso_id = $curso_externo['curso'];
			}
			guarani::act('act_plataformas_virtuales')->insertar_curso_llamado_mesa_pv($curso_id, $this->s__llamado_mesa);
			toba::db()->cerrar_transaccion();
			toba::notificacion()->info(guarani_mensajes::get('800_vinculacion_exitosa_curso_moodle'));
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			$this->datos_form_vincular = $datos;
			$this->vincular_curso = true;
			toba::logger()->debug('Ocurrió un error al vincular curso en Moodle:');
			toba::logger()->error($e->getMessage());
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_vinculacion_curso_moodle'));
			return false;
		}
		$this->s__datos_curso_virtual = toba::consulta_php('co_mesas_de_examen')->get_curso_virtual_llamado_mesa($this->s__llamado_mesa);
		$this->s__alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($this->s__llamado_mesa);
		$this->s__alumnos_eliminados = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($this->s__llamado_mesa, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
		$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
		$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
		$this->actualizar_docentes_moodle();
		if ($datos['matricular']) {
			$this->actualizar_alumnos_moodle();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__desvincular()
	{
		toba::db()->abrir_transaccion();
		try {
			guarani::act('act_mesas_de_examen')->desvincular_curso_virtual_llamado_mesa($this->s__llamado_mesa);

			$this->s__alumnos = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_llamado_mesa_moodle($this->s__llamado_mesa);
			$this->s__alumnos_eliminados = toba::consulta_php('co_mesas_de_examen')->get_alumnos_eliminados_a_llamado_mesa_moodle($this->s__llamado_mesa, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
			$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
			$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
			$actualizar_alumnos_ok = $this->actualizar_alumnos_moodle(true);
			if (!$actualizar_alumnos_ok) {
				toba::db()->abortar_transaccion();
				return false;
			}

			$this->s__docentes = array();
			$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_mesas_de_examen')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
			$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
			$actualizar_docentes_ok = $this->actualizar_docentes_moodle();
			if (!$actualizar_docentes_ok) {
				toba::db()->abortar_transaccion();
				return false;
			}

			toba::db()->cerrar_transaccion();
			toba::notificacion()->info(guarani_mensajes::get('800_curso_virtual_llamado_mesa_desvinculacion_ok'));
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->error(guarani_mensajes::get('guardar_error'));
		}
	}

	function get_plataformas_llamado_mesa()
	{
		$datos_llamado_mesa = $this->cn()->entidad()->get_datos_llamado();
		return toba::consulta_php('co_mesas_de_examen')->get_plataformas_virtuales_llamado_mesa($datos_llamado_mesa['llamado_mesa']);
	}
}