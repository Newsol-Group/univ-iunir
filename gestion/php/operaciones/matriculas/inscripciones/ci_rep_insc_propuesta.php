<?php
class ci_rep_insc_propuesta extends ci_rep_sin_listado_inicial
{
	protected $conf_servicios_excluidos = array('cascadas_efs', 'filtrado_ef_ce', 'cascadas_columnas');
	protected $s__seleccion;
	protected $s__imprimir = false;
	protected $s__preinscripcion;

        const IMPRIMIR = 0;
        const IMPRIMIR_PREINSCRIPCION = 1;

        function conf()
	{
		$datos = $this->cuadro()->get_datos();
		if (empty($datos)) {
			if ($this->pantalla()->existe_evento('pdf')) {
				$this->pantalla()->eliminar_evento('pdf');
			}
			if ($this->pantalla()->existe_evento('pdf2')) {
				$this->pantalla()->eliminar_evento('pdf2');
			}
		}
	}

	function get_tabla_principal()
	{
		return 'sga_propuestas_aspira';
	}

	function get_datos($filtro)
	{
                // Ordeno según el corte de control del cuadro
                $order = 'ubicacion_nombre, anio_periodo_desc, propuesta_descripcion';
		return toba::consulta_php('co_inscripciones')->get_inscriptos_perfil_de_datos($filtro, $order);
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$servicio = toba::memoria()->get_servicio_solicitado();
		if (!in_array($servicio, $this->conf_servicios_excluidos)) {
			parent::conf__cuadro($cuadro);
		}
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true)));
	}

	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__seleccion)) {
			foreach ($this->s__seleccion as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$parametros = toba::memoria()->get_parametros();

                // Si se hizo click en "Imprimir Preinscripción"
		if (isset($parametros['preinscripcion']) && $parametros['preinscripcion'] == static::IMPRIMIR_PREINSCRIPCION) {


                    $path = guarani::get_path_reporte_jasper('FichaPreinscripcion.jasper');
                    $report->set_path_reporte($path);
                    $institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());
                    $report->set_parametro('titulo', 'S', '');
                    $path_logo = guarani::get_path_logo_impresion();
                    $report->set_parametro('logo', 'S', $path_logo);
                    $report->set_parametro('institucion', 'S', $institucion['nombre']);

                    // Si $this->s__seleccion es iterable hago el foreach
                    // https://www.w3docs.com/snippets/php/how-to-fix-the-invalid-argument-supplied-for-foreach-php-error.html
                    // https://www.php.net/manual/es/function.is-iterable.php
                    if (is_iterable($this->s__seleccion)) {

			foreach ($this->s__seleccion as $dato) {

				$persona = $dato['persona'];
				$propuesta = $dato['propuesta'];

				$datos_propuesta = toba::consulta_php('co_propuestas')->get_descripcion_propuesta($propuesta);
				$dato_censal = toba::consulta_php('co_datos_censales')->get_id_dato_censal($persona);
				$dato_censal = $dato_censal["dato_censal"];

				$datos_personas = toba::consulta_php('co_personas')->get_datos_personales_rest($persona);
				$coberturas_salud = toba::consulta_php('co_datos_censales')->get_coberturas_salud();
				$estados_civiles = toba::consulta_php('co_datos_censales')->get_estados_civiles();
				$niveles_idioma = toba::consulta_php('co_datos_censales')->get_niveles_idioma();
				$orientaciones = toba::consulta_php('co_personas')->get_orientaciones_recibidas();
				$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($persona);

				$report->set_parametro('1_apellido', 'S', $datos_persona['apellido']);
				$report->set_parametro('1_nombre', 'S', $datos_persona['nombres']);
				$report->set_parametro('1_propuesta', 'S', $datos_propuesta);
				$report->set_parametro('1_tipo_documento', 'S', $datos_persona['tipo_documento']);
				$report->set_parametro('1_nro_documento', 'S', $datos_persona['nro_documento']);
				$report->set_parametro('1_nacionalidad', 'S', $datos_persona['nacionalidad_valor']);
				$report->set_parametro('1_fecha_nacimiento', 'S', $datos_persona['fecha_nacimiento_formato_visual']);

				if ($dato_censal) {
					$datos_personales = current(toba::consulta_php('co_datos_censales')->get_datos_personales($dato_censal));

					if ($datos_personales) {
						$datos_personales = $this->adecuar_null($datos_personales);
						$report->set_parametro('2_domicilio_periodo_calle', 'S', $datos_personales['periodo_lectivo_calle']);
						$report->set_parametro('2_domicilio_periodo_piso', 'S', $datos_personales['periodo_lectivo_piso']);
						$report->set_parametro('2_domicilio_periodo_unidad', 'S', $datos_personales['periodo_lectivo_unidad']);
						$report->set_parametro('2_domicilio_periodo_cp', 'S', $datos_personales['periodo_lectivo_codigo_postal']);
						$report->set_parametro('2_domicilio_periodo_numero', 'S', $datos_personales['periodo_lectivo_numero']);
						$report->set_parametro('2_domicilio_periodo_departamento', 'S', $datos_personales['periodo_lectivo_departamento']);
						$report->set_parametro('2_domicilio_periodo_barrio', 'S', $datos_personales['periodo_lectivo_barrio']);
						$report->set_parametro('2_domicilio_procedencia_calle', 'S', $datos_personales['procedencia_calle']);
						$report->set_parametro('2_domicilio_procedencia_piso', 'S', $datos_personales['procedencia_piso']);
						$report->set_parametro('2_domicilio_procedencia_unidad', 'S', $datos_personales['procedencia_unidad']);
						$report->set_parametro('2_domicilio_procedencia_cp', 'S', $datos_personales['procedencia_codigo_postal']);
						$report->set_parametro('2_domicilio_procedencia_numero', 'S', $datos_personales['procedencia_numero']);
						$report->set_parametro('2_domicilio_procedencia_departamento', 'S', $datos_personales['procedencia_departamento']);
						$report->set_parametro('2_domicilio_procedencia_localidad', 'S', $datos_personales['procedencia_localidad']);
						$report->set_parametro('2_domicilio_procedencia_barrio', 'S', $datos_personales['procedencia_barrio']);
						$tipos_viviendas = toba::consulta_php('co_datos_censales')->get_tipos_viviendas();
						$tipo_vivienda = $this->get_descripcion($datos_personales['tipo_vivienda'], $tipos_viviendas);
						$report->set_parametro('2_domicilio_periodo_tipo', 'S', $tipo_vivienda);
						$vive_con = toba::consulta_php('co_datos_censales')->get_vive_con();
						$vivecon = $this->get_descripcion($datos_personales['vive_con'], $vive_con);
						$report->set_parametro('2_domicilio_periodo_vivis', 'S', $vivecon);

						if (is_int($datos_personales['periodo_lectivo_localidad'])) {
							$localidades = explode(' -', toba::consulta_php('co_mug')->get_localidad($datos_personales['periodo_lectivo_localidad']));
							if ($localidades) {
								$report->set_parametro('2_domicilio_periodo_localidad', 'S', $localidades[0]);
							} else {
								$report->set_parametro('2_domicilio_periodo_localidad', 'S', '-');
							}
						} else {
							$report->set_parametro('2_domicilio_periodo_localidad', 'S', '-');
						}

						$report->set_parametro('4_unido_hecho', 'S', $datos_personales['unido_hecho']);
						$report->set_parametro('4_cantidad_hijos', 'S', $datos_personales['cantidad_hijos']);
						$report->set_parametro('4_cantidad_familia', 'S', $datos_personales['cantidad_familia']);
						$estado_civil = $this->get_descripcion($datos_personales['estado_civil'], $estados_civiles);
						$report->set_parametro('4_estado_civil', 'S', $estado_civil);
						$report->set_parametro('4_situacion_padre', 'S', $datos_personales['situacion_padre']);
						$report->set_parametro('4_situacion_madre', 'S', $datos_personales['situacion_madre']);
						$cobertura_descripcion = $this->get_descripcion($datos_personales['cobertura_salud'], $coberturas_salud);
						$report->set_parametro('5_cobertura_salud', 'S', $cobertura_descripcion);
						$report->set_parametro('5_es_celiaco', 'S', $this->get_si_no($datos_personales['es_celiaco']));
					} else {
						$report->set_parametro('2_domicilio_periodo_calle', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_piso', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_unidad', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_cp', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_numero', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_departamento', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_barrio', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_calle', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_piso', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_unidad', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_cp', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_numero', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_departamento', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_localidad', 'S', '-');
						$report->set_parametro('2_domicilio_procedencia_barrio', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_tipo', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_vivis', 'S', '-');
						$report->set_parametro('2_domicilio_periodo_localidad', 'S', '-');
						$report->set_parametro('4_unido_hecho', 'S', '-');
						$report->set_parametro('4_cantidad_hijos', 'S', '-');
						$report->set_parametro('4_cantidad_familia', 'S', '-');
						$report->set_parametro('4_estado_civil', 'S', '-');
						$report->set_parametro('4_situacion_padre', 'S', '-');
						$report->set_parametro('4_situacion_madre', 'S', '-');
						$report->set_parametro('5_cobertura_salud', 'S', '-');
						$report->set_parametro('5_es_celiaco', 'S', '-');
					}

					$datos_economicos = current(toba::consulta_php('co_datos_censales')->get_datos_economicos($dato_censal));
					if ($datos_economicos) {
						$datos_economicos = $this->adecuar_null($datos_economicos);
						$report->set_parametro('3_costeo_estudios_familiar', 'S', $this->get_si_no($datos_economicos['costeo_estudios_familiar']));
						$report->set_parametro('3_costeo_estudios_plan_social', 'S', $this->get_si_no($datos_economicos['costeo_estudios_plan_social']));
						$report->set_parametro('3_costeo_estudios_trabajo', 'S', $this->get_si_no($datos_economicos['costeo_estudios_trabajo']));
						$report->set_parametro('3_costeo_estudios_beca', 'S', $this->get_si_no($datos_economicos['costeo_estudios_beca']));
						$report->set_parametro('3_costeo_estudios_otro', 'S', $this->get_si_no($datos_economicos['costeo_estudios_otro']));
						$report->set_parametro('3_costeo_estudios_descripcion', 'S', $datos_economicos['costeo_estudios_descripcion']);
						$report->set_parametro('3_beca_fuente_universidad', 'S', $this->get_si_no($datos_economicos['beca_fuente_universidad']));
						$report->set_parametro('3_beca_fuente_nacional', 'S', $this->get_si_no($datos_economicos['beca_fuente_nacional']));
						$report->set_parametro('3_beca_fuente_provincial', 'S', $this->get_si_no($datos_economicos['beca_fuente_provincial']));
						$report->set_parametro('3_beca_fuente_municipal', 'S', $this->get_si_no($datos_economicos['beca_fuente_municipal']));
						$report->set_parametro('3_beca_fuente_internacional', 'S', $this->get_si_no($datos_economicos['beca_fuente_internacional']));
						$report->set_parametro('3_beca_fuente_otra', 'S', $this->get_si_no($datos_economicos['beca_fuente_otra']));
						$report->set_parametro('3_beca_tipo_economica', 'S', $this->get_si_no($datos_economicos['beca_tipo_economica']));
						$report->set_parametro('3_beca_tipo_servicios', 'S', $this->get_si_no($datos_economicos['beca_tipo_servicios']));
						$report->set_parametro('3_beca_tipo_investigacion', 'S', $this->get_si_no($datos_economicos['beca_tipo_investigacion']));
						$report->set_parametro('3_beca_economica_transporte', 'S', $this->get_si_no($datos_economicos['beca_economica_transporte']));
						$report->set_parametro('3_beca_economica_efectivo', 'S', $this->get_si_no($datos_economicos['beca_economica_efectivo']));
						$report->set_parametro('3_beca_economica_fotocopias', 'S', $this->get_si_no($datos_economicos['beca_economica_fotocopias']));
						$report->set_parametro('3_beca_economica_habitacional', 'S', $this->get_si_no($datos_economicos['beca_economica_habitacional']));
						$report->set_parametro('3_beca_economica_comedor', 'S', $this->get_si_no($datos_economicos['beca_economica_comedor']));
						$report->set_parametro('4_trabajo_tarea_descripcion', 'S', $datos_economicos['trabajo_tarea_descripcion']);
					} else {
						$report->set_parametro('3_costeo_estudios_familiar', 'S', '-');
						$report->set_parametro('3_costeo_estudios_plan_social', 'S', '-');
						$report->set_parametro('3_costeo_estudios_trabajo', 'S', '-');
						$report->set_parametro('3_costeo_estudios_beca', 'S', '-');
						$report->set_parametro('3_costeo_estudios_otro', 'S', '-');
						$report->set_parametro('3_costeo_estudios_descripcion', 'S', '-');
						$report->set_parametro('3_beca_fuente_universidad', 'S', '-');
						$report->set_parametro('3_beca_fuente_nacional', 'S', '-');
						$report->set_parametro('3_beca_fuente_provincial', 'S', '-');
						$report->set_parametro('3_beca_fuente_municipal', 'S', '-');
						$report->set_parametro('3_beca_fuente_internacional', 'S', '-');
						$report->set_parametro('3_beca_fuente_otra', 'S', '-');
						$report->set_parametro('3_beca_tipo_economica', 'S', '-');
						$report->set_parametro('3_beca_tipo_servicios', 'S', '-');
						$report->set_parametro('3_beca_tipo_investigacion', 'S', '-');
						$report->set_parametro('3_beca_economica_transporte', 'S', '-');
						$report->set_parametro('3_beca_economica_efectivo', 'S', '-');
						$report->set_parametro('3_beca_economica_fotocopias', 'S', '-');
						$report->set_parametro('3_beca_economica_habitacional', 'S', '-');
						$report->set_parametro('3_beca_economica_comedor', 'S', '-');
						$report->set_parametro('4_trabajo_tarea_descripcion', 'S', '-');
					}

					$datos_actividades = current(toba::consulta_php('co_datos_censales')->get_datos_actividades($dato_censal));
					if ($datos_actividades) {
						$datos_actividades = $this->adecuar_null($datos_actividades);
						$report->set_parametro('5_tecnologia_pc_casa', 'S', $this->get_si_no($datos_actividades['tecnologia_pc_casa']));
						$report->set_parametro('5_tecnologia_pc_trabajo', 'S', $this->get_si_no($datos_actividades['tecnologia_pc_trabajo']));
						$report->set_parametro('5_tecnologia_pc_otro_lugar', 'S', $this->get_si_no($datos_actividades['tecnologia_pc_otro_lugar']));
						$report->set_parametro('5_tecnologia_pc_universidad', 'S', $this->get_si_no($datos_actividades['tecnologia_pc_universidad']));
						$report->set_parametro('5_tecnologia_int_casa', 'S', $this->get_si_no($datos_actividades['tecnologia_int_casa']));
						$report->set_parametro('5_tecnologia_int_movil', 'S', $this->get_si_no($datos_actividades['tecnologia_int_movil']));
						$report->set_parametro('5_tecnologia_int_trabajo', 'S', $this->get_si_no($datos_actividades['tecnologia_int_trabajo']));
						$report->set_parametro('5_tecnologia_int_otro_lugar', 'S', $this->get_si_no($datos_actividades['tecnologia_int_otro_lugar']));
						$report->set_parametro('5_tecnologia_int_universidad', 'S', $this->get_si_no($datos_actividades['tecnologia_int_universidad']));
						$report->set_parametro('5_deportes', 'S', $this->get_si_no($datos_actividades['deportes']));
						$report->set_parametro('5_deportes_gimnasio', 'S', $this->get_si_no($datos_actividades['deportes_gimnasio']));
						$report->set_parametro('5_deportes_particular', 'S', $this->get_si_no($datos_actividades['deportes_particular']));
						$report->set_parametro('5_deportes_otro_lugar', 'S', $this->get_si_no($datos_actividades['deportes_otro_lugar']));
						$report->set_parametro('5_deportes_universidad', 'S', $this->get_si_no($datos_actividades['deportes_universidad']));
						$report->set_parametro('5_deportes_tenis', 'S', $this->get_si_no($datos_actividades['deportes_tenis']));
						$report->set_parametro('5_deportes_voley', 'S', $this->get_si_no($datos_actividades['deportes_voley']));
						$report->set_parametro('5_deportes_otros', 'S', $this->get_si_no($datos_actividades['deportes_otros']));
						$report->set_parametro('5_deportes_futbol', 'S', $this->get_si_no($datos_actividades['deportes_futbol']));
						$report->set_parametro('5_deportes_basquet', 'S', $this->get_si_no($datos_actividades['deportes_basquet']));
						$report->set_parametro('5_deportes_gimnasia', 'S', $this->get_si_no($datos_actividades['deportes_gimnasia']));
						$report->set_parametro('5_deportes_handball', 'S', $this->get_si_no($datos_actividades['deportes_handball']));
						$report->set_parametro('5_deportes_natacion', 'S', $this->get_si_no($datos_actividades['deportes_natacion']));
						$report->set_parametro('5_idioma_otro', 'S', $datos_actividades['idioma_otro']);
						$report->set_parametro('5_nivel_idioma_otro', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_otro'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_chino', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_chino'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_ingles', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_ingles'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_aleman', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_aleman'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_frances', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_frances'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_italiano', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_italiano'], $niveles_idioma));
						$report->set_parametro('5_nivel_idioma_portugues', 'S', $this->get_descripcion($datos_actividades['nivel_idioma_portugues'], $niveles_idioma));
					} else {
						$report->set_parametro('5_tecnologia_pc_casa', 'S', '-');
						$report->set_parametro('5_tecnologia_pc_trabajo', 'S', '-');
						$report->set_parametro('5_tecnologia_pc_otro_lugar', 'S', '-');
						$report->set_parametro('5_tecnologia_pc_universidad', 'S', '-');
						$report->set_parametro('5_tecnologia_int_casa', 'S', '-');
						$report->set_parametro('5_tecnologia_int_movil', 'S', '-');
						$report->set_parametro('5_tecnologia_int_trabajo', 'S', '-');
						$report->set_parametro('5_tecnologia_int_otro_lugar', 'S', '-');
						$report->set_parametro('5_tecnologia_int_universidad', 'S', '-');
						$report->set_parametro('5_deportes', 'S', '-');
						$report->set_parametro('5_deportes_gimnasio', 'S', '-');
						$report->set_parametro('5_deportes_particular', 'S', '-');
						$report->set_parametro('5_deportes_otro_lugar', 'S', '-');
						$report->set_parametro('5_deportes_universidad', 'S', '-');
						$report->set_parametro('5_deportes_tenis', 'S', '-');
						$report->set_parametro('5_deportes_voley', 'S', '-');
						$report->set_parametro('5_deportes_otros', 'S', '-');
						$report->set_parametro('5_deportes_futbol', 'S', '-');
						$report->set_parametro('5_deportes_basquet', 'S', '-');
						$report->set_parametro('5_deportes_gimnasia', 'S', '-');
						$report->set_parametro('5_deportes_handball', 'S', '-');
						$report->set_parametro('5_deportes_natacion', 'S', '-');
						$report->set_parametro('5_idioma_otro', 'S', '-');
						$report->set_parametro('5_nivel_idioma_otro', 'S', '-');
						$report->set_parametro('5_nivel_idioma_chino', 'S', '-');
						$report->set_parametro('5_nivel_idioma_ingles', 'S', '-');
						$report->set_parametro('5_nivel_idioma_aleman', 'S', '-');
						$report->set_parametro('5_nivel_idioma_frances', 'S', '-');
						$report->set_parametro('5_nivel_idioma_italiano', 'S', '-');
						$report->set_parametro('5_nivel_idioma_portugues', 'S', '-');
					}

					$eleccion_institucion = current(toba::consulta_php('co_datos_censales')->get_datos_eleccion_institucion($dato_censal));
					if ($eleccion_institucion) {
						$eleccion_institucion = $this->adecuar_null($eleccion_institucion);
						$report->set_parametro('1_como_conocio_institucion', 'S', $eleccion_institucion['como_conocio_institucion']);
						$report->set_parametro('1_insc_otra_institucion_desc', 'S', $eleccion_institucion['insc_otra_institucion_desc']);
						$report->set_parametro('1_insc_otra_institucion', 'S', $this->get_si_no($eleccion_institucion['insc_otra_institucion']));
						$report->set_parametro('1_mot_otros', 'S', $this->get_si_no($eleccion_institucion['mot_otros_i']));
						$report->set_parametro('1_mot_difusion', 'S', $this->get_si_no($eleccion_institucion['mot_difusion']));
						$report->set_parametro('1_mot_economico', 'S', $this->get_si_no($eleccion_institucion['mot_economico']));
						$report->set_parametro('1_mot_prestigio', 'S', $this->get_si_no($eleccion_institucion['mot_prestigio']));
						$report->set_parametro('1_mot_ubicacion', 'S', $this->get_si_no($eleccion_institucion['mot_ubicacion']));
						$report->set_parametro('1_mot_sistema_ingreso', 'S', $this->get_si_no($eleccion_institucion['mot_sistema_ingreso']));
						$report->set_parametro('1_mot_recomendacion_amigos', 'S', $this->get_si_no($eleccion_institucion['mot_recomendacion_amigos']));
						$report->set_parametro('1_mot_recomendacion_estudiantes', 'S', $this->get_si_no($eleccion_institucion['mot_recomendacion_estudiantes']));
					} else {
						$report->set_parametro('1_como_conocio_institucion', 'S', '-');
						$report->set_parametro('1_insc_otra_institucion_desc', 'S', '-');
						$report->set_parametro('1_insc_otra_institucion', 'S', '-');
						$report->set_parametro('1_mot_otros', 'S', '-');
						$report->set_parametro('1_mot_difusion', 'S', '-');
						$report->set_parametro('1_mot_economico', 'S', '-');
						$report->set_parametro('1_mot_prestigio', 'S', '-');
						$report->set_parametro('1_mot_ubicacion', 'S', '-');
						$report->set_parametro('1_mot_sistema_ingreso', 'S', '-');
						$report->set_parametro('1_mot_recomendacion_amigos', 'S', '-');
						$report->set_parametro('1_mot_recomendacion_estudiantes', 'S', '-');
					}

					$eleccion_propuesta = current(toba::consulta_php('co_datos_censales')->get_datos_eleccion_propuesta($dato_censal));
					if ($eleccion_propuesta) {
						$eleccion_propuesta = $this->adecuar_null($eleccion_propuesta);
						$report->set_parametro('1_mot_vocacion', 'S', $this->get_si_no($eleccion_propuesta['mot_vocacion']));
						$report->set_parametro('1_mot_otros_propuesta', 'S', $this->get_si_no($eleccion_propuesta['mot_otros_p']));
						$report->set_parametro('1_mot_util_sociedad', 'S', $this->get_si_no($eleccion_propuesta['mot_util_sociedad']));
						$report->set_parametro('1_mot_mejora_economica', 'S', $this->get_si_no($eleccion_propuesta['mot_mejora_economica']));
						$report->set_parametro('1_mot_insercion_laboral', 'S', $this->get_si_no($eleccion_propuesta['mot_insercion_laboral']));
						$report->set_parametro('1_mot_perfil_profesional', 'S', $this->get_si_no($eleccion_propuesta['mot_perfil_profesional']));
						$report->set_parametro('1_mot_sugerencia_familiar', 'S', $this->get_si_no($eleccion_propuesta['mot_sugerencia_familiar']));
						$report->set_parametro('1_mot_reconocimiento_social', 'S', $this->get_si_no($eleccion_propuesta['mot_reconocimiento_social']));
					} else {
						$report->set_parametro('1_mot_vocacion', 'S', '-');
						$report->set_parametro('1_mot_otros_propuesta', 'S', '-');
						$report->set_parametro('1_mot_util_sociedad', 'S', '-');
						$report->set_parametro('1_mot_mejora_economica', 'S', '-');
						$report->set_parametro('1_mot_insercion_laboral', 'S', '-');
						$report->set_parametro('1_mot_perfil_profesional', 'S', '-');
						$report->set_parametro('1_mot_sugerencia_familiar', 'S', '-');
						$report->set_parametro('1_mot_reconocimiento_social', 'S', '-');
					}

					$datos_colegio_secundario = current(toba::consulta_php('co_datos_censales')->get_datos_colegio_secundario($persona));
					if ($datos_colegio_secundario) {
						$datos_colegio_secundario = $this->adecuar_null($datos_colegio_secundario);
						$report->set_parametro('5_secundario_titulo', 'S', $datos_colegio_secundario['titulo']);
						$report->set_parametro('5_secundario_anio_egreso', 'S', $datos_colegio_secundario['anio_egreso']);
						$report->set_parametro('5_secundario_nombre_colegio', 'S', $datos_colegio_secundario['nombre_colegio']);
						$report->set_parametro('5_secundario_adeuda_materias', 'S', $this->get_si_no($datos_colegio_secundario['adeuda_materias']));
					} else {
						$report->set_parametro('5_secundario_titulo', 'S', '-');
						$report->set_parametro('5_secundario_anio_egreso', 'S', '-');
						$report->set_parametro('5_secundario_nombre_colegio', 'S', '-');
						$report->set_parametro('5_secundario_adeuda_materias', 'S', '-');
					}

					$datos_estudios_superiores = current(toba::consulta_php('co_datos_censales')->get_datos_estudios_superiores($persona));
					if ($datos_estudios_superiores) {
						$datos_estudios_superiores = $this->adecuar_null($datos_estudios_superiores);
						$estudios_superiores_estados = toba::consulta_php('co_comunes')->get_estado_finalizado_abandonado();
						$report->set_parametro('5_estudios_superiores_lugar', 'S', $datos_estudios_superiores['lugar']);
						$report->set_parametro('5_estudios_superiores_estado', 'S', $datos_estudios_superiores['estado']);
						$report->set_parametro('5_estudios_superiores_propuesta', 'S', $datos_estudios_superiores['propuesta']);
						$report->set_parametro('5_estudios_superiores_anio_egreso', 'S', $datos_estudios_superiores['anio_egreso']);
						$report->set_parametro('5_estudios_superiores_razon_abandono', 'S', $datos_estudios_superiores['razon_abandono']);
						$report->set_parametro('5_estudios_superiores_estado', 'S', $this->get_descripcion($datos_estudios_superiores['estado'], $estudios_superiores_estados));
					} else {
						$report->set_parametro('5_estudios_superiores_lugar', 'S', '-');
						$report->set_parametro('5_estudios_superiores_estado', 'S', '-');
						$report->set_parametro('5_estudios_superiores_propuesta', 'S', '-');
						$report->set_parametro('5_estudios_superiores_anio_egreso', 'S', '-');
						$report->set_parametro('5_estudios_superiores_razon_abandono', 'S', '-');
						$report->set_parametro('5_estudios_superiores_estado', 'S', '-');
					}
				} else {
					$report->set_parametro('5_estudios_superiores_lugar', 'S', '-');
					$report->set_parametro('5_estudios_superiores_estado', 'S', '-');
					$report->set_parametro('5_estudios_superiores_propuesta', 'S', '-');
					$report->set_parametro('5_estudios_superiores_anio_egreso', 'S', '-');
					$report->set_parametro('5_estudios_superiores_razon_abandono', 'S', '-');
					$report->set_parametro('5_estudios_superiores_estado', 'S', '-');
					$report->set_parametro('5_secundario_titulo', 'S', '-');
					$report->set_parametro('5_secundario_anio_egreso', 'S', '-');
					$report->set_parametro('5_secundario_nombre_colegio', 'S', '-');
					$report->set_parametro('5_secundario_adeuda_materias', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_cp', 'S', '-');
					$report->set_parametro('4_unido_hecho', 'S', '-');
					$report->set_parametro('4_cantidad_hijos', 'S', '-');
					$report->set_parametro('4_cantidad_familia', 'S', '-');
					$report->set_parametro('4_estado_civil', 'S', '-');
					$report->set_parametro('4_situacion_padre', 'S', '-');
					$report->set_parametro('4_situacion_madre', 'S', '-');
					$report->set_parametro('5_cobertura_salud', 'S', '-');
					$report->set_parametro('5_es_celiaco', 'S', '-');
					$report->set_parametro('3_beca_fuente_otra', 'S', '-');
					$report->set_parametro('3_beca_tipo_economica', 'S', '-');
					$report->set_parametro('3_beca_tipo_servicios', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_calle', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_piso', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_unidad', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_numero', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_departamento', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_barrio', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_calle', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_piso', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_unidad', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_cp', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_numero', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_departamento', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_localidad', 'S', '-');
					$report->set_parametro('2_domicilio_procedencia_barrio', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_tipo', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_vivis', 'S', '-');
					$report->set_parametro('2_domicilio_periodo_localidad', 'S', '-');
					$report->set_parametro('3_costeo_estudios_familiar', 'S', '-');
					$report->set_parametro('3_costeo_estudios_plan_social', 'S', '-');
					$report->set_parametro('3_costeo_estudios_trabajo', 'S', '-');
					$report->set_parametro('3_costeo_estudios_beca', 'S', '-');
					$report->set_parametro('3_costeo_estudios_otro', 'S', '-');
					$report->set_parametro('3_costeo_estudios_descripcion', 'S', '-');
					$report->set_parametro('3_beca_fuente_universidad', 'S', '-');
					$report->set_parametro('3_beca_fuente_nacional', 'S', '-');
					$report->set_parametro('3_beca_fuente_provincial', 'S', '-');
					$report->set_parametro('3_beca_fuente_municipal', 'S', '-');
					$report->set_parametro('3_beca_fuente_internacional', 'S', '-');
					$report->set_parametro('3_beca_tipo_investigacion', 'S', '-');
					$report->set_parametro('3_beca_economica_transporte', 'S', '-');
					$report->set_parametro('3_beca_economica_efectivo', 'S', '-');
					$report->set_parametro('3_beca_economica_fotocopias', 'S', '-');
					$report->set_parametro('3_beca_economica_habitacional', 'S', '-');
					$report->set_parametro('3_beca_economica_comedor', 'S', '-');
					$report->set_parametro('4_trabajo_tarea_descripcion', 'S', '-');
					$report->set_parametro('5_tecnologia_pc_casa', 'S', '-');
					$report->set_parametro('5_tecnologia_pc_trabajo', 'S', '-');
					$report->set_parametro('5_tecnologia_pc_otro_lugar', 'S', '-');
					$report->set_parametro('5_tecnologia_pc_universidad', 'S', '-');
					$report->set_parametro('5_tecnologia_int_casa', 'S', '-');
					$report->set_parametro('5_tecnologia_int_movil', 'S', '-');
					$report->set_parametro('5_tecnologia_int_trabajo', 'S', '-');
					$report->set_parametro('5_tecnologia_int_otro_lugar', 'S', '-');
					$report->set_parametro('5_tecnologia_int_universidad', 'S', '-');
					$report->set_parametro('5_deportes', 'S', '-');
					$report->set_parametro('5_deportes_gimnasio', 'S', '-');
					$report->set_parametro('5_deportes_particular', 'S', '-');
					$report->set_parametro('5_deportes_otro_lugar', 'S', '-');
					$report->set_parametro('5_deportes_universidad', 'S', '-');
					$report->set_parametro('5_deportes_tenis', 'S', '-');
					$report->set_parametro('5_deportes_voley', 'S', '-');
					$report->set_parametro('5_deportes_otros', 'S', '-');
					$report->set_parametro('5_deportes_futbol', 'S', '-');
					$report->set_parametro('5_deportes_basquet', 'S', '-');
					$report->set_parametro('5_deportes_gimnasia', 'S', '-');
					$report->set_parametro('5_deportes_handball', 'S', '-');
					$report->set_parametro('5_deportes_natacion', 'S', '-');
					$report->set_parametro('5_idioma_otro', 'S', '-');
					$report->set_parametro('5_nivel_idioma_otro', 'S', '-');
					$report->set_parametro('5_nivel_idioma_chino', 'S', '-');
					$report->set_parametro('5_nivel_idioma_ingles', 'S', '-');
					$report->set_parametro('5_nivel_idioma_aleman', 'S', '-');
					$report->set_parametro('5_nivel_idioma_frances', 'S', '-');
					$report->set_parametro('5_nivel_idioma_italiano', 'S', '-');
					$report->set_parametro('5_nivel_idioma_portugues', 'S', '-');
					$report->set_parametro('1_como_conocio_institucion', 'S', '-');
					$report->set_parametro('1_insc_otra_institucion_desc', 'S', '-');
					$report->set_parametro('1_insc_otra_institucion', 'S', '-');
					$report->set_parametro('1_mot_otros', 'S', '-');
					$report->set_parametro('1_mot_difusion', 'S', '-');
					$report->set_parametro('1_mot_economico', 'S', '-');
					$report->set_parametro('1_mot_prestigio', 'S', '-');
					$report->set_parametro('1_mot_ubicacion', 'S', '-');
					$report->set_parametro('1_mot_sistema_ingreso', 'S', '-');
					$report->set_parametro('1_mot_recomendacion_amigos', 'S', '-');
					$report->set_parametro('1_mot_recomendacion_estudiantes', 'S', '-');
					$report->set_parametro('1_mot_vocacion', 'S', '-');
					$report->set_parametro('1_mot_otros_propuesta', 'S', '-');
					$report->set_parametro('1_mot_util_sociedad', 'S', '-');
					$report->set_parametro('1_mot_mejora_economica', 'S', '-');
					$report->set_parametro('1_mot_insercion_laboral', 'S', '-');
					$report->set_parametro('1_mot_perfil_profesional', 'S', '-');
					$report->set_parametro('1_mot_sugerencia_familiar', 'S', '-');
					$report->set_parametro('1_mot_reconocimiento_social', 'S', '-');
				}

				$xml = '<tabla><datos>';
				$xml .= '<fila>
                        <dato clave="persona" valor="' . $persona . '"/>
                        <dato clave="propuesta" valor="' . $propuesta . '"/>
                        </fila>';
				$xml .= '</datos></tabla>';

				$report->set_xml(utf8_e_seguro($xml));
				$report->completar_con_datos();
			}
                    }
                // Si se hizo click en "Imprimir"
		} else {
			parent::vista_jasperreports($report);
		}
	}

	function evt__pdf()
	{
		$this->s__imprimir = true;
		$this->s__preinscripcion = static::IMPRIMIR;
	}

	function evt__pdf2()
	{
		$this->s__imprimir = true;
		$this->s__preinscripcion = static::IMPRIMIR_PREINSCRIPCION;
	}

	function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "
                window.open(vinculador.get_url(null,null,'vista_jasperreports', {preinscripcion: {$this->s__preinscripcion}}, [{$this->objeto_js}._id]));
                ";
		}
	}

	function get_descripcion($aguja, $pajar)
	{
		$descripcion = '-';
		foreach ($pajar as $p) {
			if ($p['valor'] == $aguja) {
				$descripcion = $p['descr'];
			}
		}
		return $descripcion;
	}

	function get_si_no($valor)
	{
		if ($valor == NULL) {
			return '-';
		}
		switch ($valor) {
			case comunes::si: return comunes::si_desc;
				break;
			case comunes::no: return comunes::no_desc;
				break;
			default: return '-';
		}
	}

	function adecuar_null($array)
	{
		foreach ($array as $clave => $elemento) {
			if ($elemento == NULL) {
				$array[$clave] = '-';
			}
		}
		return $array;
	}

}