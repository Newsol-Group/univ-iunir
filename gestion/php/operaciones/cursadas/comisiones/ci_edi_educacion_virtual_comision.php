<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 *
 */
class ci_edi_educacion_virtual_comision extends ci_edi_educacion_virtual
{
	protected $s__comision;

	function crear_curso_moodle($fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null)
	{
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->crearAsociarCursoComision(
				$this->s__datos_plataforma['plataforma'],
				$this->s__comision,
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

	function crear_grupo_curso_moodle($idnumber, $description, $courseid, $name, $comision)
	{
		
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->CrearAsociarGrupoComision(
				
				$idnumber,
				$description, 
				$courseid, 
				$name, 
				$comision
		);
		if ($creacion_ok) {
			return true;
		} else {
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_creacion_grupo_curso_moodle', [$messageMoodle]));
			return false;
		}
	}

	function eliminar_grupo_curso_moodle($grupo)
	{
		$eliminacion_grupo_ok = true;
                
                try {
                    $this->get_moodle_obj()->borrarGrupos([$grupo], true);
                } catch (\Exception $e) {
                    $eliminacion_grupo_ok = false;
                }

                return $eliminacion_grupo_ok;
	}
	
	function get_mensaje_problemas_conexion()
	{
		return guarani_mensajes::get('800_problemas_conexion_pev');
	}

	function get_mensaje_nuevo_curso_virtual()
	{
		return guarani_mensajes::get('800_leyenda_nuevo_curso_virtual');
	}

	function get_mensaje_docentes_coinciden_moodle()
	{
		return guarani_mensajes::get('800_docentes_comision_coinciden_moodle');
	}

	function get_mensaje_docentes_no_coinciden_moodle()
	{
		return guarani_mensajes::get('800_docentes_comision_no_coinciden_moodle');
	}

	function get_mensaje_alumnos_coinciden_moodle()
	{
		return guarani_mensajes::get('800_alumnos_comision_coinciden_moodle');
	}

	function get_mensaje_alumnos_no_coinciden_moodle()
	{
		return guarani_mensajes::get('800_alumnos_comision_no_coinciden_moodle');
	}

	function get_mensaje_actas_cerrada_virtual()
	{
		return guarani_mensajes::get('800_actas_cerrada_virtual');
	}

	function get_mensaje_multiples_pev()
	{
		return guarani_mensajes::get('800_multiples_pev_comision');
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

		$datos_comision = $this->cn()->entidad()->get_datos_generales();

		// Si se trata de una comisión nueva, salgo.
		if (!isset($datos_comision['comision'])) {
			return false;
		}

		$this->s__comision = $datos_comision['comision'];
		$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($this->s__comision);
		if (empty($this->s__datos_curso_virtual)) {
			$plataformas = toba::consulta_php('co_comisiones')->get_plataformas_by_comision($this->s__comision);
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
		// Si no existe una plataforma asociada a la Actividad
		if (empty($this->s__datos_plataforma)) {
			$plataforma_ok = false;
		} else {
			// Si se logra establecer conexión
			if ($this->get_moodle_obj()->verificarConexion()) {
				$this->s__funciones_faltantes_moodle = $this->get_moodle_obj()->verificarFunciones();
				// Si todas las funciones requeridas fueron habilitadas
				if (empty($this->s__funciones_faltantes_moodle)) {
					$plataforma_ok = true;
					$faltan_funciones = false;
					// Si se trata de una comisión con actas cerradas salgo
					if (toba::consulta_php('co_comisiones')->tiene_acta_cursada_promocion_cerrada($datos_comision['comision'])) { 
						$plataforma_ok = false;
						$tiene_actas_cerradas = true;
					}
				} else { // si faltan habilitar funciones
					$plataforma_ok = false;
					$faltan_funciones = true;
				}
			} else {
				$plataforma_ok = false;
			}
		}

		// Si existe una plataforma asociada a la Actividad y se logra establecer conexión con ella
		if ($plataforma_ok) {
			$this->s__docentes = toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($this->s__comision, $this->s__datos_plataforma['plataforma']);
			$this->s__roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->s__datos_plataforma['plataforma'], 'int_pv_roles_docentes.responsabilidad IS NOT NULL');
			// Si la Comisión no posee un curso asociado
			if (empty($this->s__datos_curso_virtual)) {
				// Si se hizo click en el botón "Crear"
				if ($this->crear_curso) {
					$this->set_pantalla('pant_form_crear');
				} elseif ($this->vincular_curso) { // Si se hizo click en el botón "Vincular"
					$this->set_pantalla('pant_form_vincular');
				} else {
					$this->set_pantalla('pant_sin_curso_asociado');
				}           
			} else {// Si la Comisión posee un curso asociado
				$info_curso = $this->get_info_curso_virtual();
				// Verifico que el curso verdaderamente exista en Moodle
				if (is_null($info_curso)) {
					// Si el curso no existe en Moodle lo elimino en Guaraní
					guarani::act('act_plataformas_virtuales')->borrar_curso_comision_pv($this->s__datos_curso_virtual['curso']);
					$this->set_pantalla('pant_sin_curso_asociado');
				} else { // Si el curso existe en Moodle, muestro información del mismo
					$this->s__alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($this->s__comision);
					$this->s__alumnos_eliminados = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($this->s__comision, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
					$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
					$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
					$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
					$this->set_pantalla('pant_curso_asociado');
				}
			}
		} elseif ($faltan_funciones) { // Si faltan habilitar funciones en Moodle
			$this->set_pantalla('pant_faltan_funciones');
		} elseif ($tiene_actas_cerradas) {
			$this->set_pantalla('pant_actas_cerradas');
		} else { // Si no existe una plataforma asociada a la Actividad o no se logra establecer conexión
			$this->set_pantalla('pant_sin_plataforma');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_crear -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_crear(guarani_pers_ei_formulario $form)
	{
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		$comision_id =  $datos_comision['comision'];
		$datos_comision = current(toba::consulta_php('co_comisiones')->get_listado_comisiones_curso_virtual("vw_comisiones.comision = {$datos_comision['comision']}"));
		if (!empty($this->s__datos_plataforma['categoria_curso_default'])) {
			$datos['categoria'] = $this->s__datos_plataforma['categoria_curso_default'];
		}
		$datos['nombre'] = "{$datos_comision['periodo_nombre']} - ({$datos_comision['elemento_codigo']}) {$datos_comision['elemento_nombre']} - {$datos_comision['comision_nombre']}";
		$datos['nombre_abrev'] = "{$datos_comision['periodo_nombre']} - ({$datos_comision['elemento_codigo']}) {$datos_comision['elemento_nombre']} - {$datos_comision['comision_nombre']}";
		$datos['fecha_inicio'] = $datos_comision['fecha_inicio_dictado'];
		$datos['fecha_fin'] = $datos_comision['fecha_fin_dictado'];
		$datos['grupo_nombre'] = $datos_comision['comision_nombre'];
		$datos['grupo_id'] = $comision_id;

		$form->set_datos_defecto($datos);

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

		// Si la validación de fechas es exitosa
		if ($validacion_fechas_ok) {

			$startdate = null;
			$enddate = null;

			// Si ingresó "Fecha Inicio"
			if (!is_null($datos['fecha_inicio'])) {
				$fecha_inicio_obj = DateTime::createFromFormat('Y-m-d', $datos['fecha_inicio']);
				$startdate = $fecha_inicio_obj->getTimestamp();

				// Si ingresó "Fecha Inicio" y "Fecha Fin"
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
		
		} else { // sino
			$crear_curso_ok = false;
		}

		// Si el curso se creo bien
		if ($crear_curso_ok) {

			toba::notificacion()->info(guarani_mensajes::get("800_creacion_exitosa_curso_moodle"));

			$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($this->s__comision);
			$this->s__alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($this->s__comision);
			$this->s__alumnos_eliminados = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($this->s__comision, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
			$this->s__alumnos_curso_virtual_guarani = [];
			$this->s__alumnos_curso_virtual_moodle = [];
			$this->s__docentes_curso_virtual_guarani = [];
			$this->s__docentes_curso_virtual_moodle = [];

			// Si se debe importar contenido de otro curso
			if ($datos['importar']) {
				
				try {
					
					$this->get_moodle_obj()->importarContenidoCurso($datos['copiar_de'], $this->s__datos_curso_virtual['id_curso_externo'], true);
					
				} catch (Exception $e) {
					
					toba::notificacion()->warning(guarani_mensajes::get("800_fallo_importacion_curso_moodle", [$e->getMessage()]));
					
				}
				
			}

			// Si se deben crear grupo  $idnumber, $description, $courseid, $name, $comision
			if ($datos['crear_grupo']) {
				$this->crear_grupo_curso_moodle(
					$this->s__comision,
					nl2br($datos['descripcion']),
					$this->s__datos_curso_virtual['id_curso_externo'],
					$datos['nombre_abrev'],
					$this->s__comision					
				);
			}

			// Se matriculan los docentes
			$this->actualizar_docentes_moodle();

			// Si se deben matricular los alumnos
			if ($datos['matricular']) {
				// Se matriculan los alumnos
				$this->actualizar_alumnos_moodle();
			}

		} else { // sino
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
		
			// Si el curso externo no se vinculó con Guaraní
			if (empty($curso_externo)) {
				// Guardo el curso en la DB
				$curso_id = guarani::act('act_plataformas_virtuales')->insertar_curso_pv($this->s__datos_plataforma['plataforma'], $datos['curso']);
			} else { // sino
				$curso_id = $curso_externo['curso'];
			}


			// Vinculo el curso con la comisión
			guarani::act('act_plataformas_virtuales')->insertar_curso_comision_pv($curso_id, $this->s__comision);

			toba::db()->cerrar_transaccion();

			toba::notificacion()->info(guarani_mensajes::get("800_vinculacion_exitosa_curso_moodle"));

		} catch (Exception $e) {

			toba::db()->abortar_transaccion();

			$this->datos_form_vincular = $datos;
			$this->vincular_curso = true;

			toba::logger()->debug("Ocurrió un error al vincular curso en Moodle:");
			toba::logger()->error($e->getMessage());

			toba::notificacion()->error(guarani_mensajes::get("800_fallo_vinculacion_curso_moodle"));
			return false;

		}

		$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($this->s__comision);
		$this->s__alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($this->s__comision);
		$this->s__alumnos_eliminados = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($this->s__comision, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
		$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
		$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
		$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();

		$descripcion = 'SIN DESCRIPCION';
		
		$datos_curso = toba::consulta_php('co_comisiones')->get_nombre_comision($this->s__comision);
		toba::logger()->debug("DATOS CURSO DE VINCULAR CURSO ");
		toba::logger()->var_dump($datos_curso);

		if ($datos['crear_grupo']) {
			$this->crear_grupo_curso_moodle(
				$this->s__comision,
					nl2br($descripcion),
					$this->s__datos_curso_virtual['id_curso_externo'],
					$datos_curso,
					$this->s__comision		
			);
		}
		
		// Se matriculan los docentes
		$this->actualizar_docentes_moodle();

		// Si se deben matricular los alumnos
		if ($datos['matricular']) {
			// Se matriculan los alumnos
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
			
			// Debo pasar la plataforma porque al desvincular el curso de la comisión hace que el JOIN me traiga null la columna "usuario_moodle"
			$plataforma = $this->s__datos_plataforma['plataforma'];

			$datos_curso = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($this->s__comision);

                        $grupo = $datos_curso['grupo'] ?? comunes::no;
			toba::logger()->debug("DATOS CURSO VIRTUAL PARA ELIMINAR GRUPO");
			toba::logger()->var_dump($grupo);

                        // Si tiene un grupo asociado lo elimino
                        if ($grupo != comunes::no) {
                            
                            $eliminacion_grupo_ok = $this->eliminar_grupo_curso_moodle($grupo);

                            // Si falla la eliminación del grupo salgo
                            if (!$eliminacion_grupo_ok) {
                                    toba::notificacion()->error(guarani_mensajes::get('800_fallo_eliminacion_grupo_curso_moodle'));
                                    toba::db()->abortar_transaccion();
                                    return false;
                            }
                         
                        }
                        
			// Desvinculo comisiï¿½n del curso virtual
			$this->cn()->entidad()->desvincular_curso_virtual();

			// Actualizo alumnos en Moodle
			$this->s__alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($this->s__comision, '', $plataforma);
			$this->s__alumnos_eliminados = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($this->s__comision, 'alumnos_eliminados.usuario_moodle IS NOT NULL', $plataforma);
			$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
			$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
			$actualizar_alumnos_ok = $this->actualizar_alumnos_moodle(true);

			// Si los alumnos NO se actualizaron bien
			if (!$actualizar_alumnos_ok) {
				toba::db()->abortar_transaccion();
				return false;
			}

			// Actualizo docentes en Moodle
			$this->s__docentes = [];
			$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
			$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
			$actualizar_docentes_ok = $this->actualizar_docentes_moodle();

			// Si los docentes NO se actualizaron bien
			if (!$actualizar_docentes_ok) {
				toba::db()->abortar_transaccion();
				return false;
			}

			toba::db()->cerrar_transaccion();
			toba::notificacion()->info(guarani_mensajes::get('800_curso_virtual_comision_desvinculacion_ok'));

		} catch (Exception $e) {

			toba::db()->abortar_transaccion();
			toba::notificacion()->error(guarani_mensajes::get("guardar_error"));

		}

	}

	function get_plataformas_comision()
	{
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		return toba::consulta_php('co_comisiones')->get_plataformas_by_comision($datos_comision['comision']);
	}
}