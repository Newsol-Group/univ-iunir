<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelDb;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;

class datos_censales
{
	
	/**
	 * 
	 * parametros: dato_censal
	 * cache: memoria
	 * filas: n
	 */
	function controlar_tablas($parametros)
	{
		$dato_censal = $parametros['dato_censal'];
		
		if (!$this->existe_dato_en_tabla('mdp_datos_personales', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_datos_personales', $dato_censal);
		}
		if (!$this->existe_dato_en_tabla('mdp_datos_economicos', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_datos_economicos', $dato_censal);
		}
		if (!$this->existe_dato_en_tabla('mdp_datos_actividades', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_datos_actividades', $dato_censal);
		}
		if (!$this->existe_dato_en_tabla('mdp_eleccion_institucion', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_eleccion_institucion', $dato_censal);
		}
		if (!$this->existe_dato_en_tabla('mdp_eleccion_propuesta', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_eleccion_propuesta', $dato_censal);
		}
		if (!$this->existe_dato_en_tabla('mdp_datos_discapacidad', $dato_censal)) {
			$this->insertar_dato_en_tabla('mdp_datos_discapacidad', $dato_censal);
		}
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: no
	 * filas: 1
	 */
	function insertar_dato_censal($parametros)
	{
		$persona = $parametros['persona'];
		$fecha_hoy = kernel::db()->quote(\guarani_fecha::get_hoy());
		
		$sql = "INSERT INTO mdp_datos_censales
				(
					persona,
					fecha_relevamiento,
					fecha_actualizacion
				) 
				VALUES 
				(
					$persona,
					$fecha_hoy,
					$fecha_hoy
				)";
		
		kernel::db()->ejecutar($sql);
		
		$sql_seq = "SELECT currval('mdp_datos_censales_seq') as seq;";
		$datos_seq = kernel::db()->consultar_fila($sql_seq);
		return $datos_seq['seq'];
	}
	
	protected function existe_dato_en_tabla($tabla, $dato_censal)
	{
		$sql = "SELECT 	1
				FROM 	$tabla
				WHERE	dato_censal = $dato_censal
				";
		
		$datos = kernel::db()->consultar_fila($sql);
		return \guarani_arreglos::count($datos) > 0;
	}
	
	protected function insertar_dato_en_tabla($tabla, $dato_censal)
	{
		$sql = "INSERT 
				INTO	$tabla (dato_censal) 
				VALUES	($dato_censal); 
				";
		kernel::db()->ejecutar($sql);
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: 1
	 */
	function get_ultima_actualizacion_dc($parametros)
	{
		$persona = $parametros['persona'];
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT	to_char(mdp_datos_censales.fecha_actualizacion, $formato_fecha_visual) as fecha_actualizacion
				FROM	mdp_datos_censales
				WHERE	mdp_datos_censales.persona = $persona
				";
		
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['fecha_actualizacion'];
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: 1
	 */
	function get_datos_censales($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_personas.identidad_genero_otro,
					mdp_personas.identidad_genero,
					mdp_personas.apellido_elegido,
					mdp_personas.nombres_elegido,
					mdp_personas.nombres,
					mdp_personas.apellido,
					mdp_personas.sexo,
                                        
                                        mdp_personas.pertenece_pueblo_originario,
					mdp_personas.pueblo_originario,
					mdp_personas.pueblo_originario_otro,

					mdp_datos_personales.estado_civil,
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
					
					mdp_datos_actividades.tecnologia_pc_casa,
					mdp_datos_actividades.tecnologia_pc_trabajo,
					mdp_datos_actividades.tecnologia_pc_universidad,
					mdp_datos_actividades.tecnologia_pc_otro_lugar,
					mdp_datos_actividades.tecnologia_int_casa,
					mdp_datos_actividades.tecnologia_int_trabajo,
					mdp_datos_actividades.tecnologia_int_universidad,
					mdp_datos_actividades.tecnologia_int_otro_lugar,
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
					mdp_datos_actividades.nivel_idioma_otro,
					mdp_datos_actividades.tecnologia_int_movil,

					mdp_datos_economicos.costeo_estudios_familiar,
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
					mdp_datos_economicos.trabajo_tarea_descripcion,
					
					CASE WHEN localidad_per_lect.localidad = '1' -- CABA
						THEN localidad_per_lect.nombre || ', ' || pais_per_lect.nombre 
						ELSE localidad_per_lect.nombre || ', ' || provincia_per_lect.nombre || ', ' || pais_per_lect.nombre 
					END AS periodo_lectivo_localidad_descr,
					
					CASE WHEN localidad_proc.localidad = '1' -- CABA
						THEN localidad_proc.nombre || ', ' || pais_proc.nombre 
						ELSE localidad_proc.nombre || ', ' || provincia_proc.nombre || ', ' || pais_proc.nombre 
					END AS procedencia_localidad_descr,
					
					mdp_eleccion_institucion.mot_economico,
					mdp_eleccion_institucion.mot_prestigio,
					mdp_eleccion_institucion.mot_difusion,
					mdp_eleccion_institucion.mot_recomendacion_estudiantes,
					mdp_eleccion_institucion.mot_recomendacion_amigos,
					mdp_eleccion_institucion.mot_sistema_ingreso,
					mdp_eleccion_institucion.mot_ubicacion,
					mdp_eleccion_institucion.mot_otros_i,
					mdp_eleccion_institucion.insc_otra_institucion,
					mdp_eleccion_institucion.insc_otra_institucion_desc,
					mdp_eleccion_institucion.como_conocio_institucion,
					
					mdp_eleccion_propuesta.mot_vocacion,
					mdp_eleccion_propuesta.mot_sugerencia_familiar,
					mdp_eleccion_propuesta.mot_reconocimiento_social,
					mdp_eleccion_propuesta.mot_insercion_laboral,
					mdp_eleccion_propuesta.mot_perfil_profesional,
					mdp_eleccion_propuesta.mot_mejora_economica,
					mdp_eleccion_propuesta.mot_util_sociedad,
					mdp_eleccion_propuesta.mot_otros_p,
					
					mdp_datos_discapacidad.condicion_discapacidad,
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
					
				FROM 
					mdp_datos_censales
					JOIN mdp_personas ON mdp_personas.persona = mdp_datos_censales.persona
					JOIN mdp_eleccion_institucion ON mdp_eleccion_institucion.dato_censal = mdp_datos_censales.dato_censal
					JOIN mdp_eleccion_propuesta ON mdp_eleccion_propuesta.dato_censal = mdp_datos_censales.dato_censal
					JOIN mdp_datos_actividades ON mdp_datos_actividades.dato_censal	= mdp_datos_censales.dato_censal
					JOIN mdp_datos_economicos ON mdp_datos_economicos.dato_censal = mdp_datos_censales.dato_censal
					JOIN mdp_datos_personales ON mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal
					JOIN mdp_datos_discapacidad ON (mdp_datos_discapacidad.dato_censal = mdp_datos_censales.dato_censal)
					-- Localidad per\EDodo lectivo -------------------------------
					LEFT JOIN mug_localidades AS localidad_per_lect	ON mdp_datos_personales.periodo_lectivo_localidad = localidad_per_lect.localidad
					LEFT JOIN mug_dptos_partidos AS partido_per_lect ON localidad_per_lect.dpto_partido = partido_per_lect.dpto_partido
					LEFT JOIN mug_provincias AS provincia_per_lect ON provincia_per_lect.provincia = partido_per_lect.provincia
					LEFT JOIN mug_paises AS pais_per_lect ON pais_per_lect.pais = provincia_per_lect.pais
					-- Localidad de procedencia --------------------------------
					LEFT JOIN mug_localidades AS localidad_proc ON mdp_datos_personales.procedencia_localidad = localidad_proc.localidad
					LEFT JOIN mug_dptos_partidos AS partido_proc ON localidad_proc.dpto_partido = partido_proc.dpto_partido
					LEFT JOIN mug_provincias AS provincia_proc ON provincia_proc.provincia = partido_proc.provincia
					LEFT JOIN mug_paises AS pais_proc ON pais_proc.pais = provincia_proc.pais
					
				WHERE
					mdp_datos_censales.persona = $persona
				";
		
		return kernel::db()->consultar_fila($sql);
	}
	
	/**
	 * 
	 * parametros: dato_censal
	 * cache: no
	 * filas: n
	 */
	function actualizar_fecha_modificacion($parametros)
	{
		$sql = "UPDATE	mdp_datos_censales 
				SET		fecha_actualizacion = ".\guarani_sql::fecha_actual ."
				WHERE	dato_censal = {$parametros['dato_censal']}
				";
		kernel::db()->ejecutar($sql);
	}
	
	/**
	 * 
	 * parametros: persona, fecha, cant_meses
	 * no_quote: cant_meses
	 * cache: memoria
	 * filas: 1
	 */
	function tiene_datos_censales_actualizados($parametros)
	{
		$persona = $parametros['persona'];
		$fecha = $parametros['fecha'];
		$cant_meses = $parametros['cant_meses'];
		$sql = "SELECT  COUNT(*) AS cant
				FROM 	mdp_datos_censales
				WHERE	mdp_datos_censales.persona = $persona
				AND     mdp_datos_censales.fecha_actualizacion >= ($fecha::date - interval '$cant_meses month')::date
		";
		$rs = kernel::db()->consultar_fila($sql);
		return $rs['cant'] > 0;
	}
	
	//--------------------------------------------------------------------------
	//---- Antecedentes --------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_antecedentes_docentes($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_antecedentes_docentes.antecedente_docente,
					mdp_antecedentes_docentes.institucion,
					mdp_antecedentes_docentes.institucion_otra,
					COALESCE (sga_instituciones.nombre_abreviado, mdp_antecedentes_docentes.institucion_otra) AS institucion_descr,
					mdp_antecedentes_docentes.colegio,
					mdp_antecedentes_docentes.colegio_otro,
					mdp_antecedentes_docentes.cargo,
					mdp_antecedentes_docentes.materia,
					mdp_antecedentes_docentes.trabajo_hora_sem,
					mdp_antecedentes_docentes.fecha_desde,
					mdp_antecedentes_docentes.fecha_hasta
				FROM 
					mdp_antecedentes_docentes
					LEFT JOIN sga_instituciones ON	sga_instituciones.institucion = mdp_antecedentes_docentes.institucion
				WHERE	
					mdp_antecedentes_docentes.persona = $persona
				ORDER BY
					mdp_antecedentes_docentes.fecha_desde DESC
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['antecedente_docente']);
			$dato['fecha_desde_v'] = convertir_fecha_formato_frontend($dato['fecha_desde']);
			$dato['fecha_hasta_v'] = (!empty($dato['fecha_hasta'])) ? convertir_fecha_formato_frontend($dato['fecha_hasta']) : '--';
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_antecedentes_profesionales($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_antecedentes_profesionales.antecedente_profesional,
					mdp_antecedentes_profesionales.persona,
					mdp_antecedentes_profesionales.empresa,
					mdp_antecedentes_profesionales.cargo,
					mdp_antecedentes_profesionales.area,
					mdp_antecedentes_profesionales.trabajo_hora_sem,
					mdp_antecedentes_profesionales.fecha_desde,
					mdp_antecedentes_profesionales.fecha_hasta
				FROM 
					mdp_antecedentes_profesionales
				WHERE	
					mdp_antecedentes_profesionales.persona = $persona
				ORDER BY
					mdp_antecedentes_profesionales.fecha_desde DESC
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['antecedente_profesional']);
			$dato['fecha_desde_v'] = convertir_fecha_formato_frontend($dato['fecha_desde']);
			$dato['fecha_hasta_v'] = (!empty($dato['fecha_hasta'])) ? convertir_fecha_formato_frontend($dato['fecha_hasta']) : '--';
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_datos_contactos($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_personas_contactos.persona_contacto,
					mdp_personas_contactos.persona,
					mdp_personas_contactos.contacto_tipo,
					mdp_contactos_tipo.descripcion AS contacto_tipo_descr,
					mdp_personas_contactos.otros_contactos,
					mdp_personas_contactos.email,
					mdp_personas_contactos.telefono_codigo_area,
					mdp_personas_contactos.telefono_numero,
					-- El mail principal se lista primero
					CASE 
						WHEN mdp_personas_contactos.contacto_tipo = '".\persona::mail_primario."' THEN  1
						ELSE 2
					END AS orden
				FROM 
					mdp_personas_contactos,
					mdp_contactos_tipo
				WHERE
					mdp_personas_contactos.persona = $persona
					AND mdp_contactos_tipo.contacto_tipo = mdp_personas_contactos.contacto_tipo
				ORDER BY 
					orden,
					mdp_personas_contactos.contacto_tipo
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['persona_contacto']);
			
			$tipo_contacto = $dato['contacto_tipo'];
            $descripcion = '';
            if (\persona::es_contacto_telefonico($tipo_contacto)) {
                $descripcion = (!empty($dato['telefono_codigo_area'])) ? '(' . $dato['telefono_codigo_area'] . ') ' : '';
				$descripcion .= $dato['telefono_numero'];
            } elseif (\persona::es_contacto_mail($tipo_contacto)) {
                $descripcion = $dato['email'];
            } elseif (($tipo_contacto == \persona::otro_contacto) ||($tipo_contacto == \persona::facebook)  ||($tipo_contacto == \persona::instagram)||($tipo_contacto == \persona::twitter)||($tipo_contacto == \persona::linkedin)      ) {
                $descripcion = $dato['otros_contactos'];
            }
            $dato['contacto_descripcion'] = $descripcion;
			
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_allegados($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_personas_allegados.dato_allegado,
					mdp_datos_censales.persona,
					mdp_personas_allegados.tipo_allegado,
					mdp_tipo_allegado.descripcion AS tipo_allegado_descr,
					mdp_personas_allegados.apellido,
					mdp_personas_allegados.nombres,
					mdp_personas_allegados.apellido || ', ' ||	mdp_personas_allegados.nombres AS apellido_y_nombres,
					mdp_personas_allegados.calle,
					mdp_personas_allegados.numero,
					mdp_personas_allegados.piso,
					mdp_personas_allegados.departamento,
					mdp_personas_allegados.unidad,
					mdp_personas_allegados.localidad,
					mdp_personas_allegados.barrio,
					mdp_personas_allegados.codigo_postal,
					mdp_personas_allegados.telefono,
					mdp_personas_allegados.estudio_alcanzado,
					mdp_personas_allegados.trabajo_existe,
					mdp_personas_allegados.trabajo_hace,
					mdp_personas_allegados.trabajo_ocupacion,
					mdp_personas_allegados.trabajo_tarea,
					mdp_personas_allegados.trabajo_otro,
					CASE WHEN mug_localidades.localidad = '1' -- CABA
						THEN mug_localidades.nombre || ', ' || mug_paises.nombre 
						ELSE mug_localidades.nombre || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre 
					END AS localidad_descr
				FROM 
					mdp_personas_allegados
					JOIN mdp_datos_censales ON mdp_personas_allegados.dato_censal = mdp_datos_censales.dato_censal and mdp_datos_censales.persona = $persona
					JOIN mdp_tipo_allegado ON mdp_tipo_allegado.tipo_allegado = mdp_personas_allegados.tipo_allegado
					LEFT JOIN mug_localidades ON mug_localidades.localidad = mdp_personas_allegados.localidad
					LEFT JOIN mug_dptos_partidos ON mug_dptos_partidos.dpto_partido = mug_localidades.dpto_partido
					LEFT JOIN mug_provincias ON mug_provincias.provincia = mug_dptos_partidos.provincia
					LEFT JOIN mug_paises ON mug_paises.pais = mug_provincias.pais
				WHERE 
					mdp_datos_censales.persona = $persona
				ORDER BY
					mdp_personas_allegados.apellido,
					mdp_personas_allegados.nombres
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['dato_allegado']);
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	/**
	 * 
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_datos_estudios($parametros)
	{
		$persona = $parametros['persona'];
		
		$sql = "SELECT 
					mdp_datos_estudios.dato_estudio,
					mdp_datos_estudios.persona,
					mdp_datos_estudios.nivel_estudio,
					mdp_nivel_estudio.descripcion AS nivel_estudio_descr,
					-- Instituci\F3n 
					mdp_datos_estudios.institucion,
					sga_instituciones.nombre AS institucion_nombre,
					mdp_datos_estudios.institucion_otra,
					COALESCE (sga_instituciones.nombre, mdp_datos_estudios.institucion_otra) AS institucion_descr,
					-- Colegio
					mdp_datos_estudios.colegio,
					sga_colegios_secundarios.nombre AS colegio_nombre,
					mdp_datos_estudios.colegio_otro,
					mdp_datos_estudios.adeuda_materias_secundario,
					COALESCE (sga_colegios_secundarios.nombre, mdp_datos_estudios.colegio_otro) AS colegio_descr,
					COALESCE (sga_instituciones.nombre, mdp_datos_estudios.institucion_otra, sga_colegios_secundarios.nombre, mdp_datos_estudios.colegio_otro) AS institucion_colegio_descr,
					-- T\EDtulo
					mdp_datos_estudios.titulo,
					mdp_titulos.nombre AS titulo_nombre,
					mdp_datos_estudios.titulo_otro,
					COALESCE (mdp_titulos.nombre, mdp_datos_estudios.titulo_otro) AS titulo_descr,
					mdp_datos_estudios.fecha_ingreso,
					mdp_datos_estudios.fecha_egreso,
					mdp_datos_estudios.anio_ingreso,
					mdp_datos_estudios.anio_egreso,
					mdp_datos_estudios.orientacion_recibida,
					mdp_datos_estudios.titulo_tesis,
					mdp_datos_estudios.director_tesis,
					mdp_datos_estudios.estado,
					mdp_datos_estudios.razon_abandono,
					mdp_datos_estudios.razon_abandono_otros
				FROM 
					mdp_datos_estudios 
					JOIN mdp_nivel_estudio ON mdp_nivel_estudio.nivel_estudio = mdp_datos_estudios.nivel_estudio
					LEFT JOIN sga_colegios_secundarios ON sga_colegios_secundarios.colegio = mdp_datos_estudios.colegio
					LEFT JOIN sga_instituciones ON sga_instituciones.institucion = mdp_datos_estudios.institucion
					LEFT JOIN mdp_titulos ON mdp_titulos.titulo = mdp_datos_estudios.titulo
				WHERE
					mdp_datos_estudios.persona = $persona
				ORDER BY
					mdp_datos_estudios.nivel_estudio
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['dato_estudio']);
			
			if (!empty($dato['fecha_ingreso'])) {
				$dato['fecha_ingreso'] = convertir_fecha_formato_frontend($dato['fecha_ingreso']);	
			}
			if (!empty($dato['fecha_egreso'])) {
				$dato['fecha_egreso'] = convertir_fecha_formato_frontend($dato['fecha_egreso']);	
			}
			$dato['institucion_colegio_descr'] = capitalizar($dato['institucion_colegio_descr']);
			$dato['titulo_descr'] = capitalizar($dato['titulo_descr']);
			
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	//--------------------------------------------------------------------------
	//---- Selecci\F3n de localidad ----------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * 
	 * parametros: localidad
	 * cache: memoria
	 * filas: 1
	 */
	function get_descr_localidad($parametros)
	{
		$localidad = $parametros['localidad'];
		
		$sql = "SELECT 
					CASE WHEN mug_localidades.localidad = '1' -- CABA
						THEN mug_localidades.nombre || ', ' || mug_paises.nombre 
						ELSE mug_localidades.nombre || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre 
					END AS localidad_descr
				FROM 	
					mug_localidades,
					mug_dptos_partidos,
					mug_provincias,
					mug_paises
				WHERE
					mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
					AND mug_dptos_partidos.provincia = mug_provincias.provincia
					AND mug_provincias.pais = mug_paises.pais
					AND mug_localidades.localidad = $localidad
				";
		
		$datos = kernel::db()->consultar_fila($sql);
		$datos['localidad_descr'] = capitalizar($datos['localidad_descr']);
		return $datos;
	}
	
	/**
	 * parametros: localidad_nombre, pais
	 * param_null: localidad_nombre, pais
	 * no_quote: localidad_nombre, pais
	 * param_tipo: string, int
	 * cache: memoria
	 * filas: n
	 */
	function get_localidades_autocomplete($parametros)
	{
		$localidad_nombre = $parametros['localidad_nombre'];
		$pais = $parametros['pais'];
		
		$where_localidad = '';
		
		if (!empty($localidad_nombre)) {
			$localidad_nombre = kernel::db()->quote_like($localidad_nombre, '%', '%');
			$where_localidad .= " AND f_limpiar_acentos(mug_localidades.nombre) ILIKE $localidad_nombre";
		}
		
		if (!empty($pais)) {
			$pais = kernel::db()->quote($pais);
			$where_localidad .= " AND mug_paises.pais = $pais";
		}
		
		$sql = "SELECT 	
					mug_localidades.localidad,
					CASE WHEN mug_localidades.localidad = '1' -- CABA
						THEN mug_localidades.nombre || ', ' || mug_paises.nombre 
						ELSE mug_localidades.nombre || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre 
					END AS localidad_descr
				FROM 	
					mug_localidades,
					mug_dptos_partidos,
					mug_provincias,
					mug_paises
				WHERE
					mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
					AND mug_dptos_partidos.provincia = mug_provincias.provincia
					AND mug_provincias.pais = mug_paises.pais
					$where_localidad
				ORDER BY 
					lower(mug_localidades.nombre),
					lower(mug_provincias.nombre)
				";
		
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: nombre
	 * param_null: nombre
	 * no_quote: nombre
	 * param_tipo: string
	 * cache: memoria
	 * filas: n
	 */
	function get_instituciones_autocomplete($parametros)
	{
		$nombre = $parametros['nombre'];
		$where = '';
		
		if (!empty($nombre)) {
			$nombre = kernel::db()->quote_like($nombre, '%', '%');
			$where = " WHERE f_limpiar_acentos(sga_instituciones.nombre) ILIKE $nombre";
		}
		
		$sql = "SELECT 
					sga_instituciones.institucion as valor,
					sga_instituciones.nombre as descr
				FROM 
					sga_instituciones
				$where
				ORDER BY 
					lower(sga_instituciones.nombre)
				";
		
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: nombre
	 * param_null: nombre
	 * no_quote: nombre
	 * param_tipo: string
	 * cache: memoria
	 * filas: n
	 */
	function get_colegios_autocomplete($parametros)
	{
		$nombre = $parametros['nombre'];
		$where = '';
		
		if (!empty($nombre)) {
			$nombre = kernel::db()->quote_like($nombre, '%', '%');
			$where = " AND (f_limpiar_acentos(sga_colegios_secundarios.nombre) ILIKE $nombre OR f_limpiar_acentos(mug_localidades.nombre) ILIKE $nombre ) ";
		}
		
		$sql = "SELECT 
				sga_colegios_secundarios.colegio AS valor,
				COALESCE (sga_colegios_secundarios.nombre || ' (' || mug_localidades.nombre || ')', sga_colegios_secundarios.nombre) AS descr
			FROM sga_colegios_secundarios
			LEFT JOIN mug_localidades ON mug_localidades.localidad = sga_colegios_secundarios.localidad
			WHERE nivel = '".\institucion::colegio_nivel_secundario."'
			$where
			ORDER BY lower(sga_colegios_secundarios.nombre)
			";
		
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: nombre, tipo
	 * param_null: nombre, tipo
	 * no_quote: nombre, tipo
	 * param_tipo: string, tipo
	 * cache: memoria
	 * filas: n
	 */
	function get_titulos_autocomplete($parametros)
	{
		$nombre = $parametros['nombre'];
		
		if ($parametros['tipo'] == \persona::nivel_secundario) {
			$where = " WHERE mdp_titulos.titulo_tipo = 'C'";
		} else {
			$where = " WHERE mdp_titulos.titulo_tipo <> 'C'";
		}
		
		if (!empty($nombre)) {
			$nombre = kernel::db()->quote_like($nombre, '%', '%');
			$where .= " AND f_limpiar_acentos(mdp_titulos.nombre) ILIKE $nombre";
		}
		
		$sql = "SELECT 
					mdp_titulos.titulo AS valor,
					mdp_titulos.nombre AS descr
				FROM 
					mdp_titulos
				$where
				ORDER BY 
					lower(mdp_titulos.nombre)
				";
		
		return kernel::db()->consultar($sql);
	}
}
