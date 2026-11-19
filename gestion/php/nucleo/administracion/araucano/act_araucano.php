<?php
/**
 *
 * @package Administración
 * @subpackage Araucanoa
 */
class act_araucano_nucleo
{
	//--------------------------------------------------------------------------
	//-- Carga la historia academica en la tabla
	//--------------------------------------------------------------------------
	function cargar_tabla_historia_academica($responsable_academica=null)
	{
		/*
		  Este metodo carga la tabla "int_arau_nominal_ha" con la historia academica de los alumnos.
		  Cursadas / Promociones / Examenes / Equivalencias Totales / Equivalencias de Regularidad / Aprobaciones de actividades por Resolucion.
		  
		  Esta tabla será consulta en los procesos de araucano para cargar informacion en la tabla "int_arau_nominal_actividades"
		  
		  origen_codigo: R = Cursada / P = Promocion / E = Examen / A = Aprob x Resolucion / B = Equivalencia Total / C = Equiv de Regularidad.
		*/
		
		// Filtro por RESPONSABLE ACADEMICA
		// Actividades de las propuestas de la RA. Estas actividades pueden estar en propuestas de otras RA (materia comun)
		if (is_null($responsable_academica)) {
			$responsable_academica = 'null';
			$where_ra = 'responsable_academica IS NULL';
			$join_ra = '';
		} else {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$where_ra = "responsable_academica = $responsable_academica";
			$join_ra = "JOIN (	SELECT	DISTINCT vw_actividades_plan.elemento
								FROM    sga_propuestas_ra
										JOIN vw_actividades_plan ON vw_actividades_plan.propuesta = sga_propuestas_ra.propuesta
								WHERE	sga_propuestas_ra.responsable_academica = $responsable_academica) as e ON e.elemento = sga_elementos.elemento";
		}

		$sql = "
          -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
          -- Borrado de Indices
          -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
          DROP INDEX IF EXISTS id_int_arau_nominal_ha1;
          DROP INDEX IF EXISTS id_int_arau_nominal_ha2;
          DROP INDEX IF EXISTS id_int_arau_nominal_ha3;

		  -- Borro los datos de la tabla.
		  DELETE
		  FROM	int_arau_nominal_ha
		  WHERE	$where_ra;

          -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
          -- Llenado de la tabla de Historia Academica
          -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
		  
		  -- **********************************************************************
          -- PROMOCIONES/CURSADAS. 
		  -- **********************************************************************
          INSERT INTO int_arau_nominal_ha (
				persona, alumno, plan_version, elemento, actividad_codigo, actividad_nombre, elemento_revision, 
                fecha, resultado, comision, origen, origen_codigo, nro_libro, nro_acta, folio_fisico, expediente,
				ubicacion, responsable_academica)
          SELECT 
             sga_alumnos.persona,
             sga_alumnos.alumno,
             sga_actas_detalle.plan_version,
             sga_comisiones.elemento,
         	 sga_elementos.codigo,
         	 sga_elementos.nombre,
             sga_elementos_revision.elemento_revision,
             sga_actas_detalle.fecha,
             sga_actas_detalle.resultado,
			 sga_actas.comision,
             CASE sga_actas.origen 
			   WHEN 'R' THEN cast('Cursada' as varchar(30)) 
			   WHEN 'P' THEN cast('Promoción' as varchar(30)) 
			 END as origen,
			 sga_actas.origen as origen_codigo,
         	 sga_libros_actas.nro_libro,
             sga_actas.nro_acta,
         	 sga_actas_folios.folio_fisico,
             cast(null as varchar(30)) as expediente,
			 sga_comisiones.ubicacion,
			 $responsable_academica as responsable_academica
          FROM 
             sga_actas
             JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
             JOIN sga_comisiones ON sga_comisiones.comision = sga_actas.comision
             JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
			 $join_ra
             JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento 
             JOIN sga_alumnos ON sga_alumnos.alumno = sga_actas_detalle.alumno
             LEFT JOIN sga_actas_folios ON (sga_actas_folios.id_acta = sga_actas_detalle.id_acta AND sga_actas_folios.folio = sga_actas_detalle.folio)
             LEFT JOIN sga_libros_tomos ON sga_libros_tomos.libro_tomo = sga_actas_folios.libro_tomo
             LEFT JOIN sga_libros_actas ON sga_libros_actas.libro = sga_libros_tomos.libro
          WHERE sga_actas.estado = '".acta::estado_cerrada."' 
            AND sga_actas.origen IN ('".acta::origen_regulares."','".acta::origen_promociones."') 
            AND sga_actas_detalle.estado = '".acta::detalle_estado_activo."' 
            AND sga_actas_detalle.rectificado = '".comunes::no."' 
            AND (sga_actas.origen = '".acta::origen_regulares."' OR sga_actas.origen = '".acta::origen_promociones."' AND sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."');
              
         -- **********************************************************************
         -- EXAMENES. 
         -- **********************************************************************
         INSERT INTO int_arau_nominal_ha (persona, alumno, plan_version, elemento, actividad_codigo, actividad_nombre, elemento_revision, instancia,
                fecha, resultado, origen, origen_codigo, nro_libro, nro_acta, folio_fisico, expediente, ubicacion, responsable_academica)
          SELECT 
             sga_alumnos.persona,
             sga_alumnos.alumno,
             sga_actas_detalle.plan_version,
             sga_mesas_examen.elemento,
             sga_elementos.codigo,
             sga_elementos.nombre,
             sga_elementos_revision.elemento_revision,
             sga_actas_detalle.instancia,
             sga_actas_detalle.fecha,
             sga_actas_detalle.resultado,
             cast('Examen' as varchar(30)) as origen,
             sga_actas.origen as origen_codigo,
             sga_libros_actas.nro_libro,
             sga_actas.nro_acta,
             sga_actas_folios.folio_fisico,
             cast(null as varchar(30)) as expediente,
			 sga_mesas_examen.ubicacion,
			 $responsable_academica as responsable_academica
          FROM 
             sga_actas
             JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
             JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa
             JOIN sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
             JOIN sga_elementos ON sga_elementos.elemento = sga_mesas_examen.elemento
			 $join_ra
         	 JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento 
         	 JOIN sga_alumnos ON sga_alumnos.alumno = sga_actas_detalle.alumno
         	 LEFT JOIN sga_actas_folios ON (sga_actas_folios.id_acta = sga_actas_detalle.id_acta AND sga_actas_folios.folio = sga_actas_detalle.folio)
         	 LEFT JOIN sga_libros_tomos ON sga_libros_tomos.libro_tomo = sga_actas_folios.libro_tomo
         	 LEFT JOIN sga_libros_actas ON sga_libros_actas.libro = sga_libros_tomos.libro
          WHERE sga_actas.estado = '".acta::estado_cerrada."' 
            AND sga_actas.origen = '".acta::origen_examen."' 
            AND sga_actas_detalle.estado = '".acta::detalle_estado_activo."' 
            AND sga_actas_detalle.instancia IN (SELECT instancia FROM sga_instancias WHERE aplica_a = '".mesa_de_examen::aplica_mesa."' AND visible_hist_academica = '".comunes::si."') -- Regulares, Libres, Complementarios, Tesis...
            AND sga_actas_detalle.rectificado = '".comunes::no."'
            AND sga_actas_detalle.resultado IN ('".escalas_notas::resultado_aprobado."', '".escalas_notas::resultado_reprobado."'); 

         -- **********************************************************************
         -- EQUIVALENCIAS TOTALES/REGULARIDAD/PARCIAL. Solo equivalencias con resultado APROBADO.
         -- **********************************************************************
         INSERT INTO int_arau_nominal_ha (
				persona, alumno, plan_version, instancia, equiv_origen, elemento, actividad_codigo, actividad_nombre, elemento_revision,
				fecha, resultado, origen, origen_codigo, nro_libro, nro_acta, folio_fisico, expediente, ubicacion, responsable_academica)
          SELECT 
             sga_alumnos.persona,
             sga_equiv_tramite.alumno,
             sga_equiv_tramite.plan_version,
         	 sga_equiv_otorgada.instancia,
         	 sga_equiv_tramite.origen,
             sga_equiv_otorgada.elemento,
         	 sga_elementos.codigo,
         	 sga_elementos.nombre,
             sga_elementos_revision.elemento_revision,
             sga_equiv_otorgada.fecha,
             sga_equiv_otorgada.resultado,
             cast('Equivalencia' as varchar(30)) as origen,
			 CASE sga_equiv_otorgada.instancia
			   WHEN 10 THEN 'B' 
			   WHEN 11 THEN 'C'
			   WHEN 12 THEN 'D'
			 END as origen_codigo,
             cast(null as varchar(30)) as nro_libro,
             cast(null as varchar(30)) as nro_acta,
             cast(null as integer) as folio_fisico,
			 cast(get_resolucion_equivalencia(sga_equiv_otorgada.equivalencia, sga_equiv_otorgada.grupo_equivalencia, sga_equiv_tramite.documento, 2) as varchar(30)) as expediente,
			 sga_alumnos.ubicacion,
			 $responsable_academica as responsable_academica
          FROM sga_equiv_tramite
          JOIN sga_equiv_otorgada ON sga_equiv_tramite.equivalencia_tramite = sga_equiv_otorgada.equivalencia_tramite
          JOIN sga_elementos ON sga_elementos.elemento = sga_equiv_otorgada.elemento
		  $join_ra
          JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
          JOIN sga_alumnos ON sga_alumnos.alumno = sga_equiv_tramite.alumno 
         WHERE sga_equiv_tramite.estado = '".equivalencia::estado_cerrado."'
           AND (sga_equiv_tramite.generacion_automatica = '".comunes::no."' OR 
		        (sga_equiv_tramite.generacion_automatica = '".comunes::si."' AND COALESCE(sga_equiv_tramite.propuesta,sga_alumnos.propuesta) <> sga_alumnos.propuesta) OR 
		        (sga_equiv_tramite.generacion_automatica = '".comunes::si."' AND sga_equiv_tramite.propuesta IS NULL AND 
				 sga_alumnos.propuesta <> (SELECT COALESCE(m.propuesta_origen,-1) 
                                             FROM sga_equiv_grupos as eg 
                                             JOIN sga_equiv_matrices as m ON m.matriz = eg.matriz 
                                             WHERE eg.grupo_equivalencia = sga_equiv_otorgada.grupo_equivalencia
											 AND m.alcance IN ('General', 'Propuesta', 'Propuesta-Plan')
										  )   
                 )
				)
           AND sga_equiv_otorgada.rectificado = '".comunes::no."'
           AND sga_equiv_otorgada.estado = '".equivalencia::detalle_estado_activo."'
           AND sga_equiv_otorgada.resultado = '".escalas_notas::resultado_aprobado."'
		   AND sga_equiv_otorgada.instancia <> 14;
         
         -- **********************************************************************
         -- RECONOCIMIENTO POR RESOLUCION. Solo actividades (No modulos)
         -- **********************************************************************
         INSERT INTO int_arau_nominal_ha (
				persona, alumno, plan_version, elemento, actividad_codigo, actividad_nombre, elemento_revision, 
				fecha, resultado, origen, origen_codigo, nro_libro, nro_acta, folio_fisico, expediente, ubicacion, responsable_academica)
         SELECT 
             sga_alumnos.persona,
             sga_alumnos.alumno,
             sga_reconocimiento.plan_version,
             sga_reconocimiento_act.elemento,
         	 sga_elementos.codigo,
         	 sga_elementos.nombre,
             sga_elementos_revision.elemento_revision,
             sga_reconocimiento.fecha,
         	 'A' as resultado,
             cast('Aprob. por Resolución' as varchar(30)) as origen,
			 'A' as origen_codigo,
             cast(null as varchar(30)) as nro_libro,
             cast(null as varchar(30)) as nro_acta,
             cast(null as integer) as folio_fisico,
             sga_documentos.documento_numero as expediente,
			 sga_alumnos.ubicacion,
			 $responsable_academica as responsable_academica
          FROM 
             sga_reconocimiento
             JOIN sga_reconocimiento_act ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
             JOIN sga_alumnos ON sga_alumnos.alumno = sga_reconocimiento.alumno
             JOIN sga_documentos ON sga_documentos.documento = sga_reconocimiento.documento
             JOIN sga_elementos ON sga_elementos.elemento = sga_reconocimiento_act.elemento
			 $join_ra
             JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
         	 JOIN sga_g3entidades_subtipos ON (sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo AND sga_g3entidades_subtipos.entidad_tipo = 2) -- Actividades
         WHERE sga_reconocimiento.estado = '".equivalencia::estado_cerrado."' 
           AND sga_reconocimiento_act.reconocimiento_total = '".comunes::si."'  
		   AND sga_reconocimiento_act.rectificado = '".comunes::no."'
           AND sga_reconocimiento_act.estado = '".equivalencia::detalle_estado_activo."'
		   AND sga_reconocimiento_act.homologada = '".comunes::no."';
         
         -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
         -- Creacion de indices
         -- ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  
         CREATE INDEX id_int_arau_nominal_ha1 ON int_arau_nominal_ha (responsable_academica, alumno, elemento, fecha);
         CREATE INDEX id_int_arau_nominal_ha2 ON int_arau_nominal_ha (responsable_academica, persona, elemento, fecha);
         CREATE INDEX id_int_arau_nominal_ha3 ON int_arau_nominal_ha (responsable_academica, alumno, resultado, origen_codigo);
		 
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Araucano Nominal - Se cargo la tabla int_arau_nominal_ha. ************'); 
	}

	//--------------------------------------------------------------------------
	//-- Relevamiento de alumnos - Archivo Nro. 0
	//--------------------------------------------------------------------------

	function eliminar_nominal_alumnos($anio_academico, $responsable_academica=null)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$where_ra = is_null($responsable_academica) ? 'responsable_academica IS NULL' : 'responsable_academica = '.toba::db()->quote($responsable_academica);
		$sql = "DELETE
				FROM	int_arau_nominal_alumnos
				WHERE	anio_academico = $anio_academico AND
						$where_ra
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los alumnos para informar a SIU-Araucano con éxito. ************'); 
	}

	function generar_nominal_alumnos($anio_academico, $responsable_academica=null)
	{
		/*
		 Recupera un registro por cada persona que es alumno. Alumnos que:
 		  - Estan en al menos una propuesta y que tiene asociado titulos con titulo araucano definido.
		  - Alumnos activos o pasivos con fecha de cambio a pasivo mayor a la fecha de inicio del año a evaluar
		  - Que tienen fecha de ingreso anterior o igual al año academico a informar.
		  - El dato censal económico se toma al inicio del año académico.
		  - El dato "Pais que otorgó el título anterior" para extranjeros se calcula en la oferta que es donde se conoce el título a informar.
		*/
		$fecha_dato_censal = "01/04/$anio_academico";
		$formato_fecha_visual	= toba::db()->quote(comunes::formato_fecha_visual);
		$anio_academico = toba::db()->quote($anio_academico);
		if (is_null($responsable_academica)) {
			$responsable_academica = 'null';
			$where_ra = '';
		} else {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$where_ra = " AND sga_alumnos.propuesta IN (SELECT	sga_propuestas_ra.propuesta
														FROM	sga_propuestas_ra
														WHERE	sga_propuestas_ra.responsable_academica = $responsable_academica)";
		}
		$sql = "INSERT INTO int_arau_nominal_alumnos (anio_academico, responsable_academica, persona, tipo_documento, nro_documento, cuil_cuit, horas_trabajo, nivel_instruccion_padre, nivel_instruccion_madre, pais_nacimiento, dato_censal_personal, dato_censal_discapacidad)
				SELECT		$anio_academico as anio_academico,
							$responsable_academica as responsable_academica,
							mdp_personas.persona,
							mdp_personas_documentos.tipo_documento,
							mdp_personas_documentos.nro_documento,
							CASE
								WHEN mdp_personas.nacionalidad = ".persona::nac_extranjero." THEN '88888888888'
								ELSE COALESCE(personas_documento_cuit.nro_documento, '99999999999')
							END as cuil_cuit,
							get_horas_trabajo_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual)) as horas_trabajo,
							get_nivel_instruccion_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual), '".persona::allegado_padre."') as nivel_instruccion_padre,
							get_nivel_instruccion_araucano(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual), '".persona::allegado_madre."') as nivel_instruccion_madre,
							COALESCE(get_pais_localidad(mdp_personas.localidad_nacimiento), 99999) as pais_nacimiento,
							get_dato_censal_personal(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual)) as dato_censal_personal,
							get_dato_censal_discapacidad(mdp_personas.persona, to_date('$fecha_dato_censal', $formato_fecha_visual)) as dato_censal_discapacidad
				FROM		sga_alumnos
							JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
							JOIN mdp_personas_documentos ON mdp_personas_documentos.documento = mdp_personas.documento_principal
							JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp)
							LEFT JOIN mdp_personas_documentos as personas_documento_cuit ON (mdp_personas.persona = personas_documento_cuit.persona AND personas_documento_cuit.tipo_documento = ".persona::doc_CUIL.")
				WHERE		sga_propuestas_aspira.anio_academico <= $anio_academico AND
							sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."', '".propuesta::inscripcion_pendiente."') AND
							(sga_alumnos.calidad = '".alumno::calidad_activo."' OR sga_alumnos.calidad = '".alumno::calidad_pasivo."' AND to_date('$fecha_dato_censal', $formato_fecha_visual) < (SELECT MAX(fecha) FROM sga_alumnos_hist_calidad as c WHERE c.alumno = sga_alumnos.alumno AND c.calidad = sga_alumnos.calidad)) AND
							-- Solo alumnos que tienen certificados con titulo araucano (de cualquier nivel)
							sga_alumnos.plan_version IN (	SELECT	sga_planes_certificados.plan_version 
															FROM	sga_planes_certificados
																	JOIN sga_certificados ON sga_certificados.certificado = sga_planes_certificados.certificado
															WHERE	sga_certificados.titulo_araucano IS NOT NULL AND
																	sga_certificados.informar_araucano = '".comunes::si."')
							$where_ra
				GROUP BY	mdp_personas.persona,
							mdp_personas_documentos.tipo_documento,
							mdp_personas_documentos.nro_documento,
							personas_documento_cuit.nro_documento,
							mdp_personas_documentos.pais_documento;
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron los alumnos para informar a SIU-Araucano con éxito. ************'); 
	}

	//--------------------------------------------------------------------------
	//-- Relevamiento de alumnos por oferta - Archivo Nro. 1
	//--------------------------------------------------------------------------

	function eliminar_nominal_alumnos_oferta($anio_academico, $responsable_academica=null)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$where_ra = is_null($responsable_academica) ? 'responsable_academica IS NULL' : 'responsable_academica = '.toba::db()->quote($responsable_academica);
		$sql = "DELETE
				FROM	int_arau_nominal_oferta
				WHERE	anio_academico = $anio_academico AND
						$where_ra
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los alumnos por oferta para informar a SIU-Araucano con éxito. ************'); 
	}

	function generar_nominal_alumnos_oferta($anio_academico, $responsable_academica=null)
	{
		/*
		  Informa todos los alumnos del archivo 0 por cada titulo araucano de su plan de estudios.
		  Por cada certificado que obtuvo en el periodo a informar o que aun no lo obtuvo. Si lo obtuvo antes no se informa
		  unidad_academica_araucano = Codigo de institucion Araucano de la sede del alumno o de la responsable academica de la propuesta del alumno
		  Desde esta tabla se obtiene el archivo 2 de Araucano Nominal.
		*/ 
		$fecha_desde = "01/04/$anio_academico";
		$fecha_hasta = "31/03/" . ($anio_academico + 1);
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$anio_academico = toba::db()->quote($anio_academico);
		if (is_null($responsable_academica)) {
			$responsable_academica = 'null';
			$condicion_ra = 'IS NULL';
			$where_ra = '';
		} else {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$condicion_ra = "= $responsable_academica";
			$where_ra = " AND sga_alumnos.propuesta IN (SELECT	sga_propuestas_ra.propuesta
														FROM	sga_propuestas_ra
														WHERE	sga_propuestas_ra.responsable_academica = $responsable_academica)";
		}
		$sql = "INSERT INTO int_arau_nominal_oferta (anio_academico, responsable_academica, alumno, persona, unidad_academica_araucano, titulo_araucano, certificado, anio_academico_ingreso, forma_ingreso, fecha_inscripcion, tipo_ingreso, fecha_egreso, fecha_pasivo, dependencia_ingreso)
				SELECT	$anio_academico as anio_academico,
						$responsable_academica as responsable_academica,
						sga_alumnos.alumno,
						int_arau_nominal_alumnos.persona,
						get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IA') as unidad_academica_araucano,
						sga_certificados.titulo_araucano,
						sga_certificados.certificado,
						get_anio_academico_ingreso_alumno(sga_alumnos.alumno, 3) as anio_academico_ingreso, -- Año academico de ingreso en el ciclo basico o sino en la propuesta
						get_forma_ingreso_araucano_nominal(sga_alumnos.alumno, $responsable_academica) as forma_ingreso,
						sga_propuestas_aspira.fecha_inscripcion,
						sga_propuestas_aspira.tipo_ingreso,
						CASE 
						  WHEN sga_certificados_otorg.fecha_egreso > to_date('$fecha_hasta', $formato_fecha_visual) THEN cast(NULL as date)  -- es egresado despues de la fecha fin del año academico.
						  ELSE sga_certificados_otorg.fecha_egreso
						END as fecha_egreso,
						CASE sga_alumnos.calidad
						  WHEN '".alumno::calidad_pasivo."' THEN (SELECT MAX(fecha) FROM sga_alumnos_hist_calidad as c WHERE c.alumno = sga_alumnos.alumno AND c.calidad = sga_alumnos.calidad)
						  ELSE cast(NULL as date)
						END as fecha_pasivo,
						get_dependencia_araucano_propuesta_ubicacion(sga_propuestas_aspira.propuesta, sga_propuestas_aspira.ubicacion) as dependencia_ingreso
				FROM	int_arau_nominal_alumnos
						JOIN sga_alumnos ON sga_alumnos.persona = int_arau_nominal_alumnos.persona
						JOIN sga_planes_certificados ON (sga_planes_certificados.plan_version = sga_alumnos.plan_version)
						JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
						JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp)
						JOIN sga_certificados ON (sga_certificados.certificado = sga_planes_certificados.certificado)
						LEFT JOIN sga_certificados_otorg ON (sga_certificados_otorg.alumno = sga_alumnos.alumno AND sga_certificados_otorg.certificado = sga_planes_certificados.certificado AND sga_certificados_otorg.anulado = 0)
				WHERE	int_arau_nominal_alumnos.anio_academico = $anio_academico AND
						int_arau_nominal_alumnos.responsable_academica $condicion_ra AND
						sga_propuestas_aspira.anio_academico <= $anio_academico AND
						sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."', '".propuesta::inscripcion_pendiente."') AND
						sga_certificados.titulo_araucano IS NOT NULL AND
						sga_certificados.informar_araucano = '".comunes::si."' AND 
						(sga_certificados.modalidad_araucano IS NULL OR sga_certificados.modalidad_araucano = sga_alumnos.modalidad ) AND 
						-- No obtuvo el titulo o lo obtuvo en el año a informar o lo obtuvo pasada la fecha fin (en este caso se informa como reinscripto)
						(COALESCE(sga_certificados_otorg.fecha_egreso, to_date('$fecha_desde', $formato_fecha_visual)) BETWEEN to_date('$fecha_desde', $formato_fecha_visual) AND to_date('$fecha_hasta', $formato_fecha_visual) OR
						sga_certificados_otorg.fecha_egreso IS NOT NULL AND sga_certificados_otorg.fecha_egreso > to_date('$fecha_hasta', $formato_fecha_visual))
						$where_ra;

			-- Saco de la tabla de oferta aquellos alumnos que estan en calidad Pasivo con fecha anterior al inicio del año academico a evaluar.			
			DELETE
			FROM	int_arau_nominal_oferta 
			WHERE	anio_academico = $anio_academico AND
					responsable_academica $condicion_ra AND
					fecha_pasivo IS NOT NULL AND
					fecha_pasivo < to_date('$fecha_desde', $formato_fecha_visual);

			-- Se actualiza el dato 'pais_titulo_anterior'.
			-- Toma el 1er título que encuentra del alumno que va a informar (podría informar un título intermedio y uno final o titulos de diferentes propuestas)
			UPDATE	int_arau_nominal_alumnos
			SET		pais_titulo_anterior = CAST((	SELECT		COALESCE(get_titulo_institucion_habilitante(int_arau_nominal_oferta.alumno, 7), '99999')
													FROM		int_arau_nominal_oferta
													WHERE		int_arau_nominal_oferta.anio_academico = $anio_academico AND
																int_arau_nominal_oferta.responsable_academica $condicion_ra AND
																int_arau_nominal_oferta.persona = int_arau_nominal_alumnos.persona
													ORDER BY	int_arau_nominal_oferta.fecha_inscripcion
													LIMIT		1) as integer)
			FROM	mdp_personas
			WHERE	int_arau_nominal_alumnos.anio_academico = $anio_academico AND
					int_arau_nominal_alumnos.responsable_academica $condicion_ra AND
					mdp_personas.persona = int_arau_nominal_alumnos.persona;

			-- Se actualiza el dato 'cue_escuela_origen'.
			-- Se tiene en cuenta que hay casos en que una persona tiene más de una inscripción a propuesta (puede tener diferentes tipos de ingreso) y más de un estudio secundario cargado.
			UPDATE	int_arau_nominal_alumnos
			SET		cue_escuela_origen = (	SELECT		CASE
															WHEN mdp_personas.nacionalidad = ".persona::nac_extranjero." THEN 888888888
															WHEN int_arau_nominal_oferta.tipo_ingreso = ".inscripcion::tipo_ingreso_mayor_25." THEN 777777777
															ELSE COALESCE((	SELECT		sga_colegios_secundarios.cue
																			FROM		mdp_datos_estudios
																						JOIN sga_colegios_secundarios ON mdp_datos_estudios.colegio = sga_colegios_secundarios.colegio
																			WHERE		mdp_datos_estudios.persona = mdp_personas.persona AND
																						mdp_datos_estudios.nivel_estudio = ".persona::nivel_secundario."
																			ORDER BY	mdp_datos_estudios.fecha_egreso DESC
																			LIMIT		1), 999999999)
														END
											FROM		int_arau_nominal_oferta
														JOIN mdp_personas ON int_arau_nominal_oferta.persona = mdp_personas.persona
											WHERE		int_arau_nominal_oferta.anio_academico = $anio_academico AND
														int_arau_nominal_oferta.responsable_academica $condicion_ra AND
														int_arau_nominal_oferta.persona = int_arau_nominal_alumnos.persona
											ORDER BY	int_arau_nominal_oferta.fecha_inscripcion
											LIMIT		1)
			WHERE	int_arau_nominal_alumnos.anio_academico = $anio_academico AND
					int_arau_nominal_alumnos.responsable_academica $condicion_ra;
		";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron los alumnos por oferta para informar a SIU-Araucano con éxito. ************'); 
	}

	//----------------------------------------------------------------------------------
	//  Relevamiento de actividades (examenes, promociones, equivalencias totales/regularidad, cursadas, aprob x resolucion)
	//  Para generacion de Archivo 2 y Archivo 3
	//----------------------------------------------------------------------------------

	function eliminar_nominal_actividades($anio_academico, $responsable_academica=null)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$where_ra = is_null($responsable_academica) ? 'responsable_academica IS NULL' : 'responsable_academica = '.toba::db()->quote($responsable_academica);
		$sql = "DELETE
				FROM	int_arau_nominal_actividades 
				WHERE	anio_academico = $anio_academico AND
						$where_ra
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los alumnos por oferta para informar a SIU-Araucano con éxito. ************'); 
	}

	function get_actividades_x_certificado($responsable_academica=null)
	{
		// Cargo la tabla con las actividades de cada certificado x version de plan de estudios.
		// Usada para generar el archivo de rendimiento academico (Archivo 2 y Archivo 3)
		$responsable_academica = is_null($responsable_academica)? 'null' : toba::db()->quote($responsable_academica); 
		$sql = "DELETE FROM int_arau_nominal_act_x_certificado;
				INSERT INTO int_arau_nominal_act_x_certificado (plan_version, certificado, elemento)
					SELECT DISTINCT e.* FROM get_actividades_x_certificado_plan_version (NULL, NULL, $responsable_academica) as e (integer, integer, integer);
				
				-- OPTATIVAS: Marco las actividades que son optativas en el plan de estudios. (que forman parte de alguna generica)			
				UPDATE int_arau_nominal_act_x_certificado
				   SET obligatoriedad = (CASE 
				                           WHEN (SELECT COUNT(1) 
                                                    FROM vw_optativas_plan as opt
										           WHERE opt.plan_version = int_arau_nominal_act_x_certificado.plan_version
												     AND opt.elemento     = int_arau_nominal_act_x_certificado.elemento
												) > 0 THEN 2
										   ELSE 0			
										END);
				";  

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ SIU-Araucano - Se registraron las actividades por cada certificado de los planes de estudios. ************'); 
	}

	function generar_nominal_actividades($anio_academico, $cuadros_4_y_5, $archivo_13, $responsable_academica=null)
	{
		/*
		Recupera las materias aprobadas y desaprobadas en un rango de fechas.
		Por cada oferta (certificado) se informan las actividades aprobadas/desaprobadas que forman parte de la oferta.
		Se verifica que la actividad exista segun la version del plan donde la realizó y para el certificado que se esta informando la misma.

		Si el alumno esta Pasivo, solo recupera historia academica anterior a la fecha en que paso a estar pasivo en la propuesta.
		Este filtro es por el tema de "actividades comunes entre propuestas" para que no reconozca actividades en una propuesta donde
		el alumno esta pasivo y esas actividades fueron realizadas en otra propuesta con fecha posterior a la fecha de cambio a pasivo.
		*/
		$fecha_desde = "01/04/$anio_academico";
		$fecha_hasta = '31/03/'.($anio_academico + 1);
		$anio_academico_quoteado = toba::db()->quote($anio_academico);
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		if ($cuadros_4_y_5) {
			// Muestro toda la historia
			$filtro_fecha = "AND ha.fecha <= to_date('$fecha_hasta', $formato_fecha_visual)";
		} else {
			$filtro_fecha = "AND ha.fecha BETWEEN to_date('$fecha_desde', $formato_fecha_visual) AND to_date('$fecha_hasta', $formato_fecha_visual)" ;
		}
		if (is_null($responsable_academica)) {
			$responsable_academica = 'null';
			$condicion_ra = 'IS NULL';
		} else {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$condicion_ra = "= $responsable_academica";
		}
		$sql = "INSERT INTO int_arau_nominal_actividades (
		                anio_academico, responsable_academica, persona, alumno, certificado, 
						fecha, resultado, elemento, actividad_codigo, actividad_nombre, carga_horaria, obligatoriedad, comision,
		                origen, origen_codigo, forma_aprobacion_regularizadas, forma_aprobacion, anio_cursada, institucion_araucano_ubicacion,
						nro_libro, nro_acta, folio_fisico, expediente)
				SELECT	int_arau_nominal_oferta.anio_academico,
						int_arau_nominal_oferta.responsable_academica,
						int_arau_nominal_oferta.persona,
						int_arau_nominal_oferta.alumno,
						int_arau_nominal_oferta.certificado,
						ha.fecha,
						CASE ha.resultado
							WHEN '".escalas_notas::resultado_aprobado."' THEN 1
							WHEN '".escalas_notas::resultado_reprobado."' THEN 2
							WHEN '".escalas_notas::resultado_ausente."' THEN 3
						END as resultado,
						ha.elemento,
						ha.actividad_codigo,
						ha.actividad_nombre,
						CAST(sga_elementos_plan.horas_totales as INTEGER) as carga_horaria,
						act.obligatoriedad, 
						ha.comision,
						ha.origen,
						ha.origen_codigo,
						CASE
							-- Cursadas
							WHEN ha.origen_codigo = '".acta::origen_regulares."' THEN 1
							-- Promociones
							WHEN ha.origen_codigo = '".acta::origen_promociones."' THEN 1
							-- Equivalencias de Regularidad
							WHEN ha.origen_codigo = '".acta::origen_equivalencias_regulares."' AND ha.equiv_origen = ".equivalencia::origen_ext_libre." THEN 2
							WHEN ha.origen_codigo = '".acta::origen_equivalencias_regulares."' AND ha.equiv_origen IN (".equivalencia::origen_ext_pase.", ".equivalencia::origen_ext_convenio.") THEN 3
							WHEN ha.origen_codigo = '".acta::origen_equivalencias_regulares."' AND ha.equiv_origen IN (".equivalencia::origen_int_libre.", ".equivalencia::origen_int_prop.", ".equivalencia::origen_int_plan.", ".equivalencia::origen_int_version.", ".equivalencia::origen_int_cursada_simultanea.") THEN 4
						END as forma_aprobacion_regularizadas,
						CASE
							-- Examenes 
							WHEN ha.origen_codigo = '".acta::origen_examen."' THEN 
							  (CASE ha.instancia
								WHEN ".mesa_de_examen::inst_exam_libre." THEN 1
								ELSE 0 -- Resto de los examenes: Regular/Complementario/Tesis/etc
							  END) 
							-- Promociones
							WHEN ha.origen_codigo = '".acta::origen_promociones."' THEN 2
							-- Equivalencias totales
							WHEN ha.origen_codigo = '".acta::origen_equivalencias."' AND ha.equiv_origen = ".equivalencia::origen_ext_libre." THEN 3
							WHEN ha.origen_codigo = '".acta::origen_equivalencias."' AND ha.equiv_origen IN (".equivalencia::origen_ext_pase.", ".equivalencia::origen_ext_convenio.") THEN 4
							WHEN ha.origen_codigo = '".acta::origen_equivalencias."' AND ha.equiv_origen IN (".equivalencia::origen_int_libre.", ".equivalencia::origen_int_prop.", ".equivalencia::origen_int_plan.", ".equivalencia::origen_int_version.", ".equivalencia::origen_int_cursada_simultanea.") THEN 5
							-- Aprob x Resolucion
							WHEN ha.origen_codigo = '".acta::origen_aprobacion_resolucion."' THEN 3  -- Consideramos como Externa Libre.
							ELSE 6 -- Homologacion.
						END as forma_aprobacion,
						COALESCE(sga_periodos.anio_academico, 0) as anio_cursada, -- Para promociones se toma el año del período de la comisión.
						-- Se recupera la dependencia segun la propuesta por la que se informa la actividad
						get_dependencia_araucano_propuesta_ubicacion(sga_alumnos.propuesta, ha.ubicacion) as institucion_araucano_ubicacion,
						ha.nro_libro,
						ha.nro_acta,
						ha.folio_fisico,
						ha.expediente
				
				FROM	int_arau_nominal_oferta
						JOIN sga_alumnos ON sga_alumnos.alumno = int_arau_nominal_oferta.alumno
						JOIN int_arau_nominal_ha as ha ON ha.persona = sga_alumnos.persona AND ha.responsable_academica $condicion_ra
                        JOIN sga_elementos_plan ON (sga_alumnos.plan_version = sga_elementos_plan.plan_version AND ha.elemento_revision = sga_elementos_plan.elemento_revision)
                        JOIN int_arau_nominal_act_x_certificado as act ON (sga_alumnos.plan_version = act.plan_version AND ha.elemento = act.elemento AND int_arau_nominal_oferta.certificado = act.certificado)
						JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = ha.plan_version
						JOIN sga_planes_versiones as pv_destino ON pv_destino.plan_version = sga_alumnos.plan_version
						LEFT JOIN sga_comisiones ON sga_comisiones.comision = ha.comision
						LEFT JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						LEFT JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
						LEFT JOIN sga_ubicaciones ON ha.ubicacion = sga_ubicaciones.ubicacion									
				
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico_quoteado AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra
						$filtro_fecha AND
						(int_arau_nominal_oferta.fecha_pasivo IS NULL OR (int_arau_nominal_oferta.fecha_pasivo IS NOT NULL AND ha.fecha <= int_arau_nominal_oferta.fecha_pasivo)) AND
						 -- Si es una equivalencia solo recupero equivalencias totales y de regularidad.
						((ha.origen = 'Equivalencia' AND ha.origen_codigo IN ('B','C')) OR ha.origen <> 'Equivalencia') AND
						-- Si la actividad es comun, y la realizo en otra propuesta, que solo la informe si no esta definida como de NO reconocimiento automatico.
						NOT EXISTS (SELECT	1
									FROM	sga_elementos_no_comunes as enc
									WHERE	enc.plan_origen = pv_origen.plan AND -- donde realizó la actividad
											enc.plan_destino = pv_destino.plan AND -- donde se reconoce e informa la actividad
											enc.elemento = ha.elemento);
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron las actividades para informar a SIU-Araucano con éxito. ************'); 

		// Saco de la tabla de alumnos y oferta aquellos alumnos que no son nuevos inscriptos, ni reinscriptos ni egresados
		$sql = "DROP TABLE IF EXISTS _temp_arau_nom_eliminar;
                CREATE TEMP TABLE _temp_arau_nom_eliminar (alumno integer NOT NULL, certificado integer NOT NULL);
                CREATE INDEX _id__temp_arau_nom_eliminar ON _temp_arau_nom_eliminar (alumno, certificado);
				
		        DROP TABLE IF EXISTS _temp_arau_nom_informar;
                CREATE TEMP TABLE _temp_arau_nom_informar (alumno integer NOT NULL, certificado integer NOT NULL);
                CREATE INDEX _id_temp_arau_nom_informar ON _temp_arau_nom_informar (alumno, certificado);

                -- Cargo la tabla con alumnos sin actividades
				INSERT INTO _temp_arau_nom_eliminar (alumno, certificado)
				SELECT	alumno,
						certificado
				FROM	int_arau_nominal_oferta
				WHERE	anio_academico = $anio_academico_quoteado AND
						responsable_academica $condicion_ra AND
						fecha_egreso IS NULL AND -- Que no haya egresado por esa oferta en este año..  
						anio_academico_ingreso < $anio_academico_quoteado -- Que no sea alumno ingresante (Nuevo Inscripto)
				EXCEPT
				-- No considero alumnos que tienen historia academica en el año academico a evaluar
				SELECT	alumno,
						certificado
				FROM	int_arau_nominal_actividades
				WHERE	anio_academico = $anio_academico_quoteado AND
						responsable_academica $condicion_ra AND
						fecha BETWEEN to_date('$fecha_desde', $formato_fecha_visual) AND to_date('$fecha_hasta', $formato_fecha_visual);

				-- Reinscripcion en la propuesta en el año academico
				INSERT INTO _temp_arau_nom_informar (alumno, certificado)
				SELECT	DISTINCT int_arau_nominal_oferta.alumno,
						int_arau_nominal_oferta.certificado
				FROM	int_arau_nominal_oferta
						JOIN sga_reinscripciones ON (sga_reinscripciones.alumno = int_arau_nominal_oferta.alumno AND sga_reinscripciones.anio_academico = int_arau_nominal_oferta.anio_academico)
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico_quoteado AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra;

				-- Inscripiones a cursadas
				INSERT INTO _temp_arau_nom_informar (alumno, certificado)
				SELECT	DISTINCT int_arau_nominal_oferta.alumno,
						int_arau_nominal_oferta.certificado
				FROM	int_arau_nominal_oferta
						JOIN sga_insc_cursada ON sga_insc_cursada.alumno = int_arau_nominal_oferta.alumno
						JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
						JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						JOIN int_arau_nominal_act_x_certificado as act ON (act.plan_version = sga_insc_cursada.plan_version AND act.elemento = sga_comisiones.elemento AND act.certificado = int_arau_nominal_oferta.certificado)	
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico_quoteado AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra AND
						int_arau_nominal_oferta.anio_academico = sga_periodos.anio_academico;

				-- Inscricion a examen en el año academico
				INSERT INTO _temp_arau_nom_informar (alumno, certificado)
				SELECT	DISTINCT int_arau_nominal_oferta.alumno,
						int_arau_nominal_oferta.certificado
				FROM	int_arau_nominal_oferta
						JOIN sga_insc_examen ON sga_insc_examen.alumno = int_arau_nominal_oferta.alumno
						JOIN sga_llamados_mesa ON sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
						JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
						JOIN int_arau_nominal_act_x_certificado as act ON (act.plan_version = sga_insc_examen.plan_version AND act.elemento = sga_mesas_examen.elemento AND act.certificado = int_arau_nominal_oferta.certificado)	
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico_quoteado AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra AND
						int_arau_nominal_oferta.anio_academico = sga_mesas_examen.anio_academico;

				-- Saco del archivo de alumnos a eliminar aquellos que tienen algo de historia academica en el año a informar		
				DELETE FROM _temp_arau_nom_eliminar
				WHERE (alumno, certificado) IN (SELECT DISTINCT alumno, certificado FROM _temp_arau_nom_informar);

				-- Borro del archivo 1 aquellos alumnos que no tienen historia academica y  no tienen reinscripcion en el año academico a informar.
				-- Es decir aquellos alumnos que estan en la tabla temporal _temp_arau_nom_eliminar 
				DELETE
				FROM	int_arau_nominal_oferta
				WHERE	anio_academico = $anio_academico_quoteado AND
						responsable_academica $condicion_ra AND
						EXISTS (SELECT	1
								FROM	_temp_arau_nom_eliminar as t
								WHERE	t.alumno = int_arau_nominal_oferta.alumno AND
										t.certificado = int_arau_nominal_oferta.certificado);
			";

			if ($archivo_13) {
				//--------------------------------------------------------------------------
				//-- Relevamiento de alumnos internacionales de movilidad - Archivo Nro. 13
				//--------------------------------------------------------------------------
				$sql .= "
				-- Limpio la tabla int_arau_nominal_movilidad.
				DELETE
				FROM	int_arau_nominal_movilidad
				WHERE	anio_academico = $anio_academico AND
						responsable_academica $condicion_ra;

				-- Inserto en int_arau_nominal_movilidad los alumnos internacionales de propuestas de movilidad.
				INSERT INTO int_arau_nominal_movilidad (anio_academico, responsable_academica, alumno, persona, tipo_documento, nro_documento, pais_nacimiento, dato_censal_personal,
						titulo_institucion_habilitante, unidad_academica_araucano, titulo_araucano, certificado, convenio, convenio_tipo, fecha_inscripcion, fecha_egreso, area_araucano_propuesta)
				SELECT	$anio_academico as anio_academico,
						$responsable_academica as responsable_academica,
						sga_alumnos.alumno,
						int_arau_nominal_alumnos.persona,
						int_arau_nominal_alumnos.tipo_documento,
						int_arau_nominal_alumnos.nro_documento,
						int_arau_nominal_alumnos.pais_nacimiento,
						int_arau_nominal_alumnos.dato_censal_personal,
						COALESCE(get_titulo_institucion_habilitante(sga_alumnos.alumno, 0), '') as titulo_institucion_habilitante,
						int_arau_nominal_oferta.unidad_academica_araucano,
						int_arau_nominal_oferta.titulo_araucano,
						int_arau_nominal_oferta.certificado,
						CASE
							WHEN sga_propuestas_aspira.convenio IS NOT NULL THEN 1
							ELSE 2
						END as convenio,
						CASE
							WHEN sga_convenios.convenio_tipo = ".convenio::tipo_marco." THEN 1
							WHEN sga_convenios.convenio_tipo = ".convenio::tipo_especifico." THEN 2
						END as convenio_tipo,
						int_arau_nominal_oferta.fecha_inscripcion,
						int_arau_nominal_oferta.fecha_egreso,
						COALESCE((	SELECT		sga_rd_areas.codigo_araucano
									FROM		sga_propuestas_areas_tem
												JOIN sga_rd_areas ON sga_propuestas_areas_tem.area_tematica = sga_rd_areas.area
									WHERE		sga_propuestas_areas_tem.propuesta = sga_propuestas.propuesta
									ORDER BY	sga_rd_areas.nombre
									LIMIT		1), '".propuesta::codigo_aracano_sin_area_tematica."') as area_araucano_propuesta
				FROM	int_arau_nominal_oferta
						JOIN int_arau_nominal_alumnos ON (int_arau_nominal_oferta.persona = int_arau_nominal_alumnos.persona AND int_arau_nominal_oferta.anio_academico = $anio_academico AND int_arau_nominal_oferta.responsable_academica $condicion_ra)
						JOIN mdp_personas ON int_arau_nominal_oferta.persona = mdp_personas.persona
						JOIN sga_alumnos ON int_arau_nominal_oferta.alumno = sga_alumnos.alumno
						JOIN sga_propuestas_aspira ON (sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta)
						JOIN sga_planes_certificados ON sga_alumnos.plan_version = sga_planes_certificados.plan_version
						JOIN sga_certificados ON sga_planes_certificados.certificado = sga_certificados.certificado
						JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
						JOIN sga_propuestas_tipos ON sga_propuestas_tipos.propuesta_tipo = sga_propuestas.propuesta_tipo
						LEFT JOIN sga_convenios ON sga_propuestas_aspira.convenio = sga_convenios.convenio
						LEFT JOIN his_datos_personales ON int_arau_nominal_alumnos.dato_censal_personal = his_datos_personales.dato_censal_per
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra AND
						-- Sólo propuestas que informan alumnos de movilidad internacional
						sga_propuestas_tipos.informar_movilidad_internacional = '".comunes::si."' AND
						-- Sólo certificados que informan para movilidad internacional
						sga_certificados.movilidad_internacional = '".comunes::si."' AND
						-- Sólo alumnos de nacionalidad extranjera.
						mdp_personas.nacionalidad = ".persona::nac_extranjero." AND
						-- Sólo alumnos con domicilio de procedencia fuera del país.
						COALESCE(get_pais_localidad(his_datos_personales.procedencia_localidad), 99999) <> ".persona::pais_argentina." AND
						-- Sólo alumnos con título anterior de origen extranjero.
						int_arau_nominal_alumnos.pais_titulo_anterior <> ".persona::pais_argentina.";

				-- Borro del archivo 1 aquellos alumnos que se informan en el archivo 13.
				DELETE
				FROM	int_arau_nominal_oferta
				WHERE	anio_academico = $anio_academico_quoteado AND
						responsable_academica $condicion_ra AND
						EXISTS (SELECT	1
								FROM	int_arau_nominal_movilidad
								WHERE	int_arau_nominal_movilidad.alumno = int_arau_nominal_oferta.alumno AND
										int_arau_nominal_movilidad.certificado = int_arau_nominal_oferta.certificado AND
										int_arau_nominal_movilidad.anio_academico = $anio_academico_quoteado AND
										int_arau_nominal_movilidad.responsable_academica $condicion_ra);
				";
			}

			$sql .= "-- Borro del archivo 0 aquellos alumnos que no informan ningun certificado y que no tienen reinscripcion en el año academico a informar.
					DELETE
					FROM	int_arau_nominal_alumnos
					WHERE	anio_academico = $anio_academico_quoteado AND
							responsable_academica $condicion_ra AND
							persona NOT IN (SELECT persona FROM int_arau_nominal_oferta WHERE anio_academico = $anio_academico_quoteado AND responsable_academica $condicion_ra);

				-- Se elimina la actividad académica de alumnos que no están en el archivo 1.
				DELETE
				FROM	int_arau_nominal_actividades
				WHERE	anio_academico = $anio_academico_quoteado AND
						responsable_academica $condicion_ra AND
						(alumno, certificado) NOT IN (SELECT alumno, certificado FROM int_arau_nominal_oferta WHERE anio_academico = $anio_academico_quoteado AND responsable_academica $condicion_ra);

				-- Se actualiza el dato 'anio_cursada' para examen regular.
				-- No se tiene en cuenta la vigencia de la regularidad.
				-- Si la regularidad se dio por equivalencia no hay año académico en vw_regularidades, se informa 0.
				UPDATE	int_arau_nominal_actividades
				SET		anio_cursada = COALESCE((	SELECT		vw_regularidades.anio_academico
													FROM		vw_regularidades
													WHERE		vw_regularidades.alumno = int_arau_nominal_actividades.alumno AND
																vw_regularidades.elemento = int_arau_nominal_actividades.elemento AND
																vw_regularidades.resultado = '".comunes::resultado_nota_aprobado."' AND
																-- Anteriores al '31/03/'.($anio_academico + 1).
																vw_regularidades.fecha <= to_date('$fecha_hasta', $formato_fecha_visual)
													ORDER BY	vw_regularidades.fecha DESC
													LIMIT		1), cast(0 as NUMERIC))
				WHERE	int_arau_nominal_actividades.anio_academico = $anio_academico_quoteado AND
						int_arau_nominal_actividades.responsable_academica $condicion_ra AND
						int_arau_nominal_actividades.forma_aprobacion = 0; -- Examen regular.

				-- Se actualiza el dato 'dependencia_egreso' para quienes egresen en el año a informar.
				-- Se toma la dependencia de araucano que corresponde a la ubicación de la última actividad aprobada para la oferta.
				UPDATE	int_arau_nominal_oferta
				SET		dependencia_egreso = (	SELECT		int_arau_nominal_actividades.institucion_araucano_ubicacion
												FROM		int_arau_nominal_actividades
												WHERE		int_arau_nominal_actividades.anio_academico = $anio_academico_quoteado AND
															int_arau_nominal_actividades.responsable_academica $condicion_ra AND
															int_arau_nominal_actividades.alumno = int_arau_nominal_oferta.alumno AND
															int_arau_nominal_actividades.certificado = int_arau_nominal_oferta.certificado AND
															int_arau_nominal_actividades.origen_codigo IN ('A','B','E','P') AND
															int_arau_nominal_actividades.resultado = 1
												ORDER BY	int_arau_nominal_actividades.fecha DESC
												LIMIT		1)
				WHERE	int_arau_nominal_oferta.anio_academico = $anio_academico AND
						int_arau_nominal_oferta.responsable_academica $condicion_ra AND
						int_arau_nominal_oferta.fecha_egreso IS NOT NULL;
		";						
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ ARAUCANO NOMINAL: Se borra la oferta academica y alumnos que no tienen registradas actividades ni estan reinscritos ni son egresados ese año o ingresantes. ************'); 
	}

	//--------------------------------------------------------------------------
	//-- Se deshabilitan los trigger de Auditoría
	//--------------------------------------------------------------------------

	function deshabilitar_trigger_auditoria()
	{
		$sql = "ALTER TABLE int_arau_nominal_alumnos DISABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_actividades DISABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_oferta DISABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_act_x_certificado DISABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_ha DISABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_movilidad DISABLE TRIGGER USER;
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se deshabilitaron los trigger de Auditoría de las tablas Araucano ************'); 
	}

	//--------------------------------------------------------------------------
	//-- Se habilitan los trigger de Auditoría
	//--------------------------------------------------------------------------

	function habilitar_trigger_auditoria()
	{
		$sql = "ALTER TABLE int_arau_nominal_alumnos ENABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_actividades ENABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_oferta ENABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_act_x_certificado ENABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_ha ENABLE TRIGGER USER;
				ALTER TABLE int_arau_nominal_movilidad ENABLE TRIGGER USER;
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se habilitaron los trigger de Auditoría de las tablas Araucano ************'); 
	}

	//--------------------------------------------------------------------------
	// Método para correr el VACUUM sobre las tablas de Araucano luego de que se cargan con datos
	//--------------------------------------------------------------------------

	function vacuum($tabla, $analyze = true)
	{
		// Se desahabilita la transacción en el 3er parametro del "ejecutar". 
		// Esto se hace porque Postgres no permite correr el VACUUM dentro de una transacción.
		if ($analyze) {
		   $sql = "VACUUM ANALYZE $tabla ";
		} else {
		   $sql = "VACUUM $tabla ";
		}			
		guarani_db::ejecutar($sql, false, true); 
		toba::logger()->info('************ Se realizó el VACUUM de la tabla '.$tabla.' ************'); 
	}
}
