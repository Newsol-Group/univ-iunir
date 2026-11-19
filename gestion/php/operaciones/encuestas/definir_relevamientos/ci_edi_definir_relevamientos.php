<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_definir_relevamientos extends ci_edicion_entidad
{
	protected $datos_temp_habilitacion;
	protected $datos_temp_ras;
	protected $datos_temp_tipos_propuesta;
	protected $datos_temp_alumnos;
	protected $datos_temp_modalidades;
	protected $datos_temp_ubicaciones;
	protected $datos_temp_docentes;
	protected $encuestas_seleccionadas;
	protected $s__convocatorias;

	const chars_maximo_combo = 75;
	
	function inicializar($parametro = array())
	{
		parent::inicializar();
		$this->componentes_eventos_a_inhabilitar['cuadro'] = array('seleccion', 'agregar', 'eliminar');
		$this->eventos_controlador_a_inhabilitar = array('eliminar');
	}

	function post_configurar()
	{
		if (!$this->cn()->entidad()->es_editable()) {
			$this->inhabilitar_componentes_eventos();
			$this->dep('form_habilitacion')->set_solo_lectura(array('titulo', 'descripcion', 'activo'), false);
			$this->dep('form_responsables_academicas')->set_solo_lectura(null, false);
			$this->dep('form_tipos_propuesta')->set_solo_lectura(null, false);
			$this->inhabilitar_eventos_controlador();
		} elseif (!$this->cn()->entidad()->es_nueva() && $this->cn()->entidad()->get_columna_habilitacion('tipo_relevamiento') == encuesta::tipo_relevamiento_becas) {
			// Aún cuando el relevamiento es editable no se debería poder cambiar el tipo o la convocatoria.
			$this->dep('form_habilitacion')->set_solo_lectura(array('tipo_relevamiento', 'tehuelche_unidad_gestion', 'becas_convocatoria'));
		}
	}

	function ini()
	{
		$this->dep('form_ml_modalidades')->set_columna_ordenamiento('modalidad_nombre');
		$this->dep('form_ml_ubicaciones')->set_columna_ordenamiento('ubicacion_nombre');
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		if (isset($datos_habilitacion['tipo']) && ($datos_habilitacion['tipo'] != encuesta::tipo_docente && $datos_habilitacion['tipo'] != encuesta::tipo_docente_academica)) {
			$this->pantalla()->tab('pant_docentes')->desactivar();
		}
		if (isset($datos_habilitacion['tipo_relevamiento']) && ($datos_habilitacion['tipo_relevamiento'] == encuesta::tipo_relevamiento_encuestas)) {
			$this->pantalla()->tab('pant_datos_censales')->desactivar();
		}
		if (!$this->usa_repositorio_digital() ||
			(isset($datos_habilitacion['tipo_relevamiento']) && $datos_habilitacion['tipo_relevamiento'] == encuesta::tipo_relevamiento_encuestas)) {
			$this->pantalla()->tab('pant_requisitos')->desactivar();
		}
	}

	function conf__pant_formularios(toba_ei_pantalla $pantalla)
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		if ($datos_habilitacion['tipo'] != encuesta::tipo_docente_academica) {
			$this->dep('formularios_asociados')->eliminar_columnas(array('subtipo_nombre'));
			$this->dep('filtro_formularios')->eliminar_columnas(array('subtipo'));
			$this->dep('formularios_disponibles')->eliminar_columnas(array('subtipo_nombre'));
		}
	}

	function conf__pant_alcance(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('editor_actividades');
		$pantalla->eliminar_dep('editor_propuestas');
		$pantalla->eliminar_dep('form_responsables_academicas');
		$pantalla->eliminar_dep('form_tipos_propuesta');

		$alcance = $this->cn()->entidad()->get_columna_habilitacion('alcance');

		if (!empty($alcance)) {
			switch ($alcance) {
				case encuesta::alcance_actividades:
					$pantalla->agregar_dep('editor_actividades');
					break;
				case encuesta::alcance_propuestas:
					$pantalla->agregar_dep('editor_propuestas');
					break;
				case encuesta::alcance_responsables_academicas:
					$pantalla->agregar_dep('form_responsables_academicas');
					break;
				case encuesta::alcance_tipos_propuesta:
					$pantalla->agregar_dep('form_tipos_propuesta');
			}
		}
	}

	function conf__pant_destinatarios(toba_ei_pantalla $pantalla)
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		if ($datos_habilitacion['tipo'] == encuesta::tipo_egresados) {
			$pantalla->eliminar_dep('form_alumnos');
			$pantalla->eliminar_dep('form_alumnos_generica');
			$pantalla->eliminar_dep('form_alumnos_beca');
			$pantalla->eliminar_dep('form_pares');
			$pantalla->eliminar_dep('form_destinatarios_docentes');
			$pantalla->eliminar_dep('form_ml_modalidades');
			$pantalla->eliminar_dep('form_ml_ubicaciones');
		} elseif ($datos_habilitacion['tipo'] == encuesta::tipo_pares) {
			$pantalla->eliminar_dep('form_alumnos');
			$pantalla->eliminar_dep('form_alumnos_generica');
			$pantalla->eliminar_dep('form_alumnos_beca');
			$pantalla->eliminar_dep('form_egresados');
			$pantalla->eliminar_dep('form_destinatarios_docentes');
		} else {
			$pantalla->eliminar_dep('form_egresados');
			$pantalla->eliminar_dep('form_pares');
			if ($datos_habilitacion['tipo'] == encuesta::tipo_generica) {
				$pantalla->eliminar_dep('form_alumnos');
				if ($datos_habilitacion['tipo_relevamiento'] == encuesta::tipo_relevamiento_becas) {
					$pantalla->eliminar_dep('form_alumnos_generica');
					$pantalla->eliminar_dep('form_destinatarios_docentes');
					$pantalla->set_descripcion(toba::mensajes()->get('800ENC_destinatarios_beca'));
				} else {
					$pantalla->eliminar_dep('form_alumnos_beca');
					$dependencia_a_eliminar = ($datos_habilitacion['encuestado'] == encuesta::encuestado_alumno) ? 'form_destinatarios_docentes' : 'form_alumnos_generica';
					$pantalla->eliminar_dep($dependencia_a_eliminar);
				}
			} else {
				$pantalla->eliminar_dep('form_alumnos_generica');
				$pantalla->eliminar_dep('form_alumnos_beca');
				$dependencia_a_eliminar = ($datos_habilitacion['encuestado'] == encuesta::encuestado_alumno) ? 'form_destinatarios_docentes' : 'form_alumnos';
				$pantalla->eliminar_dep($dependencia_a_eliminar);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_habilitacion ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_habilitacion(form_habilitacion $form)
	{
		$datos_defecto = array('obligatoria' => comunes::si,
			'anonima' => comunes::si,
			'activo' => comunes::si);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_habilitacion)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_habilitacion);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			if (isset($datos['becas_convocatoria']) && !is_null($datos['becas_convocatoria'])) {
				$datos['becas'] = $this->get_becas($datos['becas_convocatoria'], $datos['tehuelche_unidad_gestion']);
			}
			$form->set_datos($datos);
		}

		if (!encuesta::kolla_utiliza_ug()) {
			$form->desactivar_efs(array('kolla_unidad_gestion'));
		}

		if (!beca::tehuelche_utiliza_ug()) {
			$form->desactivar_efs(array('tehuelche_unidad_gestion'));
		}

		if (!beca::conexion_tehuelche_ok()) {
			$desc = $form->ef('becas_convocatoria')->get_descripcion();
			$desc .= ' Debe configurar el acceso al servicio web de SIU-Tehuelche para obtener las Convocatorias disponibles.';
			$form->ef('becas_convocatoria')->set_descripcion($desc);
		}
	}

	function evt__form_habilitacion__modif($datos)
	{
		if (!encuesta::kolla_utiliza_ug()) {
			$datos['kolla_unidad_gestion'] = encuesta::get_ug_default_kolla();
		}
		if (!beca::tehuelche_utiliza_ug()) {
			$datos['tehuelche_unidad_gestion'] = beca::get_ug_default_tehuelche();
		}
		try {
			$this->cn()->entidad()->set_datos_habilitacion($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_habilitacion = $datos;
			throw $e;
		}
	}

	function evt__form_habilitacion__cambio_tipo_relevamiento($datos)
	{
		// Dependiendo del nuevo tipo de relevamiento se resetean los datos de destinatarios.
		if ($datos['tipo_relevamiento'] == encuesta::tipo_relevamiento_encuestas) {
			$datos_habilitacion = array('alumnos' => encuesta::alumnos_todos,
										'edad_desde' => null,
										'edad_hasta' => null,
										'trabaja' => null,
										'tiene_hijos' => null,
										'tiene_discapacidad' => null,
										'pertenece_pueblo_originario' => null,
										'alquila' => null,
										'tiene_cobertura_de_salud' => null,
										'vive_cursa_misma_localidad' => null);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_requisitos();
		} else {
			$datos_habilitacion = array('alumnos' => encuesta::alumnos_todos,
										'alumnos_inscriptos' => comunes::no,
										'alumnos_reinscriptos' => comunes::no);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_tesis_estados();
		}

		$this->evt__form_habilitacion__modif($datos);
	}

	function evt__form_habilitacion__cambio_tipo($datos)
	{
		$tipo_original = $this->cn()->entidad()->get_columna_habilitacion('tipo');

		// Eliminar las encuestas asociadas.
		$this->cn()->entidad()->eliminar_encuestas_grupo();

		// Dependiendo del nuevo tipo se resetean los datos de destinatarios.
		if ($datos['tipo'] == encuesta::tipo_generica) {
			$datos_habilitacion = array('inscriptos_comision' => encuesta::inscriptos_comision_todos,
										'cursada_aprobada' => comunes::si,
										'cursada_desaprobada' => comunes::no,
										'cursada_promocionada' => comunes::no,
										'anio_academico' => null,
										'periodo_lectivo' => null,
										'egresados' => encuesta::egresados_todos,
										'fecha_egreso_desde' => null,
										'fecha_egreso_hasta' => null,
										'pares_destinatario' => null,
										'pares_responsable' => null);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_certificados();
		} elseif ($datos['tipo'] == encuesta::tipo_egresados) {
			$datos_habilitacion = array('inscriptos_comision' => encuesta::inscriptos_comision_todos,
										'cursada_aprobada' => comunes::si,
										'cursada_desaprobada' => comunes::no,
										'cursada_promocionada' => comunes::no,
										'anio_academico' => null,
										'periodo_lectivo' => null,
										'alumnos' => encuesta::alumnos_todos,
										'alumnos_inscriptos' => comunes::no,
										'alumnos_reinscriptos' => comunes::no,
										'edad_desde' => null,
										'edad_hasta' => null,
										'trabaja' => null,
										'tiene_hijos' => null,
										'tiene_discapacidad' => null,
										'pertenece_pueblo_originario' => null,
										'alquila' => null,
										'tiene_cobertura_de_salud' => null,
										'vive_cursa_misma_localidad' => null,
										'pares_destinatario' => null,
										'pares_responsable' => null);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_anios_academicos();
			$this->cn()->entidad()->eliminar_tesis_estados();
			$this->cn()->entidad()->eliminar_modalidades();
			$this->cn()->entidad()->eliminar_ubicaciones();
		} elseif ($datos['tipo'] == encuesta::tipo_pares) {
			$datos_habilitacion = array('inscriptos_comision' => encuesta::inscriptos_comision_todos,
										'cursada_aprobada' => comunes::si,
										'cursada_desaprobada' => comunes::no,
										'cursada_promocionada' => comunes::no,
										'anio_academico' => null,
										'periodo_lectivo' => null,
										'alumnos' => encuesta::alumnos_todos,
										'alumnos_inscriptos' => comunes::no,
										'alumnos_reinscriptos' => comunes::no,
										'edad_desde' => null,
										'edad_hasta' => null,
										'trabaja' => null,
										'tiene_hijos' => null,
										'tiene_discapacidad' => null,
										'pertenece_pueblo_originario' => null,
										'alquila' => null,
										'tiene_cobertura_de_salud' => null,
										'vive_cursa_misma_localidad' => null,
										'egresados' => encuesta::egresados_todos,
										'fecha_egreso_desde' => null,
										'fecha_egreso_hasta' => null,
										'pares_destinatario' => null,
										'pares_responsable' => null);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_anios_academicos();
			$this->cn()->entidad()->eliminar_tesis_estados();
			$this->cn()->entidad()->eliminar_certificados();
		} elseif ($tipo_original == encuesta::tipo_generica || $tipo_original == encuesta::tipo_egresados || $tipo_original == encuesta::tipo_pares) {
			$datos_habilitacion = array('alumnos' => encuesta::alumnos_todos,
										'alumnos_inscriptos' => comunes::no,
										'alumnos_reinscriptos' => comunes::no,
										'edad_desde' => null,
										'edad_hasta' => null,
										'trabaja' => null,
										'tiene_hijos' => null,
										'tiene_discapacidad' => null,
										'pertenece_pueblo_originario' => null,
										'alquila' => null,
										'tiene_cobertura_de_salud' => null,
										'vive_cursa_misma_localidad' => null,
										'egresados' => encuesta::egresados_todos,
										'fecha_egreso_desde' => null,
										'fecha_egreso_hasta' => null,
										'pares_destinatario' => null,
										'pares_responsable' => null);
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
			$this->cn()->entidad()->eliminar_anios_academicos();
			$this->cn()->entidad()->eliminar_tesis_estados();
			$this->cn()->entidad()->eliminar_certificados();
		}

		// Si el nuevo tipo es diferente a "Evaluación Docente" y "Evaluación Docente y Académica" se resetean los datos de docentes.
		if ($datos['tipo'] != encuesta::tipo_docente && $datos['tipo'] != encuesta::tipo_docente_academica) {
			$this->cn()->entidad()->modificar_columna_habilitacion('docentes', encuesta::docentes_todos);
			$this->cn()->entidad()->eliminar_docentes_resp();
		}

		$this->evt__form_habilitacion__modif($datos);
	}

	function evt__form_habilitacion__cambio_encuestado($datos)
	{
		// Dependiendo del nuevo conjunto de encuestados se resetean los datos de destinatarios.
		if ($datos['encuestado'] == encuesta::encuestado_alumno) {
			$this->cn()->entidad()->modificar_columna_habilitacion('docentes', encuesta::docentes_todos);
			$this->cn()->entidad()->eliminar_docentes_resp();
		} else {
			if ($datos['tipo'] == encuesta::tipo_academica) {
				$datos_habilitacion = array('inscriptos_comision' => encuesta::inscriptos_comision_todos,
											'cursada_aprobada' => comunes::si,
											'cursada_desaprobada' => comunes::no,
											'cursada_promocionada' => comunes::no);
			} else { // Tipo genérica.
				$datos_habilitacion = array('alumnos' => encuesta::alumnos_todos,
											'alumnos_inscriptos' => comunes::no,
											'alumnos_reinscriptos' => comunes::no);
				$this->cn()->entidad()->eliminar_anios_academicos();
				$this->cn()->entidad()->eliminar_tesis_estados();
			}
			$this->cn()->entidad()->set_datos_habilitacion($datos_habilitacion);
		}
		$this->evt__form_habilitacion__modif($datos);
	}

	function get_estilos_habilitaciones()
	{
		return encuesta::get_estilos_habilitaciones();
	}
	
	//-----------------------------------------------------------------------------------
	//---- formularios_asociados --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formularios_asociados(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('gde_encuestas');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_grupo();
		$cuadro->set_datos($datos);
	}

	function evt__formularios_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_encuesta_grupo($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_formularios -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_formularios(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_formularios', false);
	}

	function evt__filtro_formularios__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_formularios', $datos);
	}

	function evt__filtro_formularios__cancelar()
	{
		$this->filtro_limpiar('filtro_formularios');
	}

	//-----------------------------------------------------------------------------------
	//---- formularios_disponibles ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formularios_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_formularios');
		$etiqueta = guarani_etiquetas::get_tabla('gde_encuestas');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;

		// Filtro por unidad de gestion.
		$ug = $this->cn()->entidad()->get_columna_habilitacion('kolla_unidad_gestion');
		if (empty($ug)) {
			$ug = encuesta::get_ug_default_kolla();
		}
		$filtro .= ' AND gde_encuestas.kolla_unidad_gestion = ' . toba::db()->quote($ug);

		// Filtro por subtipo de encuestas.
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		$filtro .= " AND gde_encuestas.subtipo IN (SELECT gde_subtipos_x_tipo.subtipo FROM gde_subtipos_x_tipo WHERE gde_subtipos_x_tipo.tipo = {$datos_habilitacion['tipo']})";

		$encuestas_asociadas = $this->cn()->entidad()->get_datos_grupo();
		if (!empty($encuestas_asociadas)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($encuestas_asociadas, 'gde_encuestas', 'encuesta', 'encuesta', true);
		}

		$datos = toba::consulta_php('co_encuestas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__formularios_disponibles__seleccion($seleccion)
	{
		$this->encuestas_seleccionadas = $seleccion;
	}

	function evt__formularios_disponibles__agregar()
	{
		if (!empty($this->encuestas_seleccionadas)) {
			foreach ($this->encuestas_seleccionadas as $encuesta) {
				$this->cn()->entidad()->agregar_encuesta_grupo($encuesta);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_alcance -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_alcance(form_alcance $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos());
	}

	function evt__form_alcance__modificacion($datos)
	{
		$alcance = $this->cn()->entidad()->get_columna_habilitacion('alcance');
		if ($datos['alcance'] != $alcance) {
			$this->cn()->entidad()->resetear_relacion();
			if ($alcance == encuesta::alcance_actividades) {
				$this->dep('editor_actividades')->resetear();
			} elseif ($alcance == encuesta::alcance_propuestas) {
				$this->dep('editor_propuestas')->resetear();
			}
		}
		$this->cn()->entidad()->set_datos_habilitacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_responsables_academicas -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_responsables_academicas(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_ras)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_ras);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_responsables_academicas(), 'responsable_academica');
			$form->set_datos(array('responsable_academica' => $datos));
		}
	}

	function evt__form_responsables_academicas__modif($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_responsables_academicas($datos['responsable_academica']);
		} catch (guarani_error $e) {
			$this->datos_temp_ras = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_tipos_propuesta ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tipos_propuesta(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_tipos_propuesta)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_tipos_propuesta);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_tipos_propuesta(), 'propuesta_tipo');
			$form->set_datos(array('propuesta_tipo' => $datos));
		}
	}

	function evt__form_tipos_propuesta__modif($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_tipos_propuesta($datos['propuesta_tipo']);
		} catch (guarani_error $e) {
			$this->datos_temp_tipos_propuesta = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_alumnos_generica --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_alumnos_generica(form_alumnos_generica $form)
	{
		$datos_defecto = array('alumnos' => encuesta::alumnos_todos);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_alumnos)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_alumnos);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$datos['anios_academicos'] = aplanar_matriz($this->cn()->entidad()->get_datos_anios_academicos(), 'anio_academico');
			$datos['tesis_estados'] = aplanar_matriz($this->cn()->entidad()->get_datos_tesis_estados(), 'estado');
			$form->set_datos($datos);
		}
	}

	function evt__form_alumnos_generica__modificacion($datos)
	{
		$this->datos_temp_alumnos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_alumnos_beca ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_alumnos_beca(form_alumnos_beca $form)
	{
		$datos_defecto = array('alumnos' => encuesta::alumnos_todos);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_alumnos)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_alumnos;
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$datos['anios_academicos'] = aplanar_matriz($this->cn()->entidad()->get_datos_anios_academicos(), 'anio_academico');
		}

		if (isset($datos['edad_desde']) || isset($datos['edad_hasta'])) {
			$datos['edad'] = comunes::get_mayor_menor_entre($datos, 'edad_desde', 'edad_hasta');
			if ($datos['edad'] == comunes::menor) {
				$datos['edad_desde'] = $datos['edad_hasta'];
				unset($datos['edad_hasta']);
			}
		}

		$form->set_datos($datos);
	}

	function evt__form_alumnos_beca__modificacion($datos)
	{
		if ($datos['edad'] == comunes::menor) {
			$datos['edad_hasta'] = $datos['edad_desde'];
			$datos['edad_desde'] = null;
		}
		$this->datos_temp_alumnos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_alumnos -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_alumnos(form_alumnos $form)
	{
		$datos_defecto = array('inscriptos_comision' => encuesta::inscriptos_comision_todos,
			'cursada_aprobada' => comunes::si);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_alumnos)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_alumnos);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_habilitacion());
		}
	}

	function evt__form_alumnos__modificacion($datos)
	{
		$this->datos_temp_alumnos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_modalidades ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_temp_modalidades)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_modalidades;
		} else {
			$datos = $this->cn()->entidad()->get_datos_modalidades();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__form_ml_modalidades__modificacion($datos)
	{
		$this->datos_temp_modalidades = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_ubicaciones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_ubicaciones(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_temp_ubicaciones)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_ubicaciones;
		} else {
			$datos = $this->cn()->entidad()->get_datos_ubicaciones();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_ubicaciones', 'ubicacion', 'ubicacion', true);
		$datos_posibles = toba::consulta_php('co_ubicaciones')->get_listado($filtro);

		if ($this->cn()->entidad()->es_nueva() && toba::consulta_php('co_ubicaciones')->get_cantidad_ubicaciones() == 1 && isset($datos_posibles[0])) {
			$datos_posibles[0]['requerido'] = 1;
			$form_ml->agregar_registro($datos_posibles[0]);
		}

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__form_ml_ubicaciones__modificacion($datos)
	{
		$this->datos_temp_ubicaciones = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_docentes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_docentes(form_docentes $form)
	{
		$datos_defecto = array('docentes' => encuesta::docentes_todos);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_docentes)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_docentes);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$datos['responsabilidades'] = aplanar_matriz($this->cn()->entidad()->get_datos_docentes_resp(), 'responsabilidad');
			$form->set_datos($datos);
		}
	}

	function evt__form_docentes__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_habilitacion($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_docentes = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_egresados ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_egresados(form_egresados $form)
	{
		$datos_defecto = array('egresados' => encuesta::egresados_todos);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_alumnos)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_alumnos);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$datos['certificados'] = aplanar_matriz($this->cn()->entidad()->get_datos_certificados(), 'certificado');
			$form->set_datos($datos);
		}
	}

	function evt__form_egresados__modificacion($datos)
	{
		$this->datos_temp_alumnos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_pares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_pares(form_pares $form)
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo); 

		$datos_defecto = array('pares_destinatario' => encuesta::pares_responsable_docentes);
		$form->set_datos_defecto($datos_defecto);

		if (!$this->cn()->entidad()->es_nueva() && toba::consulta_php('co_encuestas')->get_tipo_habilitacion($this->cn()->entidad()->get_columna_habilitacion('habilitacion')) == encuesta::tipo_pares) {
			$form->set_solo_lectura(array('pares_destinatario', 'pares_responsable'));
		}

		if (isset($this->datos_temp_alumnos)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_alumnos);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$form->set_datos($datos);
		}
	}

	function evt__form_pares__modificacion($datos)
	{
		$this->datos_temp_alumnos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_destinatarios_docentes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_destinatarios_docentes(form_destinatarios_docentes $form)
	{
		$datos_defecto = array('docentes' => encuesta::docentes_todos);
		$form->set_datos_defecto($datos_defecto);

		if (isset($this->datos_temp_destinatarios_docentes)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_destinatarios_docentes);
		} else {
			$datos = $this->cn()->entidad()->get_datos_habilitacion();
			$datos['responsabilidades'] = aplanar_matriz($this->cn()->entidad()->get_datos_docentes_resp(), 'responsabilidad');
			$form->set_datos($datos);
		}
	}

	function evt__form_destinatarios_docentes__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_habilitacion($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_destinatarios_docentes = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- requisitos_asociados ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__requisitos_asociados(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->entidad()->get_datos_requisitos();
		$cuadro->set_datos($datos);
	}

	function evt__requisitos_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_requisito($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_requisitos ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_requisitos(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_requisitos', false);
	}

	function evt__filtro_requisitos__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_requisitos', $datos);
	}

	function evt__filtro_requisitos__cancelar()
	{
		$this->filtro_limpiar('filtro_requisitos');
	}

	//-----------------------------------------------------------------------------------
	//---- requisitos_disponibles -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__requisitos_disponibles(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_requisitos');
		$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos');
		$indx_msj_eof = empty($filtro) ? '800_eof_cuadro' : '800_eof_cuadro_filtrado';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural'))));

		$filtro = empty($filtro) ? 'true' : $filtro;

		// Filtro por requisitos que aplican a becas.
		$filtro .= " AND sga_requisitos.aplica_a_becas = '".comunes::si."'";

		$requisitos_asociados = $this->cn()->entidad()->get_datos_requisitos();
		if (!empty($requisitos_asociados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($requisitos_asociados, 'sga_requisitos', 'requisito', 'requisito', true);
		}

		$datos = toba::consulta_php('co_requisitos')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__requisitos_disponibles__seleccion($seleccion)
	{
		$this->requisitos_seleccionados = $seleccion;
	}

	function evt__requisitos_disponibles__agregar()
	{
		if (!empty($this->requisitos_seleccionados)) {
			foreach ($this->requisitos_seleccionados as $requisito) {
				$requisito['obligatorio'] = comunes::si;
				$this->cn()->entidad()->agregar_requisito($requisito);
			}
		}
	}

	function post_eventos()
	{
		try {
			if (isset($this->datos_temp_alumnos)) {
				$this->cn()->entidad()->set_datos_habilitacion($this->datos_temp_alumnos);
				unset($this->datos_temp_alumnos);
			}
			if (isset($this->datos_temp_modalidades)) {
				$this->cn()->entidad()->set_datos_modalidades($this->datos_temp_modalidades);
				unset($this->datos_temp_modalidades);
			}
			if (isset($this->datos_temp_ubicaciones)) {
				$this->cn()->entidad()->set_datos_ubicaciones($this->datos_temp_ubicaciones);
				unset($this->datos_temp_ubicaciones);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__validar_cambio_tipo_relevamiento($params, toba_ajax_respuesta $respuesta)
	{
		$tipo_relevamiento = current($params);
		$tipo_relevamiento_original = $this->cn()->entidad()->get_columna_habilitacion('tipo_relevamiento');
		$salida = false;
		if (!empty($tipo_relevamiento_original) && $tipo_relevamiento != $tipo_relevamiento_original) {
			$salida = array('mensaje' => guarani_mensajes::get('800ENC_confirmar_cambio_tipo_relevamiento'));
		}
		$respuesta->set($salida);
	}

	function ajax__validar_cambio_tipo($params, toba_ajax_respuesta $respuesta)
	{
		$tipo = current($params);
		$tipo_original = $this->cn()->entidad()->get_columna_habilitacion('tipo');

		$encuestas = $this->cn()->entidad()->get_datos_grupo();
		$docentes_resp = $this->cn()->entidad()->get_datos_docentes_resp();

		$salida = false;
		if (!empty($encuestas) ||
			!empty($tipo_original) && ($tipo == encuesta::tipo_generica || $tipo == encuesta::tipo_egresados || $tipo_original == encuesta::tipo_generica || $tipo_original == encuesta::tipo_egresados) ||
			!empty($docentes_resp) && ($tipo == encuesta::tipo_academica || $tipo == encuesta::tipo_generica || $tipo == encuesta::tipo_egresados)) {
			$salida = array('mensaje' => guarani_mensajes::get('800ENC_confirmar_cambio_tipo'));
		}

		$respuesta->set($salida);
	}

	function ajax__validar_cambio_encuestado($params, toba_ajax_respuesta $respuesta)
	{
		$encuestado = current($params);
		$encuestado_original = $this->cn()->entidad()->get_columna_habilitacion('encuestado');
		$salida = false;
		if (!empty($encuestado_original) && $encuestado != $encuestado_original) {
			$salida = array('mensaje' => guarani_mensajes::get('800ENC_confirmar_cambio_encuestado'));
		}
		$respuesta->set($salida);
	}

	function ajax__datos_becas_convocatoria($params, toba_ajax_respuesta $respuesta)
	{
		$becas_convocatoria = current($params);
		$datos_becas_convocatoria = $this->s__convocatorias[$becas_convocatoria];
		$becas = '';
		foreach ($datos_becas_convocatoria['becas'] as $beca) {
			$becas .= empty($becas) ? $beca['nombre'] : "/{$beca['nombre']}";
		}
		$datos_becas_convocatoria['becas'] = $becas;
		$datos_becas_convocatoria['fecha_desde_visual'] = guarani_fecha::convertir_fecha_formato_frontend($datos_becas_convocatoria['fecha_desde']);
		$datos_becas_convocatoria['fecha_hasta_visual'] = guarani_fecha::convertir_fecha_formato_frontend($datos_becas_convocatoria['fecha_hasta']);
		$datos_becas_convocatoria['fecha_evaluacion_ha_desde_visual'] = empty($datos_becas_convocatoria['pe_desde']) ? null : guarani_fecha::convertir_fecha_formato_frontend($datos_becas_convocatoria['pe_desde']);
		$datos_becas_convocatoria['fecha_evaluacion_ha_hasta_visual'] = guarani_fecha::convertir_fecha_formato_frontend($datos_becas_convocatoria['pe_hasta']);
		if (!empty($datos_becas_convocatoria)) {
			$respuesta->set($datos_becas_convocatoria);
		} else {
			$respuesta->set(false);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Obtiene un conjunto de convocatorias desde Tehuelceh para la Unidad de Gestión especificada.
	 * Si no se envía un valor, se obtiene el default (parametrizado).
	 * 
	 * @param int $ug
	 * @return array
	 */
	function get_convocatorias_tehuelche($ug = null)
	{
		if (beca::conexion_tehuelche_ok()) {
			try {
				if (is_null($ug)) {
					$ug = beca::get_ug_default_tehuelche();
				}
				if ($this->cn()->entidad()->es_editable()) {
					$convocatorias = beca::get_convocatorias($ug, beca::validez_vigentes_futuras);
				} else {
					$habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
					$convocatorias = array(beca::get_convocatoria($ug, $habilitacion['becas_convocatoria']));
				}
				$this->s__convocatorias = array();
				// Se excluyen las convocatorias asociadas a algún relevamiento.
				foreach ($convocatorias as $clave => $valor) {
					if (isset($valor['convocatoria']) && !$this->cn()->existe_relevamiento($valor['convocatoria'])) {
						$this->s__convocatorias[$valor['convocatoria']] = $valor;
						$this->s__convocatorias[$valor['convocatoria']]['descripcion'] = $valor['nombre'];
					}
				}
				return $this->s__convocatorias;
			} catch (\GuzzleHttp\Exception\ClientException $e) {
				return array(array('convocatoria' => 'nopar', 'descripcion' => 'Unidad de Gestión Tehuelche inválida'));
			}
		} else {
			return array();
		}
	}

	function get_becas($becas_convocatoria, $ug = null)
	{
		$becas = '';
		if (beca::conexion_tehuelche_ok()) {
			try {
				if (is_null($ug)) {
					$ug = beca::get_ug_default_tehuelche();
				}
				$convocatoria = beca::get_convocatoria($ug, $becas_convocatoria);
				foreach ($convocatoria['becas'] as $beca) {
					$becas .= empty($becas) ? $beca['nombre'] : "/{$beca['nombre']}";
				}
			} catch (\GuzzleHttp\Exception\ClientException $e) {
				return $becas;
			}
		}
		return $becas;
	}

	/**
	 * Recupera los subtipos de encuesta válidos para el tipo del grupo de encuestas.
	 *
	 * @return array
	 */
	function get_encuesta_subtipos()
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		$filtro = " gde_subtipos.subtipo IN (SELECT gde_subtipos_x_tipo.subtipo FROM gde_subtipos_x_tipo WHERE gde_subtipos_x_tipo.tipo = {$datos_habilitacion['tipo']})";
		return toba::consulta_php('co_encuestas')->get_encuesta_subtipos($filtro);
	}

	/**
	 * Recupera el conjunto de años académicos mayores o iguales al año calendario - 1.
	 *
	 * @return array
	 */
	function get_anios_academicos_mayores_o_iguales_calendario()
	{
		return toba::consulta_php('co_anios_academicos')->get_anios_academicos_mayores_o_iguales(guarani_fecha::get_hoy_parte('anio')-1);
	}

	/**
	 * Recupera los certificados asociables a la habilitación de acuerdo al alcance.
	 *
	 * @return array
	 */
	function get_certificados()
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		if (!isset($datos_habilitacion['alcance']) || $datos_habilitacion['alcance'] == encuesta::alcance_toda_institucion) {
			toba::consulta_php('co_certificados')->get_listado('', 'certificado_descr');
		} else {
			switch ($datos_habilitacion['alcance']) {
				case encuesta::alcance_propuestas:
					return toba::consulta_php('co_certificados')->get_certificados_por_propuestas($this->cn()->entidad()->get_datos_propuestas());
				case encuesta::alcance_responsables_academicas:
					return toba::consulta_php('co_certificados')->get_certificados_por_responsables_academicas($this->cn()->entidad()->get_datos_responsables_academicas());
				case encuesta::alcance_tipos_propuesta:
					return toba::consulta_php('co_certificados')->get_certificados_por_tipos_propuesta($this->cn()->entidad()->get_datos_tipos_propuesta());
			}
		}
	}

	function get_alcances()
	{
		$datos_habilitacion = $this->cn()->entidad()->get_datos_habilitacion();
		return toba::consulta_php('co_encuestas')->get_habilitacion_alcances_x_tipo($datos_habilitacion['tipo']);
	}

	/**
	 * Configuración parametrizada que determina si se utiliza Repositorio Digital 
	 * Integrado (RDI) para almacenar los archivos asociados a requisitos documentales.
	 * 
	 * @return boolean
	 */
	function usa_repositorio_digital()
	{
		return guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si;
	}
}