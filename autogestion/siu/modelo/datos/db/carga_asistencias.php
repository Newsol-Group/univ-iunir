<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\alumno_foto;

class carga_asistencias
{
	/**
	 * parametros: comision, clase, subcomision
	 * param_null: subcomision
	 * cache: no
	 * filas: n
	 */
	function clase_detalle($parametros)
	{
		// Listado de alumnos activos de una comision/subcomisión.
		$from_subcomision = '';
		if (!is_null($parametros['subcomision'])) {
			$from_subcomision = " JOIN sga_insc_subcomision ON (sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion AND  
                                 sga_insc_subcomision.subcomision = {$parametros['subcomision']} ) ";
		}

		$sql = "SELECT  
                      sga_insc_cursada.alumno,
                      sga_alumnos.legajo,
                      vw_personas.apellido_nombres AS apellido_y_nombres,
                      vw_personas.apellido_nombres_elegido AS apellido_y_nombres_elegido,
                      vw_personas.tipo_nro_documento AS documento_principal,
                      sga_insc_cursada.comision,
                      COALESCE(sga_clases_asistencia.cant_inasistencias, -1) as cant_inasistencias,
                      vw_personas.id_imagen as id_imagen,
                      COALESCE(sga_clases_asistencia_acum.libre,0) as libre,
					  sga_clases_asistencia.cant_justificadas as cant_justificadas,
					  sga_clases_asistencia.motivo_ausencia as motivo_ausencia,
					  sga_motivos_ausencias.nombre as motivo_ausencia_nombre,
					  sga_motivos_ausencias.descripcion as motivo_ausencia_descripcion,
					  sga_clases_asistencia.estado_justificacion as estado_justificacion,
					  sga_clases_asistencia.observaciones as observaciones
					  
                 FROM sga_insc_cursada
                 JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
                 JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
                 $from_subcomision
				 LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.alumno = sga_insc_cursada.alumno AND sga_clases_asistencia_acum.comision = sga_insc_cursada.comision) 
                 LEFT JOIN sga_clases_asistencia ON (sga_clases_asistencia.clase = {$parametros['clase']} AND sga_clases_asistencia.alumno = sga_insc_cursada.alumno) 
				 LEFT JOIN sga_motivos_ausencias ON (sga_motivos_ausencias.motivo_ausencia = sga_clases_asistencia.motivo_ausencia)
                WHERE sga_insc_cursada.comision = {$parametros['comision']}
                  AND sga_alumnos.calidad <> 'P'
                ORDER BY vw_personas.apellido_nombres_elegido
				";
				
		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $key => $value) {
			$datos[$key][catalogo::id] = catalogo::generar_id($value['alumno']);
			$datos[$key]['id_imagen'] = $value['id_imagen'];

			if (!empty($datos[$key]['id_imagen'])) {
				$datos[$key]['url_imagen'] = alumno_foto::url_imagen($datos[$key]['id_imagen']);
				$datos[$key]['url_imagen_mediana'] = alumno_foto::url_imagen($datos[$key]['id_imagen'], alumno_foto::TAMANIO_MEDIANA);
				$datos[$key]['url_imagen_grande'] = alumno_foto::url_imagen($datos[$key]['id_imagen'], alumno_foto::TAMANIO_GRANDE);
			} else {
				$datos[$key]['url_imagen'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm.png');
				$datos[$key]['url_imagen_mediana'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png');
				$datos[$key]['url_imagen_grande'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png');
			}
		}
		return $datos;
	}

	/**
	 * parametros: comision
	 * cache: memoria
	 * filas: 1
	 */
	function tipo_asistencia($parametros)
	{
		$sql = "SELECT      asistencia_por_horas, asistencia_cantidad_inasist
                FROM        sga_comisiones
                WHERE       sga_comisiones.comision = {$parametros['comision']}
                ";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: clase
	 * cache: no
	 * filas: 1
	 */
	function clase_cabecera($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		
		$sql = "SELECT 
                    vw_clases.clase,
                    to_char(vw_clases.fecha, $formato_fecha_visual) as fecha_formateada,
                    vw_clases.cantidad_horas_dictadas,
                    vw_clases.tipo_clase_nombre as tipo_clase,
                    to_char(vw_clases.hora_inicio, $formato_hora_visual) AS hora_inicio_formateada,
                    to_char(vw_clases.hora_finalizacion, $formato_hora_visual) AS hora_finalizacion_formateada,
                    vw_clases.comision_nombre,
                    vw_clases.comision,
                    vw_clases.dia_semana,
                    vw_comisiones.elemento_nombre as actividad_nombre,
                    vw_comisiones.elemento_codigo as actividad_codigo
                FROM 
                    vw_clases,
                    vw_comisiones
                WHERE 
                        vw_clases.comision = vw_comisiones.comision 
                    AND vw_clases.clase = {$parametros['clase']}";


		$datos = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * parametros: alumno, clase, cantidad, motivo
	 * param_null: motivo
	 * cache: no
	 * filas: 1
	 */
	function guardar($parametros)
	{
		$sql_valores_guardados = "
				SELECT	cant_inasistencias, motivo_ausencia 
                FROM	sga_clases_asistencia
                WHERE	clase = {$parametros['clase']}
						AND alumno = {$parametros['alumno']}
                ";
		$consulta_valores = kernel::db()->consultar_fila($sql_valores_guardados);

		if (!isset($parametros['motivo'])) {
			$parametros['motivo'] = "null";
		}

		if (!empty($consulta_valores)) {
			// update
			if ($consulta_valores['cant_inasistencias'] != $parametros['cantidad'] && $consulta_valores['motivo_ausencia'] != $parametros['motivo']) {
				$sql = "UPDATE 
							sga_clases_asistencia
						SET
							cant_inasistencias = {$parametros['cantidad']},
							motivo_ausencia = {$parametros['motivo']}
						WHERE
							clase = {$parametros['clase']}
							AND alumno = {$parametros['alumno']}";

				$consulta = kernel::db()->ejecutar($sql);
				return $consulta;
			}
		} else {
			// insert
			$sql = "INSERT INTO sga_clases_asistencia (clase, alumno, cant_inasistencias, motivo_ausencia, estado) VALUES
					({$parametros['clase']}, {$parametros['alumno']}, {$parametros['cantidad']}, {$parametros['motivo']}, 'A')
					";

			$consulta = kernel::db()->ejecutar($sql);
			return $consulta;
		}
	}

	/**
	 * parametros: comision, subcomision, fecha_desde, cantidad_clases, imprime_alumnos_libres, tipo_clase
	 * param_null: subcomision, tipo_clase
	 * cache: no
	 * filas: n
	 */
	function get_planilla($parametros)
	{
		// Recupera la planilla de asistencia a clases para las proximas clases de una comision/subcomision
		// Parámetros:
		//  + fecha_desde = Fecha a partir de la cual se consideraran las clases a imprimir.
		//  + cantidad_clases = cantidad de clases a imprimir. Próximas clases a partir de la fecha indicada
		//  + imprime_alumnos_libres = true (Imprime alumnos libres) / false (No incluye los alumnos libres por asistencia)
		//  + tipo_clase = Tipo de clase a imprimir (sga_clases_tipos).
		
		// Datos de la comision y subcomisoin
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$from_subcomision = '';
		$where_tipo_clase = '';
		$where_subcomision = '';
		$columns_subcomision = '';
		$horario_aulas = ", f_horario_comision_aula(sga_comisiones.comision, true) as horario_aulas ";
		$docentes = ", f_docentes_comision(sga_comisiones.comision, true) as docentes ";

		if (!is_null($parametros['subcomision'])) {
			$columns_subcomision = " sga_subcomisiones.subcomision as subcomision,
                                     sga_subcomisiones.nombre as subcomision_nombre,";
			$from_subcomision = " JOIN sga_subcomisiones ON sga_subcomisiones.subcomision = {$parametros['subcomision']} ";
			$horario_aulas = ", f_horario_subcomision_aula(sga_subcomisiones.subcomision, true) as horario_aulas ";
			$docentes = ", f_docentes_subcomision(sga_subcomisiones.subcomision, true) as docentes ";
		}
		$sql_cabecera = "SELECT
                            sga_periodos.nombre as periodo_lectivo_nombre, 
                            sga_periodos.anio_academico as anio_academico,
                            sga_comisiones.comision as comision,
                            sga_comisiones.nombre as comision_nombre,
                            $columns_subcomision
                            sga_elementos.codigo as actividad_codigo,
                            sga_elementos.nombre as actividad_nombre,
                            sga_catedras.nombre as catedra_nombre                            
                            $horario_aulas
                            $docentes                            
                      FROM sga_comisiones
                      JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
                      JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
                      JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
                      $from_subcomision
					  LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
                     WHERE  sga_comisiones.comision = {$parametros['comision']} 
                ";

		$consulta_cabecera = kernel::db()->consultar_fila($sql_cabecera);

		// ALUMNOS de la comision/subcomisión
		$from_subcomision = '';
		if (!is_null($parametros['subcomision'])) {
			$from_subcomision = " JOIN sga_insc_subcomision ON (sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion AND
                                    sga_insc_subcomision.subcomision = {$parametros['subcomision']} ) ";
		}
		$sql_alumnos = "SELECT
                            sga_alumnos.alumno as alumno,
                            sga_alumnos.legajo as alumno_legajo,
                            vw_personas.apellido_nombres as alumno_nombre,
                            vw_personas.apellido_nombres_elegido as alumno_nombre_elegido,
                            vw_personas.tipo_nro_documento AS nro_identificacion,
                            sga_propuestas.nombre_abreviado AS propuesta_nombre,
                            COALESCE(sga_clases_asistencia_acum.total_inasistencias, 0.00) as total_inasistencias,
                            sga_clases_asistencia_acum.total_justificadas,
                            sga_clases_asistencia_acum.total_inasistencias || ' (' || sga_clases_asistencia_acum.total_justificadas || ')' as total_inasist_just,
                            sga_clases_asistencia_acum.libre,
                            CASE
                                WHEN sga_clases_asistencia_acum.libre = 1 THEN 'Sí'
                                ELSE 'No'
                            END AS alumno_esta_libre
                      FROM  sga_insc_cursada
                      $from_subcomision
                      JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
                      JOIN sga_propuestas ON sga_propuestas.propuesta  = sga_alumnos.propuesta
                      JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
                      LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.alumno = sga_insc_cursada.alumno AND sga_clases_asistencia_acum.comision = sga_insc_cursada.comision)
                     WHERE sga_insc_cursada.comision = {$parametros['comision']} 
                       AND sga_alumnos.calidad <> 'P' 
                       AND ({$parametros['imprime_alumnos_libres']} = 'S' OR
                             ({$parametros['imprime_alumnos_libres']} = 'N' AND COALESCE(sga_clases_asistencia_acum.libre, 0) = 0)
                           ) 
                ORDER BY vw_personas.apellido_nombres_elegido
                ";
		$consulta_alumnos = kernel::db()->consultar($sql_alumnos);

		// Clases de la comision/subcomision
		$from_subcomision = '';
		if (!is_null($parametros['subcomision'])) {
			$from_subcomision = " JOIN sga_subcomisiones_bh ON (sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
                                    sga_subcomisiones_bh.subcomision = {$parametros['subcomision']} ) ";
		}

		$where_tipo_clase = '';
		if (!is_null($parametros['tipo_clase'])) {
			$where_tipo_clase = " AND sga_clases_tipos.tipo_clase = {$parametros['tipo_clase']}";
		}

		// Clases 
		$sql_clases = "SELECT
                   sga_clases.clase as clase,
                   sga_asignaciones.dia_semana as dia_semana,
                   COALESCE(to_char(sga_asignaciones.hora_inicio, 'HH:MM'),'') || ' - ' || COALESCE(to_char(sga_asignaciones.hora_finalizacion, 'HH:MM'), '') as hora_inicio_fin,
                   sga_clases.fecha as fecha_clase,
                   to_char(sga_clases.fecha, $formato_fecha_visual) as fecha_clase_visual,
                   sga_clases_tipos.nombre as tipo_clase_desc
              FROM sga_comisiones_bh
              JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
              $from_subcomision
              JOIN sga_clases ON sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
              JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
             WHERE sga_comisiones_bh.comision = {$parametros['comision']}
               AND sga_clases.valido = 1
               AND sga_clases.fecha >= to_date ({$parametros['fecha_desde']}, $formato_fecha_visual)
               $where_tipo_clase
            ORDER BY sga_clases.fecha
            LIMIT {$parametros['cantidad_clases']}
            ";
		$consulta_clases = kernel::db()->consultar($sql_clases);

		return array(
			'cabecera' => $consulta_cabecera,
			'alumnos' => $consulta_alumnos,
			'clases' => $consulta_clases
		);
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function get_tipos_clase()
	{
		$sql = "select tipo_clase as id, nombre as desc from sga_clases_tipos";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * parametros: comision, subcomision
	 * param_null: subcomision
	 * cache: memoria
	 * filas: n
	 */
	function get_tipos_clase_comision($parametros)
	{
		// Recupera tipos de Clase de una comision o subcomision		
		$from_subcomision = '';
		if (!is_null($parametros['subcomision'])) {
			$from_subcomision = " JOIN sga_subcomisiones_bh ON (sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
                                  sga_subcomisiones_bh.subcomision = {$parametros['subcomision']} ) ";
		}

		$sql = "SELECT DISTINCT sga_clases_tipos.tipo_clase as id, sga_clases_tipos.nombre as desc 
                  FROM sga_comisiones_bh
                  JOIN sga_clases_tipos	ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
                  $from_subcomision
                  WHERE sga_comisiones_bh.comision = {$parametros['comision']}
               ";

		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * parametros: comision
	 * cache: memoria
	 * filas: 1
	 */
	function tiene_banda_horaria_comision($parametros)
	{
		$sql = "SELECT count(*) AS cant_bh FROM sga_comisiones_bh WHERE comision={$parametros['comision']}";

		$fila = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
		$tiene_banda_horaria = ($fila['cant_bh'] > 0);
		return $tiene_banda_horaria;
	}

	/**
	 * parametros: subcomision
	 * cache: memoria
	 * filas: 1
	 */
	function tiene_banda_horaria_subcomision($parametros)
	{
		$sql = "SELECT count(*) AS cant_bh FROM sga_subcomisiones_bh WHERE subcomision={$parametros['subcomision']}";

		$fila = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
		$tiene_banda_horaria = ($fila['cant_bh'] > 0);
		return $tiene_banda_horaria;
	}

}