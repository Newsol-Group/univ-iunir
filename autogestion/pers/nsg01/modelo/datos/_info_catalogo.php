<?php
namespace nsg01\modelo\datos;

class _info_catalogo
{
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

	static function alumno__get_alumno()
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

	static function alumno__get_alumno_propuesta_cursos()
	{
		return array (
  'parametros' => array ('propuesta'),
  'param_null' => array ('propuesta'),
  'no_quote' => array ('propuesta'),
  'cache' => 'no',
  'filas' => '1',
);
	}

	static function alumno__get_datos_ctacte()
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

	static function carga_evaluaciones_parciales__alumnos_excluidos_agregar_evaluacion()
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

	static function carga_evaluaciones_parciales__sql_alumno_libre_en_comision()
	{
		return array (
);
	}

	static function carga_evaluaciones_parciales__sql_alumno_no_libre_en_comision()
	{
		return array (
);
	}

	static function carga_evaluaciones_parciales__sql_comision_evaluacion()
	{
		return array (
);
	}

	static function carga_evaluaciones_parciales__sql_origen_alumnos_agregar_evaluacion()
	{
		return array (
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

	static function datos_facturacion__actualizar_datos_facturacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'datos_facturacion',
  ),
  'cache' => 'no',
);
	}

	static function datos_facturacion__get_datos_facturacion_alumno()
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

	static function datos_facturacion__get_descr_empresa()
	{
		return array (
  'parametros' => 
  array (
    0 => 'empresa',
  ),
  'no_quote' => 'empresa',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_facturacion__get_descr_forma_pago()
	{
		return array (
  'parametros' => 
  array (
    0 => 'forma_pago',
  ),
  'no_quote' => 'forma_pago',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_facturacion__get_descr_medio_pago()
	{
		return array (
  'parametros' => 
  array (
    0 => 'medio_pago',
  ),
  'no_quote' => 'medio_pago',
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_facturacion__get_empresas()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_facturacion__get_formas_pago_alumno()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'no',
);
	}

	static function datos_facturacion__get_medios_pago()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
);
	}

	static function datos_facturacion__registrar_datos_facturacion()
	{
		return array (
  'parametros' => 
  array (
    0 => 'datos_facturacion',
  ),
  'cache' => 'no',
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

	static function docente__asistencia_alum_inscriptos_comision()
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

	static function persona__comparar_prioridad_default()
	{
		return array (
);
	}

	static function persona__es_propuesta_no_madre()
	{
		return array (
);
	}

	static function persona__get_prioridad_default()
	{
		return array (
);
	}

	static function persona__get_propuesta_en_carreras()
	{
		return array (
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

	static function persona__tiene_propuesta_madre()
	{
		return array (
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

	static function solicitudes__get_materias_inscripto()
	{
		return array (
  'parametros' => 
  array (
    0 => 'alumno',
  ),
  'no_quote' => 'alumno',
  'cache' => 'no',
);
	}

	static function solicitudes__get_motivos_baja()
	{
		return array (
  'cache' => 'memoria',
  'filas' => 'n',
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

	static function solicitudes__get_tipos_beneficios()
	{
		return array (
  'cache' => 'memoria',
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
  'cache' => 'memoria',
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
    7 => 'motivo_baja',
    8 => 'tipo_beneficio',
    9 => 'comision',
    10 => 'codigo_verificacion',
    11 => 'formulario',
    12 => 'formulario_campo',
    13 => 'formulario_valor',
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

}

?>
