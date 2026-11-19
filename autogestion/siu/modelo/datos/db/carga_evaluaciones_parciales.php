<?php

namespace siu\modelo\datos\db;

use siu\modelo\datos\catalogo;
use siu\modelo\datos\util;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\entidades\alumno_foto;

class carga_evaluaciones_parciales
{
	/**
	 * parametros: docente, comision, subcomision
	 * param_null: comision, subcomision
	 * no_quote: subcomision
	 * cache: no
	 * cache_expiracion: 3600
	 * filas: n
	 */
	function listado_evaluaciones_parciales($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$resultado_ausente = kernel::db()->quote(\escalas_notas::resultado_ausente);

		$where = isset($parametros['comision']) && $parametros['comision'] != null ? "AND vw_comisiones.comision = {$parametros['comision']}" : '';

		//si viene una subco genero el id de la fila de la zona (comision o comision_subocomision)
		$subco_zona = isset($parametros['subcomision']) && (int) $parametros['subcomision'] ?
				'_' . $parametros['subcomision'] : '';

		// Listado de evaluaciones parciales de comisiones en las que esta el docente
		// Puede haber comisiones sin evaluaciones parciales
		$sql = " -- Comisiones del docente con evaluaciones parciales
		    SELECT
		    vw_comisiones.comision || '_' || sga_evaluaciones.evaluacion as id_fila,
			vw_comisiones.comision || '$subco_zona' as id, --zona
			'${parametros['subcomision']}' as subcomision,
			vw_comisiones.elemento as actividad,
			vw_comisiones.entidad,
			vw_comisiones.elemento_codigo as actividad_codigo,
			vw_comisiones.elemento_nombre as actividad_nombre,
			vw_comisiones.comision as comision,
			vw_comisiones.comision_nombre as comision_nombre,
			vw_comisiones.anio_academico as anio_academico,
			vw_comisiones.periodo_lectivo as periodo_lectivo,
			vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
			vw_comisiones.catedra as catedra,
			sga_catedras.nombre as catedra_nombre,
			sga_turnos_cursadas.nombre as turno_cursada_nombre,
			vw_comisiones.ubicacion as ubicacion,
			sga_ubicaciones.nombre as ubicacion_nombre,
			(SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = vw_comisiones.comision) as cant_inscriptos,
			(SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = vw_comisiones.comision AND estado = 'A') as cant_inscriptos_aceptados,
			(SELECT COUNT(1) FROM sga_eval_detalle WHERE evaluacion = sga_evaluaciones.evaluacion AND resultado IS NOT NULL) as cant_con_notas,
			(SELECT COUNT(*) FROM sga_eval_detalle WHERE evaluacion = sga_evaluaciones.evaluacion) as cant_alumnos,
			(SELECT cast( SUM(CASE WHEN ((nota IS NOT NULL) OR (nota IS NULL AND resultado = $resultado_ausente)) THEN 1 ELSE 0 END) * 100.00 / COUNT(*) as decimal(5,2))
				FROM sga_eval_detalle WHERE evaluacion = sga_evaluaciones.evaluacion) as porcentaje_carga,
			sga_evaluaciones.evaluacion,
			sga_evaluaciones.nombre as evaluacion_nombre,
			sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
			to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as evaluacion_fecha,
			sga_evaluaciones.estado as estado,
			sga_evaluaciones_estados.nombre as estado_nombre,
			sga_evaluaciones.escala_nota as evaluacion_escala_nota
			
			FROM sga_docentes_comision
			JOIN vw_comisiones ON vw_comisiones.comision = sga_docentes_comision.comision
			JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_comisiones.entidad
			JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
			JOIN sga_evaluaciones_estados ON sga_evaluaciones_estados.estado = sga_evaluaciones.estado
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
			LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
			
			WHERE sga_docentes_comision.docente = {$parametros['docente']}
			  AND vw_comisiones.fecha_inactivacion >= CURRENT_DATE
              AND sga_evaluaciones_tipos.automatica = 'N' 
              AND sga_evaluaciones_tipos.aplica_a = 'C' 
			$where
			  -- No mostrar comisiones con actas de cursadas o promocion cerrada
			  -- AND NOT EXISTS (SELECT 1 FROM sga_actas WHERE comision = vw_comisiones.comision AND estado = 'C')

           UNION ALL
           -- Comisiones del docente sin evaluaciones parciales 
           SELECT
            vw_comisiones.comision || '_' as id_fila,
            vw_comisiones.comision || '$subco_zona' as id, --zona
            '${parametros['subcomision']}' as subcomision,
			vw_comisiones.elemento as actividad,
			vw_comisiones.entidad,
			vw_comisiones.elemento_codigo as actividad_codigo,
			vw_comisiones.elemento_nombre as actividad_nombre,
			vw_comisiones.comision as comision,
			vw_comisiones.comision_nombre as comision_nombre,
			vw_comisiones.anio_academico as anio_academico,
			vw_comisiones.periodo_lectivo as periodo_lectivo,
			vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
			vw_comisiones.catedra as catedra,
			sga_catedras.nombre as catedra_nombre,
			sga_turnos_cursadas.nombre as turno_cursada_nombre,
			vw_comisiones.ubicacion as ubicacion,
			sga_ubicaciones.nombre as ubicacion_nombre,
			(SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = vw_comisiones.comision) as cant_inscriptos,
			(SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = vw_comisiones.comision AND estado = 'A') as cant_inscriptos_aceptados,
			0.00 as porcentaje_carga,
			cast(null as integer) as cant_con_notas,
			cast(null as integer) as cant_alumnos,
			cast(null as integer) as evaluacion,
			cast(null as varchar(100)) as evaluacion_nombre,
			cast(null as varchar(50))  as evaluacion_tipo_nombre,
			cast(null as varchar(10)) as evaluacion_fecha,
			cast(null as char(1)) as estado,
			cast(null as varchar(20)) as estado_nombre,
			cast(null as integer) as evaluacion_escala_nota
			
			FROM sga_docentes_comision
			JOIN vw_comisiones ON vw_comisiones.comision = sga_docentes_comision.comision
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
			LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
			
			WHERE sga_docentes_comision.docente = {$parametros['docente']} 
			  AND vw_comisiones.fecha_inactivacion >= CURRENT_DATE
			  -- No mostrar comisiones con actas de cursadas o promocion cerrada
			  -- AND NOT EXISTS (SELECT 1 FROM sga_actas WHERE comision = vw_comisiones.comision AND estado = 'C')
			  AND NOT EXISTS (SELECT 1 FROM sga_evaluaciones, sga_evaluaciones_tipos
			                   WHERE sga_evaluaciones.entidad = vw_comisiones.entidad
				                 AND sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				                 AND sga_evaluaciones_tipos.automatica = 'N' 
				                 AND sga_evaluaciones_tipos.aplica_a = 'C' )
			$where
			ORDER BY 6, 7, 8
		 ";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
//		foreach($datos as $key => $value)
//		{
//			$datos[$key]['comision_url'] = catalogo::generar_id($value['comision']);
//			$datos[$key][catalogo::id] = isset($datos[$key]['evaluacion']) ? catalogo::generar_id($value['comision'].$value['evaluacion']) : '';
//		}
		return $datos;
	}

	/**
	 * parametros: comision, subcomision
	 * param_null: subcomision
	 * no_quote: subcomision
	 * cache: no
	 */
	function comision_cabecera($parametros)
	{
		//si viene una subco genero el id de la fila de la zona (comision o comision_subocomision)
		$subco_zona = isset($parametros['subcomision']) && (int) $parametros['subcomision'] ? '_' . $parametros['subcomision'] : '';

		$sql = "SELECT
					vw_comisiones.comision || '$subco_zona' as id, --zona
					vw_comisiones.comision,
					vw_comisiones.entidad,
					'${parametros['subcomision']}' as subcomision,
					vw_comisiones.elemento_nombre as actividad_nombre,
					vw_comisiones.elemento_codigo as actividad_codigo,
					vw_comisiones.comision_nombre as comision_nombre

				FROM    vw_comisiones
				WHERE
						vw_comisiones.comision = {$parametros['comision']}
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: comision, escala_asignada
	 * no_quote: comision, escala_asignada
	 * cache: memoria
	 * filas: n
	 */
	function listado_escala_notas($parametros)
	{
		$propuestas = \toba::consulta_php('co_comisiones')->get_propuestas_comision($parametros['comision']);
		$ids_propuestas = array_column($propuestas, "propuesta");
		return \toba::consulta_php('co_escalas_notas')->get_escala_notas_evaluaciones_comision($ids_propuestas, $parametros['escala_asignada']);
	}

	/**
	 * parametros:
	 * cache: memoria
	 * filas: n
	 */
	function listado_tipo_evaluacion($parametros = null)
	{
		$sql = "SELECT evaluacion_tipo, nombre, descripcion 
		          FROM sga_evaluaciones_tipos 
		          WHERE aplica_a = 'C' AND automatica = 'N' 
		          ORDER BY 2
		       ";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * Alta de una evaluacion parcial en una comision.
	 * parametros: comision, nombre, descripcion, evaluacion_tipo, visible_al_alumno, promediable, fecha, hora_inicio, hora_fin, escala_nota, instancias, eval_relacionadas
	 * param_null: descripcion, hora_fin, instancias, eval_relacionadas
	 * cache: no
	 * filas: 1
	 */
	function alta_evaluacion_parcial($parametros)
	{
		$descripcion = isset($parametros['descripcion']) ? $parametros['descripcion'] : 'NULL';
		$hora_fin = isset($parametros['hora_fin']) ? $parametros['hora_fin'] : 'NULL';
		$array_instancias = catalogo::array_to_postgres($parametros['instancias']) . '::integer[]';
		$array_eval_relacionadas = catalogo::array_to_postgres($parametros['eval_relacionadas']) . '::integer[]';
		$generacion = kernel::db()->quote('D'); // Evaluacion generada por el docente.
		
		$sql = "SELECT * FROM f_evaluacion_alta(
			{$parametros['comision']},
			{$parametros['nombre']},
			$descripcion,
			{$parametros['evaluacion_tipo']},
			{$parametros['fecha']},
			{$parametros['hora_inicio']},
			$hora_fin,
			{$parametros['escala_nota']},
			{$parametros['visible_al_alumno']},
			{$parametros['promediable']},
			$array_instancias,
			$array_eval_relacionadas,
			$generacion)
		";
		return kernel::db()->consultar_fila($sql, db::FETCH_NUM);
	}

	/**
	 * parametros: evaluacion, nombre, descripcion, evaluacion_tipo, fecha, hora_inicio, hora_fin, escala_nota, visible_al_alumno, promediable, flag_instancias, instancias, flag_relacionadas, eval_relacionadas
	 * param_null: descripcion, hora_fin, instancias, eval_relacionadas
	 * no_quote: evaluacion, flag_instancias, flag_relacionadas
	 * cache: no
	 * filas: 1
	 */
	function modificar_evaluacion_parcial($parametros)
	{

		// Si existen alumnos para dicha evaluación, no permito la modificación de los campos Fecha, Tipo, Escala y Visible.
		$datos_evaluacion = catalogo::consultar('carga_evaluaciones_parciales', 'evaluacion_cabecera', array('evaluacion' => $parametros['evaluacion']));
		if ($datos_evaluacion['cant_alumnos'] > 0) {
			//$parametros['fecha']				= kernel::db()->quote($datos_evaluacion['evaluacion_fecha']);
			$parametros['evaluacion_tipo']		= kernel::db()->quote($datos_evaluacion['evaluacion_tipo']);
			$parametros['escala_nota']			= kernel::db()->quote($datos_evaluacion['escala_nota']);
			$parametros['visible_al_alumno']	= kernel::db()->quote($datos_evaluacion['visible_al_alumno']);
		}

		$parametros['evaluacion'] = kernel::db()->quote($parametros['evaluacion']);

		$descripcion = isset($parametros['descripcion']) ? $parametros['descripcion'] : 'NULL';
		$hora_fin = isset($parametros['hora_fin']) ? $parametros['hora_fin'] : 'NULL';
		$flag_instancias = $parametros['flag_instancias'] == 1 ? 'TRUE' : 'FALSE';
		$array_instancias = catalogo::array_to_postgres($parametros['instancias']) . '::integer[]';
		$flag_relacionadas = $parametros['flag_relacionadas'] == 1 ? 'TRUE' : 'FALSE';
		$array_eval_relacionadas = (!empty($parametros['eval_relacionadas'])) ? catalogo::array_to_postgres($parametros['eval_relacionadas']) . '::integer[]' : 'null';
		$sql = "SELECT * FROM f_evaluacion_modificacion(
			{$parametros['evaluacion']},
			{$parametros['nombre']},
			$descripcion,
			{$parametros['evaluacion_tipo']},
			{$parametros['fecha']},
			{$parametros['hora_inicio']},
			$hora_fin,
			{$parametros['escala_nota']},
			{$parametros['visible_al_alumno']},
			{$parametros['promediable']},
			$flag_instancias,
			$array_instancias,
			$flag_relacionadas,
			$array_eval_relacionadas)
		";
		return kernel::db()->consultar_fila($sql, db::FETCH_NUM);
	}

	/**
	 * parametros: evaluacion
	 * cache: no
	 * filas: 1
	 */
	function baja_evaluacion_parcial($parametros)
	{
		// Da de baja la evaluacion parcial.	
		$sql = "SELECT * FROM f_evaluacion_baja ({$parametros['evaluacion']})";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);

		// Si datos.resultado = 1 OK, -1 = ERROR

		return $datos;
	}

	//---------------------------------------------
	//	CARGA NOTAS
	//---------------------------------------------		
	/**
	 * parametros: escala_notas
	 * cache: memoria
	 * filas: n
	 */
	function escala_notas($parametros)
	{
		//$sql = "EXECUTE PROCEDURE sp_det_escala_parc({$parametros['escala_notas']})";
		// Mostrar la escala de notas como se hace en examenes/promociones/cursadas
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}

	/**
	 * parametros: comision
	 * cache: memoria
	 * filas: n
	 */
	function lista_evaluaciones_comision($parametros)
	{
		// Lista las evaluaciones que ya estan creadas en la comsion, para seleccionar cuales relaciona con la evaluacion a crear
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT
					sga_evaluaciones.evaluacion as evaluacion,
					sga_evaluaciones.nombre as evaluacion_nombre,
					sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
					sga_evaluaciones.fecha as fecha,
					to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as evaluacion_fecha,
					sga_evaluaciones.estado as estado,
					sga_evaluaciones_estados.nombre as estado_nombre,
					sga_escalas_notas.nombre as escala_nota_nombre
					
				FROM sga_comisiones
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = sga_comisiones.entidad
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN sga_evaluaciones_estados ON sga_evaluaciones_estados.estado = sga_evaluaciones.estado
				LEFT JOIN sga_escalas_notas ON sga_escalas_notas.escala_nota = sga_evaluaciones.escala_nota
				
				WHERE sga_comisiones.comision = {$parametros['comision']}
				  AND sga_evaluaciones_tipos.automatica = 'N'
			   ORDER BY sga_evaluaciones.fecha
		 		";

		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		Return $datos;
	}

	/**
	 * parametros: comision
	 * cache: memoria
	 * filas: n
	 */
	function lista_instancias_comision($parametros)
	{
		// Lista las instancias de la comsion
		$sql = "SELECT
				si.instancia,
				si.nombre AS instancia_nombre,
				si.orden AS instancia_orden,
				sen.escala_nota,
				sen.nombre AS escala_nota_nombre,
				sci.porc_asistencia
			FROM sga_comisiones_instancias AS sci
			INNER JOIN sga_instancias AS si ON sci.instancia = si.instancia
			INNER JOIN sga_escalas_notas AS sen ON sci.escala_nota = sen.escala_nota
			WHERE sci.comision = {$parametros['comision']}
			ORDER BY 1, 3";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: evaluacion
	 * cache: no
	 * filas: n
	 */
	function evaluacion_cabecera($parametros)
	{
		//$sql = "execute procedure sp_eval_cabecera(	{$parametros['comision']},
		// $sql = "execute procedure sp_eval_parc_alum(	{$parametros['comision']}, parametros['evaluacion']})";
		// Recupera datos de la cabecera de la evaluaciï¿½n
		// Listado de evaluaciones parciales de comisiones en las que esta el docente
		// Puede haber comisiones sin evaluaciones parciales
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);

		$sql = "SELECT
					sga_evaluaciones.evaluacion as evaluacion,
					sga_evaluaciones.nombre as evaluacion_nombre,
					sga_evaluaciones.descripcion as descripcion,
					sga_evaluaciones.evaluacion_tipo as evaluacion_tipo,
					sga_evaluaciones_tipos.nombre as evaluacion_tipo_nombre,
					to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as evaluacion_fecha,
					to_char(CURRENT_DATE + sga_evaluaciones.hora_inicio, $formato_hora_visual) as evaluacion_hora_inicio,
					to_char(CURRENT_DATE + sga_evaluaciones.hora_fin, $formato_hora_visual) as evaluacion_hora_fin,
					sga_evaluaciones.estado as estado,
					sga_evaluaciones_estados.nombre as estado_nombre,
					to_char(sga_evaluaciones.fecha_cierre, $formato_fecha_visual) as fecha_cierre,
					sga_evaluaciones.escala_nota,
					sga_evaluaciones.visible_al_alumno,
					sga_evaluaciones.promediable,
					sga_escalas_notas.nombre as escala_nota_nombre,
					sga_escalas_notas.tipo as escala_nota_tipo,
					vw_comisiones.elemento as actividad,
					vw_comisiones.elemento_codigo as actividad_codigo,
					vw_comisiones.elemento_nombre as actividad_nombre,
					vw_comisiones.comision as comision,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.anio_academico as anio_academico,
					vw_comisiones.periodo_lectivo as periodo_lectivo,
					vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
					vw_comisiones.catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					sga_turnos_cursadas.nombre as turno_cursada_nombre,
					vw_comisiones.ubicacion as ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					vw_comisiones.entidad,
					(SELECT COUNT(*) FROM sga_eval_detalle WHERE evaluacion = sga_evaluaciones.evaluacion AND nota IS NOT NULL) as cant_con_notas,
					(SELECT COUNT(*) FROM sga_eval_detalle WHERE evaluacion = sga_evaluaciones.evaluacion) as cant_alumnos
					
				FROM sga_evaluaciones
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN sga_evaluaciones_estados ON sga_evaluaciones_estados.estado = sga_evaluaciones.estado
				JOIN vw_comisiones ON vw_comisiones.entidad = sga_evaluaciones.entidad
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
				LEFT JOIN sga_escalas_notas ON sga_escalas_notas.escala_nota = sga_evaluaciones.escala_nota
				LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
				
				WHERE sga_evaluaciones.evaluacion = {$parametros['evaluacion']}
		 		";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: comision, evaluacion, subcomision
         * param_null: subcomision
         * no_quote: comision, subcomision
	 * cache: no
	 * filas: n
	 */
	function evaluacion_detalle($parametros)
	{
		// G2 -> $sql = "execute procedure sp_eval_parc_alum(	{$parametros['comision']},{$parametros['evaluacion']})";
		$formato_fecha_hora_visual_milisegundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_milisegundos);

                $filtro_subcomision = "";
                // Si es una subcomisión
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = carga_notas_cursada_comision::filtro_subcomision('sga_eval_detalle.alumno', $parametros['subcomision']);
                }

		$sql = "SELECT 
					-- Datos del alumno
					vw_personas.persona as persona,
					vw_personas.id_imagen as id_imagen,
					sga_alumnos.legajo as legajo,
					vw_personas.apellido_nombres as alumno_nombre,
					vw_personas.apellido_nombres_elegido as apellido_nombres_elegido,
					vw_personas.tipo_nro_documento as tipo_nro_documento,
					
					-- Datos de la evaluacion
					sga_eval_detalle.evaluacion as evaluacion,
					sga_eval_detalle.alumno as alumno,
					sga_eval_detalle.folio as folio,
					sga_eval_detalle.renglon as renglon,
					sga_evaluaciones.fecha as fecha,
					sga_eval_detalle.escala_nota as escala_nota,
					sga_eval_detalle.nota as nota,
					sga_escalas_notas_det.descripcion as nota_nombre,
					sga_eval_detalle.resultado as resultado,
					sga_escalas_notas_resultado.descripcion as resultado_nombre,
					sga_eval_detalle.corregido_por as corregido_por,
					sga_eval_detalle.observaciones as observaciones,
					sga_eval_detalle.rectificado as rectificado,
					to_char(sga_eval_detalle.ultimo_cambio, $formato_fecha_hora_visual_milisegundos) as ultimo_cambio
					
			FROM sga_eval_detalle
			JOIN sga_evaluaciones ON sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion
			JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno
			JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND 
			                                    sga_escalas_notas_det.nota = sga_eval_detalle.nota)
			LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
			
			WHERE sga_eval_detalle.evaluacion = {$parametros['evaluacion']}
			$filtro_subcomision
			ORDER BY vw_personas.apellido_nombres   
		";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $id => $dato) {
			if (!empty($dato['id_imagen'])) {
				$datos[$id]['url_imagen'] = alumno_foto::url_imagen($datos[$id]['id_imagen']);
				$datos[$id]['url_imagen_grande'] = alumno_foto::url_imagen($datos[$id]['id_imagen'], alumno_foto::TAMANIO_GRANDE);
			} else {
				$datos[$id]['url_imagen'] = kernel::vinculador()->vinculo_recurso('/img/iconos/mm.png');
				$datos[$id]['url_imagen_grande'] = kernel::vinculador()->vinculo_recurso('/img/iconos/mm_grande.png');
			}
		}
		return $datos;
	}

	/**
	 * parametros: evaluacion, alumno, fecha, nota, resultado, corregido_por, observaciones, ultimo_cambio
	 * cache: no
	 * param_tipo: int, int, str/10, str/10, str/1, str/50, str/100, str/23
	 * param_null: corregido_por, observaciones, nota, resultado
	 * filas: 1
	 */
	function guardar_renglon($parametros)
	{
		$parametros = util::formato_null($parametros);
		//$sql = " EXECUTE PROCEDURE sp_upt_evalParcAlu(...)";
		// El parametro "fecha" debe venir en formato "dd/mm/yyyy"
		// El parametro "ultimo" debe venir en formato string "dd/mm/yyyy hh:mm:ss.sss" con milisegundos
		// Actualiza los datos de la evaluacion parcial de un alumno.
		// Si no encontró filas es porque el alumno no existe en la evaluacion o fue modificado por otro usuario desde que se levantaron los datos
		// hasta que se modifico el registr
		// $formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		/* $sql = "UPDATE sga_eval_detalle
		  SET 
		  nota          = {$parametros['nota']},
		  resultado     = {$parametros['resultado']},
		  corregido_por = {$parametros['corregido_por']},
		  observaciones = {$parametros['observaciones']}
		  WHERE evaluacion  = {$parametros['evaluacion']}
		  AND alumno        = {$parametros['alumno']}
		  AND to_char(ultimo_cambio, 'DD/MM/YYYY HH24:MI:SS.MS') = {$parametros['ultimo_cambio']}";
		 */
		$sql = "SELECT * FROM f_evaluacion_modificar_nota({$parametros['evaluacion']},
		                                                   {$parametros['alumno']},
		                                                   {$parametros['fecha']},
		                                                   {$parametros['nota']},
		                                                   {$parametros['resultado']}, 
		                                                   {$parametros['corregido_por']}, 
		                                                   {$parametros['observaciones']},
		                                                   {$parametros['ultimo_cambio']}
		                                                  )";

		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: evaluacion, escala_nota, param_alumnos, alumno, subcomision, resultado, evaluacion_origen
	 * param_null: alumno, subcomision, resultado, evaluacion_origen
	 * cache: no
	 * filas: n
	 */
	function agregar_alumnos_a_evaluacion($parametros)
	{
		/*
		  La evaluacion, param_alumnos y escala_nota debe pasarse siempre.
		  param_buscar_alumnos:
		  - ALUMNO      = Inserta el alumno pasado por parámetro (alumno)
		  - COMISION    = Todos los Alumnos de la comision (comision)
		  - SUBCOMISION = Todos los Alumnos de una subcomision. Viene el dato en subcomision
		  - EVALUACION  = Alumnos de una evaluacion. Vienen los datos en evaluacion_origen y resultado
		 */
		$sql_not_exists = " (SELECT alumno FROM sga_eval_detalle WHERE evaluacion = {$parametros['evaluacion']})";
		if ($parametros['param_alumnos'] == "'ALUMNO'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					VALUES ({$parametros['alumno']}, {$parametros['evaluacion']}, {$parametros['escala_nota']})";
		} elseif ($parametros['param_alumnos'] == "'COMISION'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_eval_detalle_cursadas.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_evaluaciones,
							sga_evaluaciones as eval_comision,
							sga_evaluaciones_tipos,
							sga_eval_detalle_cursadas
					WHERE	sga_evaluaciones.evaluacion = {$parametros['evaluacion']} AND
							eval_comision.entidad = sga_evaluaciones.entidad AND
							sga_evaluaciones_tipos.evaluacion_tipo = eval_comision.evaluacion_tipo AND
							sga_evaluaciones_tipos.automatica = 'S' AND
							sga_eval_detalle_cursadas.evaluacion = eval_comision.evaluacion AND
							sga_eval_detalle_cursadas.alumno NOT IN $sql_not_exists
			";
		} elseif ($parametros['param_alumnos'] == "'SUBCOMISION'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_insc_cursada.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_insc_subcomision,
							sga_insc_cursada
					WHERE	sga_insc_subcomision.subcomision = {$parametros['subcomision']} AND
							sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion AND
							sga_insc_cursada.estado = 'A' AND
							sga_insc_cursada.alumno NOT IN $sql_not_exists
			";
		} elseif ($parametros['param_alumnos'] == "'EVALUACION'") {
			$sql_resultado = '';
			if ($parametros['resultado'] == "'X'") {
				// Ausentes y desaprobados
				$sql_resultado = " AND sga_eval_detalle.resultado IN ('R','U') ";
			}
			if ($parametros['resultado'] == "'A'" || $parametros['resultado'] == "'R'" || $parametros['resultado'] == "'U'" ) {	
				// Aprobados, Desaprobados o Ausentes.
				$sql_resultado = " AND sga_eval_detalle.resultado = ".$parametros['resultado'];
			}

			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_eval_detalle.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_eval_detalle
					WHERE	sga_eval_detalle.evaluacion = {$parametros['evaluacion_origen']}
							$sql_resultado AND
							sga_eval_detalle.alumno NOT IN $sql_not_exists
			";
		}
		return kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: evaluacion, alumno, ultimo_cambio
	 * cache: no
	 * filas: 1
	 */
	function sacar_alumno_de_evaluacion($parametros)
	{
		// El parametro "ultimo" debe venir en formato string "dd/mm/yyyy hh:mm:ss.sss" con milisegundos
		// Saca el alumno de la evaluaciÃ³n
		// Si no devuelve filas es porque el alumno ya fue eliminado o fue modificado entre que el usuario levanto los datos de este alumno
		// hasta que el usuario confirmÃ³ la eliminacion del alumno de la evaluacion.
		$formato_fecha_hora_visual_milisegundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_milisegundos);

		$sql = "DELETE FROM sga_eval_detalle
				WHERE evaluacion = {$parametros['evaluacion']}
					AND alumno = {$parametros['alumno']}
					AND to_char(ultimo_cambio, $formato_fecha_hora_visual_milisegundos) = {$parametros['ultimo_cambio']}";
		return kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: comision, docente
         * param_null: docente
         * no_quote: docente
	 * cache: no
	 * filas: n
	 */
	function lista_subcomisiones_comision($parametros)
	{
                $filtro_docente = "";
                if (!is_null($parametros['docente'])) {
                    $filtro_docente = "AND sga_subcomisiones.subcomision IN (SELECT sga_docentes_subcomision.subcomision FROM sga_docentes_subcomision WHERE sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision AND sga_docentes_subcomision.docente = {$parametros['docente']})";
                }
                
		$sql = "SELECT
				sga_subcomisiones.subcomision as subcomision,
				sga_subcomisiones.nombre as nombre,
				sga_clases_tipos.nombre as tipo_clase,
				f_docentes_subcomision(sga_subcomisiones.subcomision, false) as docentes,
				f_horario_subcomision(sga_subcomisiones.subcomision) as horario
				FROM sga_subcomisiones, sga_clases_tipos
			WHERE sga_subcomisiones.comision = {$parametros['comision']}
			  AND sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
                          $filtro_docente
			ORDER BY tipo_clase, nombre";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: comision, subcomision
         * param_null: subcomision
	 * no_quote: subcomision
	 * cache: no
	 * filas: n
	 */
	function buscar_alumnos_comision($parametros)
	{
		$sql = "SELECT sga_evaluaciones.evaluacion
				FROM sga_comisiones, sga_evaluaciones, sga_evaluaciones_tipos
				WHERE sga_comisiones.comision = {$parametros['comision']}
				AND sga_evaluaciones.entidad = sga_comisiones.entidad
				AND sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				AND sga_evaluaciones_tipos.automatica = '".\comunes::si."'";
		$datos = kernel::db()->consultar_fila($sql);

                $filtro_subcomision = "";
                // Si es una subcomisión
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = carga_notas_cursada_comision::filtro_subcomision('sga_alumnos.alumno', $parametros['subcomision']);
                }

		$sql2 = "SELECT
					sga_alumnos.alumno as alumno,
					vw_personas.apellido_nombres_elegido as alumno_nombre,
					sga_alumnos.legajo as legajo
				FROM sga_eval_detalle_cursadas
				JOIN sga_alumnos ON (sga_alumnos.alumno = sga_eval_detalle_cursadas.alumno)
				JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				WHERE sga_eval_detalle_cursadas.evaluacion = {$datos['evaluacion']}
				AND (f_limpiar_acentos(vw_personas.apellido_nombres_elegido) ILIKE '%' || {$parametros['term']} || '%' OR
				sga_alumnos.legajo ILIKE '%' || {$parametros['term']} || '%')
                                $filtro_subcomision";
                                
		return kernel::db()->consultar($sql2);

		/*
		 * 
		 * $term = kernel::db()->quote_like($term_sin_quotes, '%', '%');
		  $where = " AND (f_limpiar_acentos(vw_personas.apellido_nombres) ILIKE $term OR
		  f_limpiar_acentos(sga_alumnos.legajo)	ILIKE $term OR
		  f_limpiar_acentos(vw_personas.nro_documento)	ILIKE $term )";
		 */
	}

	/**
	 * parametros: evaluacion
	 * cache: no
	 * filas: n
	 */
	function lista_relacionadas($parametros)
	{
		$sql = "SELECT evaluacion_padre FROM sga_evaluaciones_relacion WHERE evaluacion_hijo = {$parametros['evaluacion']}";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: evaluacion
	 * cache: no
	 * filas: n
	 */
	function lista_instancias($parametros)
	{
		$sql = "SELECT instancia FROM sga_evaluaciones_instancias WHERE evaluacion = {$parametros['evaluacion']}";
		return kernel::db()->consultar($sql);
	}

}