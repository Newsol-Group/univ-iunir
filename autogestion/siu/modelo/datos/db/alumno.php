<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use siu\modelo\_comun\abm;
use siu\modelo\_comun\clienteRDI;

class alumno
{
	/**
	 * parametros: alumno
	 * param_null: 
	 * cache: memoria
	 * filas: 1
	 */
	function es_ingresante_periodos_insc_cursada($parametros)
	{
		// Verifica si el alumno es ingresante en la fecha actual en base a los periodos de inscripcion vigentes para cursadas
		$alumno = filter_var($parametros['alumno'], FILTER_SANITIZE_NUMBER_INT);
		$sql = "SELECT * FROM f_es_ingresante_periodos_insc_cursada($alumno) as es_ingresante";
		$rs = kernel::db()->consultar_fila($sql);
		return ($rs['es_ingresante'] == 1);
	}

	/**
	 * parametros: alumno, anio_academico
	 * param_null: anio_academico
	 * cache: memoria
	 * filas: 1
	 */
	function es_ingresante($parametros)
	{
		// Verifica si es ingresante en base a el año academico pasado por parametro.
		// 1 = Es ingresante en el año academico pasado por parametro
		// 2 = El año academico pasado por parametro es anterior al año academico de ingreso. Lo consideramos ingresante por como y para lo que se usa este métod
		$alumno = filter_var($parametros['alumno'], FILTER_SANITIZE_NUMBER_INT);
		$anio_academico = is_null($parametros['anio_academico']) ? 'null' : $parametros['anio_academico'];
		$sql = "SELECT * FROM f_es_ingresante($alumno, $anio_academico) AS es_ingresante";
		$rs = kernel::db()->consultar_fila($sql);
		return ($rs['es_ingresante'] >= 1);
	}

	/**
	 * parametros: alumno
	 * param_null: 
	 * cache: memoria
	 * filas: 1
	 */
	function fecha_cierre_requisitos_ingreso_mayor_a_hoy($parametros)
	{
		// Devuelve true si es que aun esta a tiempo para presentar requisitos de ingreso segun fecha de cierre de 
		// los requisitos asociados a su período de inscripcion a propuesta.
		$sql = "SELECT COUNT(*) as cantidad
		          FROM sga_alumnos, 
		               sga_propuestas_aspira,
		               sga_situacion_aspirante,
					   vw_periodos_insc_propuesta
		          WHERE sga_alumnos.alumno = {$parametros['alumno']}
		          AND sga_propuestas_aspira.persona   = sga_alumnos.persona
		          AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
		          AND sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
		          AND sga_situacion_aspirante.resultado_asp IN ('A','P')
				  AND vw_periodos_insc_propuesta.periodo_insc = sga_propuestas_aspira.periodo_insc
				  AND vw_periodos_insc_propuesta.fecha_cierre_requisitos >= CURRENT_DATE
			  ";
		$rs = kernel::db()->consultar_fila($sql);
		return ($rs['cantidad'] >= 1);
	}

	/**
	 * parametros: alumno, actividad
	 * cache: no
	 * filas: 1
	 */
	function get_datos_ultima_cursada_actividad($parametros)
	{
		// Tomo la ultima regularidad (vigente o no) de la actividad
		// origen: R = Acta de Regularidad / P = Acta de Promoción (son alumnos que no estan en acta de cursadas de esa comisión) / C = Equivalencia de Regularidad
		// tipo  : Regularidad / Promoción / Equivalencia de Regularidad  (No se consideran las Equivalencias Parciales)
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT 
					vw_regularidades.anio_academico as anio_academico,
					vw_regularidades.periodo_lectivo_nombre as periodo_lectivo_nombre,
					vw_regularidades.fecha as fecha_regularidad,
					to_char(vw_regularidades.fecha, $formato_fecha_visual) as sfecha_regularidad,
					COALESCE(to_char(vw_regularidades.fecha_vigencia, $formato_fecha_visual), 'Sin Vencimiento') as sfecha_fin_vigencia,
					vw_regularidades.es_vigente as es_vigente,
					vw_regularidades.comision as comision,
					sga_comisiones.nombre as comision_nombre,
					sga_catedras.nombre as catedra_nombre,
					vw_regularidades.nro_resolucion_descripcion as nro_resolucion_descripcion, -- Si es por equivalencia
					vw_regularidades.nota as nota,
					vw_regularidades.nota_descripcion as nota_descripcion,
					-- sga_det_escala.concepto as nota_concepto,
					vw_regularidades.resultado_descripcion as resultado_descripcion,
					vw_regularidades.tipo as tipo,
					vw_regularidades.origen as origen
				FROM vw_regularidades
						LEFT JOIN sga_comisiones ON sga_comisiones.comision = vw_regularidades.comision
						LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				WHERE vw_regularidades.alumno   = {$parametros['alumno']}
				  AND vw_regularidades.elemento = {$parametros['actividad']}
				ORDER BY vw_regularidades.fecha DESC
				LIMIT 1
				";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: alumno, anio_academico
	 * cache: memoria
	 * filas: 1
	 */
	function es_ingresante_carrera($parametros)
	{
		// Retorna: 1 - Es Ingresante / 0 - No es Ingresante
		$sql = "SELECT * FROM f_es_ingresante ({$parametros['alumno']}, {$parametros['anio_academico']})";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos[0];
	}

	/**
	 * @deprecated Usar "toba::consulta_php('co_alumnos', true)->get_actividad_academica($alumno)" en su lugar.
	 * parametros: alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_actividad_academica($parametros)
	{
		// Historia académica del Alumno: Examenes, Promociones, Regularidades, Equivalencias Totales y Equivalencias de Regularidad.
		// equivalencia_instancia = 10 - Total / 11 - Regularidad / 12 = Parcial
		// tipo = Aprobacion / Regularidades / Eq. Parcial / En Curso
		// Las equivalencias parciales se pueden recuperar con la vista vw_equiv_parciales. 
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);

		$sql = "SELECT	
						cast('Aprobacion' as varchar(11)) as tipo, -- Historia Academica - Aprobaciones
						sga_elementos_plan.anio_de_cursada, -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						EXTRACT(YEAR FROM vw_hist_academica.fecha) as anio,
						vw_hist_academica.elemento as materia,
						vw_hist_academica.actividad_codigo as materia_codigo,
						vw_hist_academica.actividad_nombre as materia_nombre,
						vw_hist_academica.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_hist_academica.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_hist_academica.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision, -- comision en cursadas
						vw_hist_academica.escala_nota,
						vw_hist_academica.nota,
						vw_hist_academica.nota_descripcion as nota_descripcion,
						-- sga_escalas_notas_concepto.descripcion as nota_concepto,
						vw_hist_academica.resultado as resultado,
						vw_hist_academica.resultado_descripcion as resultado_descripcion,
						CASE vw_hist_academica.origen  
						   WHEN 'P' THEN 'Promocion' 
						   WHEN 'A' THEN 'AprobRes' 
						   WHEN 'E' THEN
						        (CASE vw_hist_academica.instancia
						          WHEN 7 THEN 'Examen'
						          ELSE vw_hist_academica.tipo
						        END)  
						   ELSE vw_hist_academica.tipo 
						END	as origen,
						CASE vw_hist_academica.origen 
						   WHEN 'B' THEN " . \equivalencia::equiv_instancia_total . "
						   ELSE cast(null as integer) 
						END as equivalencia_instancia,
						1 as vigente, 	   -- vigente en cursadas
						cast(null as integer) as cond_regularidad, -- condicion de regularidad en cursadas
						vw_hist_academica.creditos as credito,
						CASE vw_hist_academica.origen
							WHEN 'E' THEN vw_hist_academica.nro_acta  -- Examen
							WHEN 'P' THEN vw_hist_academica.nro_acta  -- Promocion
							WHEN 'A' THEN vw_hist_academica.nro_resolucion_descripcion  -- Aprobacion x Resolución
							WHEN 'B' THEN vw_hist_academica.nro_resolucion_descripcion  -- Equivalncia total.
							ELSE '' 
						END as acta_resolucion,
						vw_hist_academica.plan_version,
						vw_hist_academica.id_acta as id_acta,
						vw_hist_academica.equivalencia,
						to_char(cast(null as date) , $formato_fecha_visual) as fin_vigencia_regul,
						-- Datos Inscripcion a cursada.
						cast(null as varchar(100))	as comision_nombre, 
						vw_hist_academica.anio_academico	as anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_hist_academica
							LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_hist_academica.elemento_revision
														AND sga_elementos_plan.plan_version = vw_hist_academica.plan_version
					WHERE vw_hist_academica.alumno =  {$parametros['alumno']}
			
			UNION ALL
			
				SELECT		
						cast('Regularidad' as varchar(11)) as tipo, -- Regularidades 
						sga_elementos_plan.anio_de_cursada, -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						EXTRACT(YEAR FROM vw_regularidades.fecha) as anio,
						vw_regularidades.elemento as materia,
						vw_regularidades.actividad_codigo as materia_codigo,
						vw_regularidades.actividad_nombre as materia_nombre,
						vw_regularidades.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_regularidades.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_regularidades.fecha, $formato_fecha_orden) as fecha_orden,
						vw_regularidades.comision,
						vw_regularidades.escala_nota,
						vw_regularidades.nota,
						vw_regularidades.nota_descripcion as nota_descripcion,
						-- sga_escala_notas_conceptos.descripcion as nota_concepto,
						vw_regularidades.resultado as resultado,
						vw_regularidades.resultado_descripcion as resultado_descripcion,
						CASE vw_regularidades.tipo 
						  WHEN 'Equivalencia de Regularidad' THEN 'Equivalencia'  
						  ELSE vw_regularidades.tipo
						END as origen,   
						CASE vw_regularidades.tipo 
						   WHEN 'Equivalencia de Regularidad' THEN " . \equivalencia::equiv_instancia_regularidad . "  
						   ELSE cast(null as integer) 
						END as equivalencia_instancia,
						vw_regularidades.es_vigente as es_vigente,
						vw_regularidades.cond_regularidad,
						vw_regularidades.creditos as credito,
						CASE vw_regularidades.origen
						  WHEN 'P' THEN cast(vw_regularidades.nro_acta as varchar(20)) -- Acta de Promocion
						  WHEN 'R' THEN cast(vw_regularidades.nro_acta as varchar(20)) -- Acta de Regulares
						  WHEN 'C' THEN vw_regularidades.nro_resolucion_descripcion  -- Equiv de Regularidad
						  ELSE '' 
						END as acta_resolucion,
						vw_regularidades.plan_version,
						vw_regularidades.id_acta as id_acta,
						vw_regularidades.equivalencia,
						to_char(vw_regularidades.fecha_vigencia, $formato_fecha_visual) as fin_vigencia_regul,
			
						cast(null as varchar(100))	as comision_nombre, 
						vw_regularidades.anio_academico as anio_academico, 
						vw_regularidades.periodo_lectivo_nombre	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_regularidades
							LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_regularidades.elemento_revision
																				AND sga_elementos_plan.plan_version = vw_regularidades.plan_version
					WHERE vw_regularidades.alumno = {$parametros['alumno']}
					  AND vw_regularidades.origen <> 'P' -- Las promociones las obtengo por la vista vw-hist_academica
								
			UNION ALL
			
				SELECT		
						cast('Eq. Parcial' as varchar(11)) as tipo, -- Equivalencias Parciales
						sga_elementos_plan.anio_de_cursada,         
						sga_elementos_plan.periodo_de_cursada,      
						EXTRACT(YEAR FROM vw_equiv_parciales.fecha) as anio,
						vw_equiv_parciales.elemento as materia,
						vw_equiv_parciales.actividad_codigo as materia_codigo,
						vw_equiv_parciales.actividad_nombre as materia_nombre,
						vw_equiv_parciales.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_equiv_parciales.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_equiv_parciales.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision,
						vw_equiv_parciales.escala_nota,
						vw_equiv_parciales.nota,
						vw_equiv_parciales.nota_descripcion as nota_descripcion,
						-- sga_escala_notas_conceptos.descripcion as nota_concepto,
						vw_equiv_parciales.resultado as resultado,
						vw_equiv_parciales.resultado_descripcion as resultado_descripcion,
						'Equivalencia' as origen,   
						" . \equivalencia::equiv_instancia_parcial . " as equivalencia_instancia,
						vw_equiv_parciales.es_vigente as es_vigente,
						cast(null as integer) as cond_regularidad,
						sga_elementos_plan.creditos as credito,
						vw_equiv_parciales.nro_resolucion_descripcion as acta_resolucion,
						vw_equiv_parciales.plan_version,
						cast(null as integer) as id_acta,
						vw_equiv_parciales.equivalencia,
						to_char(vw_equiv_parciales.fecha_vigencia, $formato_fecha_visual) as fin_vigencia_regul,
			 
						cast(null as varchar(100))	as comision_nombre, 
						cast(null as integer) as  anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_equiv_parciales
							LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_equiv_parciales.elemento_revision
														AND sga_elementos_plan.plan_version = vw_equiv_parciales.plan_version
					WHERE vw_equiv_parciales.alumno = {$parametros['alumno']}

				UNION ALL
				
				SELECT
						cast('En Curso' as varchar(11)) as tipo, -- En Curso
						sga_elementos_plan.anio_de_cursada,    -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						sga_periodos.anio_academico as anio, 
						sga_comisiones.elemento as materia,
						sga_elementos.codigo as materia_codigo,
						sga_elementos_plan.nombre as materia_nombre,
						sga_elementos_plan.nombre_abreviado as materia_nombre_red,
						to_char(sga_periodos_lectivos.fecha_inicio_dictado, $formato_fecha_visual) as fecha_display,
						to_char(sga_periodos_lectivos.fecha_inicio_dictado, $formato_fecha_orden) as fecha_orden,
						sga_insc_cursada.comision,
						cast(null as int) as escala_notas, 
						cast(null as varchar(10)) as nota, 
						cast(null as varchar(100)) as nota_descripcion, 
						-- cast(null as varchar(15)) as nota_concepto, 
						cast(null as char(1)) as resultado, 
						cast(null as varchar(50)) as resultado_descripcion, 
						'EnCurso' as origen,
						cast(null as integer) as equivalencia_instancia,
						cast(null as int) as vigente, 
						cast(null as int) as cond_regularidad,
						cast(null as decimal(8,2)) as credito, 
						cast(null as varchar(30)) as acta_resolucion,
						sga_insc_cursada.plan_version as plan_version,
						cast(null as int) as id_acta, 
						cast(null as int) as equivalencia,			
						to_char(cast(null as date) , $formato_fecha_visual) as fin_vigencia_regul,	
						sga_comisiones.nombre as comision_nombre,
						sga_periodos.anio_academico,
						sga_periodos.nombre as periodo_lectivo,
						-- sga_comisiones.turno as turno,
						sga_turnos_cursadas.descripcion as turno_cursada,
						sga_catedras.nombre as catedra,
						f_instancias_insc_cursada(sga_insc_cursada.inscripcion) as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM
						sga_insc_cursada
						JOIN sga_comisiones ON sga_comisiones.comision = sga_insc_cursada.comision
						JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
						JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
						JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						                            sga_elementos_plan.plan_version      = sga_insc_cursada.plan_version)
						JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
						JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
						 LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
						 LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra

				WHERE	sga_insc_cursada.alumno  = {$parametros['alumno']}
				AND sga_periodos.fecha_fin >= CURRENT_DATE  -- Comisiones actuales o futuras
			ORDER BY fecha_orden
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_creditos_alumno($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);

		$sql = "
				SELECT	-- Créditos globales
						cast('CreditosGlo' as varchar(11)) as tipo, 
						cast(null as integer) as anio_de_cursada, 
						cast(null as integer) as periodo_de_cursada, 
						EXTRACT(YEAR FROM sga_reconocimiento.fecha) as anio,
						cast(null as integer) as materia,  
						cast(null as varchar(10)) as materia_codigo,
						'Créditos Globales' as materia_nombre,
						'Globales' as materia_nombre_red,
						to_char(sga_reconocimiento.fecha, $formato_fecha_visual) as fecha_display,
						to_char(sga_reconocimiento.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision, 
						cast(null as integer) as escala_nota,
						cast(null as varchar(10)) as nota,
						cast(null as varchar(255)) as nota_descripcion,
						'A' as resultado,
						'Aprobado' as resultado_descripcion,
						'Creditos' as origen,
						cast(null as integer) as equivalencia_instancia,
						1 as vigente, 	   
						cast(null as integer) as cond_regularidad,
						sga_reconocimiento.total_creditos as credito,
						sga_documentos.documento_numero as acta_resolucion,
						sga_reconocimiento.plan_version,
						cast(null as integer) as id_acta,
						cast(null as integer) as equivalencia,
						cast(null as date) as fin_vigencia_regul,
						cast(null as varchar(100))	as comision_nombre, 
						cast(null as integer) as anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						sga_reconocimiento.nro_tramite -- Para recuperar la documentacion presentada x el alumno
						
				FROM sga_reconocimiento
				JOIN sga_documentos ON sga_documentos.documento = sga_reconocimiento.documento
															   
 				WHERE sga_reconocimiento.alumno = {$parametros['alumno']}
			  	AND sga_reconocimiento.estado = 'C' 				   
			  	AND sga_reconocimiento.tipo_reconocimiento = 'CG' 
				--AND sga_reconocimiento.total_creditos > 0
				
				UNION ALL
				
				-- Créditos de modulos
				SELECT	
						cast('CreditosMod' as varchar(11)) as tipo, 
						cast(null as integer) as anio_de_cursada, 
						cast(null as integer) as periodo_de_cursada, 
						EXTRACT(YEAR FROM sga_reconocimiento.fecha) as anio,
						sga_reconocimiento_act.elemento as materia,  -- modulo
						sga_elementos.codigo as materia_codigo, -- codigo del modulo
						'Créditos Módulo: ' || sga_elementos_plan.nombre as materia_nombre,
						'Módulo: ' || sga_elementos_plan.nombre_abreviado as materia_nombre_red,
						to_char(sga_reconocimiento.fecha, $formato_fecha_visual) as fecha_display,
						to_char(sga_reconocimiento.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision, 
						sga_reconocimiento_act.escala_nota,
						sga_reconocimiento_act.nota,
						cast(null as varchar(255)) as nota_descripcion,
						'A' as resultado,
						'Aprobado' as resultado_descripcion,
						'Creditos' as origen,
						cast(null as integer) as equivalencia_instancia,
						1 as vigente, 	   
						cast(null as integer) as cond_regularidad,
						sga_reconocimiento_act.creditos as credito,
						sga_documentos.documento_numero as acta_resolucion,
						sga_reconocimiento.plan_version,
						cast(null as integer) as id_acta,
						cast(null as integer) as equivalencia,
						cast(null as date) as fin_vigencia_regul,
						cast(null as varchar(100))	as comision_nombre, 
						cast(null as integer) as anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						CASE sga_reconocimiento_act.reconocimiento_total
						    WHEN 'S' THEN 'total'
						    WHEN 'N' THEN 'parcial'
						END as tipo_reconocimiento,
						sga_reconocimiento.nro_tramite -- Para recuperar la documentacion presentada x el alumno
						 
				FROM sga_reconocimiento
				JOIN sga_documentos ON sga_documentos.documento = sga_reconocimiento.documento
				JOIN sga_reconocimiento_act ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
				JOIN sga_elementos ON sga_elementos.elemento = sga_reconocimiento_act.elemento
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
				JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND sga_elementos_plan.plan_version = sga_reconocimiento.plan_version)
				JOIN sga_g3entidades_subtipos ON (sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo AND sga_g3entidades_subtipos.entidad_tipo = 1) -- Modulos
															   
 				WHERE sga_reconocimiento.alumno = {$parametros['alumno']}
				AND sga_reconocimiento.estado = 'C' 
				AND sga_reconocimiento.tipo_reconocimiento = 'AM' 
				--AND sga_reconocimiento_act.creditos > 0
				
			  ORDER BY fecha_orden
		";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: alumno, orden
	 * no_quote: alumno, orden
	 * cache: memoria
	 * filas: n
	 */
	function get_actividad_academica_con_creditos($parametros)
	{

		$actividad_academica = catalogo::consultar('alumno', 'get_actividad_academica', array('alumno' => $parametros['alumno']));


		$creditos_alumno = catalogo::consultar('alumno', 'get_creditos_alumno', array('alumno' => $parametros['alumno']));
		$actividad_academica_creditos = array_merge($actividad_academica, $creditos_alumno);

		if ($parametros['orden'] == 'anio') {

			//Es como hacer ORDER BY fecha_orden DESC, materia_nombre ASC
			usort($actividad_academica_creditos, function($a, $b) {

				$fecha_orden_a = intval($a["fecha_orden"]);
				$fecha_orden_b = intval($b["fecha_orden"]);
				$cmp_fecha_orden = $fecha_orden_b - $fecha_orden_a;
				$cmp_materia_nombre = strcasecmp($a["materia_nombre"], $b["materia_nombre"]);

				if ($cmp_fecha_orden == 0) {
					return $cmp_materia_nombre;
				} else {
					return $cmp_fecha_orden;
				}
			});
		} elseif ($parametros['orden'] == 'materia') {

			//Es como hacer ORDER BY materia_nombre ASC, fecha_orden DESC
			usort($actividad_academica_creditos, function($a, $b) {

				$cmp_materia_nombre = strcasecmp($a["materia_nombre"], $b["materia_nombre"]);
				$fecha_orden_a = intval($a["fecha_orden"]);
				$fecha_orden_b = intval($b["fecha_orden"]);
				$cmp_fecha_orden = $fecha_orden_b - $fecha_orden_a;

				if ($cmp_materia_nombre == 0) {
					return $cmp_fecha_orden;
				} else {
					return $cmp_materia_nombre;
				}
			});
		}

		return $actividad_academica_creditos;
	}

	/**
	 * parametros: persona, id_acta
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_examen($parametros)
	{
		// ex get_detalle_aprobada
		// Recupera los datos de un examen.
		$sql = "SELECT	vw_mesas_examen.anio_academico,
						vw_mesas_examen.turno_examen_nombre,
						vw_mesas_examen.mesa_examen_nombre,
						sga_instancias.nombre as tipo_inscripcion
				FROM	sga_actas_detalle
						JOIN sga_actas ON sga_actas_detalle.id_acta = sga_actas.id_acta
						JOIN vw_mesas_examen ON sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
						JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
						JOIN sga_instancias ON sga_actas_detalle.instancia = sga_instancias.instancia
				WHERE	sga_alumnos.persona = {$parametros['persona']} AND
						sga_actas_detalle.id_acta = {$parametros['id_acta']}
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: persona, comision
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_en_curso($parametros)
	{
		$datos = array();
		$datos['evaluaciones'] = $this->get_datos_parciales($parametros);
		return $datos;
	}

	/**
	 * parametros: id_acta
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_promo($parametros)
	{
		$datos = array();
		$sql = "SELECT
					sga_comisiones.comision as comision,
					sga_periodos.anio_academico as anio_academico,
					sga_comisiones.nombre as comision_nombre,
					sga_periodos.nombre as periodo_lectivo,
					sga_catedras.nombre as catedra,
					sga_turnos_cursadas.nombre as turno_cursada
				FROM sga_actas
				JOIN sga_comisiones ON sga_comisiones.comision = sga_actas.comision
				JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
				WHERE sga_actas.id_acta = {$parametros['id_acta']}
		";

		$datos_comision = array();
		$datos_comision = kernel::db()->consultar_fila($sql);
		$datos = $datos_comision;

		return $datos;
	}

	/**
	 * parametros: id_acta
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_cursada($parametros)
	{
		$datos = array();
		$sql = "SELECT
					sga_comisiones.comision,
					sga_periodos.anio_academico,
					sga_comisiones.nombre,
					sga_comisiones.comision as comision,
					sga_periodos.anio_academico as anio_academico,
					sga_comisiones.nombre as comision_nombre,
					sga_periodos.nombre as periodo_lectivo,
					sga_catedras.nombre as catedra,
					sga_turnos_cursadas.nombre as turno_cursada
				FROM sga_actas
				JOIN sga_comisiones ON sga_comisiones.comision = sga_actas.comision
				JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
				WHERE sga_actas.id_acta = {$parametros['id_acta']}
		";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: equivalencia
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_equiv($parametros)
	{

		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT
					CASE
					   WHEN sga_equiv_otorgada.fecha_vigencia IS NULL THEN 'Sin Vencimiento'
					   ELSE to_char(sga_equiv_otorgada.fecha_vigencia, $formato_fecha_visual) 
					END as vencimiento,
					sga_documentos.documento_numero as resolucion,
					sga_documentos.observaciones as resolucion_obs,
					sga_equiv_otorgada.temas_a_rendir
				FROM sga_equiv_otorgada 
				JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = sga_equiv_otorgada.equivalencia_tramite
				LEFT JOIN sga_documentos ON sga_documentos.documento = sga_equiv_tramite.documento
				WHERE sga_equiv_otorgada.equivalencia = {$parametros['equivalencia']}
		";

		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: nro_tramite
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_creditos($parametros)
	{

		$sql = "
			SELECT rta.nombre AS tipo_documentacion,
				   rd.nombre AS nombre_documentacion
			FROM sga_reconocimiento_doc AS rd
			JOIN sga_reconocimiento_tipo_actividad AS rta ON (rd.tipo_actividad = rta.tipo_actividad)
			WHERE rd.nro_tramite = {$parametros['nro_tramite']}
		";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Listado de evaluaciones parciales del alumno en una comisión.
	 * parametros: persona, comision
	 * no_quote: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_datos_parciales($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);

		$sql = "SELECT
					 sga_evaluaciones.evaluacion as evaluacion,
					 sga_evaluaciones.nombre as evaluacion_desc,
					 sga_evaluaciones_tipos.nombre as tipo_evaluacion,
					 sga_evaluaciones.fecha as fecha_hora_parcial,
					 to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as sfecha,
					 to_char(sga_evaluaciones.fecha, $formato_fecha_orden) as sfecha_orden,
					 sga_eval_detalle.nota as nota,
					 sga_escalas_notas_det.descripcion as nota_descripcion,
					 sga_eval_detalle.resultado as resultado,
					 sga_escalas_notas_resultado.descripcion as resultado_desc,
					 sga_eval_detalle.corregido_por
					 --    sga_eval_detalle.observaciones
				FROM sga_comisiones
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = sga_comisiones.entidad
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN sga_eval_detalle ON sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND sga_escalas_notas_det.nota = sga_eval_detalle.nota)
				LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
				WHERE sga_comisiones.comision	 = {$parametros['comision']}
				  AND sga_evaluaciones.visible_al_alumno = 'S'
				  AND sga_alumnos.persona = {$parametros['persona']}
				ORDER BY sga_evaluaciones.fecha
		";
		return kernel::db()->consultar($sql);
	}

	/**
	 * @deprecated Usar "toba::consulta_php('co_alumnos', true)->get_materias_genericas_alumno($plan_version)" en su lugar.
	 * parametros: plan_version
	 * cache: memoria
	 * filas: n
	 */
	function get_materias_genericas_alumno($parametros)
	{		
		$datos = array();
		$sql = "SELECT	
		                gen.elemento as generica_elemento,
		                gen.codigo as generica_codigo,
		                gen.nombre as generica_nombres,
		                opt.elemento as optativa_elemento,
		                opt.codigo as optativa_codigo,
		                opt.nombre as optativa_nombres,
		                opt.puntaje as optativa_puntaje
				FROM vw_actividades_genericas_plan as gen
				JOIN vw_optativas as opt ON opt.elemento_generica = gen.elemento
				WHERE gen.plan_version = {$parametros["plan_version"]}
				ORDER BY 6;
				";

		$genericas = kernel::db()->consultar($sql);
		return $genericas;
	}

	/**
	 * parametros: plan_version
	 * cache: memoria
	 * filas: n
	 */
	function get_detalle_plan_estudios($parametros)
	{

		$sql = "
                SELECT vw_actividades_plan.codigo AS materia,
                       vw_actividades_plan.nombre AS nombre_materia,
                       sga_elementos_plan.anio_de_cursada AS anio,
                       sga_elementos_plan.periodo_de_cursada AS periodo,
                       COALESCE(sga_elementos_plan.anio_de_cursada, 0) AS anio_orden,
                       COALESCE(sga_elementos_plan.periodo_de_cursada, 0) AS periodo_orden,
                       vw_actividades_plan.entidad_subtipo AS tipo,
                       vw_actividades_plan.entidad_subtipo_nombre AS tipo_detalle,
                       sga_elementos_plan.creditos AS credito
                FROM vw_actividades_plan
                JOIN sga_elementos_plan ON (vw_actividades_plan.plan_version = sga_elementos_plan.plan_version AND vw_actividades_plan.elemento_revision = sga_elementos_plan.elemento_revision)
                WHERE vw_actividades_plan.plan_version = {$parametros["plan_version"]}
                ORDER BY anio_orden, periodo_orden, nombre_materia
                ";

		$detalle_plan = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		$ret_final = array();
		$ret_final['detalle'] = $detalle_plan;
		return $ret_final;
	}

	/**
	 * parametros: alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_certificados_obtenidos($parametros)
	{

		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT
					sga_certificados.certificado as certificado,
					sga_certificados.codigo as codigo,
					sga_certificados.nombre as nombre,
					sga_certificados.nombre_femenino as nombre_femenino,
					tn.descripcion as nivel_titulo,
					to_char(sga_certificados_otorg.fecha_inicio_tramite, $formato_fecha_visual) as fecha_inicio_tramite,
					to_char(sga_certificados_otorg.fecha_egreso, $formato_fecha_visual) as fecha_egreso,
					sga_certificados_otorg.promedio as promedio_con_aplazos,
					sga_certificados_otorg.promedio_sin_aplazos as promedio_sin_aplazos,
					mce_estados.descripcion as estado_actual_tramite
				FROM sga_certificados_otorg
				JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado
				JOIN sga_certificados ON sga_certificados.certificado = sga_certificados_otorg.certificado
				LEFT JOIN sga_titulos_niveles as tn ON tn.titulo_nivel = sga_certificados.titulo_nivel
					
				WHERE sga_certificados_otorg.alumno = {$parametros['alumno']}
				  AND sga_certificados_otorg.anulado = 0
				ORDER BY  sga_certificados.titulo_nivel 
				";

		$datos_titulos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos_titulos;
	}

	/**
	 * parametros: alumno
	 * no_quote: alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_promedios_por_titulo($parametros)
	{

		$datos = [];
		
		// 1) Obtengo el promedio de todas las actividades del plan sin tener en cuenta un titulo.
		$datos_titulo['nombre'] = 'Promedios en la carrera';
		$datos_titulo['promedio_general'] = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, null);
		$datos_titulo['promedio_sin_aplazos'] = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, null, \comunes::no);
		$datos[0] = $datos_titulo;
		
		// 2) Recupera los titulos de la propuesta del alumno y consulta los promedios.
		$titulos = catalogo::consultar('alumno', 'get_titulos_propuesta', $parametros);
		foreach ($titulos as $titulo) {
			$datos_titulo['nombre'] = $titulo['certificado_nombre'];
			$datos_titulo['promedio_general'] = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, $titulo['certificado']);
			$datos_titulo['promedio_sin_aplazos'] = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, $titulo['certificado'], \comunes::no);
			$datos[$titulo['certificado']] = $datos_titulo;
		}

		// Retorno los datos de los titulos
		return $datos;
	}

	/**
	 * parametros: alumno
	 * cache: no
	 * filas: n
	 */
	function get_titulos_propuesta($parametros)
	{
		// Recupera las certificaciones de la propuesta del alumno.
		$sql = "SELECT
					sga_certificados.nombre as certificado_nombre,
					sga_planes_certificados.certificado as certificado,
					sga_certificados.codigo as codigo,
					sga_certificados.titulo_nivel as titulo_nivel,
					sga_certificados.titulo_araucano as titulo_araucano,
					sga_certificados_tipos.nombre as tipo_certificado
				FROM sga_alumnos
				JOIN sga_planes_certificados ON sga_planes_certificados.plan_version = sga_alumnos.plan_version
				JOIN sga_certificados ON sga_certificados.certificado = sga_planes_certificados.certificado
				JOIN sga_certificados_tipos ON sga_certificados_tipos.certificado_tipo = sga_certificados.certificado_tipo
				WHERE sga_alumnos.alumno = {$parametros['alumno']}
				ORDER BY 1
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: _ua,  carrera, legajo, titulo, materia
	 * cache: memoria
	 * filas: 1
	 */
	function get_resultado_generica($parametros)
	{
		// @TODO = G3: Se debe invocar al metodo de la regla de cumplimiento de la activdad genérica.

		/** Se realiza de la misma manera que en sp_evalua_materia retorna * */
		//Retorna:  char(1) = A - Aprobo / R - No Aprobo la Mat. Generica
		//integer = sumatoria de los crÃ©ditos de las optativas aprobadas


		$sql = "execute procedure sp_resultGenerica(" . $parametros["_ua"] . ", 
													'" . $parametros["carrera"] . "',
													'" . $parametros["legajo"] . "',
													'" . $parametros["titulo"] . "',
													'0',
													" . $parametros["materia"] . ")";

		return kernel::db()->consultar($sql, db::FETCH_NUM);
	}

	/**
	 * parametros: actividad_generica, plan_version
	 * cache: memoria
	 * filas: n
	 */
	function get_optativas_generica($parametros)
	{
		// Actividades optativas de una genérica
		if (isNull($parametros['plan_version'])) {
			// Muestra el nombre de las optativas con el nombre generico.
			$sql = "SELECT
						vw_optativas.elemento as optativa,
						vw_optativas.codigo as optativa_codigo,
						vw_optativas.nombre as optativa_nombre,
						vw_optativas.nombre_abreviado as optativa_nombre_abreviado,
						vw_optativas.orden as orden,
						vw_optativas.puntaje as puntaje
				FROM 	vw_optativas
				WHERE 	vw_optativas.elemento_generica = {$parametros['actividad_generica']}
				ORDER BY 3
			";
		} else {
			// Muestra el nombre de la optativa en el plan de estudios.
			$sql = "SELECT
						vw_optativas_plan.elemento as optativa,
						vw_optativas_plan.codigo as optativa_codigo,
						vw_optativas_plan.nombre as optativa_nombre,
						vw_optativas_plan.nombre_abreviado as optativa_nombre_abreviado,
						vw_optativas_plan.orden as orden,
						vw_optativas_plan.puntaje as puntaje
				FROM vw_optativas_plan					 
				WHERE vw_optativas_plan.elemento_generica = {$parametros['actividad_generica']}
				  AND vw_optativas_plan.plan_version      = {$parametros['plan_version']}
				ORDER BY 3";
		}
		
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: plan_version, elemento
	 * cache: memoria
	 * filas: n
	 */
	function get_correlativas($parametros)
	{
		// Recupera correlativas para cursar y rendir una actividad en una version de plan de estudios.
		$sql = "SELECT
                        v.condicion_tipo as tipo_correlativa,
						v.condicion_tipo_nombre as tipo_correlativa_nombre,
						v.grupo_condicion as grupo_condicion,
						v.grupo_orden as grupo_orden,
						v.orden as requisito_orden,
						CASE v.tipo
						  WHEN 'E' THEN v.elemento_codigo
						  WHEN 'R' THEN cast(v.requisito as varchar(255))
						END as requisito_codigo,
						CASE v.tipo
						  WHEN 'E' THEN v.elemento_nombre
						  WHEN 'R' THEN v.requisito_nombre
						END as requisito_nombre,
						tipo,
						ec.entidad_subtipo,
						operador_not,
						CASE
						  WHEN parametros IS NULL THEN COALESCE(estado_nombre, requisito_nombre, get_regla_mensaje(regla, parametros))
						  ELSE COALESCE(estado_nombre, get_regla_mensaje(regla, parametros), requisito_nombre)
						END as condicion
				FROM sga_elementos as e
				JOIN vw_condiciones as v ON v.condicion_entidad = e.entidad
				LEFT JOIN sga_elementos as ec ON (ec.entidad = v.entidad)
				WHERE e.elemento = {$parametros['elemento']}
				  AND v.plan_version = {$parametros['plan_version']}
		          AND v.condicion_tipo IN ('1', '2') -- Correlativas para cursar y rendir
				ORDER BY tipo_correlativa, grupo_orden, grupo_condicion, requisito_orden
			";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function actuacion_provisoria_examen($parametros)
	{
		return \toba::consulta_php('co_alumnos')->get_actuacion_provisoria_examen($parametros['persona']);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function actuacion_provisoria_cursada($parametros)
	{
		return \toba::consulta_php('co_alumnos')->get_actuacion_provisoria_cursada($parametros['persona']);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function actuacion_provisoria_promocion($parametros)
	{
		return \toba::consulta_php('co_alumnos')->get_actuacion_provisoria_promocion($parametros['persona']);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function aulas($parametros)
	{
		// TODO: FALTA CAMBIAR EN G3
		$sql = "execute procedure sp_agendaDeClases({$parametros['_ua']}, {$parametros['nro_inscripcion']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function tesis($parametros)
	{
		// Recupera las tesis que tenga registrada la persona en cualquier propuesta
		$sql = "SELECT 
					sga_tesis.tesis as tesis,
					sga_tesis.titulo as titulo,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado
				FROM
					sga_alumnos
					JOIN sga_tesis_alumnos ON sga_alumnos.alumno = sga_tesis_alumnos.alumno
					JOIN sga_tesis ON sga_tesis.tesis = sga_tesis_alumnos.tesis 
					JOIN sga_propuestas on sga_propuestas.propuesta = sga_alumnos.propuesta
				WHERE sga_alumnos.persona = {$parametros['persona']}
				ORDER BY sga_tesis.titulo
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: tesis, persona
	 * cache: memoria
	 * filas: n
	 */
	function tesis_datos($parametros)
	{
		// Recupera los datos de la tesis.	
		// Necesita el id de persona para poder recuperar el nombre de la actividad de la tesis en la version del plan del alumno.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT 
					sga_tesis.tesis as tesis,
					sga_tesis.titulo as titulo,
					vw_actividades_plan.nombre as actividad_nombre,
					vw_actividades_plan.codigo as actividad_codigo,
					sga_tesis.expediente as nro_expediente,
					sga_tesis.estado_actual as estado_actual,
					mce_estados.nombre as estado_actual_desc,
					to_char(sga_tesis.fecha_ult_cambio, $formato_fecha_visual) as fecha_ultimo_cambio,
					f_tutores_tesis(sga_tesis.tesis, true) as tutores,
					f_evaluadores_tesis(sga_tesis.tesis, true) as evaluadores,
					to_char(sga_tesis.fecha_aprobacion, $formato_fecha_visual) as fecha_aprobacion,
					to_char(sga_tesis.fecha_vencimiento, $formato_fecha_visual) as fecha_vencimiento,
					to_char(sga_tesis.fecha_defensa, $formato_fecha_visual) as fecha_defensa
				FROM sga_tesis
				JOIN sga_tesis_alumnos on sga_tesis_alumnos.tesis = sga_tesis.tesis
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_tesis_alumnos.alumno
				JOIN vw_actividades_plan on (vw_actividades_plan.elemento = sga_tesis.actividad AND vw_actividades_plan.plan_version = sga_alumnos.plan_version)
				LEFT JOIN mce_estados ON mce_estados.estado = sga_tesis.estado_actual
				WHERE sga_tesis.tesis = {$parametros['tesis']}
				  AND sga_alumnos.persona = {$parametros['persona']}
				";

		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: tesis
	 * cache: memoria
	 * filas: n
	 */
	function tesis_estados($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		// Lista los cambios de estado de la tesis
		$sql = "SELECT 
					to_char(sga_tesis_cmb.fecha, $formato_fecha_visual) as fecha_cambio,
					estado_nue.nombre as estado_nuevo_desc,
					sga_tesis_cmb.observaciones as observaciones,
					estado_ant.nombre as estado_anterior_desc,
					mce_acciones.nombre as accion_nombre
				FROM sga_tesis_cmb
				JOIN mce_estados as estado_nue ON estado_nue.estado = sga_tesis_cmb.estado_nuevo
				LEFT JOIN mce_acciones ON sga_tesis_cmb.accion = mce_acciones.accion
				LEFT JOIN mce_estados as estado_ant ON estado_ant.estado = sga_tesis_cmb.estado_anterior
				WHERE sga_tesis_cmb.tesis = {$parametros['tesis']}
				ORDER BY sga_tesis_cmb.fecha
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: alumno
	 * cache: memoria
	 * filas: n
	 */
	function regularidades($parametros)
	{
		// @TODO = Usar la vista vw_regularidades. Ver parametros 3, 3, 3

		$sql = "execute procedure sp_regulAlumno({$parametros['_ua']}, {$parametros['carrera']}, {$parametros['legajo']},3,3,3)";
		$sql = "SELECT
				FROM vw_regularidades
				WHERE
				ORDER BY
				";

		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);

		$nuevo = array();
		foreach ($datos as $id => $dato) {
			$nuevo[$id]['MATERIA'] = $dato[3];
			$nuevo[$id]['MATERIA_NOMBRE'] = $dato[12];
			$nuevo[$id]['RESULTADO'] = $dato[7];
			$nuevo[$id]['FECHA_VENCIMIENTO'] = "";
			$nuevo[$id]['FECHA_REGULARIDAD'] = "";
			if (!empty($dato[9])) {
				$nuevo[$id]['FECHA_VENCIMIENTO'] = date("d/m/Y", strtotime($dato[9]));
			}

			if (!empty($dato[10])) {
				$nuevo[$id]['FECHA_REGULARIDAD'] = date("d/m/Y", strtotime($dato[10]));
			}

			$nuevo[$id]['DETALLE']['FECHA_VENCIMIENTO'] = $nuevo[$id]['FECHA_VENCIMIENTO'];

			$nuevo[$id]['DETALLE']['ANIO_ACADEMICO'] = $dato[4];
			$nuevo[$id]['DETALLE']['PER_LEC'] = $dato[5];
			$nuevo[$id]['DETALLE']['ORIGEN'] = $dato[8];
		}
		return $nuevo;
	}

	/**
	 * cache: no
	 * filas: n
	 */
	function periodos_lectivos()
	{
		//  @TODO: FALTA CAMBIAR EN G3
		$sql = "execute procedure sp_perlects_actual()";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}

	/**
	 * parametros: _ua, nro_inscripcion, anio, periodo_lectivo
	 * cache: no
	 * filas: n
	 */
	function creditos($parametros)
	{
		//  @TODO: FALTA CAMBIAR EN G3
		$datos = array();
		$sql = "execute procedure sp_CredAluAnioPl ({$parametros['_ua']}, {$parametros['nro_inscripcion']}, {$parametros['anio']}, {$parametros['periodo_lectivo']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);

		$resultado = array();
		$i = 0;
		foreach ($datos as $valor) {
			$resultado[$i]['carrera'] = $valor[1];
			$resultado[$i]['cred_mano'] = $valor[3];
			$resultado[$i]['cred_cur'] = $valor[4];
			$i++;
		}
		return $resultado;
	}

	/**
	 * recupero las comisiones de la materia a las que el alumno está inscripto
	 * parametros: alumno
	 * cache: no
	 * filas: n
	 */
	function comision_inscripto($parametros)
	{
		//  @TODO: FALTA CAMBIAR EN G3
		$sql = "execute procedure sp_InscCurAlu({$parametros['_ua']}, {$parametros['carrera']}, {$parametros['legajo']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}

	/**
	 * recupero las clases de una comision/subcomision
	 * parametros: comision, subcomision
	 * cache: no
	 * filas: n
	 */
	function temas_dictados($parametros)
	{
		//  @TODO: FALTA CAMBIAR EN G3
		if (empty($parametros['subcomision'])) {
			$parametros['subcomision'] = 'null';
		} else {
			$parametros['subcomision'] = "{$parametros['subcomision']}";
		}
		$sql = "execute procedure sp_ClasesComision({$parametros['comision']}, {$parametros['subcomision']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		$resultado = array();
		if (!empty($datos)) {
			$i = 0;
			foreach ($datos as $dato) {
				$resultado[$i]['fecha'] = date("d/m/Y", strtotime($dato[9]));
				$resultado[$i]['inicio'] = $dato[12];
				$resultado[$i]['fin'] = $dato[13];
				$resultado[$i]['horas'] = $dato[14];
				$resultado[$i]['tema'] = $dato[10];
				$i++;
			}
		}
		return $resultado;
	}

	/**
	 * parametros: persona, estado, vigentes
	 * cache: memoria
	 * filas: n
	 */
	function inscripciones_examenes($parametros)
	{
		// Recupera inscripciones a examenes en cualquier estado, vigentes o no vigentes.
		// ******************************** Filtros **********************************************
		// estado = T - Todos los estados (Aceptado, Pendiente)/  A - Aceptado / P - Pendientes / R - Rechazadas / AP = Aceptadas y Pendientes / B - Baja
		// $where_estado = ($parametros['estado'] == "'T'") ? '' : ($parametros['estado'] == "'AP'" ? " AND vw_insc_examen.estado IN ('A','P')" : " AND vw_insc_examen.estado = {$parametros['estado']}");
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);

		$where_estado_1 = "";
		$where_estado_2 = "";
		if ($parametros['estado'] == "'A'" || $parametros['estado'] == "'P'") {
			$where_estado_1 = " AND vw_insc_examen.estado = {$parametros['estado']}";
		}
		if ($parametros['estado'] == "'AP'") {
			$where_estado_1 = " AND vw_insc_examen.estado IN ('A','P')";
		}
		if ($parametros['estado'] == "'R'" || $parametros['estado'] == "'B'") {
			$where_estado_2 = " AND vw_insc_examen_log.estado = {$parametros['estado']}";
		}


		// vigentes = S - Mesas de examen de hoy o futuras /  T - Todas
		$where_vigentes = $parametros['vigentes'] == "'S'" ? ' AND vw_mesas_examen.mesa_examen_fecha >= CURRENT_DATE ' : '';
		// ****************************************************************************************
		// Listado de inscripciones a examenes de un alumno
		$sql = "SELECT
					vw_insc_examen.inscripcion,
					vw_insc_examen.alumno,
					vw_mesas_examen.mesa_examen_elemento,
					vw_mesas_examen.llamado_mesa,
					vw_mesas_examen.mesa_examen,
					vw_insc_examen.nro_transaccion,
					vw_insc_examen.fecha_inscripcion,
					vw_insc_examen.plan_version,
					to_char(vw_insc_examen.fecha_inscripcion, 'DD/MM/YYYY HH:MM:SS') as fecha_inscripcion_visual,
					vw_mesas_examen.turno_examen,
					vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
					vw_mesas_examen.llamado_nombre as llamado_nombre,
					vw_mesas_examen.anio_academico,
					vw_mesas_examen.turno_examen_nombre as turno_nombre,
					vw_mesas_examen.mesa_examen_fecha as fecha_examen,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen_visual,
					vw_insc_examen.instancia,
					sga_instancias.nombre as instancia_nombre,
					sga_catedras.nombre as catedra_nombre,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_espacios_llamado_mesa(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre, 
					-- f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) as modalidades_nombre,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					vw_insc_examen.estado as estado,
					sga_inscripciones_estados.nombre as estado_nombre,
					vw_actividades.elemento as actividad_elemento,
					vw_actividades.codigo as actividad_codigo,
					vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_nombre,
					cast(null as integer) as nro_transaccion_log, -- Baja o Rechazo
					cast(null as varchar(16)) as fecha_log, -- Baja o Rechazo
					vw_insc_examen.fecha_inscripcion as fecha_orden,
					int_pv_cursos_llamados_mesa.curso as moodle_curso
				FROM
					sga_alumnos
					JOIN vw_insc_examen ON  vw_insc_examen.alumno = sga_alumnos.alumno 
					JOIN vw_mesas_examen  ON vw_mesas_examen.llamado_mesa = vw_insc_examen.llamado_mesa 
					JOIN sga_ubicaciones ON vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_instancias ON vw_insc_examen.instancia = sga_instancias.instancia 
					JOIN vw_actividades ON vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_inscripciones_estados ON  sga_inscripciones_estados.estado = vw_insc_examen.estado
					LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra 
					LEFT JOIN int_pv_cursos_llamados_mesa	ON (int_pv_cursos_llamados_mesa.llamado_mesa = vw_mesas_examen.llamado_mesa)
				
				WHERE	sga_alumnos.persona = {$parametros['persona']}
					$where_estado_1
					$where_vigentes
				";

		// Inscripciones rechazadas - Dadas de Baja
		$sql_insc_rechazadas_bajas = "SELECT
					vw_insc_examen_log.inscripcion,
					vw_insc_examen_log.alumno,
					vw_mesas_examen.mesa_examen_elemento,
					vw_mesas_examen.llamado_mesa,
					vw_mesas_examen.mesa_examen,
					vw_insc_examen_log.nro_transaccion,
					vw_insc_examen_log.fecha_inscripcion,
					vw_insc_examen_log.plan_version,
					to_char(vw_insc_examen_log.fecha_inscripcion, 'DD/MM/YYYY HH:MM:SS') as fecha_inscripcion_visual,
					vw_mesas_examen.turno_examen,
					vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
					vw_mesas_examen.llamado_nombre as llamado_nombre,
					vw_mesas_examen.anio_academico,
					vw_mesas_examen.turno_examen_nombre as turno_nombre,
					vw_mesas_examen.mesa_examen_fecha as fecha_examen,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen_visual,
					vw_insc_examen_log.instancia,
					sga_instancias.nombre as instancia_nombre,
					sga_catedras.nombre as catedra_nombre,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_espacios_llamado_mesa(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre, 
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					vw_insc_examen_log.operacion as estado,
					CASE
					  WHEN vw_insc_examen_log.operacion = 'R' THEN 'Rechazado'
					  WHEN vw_insc_examen_log.operacion = 'B' THEN 'Baja'
					  ELSE ''
					END as estado_nombre,
					vw_actividades.elemento as actividad_elemento,
					vw_actividades.codigo as actividad_codigo,
					vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_nombre,
					vw_insc_examen_log.nro_transaccion_log, -- Baja o Rechazo
					to_char(vw_insc_examen_log.fecha_operacion, $formato_fecha_hora_visual_sin_segundos) as fecha_log, -- Baja o Rechazo
					vw_insc_examen_log.fecha_operacion as fecha_orden,
					int_pv_cursos_llamados_mesa.curso as moodle_curso
				FROM
					sga_alumnos
					JOIN vw_insc_examen_log ON vw_insc_examen_log.alumno = sga_alumnos.alumno 
					JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen_log.llamado_mesa 
					JOIN sga_ubicaciones ON  vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion 
					JOIN sga_instancias ON vw_insc_examen_log.instancia = sga_instancias.instancia 
					JOIN vw_actividades ON vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento 
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta 
					LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
					LEFT JOIN int_pv_cursos_llamados_mesa	ON (int_pv_cursos_llamados_mesa.llamado_mesa = vw_mesas_examen.llamado_mesa)
				
				WHERE sga_alumnos.persona = {$parametros['persona']}
					$where_estado_2
					$where_vigentes
				";
				
		// Todas
		if ($parametros['estado'] == "'T'") {
			$sql_insc = "$sql UNION ALL $sql_insc_rechazadas_bajas";
		}

		// Aceptadas y/o Pendientes
		if (($parametros['estado'] == "'A'") || ($parametros['estado'] == "'P'") || ($parametros['estado'] == "'AP'")) {
			$sql_insc = $sql;
		}
		
		// Rechazadas o Dadas de Baja
		if (($parametros['estado'] == "'R'") || ($parametros['estado'] == "'B'")) {
			$sql_insc = $sql_insc_rechazadas_bajas;
		}
		
		$sql_insc .= ' ORDER BY fecha_orden DESC';
		
		// Agrego el order by y consulto las inscripciones
		$datos = kernel::db()->consultar($sql_insc);
		
		foreach ($datos as $key => $dato) {
			$datos[$key]['url_inscripcion'] = catalogo::generar_id($dato['inscripcion']);
		}
		
		return $datos;
	}

	/**
	 * Recupera las inscripciones a cursadas de una persona en todas las propuestas, segun parametro recupera
	 * inscripciones vigentes o todas, y por estado
	 * 
	 * parametros: persona, estado, vigentes, cursos
	 * no_quote: cursos
	 * cache: memoria
	 * filas: n
	 */
	function inscripciones_cursadas($parametros)
	{
		// estado = T - Todos los estados (Aceptado, Pendiente, Rechazadas, Bajas) /  
		//          A - Aceptado  
		//          P - Pendientes  
		//          AP - Aceptadas y Pendientes
		//          R - Rechazadas
		//          B - Baja
		// $where_estado = ($parametros['estado'] == "'T'") ? '' : ($parametros['estado'] == "'AP'" ? " AND sga_insc_cursada.estado IN ('A','P')" : " AND sga_insc_cursada.estado = {$parametros['estado']}");

		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);

		$where_estado_1 = "";
		$where_estado_2 = "";
		$where_cursos = "";
		if ($parametros['estado'] == "'A'" || $parametros['estado'] == "'P'") {
			$where_estado_1 = " AND sga_insc_cursada.estado = {$parametros['estado']}";
		}
		if ($parametros['estado'] == "'AP'") {
			$where_estado_1 = " AND sga_insc_cursada.estado IN ('A','P')";
		}
		if ($parametros['estado'] == "'R'" || $parametros['estado'] == "'B'") {
			$where_estado_2 = " AND sga_insc_cursada_log.estado = {$parametros['estado']}";
		}

		// vigentes = S - Solo de periodos lectivos actuales o futuros /  N - Vigentes e historicas
		if ($parametros['vigentes'] == "'S'") {
			$where_vigentes = " AND sga_periodos.fecha_fin >= CURRENT_DATE ";
		} else {
			$where_vigentes = "";
		}

		if ($parametros['cursos'] == true) {
			$where_cursos = " AND sga_propuestas.propuesta_tipo = " . \g3entidad_nucleo::subtipo_propuesta_curso;
		}


		// Inscripciones Aceptadas y/o Pendientes
		$sql_aceptadas = " SELECT
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_ubicaciones.nombre as ubicacion_nombre,
					sga_propuestas.nombre_abreviado	as propuesta_nombre_abreviado,
					sga_propuestas.propuesta  as propuesta,
					sga_elementos.elemento	as actividad,
					sga_elementos_plan.nombre	as actividad_nombre,
					sga_elementos.codigo	as actividad_codigo,
					sga_periodos.anio_academico,
					sga_periodos.fecha_inicio,
					sga_periodos.nombre	as periodo_lectivo_nombre,
					sga_comisiones.comision as comision,
					sga_comisiones.nombre	as comision_nombre,
					sga_turnos_cursadas.nombre	as turno_nombre,
					sga_catedras.nombre	as catedra_nombre,
					f_instancias_insc_cursada(sga_insc_cursada.inscripcion) as instancias,
					sga_insc_cursada.estado as estado,
					sga_inscripciones_estados.nombre as estado_nombre,
					sga_insc_cursada.inscripcion,
					sga_insc_cursada.nro_transaccion,
					sga_insc_cursada.plan_version,
					to_char(sga_insc_cursada.fecha_inscripcion,$formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion_rechazo,
					cast(null as integer) as nro_transaccion_rechazo,
					(SELECT count(*) FROM sga_subcomisiones WHERE comision = sga_comisiones.comision) as con_subcomisiones,
					sga_insc_cursada.fecha_inscripcion as fecha_orden,
					int_pv_cursos_comisiones.curso as moodle_curso
				FROM
					sga_alumnos
					JOIN sga_insc_cursada				ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
					JOIN sga_inscripciones_estados		ON (sga_insc_cursada.estado = sga_inscripciones_estados.estado)
					JOIN sga_comisiones					ON (sga_comisiones.comision = sga_insc_cursada.comision)
					JOIN sga_ubicaciones				ON (sga_ubicaciones.ubicacion = sga_comisiones.ubicacion)

					JOIN sga_elementos					ON (sga_elementos.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_revision			ON (sga_elementos_revision.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_plan				ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND sga_elementos_plan.plan_version = sga_insc_cursada.plan_version)
					
					JOIN sga_periodos_lectivos			ON (sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo)
					JOIN sga_periodos					ON (sga_periodos_lectivos.periodo  = sga_periodos.periodo)
					JOIN sga_propuestas					ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
					LEFT JOIN sga_catedras				ON (sga_catedras.catedra = sga_comisiones.catedra)
					LEFT JOIN sga_turnos_cursadas		ON (sga_turnos_cursadas.turno = sga_comisiones.turno)
					LEFT JOIN int_pv_cursos_comisiones	ON (int_pv_cursos_comisiones.comision = sga_comisiones.comision)
			WHERE sga_alumnos.persona = {$parametros['persona']}
			$where_estado_1
			$where_vigentes
			$where_cursos
			";

		// Inscripciones Rechazadas o dadas de Baja
		$sql_rechazadas_baja = "
			        SELECT
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_ubicaciones.nombre as ubicacion_nombre,
					sga_propuestas.nombre_abreviado	as propuesta_nombre_abreviado,
					sga_propuestas.propuesta  as propuesta,
					sga_elementos.elemento	as actividad,
					sga_elementos_plan.nombre	as actividad_nombre,
					sga_elementos.codigo	as actividad_codigo,
					sga_periodos.anio_academico,
					sga_periodos.fecha_inicio,
					sga_periodos.nombre	as periodo_lectivo_nombre,
					sga_comisiones.comision as comision,
					sga_comisiones.nombre	as comision_nombre,
					sga_turnos_cursadas.nombre	as turno_nombre,
					sga_catedras.nombre	as catedra_nombre,
					cast(null as varchar) as instancias,
					sga_insc_cursada_log.operacion as estado,
					CASE
					  WHEN sga_insc_cursada_log.operacion = 'R' THEN 'Rechazada'
					  WHEN sga_insc_cursada_log.operacion = 'B' THEN 'Baja'
					  WHEN sga_insc_cursada_log.operacion = 'C' THEN 'Baja por cambio de comisión'
					  ELSE ''
					END as estado_nombre,
					sga_insc_cursada_log.inscripcion as inscripcion,
					cast(null as integer) as nro_transaccion,
					sga_insc_cursada_log.plan_version,
					to_char(sga_insc_cursada_log.fecha_operacion,$formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion_rechazo,
					sga_insc_cursada_log.nro_transaccion_log as nro_transaccion_rechazo,
					0 as con_subcomisiones,
					sga_insc_cursada_log.fecha_operacion as fecha_orden,
					int_pv_cursos_comisiones.curso as moodle_curso
				FROM
					sga_alumnos
					JOIN sga_insc_cursada_log			ON (sga_insc_cursada_log.alumno = sga_alumnos.alumno)
					JOIN sga_comisiones					ON (sga_comisiones.comision = sga_insc_cursada_log.comision)

					JOIN sga_elementos					ON (sga_elementos.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_revision			ON (sga_elementos_revision.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_plan				ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND sga_elementos_plan.plan_version = sga_insc_cursada_log.plan_version)
					
					JOIN sga_ubicaciones				ON (sga_ubicaciones.ubicacion = sga_comisiones.ubicacion)
					JOIN sga_periodos_lectivos			ON (sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo)
					JOIN sga_periodos					ON (sga_periodos_lectivos.periodo  = sga_periodos.periodo)
					JOIN sga_propuestas					ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
					LEFT JOIN sga_catedras				ON (sga_catedras.catedra = sga_comisiones.catedra)
					LEFT JOIN sga_turnos_cursadas		ON (sga_turnos_cursadas.turno = sga_comisiones.turno)
					LEFT JOIN int_pv_cursos_comisiones	ON (int_pv_cursos_comisiones.comision = sga_comisiones.comision)
			WHERE sga_alumnos.persona = {$parametros['persona']}
			$where_estado_2
			$where_vigentes
			$where_cursos
			";


		if ($parametros['estado'] == "'R'" || $parametros['estado'] == "'B'") {
			$sql = $sql_rechazadas_baja;
		}
		if ($parametros['estado'] == "'A'" || $parametros['estado'] == "'P'" || $parametros['estado'] == "'AP'") {
			$sql = $sql_aceptadas;
		}
		if ($parametros['estado'] == "'T'") {
			$sql = $sql_aceptadas . ' UNION ALL ' . $sql_rechazadas_baja;
		}
		$sql .= ' ORDER BY fecha_orden DESC';

		$datos = kernel::db()->consultar($sql);
		
		foreach ($datos as $key => $dato) {
			$datos[$key]['url_actividad'] = catalogo::generar_id($dato['actividad_codigo']);
			$datos[$key]['url_comision'] = catalogo::generar_id($dato['comision']);
			$datos[$key]['url_inscripcion'] = catalogo::generar_id($dato['inscripcion']);
		}
		
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: n
	 */
	function inscripciones_subcomisiones($parametros)
	{
		$sql = "SELECT 
					-- Subcomisiones 
					sga_subcomisiones.subcomision as subcomision,
					sga_subcomisiones.nombre as subcomision_nombre,
					sga_clases_tipos.nombre as tipo_clase,
					f_horario_subcomision(sga_subcomisiones.subcomision) as horario_subcomision,
					f_docentes_subcomision(sga_subcomisiones.subcomision, true) as docentes_subcomision
				FROM sga_insc_subcomision
				JOIN sga_subcomisiones ON sga_subcomisiones.subcomision = sga_insc_subcomision.subcomision
				JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
				WHERE sga_insc_subcomision.inscripcion = {$parametros['inscripcion']}
			";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * cache: no
	 * filas: 1
	 */
	function get_cantidad_activos()
	{
		// Cantidad de alumnos activos	
		$sql = "SELECT COUNT(DISTINCT persona) as cantidad FROM sga_alumnos WHERE calidad = 'A'";
		$rs = kernel::db()->consultar_fila($sql);
		return $rs['cantidad'];
	}

	/**
	 * parametros: apellido
	 * cache: no
	 * filas: n
	 */
	function get_alumnos_activos($parametros)
	{
		$apellido = kernel::db()->quote_like(substr($parametros['apellido'], 1, -1), '%', '%');
		$sql = "SELECT DISTINCT v.persona, v.apellido, v.nombres, v.tipo_nro_documento
				FROM vw_personas as v
				JOIN sga_alumnos as a ON a.persona = v.persona
				WHERE f_limpiar_acentos(v.apellido) ILIKE $apellido
				  AND a.calidad = 'A'
				ORDER BY v.apellido, v.nombres";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: term, docente
         * no_quote: term
	 * cache: no
	 * filas: n
	 */
	function get_alumnos_activos_comisiones_mesas_un_docente($parametros)
	{
		// Obtiene los alumnos de las comisiones vigentes y de las mesas desde 20 dias atras 
		$termino = kernel::db()->quote_like($parametros['term'], '%', '%');
		$cant_dias_atras_mesa  = 20;
		
		$sql = "SELECT DISTINCT v.persona, v.apellido_nombres_elegido, v.tipo_nro_documento
				  FROM sga_docentes_comision as dc
				  JOIN sga_comisiones as c ON c.comision = dc.comision
				  JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo 
				  JOIN sga_periodos as p ON p.periodo = pl.periodo
				  JOIN sga_insc_cursada as i ON i.comision = dc.comision 
				  JOIN sga_alumnos as a ON a.alumno = i.alumno
				  JOIN vw_personas as v ON a.persona = v.persona
				 WHERE dc.docente = {$parametros['docente']}
				   AND CURRENT_DATE >= p.fecha_inicio
				   AND CURRENT_DATE <= pl.fecha_inactivacion
				   AND (
                                    f_limpiar_acentos(v.apellido_nombres_elegido) ILIKE $termino
                                    OR f_limpiar_acentos(v.nro_documento) ILIKE $termino
                                    )
				   AND a.calidad = '".\alumno::calidad_activo."'
				UNION 
                        SELECT DISTINCT v.persona, v.apellido_nombres_elegido, v.tipo_nro_documento
				  FROM sga_docentes_mesa_llamado as dm
				  JOIN sga_llamados_mesa as llm ON llm.llamado_mesa = dm.llamado_mesa
				  JOIN sga_insc_examen as i ON i.llamado_mesa = llm.llamado_mesa
				  JOIN sga_alumnos as a ON a.alumno = i.alumno
				  JOIN vw_personas as v ON a.persona = v.persona
				 WHERE dm.docente = {$parametros['docente']}
				   AND llm.fecha >= (CURRENT_DATE - $cant_dias_atras_mesa)
                                   AND (
                                    f_limpiar_acentos(v.apellido_nombres_elegido) ILIKE $termino
                                    OR f_limpiar_acentos(v.nro_documento) ILIKE $termino
                                    )
				   AND a.calidad = '".\alumno::calidad_activo."'
			ORDER BY apellido_nombres_elegido";
		return kernel::db()->consultar($sql);
	}


	/**
	 * parametros: alumno
	 * cache: memoria
	 * filas: 1
	 */
	function get_datos($parametros)
	{
		// Datos básicos del alumno
		$sql = "SELECT	sga_alumnos.persona,
						sga_alumnos.alumno,
						sga_alumnos.legajo,
						sga_alumnos.propuesta,
						sga_alumnos.plan_version,
						vw_personas.apellido_nombres,
						vw_personas.nro_documento,
						vw_personas.tipo_nro_documento,
						vw_personas.apellido_nombres_elegido as nombre_alumno,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado propuesta_nombre_abreviado
				FROM	sga_alumnos
				JOIN	vw_personas ON (sga_alumnos.persona = vw_personas.persona)
				JOIN	sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				WHERE	sga_alumnos.alumno = {$parametros['alumno']}
		";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: alumno
	 * cache: no
	 * filas: 1
	 */
	function get_datos_ficha($parametros)
	{
		// Apellido y nombre del alumno	
		$sql = "SELECT	vw_personas.apellido_nombres_elegido as nombre_alumno
				FROM	sga_alumnos, 
						vw_personas
				WHERE	sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.alumno = {$parametros['alumno']}
		";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * 	parametros: term, alumno, elemento, evaluacion
	 * 	param_null: term, alumno
         *      no_quote: term
	 * 	cache: memoria
	 *
	 * 
	 */
	function get_alumnos_actas_fuera_calendario($parametros)
	{
		$where = '';
		if (!empty($parametros['term'])) {
			//Agrego los % %
			$term = kernel::db()->quote_like($parametros['term'], '%', '%');
			$where = " AND (f_limpiar_acentos(vw_personas.apellido_nombres_elegido) ILIKE $term OR
					f_limpiar_acentos(sga_alumnos.legajo)                   ILIKE $term OR
				        f_limpiar_acentos(vw_personas.nro_documento)            ILIKE $term )";
		} else {
			$where = " AND sga_alumnos.alumno = {$parametros['alumno']}";
		}
		// Alumnos activos de planes de estudios que tienen la actividad pasada por parametro.		
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado propuesta_nombre_abreviado,
						vw_personas.nro_documento,
						vw_personas.tipo_nro_documento,
						vw_personas.apellido_nombres_elegido
				FROM 	sga_alumnos
				JOIN	vw_personas		ON (vw_personas.persona = sga_alumnos.persona)
				JOIN	sga_propuestas	ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				WHERE sga_alumnos.calidad = '" . \alumno::calidad_activo . "'
				  AND sga_alumnos.plan_version IN (SELECT sga_elementos_plan.plan_version 
				                                     FROM sga_elementos_plan, 
				                                          sga_elementos_revision
				                                    WHERE sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
				                                      AND sga_elementos_revision.elemento = {$parametros['elemento']}
				                                   )" .
				$where . "
				ORDER BY apellido_nombres_elegido";
		if (!empty($parametros['term'])) {
			return kernel::db()->consultar($sql);
		}

		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: alumno, evaluacion, id_acta
	 * param_null: id_acta
	 * cache: no
	 * filas: n
	 */
	function alumno_en_acta_abierta($parametros)
	{
		// Verifica si el alumno se encuentra en una mesa de examen o en un acta de examen abierta de esa mesa de examen.
		$acta = '';
		if (isset($parametros['id_acta'])) {
			$acta = "AND sga_eval_detalle_examenes.id_acta != {$parametros['id_acta']}";
		}

		$sql = "SELECT count(*)
				FROM sga_eval_detalle_examenes
				WHERE sga_eval_detalle_examenes.evaluacion = {$parametros['evaluacion']}
				AND sga_eval_detalle_examenes.alumno = {$parametros['alumno']} 
				" . $acta
		;

		$resultado = kernel::db()->consultar_fila($sql);
		return $resultado['count'] != 0;
	}

	/**
	 * parametros: alumno, evaluacion
	 * cache: no
	 * filas: n
	 */
	function alumno_en_acta_cerrada($parametros)
	{

		$sql = "SELECT count(*)
				FROM sga_actas
				JOIN sga_actas_detalle ON (sga_actas_detalle.id_acta = sga_actas.id_acta)
				WHERE sga_actas.evaluacion =  {$parametros['evaluacion']}
				AND sga_actas_detalle.alumno = {$parametros['alumno']}
				AND sga_actas.estado = 'C'
				AND sga_actas_detalle.estado = 'A'
				AND sga_actas_detalle.rectificado = 'N'
				";

		$resultado = kernel::db()->consultar_fila($sql);
		return $resultado['count'] != 0;
	}

	/**
	 * parametros: persona, propuesta
	 * cache: no
	 * filas: n
	 */
	function get_inasistencias($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
					sga_clases.fecha AS clase_fecha,
					to_char(sga_clases.fecha, $formato_fecha_visual) as clase_fecha_visual,
					sga_elementos.elemento,
					'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre	AS actividad_cod_nombre,
					sga_comisiones.comision,
					sga_comisiones.nombre AS comision_nombre,
					sga_clases.tema_dictado,
					sga_clases_tipos.nombre AS clase_tipo_nombre,
					sga_clases_asistencia.cant_inasistencias,
					sga_clases_asistencia.cant_justificadas,
					CASE WHEN sga_clases_asistencia.cant_justificadas > 0 THEN 'Sí' ELSE 'No' END AS justificado
					
				FROM sga_alumnos
				JOIN sga_clases_asistencia ON sga_clases_asistencia.alumno = sga_alumnos.alumno
				JOIN sga_clases ON sga_clases.clase = sga_clases_asistencia.clase
				JOIN sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
				JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				JOIN sga_comisiones ON sga_comisiones.comision = sga_comisiones_bh.comision
				JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
				JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				
				WHERE sga_alumnos.persona = {$parametros['persona']}
				  AND sga_alumnos.propuesta = {$parametros['propuesta']}
				  -- Información correspondiente períodos lectivos vigentes
				  AND CURRENT_DATE BETWEEN sga_periodos.fecha_inicio AND sga_periodos.fecha_fin
				  AND sga_clases.valido = 1  -- Clase válida
				  AND sga_clases_asistencia.cant_inasistencias > 0  -- Sólo clases donde el alumno estuvo ausente
				ORDER BY
					sga_elementos.elemento,
					sga_comisiones.comision,
					sga_clases.fecha DESC
				";

		$datos = kernel::db()->consultar($sql);

		// Se formatea el arreglo de salida para mantener las agrupaciones por actividad
		$salida = array();
		$comision = -1;
		$elemento_descr = '';
		foreach ($datos as $dato) {
			if ($dato['comision'] != $comision) {
				$comision = $dato['comision'];
				$elemento_descr = $dato['actividad_cod_nombre'] . ' - Comisión: ' . $dato['comision_nombre'];
				$salida[$elemento_descr] = array();
			}
			$salida[$elemento_descr][] = $dato;
		}

		return $salida;
	}

	/**
	 * parametros: term
	 * cache: no
	 * no_quote: term
	 * filas: n
	 */
	function buscar_alumno($parametros)
	{
		$termino = strtolower($parametros['term']);
		$delimitadores = array(",", ";");
		$termino = trim(str_replace($delimitadores, " ", $termino));
		$terminos = explode(" ", $termino);
		$no_vacio = function($string) {
			return $string != "";
		};
		// se quitan los terminos vacios
		$terminos = array_filter($terminos, $no_vacio);
		// se quitan los terminos repetidos
		$terminos = array_unique($terminos);

		$like = "";
		if (\guarani_arreglos::count($terminos) > 0) {
			$i = 0;
			$like .= " AND (";
			foreach ($terminos as $term) {

				if ($i != 0) {
					$like .= " AND ";
				}

				$like .= "LOWER(p.apellido || ' ' || p.nombres || ' ' || a.legajo || ' ' || p.nro_documento) LIKE " . kernel::db()->quote_like($term, '%', '%');
				$i++;
			}
			$like .= ")";
		}

		$sql = "SELECT 
					p.persona,
					'(' || p.desc_tipo_documento || ': ' || p.nro_documento || ') ' || p.apellido || ', ' || p.nombres || COALESCE(' (' || a.legajo || ')', '') as descripcion,
					p.apellido || ', ' || p.nombres as descripcion_persona
				FROM
					sga_alumnos AS a,
					vw_personas AS p
				WHERE
					a.persona = p.persona
					$like
				ORDER BY
					descripcion";

		$datos = kernel::db()->consultar($sql);

		if (!empty($datos)) {
			foreach ($datos as $key => $value) {
				$datos[$key][catalogo::id] = catalogo::generar_id($value['persona']);
			}
		}
		return $datos;
	}

	/**
	 * @deprecated Usar "\alumno::get_contenido_plan_version" en su lugar.
	 * parametros: plan_version, alumno
	 * no_quote: plan_version, alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_contenido_plan_version($parametros)
	{

		$params = array(
			'conocer_optativas_compartidas' => true,
			'alumno' => $parametros['alumno']
		);
		$contenido_plan_version = \toba::consulta_php('co_planes')->get_contenido_plan_version($parametros['plan_version'], $params);
		$detalle_materias_plan_version = catalogo::consultar('plan_estudio', 'get_detalle_materias_plan_version', $parametros);
		$arbol = array();
		$arbol['contenido_plan_version'] = $contenido_plan_version;
		$arbol['detalle_materias_plan_version'] = $detalle_materias_plan_version;

		return $arbol;
	}

	static function obtener_elemento_raiz(array &$contenido_plan_version)
	{

		$elemento_raiz = null;

		foreach ($contenido_plan_version as $key => $elemento_plan) {
			if ($elemento_plan['entidad_subtipo'] == \elemento::subtipo_raiz) {
				$elemento_raiz = $elemento_plan;
				unset($contenido_plan_version[$key]);
				return $elemento_raiz;
			}
		}
	}

	//http://stackoverflow.com/questions/8840319/build-a-tree-from-a-flat-array-in-php
	static function buildTree(array &$elements, $parentId = 0)
	{

		$branch = array();

		foreach ($elements as &$element) {

			if ($element['elemento_padre'] == $parentId) {
				$children = static::buildTree($elements, $element['elemento']);
				if (isset($children['modulos_genericas'])) {
					$element['modulos_genericas'] = $children['modulos_genericas'];
				}

				if (isset($children['materias'])) {
					$element['materias'] = $children['materias'];
				}

				if ($element['entidad_tipo'] != \elemento::tipo_actividad) {
					$branch['modulos_genericas'][$element['orden']] = $element;
				} else {
					$branch['materias'][$element['orden']] = $element;
				}
				unset($element);
			}
		}
		if (isset($branch['modulos_genericas']) && is_array($branch['modulos_genericas']))
			ksort($branch['modulos_genericas']);
		if (isset($branch['materias']) && is_array($branch['materias']))
			ksort($branch['materias']);
		return $branch;
	}

	/**
	 * Recupera los requisitos de ingreso a la propuesta que el alumno tiene asignados
	 * parametros: alumno, estado_req_digital
	 * no_quote: alumno, estado_req_digital
	 * param_null: estado_req_digital
	 * cache: memoria
	 * filas: n
	 */
	function get_requisitos_ingreso_propuesta($parametros)
	{
		// Si busco requisitos pendientes, no muestro aquellos que dependen (subordinado) de otro que ya esta presentado.
		// Ej "titulo secundario en tramite" depende del requisito "titulo secundario"
		// Requisitos de la propuesta del alumno segun el período de inscripcion en la propuesta.
		// El requisito puede estar presentado en años anteriores y ya no ser valido porque su presentacion es de otro año anterior y tenia vigencia...
		// Fecha tope de presentacion: 1-Fecha prorroga del requisito o 2-Fecha Excepcion en el periodo o 3-Fecha cierre requisitos del periodo
		// La funcion "f_requisito_presentado" verifica si esta presentado, exceptuado o subordinado de otro requisito que esta presentado o exceptuado.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sin_vencimiento = kernel::traductor()->trans('requisitos.sin_vencimiento');

		$case_estado_req_digital = clienteRDI::case_estado_req_digital();
		$case_observaciones = clienteRDI::case_observaciones();

		$where_estado_req_digital = "";
		// Si se filtra por estado_req_digital
		if (is_array($parametros['estado_req_digital'])) {
			$estado_req_digital = implode("', '", $parametros['estado_req_digital']);
			$where_estado_req_digital = "AND {$case_estado_req_digital} IN ('{$estado_req_digital}')";
		}

		$sql = "SELECT
					r.requisito as requisito,
					r.nombre as requisito_nombre,
					to_char(sga_requisitos_presentados.fecha_presentacion, $formato_fecha_visual) as f_presentacion,
					to_char(GREATEST(ra.fecha_prorroga, COALESCE(excep.fecha_tope, pip.fecha_cierre_requisitos)), $formato_fecha_visual) as f_tope_presentacion,
					CASE WHEN GREATEST(ra.fecha_prorroga, COALESCE(excep.fecha_tope, pip.fecha_cierre_requisitos)) >= CURRENT_DATE THEN 1
					ELSE 0 END as esta_vigente_para_presentar,
					COALESCE(to_char(sga_requisitos_presentados.fecha_vencimiento, $formato_fecha_visual), '{$sin_vencimiento}') as f_vencimiento_presentado,
					r.requisito_subordinado_de as subordinado_de,
					r.descripcion,
					r.tipo_documento_digital,
					sga_requisitos_digitales_draft.requisito_digital,
					COALESCE(sga_requisitos_digitales_draft.id_documento, sga_requisitos_digitales.id_documento) AS id_documento,
					pa.propuesta_aspira,
					$case_estado_req_digital AS estado_req_digital,
					CASE WHEN sga_requisitos_digitales_draft.requisito_digital IS NOT NULL THEN 'P' ELSE 'N' END as estado_req_digital_valor,
					$case_observaciones AS observaciones
					
			FROM sga_alumnos as alu 
			JOIN sga_propuestas_aspira as pa ON (pa.propuesta = alu.propuesta AND pa.persona = alu.persona)
			JOIN vw_periodos_inscripcion as pi ON (pi.periodo_insc = pa.periodo_insc)
			JOIN sga_periodos_inscripcion_propuesta as pip ON (pip.periodo_inscripcion = pi.periodo_inscripcion)
			JOIN sga_situacion_aspirante as sa ON (sa.situacion_asp = pa.situacion_asp) 
			JOIN sga_requisitos_aspirante as ra ON (ra.propuesta_aspira = pa.propuesta_aspira)
			JOIN sga_requisitos as r ON (r.requisito = ra.requisito)
			LEFT JOIN sga_requisitos_ingreso_excep as excep ON (excep.periodo_inscripcion = pi.periodo_inscripcion AND excep.requisito = ra.requisito) 
			LEFT JOIN sga_requisitos_presentados ON (
				sga_requisitos_presentados.requisito_presentado =
				(
					SELECT MAX(rp_aux.requisito_presentado)
					FROM sga_requisitos_presentados AS rp_aux
					WHERE rp_aux.requisito = ra.requisito
					AND rp_aux.persona = pa.persona
				)
			)
			LEFT JOIN sga_requisitos_digitales ON (sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital)
			-- Borrador del documento digital del requisito subido por el alumno.																				
			LEFT JOIN sga_requisitos_digitales_draft ON (sga_requisitos_digitales_draft.requisito = r.requisito AND sga_requisitos_digitales_draft.persona = alu.persona)
			LEFT JOIN sga_requisitos_digitales_draft_detalle ON (
				alu.persona = sga_requisitos_digitales_draft_detalle.persona
				AND r.requisito = sga_requisitos_digitales_draft_detalle.requisito
			)
			WHERE alu.alumno = {$parametros['alumno']}
			AND sa.resultado_asp IN ('".\propuesta::inscripcion_aceptada."', '".\propuesta::inscripcion_pendiente."')
			AND r.requisito_tipo = '" . \requisito::tipo_documentales . "'
			$where_estado_req_digital
			ORDER BY requisito_nombre
			";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $key => $r) {
			if(isset($r['requisito'])){
				$datos[$key][catalogo::id] = catalogo::generar_id($r['requisito']);
			}
		}
		return $datos;
	}

	/**
	 * parametros: alumno, propuesta
	 * cache: memoria
	 * filas: n
	 */
	function titulo_solicitudes($parametros)
	{
		// Recupera las solicitudes de titulos/certificaciones del alumno en la propuesta
		$sql = "SELECT
				sga_certificados_otorg.nro_solicitud as nro_solicitud,
				sga_certificados_otorg.certificado as certificado,
				sga_certificados.nombre as certificado_nombre,
				sga_certificados.titulo_nivel as certificado_nivel
			FROM
				sga_certificados_otorg
				JOIN sga_certificados ON sga_certificados.certificado = sga_certificados_otorg.certificado
				
			 WHERE sga_certificados_otorg.alumno = {$parametros['alumno']}
			   AND sga_certificados_otorg.anulado = 0";


		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);

		if (!empty($datos)) {
			foreach ($datos as $key => $value) {
				$datos[$key][catalogo::id] = catalogo::generar_id($value['nro_solicitud']);
			}
		}
		return $datos;
	}

	/**
	 * parametros: nro_solicitud
	 * cache: no
	 * filas: 1
	 */
	function titulo_datos_solicitud($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		// Recupera los datos de la solicitud de un titulo
		$sql = "SELECT
				sga_certificados_otorg.nro_solicitud as nro_solicitud,
				sga_certificados_otorg.persona as persona,
				sga_certificados_otorg.certificado as certificado,
				sga_certificados.nombre as certificado_nombre,
				sga_certificados.titulo_nivel as certificado_titulo_nivel,
				sga_certificados_otorg.alumno as alumno,
				sga_alumnos.legajo as legajo,
				sga_propuestas.nombre as propuesta_nombre,
				sga_planes.codigo as plan_codigo,
				sga_planes.nombre as plan_nombre,
				sga_certificados_otorg.plan_version,
				sga_certificados_otorg.circuito,
				to_char(sga_certificados_otorg.fecha_inicio_tramite, $formato_fecha_visual) as fecha_inicio_tramite,
				to_char(sga_certificados_otorg.fecha_egreso, $formato_fecha_visual) as fecha_egreso,
				sga_certificados_otorg.promedio as promedio,
				sga_certificados_otorg.promedio_sin_aplazos as promedio_sin_aplazos,
				sga_certificados_otorg.juramento,
				sga_juramentos.descripcion as juramento_desc,
				sga_juramentos.descripcion_abreviada as juramento_desc_abrev,
				sga_certificados_otorg.colacion,
				sga_colaciones.nombre || ' (' || to_char(sga_colaciones.fecha, $formato_fecha_visual) || ')' as colacion_desc_fecha,
				to_char(sga_certificados_otorg.fecha_cambio_estado, $formato_fecha_visual) as fecha_ultimo_cambio_estado,
				mce_estados.nombre as estado_actual,
				sga_certificados_otorg.observaciones as observaciones,
				to_char(sga_certificados_otorg.fecha_disposicion_decano, $formato_fecha_visual) as fecha_disposicion_decano,
				to_char(sga_certificados_otorg.fecha_aprobacion_conacad, $formato_fecha_visual) as fecha_aprobacion_conacad,
				to_char(sga_certificados_otorg.fecha_aprobacion_consup, $formato_fecha_visual) as fecha_aprobacion_consup,
				to_char(sga_certificados_otorg.fecha_elevacion_consup, $formato_fecha_visual) as fecha_elevacion_consup,
				to_char(sga_certificados_otorg.fecha_firma_autoridad, $formato_fecha_visual) as fecha_firma_autoridad,
				to_char(sga_certificados_otorg.fecha_ingreso_conacad, $formato_fecha_visual) as fecha_ingreso_conacad,
				sga_certificados_otorg.nro_expediente as nro_expediente,
				sga_certificados_otorg.nro_expediente_externo as nro_expediente_externo,
				sga_certificados_otorg.nro_disposicion_decano as nro_disposicion_decano,
				sga_certificados_otorg.nro_registro_diploma_fac as nro_registro_diploma_fac,
				sga_certificados_otorg.nro_registro_diploma_uni as nro_registro_diploma_uni,
				sga_certificados_otorg.resolucion_rectorado as resolucion_rectorado,
				sga_certificados_otorg.folio_registro_diploma_fac as folio_registro_diploma_fac,
				sga_certificados_otorg.orden_registro_diploma_fac as orden_registro_diploma_fac,
				sga_certificados_otorg.libro_registro_diploma_fac as libro_registro_diploma_fac
			FROM
				sga_certificados_otorg
				JOIN sga_certificados ON sga_certificados.certificado = sga_certificados_otorg.certificado
				JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_certificados_otorg.alumno
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta	
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_certificados_otorg.plan_version
				JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				LEFT JOIN sga_colaciones ON sga_colaciones.colacion = sga_certificados_otorg.colacion
				LEFT JOIN sga_juramentos ON sga_juramentos.juramento = sga_certificados_otorg.juramento
				
			 WHERE sga_certificados_otorg.nro_solicitud = {$parametros['nro_solicitud']}";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: nro_solicitud
	 * cache: no
	 * filas: n
	 */
	function titulo_cambios_de_estado($parametros)
	{
		// Recupera los movimientos del titulo. Cambios de estado del circuito de egreso
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
			       to_char(sga_certificados_otorg_cmb.fecha, $formato_fecha_visual) as fecha,
			       mce_acciones.nombre as accion_nombre,
			       anterior.nombre as estado_anterior_desc,
			       nuevo.nombre as estado_nuevo_desc,
			       sga_certificados_otorg_cmb.observaciones as observaciones,
			       sga_certificados_otorg_cmb.nro_solicitud as nro_solicitud
			  FROM sga_certificados_otorg_cmb
			       JOIN mce_estados as nuevo ON nuevo.estado = sga_certificados_otorg_cmb.estado_nuevo
			       JOIN mce_acciones ON mce_acciones.accion = sga_certificados_otorg_cmb.accion
			       LEFT JOIN mce_estados anterior ON anterior.estado = sga_certificados_otorg_cmb.estado_anterior
			 WHERE sga_certificados_otorg_cmb.nro_solicitud = {$parametros['nro_solicitud']}
			ORDER BY sga_certificados_otorg_cmb.fecha DESC";

		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * parametros: persona, requisito, id_documento
	 * no_quote: persona, requisito, id_documento
	 * cache: no
	 * filas: n
	 */
	function guardar_requisito_draft($parametros)
	{
		abm::alta('sga_requisitos_digitales_draft', $parametros);
	}

	/**
	 * parametros: persona, requisito, id_documento
	 * no_quote: persona, requisito, id_documento
	 * cache: no
	 * filas: n
	 */
	function eliminar_requisito_draft($parametros)
	{
		abm::baja('sga_requisitos_digitales_draft', $parametros);
	}

	/**
	 * parametros: datos, claves
	 * no_quote: datos, claves
	 * cache: no
	 * filas: n
	 */
	function actualizar_requisito_draft($parametros)
	{
		/* EJEMPLO
		 * $datos_aspirante = [
		  'claves' => ['requisito_digital'=> 100],
		  'datos' => ['fecha_carga' => 'CURRENT_TIMESTAMP']
		  ];
		 */
		abm::modificacion('sga_requisitos_digitales_draft', $parametros['datos'], $parametros['claves']);
	}

	/**
	 * parametros: datos, claves
	 * no_quote: datos, claves
	 * cache: no
	 * filas: n
	 */
	function actualizar_requisito_aspirante($parametros)
	{
		/* EJEMPLO
		 * $datos_aspirante = [	
		  'claves' => ['requisito'=> $requisito['requisito'],'propuesta_aspira'=>$requisito['propuesta_aspira']],
		  'datos' => ['observaciones' => null]
		  ];
		 */
		abm::modificacion('sga_requisitos_aspirante', $parametros['datos'], $parametros['claves']);
	}

	/**
	 * parametros: persona, requisito
	 * no_quote: persona, requisito
	 * cache: no
	 * filas: n
	 */
	function eliminar_sga_requisitos_digitales_draft_detalle($parametros)
	{
		abm::baja('sga_requisitos_digitales_draft_detalle', $parametros);
	}

	/**
	 * parametros: alumno
	 * no_quote: alumno
	 * cache: no
	 * filas: 1
	 */
	function get_division($parametros)
	{
		$sql = "SELECT sga_alumnos.division
				FROM sga_alumnos
				WHERE alumno = {$parametros['alumno']}";

		$datos = kernel::db()->consultar($sql);
		return $datos[0]['division'];
	}
	
	/**
	 * parametros: alumno
	 * no_quote: alumno
	 * cache: memoria
	 * filas: 1
	 */
	function get_datos_basicos($parametros)
	{
		return \toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($parametros['alumno']);
	}
	
	/**
	 * parametros: alumno, tipo_titulo, solo_promediables
	 * no_quote: alumno, solo_promediables
	 * cache: memoria
	 * filas: 1
	 */
	function get_porcentaje_avance_propuesta($parametros)
	{
		$sql = "SELECT * FROM get_porcentaje_avance_propuesta({$parametros['alumno']}, {$parametros['tipo_titulo']}, {$parametros['solo_promediables']})";
		return kernel::db()->consultar($sql);
	}
	
}
