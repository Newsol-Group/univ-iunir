<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Datos Censales
 * 
 */
class co_datos_censales
{
	/**
	 * Obtiene el ID de dato censal correspondiente a una persona
	 * 
	 * @param integer	persona	es el identificador de la persona para la cual se quiere recuperar el último dato censal.
	 */
	function get_id_dato_censal($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT get_ultimo_dato_censal($persona) AS dato_censal;";
		return guarani_db::consultar_fila($sql);
	}

	function get_cabecera_dato_censal($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$sql = "SELECT	mdp_datos_censales.dato_censal,
						mdp_datos_censales.fecha_relevamiento,
						to_char(mdp_datos_censales.fecha_relevamiento,'" . comunes::formato_fecha_visual . "') as fecha_relevamiento_formato_visual, 
						mdp_datos_censales.fecha_actualizacion,
						to_char(mdp_datos_censales.fecha_actualizacion,'" . comunes::formato_fecha_visual . "') as fecha_actualizacion_formato_visual, 
						mdp_datos_censales.persona
				FROM	mdp_datos_censales
				WHERE	mdp_datos_censales.dato_censal = $dato_censal
				;";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Determina si existe un registro de dato censal en la tabla dada
	 * 
	 * @param type $dato_censal	ID de dato censal de la persona
	 * @param type $tabla		Tabla de consulta
	 */
	function existe_registro_dc($dato_censal, $tabla)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$sql = "SELECT	1 as existe
				FROM	$tabla
				WHERE	dato_censal = $dato_censal
				;";

		$datos = guarani_db::consultar_fila($sql);
		return ($datos['existe'] ?? null);
	}

	/**
	 * Obtiene el registro de datos personales asociado a un dato censal dado de una determinada persona.
	 * 	@param integer	dato_censal	es el identificador del dato censal para el cual se quiere recuperar los datos personales.
	 */
	function get_datos_personales($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$sql = "SELECT	mdp_datos_personales.estado_civil,
						mdp_datos_personales.unido_hecho, 
						mdp_datos_personales.situacion_padre,
						mdp_datos_personales.situacion_madre,
						mdp_datos_personales.cantidad_hijos,
						mdp_datos_personales.cantidad_familia, 
						mdp_datos_personales.turno_preferido,
						mdp_datos_personales.cobertura_salud,
						mdp_datos_personales.tipo_vivienda,
						mdp_datos_personales.vive_con, 
						mdp_datos_personales.periodo_lectivo_calle,
						mdp_datos_personales.periodo_lectivo_numero,
						mdp_datos_personales.periodo_lectivo_piso,
						mdp_datos_personales.periodo_lectivo_departamento,
						mdp_datos_personales.periodo_lectivo_unidad,
						mdp_datos_personales.periodo_lectivo_localidad,
						mdp_datos_personales.periodo_lectivo_barrio,
						mdp_datos_personales.periodo_lectivo_codigo_postal,
						mdp_datos_personales.procedencia_calle,
						mdp_datos_personales.procedencia_numero,
						mdp_datos_personales.procedencia_piso,
						mdp_datos_personales.procedencia_departamento,
						mdp_datos_personales.procedencia_unidad,
						mdp_datos_personales.procedencia_localidad,
						mdp_datos_personales.procedencia_barrio,
						mdp_datos_personales.procedencia_codigo_postal,
						mdp_datos_personales.es_celiaco,
						localidad_periodo.nombre as localidad_periodo_nombre,
						provincia_periodo.nombre as provincia_periodo_nombre,
						localidad_procedencia.nombre as localidad_procedencia_nombre,
						provincia_procedencia.nombre as provincia_procedencia_nombre
				FROM	mdp_datos_personales
						LEFT JOIN mug_localidades as localidad_periodo ON localidad_periodo.localidad = mdp_datos_personales.periodo_lectivo_localidad
						LEFT JOIN mug_dptos_partidos as partido_periodo ON localidad_periodo.dpto_partido = partido_periodo.dpto_partido
						LEFT JOIN mug_provincias provincia_periodo ON partido_periodo.provincia = provincia_periodo.provincia
						LEFT JOIN mug_localidades as localidad_procedencia ON localidad_procedencia.localidad = mdp_datos_personales.procedencia_localidad
						LEFT JOIN mug_dptos_partidos as partido_procedencia ON localidad_procedencia.dpto_partido = partido_procedencia.dpto_partido
						LEFT JOIN mug_provincias provincia_procedencia ON partido_procedencia.provincia = provincia_procedencia.provincia
				WHERE	mdp_datos_personales.dato_censal = $dato_censal
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la Porcedencia (pais, provincia, localidad) de los Alumnos declarados en el último dato censal
	 * @param <type> $filtro
	 * @return <type>
	 */
	function get_procedencias($filtro = '')
	{

		$where = '';
		if ($filtro != '') {
			$where .= " AND $filtro";
		}
		$sql = "SELECT	vw_perfil_de_datos_propuestas.propuesta,
						vw_perfil_de_datos_propuestas.codigo,
						vw_perfil_de_datos_propuestas.nombre AS propuesta_nombre,
						'(' || vw_perfil_de_datos_propuestas.codigo || ') ' || vw_perfil_de_datos_propuestas.nombre AS propuesta_datos,
						sga_ubicaciones.ubicacion,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						sga_alumnos.legajo,
						" . guarani_sql::get_case_si_no('sga_alumnos', 'regular', 'regular') . ",
						sga_alumnos_calidad.nombre AS calidad_nombre,
						sga_propuestas_aspira.fecha_inscripcion,
						vw_personas.apellido_nombres AS persona_nombre,
						vw_personas.tipo_nro_documento AS tipo_nro_documento,
						vw_periodos_insc_propuesta.anio_academico,
						to_char(sga_propuestas_aspira.fecha_inscripcion,'" . comunes::formato_fecha_visual . "') AS fecha_inscripcion_desc,
						mdp_datos_censales.dato_censal,
						mdp_datos_personales.procedencia_localidad,
						vw_localidades.localidad,
						vw_localidades.localidad_nombre,
						vw_localidades.dpto_partido_nombre,
						vw_localidades.provincia_nombre,
						vw_localidades.pais,
						vw_localidades.pais_nombre,
						vw_localidades.continente_nombre

				FROM	sga_alumnos
				JOIN	sga_alumnos_calidad ON sga_alumnos_calidad.calidad = sga_alumnos.calidad
				JOIN	sga_propuestas_aspira ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta	= sga_alumnos.propuesta
				JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				JOIN	vw_periodos_insc_propuesta ON vw_periodos_insc_propuesta.periodo_insc = sga_propuestas_aspira.periodo_insc
				JOIN	vw_perfil_de_datos_propuestas ON vw_perfil_de_datos_propuestas.propuesta = sga_propuestas_aspira.propuesta
				JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_alumnos.ubicacion
				JOIN	vw_personas ON vw_personas.persona = sga_alumnos.persona
				LEFT JOIN	mdp_datos_censales ON mdp_datos_censales.dato_censal = (SELECT get_ultimo_dato_censal(vw_personas.persona))
				LEFT JOIN	mdp_datos_personales ON mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal
				LEFT JOIN	vw_localidades ON vw_localidades.localidad = mdp_datos_personales.procedencia_localidad

				WHERE	sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_aceptada . "','" . propuesta::inscripcion_pendiente . "')
				$where
				ORDER BY vw_perfil_de_datos_propuestas.propuesta, sga_ubicaciones.ubicacion, vw_personas.apellido, vw_personas.nombres";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el registro de datos economicos asociado a un dato censal dado de una determinada persona.
	 * 	@param integer	dato_censal	es el identificador del dato censal para el cual se quiere recuperar los datos personales.
	 */
	function get_datos_economicos($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$sql = "SELECT	mdp_datos_economicos.costeo_estudios_familiar,
						mdp_datos_economicos.costeo_estudios_plan_social, 
						mdp_datos_economicos.costeo_estudios_trabajo,
						mdp_datos_economicos.costeo_estudios_beca,
						mdp_datos_economicos.costeo_estudios_otro,
						mdp_datos_economicos.costeo_estudios_descripcion, 
						mdp_datos_economicos.beca,
						mdp_datos_economicos.beca_fuente_universidad,
						mdp_datos_economicos.beca_fuente_nacional,
						mdp_datos_economicos.beca_fuente_provincial, 
						mdp_datos_economicos.beca_fuente_municipal,
						mdp_datos_economicos.beca_fuente_internacional,
						mdp_datos_economicos.beca_fuente_otra,
						mdp_datos_economicos.beca_tipo_economica,
						mdp_datos_economicos.beca_tipo_servicios,
						mdp_datos_economicos.beca_tipo_investigacion,
						mdp_datos_economicos.beca_economica_transporte,
						mdp_datos_economicos.beca_economica_efectivo,
						mdp_datos_economicos.beca_economica_fotocopias,
						mdp_datos_economicos.beca_economica_habitacional,
						mdp_datos_economicos.beca_economica_comedor,
						mdp_datos_economicos.trabajo_existe,
						mdp_datos_economicos.trabajo_hace,
						mdp_datos_economicos.trabajo_familiar,
						mdp_datos_economicos.trabajo_con_descuentos_jubilatorios,
						mdp_datos_economicos.trabajo_recibe_pago,
						mdp_datos_economicos.trabajo_ocupacion,
						mdp_datos_economicos.trabajo_hora_sem,
						mdp_datos_economicos.trabajo_carrera,
						mdp_datos_economicos.trabajo_tarea_descripcion
				FROM	mdp_datos_economicos
				WHERE	mdp_datos_economicos.dato_censal = $dato_censal
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el registro de datos actividades a un dato censal dado de una determinada persona.
	 * @param integer	dato_censal	es el identificador del dato censal para el cual se quiere recuperar los datos personales.
	 */
	function get_datos_actividades($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$sql = "SELECT	mdp_datos_actividades.tecnologia_pc_casa,
						mdp_datos_actividades.tecnologia_pc_trabajo, 
						mdp_datos_actividades.tecnologia_pc_universidad,
						mdp_datos_actividades.tecnologia_pc_otro_lugar,
						mdp_datos_actividades.tecnologia_int_casa,
						mdp_datos_actividades.tecnologia_int_trabajo, 
						mdp_datos_actividades.tecnologia_int_universidad,
						mdp_datos_actividades.tecnologia_int_otro_lugar,
						mdp_datos_actividades.tecnologia_int_movil,
						mdp_datos_actividades.deportes,
						mdp_datos_actividades.deportes_universidad,
						mdp_datos_actividades.deportes_gimnasio,
						mdp_datos_actividades.deportes_particular,
						mdp_datos_actividades.deportes_otro_lugar,
						mdp_datos_actividades.deportes_basquet,
						mdp_datos_actividades.deportes_futbol,
						mdp_datos_actividades.deportes_gimnasia,
						mdp_datos_actividades.deportes_handball,
						mdp_datos_actividades.deportes_natacion,
						mdp_datos_actividades.deportes_tenis,
						mdp_datos_actividades.deportes_voley,
						mdp_datos_actividades.deportes_otros,
						mdp_datos_actividades.nivel_idioma_ingles,
						mdp_datos_actividades.nivel_idioma_aleman,
						mdp_datos_actividades.nivel_idioma_frances,
						mdp_datos_actividades.nivel_idioma_italiano,
						mdp_datos_actividades.nivel_idioma_portugues,
						mdp_datos_actividades.nivel_idioma_chino,
						mdp_datos_actividades.idioma_otro,
						mdp_datos_actividades.nivel_idioma_otro
				FROM	mdp_datos_actividades
				WHERE	mdp_datos_actividades.dato_censal = $dato_censal
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de los estados civiles asociados a los datos personales.
	 */
	function get_estados_civiles()
	{
		$sql = "SELECT		mdp_estados_civiles.estado_civil as valor,
	   						mdp_estados_civiles.descripcion as descr
				FROM 		mdp_estados_civiles
				ORDER BY	mdp_estados_civiles.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las posibles cantidades de hijos asociados a los datos personales.
	 */
	function get_cantidades_hijos()
	{
		$sql = "SELECT		mdp_cantidad_hijos.cantidad_hijos as valor,
	   						mdp_cantidad_hijos.descripcion as descr
				FROM 		mdp_cantidad_hijos
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las posibles cantidades de familiares asociados a los datos personales.
	 */
	function get_cantidades_familia()
	{
		$sql = "SELECT		mdp_cantidad_familia.cantidad_familia as valor,
	   						mdp_cantidad_familia.descripcion as descr
				FROM 		mdp_cantidad_familia
				ORDER BY	mdp_cantidad_familia.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las posibles coberturas de salud asociadas a los datos personales.
	 */
	function get_coberturas_salud()
	{
		$sql = "SELECT		mdp_cobertura_salud.cobertura_salud as valor,
	   						mdp_cobertura_salud.descripcion as descr
				FROM 		mdp_cobertura_salud
				ORDER BY	mdp_cobertura_salud.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de los posibles tipos de viviendas asociados a los datos personales.
	 */
	function get_tipos_viviendas()
	{
		$sql = "SELECT		mdp_tipo_vivienda.tipo_vivienda as valor,
	   						mdp_tipo_vivienda.descripcion as descr
				FROM 		mdp_tipo_vivienda
				ORDER BY	mdp_tipo_vivienda.orden
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las distintas posibilidades acerca de con quien vive una persona asociados a los datos personales.
	 */
	function get_vive_con()
	{
		$sql = "SELECT		mdp_vive_con.vive_con as valor,
	   						mdp_vive_con.descripcion as descr
				FROM 		mdp_vive_con
				ORDER BY	mdp_vive_con.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las posibles regularidades de acceso a internet asociadas a los datos personales.
	 */
	function get_regularidades_internet()
	{
		$sql = "SELECT		mdp_int_regularidad.tecnologia_int_regularidad as valor,
	   						mdp_int_regularidad.descripcion as descr
				FROM 		mdp_int_regularidad
				ORDER BY	mdp_int_regularidad.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de las posibles regularidades de acceso a internet asociadas a los datos personales.
	 */
	function get_niveles_idioma()
	{
		$sql = "SELECT		mdp_nivel_idioma.nivel_idioma as valor,
	   						mdp_nivel_idioma.descripcion as descr
				FROM 		mdp_nivel_idioma
				ORDER BY	mdp_nivel_idioma.descripcion
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene datos de los pueblos originarios.
	 */
	function get_datos_pueblo_originario($persona)
	{
		$sql = "SELECT p.pertenece_pueblo_originario, COALESCE(po.nombre, p.pueblo_originario_otro) as nombre_pueblo_originario
				FROM mdp_personas p, mdp_pueblos_originarios po
				WHERE p.pueblo_originario = po.pueblo_originario
				AND p.persona = $persona";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene datos de los estudios.
	 */
	function get_datos_estudios($persona)
	{
		$sql = "SELECT p.pertenece_pueblo_originario, COALESCE(po.nombre, p.pueblo_originario_otro) as nombre_pueblo_originario
				FROM mdp_personas p, mdp_pueblos_originarios po
				WHERE p.pueblo_originario = po.pueblo_originario
				AND p.persona = $persona";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de la elección de la institución 
	 */
	function get_datos_eleccion_institucion($dato_censal)
	{
		$sql = "SELECT *
				FROM mdp_eleccion_institucion
				WHERE dato_censal = $dato_censal";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de la elección de la propuesta 
	 */
	function get_datos_eleccion_propuesta($dato_censal)
	{
		$sql = "SELECT *
				FROM mdp_eleccion_propuesta
				WHERE dato_censal = $dato_censal";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos del colegio secundario
	 */
	function get_datos_colegio_secundario($persona)
	{
		$sql = "SELECT
					mdp_datos_estudios.anio_egreso as anio_egreso,
					sga_colegios_secundarios.nombre as nombre_colegio,
					mdp_datos_estudios.adeuda_materias_secundario as adeuda_materias,
					mdp_titulos.nombre as titulo
				FROM
					mdp_datos_estudios
					LEFT JOIN mdp_titulos ON mdp_datos_estudios.titulo = mdp_titulos.titulo
					LEFT JOIN sga_colegios_secundarios ON mdp_datos_estudios.colegio = sga_colegios_secundarios.colegio
				WHERE
					mdp_datos_estudios.persona = $persona
					AND mdp_datos_estudios.nivel_estudio = 3";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de estudios superiores
	 */
	function get_datos_estudios_superiores($persona)
	{
		$sql = "SELECT	COALESCE(sga_instituciones.nombre_abreviado, mdp_datos_estudios.institucion_otra) as lugar,
						COALESCE(mdp_titulos.nombre, mdp_datos_estudios.titulo_otro) as propuesta,
						--'tipo' as tipo,
						--'áreas' as areas,
						mdp_datos_estudios.nivel_estudio as nivel,
						mdp_datos_estudios.estado as estado,
						to_char(mdp_datos_estudios.fecha_egreso,'DD/MM/YYYY') as anio_egreso,
						COALESCE(mdp_datos_estudios.razon_abandono_otros, mdp_estudio_abandono.nombre) as razon_abandono
				FROM	mdp_datos_estudios
						LEFT JOIN mdp_titulos 				ON mdp_datos_estudios.titulo 				= mdp_titulos.titulo
						LEFT JOIN mdp_titulos_tipos			ON mdp_titulos_tipos.titulo_tipo			= mdp_titulos.titulo_tipo
						LEFT JOIN sga_instituciones			ON mdp_datos_estudios.institucion 			= sga_instituciones.institucion
						LEFT JOIN sga_colegios_secundarios 	ON mdp_datos_estudios.colegio 				= sga_colegios_secundarios.colegio
						LEFT JOIN mdp_orientacion_recibida 	ON mdp_datos_estudios.orientacion_recibida 	= mdp_orientacion_recibida.orientacion_recibida
						LEFT JOIN mdp_estudio_abandono ON mdp_datos_estudios.razon_abandono = mdp_estudio_abandono.razon_abandono
				WHERE	mdp_datos_estudios.persona = $persona
					AND	mdp_datos_estudios.nivel_estudio > ".persona::nivel_secundario.
                   " AND mdp_datos_estudios.nivel_estudio != ".persona::nivel_desconoce;
		return guarani_db::consultar($sql);
	}

	//--------------------------------------------------------------------------
	//---- Configuración de campos (interfaz alumno)
	//--------------------------------------------------------------------------

	function get_datos_config($indice_inicial = null, $indice_final = null)
	{
		if ((!is_null($indice_inicial)) && (!is_null($indice_final))) {
			$where = " WHERE orden BETWEEN $indice_inicial AND $indice_final";
		} else {
			$where = '';
		}

		$sql = "SELECT	tabla,
						campo,
						etiqueta,
						orden,
						visible,
						obligatorio
				FROM	mdp_datos_censales_config
				$where
				ORDER BY orden
				";

		return guarani_db::consultar($sql);
	}
    
    function get_tablas_datos_config()
    {
        $sql = "
                SELECT 
                    tabla,
                    campo,
                    etiqueta
                FROM 
                    mdp_datos_censales_config
                WHERE 
                    visible = '".comunes::si."'
                ORDER BY 
                    orden
                ";
        return guarani_db::consultar($sql);
    }
	
	function get_datos_censales_obligatorios_by_habilitacion($habilitacion)
	{
		$habilitacion	= toba::db()->quote($habilitacion);
		$si				= toba::db()->quote(comunes::si);

		$sql = "SELECT tabla, campo 
				FROM gde_datos_censales_config 
				WHERE obligatorio	= $si
				AND habilitacion	= $habilitacion
				ORDER BY tabla, campo
				";

		return guarani_db::consultar($sql);
	}

	function get_datos_censales_dependientes($campos_padre)
	{
		// Campos dependientes de otros
		$where_padre = guarani_sql::armar_condicion_in($campos_padre, 'mdp_datos_censales_dep', 'campo_padre');
		$sql = "SELECT	mdp_datos_censales_dep.campo_hijo as campo
				FROM	mdp_datos_censales_dep
				JOIN	mdp_datos_censales_config ON (mdp_datos_censales_dep.tabla_hijo = mdp_datos_censales_config.tabla AND mdp_datos_censales_dep.campo_hijo = mdp_datos_censales_config.campo)
				WHERE	$where_padre
		";
		return guarani_db::consultar($sql);
	}

   /**
    * Retorna las etiquetas de datos censales filtradas por campos.
    *
    * @param int[] $campos
    * @return string[] Etiquetas de datos censales
    */
    function get_etiquetas_datos_censales($campos = [])
    {
        $where_campos = "";
        // Si se filtra por campos
        if (!empty($campos)) {
            $where_campos = 'AND ' . guarani_sql::armar_condicion_in($campos, 'mdp_datos_censales_config', 'campo');
        }
        
        $sql = "SELECT etiqueta
                FROM mdp_datos_censales_config
                WHERE true
                $where_campos
                ORDER BY orden";

        return guarani_db::consultar($sql);
    }
    
    //--------------------------------------------------------------------------
	//---- Ticket #31329
	//--------------------------------------------------------------------------
    function get_datos_discapacidad($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);
		$sql = "SELECT	mdp_datos_discapacidad.condicion_discapacidad,
						mdp_datos_discapacidad.tiene_cud, 
						mdp_datos_discapacidad.disc_auditiva,
						mdp_datos_discapacidad.aud_tipo,
						mdp_datos_discapacidad.aud_lenguaje_senias,
						mdp_datos_discapacidad.aud_lenguaje_labial, 
						mdp_datos_discapacidad.aud_comunicacion_otra,
						mdp_datos_discapacidad.aud_apoyo_vida_diaria,
						mdp_datos_discapacidad.aud_req_interprete_lengua_senias,
						mdp_datos_discapacidad.aud_req_aro_magnetico,
						mdp_datos_discapacidad.aud_req_otros_apoyos,
						mdp_datos_discapacidad.aud_otros_apoyos,
						mdp_datos_discapacidad.disc_visual,
						mdp_datos_discapacidad.vis_dificultad_para_ver,
						mdp_datos_discapacidad.vis_apoyo_vida_diaria,
						mdp_datos_discapacidad.vis_req_archivo_audio,
						mdp_datos_discapacidad.vis_req_texto_digital,
						mdp_datos_discapacidad.vis_req_texto_braile,
						mdp_datos_discapacidad.vis_req_otros_apoyos,
						mdp_datos_discapacidad.vis_otros_apoyos,
						mdp_datos_discapacidad.disc_motora,
						mdp_datos_discapacidad.mot_apoyo_vida_diaria,
						mdp_datos_discapacidad.mot_req_apoyo_aula,
						mdp_datos_discapacidad.mot_apoyo_aula,
                        mdp_datos_discapacidad.mot_req_otros_apoyos,
                        mdp_datos_discapacidad.mot_otros_apoyos,
                        mdp_datos_discapacidad.disc_cond_psicosocial,
                        mdp_datos_discapacidad.psi_descripcion,
                        mdp_datos_discapacidad.psi_apoyo_vida_diaria,
                        mdp_datos_discapacidad.psi_req_otros_apoyos,
                        mdp_datos_discapacidad.psi_otros_apoyos,
                        mdp_datos_discapacidad.disc_otra,
                        mdp_datos_discapacidad.otra_descripcion,
                        mdp_datos_discapacidad.otra_req_apoyo_vida_diaria,
                        mdp_datos_discapacidad.otra_apoyo_vida_diaria,
                        mdp_datos_discapacidad.informacion_importante
				FROM	mdp_datos_discapacidad
				WHERE	mdp_datos_discapacidad.dato_censal = $dato_censal
				";

		return guarani_db::consultar($sql);
	}

}