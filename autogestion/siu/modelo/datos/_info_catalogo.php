<?php
namespace siu\modelo\datos;

class _info_catalogo
{
	static function acta__cantidad_alumnos_instancia()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
    1 => 'instancia',
  ),
  'cache' => 'no',
);
	}

	static function acta__eliminar_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
    1 => 'docentes',
  ),
  'no_quote' => 'docentes',
  'cache' => 'no',
);
	}

	static function acta__eliminar_eval_detalle_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'id_acta',
    2 => 'alumnos',
  ),
  'no_quote' => 'alumnos',
  'cache' => 'no',
);
	}

	static function acta__eliminar_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
    1 => 'id_acta',
    2 => 'instancias',
  ),
  'no_quote' => 'instancias',
  'cache' => 'no',
);
	}

	static function acta__get_actas_llamado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_actividades_actas_fuera_calendario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'actividad',
  ),
  'param_null' => 
  array (
    0 => 'term',
    1 => 'actividad',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_datos_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_datos_acta_alumnos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_datos_acta_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_datos_acta_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__get_evaluacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'memoria',
);
	}

	static function acta__get_instancias_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'instancia',
  ),
  'param_null' => 
  array (
    0 => 'instancia',
  ),
  'cache' => 'memoria',
);
	}

	static function acta__incrementar_version_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__info_link_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'memoria',
);
	}

	static function acta__insertar_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_acta',
    1 => 'evaluacion',
    2 => 'llamado_mesa',
    3 => 'fecha',
    4 => 'instancias',
    5 => 'renglones_folio',
    6 => 'observaciones_acta',
    7 => 'digital',
  ),
  'param_null' => 
  array (
    0 => 'observaciones_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__insertar_acta_()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_acta',
    1 => 'evaluacion',
    2 => 'llamado_mesa',
    3 => 'origen',
    4 => 'tipo_acta',
    5 => 'observaciones',
    6 => 'estado',
    7 => 'renglones_folio',
  ),
  'param_null' => 
  array (
    0 => 'observaciones',
  ),
  'cache' => 'no',
);
	}

	static function acta__insertar_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
    1 => 'docentes',
  ),
  'no_quote' => 'docentes',
  'cache' => 'no',
);
	}

	static function acta__insertar_eval_detalle_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'id_acta',
    2 => 'alumnos',
    3 => 'fecha',
  ),
  'no_quote' => 'alumnos',
  'cache' => 'no',
);
	}

	static function acta__insertar_instancia_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'instancia',
    2 => 'escala_nota',
  ),
  'cache' => 'no',
);
	}

	static function acta__insertar_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
    1 => 'id_acta',
    2 => 'instancias',
  ),
  'no_quote' => 'instancias',
  'cache' => 'no',
);
	}

	static function acta__insertar_instancias_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'mesa_examen',
  ),
  'no_quote' => 'acta',
  'cache' => 'no',
);
	}

	static function acta__modificar_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
    1 => 'docentes',
  ),
  'no_quote' => 'docentes',
  'cache' => 'no',
);
	}

	static function acta__modificar_encabezado_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
    1 => 'llamado_mesa',
    2 => 'id_acta',
    3 => 'ubicacion',
    4 => 'observaciones',
    5 => 'anio_academico',
    6 => 'hora_inicio',
    7 => 'hora_finalizacion',
    8 => 'fecha',
    9 => 'nro_acta',
  ),
  'param_null' => 
  array (
    0 => 'ubicacion',
    1 => 'observaciones',
    2 => 'anio_academico',
    3 => 'hora_inicio',
    4 => 'hora_finalizacion',
    5 => 'fecha',
    6 => 'nro_acta',
  ),
  'cache' => 'no',
);
	}

	static function acta__modificar_encabezado_acta_en_turno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
    1 => 'id_acta',
    2 => 'observaciones',
    3 => 'mesa_examen',
  ),
  'cache' => 'no',
);
	}

	static function acta__modificar_eval_detalle_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'id_acta',
    2 => 'alumnos',
  ),
  'no_quote' => 'alumnos',
  'cache' => 'no',
);
	}

	static function acta__modificar_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
    1 => 'instancias',
  ),
  'no_quote' => 'instancias',
  'cache' => 'no',
);
	}

	static function alumno__actuacion_provisoria_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__actuacion_provisoria_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__actuacion_provisoria_promocion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__actualizar_requisito_aspirante()
	{
		return array (
  'parametros' => 
  array (
    0 => 'datos',
    1 => 'claves',
  ),
  'no_quote' => 
  array (
    0 => 'datos',
    1 => 'claves',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__actualizar_requisito_draft()
	{
		return array (
  'parametros' => 
  array (
    0 => 'datos',
    1 => 'claves',
  ),
  'no_quote' => 
  array (
    0 => 'datos',
    1 => 'claves',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__alumno_en_acta_abierta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'evaluacion',
    2 => 'id_acta',
  ),
  'param_null' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__alumno_en_acta_cerrada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__aulas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__buildTree()
	{
		return array (
);
	}

	static function alumno__buscar_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
  ),
  'cache' => 'no',
  'no_quote' => 'term',
  'filas' => 'n',
);
	}

	static function alumno__comision_inscripto()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__creditos()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'nro_inscripcion',
    2 => 'anio',
    3 => 'periodo_lectivo',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__eliminar_requisito_draft()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__eliminar_sga_requisitos_digitales_draft_detalle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'requisito',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'requisito',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__es_ingresante()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'anio_academico',
  ),
  'param_null' => 
  array (
    0 => 'anio_academico',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__es_ingresante_carrera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'anio_academico',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__es_ingresante_periodos_insc_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__fecha_cierre_requisitos_ingreso_mayor_a_hoy()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__get_actividad_academica()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_actividad_academica_con_creditos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'orden',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'orden',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_alumnos_actas_fuera_calendario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'alumno',
    2 => 'elemento',
    3 => 'evaluacion',
  ),
  'param_null' => 
  array (
    0 => 'term',
    1 => 'alumno',
  ),
  'no_quote' => 'term',
  'cache' => 'memoria',
);
	}

	static function alumno__get_alumnos_activos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'apellido',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__get_alumnos_activos_comisiones_mesas_un_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'docente',
  ),
  'no_quote' => 'term',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__get_cantidad_activos()
	{
		return array (
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__get_certificados_obtenidos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_contenido_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'alumno',
  ),
  'no_quote' => 
  array (
    0 => 'plan_version',
    1 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_correlativas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'elemento',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_creditos_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_datos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__get_datos_basicos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__get_datos_ficha()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__get_datos_parciales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'comision',
  ),
  'no_quote' => 'persona',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_datos_ultima_cursada_actividad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'actividad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__get_detalle_creditos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_tramite',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_en_curso()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_equiv()
	{
		return array (
  'parametros' => 
  array (
    0 => 'equivalencia',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'id_acta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_plan_estudios()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_detalle_promo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_division()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__get_inasistencias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__get_materias_genericas_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_optativas_generica()
	{
		return array (
  'parametros' => 
  array (
    0 => 'actividad_generica',
    1 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_porcentaje_avance_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'tipo_titulo',
    2 => 'solo_promediables',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'solo_promediables',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__get_promedios_por_titulo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_requisitos_ingreso_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'estado_req_digital',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'estado_req_digital',
  ),
  'param_null' => 
  array (
    0 => 'estado_req_digital',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__get_resultado_generica()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'carrera',
    2 => 'legajo',
    3 => 'titulo',
    4 => 'materia',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function alumno__get_titulos_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__guardar_requisito_draft()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__inscripciones_cursadas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'estado',
    2 => 'vigentes',
    3 => 'cursos',
  ),
  'no_quote' => 'cursos',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__inscripciones_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'estado',
    2 => 'vigentes',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__inscripciones_subcomisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__obtener_elemento_raiz()
	{
		return array (
);
	}

	static function alumno__periodos_lectivos()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__regularidades()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__temas_dictados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__tesis()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__tesis_datos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'tesis',
    1 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__tesis_estados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'tesis',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function alumno__titulo_cambios_de_estado()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_solicitud',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function alumno__titulo_datos_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_solicitud',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__titulo_solicitudes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'propuesta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function archivos__eliminar_archivo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_archivo',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function archivos__insertar_archivo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'path',
    1 => 'nombre',
    2 => 'tamanio',
    3 => 'extension',
    4 => 'tmp_name',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function atributos_params__get_propuestas_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function atributos_params__get_propuestas_elemento()
	{
		return array (
  'parametros' => 
  array (
    0 => 'elemento',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function atributos_params__get_propuestas_llamado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function atributos_params__get_propuestas_mesa_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesa_examen',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function atributos_params__get_propuestas_ras()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuestas',
  ),
  'no_quote' => 'propuestas',
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function becas__actualizar_datos_academicos_aspirante_becas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function becas__eliminar_requisito_aspirante()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function becas__get_aspirante_solicitudes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'cuil',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'cuil',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_convocatoria_impresion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'convocatoria',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'convocatoria',
    1 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_convocatorias_aplicadas_by_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_convocatorias_by_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'vigentes',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_convocatorias_vigentes_by_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_datos_academicos_formulario_by_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'formulario',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'formulario',
    1 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_datos_reapertura()
	{
		return array (
  'parametros' => 
  array (
    0 => 'reapertura',
  ),
  'no_quote' => 'reapertura',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_info_habilitacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
  ),
  'no_quote' => 'habilitacion',
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function becas__get_requisitos_convocatoria_by_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function becas__get_requisitos_habilitacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
  ),
  'no_quote' => 'habilitacion',
  'cache' => 'memoria',
);
	}

	static function becas__get_tipo_documento_digital()
	{
		return array (
  'parametros' => 
  array (
    0 => 'requisito',
  ),
  'no_quote' => 'requisito',
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function becas__insertar_requisitos_convocatoria()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function becas__marcar_requisitos_convocatoria_no_rechazados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function becas__update_extension_requisitos_convocatoria()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
    3 => 'extension',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function becas__upsert_requisito_aspirante()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
    3 => 'id_documento',
    4 => 'extension',
    5 => 'no_posee',
  ),
  'no_quote' => 
  array (
    0 => 'habilitacion',
    1 => 'requisito',
    2 => 'persona',
    3 => 'id_documento',
    4 => 'extension',
    5 => 'no_posee',
  ),
  'param_null' => 
  array (
    0 => 'id_documento',
    1 => 'extension',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function boleto_estudiantil__cumple_condicion_be()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function boleto_estudiantil__datos_basicos_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function boleto_estudiantil__insertar_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'hash',
    1 => 'url_formulario_sube',
    2 => 'persona',
    3 => 'condicion',
    4 => 'observaciones',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function boleto_estudiantil__listado_solicitudes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function boleto_estudiantil__registrar_intentos()
	{
		return array (
);
	}

	static function boleto_estudiantil__tiene_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function boleto_estudiantil__ultima_solicitud_pendiente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function calendario_academico__get_anios_academicos_con_periodos_lectivos_vigentes()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_asistencias__clase_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'clase',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_asistencias__clase_detalle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'clase',
    2 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_asistencias__get_planilla()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
    2 => 'fecha_desde',
    3 => 'cantidad_clases',
    4 => 'imprime_alumnos_libres',
    5 => 'tipo_clase',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
    1 => 'tipo_clase',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_asistencias__get_tipos_clase()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_asistencias__get_tipos_clase_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_asistencias__guardar()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'clase',
    2 => 'cantidad',
    3 => 'motivo',
  ),
  'param_null' => 
  array (
    0 => 'motivo',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_asistencias__tiene_banda_horaria_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function carga_asistencias__tiene_banda_horaria_subcomision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'subcomision',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function carga_asistencias__tipo_asistencia()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function carga_evaluaciones_parciales__agregar_alumnos_a_evaluacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'escala_nota',
    2 => 'param_alumnos',
    3 => 'alumno',
    4 => 'subcomision',
    5 => 'resultado',
    6 => 'evaluacion_origen',
  ),
  'param_null' => 
  array (
    0 => 'alumno',
    1 => 'subcomision',
    2 => 'resultado',
    3 => 'evaluacion_origen',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__alta_evaluacion_parcial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'nombre',
    2 => 'descripcion',
    3 => 'evaluacion_tipo',
    4 => 'visible_al_alumno',
    5 => 'promediable',
    6 => 'fecha',
    7 => 'hora_inicio',
    8 => 'hora_fin',
    9 => 'escala_nota',
    10 => 'instancias',
    11 => 'eval_relacionadas',
  ),
  'param_null' => 
  array (
    0 => 'descripcion',
    1 => 'hora_fin',
    2 => 'instancias',
    3 => 'eval_relacionadas',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_evaluaciones_parciales__baja_evaluacion_parcial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_evaluaciones_parciales__buscar_alumnos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'no_quote' => 'subcomision',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__comision_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'no_quote' => 'subcomision',
  'cache' => 'no',
);
	}

	static function carga_evaluaciones_parciales__escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala_notas',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__evaluacion_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__evaluacion_detalle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'evaluacion',
    2 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__guardar_renglon()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumno',
    2 => 'fecha',
    3 => 'nota',
    4 => 'resultado',
    5 => 'corregido_por',
    6 => 'observaciones',
    7 => 'ultimo_cambio',
  ),
  'cache' => 'no',
  'param_tipo' => 
  array (
    0 => 'int',
    1 => 'int',
    2 => 'str/10',
    3 => 'str/10',
    4 => 'str/1',
    5 => 'str/50',
    6 => 'str/100',
    7 => 'str/23',
  ),
  'param_null' => 
  array (
    0 => 'corregido_por',
    1 => 'observaciones',
    2 => 'nota',
    3 => 'resultado',
  ),
  'filas' => '1',
);
	}

	static function carga_evaluaciones_parciales__lista_evaluaciones_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__lista_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__lista_instancias_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__lista_relacionadas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__lista_subcomisiones_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'no_quote' => 'docente',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__listado_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'escala_asignada',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'escala_asignada',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__listado_evaluaciones_parciales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'comision',
    2 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'no_quote' => 'subcomision',
  'cache' => 'no',
  'cache_expiracion' => '3600',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__listado_tipo_evaluacion()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_evaluaciones_parciales__modificar_evaluacion_parcial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'nombre',
    2 => 'descripcion',
    3 => 'evaluacion_tipo',
    4 => 'fecha',
    5 => 'hora_inicio',
    6 => 'hora_fin',
    7 => 'escala_nota',
    8 => 'visible_al_alumno',
    9 => 'promediable',
    10 => 'flag_instancias',
    11 => 'instancias',
    12 => 'flag_relacionadas',
    13 => 'eval_relacionadas',
  ),
  'param_null' => 
  array (
    0 => 'descripcion',
    1 => 'hora_fin',
    2 => 'instancias',
    3 => 'eval_relacionadas',
  ),
  'no_quote' => 
  array (
    0 => 'evaluacion',
    1 => 'flag_instancias',
    2 => 'flag_relacionadas',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_evaluaciones_parciales__sacar_alumno_de_evaluacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumno',
    2 => 'ultimo_cambio',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__acta_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
  ),
  'no_quote' => 'acta',
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__acta_estado()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__acta_folio()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'renglones_folio',
    2 => 'folio',
  ),
  'no_quote' => 
  array (
    0 => 'acta',
    1 => 'renglones_folio',
    2 => 'folio',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__actualizar_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'version',
    2 => 'version_impresa',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__condiciones_activas()
	{
		return array (
  'cache' => 'no',
);
	}

	static function carga_notas_cursada__condiciones_regularidad()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada__detalle_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala',
  ),
  'no_quote' => 'escala',
  'cache' => 'no',
);
	}

	static function carga_notas_cursada__get_datos_parciales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'comision',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada__guardar_renglon()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumno',
    2 => 'fecha',
    3 => 'cond_regularidad',
    4 => 'nota',
    5 => 'resultado',
  ),
  'param_null' => 
  array (
    0 => 'fecha',
    1 => 'cond_regularidad',
    2 => 'nota',
    3 => 'resultado',
  ),
  'param_tipo' => 
  array (
    0 => 'int',
    1 => 'int',
    2 => 'str/10',
    3 => 'int',
    4 => 'str/10',
    5 => 'str/1',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__lista_actas_abiertas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'comision',
  ),
  'param_null' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada__lista_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'tipo',
  ),
  'cache' => 'no',
);
	}

	static function carga_notas_cursada__porcentaje_carga_notas_promocionales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'comision',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__porcentaje_carga_notas_regulares()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'comision',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada__rango_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala',
  ),
  'no_quote' => 'escala',
  'cache' => 'no',
);
	}

	static function carga_notas_cursada__resultados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala_notas',
  ),
  'no_quote' => 'escala_notas',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada__resultados_escala_nota()
	{
		return array (
  'cache' => 'no',
);
	}

	static function carga_notas_cursada_comision__actualizar_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'version',
    2 => 'version_impresa',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__alumnos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
    2 => 'id_acta',
    3 => 'pagina',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
    1 => 'pagina',
    2 => 'id_acta',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
    2 => 'id_acta',
    3 => 'pagina',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__autocompletar_notas_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumnos',
    2 => 'columnas_completar',
    3 => 'para',
    4 => 'subcomision',
    5 => 'id_acta',
    6 => 'origen_acta',
  ),
  'no_quote' => 
  array (
    0 => 'evaluacion',
    1 => 'alumnos',
    2 => 'columnas_completar',
    3 => 'para',
    4 => 'subcomision',
    5 => 'id_acta',
    6 => 'origen_acta',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
    1 => 'id_acta',
    2 => 'origen_acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__busqueda_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
    2 => 'term',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__calcular_notas_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'instancia',
    2 => 'alumnos_completar',
  ),
  'no_quote' => 
  array (
    0 => 'evaluacion',
    1 => 'instancia',
    2 => 'alumnos_completar',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__comision_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'id_acta',
  ),
  'param_null' => 
  array (
    0 => 'id_acta',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__condiciones_activas()
	{
		return array (
  'cache' => 'no',
);
	}

	static function carga_notas_cursada_comision__condiciones_regularidad()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__detalle_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala',
  ),
  'no_quote' => 'escala',
  'cache' => 'no',
);
	}

	static function carga_notas_cursada_comision__existe_evaluacion_automatica_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'entidad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__filtro_acta()
	{
		return array (
);
	}

	static function carga_notas_cursada_comision__filtro_subcomision()
	{
		return array (
);
	}

	static function carga_notas_cursada_comision__get_datos_parciales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'comision',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__get_escalas_notas_resultados()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__get_escalas_notas_resultados_solo_ausente()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__get_listado_instancias_comisiones()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__get_nombre_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__get_opciones_combo_para()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__get_todas_opciones_combo_para()
	{
		return array (
  'parametros' => 
  array (
    0 => 'es_acta',
  ),
  'no_quote' => 'es_acta',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__guardar_renglon()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumno',
    2 => 'fecha_regular',
    3 => 'fecha_promocion',
    4 => 'cond_regularidad',
    5 => 'nota_cursada',
    6 => 'nota_promocion',
    7 => 'resultado_cursada',
    8 => 'resultado_promocion',
    9 => 'observacion_cursada',
    10 => 'observacion_promocion',
    11 => 'ultimo_cambio',
  ),
  'param_null' => 
  array (
    0 => 'fecha_regular',
    1 => 'fecha_promocion',
    2 => 'cond_regularidad',
    3 => 'nota_cursada',
    4 => 'nota_promocion',
    5 => 'resultado_cursada',
    6 => 'resultado_promocion',
    7 => 'observacion_cursada',
    8 => 'observacion_promocion',
  ),
  'param_tipo' => 
  array (
    0 => 'int',
    1 => 'int',
    2 => 'str/10',
    3 => 'str/10',
    4 => 'int',
    5 => 'str/10',
    6 => 'str/10',
    7 => 'str/1',
    8 => 'str/1',
    9 => 'str/100',
    10 => 'str/100',
    11 => 'str/100',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__lista_comisiones_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__lista_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'tipo',
  ),
  'cache' => 'no',
);
	}

	static function carga_notas_cursada_comision__porcentaje_carga_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_cursada_comision__rango_escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala',
  ),
  'no_quote' => 'escala',
  'cache' => 'no',
);
	}

	static function carga_notas_cursada_comision__resultados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'instancia',
    1 => 'escala_notas',
  ),
  'no_quote' => 
  array (
    0 => 'instancia',
    1 => 'escala_notas',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_cursada_comision__resultados_escala_nota()
	{
		return array (
  'cache' => 'no',
);
	}

	static function carga_notas_examen__acta_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'tipo_acta',
    2 => 'acta',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function carga_notas_examen__acta_folio()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'tipo_acta',
    2 => 'acta',
    3 => 'folio',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_examen__actualizar_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'tipo_acta',
    2 => 'acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_examen__busqueda_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'acta',
    2 => 'term',
  ),
  'cache' => 'no',
  'no_quote' => 'term',
  'filas' => 'n',
);
	}

	static function carga_notas_examen__guardar_renglon()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'tipo_acta',
    2 => 'acta',
    3 => 'carrera',
    4 => 'legajo',
    5 => 'fecha',
    6 => 'resultado',
    7 => 'nota',
    8 => 'nro_inscripcion',
  ),
  'param_tipo' => 
  array (
    0 => 'str/20',
    1 => 'str/20',
    2 => 'str/20',
    3 => 'str/10',
    4 => 'str/10',
    5 => 'str/20',
    6 => 'str/20',
    7 => 'str/5',
    8 => 'str/10',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_examen__lista_actas_abiertas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'nro_inscripcion',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_examen__porcentaje_carga_notas_acta_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'acta',
    2 => 'tipo_acta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__actualizar_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
    1 => 'version',
    2 => 'version_impresa',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_mesa_examen__busqueda_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
    1 => 'term',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__datos_escalas_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__escalas_usadas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__get_alumnos_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__get_datos_encabezado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_mesa_examen__get_datos_mesa_llamados()
	{
		return array (
);
	}

	static function carga_notas_mesa_examen__get_detalles_escala_nota()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala_nota',
  ),
  'no_quote' => 'escala_nota',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__get_instancias_llamado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__guardar_renglon()
	{
		return array (
  'parametros' => 
  array (
    0 => 'evaluacion',
    1 => 'alumno',
    2 => 'fecha',
    3 => 'nota',
    4 => 'resultado',
    5 => 'ultimo_cambio',
  ),
  'param_null' => 
  array (
    0 => 'fecha',
    1 => 'nota',
    2 => 'resultado',
  ),
  'param_tipo' => 
  array (
    0 => 'int',
    1 => 'int',
    2 => 'str/10',
    3 => 'str/10',
    4 => 'str/1',
    5 => 'str/100',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_notas_mesa_examen__lista_mesas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_notas_mesa_examen__resultados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function carga_temas__clase_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'clase',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function carga_temas__clase_detalle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'clase',
    2 => 'subcomision',
  ),
  'param_null' => 
  array (
    0 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function carga_temas__guardar()
	{
		return array (
  'parametros' => 
  array (
    0 => 'clase',
    1 => 'tema_dictado',
    2 => 'tema_planificado',
  ),
  'param_null' => 
  array (
    0 => 'tema_dictado',
    1 => 'tema_planificado',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function certificacion__actualizar_cuil()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'cuil',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function certificacion__borrar_cuil()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function certificacion__get_certificaciones_no_tramitadas_por_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_certificaciones_tramitadas_por_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_certificados_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'plan_version',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_colaciones_por_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_documentos_asociados_tramite_certificacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_solicitud',
  ),
  'no_quote' => 'nro_solicitud',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_juramentos()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_orientaciones_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_requisitos_by_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_solicitud',
    1 => 'estado_req_digital',
  ),
  'no_quote' => 
  array (
    0 => 'nro_solicitud',
    1 => 'estado_req_digital',
  ),
  'param_null' => 
  array (
    0 => 'estado_req_digital',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__get_tramites_certificacion_by_persona_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function certificacion__insertar_cuil()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'cuil',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function certificacion__registrar_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'certificado',
    2 => 'alumno',
    3 => 'plan_version',
    4 => 'juramento',
    5 => 'colacion',
  ),
  'param_null' => 
  array (
    0 => 'juramento',
    1 => 'colacion',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'certificado',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function certificados__certificados_argumentos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_certificado',
  ),
  'cache' => 'no',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function certificados__certificados_habilitados()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'nro_inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function certificados__certificados_solicitar()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'certificado',
    2 => 'nro_inscripcion',
    3 => 'cantidad_copias',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function certificados__guardar_argumentos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'indice_certificado',
    1 => 'orden',
    2 => 'valor',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function certificados__recuperar_certificado_web()
	{
		return array (
  'parametros' => 
  array (
    0 => 'documento',
    1 => 'codigo_validador',
  ),
  'no_quote' => 
  array (
    0 => 'documento',
    1 => 'codigo_validador',
  ),
  'cache' => 'memoria',
);
	}

	static function comisiones__get_alumnos_de_parciales_insc_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function comisiones__get_cronograma_evaluaciones_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'visible_al_alumno',
    2 => 'solo_insc_cursos',
  ),
  'param_null' => 
  array (
    0 => 'visible_al_alumno',
    1 => 'solo_insc_cursos',
  ),
  'no_quote' => 
  array (
    0 => 'visible_al_alumno',
    1 => 'solo_insc_cursos',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function comisiones__get_datos_evaluaciones_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'visible_al_alumno',
  ),
  'param_null' => 
  array (
    0 => 'visible_al_alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function comisiones__get_evaluaciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'visible_al_alumno',
  ),
  'param_null' => 
  array (
    0 => 'visible_al_alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function comisiones__get_resultados_acta_cursada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'estado_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function comisiones__get_resultados_acta_promocion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'estado_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function comisiones__get_subcomisiones_by_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function comisiones__lista_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'param_null' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'no_quote' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'cache' => 'no',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function controles____call()
	{
		return array (
);
	}

	static function cursos__actividades_propuestas_administran_cursos_con_comisiones_vigentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta_curso',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function cursos__get_cursos_academica()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
  ),
  'param_null' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function cursos__get_info_comisiones_curso()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function cursos__get_info_curso()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function cursos__get_propuestas_academica()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function cursos__lista_constancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function cursos__propuestas_administran_cursos()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__actualizar_fecha_modificacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'dato_censal',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function datos_censales__controlar_tablas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'dato_censal',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__existe_dato_en_tabla()
	{
		return array (
);
	}

	static function datos_censales__get_allegados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_antecedentes_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_antecedentes_profesionales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_colegios_autocomplete()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nombre',
  ),
  'param_null' => 
  array (
    0 => 'nombre',
  ),
  'no_quote' => 'nombre',
  'param_tipo' => 
  array (
    0 => 'string',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_datos_censales()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function datos_censales__get_datos_contactos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_datos_estudios()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_descr_localidad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'localidad',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function datos_censales__get_instituciones_autocomplete()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nombre',
  ),
  'param_null' => 
  array (
    0 => 'nombre',
  ),
  'no_quote' => 'nombre',
  'param_tipo' => 
  array (
    0 => 'string',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_localidades_autocomplete()
	{
		return array (
  'parametros' => 
  array (
    0 => 'localidad_nombre',
    1 => 'pais',
  ),
  'param_null' => 
  array (
    0 => 'localidad_nombre',
    1 => 'pais',
  ),
  'no_quote' => 
  array (
    0 => 'localidad_nombre',
    1 => 'pais',
  ),
  'param_tipo' => 
  array (
    0 => 'string',
    1 => 'int',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_titulos_autocomplete()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nombre',
    1 => 'tipo',
  ),
  'param_null' => 
  array (
    0 => 'nombre',
    1 => 'tipo',
  ),
  'no_quote' => 
  array (
    0 => 'nombre',
    1 => 'tipo',
  ),
  'param_tipo' => 
  array (
    0 => 'string',
    1 => 'tipo',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_censales__get_ultima_actualizacion_dc()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function datos_censales__insertar_dato_censal()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function datos_censales__insertar_dato_en_tabla()
	{
		return array (
);
	}

	static function datos_censales__tiene_datos_censales_actualizados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'fecha',
    2 => 'cant_meses',
  ),
  'no_quote' => 'cant_meses',
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function derecho_entidades__tiene_derecho_actas_fc()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'derecho',
    2 => 'actas_fc',
  ),
  'cache' => 'no',
  'no_quote' => 'actas_fc',
  'filas' => 'n',
);
	}

	static function derecho_entidades__tiene_derecho_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'derecho',
    2 => 'comisiones',
  ),
  'cache' => 'no',
  'no_quote' => 'comisiones',
  'filas' => 'n',
);
	}

	static function derecho_entidades__tiene_derecho_llamados_mesas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'derecho',
    2 => 'llamados_mesas',
  ),
  'cache' => 'no',
  'no_quote' => 'llamados_mesas',
  'filas' => 'n',
);
	}

	static function derechos_comision__agregar_derecho()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_',
    1 => 'derecho',
    2 => 'entidad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_comision__comision_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_comision__derechos_asignables()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function derechos_comision__derechos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_comision__derechos_comision_rol()
	{
		return array (
  'parametros' => 
  array (
    0 => 'rol',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function derechos_comision__docentes_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_comision__eliminar_derecho()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_',
    1 => 'derecho',
    2 => 'entidad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_comision__lista_actividades_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'term',
  ),
  'no_quote' => 'term',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_comision__lista_comisiones_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'actividad',
  ),
  'param_null' => 
  array (
    0 => 'actividad',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_comision__responsabilidad_docente_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'comision',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function derechos_mesa__agregar_derecho()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_',
    1 => 'derecho',
    2 => 'entidad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_mesa__cant_dias_extras_para_mostrar_mesas_examenes()
	{
		return array (
);
	}

	static function derechos_mesa__derechos_asignables()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function derechos_mesa__derechos_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_mesa__derechos_mesa_rol()
	{
		return array (
  'parametros' => 
  array (
    0 => 'rol',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function derechos_mesa__docentes_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_mesa__eliminar_derecho()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_',
    1 => 'derecho',
    2 => 'entidad',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_mesa__lista_actividades_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'term',
  ),
  'no_quote' => 'term',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_mesa__lista_mesas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'actividad',
  ),
  'param_null' => 
  array (
    0 => 'actividad',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function derechos_mesa__mesa_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function derechos_mesa__rol_docente_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'llamado_mesa',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function docente__agenda_cursadas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function docente__agenda_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function docente__alum_inscriptos_acta_fc()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__alum_inscriptos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__alum_inscriptos_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
    1 => 'incluir_otros_alumnos',
    2 => 'evaluacion',
  ),
  'param_null' => 
  array (
    0 => 'incluir_otros_alumnos',
    1 => 'evaluacion',
  ),
  'no_quote' => 
  array (
    0 => 'llamado_mesa',
    1 => 'incluir_otros_alumnos',
    2 => 'evaluacion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__alum_inscriptos_subcomision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__alum_rechazados_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__buscar_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'comisiones_activas',
    2 => 'turnos_examen_no_finalizados',
  ),
  'param_null' => 
  array (
    0 => 'comisiones_activas',
    1 => 'turnos_examen_no_finalizados',
  ),
  'no_quote' => 
  array (
    0 => 'term',
    1 => 'comisiones_activas',
    2 => 'turnos_examen_no_finalizados',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__buscar_procedencia_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'comisiones_activas',
    2 => 'turnos_examen_no_finalizados',
  ),
  'param_null' => 
  array (
    0 => 'comisiones_activas',
    1 => 'turnos_examen_no_finalizados',
  ),
  'no_quote' => 
  array (
    0 => 'comisiones_activas',
    1 => 'turnos_examen_no_finalizados',
  ),
  'cache' => 'no',
);
	}

	static function docente__comision_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__datos_basicos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function docente__datos_mesa_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__detalle_acta_cursada_promocion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__detalle_acta_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__get_clases_comisiones_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'filas',
  ),
  'no_quote' => 
  array (
    0 => 'docente',
    1 => 'filas',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '3600',
  'filas' => 'n',
);
	}

	static function docente__get_datos_cabecera_acta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
    1 => 'origen',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__get_docentes_actas_fuera_calendario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'term',
    1 => 'docente',
  ),
  'cache' => 'memoria',
);
	}

	static function docente__get_docentes_activos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'apellido',
  ),
  'no_quote' => 'apellido',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__get_docentes_formulario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'formulario',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__get_lista_ubicaciones()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function docente__get_tribunales_roles()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function docente__get_ubicaciones_por_propuestas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function docente__lista_actas_cursadas_promociones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'origen_acta',
    2 => 'anio_academico',
    3 => 'periodo_lectivo',
    4 => 'actividad_codigo',
  ),
  'param_null' => 
  array (
    0 => 'origen_acta',
    1 => 'anio_academico',
    2 => 'periodo_lectivo',
    3 => 'actividad_codigo',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_actas_examenes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'anio_academico',
    2 => 'turno_examen',
    3 => 'actividad_nombre',
  ),
  'param_null' => 
  array (
    0 => 'anio_academico',
    1 => 'turno_examen',
    2 => 'actividad_nombre',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_actas_fc_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'id_acta',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'turnos_examen_no_finalizados',
  ),
  'param_null' => 
  array (
    0 => 'id_acta',
    1 => 'actividad',
    2 => 'ubicacion',
    3 => 'turnos_examen_no_finalizados',
  ),
  'no_quote' => 'turnos_examen_no_finalizados',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_actividades()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'docente',
  ),
  'no_quote' => 'term',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function docente__lista_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'param_null' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'no_quote' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'ubicacion',
  ),
  'cache' => 'no',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function docente__lista_comisiones_activas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'periodo_lectivo',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'comisiones_activas',
  ),
  'param_null' => 
  array (
    0 => 'docente',
    1 => 'periodo_lectivo',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'comisiones_activas',
  ),
  'no_quote' => 
  array (
    0 => 'docente',
    1 => 'periodo_lectivo',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'comisiones_activas',
  ),
  'cache' => 'no',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function docente__lista_comisiones_docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
  ),
  'no_quote' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function docente__lista_comisiones_rech()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'periodo_lectivo',
    2 => 'actividad',
    3 => 'ubicacion',
  ),
  'param_null' => 
  array (
    0 => 'periodo_lectivo',
    1 => 'actividad',
    2 => 'ubicacion',
  ),
  'cache' => 'no',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function docente__lista_mesas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'turno_examen',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'fecha_desde',
    5 => 'fecha_hasta',
    6 => 'anio_academico',
    7 => 'mostrar_mesas_fuera_calendario',
    8 => 'mesas_con_inscriptos',
  ),
  'param_null' => 
  array (
    0 => 'turno_examen',
    1 => 'actividad',
    2 => 'ubicacion',
    3 => 'fecha_desde',
    4 => 'fecha_hasta',
    5 => 'anio_academico',
    6 => 'mostrar_mesas_fuera_calendario',
    7 => 'mesas_con_inscriptos',
  ),
  'no_quote' => 'mesas_con_inscriptos',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_mesas_examen_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'fecha_examen_desde',
    2 => 'fecha_examen_hasta',
  ),
  'param_null' => 
  array (
    0 => 'fecha_examen_desde',
    1 => 'fecha_examen_hasta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_mesas_examen_docente_dentro_o_fuera_calendario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'turno',
    2 => 'actividad',
    3 => 'ubicacion',
    4 => 'fuera_calendario',
    5 => 'turnos_examen_no_finalizados',
  ),
  'param_null' => 
  array (
    0 => 'turno',
    1 => 'actividad',
    2 => 'ubicacion',
    3 => 'fuera_calendario',
    4 => 'turnos_examen_no_finalizados',
  ),
  'no_quote' => 
  array (
    0 => 'fuera_calendario',
    1 => 'turnos_examen_no_finalizados',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__lista_mesas_examen_mensajes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function docente__mensaje_turno()
	{
		return array (
);
	}

	static function docente__subcomision_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function docente__subquery_email()
	{
		return array (
);
	}

	static function docente__subquery_telefono()
	{
		return array (
);
	}

	static function encuestas__alcances()
	{
		return array (
  'cache' => 'no',
);
	}

	static function encuestas__definicion_alcance()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alcance',
  ),
  'cache' => 'no',
);
	}

	static function encuestas__encuestas_pendientes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'perfil',
  ),
  'no_quote' => 'perfil',
  'cache' => 'memoria',
);
	}

	static function encuestas__encuestas_respondidas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'perfil',
    2 => 'fecha_desde',
    3 => 'fecha_hasta',
    4 => 'docente',
    5 => 'actividad',
  ),
  'param_null' => 
  array (
    0 => 'fecha_desde',
    1 => 'fecha_hasta',
    2 => 'docente',
    3 => 'actividad',
  ),
  'no_quote' => 'perfil',
  'cache' => 'no',
);
	}

	static function encuestas__get_comision_ver_encuestas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'periodo_lectivo',
    1 => 'docente',
  ),
  'cache' => 'no',
);
	}

	static function encuestas__get_id_elemento_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'item',
  ),
  'cache' => 'memoria',
);
	}

	static function encuestas__get_id_elementos()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function encuestas__get_id_formulario_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'formulario',
  ),
  'cache' => 'memoria',
);
	}

	static function encuestas__get_id_formularios()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function encuestas__get_id_kolla_encuestas()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function encuestas__get_id_kolla_habilitacion()
	{
		return array (
  'cache' => 'memoria',
);
	}

	static function encuestas__marcar_encuesta_respondida()
	{
		return array (
  'parametros' => 
  array (
    0 => 'hash',
    1 => 'fecha_respuesta',
  ),
  'param_null' => 
  array (
    0 => 'fecha_respuesta',
  ),
  'cache' => 'no',
);
	}

	static function encuestas__password_habilitacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'habilitacion',
  ),
  'cache' => 'no',
);
	}

	static function encuestas__reporte_encuestas_actuacion_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
    1 => 'anio_academico',
    2 => 'periodo_lectivo',
    3 => 'actividad',
    4 => 'comision',
  ),
  'param_null' => 
  array (
    0 => 'periodo_lectivo',
    1 => 'actividad',
    2 => 'comision',
  ),
  'cache' => 'no',
);
	}

	static function encuestas__reporte_encuestas_actuacion_otros_docentes_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
);
	}

	static function etiquetas__empresas_celulares()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function etiquetas__localidades()
	{
		return array (
  'cache' => 'memoria',
  'parametros' => 
  array (
    0 => 'provincia_id',
    1 => 'localidad',
  ),
  'no_quote' => 'localidad',
  'filas' => 'n',
);
	}

	static function etiquetas__nombre_localidad()
	{
		return array (
  'cache' => 'memoria',
  'parametros' => 
  array (
    0 => 'localidad',
  ),
  'filas' => '1',
);
	}

	static function etiquetas__paises()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function etiquetas__provincias()
	{
		return array (
  'cache' => 'memoria',
  'parametros' => 
  array (
    0 => 'pais',
  ),
  'filas' => 'n',
);
	}

	static function etiquetas__tipos_documento()
	{
		return array (
  'cache' => 'memoria',
  'parametros' => 
  array (
    0 => 'pais',
  ),
);
	}

	static function impresion_actas_examen__acta_cabecera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function impresion_actas_examen__get_puntos_de_control_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'instancias',
  ),
  'no_quote' => 'instancias',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function impresion_actas_examen__lista_actas_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function impresion_actas_examen__marcar_cerrada_por_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id_acta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__asignaciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__cupo_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comisiones',
    1 => 'param_cur_controlar_cupo_por',
    2 => 'propuesta',
  ),
  'no_quote' => 
  array (
    0 => 'comisiones',
    1 => 'param_cur_controlar_cupo_por',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__cupo_subcomisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'subcomisiones',
    1 => 'param_cur_controlar_cupo_por',
  ),
  'no_quote' => 
  array (
    0 => 'subcomisiones',
    1 => 'param_cur_controlar_cupo_por',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__dia_a_numero()
	{
		return array (
);
	}

	static function inscripcion_cursada__eliminar_inscripcion_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__existe_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__get_excepciones_periodo_inscripcion_por_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'plan_version',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__get_motivo_rechazo_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion_rechazada',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__get_periodos_inscripcion_vigentes_por_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'plan_version',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__get_proximo_periodo_inscripcion_vigente_por_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'plan_version',
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__hay_actividades_con_comisiones_vigentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
    1 => 'plan',
    2 => 'plan_version',
    3 => 'ubicacion',
    4 => 'alumno',
  ),
  'no_quote' => 
  array (
    0 => 'propuesta',
    1 => 'plan',
    2 => 'plan_version',
    3 => 'ubicacion',
    4 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__info_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_comisiones_por_inscripcion_prioridad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'nro_transaccion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_comprobante_baja()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_comprobante_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_cupo_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'comisiones_str',
    2 => 'param_cur_controla_cupo_por',
  ),
  'no_quote' => 
  array (
    0 => 'comisiones_str',
    1 => 'param_cur_controla_cupo_por',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__info_oferta_comisiones_vigentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'perfil_activo',
  ),
  'param_null' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'perfil_activo',
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__inscripcion_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'alumno',
    2 => 'persona',
    3 => 'plan_version',
    4 => 'prioridad',
    5 => 'estado',
    6 => 'tipo',
    7 => 'estado_preinscripcion',
    8 => 'fuera_de_termino',
    9 => 'nro_transaccion',
    10 => 'instancias',
    11 => 'subcomisiones',
    12 => '_interfaz',
    13 => 'periodo_lectivo',
    14 => 'param_cur_controlar_cupo',
    15 => 'param_cur_controlar_cupo_por',
    16 => 'param_cur_insc_siempre_pendiente',
  ),
  'param_null' => 
  array (
    0 => 'nro_transaccion',
  ),
  'no_quote' => 
  array (
    0 => 'instancias',
    1 => 'subcomisiones',
    2 => 'param_cur_controlar_cupo',
    3 => 'param_cur_controlar_cupo_por',
    4 => 'param_cur_insc_siempre_pendiente',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_cursada__inscripciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__lista_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'actividad',
    2 => 'propuesta',
    3 => 'plan',
    4 => 'plan_version',
    5 => 'ubicacion',
    6 => 'param_cur_usa_subcomisiones',
    7 => 'param_cur_cursa_en_cualquier_ubicacion',
    8 => 'param_cur_filtra_rango_apellido_comision',
    9 => 'param_cur_filtra_rango_apellido_subco',
    10 => 'param_cur_controlar_cupo_por',
    11 => 'inicial_apellido',
    12 => 'division',
    13 => 'param_cur_division_estricta',
  ),
  'no_quote' => 
  array (
    0 => 'param_cur_usa_subcomisiones',
    1 => 'param_cur_cursa_en_cualquier_ubicacion',
    2 => 'param_cur_filtra_rango_apellido_comision',
    3 => 'param_cur_filtra_rango_apellido_subco',
    4 => 'param_cur_controlar_cupo_por',
    5 => 'param_cur_division_estricta',
  ),
  'param_null' => 
  array (
    0 => 'division',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__lista_materias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'persona',
    2 => 'propuesta',
    3 => 'plan_version',
    4 => 'ubicacion',
    5 => 'param_cur_ingresantes_oferta_actividades',
    6 => 'param_cur_filtro_actividades_con_comision',
    7 => 'param_cur_cursa_en_cualquier_ubicacion',
    8 => 'param_cur_recursar_regularizadas',
  ),
  'param_null' => 
  array (
    0 => 'ubicacion',
  ),
  'no_quote' => 
  array (
    0 => 'param_cur_ingresantes_oferta_actividades',
    1 => 'param_cur_filtro_actividades_con_comision',
    2 => 'param_cur_cursa_en_cualquier_ubicacion',
    3 => 'param_cur_recursar_regularizadas',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__lista_parcial_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'actividad',
    2 => 'propuesta',
    3 => 'plan',
    4 => 'plan_version',
    5 => 'ubicacion',
    6 => 'inicial_apellido',
    7 => 'division',
    8 => 'param_cur_usa_subcomisiones',
    9 => 'param_cur_cursa_en_cualquier_ubicacion',
    10 => 'param_cur_filtra_rango_apellido_comision',
    11 => 'param_cur_controlar_cupo_por',
    12 => 'param_cur_division_estricta',
  ),
  'no_quote' => 
  array (
    0 => 'param_cur_usa_subcomisiones',
    1 => 'param_cur_cursa_en_cualquier_ubicacion',
    2 => 'param_cur_filtra_rango_apellido_comision',
    3 => 'param_cur_controlar_cupo_por',
    4 => 'param_cur_division_estricta',
  ),
  'param_null' => 
  array (
    0 => 'division',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_cursada__lista_parcial_subcomisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'actividad',
    2 => 'propuesta',
    3 => 'plan',
    4 => 'plan_version',
    5 => 'ubicacion',
    6 => 'inicial_apellido',
    7 => 'division',
    8 => 'param_cur_usa_subcomisiones',
    9 => 'param_cur_cursa_en_cualquier_ubicacion',
    10 => 'param_cur_filtra_rango_apellido_comision',
    11 => 'param_cur_controlar_cupo_por',
    12 => 'param_cur_division_estricta',
  ),
  'no_quote' => 
  array (
    0 => 'param_cur_filtra_rango_apellido_subco',
    1 => 'param_cur_controlar_cupo_por',
    2 => 'param_cur_division_estricta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__eliminar_inscripcion_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
    1 => '_interfaz',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_examen__get_instancias_llamado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__get_instancias_mesas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mesas',
    1 => 'tipo_plan',
  ),
  'no_quote' => 
  array (
    0 => 'mesas',
    1 => 'tipo_plan',
  ),
  'param_null' => 
  array (
    0 => 'tipo_plan',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__get_mesas_disponibles_para_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'actividad',
    2 => 'propuesta',
    3 => 'plan',
    4 => 'plan_version',
    5 => 'ubicacion',
    6 => 'modalidad',
    7 => 'division',
    8 => 'param_exa_rinde_en_cualquier_ubicacion',
    9 => 'param_exa_mostrar_mesas_todas_modalidades',
    10 => 'param_cur_division_estricta',
    11 => 'tipo_plan',
    12 => 'param_exa_multiples_actas_examen',
    13 => 'param_exa_mostrar_mesas_con_cupo_completo',
  ),
  'no_quote' => 
  array (
    0 => 'param_exa_rinde_en_cualquier_ubicacion',
    1 => 'param_exa_mostrar_mesas_todas_modalidades',
    2 => 'param_cur_division_estricta',
    3 => 'param_exa_multiples_actas_examen',
    4 => 'tipo_plan',
    5 => 'param_exa_mostrar_mesas_con_cupo_completo',
  ),
  'param_null' => 
  array (
    0 => 'ubicacion',
    1 => 'division',
    2 => 'tipo_plan',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__get_motivo_rechazo_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion_rechazada',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_examen__info_comprobante_baja()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__info_comprobante_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__info_inscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'inscripcion',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__inscripcion_mesa_examen()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'alumno',
    2 => 'actividad',
    3 => 'llamado_mesa',
    4 => 'instancia',
    5 => 'estado',
    6 => '_interfaz',
    7 => 'plan_version',
    8 => 'param_exa_insc_unico_llamado',
    9 => 'param_exa_insc_cant_max_una_actividad_por_llamado',
    10 => 'param_exa_insc_cant_max_una_actividad_por_turno',
    11 => 'param_exa_insc_siempre_pendiente',
  ),
  'no_quote' => 
  array (
    0 => 'param_exa_insc_unico_llamado',
    1 => 'param_exa_insc_cant_max_una_actividad_por_llamado',
    2 => 'param_exa_insc_cant_max_una_actividad_por_turno',
    3 => 'param_exa_insc_siempre_pendiente',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function inscripcion_examen__inscripciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function inscripcion_examen__lista_materias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'persona',
    2 => 'propuesta',
    3 => 'plan_version',
    4 => 'ubicacion',
    5 => 'param_exa_filtro_actividades_con_mesas',
    6 => 'param_exa_rinde_en_cualquier_ubicacion',
    7 => 'tipo_plan',
    8 => 'param_exa_rendir_regularizada_plan_anterior',
    9 => 'param_exa_multiples_actas_examen',
  ),
  'param_null' => 
  array (
    0 => 'ubicacion',
    1 => 'tipo_plan',
    2 => 'param_exa_rendir_regularizada_plan_anterior',
  ),
  'no_quote' => 
  array (
    0 => 'param_exa_filtro_actividades_con_mesas',
    1 => 'param_exa_rinde_en_cualquier_ubicacion',
    2 => 'param_exa_rendir_regularizada_plan_anterior',
    3 => 'param_exa_multiples_actas_examen',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => 'n',
);
	}

	static function matricula__anio_academico_reinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'periodo_inscripcion',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function matricula__carreras_a_reinscribirse()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'anio_academico_reinscripcion',
  ),
  'cache' => 'no',
);
	}

	static function matricula__datos_reinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nro_trans',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function matricula__periodos_reinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'alumno',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function matricula__reinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
  ),
  'cache' => 'no',
);
	}

	static function matricula__ultimo_periodo_inscripcion_vigente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'periodo_insc_tipo',
    2 => 'alumno',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => '1',
);
	}

	static function mensajes__get_adjuntos()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_anio_academico_mensaje()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_cant_mensajes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '300',
  'filas' => '1',
);
	}

	static function mensajes__get_comisiones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_mensajes_enviados()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'fecha_desde',
    2 => 'fecha_hasta',
    3 => 'ultimos',
  ),
  'param_null' => 
  array (
    0 => 'fecha_desde',
    1 => 'fecha_hasta',
    2 => 'ultimos',
  ),
  'cache' => 'no',
  'cache_expiracion' => '120',
  'filas' => 'n',
);
	}

	static function mensajes__get_mensajes_recibidos_vigentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'cache_expiracion' => '120',
  'filas' => 'n',
);
	}

	static function mensajes__get_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_personas_destinatarios_alumnos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_personas_destinatarios_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_personas_destinatarios_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_personas_destinatarios_subcomision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'subcomision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__get_personas_destinatarios_subcomisiones_by_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__guardar_destinatario_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
    1 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__guardar_destinatario_alumnos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
    1 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__guardar_destinatario_alumnos_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
    1 => 'mesa',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__guardar_documento()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
    1 => 'path',
    2 => 'nombre',
    3 => 'tamanio',
    4 => 'extension',
    5 => 'tmp_name',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__guardar_mensaje()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alcance',
    1 => 'titulo',
    2 => 'cuerpo',
    3 => 'fecha_desde',
    4 => 'fecha_hasta',
    5 => 'enviar_por_mail',
    6 => 'enviar_por_twitter',
    7 => 'mensaje_twitter',
    8 => 'enviar_por_facebook',
    9 => 'activo',
    10 => 'persona_alta',
    11 => 'error_envio_mail',
  ),
  'param_null' => 
  array (
    0 => 'mensaje_twitter',
    1 => 'error_envio_mail',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mensajes__leer_mensaje()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'mensaje',
    2 => 'enviado',
  ),
  'no_quote' => 'enviado',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function mesa__info_link_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'mesa_examen',
  ),
  'cache' => 'no',
);
	}

	static function mesa__insertar_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'nombre',
    1 => 'elemento',
    2 => 'catedra',
    3 => 'ubicacion',
    4 => 'observaciones',
    5 => 'mesa_en_turno_examen',
    6 => 'anio_academico',
    7 => 'division',
    8 => 'fecha',
    9 => 'hora_inicio',
    10 => 'hora_finalizacion',
    11 => 'espacio',
    12 => 'instancias',
    13 => 'modalidad',
    14 => 'docentes',
  ),
  'param_null' => 
  array (
    0 => 'catedra',
    1 => 'observaciones',
    2 => 'division',
    3 => 'espacio',
    4 => 'hora_finalizacion',
  ),
  'cache' => 'no',
);
	}

	static function moodle__eliminar_curso()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
  ),
  'no_quote' => 'curso',
  'cache' => 'no',
);
	}

	static function moodle__get_alumnos_eliminados_a_comision_moodle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'param_null' => 
  array (
    0 => 'plataforma',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'cache' => 'memoria',
);
	}

	static function moodle__get_alumnos_inscriptos_a_comision_moodle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'param_null' => 
  array (
    0 => 'plataforma',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'cache' => 'memoria',
);
	}

	static function moodle__get_alumnos_inscriptos_a_curso_moodle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
  ),
  'no_quote' => 'curso',
  'cache' => 'memoria',
);
	}

	static function moodle__get_curso_virtual_by_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'no_quote' => 'comision',
  'no_log' => 'plataforma_token',
  'cache' => 'memoria',
);
	}

	static function moodle__get_curso_virtual_by_llamado_mesa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'llamado_mesa',
  ),
  'no_quote' => 'llamado_mesa',
  'no_log' => 'plataforma_token',
  'cache' => 'memoria',
);
	}

	static function moodle__get_datos_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'no_quote' => 'comision',
  'cache' => 'memoria',
);
	}

	static function moodle__get_datos_curso_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
    1 => 'plataforma',
  ),
  'no_quote' => 
  array (
    0 => 'curso',
    1 => 'plataforma',
  ),
  'cache' => 'memoria',
);
	}

	static function moodle__get_docentes_comision_moodle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'no_quote' => 
  array (
    0 => 'comision',
    1 => 'plataforma',
  ),
  'cache' => 'memoria',
);
	}

	static function moodle__get_docentes_curso_moodle()
	{
		return array (
  'parametros' => 
  array (
    0 => 'curso',
  ),
  'no_quote' => 'curso',
  'cache' => 'memoria',
);
	}

	static function moodle__get_plataforma_educacion_virtual()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plataforma',
  ),
  'no_quote' => 'plataforma',
  'no_log' => 'token',
  'cache' => 'memoria',
);
	}

	static function moodle__get_plataformas_by_comision()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'no_quote' => 'comision',
  'no_log' => 'token',
  'cache' => 'memoria',
);
	}

	static function moodle__get_roles_docentes_by_plataforma()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plataforma',
  ),
  'no_quote' => 'plataforma',
  'cache' => 'memoria',
);
	}

	static function noticias__noticias_por_persona_id()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_id',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function noticias__tiene_noticias_por_persona_id()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona_id',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function orientacion__certificados_con_orientaciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function orientacion__eliminar_seleccion_orientacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan',
    2 => 'orientacion',
    3 => 'observaciones',
  ),
  'param_null' => 
  array (
    0 => 'observaciones',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function orientacion__orientacion_actual()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'certificado',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function orientacion__orientaciones_disponibles_x_certificado()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan',
    2 => 'plan_version',
    3 => 'certificado',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function orientacion__registrar_seleccion_orientacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan',
    2 => 'orientacion',
    3 => 'observaciones',
  ),
  'param_null' => 
  array (
    0 => 'observaciones',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function parametro__banda_horaria_cursado()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__filtro_materias_con_comision()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => 'n',
);
	}

	static function parametro__filtro_materias_con_mesa_exa()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__get_parametro_sistema()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
  ),
  'no_quote' => 'parametro',
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__get_parametro_sistema_plan()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'plan',
  ),
  'no_quote' => 
  array (
    0 => 'parametro',
    1 => 'plan',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__get_parametro_sistema_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'plan_version',
  ),
  'no_quote' => 
  array (
    0 => 'parametro',
    1 => 'plan_version',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__get_parametro_sistema_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'propuesta',
  ),
  'no_quote' => 
  array (
    0 => 'parametro',
    1 => 'propuesta',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__get_parametro_sistema_propuesta_tipo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'propuesta_tipo',
  ),
  'no_quote' => 
  array (
    0 => 'parametro',
    1 => 'propuesta_tipo',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__get_parametro_sistema_propuesta_unico()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'propuestas',
  ),
  'no_quote' => 'propuestas',
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__get_parametro_sistema_ra()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
    1 => 'responsable_academica',
  ),
  'no_quote' => 
  array (
    0 => 'parametro',
    1 => 'responsable_academica',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function parametro__inscripcion_cursada_calidad()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => 'n',
);
	}

	static function parametro__orientacion_tipo_eleccion()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__permitir_login_mail()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__recursar_regularizadas()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => 'n',
);
	}

	static function parametro__registrar_log_ingresos()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function parametro__reinscripcion_ingresante()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
);
	}

	static function persona__actualizar_configuracion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'recibe_mail_mensajes',
    2 => 'email',
  ),
  'param_null' => 
  array (
    0 => 'email',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function persona__actualizar_configuracion_checks()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'recibe_mail_mensajes',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function persona__actualizar_configuracion_email()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'email',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function persona__actualizar_password()
	{
		return array (
  'parametros' => 
  array (
    0 => 'usuario',
    1 => 'actual_pass',
    2 => 'nuevo_pass',
  ),
  'no_quote' => 
  array (
    0 => 'actual_pass',
    1 => 'nuevo_pass',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__asociar_foto()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'id_imagen',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__blanquear_email_temporal()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__buscar_clave()
	{
		return array (
  'parametros' => 
  array (
    0 => 'usuario',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__buscar_email_temporal_por_token()
	{
		return array (
  'parametros' => 
  array (
    0 => 'token',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__buscar_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'usuario',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__buscar_por_token()
	{
		return array (
  'parametros' => 
  array (
    0 => 'token',
  ),
  'no_quote' => 'token',
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__carreras()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'sesion',
  'filas' => 'n',
);
	}

	static function persona__configuracion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '600',
  'filas' => 'n',
);
	}

	static function persona__data_recuperar_pass()
	{
		return array (
  'parametros' => 
  array (
    0 => 'pais',
    1 => 'tipo_doc',
    2 => 'doc',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__datos_basicos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'sesion',
  'filas' => '1',
);
	}

	static function persona__datos_persona_header()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__desvincular_login_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'proveedor',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__existe_configuracion_email()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__get_documentos_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'tipo_documento',
  ),
  'param_null' => 
  array (
    0 => 'tipo_documento',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function persona__get_login_externos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function persona__get_propuesta_inicial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function persona__get_tipo_usuario_inicial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function persona__insertar_cabecera_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'apellido',
    1 => 'nombres',
    2 => 'sexo',
    3 => 'fecha_nacimiento',
    4 => 'localidad_nacimiento',
    5 => 'nacionalidad',
    6 => 'pais_origen',
    7 => 'usuario',
    8 => 'clave',
  ),
  'no_quote' => 
  array (
    0 => 'apellido',
    1 => 'nombres',
    2 => 'apellido_elegido',
    3 => 'nombres_elegido',
    4 => 'sexo',
    5 => 'fecha_nacimiento',
    6 => 'localidad_nacimiento',
    7 => 'nacionalidad',
    8 => 'pais_origen',
    9 => 'usuario',
    10 => 'clave',
    11 => 'identidad_genero',
    12 => 'identidad_genero_otro',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__insertar_configuracion_email()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'email',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function persona__insertar_documento_digital()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'requisito',
    2 => 'id_documento',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__insertar_documento_principal()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'pais_documento',
    2 => 'tipo_documento',
    3 => 'nro_documento',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'pais_documento',
    2 => 'tipo_documento',
    3 => 'nro_documento',
    4 => 'validado_con_renaper',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__insertar_mail_principal()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'email',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'email',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__insertar_otro_documento()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'pais_documento',
    2 => 'tipo_documento',
    3 => 'nro_documento',
    4 => 'validado_con_renaper',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'pais_documento',
    2 => 'tipo_documento',
    3 => 'nro_documento',
    4 => 'validado_con_renaper',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__insertar_registro_logueo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'registrar',
    2 => 'ip',
    3 => 'login_por',
    4 => 'status',
  ),
  'no_quote' => 'registrar',
  'param_null' => 
  array (
    0 => 'status',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__limpiar_token()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'no',
);
	}

	static function persona__login_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'proveedor',
    1 => 'id',
    2 => 'mail',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__param_nombre_persona_doc_oficiales()
	{
		return array (
);
	}

	static function persona__perfiles()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'sesion',
  'filas' => 'n',
);
	}

	static function persona__registrar_logout()
	{
		return array (
  'parametros' => 
  array (
    0 => 'log_ingreso',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__registrar_propuesta_inicial()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__reset_password()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'password',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__saml_buscar_por_persona()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__saml_buscar_por_usuario()
	{
		return array (
  'parametros' => 
  array (
    0 => 'usuario',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__set_perfil_defecto()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'perfil_inicial',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__update_email_temporal()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'email',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function persona__update_token()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'token',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'token',
  ),
  'cache' => 'no',
);
	}

	static function persona__vincular_login_externo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'id',
    2 => 'mail',
    3 => 'proveedor',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function plan_estudio__get_detalle_materias_plan_version()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function preinscripcion__asignar_turno()
	{
		return array (
);
	}

	static function preinscripcion__cambiar_estado_preinscripcion_3w()
	{
		return array (
  'parametros' => 
  array (
    0 => 'preinscripcion',
  ),
  'no_quote' => 'estado',
  'cache' => 'no',
);
	}

	static function preinscripcion__devolver_turno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'ra',
  ),
  'cache' => 'no',
);
	}

	static function preinscripcion__get_cantidad_preinscripciones_vigentes()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__get_fecha_maxima()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__get_fecha_minima()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__get_fechas_turnos_disponibles()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_franjas_horarias_disponibles()
	{
		return array (
  'parametros' => 
  array (
    0 => 'fecha',
    1 => 'ra',
  ),
  'no_quote' => 'fecha',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_listado_turnos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_modalidades_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
    1 => 'ubicacion',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '30',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_periodo_preinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
    1 => 'ubicacion',
    2 => 'modalidad',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function preinscripcion__get_propuestas_a_excluir()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'persona',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_propuestas_disponibles_preinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
    1 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_propuestas_preinscripcion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_requisitos_persona_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
    2 => 'estado_req_digital',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'propuesta',
    2 => 'estado_req_digital',
  ),
  'param_null' => 
  array (
    0 => 'estado_req_digital',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_responsables_academicas()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_turno_presentacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'ra',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__get_ubicaciones_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '30',
  'filas' => 'n',
);
	}

	static function preinscripcion__get_where_ra()
	{
		return array (
);
	}

	static function preinscripcion__hay_periodo_preinsc_vigente()
	{
		return array (
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__hay_turnos_disponibles()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__hay_turnos_preinscripcion_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'responsable_academica',
    1 => 'ubicacion',
    2 => 'modalidad',
  ),
  'no_quote' => 
  array (
    0 => 'responsable_academica',
    1 => 'ubicacion',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__proximo_inicio_preinscripcion()
	{
		return array (
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function preinscripcion__reservar_turno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'fecha',
    2 => 'ra',
    3 => 'franja',
  ),
  'no_quote' => 
  array (
    0 => 'fecha',
    1 => 'franja',
  ),
  'cache' => 'no',
);
	}

	static function pruebas_stress__limpiar_datos_comisiones_1er_cuatrimestre_2015()
	{
		return array (
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function reasignar_optativa__agregar_asignacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
    2 => 'optativa',
    3 => 'generica',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
    2 => 'optativa',
    3 => 'generica',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function reasignar_optativa__lista_optativas_con_genericas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
    1 => 'alumno',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function reasignar_optativa__modificar_asignacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
    2 => 'optativa',
    3 => 'generica',
  ),
  'no_quote' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
    2 => 'optativa',
    3 => 'generica',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function reasignar_optativa__optativa_tiene_generica_asignada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'plan_version',
    2 => 'optativa',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function reglas_calculo__get_reglas_calculo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'filtro',
  ),
  'param_null' => 
  array (
    0 => 'filtro',
  ),
  'no_quote' => 'filtro',
  'cache' => 'memoria',
);
	}

	static function sistema__bloquear_simultaneidad_operaciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'operacion',
    2 => 'tiempo',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'operacion',
    2 => 'tiempo',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function sistema__cache_lista_alumnos_carrera()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function sistema__cache_lista_alumnos_solicitud_actualizacion()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function sistema__cache_lista_alumnos_ua()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function sistema__cache_lista_carreras()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function sistema__controles_activos_punto()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'operacion',
    2 => 'evento',
    3 => '_interfaz',
    4 => 'punto_control',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '120',
  'filas' => 'n',
);
	}

	static function sistema__desbloquear_simultaneidad_operaciones()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'operacion',
    2 => 'tiempo',
  ),
  'no_quote' => 
  array (
    0 => 'persona',
    1 => 'operacion',
    2 => 'tiempo',
  ),
  'cache' => 'no',
  'filas' => '0',
);
	}

	static function sistema__escala_notas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala_notas',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__fecha_actual()
	{
		return array (
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function sistema__fecha_actual_formato()
	{
		return array (
  'parametros' => 
  array (
    0 => 'formato',
  ),
  'cache' => 'memoria',
  'cache_expiracion' => '60',
  'filas' => '1',
);
	}

	static function sistema__get_dias_semana()
	{
		return array (
  'no_quote' => '',
  'cache' => 'memoria',
);
	}

	static function sistema__get_escalas_notas_activas()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__get_escalas_notas_resultados()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__get_resultados_posibles_escala_nota()
	{
		return array (
  'parametros' => 
  array (
    0 => 'escala_nota',
  ),
  'no_quote' => 'escala_nota',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__get_turnos_cursadas()
	{
		return array (
  'no_quote' => '',
  'cache' => 'memoria',
);
	}

	static function sistema__get_ubicaciones()
	{
		return array (
  'no_quote' => '',
  'cache' => 'memoria',
);
	}

	static function sistema__mensaje()
	{
		return array (
  'parametros' => 
  array (
    0 => 'mensaje',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function sistema__parametro()
	{
		return array (
  'parametros' => 
  array (
    0 => 'parametro',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__ubicaciones()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function sistema__version_base_g3()
	{
		return array (
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function solicitudes__get_cantidades()
	{
		return array (
  'parametros' => 
  array (
    0 => 'constancia',
    1 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function solicitudes__get_max_cant_copias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'constancia',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function solicitudes__get_solicitudes_activas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function solicitudes__get_solicitudes_historicas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function solicitudes__lista_constancias_activas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'param_null' => 
  array (
    0 => 'propuesta',
  ),
  'no_quote' => 'propuesta',
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function solicitudes__nueva_solicitud()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
    1 => 'constancia',
    2 => 'copias',
    3 => 'presentar',
    4 => 'observaciones',
    5 => 'interfaz',
    6 => 'estado',
    7 => 'codigo_verificacion',
    8 => 'formulario',
    9 => 'formulario_campo',
    10 => 'formulario_valor',
  ),
  'param_null' => 
  array (
    0 => 'codigo_verificacion',
    1 => 'formulario',
    2 => 'formulario_campo',
    3 => 'formulario_valor',
  ),
  'no_quote' => 'formulario_campo',
  'cache' => 'no',
);
	}

	static function unidad_academica__actividad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'actividad',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function unidad_academica__actividades()
	{
		return array (
  'parametros' => 
  array (
    0 => 'term',
    1 => 'plan_version',
  ),
  'param_null' => 
  array (
    0 => 'plan_version',
  ),
  'no_quote' => 'term',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__anios_academicos()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__datos_actividad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'actividad',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__docentes()
	{
		return array (
  'parametros' => 
  array (
    0 => '_ua',
    1 => 'term',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__get_escala_nota_defecto_actividad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'actividad',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__get_instancias()
	{
		return array (
  'parametros' => 
  array (
    0 => 'aplica_a',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__get_instancias_comisiones()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__get_instancias_mesas_examen()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__get_responsables_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__info_institucion()
	{
		return array (
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function unidad_academica__periodos_lectivos()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_anteriores()
	{
		return array (
  'parametros' => 
  array (
    0 => 'periodo_lectivo',
  ),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function unidad_academica__periodos_lectivos_con_fechas_insc()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_con_fechas_insc_x_coeficiente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_fecha_inactivacion_mayor_igual_fecha_actual()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_fecha_inactivacion_no_finalizada()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_todos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_un_anio_academico()
	{
		return array (
  'parametros' => 
  array (
    0 => 'anio_academico',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_un_anio_academico_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'anio_academico',
    1 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_vigentes()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_vigentes_ids()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__periodos_lectivos_vigentes_por_ra()
	{
		return array (
  'parametros' => 
  array (
    0 => 'responsable_academica',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__turnos_examen()
	{
		return array (
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function unidad_academica__turnos_examen_de_anios_academicos_con_turno_no_finalizado()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__turnos_examen_por_plan()
	{
		return array (
  'parametros' => 
  array (
    0 => 'plan_version',
  ),
  'cache' => 'memoria',
  'filas' => '1',
);
	}

	static function unidad_academica__turnos_examen_todos()
	{
		return array (
  'parametros' => 
  array (
    0 => 'docente',
  ),
  'param_null' => 
  array (
    0 => 'docente',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function unidad_academica__turnos_examen_vigentes()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_carreras()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_carreras_filtro()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
    1 => 'ubicacion',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_mesas_instancia()
	{
		return array (
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_planes_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'propuesta',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_responsables_academicas()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__get_ubicacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ra',
  ),
  'param_null' => 
  array (
    0 => 'ra',
  ),
  'no_quote' => 'ra',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__horarios_cursadas()
	{
		return array (
  'parametros' => 
  array (
    0 => 'periodo_lectivo',
    1 => 'elemento',
    2 => 'turno',
    3 => 'ubicacion',
    4 => 'dia',
    5 => 'propuesta',
    6 => 'anio_cursada',
    7 => 'responsable_academica',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__lista_actividades_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'materia',
    1 => 'propuesta',
    2 => 'plan',
    3 => 'persona',
  ),
  'param_null' => 
  array (
    0 => 'persona',
  ),
  'no_quote' => 'materia',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function usuario_anonimo__mesas_examen_usuario_anonimo()
	{
		return array (
  'parametros' => 
  array (
    0 => 'ubicacion',
    1 => 'propuesta',
    2 => 'plan',
    3 => 'elemento',
    4 => 'fecha_desde',
    5 => 'fecha_hasta',
    6 => 'instancia',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function zona_clases__get_propuesta()
	{
		return array (
  'parametros' => 
  array (
    0 => 'comision',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function zona_clases__subcomision_entidad()
	{
		return array (
  'parametros' => 
  array (
    0 => 'id',
  ),
  'cache' => 'no',
  'filas' => 'n',
);
	}

	static function zona_examenes__lista_mesas_examen_docente()
	{
		return array (
  'parametros' => 
  array (
    0 => 'persona',
    1 => 'filtrar_por',
    2 => 'fecha_examen',
    3 => 'fecha_examen_desde',
    4 => 'fecha_examen_hasta',
  ),
  'param_null' => 
  array (
    0 => 'filtrar_por',
    1 => 'fecha_examen',
    2 => 'fecha_examen_desde',
    3 => 'fecha_examen_hasta',
  ),
  'no_quote' => 
  array (
    0 => 'filtrar_por',
    1 => 'fecha_examen',
    2 => 'fecha_examen_desde',
    3 => 'fecha_examen_hasta',
  ),
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

}

?>