<?php

namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use siu\modelo\_comun\abm;
use siu\modelo\_comun\clienteRDI;
use siu\modelo\datos\db\alumno as alumno_nsg;

class alumno extends alumno_nsg
{

	function get_alumno_propuesta_cursos() 
	{
		$persona_id = kernel::persona()->get_id();
		if (!kernel::persona()->es_perfil_cursos()) { 
			return kernel::persona()->get_id_alumno_activo();
		} else {
			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', 
			[
				'persona' => $persona_id
			]);
			$aux_params = [
				'persona' => $persona_id,
				'propuesta' => null
			];
			foreach($propuestas as &$propuesta) {
				$curso = catalogo::consultar('cursos', 'get_cursos_academica', $aux_params);
				$propuesta['cursos'] = $curso;
			}
			$parametros['propuesta'] = $propuesta['propuesta'];
			$aux_params = [
				'persona' => $persona_id,
				'propuesta' => $parametros['propuesta']
			];
			$alumno = catalogo::consultar('alumno', 'get_alumno', $aux_params);
			$parametros['alumno'] = $alumno;
			$alumno = $parametros['alumno']['alumno'];
			return $alumno;
		}
	}

    /**
     * parametros:	persona, propuesta
     * no_quote:	persona, propuesta
     * cache: memoria
     * filas: n
     */
    function get_alumno($parametros)
    {
	$sql = "SELECT	alumno
                        FROM	sga_alumnos 
                        WHERE	persona	= {$parametros['persona']} 
			AND 	propuesta = {$parametros['propuesta']}";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
    }
		
	/**
	 * parametros: alumno
	 * no_quote: alumno
	 * cache: memoria
	 * filas: 1
	 */
	function get_datos_ctacte($parametros)
	{
		return \toba::consulta_php('co_facturacion')->get_ctacte_alumno($parametros['alumno']);
	}

/**
	 * @deprecated Usar "toba::consulta_php('co_alumnos', true)->get_actividad_academica($alumno)" en su lugar.
	 * parametros: alumno
	 * cache: memoria
	 * filas: n
	 */
	function get_actividad_academica($parametros)
	{
		// Historia acad�mica del Alumno: Examenes, Promociones, Regularidades, Equivalencias Totales y Equivalencias de Regularidad.
		// equivalencia_instancia = 10 - Total / 11 - Regularidad / 12 = Parcial
		// tipo = Aprobacion / Regularidades / Eq. Parcial / En Curso
		// Las equivalencias parciales se pueden recuperar con la vista vw_equiv_parciales. 
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_orden = kernel::db()->quote(\comunes::formato_fecha_orden);

		$sql = "SELECT	
						cast('Aprobacion' as varchar(11)) as tipo, -- Historia Academica - Aprobaciones
						sga_elementos_plan.anio_de_cursada, -- A�o de cursada definido en el plan
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
							WHEN 'A' THEN vw_hist_academica.nro_resolucion_descripcion  -- Aprobacion x Resoluci�n
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
						sga_elementos_plan.anio_de_cursada, -- A�o de cursada definido en el plan
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
						sga_elementos_plan.anio_de_cursada,    -- A�o de cursada definido en el plan
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
				AND (sga_periodos.fecha_fin >= CURRENT_DATE OR (sga_periodos.periodo_generico = 101 and date_part('year', sga_periodos.fecha_inicio) =  date_part('year', CURRENT_DATE)))  
				-- Comisiones actuales o futuras
			ORDER BY fecha_orden
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}


	
}