<?php
class co_planes_reportes {
	
	function get_listado($wheres)
	{
		$sql = "select 	sga_planes_versiones.plan_version,
						sga_planes_versiones.plan,
						sga_planes_versiones.version,
						sga_planes_versiones.nombre as version_nombre,
						ev.descripcion as version_estado_descripcion,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes.nombre as nombre,
						sga_planes.nombre as plan_nombre,
						sga_planes.codigo as plan_codigo,
						sga_planes_estados.descripcion as plan_estado_descripcion,
						sga_propuestas.propuesta,
						sga_propuestas.codigo as propuesta_codigo,
						sga_propuestas.nombre as propuesta_nombre
				from	sga_planes_versiones,
						sga_planes,
						sga_propuestas,
						sga_planes_estados,
						sga_planes_estados ev
				where	sga_planes.plan = sga_planes_versiones.plan and
						sga_propuestas.propuesta = sga_planes.propuesta and
						sga_planes_estados.estado = sga_planes.estado and
						ev.estado = sga_planes_versiones.estado";
		if($wheres) {
			$sql .= " and ".$wheres;
		}
		return guarani_db::consultar($sql);
	}
	
        
	function get_datos_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "select	'('||sga_planes.codigo || ') - ' || sga_planes.nombre as nombre,
						f_modalidad_cursada_plan(sga_planes.plan) as modalidad,
						sga_planes.tope_creditos_cursado,
						sga_planes.tope_creditos_reg_vigentes,
						e1.descripcion as estado_plan,
						sga_planes.documento_alta as documento_plan,
						'('||sga_planes_versiones.version || ') - ' || sga_planes_versiones.nombre as version,
						sga_planes_versiones.documento_alta as documento_version,
						doc2.documento_numero as documento_plan_desc,
						doc1.documento_numero as documento_version_desc,
						sga_planes.duracion_teorica,
						sga_planes.duracion_en_anios,
						sga_planes.duracion_en_meses,
						sga_planes_versiones.cnt_materias,
						sga_planes_versiones.cnt_optativas,
						sga_planes_versiones.cnt_idiomas,
						e2.descripcion as estado_version,
						sga_planes_versiones.fecha_entrada_vigencia,
						'('||sga_propuestas.codigo|| ') - ' || sga_propuestas.nombre as propuesta
				from	sga_planes_versiones
                          LEFT JOIN sga_documentos as doc1 ON (sga_planes_versiones.documento_alta = doc1.documento),
						sga_planes
                          LEFT JOIN sga_documentos as doc2 ON (sga_planes.documento_alta = doc2.documento),
						sga_planes_estados as e1,
						sga_planes_estados as e2,
						sga_propuestas
				where	sga_planes_versiones.plan_version = $plan_version and
						sga_planes.plan = sga_planes_versiones.plan and
						sga_planes.estado = e1.estado and
						sga_planes_versiones.estado = e2.estado and
						sga_propuestas.propuesta = sga_planes.propuesta";

		return guarani_db::consultar($sql);
	}
	
	function get_lista_planes()
	{
		$sql = "	SELECT	sga_planes.nombre ||' - '||sga_planes_versiones.nombre as	nombre,
							sga_planes_versiones.plan_version as plan_version
					FROM	sga_planes,
							sga_planes_versiones
					WHERE	sga_planes.plan = sga_planes_versiones.plan
					ORDER BY sga_planes_versiones.plan, sga_planes_versiones.plan_version;";
		return guarani_db::consultar($sql);
	}
	
	function get_actividades($plan_version, $modulo=false, $mostrarme='true', $hijos_mg='false') 
	{
		$plan_version = toba::db()->quote($plan_version);

		if ($modulo) {
			$modulo = toba::db()->quote($modulo);
			// Recupero la revision del modulo que corresponde a la version del plan.
			$sql = "SELECT 	sga_elementos_revision.elemento_revision
					  FROM	sga_elementos_revision
					  JOIN	sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND 
					                              sga_elementos_plan.plan_version = $plan_version
					 WHERE	sga_elementos_revision.elemento = $modulo";
			$rtn = guarani_db::consultar_fila($sql);
			$elemento_revision_modulo = $rtn['elemento_revision'];
		}
		
		$sql = "SELECT DISTINCT
		                sga_elementos.codigo, 
						proc.nombre, 
						proc.entidad_tipo, 
						proc.entidad_subtipo, 
						sga_g3entidades_subtipos.nombre as tipo_de_actividad, 
						f_modalidad_cursada_elemento_plan(sga_elementos_plan.elemento_plan) as modalidad, 
						sga_elementos_plan.anio_de_cursada, 
						sga_periodos_genericos.nombre as periodo_de_cursada, 
						sga_periodos_genericos.orden_en_su_tipo as orden, 
						sga_elementos_plan.horas_semanales as horas_semanales, 
						sga_elementos_plan.creditos as creditos, 
						sga_elementos_plan.requiere_cursada as requiere_cursada, 
						sga_elementos_plan.requiere_examen as requiere_examen, 
						sga_elementos_plan.permite_promocion as permite_promocion, 
						sga_elementos_plan.promediable as promediable, 
						sga_elementos_comp.puntaje
					FROM ".(($modulo)?"get_elemento_contenido($elemento_revision_modulo, $plan_version, $mostrarme, true, $hijos_mg)":"get_plan_contenido($plan_version, true, $hijos_mg)")." as proc
					   JOIN sga_elementos on sga_elementos.elemento = proc.elemento 
					   JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_tipo = proc.entidad_tipo 
					                                     AND sga_g3entidades_subtipos.entidad_subtipo = proc.entidad_subtipo 
					   LEFT JOIN sga_elementos_comp ON sga_elementos_comp.elemento_padre = proc.elemento_revision_padre
					                               AND sga_elementos_comp.elemento_hijo  = proc.elemento_revision
					   JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = proc.elemento_revision
					   LEFT JOIN sga_periodos_genericos ON	sga_periodos_genericos.periodo_generico = sga_elementos_plan.periodo_de_cursada 
					WHERE (proc.entidad_tipo = '".elemento::tipo_actividad."' OR
					       (proc.entidad_tipo = '".elemento::tipo_modulo."' AND proc.entidad_subtipo = '".elemento::subtipo_materia_generica."')
					      ) 
					  AND sga_elementos_plan.plan_version = $plan_version 
					ORDER BY
						sga_elementos_plan.anio_de_cursada,
						sga_periodos_genericos.nombre,
						sga_periodos_genericos.orden_en_su_tipo
					";
		return guarani_db::consultar($sql);
	}
	
	//TODO las funciones get_plan_modulo_revision_hijos y get_plan_version_contenido
	//deberian devolver el código del elemento para evitar el join con la tabla sga_elementos
	//Comentamos el join con sga_elementos porque rompía el orden y no se armaba el árbol
	function get_modulos($plan_version, $modulo=false) 
	{
		if ($modulo) {
			$modulo = toba::db()->quote($modulo);
		}
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT		proc.elemento, 
							proc.nombre, 
							proc.entidad_tipo, 
							proc.entidad_subtipo, 
							proc.elemento_padre, 
							sga_g3entidades_subtipos.php_clase_gui as elemento_clase_gui, 
							proc.elemento_revision, 
							proc.codigo 
				FROM		".(($modulo)?"get_elemento_contenido($modulo, $plan_version, true, true, false)":"get_plan_contenido($plan_version, true, false)")." as proc, 
							sga_g3entidades_subtipos
				WHERE		sga_g3entidades_subtipos.entidad_subtipo = proc.entidad_subtipo AND
							proc.entidad_tipo = '".elemento::tipo_modulo."' AND
							proc.entidad_subtipo <> '".elemento::subtipo_materia_generica."'
				ORDER BY	proc.orden_arbol
		";
		return guarani_db::consultar($sql);
	}
	
	function get_modulo_detalle($plan_version, $modulo)
	{
		$plan_version = toba::db()->quote($plan_version);
		$modulo = toba::db()->quote($modulo);
		$sql = "select '(' || sga_elementos.codigo || ') - ' ||sga_elementos.nombre as nombre, 
						sga_reglas.nombre as forma_aprobacion_nombre, 
						sga_elementos_revision.parametros	as valor_aprobacion,
						sga_elementos_origen_creditos.nombre as origen_creditos, 
						null as creditos_otorgados 
				from 	sga_elementos_revision, 
						sga_elementos_plan, 
						sga_reglas,
						sga_elementos, 
						sga_elementos_origen_creditos 
				where 	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision 
						and sga_elementos_plan.plan_version = $plan_version
						and sga_elementos.elemento = $modulo
						and sga_elementos_revision.regla = sga_reglas.regla
						and sga_elementos.elemento = sga_elementos_revision.elemento 
						and sga_elementos_revision.origen_creditos = sga_elementos_origen_creditos.origen_creditos";
		return guarani_db::consultar($sql);
	}
	
	function get_actividad_detalle($plan_version, $actividad)
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT '('||sga_elementos.codigo|| ') - ' ||coalesce(sga_elementos_plan.nombre,sga_elementos.nombre) as nombre 
				FROM sga_elementos
				      LEFT JOIN	sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
				      LEFT JOIN	sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision 
				WHERE sga_elementos_plan.plan_version = $plan_version 
				  AND sga_elementos.elemento = $actividad
			 ";
		
		return guarani_db::consultar($sql);
	}
	
	function get_materias_genericas($plan_version) 
	{
		$plan_version = toba::db()->quote($plan_version);	
		
		$sql = "select 	proc.nombre,
						proc.elemento,
						sga_elementos.codigo
				from 	get_plan_contenido($plan_version, true, false) as proc,
						sga_elementos
				where 	proc.entidad_tipo = ".elemento::tipo_modulo."
						and proc.entidad_subtipo = ".elemento::subtipo_materia_generica." 
						and sga_elementos.elemento = proc.elemento";
		return guarani_db::consultar($sql);
	}
	
	function get_certificados($plan_version, $certificado=false) 
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "select 	sga_certificados.certificado,     
						'('||sga_certificados.codigo||') '||sga_certificados.nombre as nombre, 
						sga_certificados_tipos.descripcion as cert_tipo,
						f_responsables_academicas_certificado(sga_certificados.certificado) as responsable_academica,
						sga_rd_disciplinas.nombre as disciplina,
						sga_titulos_niveles.descripcion as titulo_nivel,
						sga_certificados.duracion_en_anios,
						sga_certificados.duracion_en_meses,
						sga_titulos_categ_coneau.descripcion as categ_coneau,
						".guarani_sql::get_case_si_no('sga_certificados','acreditado_coneau').",
						int_arau_titulos.nombre as titulo_araucano,
						sga_certificados.nro_resolucion_ministerio,
						sga_certificados.fecha_resolucion_ministerio
					FROM sga_certificados
					inner join sga_planes_certificados on sga_planes_certificados.certificado = sga_certificados.certificado
					inner join sga_certificados_tipos on sga_certificados_tipos.certificado_tipo = sga_certificados.certificado_tipo
					left join sga_rd_disciplinas on	sga_rd_disciplinas.disciplina = sga_certificados.disciplina
					left join sga_titulos_niveles on sga_titulos_niveles.titulo_nivel = sga_certificados.titulo_nivel
					left join sga_titulos_categ_coneau on sga_titulos_categ_coneau.categorizacion_coneau = sga_certificados.categorizacion_coneau
					left join int_arau_titulos on int_arau_titulos.titulo_araucano = sga_certificados.titulo_araucano
					WHERE sga_planes_certificados.plan_version = $plan_version
						";
		if($certificado) {
			$certificado = toba::db()->quote($certificado);
			$sql .= " 	and sga_certificados.certificado = $certificado
					";
		}
		$sql .= "	order by 
						sga_certificados_tipos.descripcion desc, 
						sga_certificados.certificado";

		return guarani_db::consultar($sql);
	}
	
	function get_arbol_certificado($plan_version, $certificado) 
	{
		$plan_version = toba::db()->quote($plan_version);
		$certificado = toba::db()->quote($certificado);
		$sql = "SELECT		sga_certificados.entidad::varchar as elemento,
							null::integer as requisito,
							sga_certificados.nombre,
							null as elemento_padre,
							null as condicion_tipo,
							'plan_elemento_gui_certificado' as elemento_clase_gui,
							null as estado,
							null as condicion,
							1 as orden
				FROM		sga_certificados
							JOIN sga_planes_certificados ON sga_planes_certificados.certificado = sga_certificados.certificado
							LEFT JOIN sga_condiciones ON sga_condiciones.entidad = sga_certificados.entidad AND sga_condiciones.plan_version = sga_planes_certificados.plan_version
				WHERE		sga_certificados.certificado = $certificado AND
							sga_planes_certificados.plan_version = $plan_version
				UNION
				SELECT		'g_'|| sga_condiciones_grupos.grupo_condicion as elemento,
							null::integer as requisito,
							'grupo '||sga_condiciones_grupos.orden as nombre,
							sga_certificados.entidad::varchar as padre,
							null as condicion_tipo,
							'plan_elemento_gui_grupo' as elemento_clase_gui,
							null as estado,
							null as condicion,
							2 as orden
				FROM		sga_certificados
							JOIN sga_condiciones ON sga_condiciones.entidad = sga_certificados.entidad
							JOIN sga_condiciones_grupos ON sga_condiciones_grupos.condicion = sga_condiciones.condicion
				WHERE		sga_certificados.certificado = $certificado AND
							sga_condiciones.plan_version = $plan_version
				UNION
				SELECT		COALESCE(vw_elementos.elemento::varchar,'r_'|| sga_requisitos.requisito) as elemento,
							sga_requisitos.requisito::integer as requisito,
							COALESCE(sga_elementos_plan.nombre, sga_requisitos.nombre) as nombre,
							'g_'|| sga_condiciones_grupos.grupo_condicion as padre,
							null as condicion_tipo,
							CASE
								WHEN sga_condiciones_requisitos.entidad IS NULL AND sga_condiciones_requisitos.requisito IS NOT NULL THEN 'plan_elemento_gui_requisito'
								ELSE sga_g3entidades_subtipos.php_clase_gui
							END as elemento_clase_gui,
							sga_condiciones_requisitos.estado,
							CASE
								WHEN sga_condiciones_requisitos.entidad IS NULL AND sga_condiciones_requisitos.requisito IS NOT NULL THEN get_regla_mensaje(sga_requisitos.regla, null)
								ELSE COALESCE(sga_estados.nombre, get_regla_mensaje(sga_condiciones_requisitos.regla, sga_condiciones_requisitos.parametros))
							END as condicion, 
							3 as orden
				FROM		sga_certificados
							JOIN sga_condiciones ON sga_condiciones.entidad = sga_certificados.entidad
							JOIN sga_condiciones_grupos ON sga_condiciones_grupos.condicion = sga_condiciones.condicion
							JOIN sga_condiciones_requisitos ON sga_condiciones_requisitos.grupo_condicion = sga_condiciones_grupos.grupo_condicion
							LEFT JOIN sga_requisitos ON sga_condiciones_requisitos.requisito = sga_requisitos.requisito
							LEFT JOIN sga_g3entidades ON sga_condiciones_requisitos.entidad = sga_g3entidades.entidad
							LEFT JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo 
							LEFT JOIN vw_elementos ON vw_elementos.entidad = sga_condiciones_requisitos.entidad
							LEFT JOIN sga_elementos_plan ON (vw_elementos.elemento_revision = sga_elementos_plan.elemento_revision AND sga_elementos_plan.plan_version = sga_condiciones.plan_version)
							LEFT JOIN sga_estados ON sga_estados.estado = sga_condiciones_requisitos.estado
				WHERE		sga_certificados.certificado = $certificado AND
							sga_condiciones.plan_version = $plan_version
				ORDER BY	orden
		";
		return guarani_db::consultar($sql);
	}
	
	function get_restricciones_actividad($plan_version, $elemento, $tipo) 
	{
		$plan_version = toba::db()->quote($plan_version);
		$elemento = toba::db()->quote($elemento);
		$tipo = toba::db()->quote($tipo);
		
		$sql = "
		select 	$elemento::varchar as elemento,
				COALESCE(ep.nombre, e1.nombre) as nombre,
				null as elemento_padre,
				null as condicion_tipo,
				sga_g3entidades_subtipos.php_clase_gui as elemento_clase_gui,
				null as condicion,
				0 as orden
		from
			sga_elementos as e1
			LEFT JOIN sga_g3entidades on e1.entidad = sga_g3entidades.entidad
			LEFT JOIN sga_g3entidades_subtipos on sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo 
			LEFT JOIN vw_elementos_plan AS ep ON (ep.elemento = e1.elemento AND ep.plan_version = $plan_version)
		where e1.elemento = $elemento
		
		union
		
		select	'rt_a' as elemento,
			'Anterior' as nombre,
			$elemento::varchar as elemento_padre,
			$tipo as condicion_tipo,
			'plan_elemento_gui_accion' as elemento_clase_gui,
			'a' as condicion,
			1 as orden
		from 	sga_condiciones_tipos
		where 	sga_condiciones_tipos.condicion_tipo = $tipo	
		union
		
		select 	'rt_p' as elemento,
			'Posterior' as nombre,
			$elemento::varchar as elemento_padre,
			$tipo as condicion_tipo,
			'plan_elemento_gui_accion' as elemento_clase_gui,
			'p' as condicion,
			1 as orden
		from 	sga_condiciones_tipos
		where 	sga_condiciones_tipos.condicion_tipo = $tipo		
		
		union	
		select	'g_'||sga_condiciones_grupos.grupo_condicion::varchar as elemento,
				'Opcion '||sga_condiciones_grupos.orden as nombre,
				case 
				  when e1.elemento = $elemento then 'rt_a' 
				  else 'rt_p' 
				end as elemento_padre,
				$tipo as condicion_tipo,
				'plan_elemento_gui_grupo' as elemento_clase_gui,
				null as condicion,
				(sga_condiciones_grupos.orden * 100) as orden 
		from	
			sga_condiciones_requisitos
			inner join sga_condiciones_grupos on sga_condiciones_requisitos.grupo_condicion = sga_condiciones_grupos.grupo_condicion
			inner join sga_condiciones on sga_condiciones.condicion = sga_condiciones_grupos.condicion
			inner join sga_elementos as e1 on e1.entidad = sga_condiciones.entidad
			LEFT JOIN sga_elementos on sga_elementos.entidad = sga_condiciones_requisitos.entidad
		where
			(e1.elemento = $elemento or sga_elementos.elemento = $elemento) and
			sga_condiciones.plan_version = $plan_version and
			sga_condiciones.condicion_tipo = $tipo		
											
		union
		
		select 	coalesce(case when e2.elemento = $elemento then e1.elemento::varchar else e2.elemento::varchar end, 'r_'|| sga_requisitos.requisito::varchar) as elemento,
				coalesce(case when e2.elemento = $elemento then COALESCE(ep1.nombre, e1.nombre) else COALESCE(ep2.nombre, e2.nombre) end, sga_requisitos.nombre) as nombre,
				'g_'|| sga_condiciones_grupos.grupo_condicion as padre,
				null as condicion_tipo,
				case when sga_requisitos.requisito is not null then 'plan_elemento_gui_requisito' else sga_g3entidades_subtipos.php_clase_gui end as elemento_clase_gui,
				case when sga_condiciones_requisitos.entidad is null and sga_condiciones_requisitos.requisito is not null then get_regla_mensaje(sga_requisitos.regla, null) else coalesce(sga_estados.nombre, get_regla_mensaje(sga_condiciones_requisitos.regla, sga_condiciones_requisitos.parametros)) end as condicion ,
				(select (sga_condiciones_grupos.orden * 100) from sga_condiciones_grupos
				where grupo_condicion = sga_condiciones_requisitos.grupo_condicion) + sga_condiciones_requisitos.orden as orden 
		from
			sga_condiciones_requisitos
			inner join sga_condiciones_grupos on sga_condiciones_requisitos.grupo_condicion = sga_condiciones_grupos.grupo_condicion
			inner join sga_condiciones on sga_condiciones.condicion = sga_condiciones_grupos.condicion
			inner join sga_elementos as e1 on e1.entidad = sga_condiciones.entidad
			left join sga_requisitos on sga_condiciones_requisitos.requisito = sga_requisitos.requisito
			left join sga_g3entidades on ((e1.elemento <> $elemento AND sga_condiciones_requisitos.entidad = sga_g3entidades.entidad) OR
				                          (e1.elemento = $elemento AND e1.entidad = sga_g3entidades.entidad)
										 )
			left join sga_g3entidades_subtipos on sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo 
			left join sga_elementos as e2 on e2.entidad = sga_condiciones_requisitos.entidad
			left join sga_estados on sga_estados.estado = sga_condiciones_requisitos.estado
			LEFT JOIN vw_elementos_plan AS ep1 ON (ep1.elemento = e1.elemento AND ep1.plan_version = $plan_version)
			LEFT JOIN vw_elementos_plan AS ep2 ON (ep2.elemento = e1.elemento AND ep2.plan_version = $plan_version)
		
		where 	
			(e1.elemento = $elemento OR e2.elemento = $elemento) and
			sga_condiciones.plan_version = $plan_version and
			sga_condiciones.condicion_tipo= $tipo		
			
		order by orden";
		
		return guarani_db::consultar($sql, PDO::FETCH_ASSOC, false);
	}
	
	function get_restricciones($plan_version, $elemento, $tipo) 
	{
		$plan_version = toba::db()->quote($plan_version);
		$elemento = toba::db()->quote($elemento);
		$tipo = toba::db()->quote($tipo);
		
		$sql = "
		SELECT 	'rt_'||$tipo					AS elemento,
				sga_condiciones_tipos.nombre	AS nombre,
				null							AS elemento_padre,
				$tipo							AS condicion_tipo,
				'plan_elemento_gui_accion'		AS elemento_clase_gui,
				null							AS condicion,
				0								AS orden,
				null							AS operador_not
		FROM 	sga_condiciones_tipos
		WHERE 	sga_condiciones_tipos.condicion_tipo = $tipo
			
		UNION	
		
		SELECT	'g_'||sga_condiciones_grupos.grupo_condicion::varchar	AS elemento,
				'Opcion '||sga_condiciones_grupos.orden					AS nombre,
				'rt_'||$tipo											AS elemento_padre,
				$tipo													AS condicion_tipo,
				'plan_elemento_gui_grupo'								AS elemento_clase_gui,
				null													AS condicion,
				(sga_condiciones_grupos.orden * 100)					AS orden,
				null													AS operador_not
		FROM	sga_condiciones_grupos,
				sga_elementos,
				sga_condiciones
		WHERE	sga_condiciones_grupos.condicion = sga_condiciones.condicion AND
				sga_elementos.entidad = sga_condiciones.entidad AND
				sga_elementos.elemento = $elemento AND
				sga_condiciones.plan_version = $plan_version AND
				sga_condiciones.condicion_tipo= $tipo
		
		UNION
		
		SELECT 	coalesce(e2.elemento::varchar, 'r_'|| sga_requisitos.requisito::varchar)	AS elemento,
				coalesce(COALESCE(ep2.nombre, e2.nombre), sga_requisitos.nombre)			AS nombre,
				'g_'|| sga_condiciones_grupos.grupo_condicion								AS padre,
				coalesce(sga_requisitos.requisito,sga_condiciones_requisitos.regla)::text	AS condicion_tipo,
				CASE
					WHEN sga_requisitos.requisito IS NOT NULL THEN 
					'plan_elemento_gui_requisito' 
					ELSE
					sga_g3entidades_subtipos.php_clase_gui 
				END																			AS elemento_clase_gui,
				CASE 
					WHEN sga_condiciones_requisitos.entidad IS NULL AND sga_condiciones_requisitos.requisito IS NOT NULL THEN
					get_regla_mensaje(sga_requisitos.regla, null) 
					ELSE 
					coalesce(sga_estados.nombre, get_regla_mensaje(sga_condiciones_requisitos.regla, sga_condiciones_requisitos.parametros)) 
				END																			AS condicion,
				(	SELECT ( sga_condiciones_grupos.orden * 100 ) 
					FROM sga_condiciones_grupos
					WHERE grupo_condicion = sga_condiciones_requisitos.grupo_condicion
				) + sga_condiciones_requisitos.orden										AS orden,
				sga_condiciones_requisitos.operador_not::text								AS operador_not
		FROM	sga_condiciones_requisitos
				JOIN sga_condiciones_grupos ON sga_condiciones_requisitos.grupo_condicion = sga_condiciones_grupos.grupo_condicion
				JOIN sga_condiciones ON	sga_condiciones.condicion = sga_condiciones_grupos.condicion
				JOIN sga_elementos as e1 ON	e1.entidad = sga_condiciones.entidad
				LEFT JOIN sga_requisitos ON	sga_condiciones_requisitos.requisito = sga_requisitos.requisito
				LEFT JOIN sga_g3entidades ON sga_condiciones_requisitos.entidad = sga_g3entidades.entidad
				LEFT JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo 
				LEFT JOIN sga_elementos as e2 ON e2.entidad = sga_condiciones_requisitos.entidad
				LEFT JOIN vw_elementos_plan as ep2 ON (ep2.elemento = e2.elemento AND ep2.plan_version = $plan_version)
				LEFT JOIN sga_estados ON sga_estados.estado = sga_condiciones_requisitos.estado
		WHERE 	e1.elemento = $elemento AND
				sga_condiciones.plan_version = $plan_version AND
				sga_condiciones.condicion_tipo= $tipo	
		ORDER 	by orden;		
		";
        
        return guarani_db::consultar($sql, PDO::FETCH_ASSOC, false);
	}
	
	function get_actividades_restringidas($plan_version) 
	{ 
		// Recupera las correlativas de las actividades de la version del plan de estudios pasado por parametro.
		$plan_version = toba::db()->quote($plan_version);
        $sql = "SELECT 
					gp.elemento as elemento,
					gp.codigo as codigo,
					gp.entidad_tipo,
					gp.entidad_subtipo,
					gp.elemento_padre as elemento_padre,
					gp.nombre,
					gp.elemento_revision,
					CASE 
					  WHEN gp.orden is not null then gp.orden 
					  ELSE -1
					END as orden,
					el.nombre elemento_nombre,
					es1.nombre as subtipo,
					sga_condiciones.condicion_tipo as tipo,
					sga_condiciones_tipos.nombre as desc_tipo,
					sga_condiciones_grupos.grupo_condicion as grupo_restriccion,
					COALESCE('Opcion ' || sga_condiciones_grupos.orden, 'Sin correlativas') as opciones,
					sga_condiciones_requisitos.requisito_condicion as requisito,
					COALESCE(COALESCE(ep_requisito.nombre, ele_requisito.nombre), sga_requisitos.nombre) as requisito_nombre,
					COALESCE('e_' || ele_requisito.entidad::varchar, 'r_' || sga_requisitos.requisito::varchar) as condicion_cumplimiento,
					CASE 
					  WHEN sga_condiciones_requisitos.entidad is null and sga_condiciones_requisitos.requisito is not null THEN	get_regla_mensaje(sga_requisitos.regla, null) 
					  ELSE COALESCE(sga_estados.nombre,get_regla_mensaje(sga_condiciones_requisitos.regla, sga_condiciones_requisitos.parametros)) 
					END as condicion,
					COALESCE(sga_condiciones_grupos.orden, 0) as orden_grupo,
					COALESCE(sga_condiciones_requisitos.orden, 0) as orden_requisito
				FROM   
					get_plan_contenido ($plan_version) as gp
					JOIN sga_elementos as el ON el.elemento = gp.elemento
					JOIN sga_g3entidades_subtipos as es1 ON es1.entidad_subtipo = el.entidad_subtipo
					LEFT JOIN sga_condiciones ON (sga_condiciones.plan_version = $plan_version AND
												  sga_condiciones.entidad = el.entidad AND 
					                              sga_condiciones.condicion_tipo IN ('".condicion::tipo_rendir."','".condicion::tipo_cursar."')
												  )
					LEFT JOIN sga_condiciones_tipos ON sga_condiciones.condicion_tipo = sga_condiciones_tipos.condicion_tipo
					LEFT JOIN sga_condiciones_grupos ON sga_condiciones.condicion = sga_condiciones_grupos.condicion
					LEFT JOIN sga_condiciones_requisitos ON sga_condiciones_requisitos.grupo_condicion = sga_condiciones_grupos.grupo_condicion
					-- Requisitos
					LEFT JOIN sga_requisitos ON sga_condiciones_requisitos.requisito = sga_requisitos.requisito
					-- Actividades/Modulos
					LEFT JOIN sga_g3entidades ON sga_condiciones_requisitos.entidad = sga_g3entidades.entidad
					LEFT JOIN sga_elementos as ele_requisito ON ele_requisito.entidad = sga_condiciones_requisitos.entidad
					LEFT JOIN vw_elementos_plan as ep_requisito ON (ep_requisito.elemento = ele_requisito.elemento AND ep_requisito.plan_version = $plan_version)
					LEFT JOIN sga_estados ON sga_estados.estado = sga_condiciones_requisitos.estado,
					sga_elementos_plan as ep,
					sga_elementos_revision as er
				WHERE 
					ep.plan_version = $plan_version 
					AND er.elemento_revision = ep.elemento_revision 
					AND el.elemento = er.elemento
				ORDER BY orden_arbol,
				         sga_condiciones.condicion_tipo, 
						 sga_condiciones_grupos.orden,
					     sga_condiciones_requisitos.orden
				"; 
    	return guarani_db::consultar($sql, PDO::FETCH_ASSOC, false);
	} 
	
	function get_requisitos_plan_version($plan_version, $where = '')
	{	
		//TODO CONSTANTES ACCIONES
		//TODO   ver meter en funcion 	get_plan_version_contenido.elemento_codigo ojo con el filtro
		
		if ($where) $where = ' WHERE '.$where.' ';
		$planv = toba::db()->quote($plan_version);
		$sql = "SELECT	get_plan_contenido.elemento as elemento,
		                get_plan_contenido.codigo as codigo,
						get_plan_contenido.entidad_tipo,
						get_plan_contenido.entidad_subtipo,
						get_plan_contenido.entidad_clase_gui as elemento_clase_gui,
						get_plan_contenido.elemento_padre as elemento_padre,
						get_plan_contenido.nombre,
						get_plan_contenido.elemento_revision,
						case 
							when get_plan_contenido.orden is not null then 
								get_plan_contenido.orden 
							else
								-1
							end as orden,
        				(SELECT 	count(*) 
        					FROM  	sga_condiciones,
        							sga_elementos
							WHERE	sga_condiciones.plan_version = $planv
					 		AND		sga_condiciones.entidad = sga_elementos.entidad
					 		AND		sga_elementos.elemento = get_plan_contenido.elemento
                     		AND		sga_condiciones.condicion_tipo = '".condicion::tipo_rendir."') as req_aprobada,
                     	(SELECT 	count(*) 
        					FROM  	sga_condiciones,
        							sga_elementos
							WHERE	sga_condiciones.plan_version = $planv
					 		AND		sga_condiciones.entidad = sga_elementos.entidad
					 		AND		sga_elementos.elemento = get_plan_contenido.elemento
                     		AND		sga_condiciones.condicion_tipo = '".condicion::tipo_cursar."') as req_cursada
 				FROM	get_plan_contenido ($planv)
 				order by orden_arbol
 					  ". $where ;
		$datos = guarani_db::consultar($sql);
		if(!isset($datos)) {
			throw new guarani_error("No se pudo recuperar datos del plan_version [$plan_version] ");
		}
		return $datos;
	}
	
	function get_nodos_plan($plan_version, $actividades=true)
	{
		$plan_version = toba::db()->quote($plan_version);
		if ($actividades) {
			// Actividades y materias genéricas.
			$where = "	get_plan_contenido.entidad_tipo = '".elemento::tipo_actividad."' OR
						get_plan_contenido.entidad_tipo = '".elemento::tipo_modulo."' AND 
						get_plan_contenido.entidad_subtipo = '".elemento::subtipo_materia_generica."'";
		} else {
			// Módulos.
			$where = "	get_plan_contenido.entidad_tipo = '".elemento::tipo_modulo."' AND
						get_plan_contenido.entidad_subtipo = '".elemento::subtipo_agrupador_modulos."'";
		}
		$sql = "SELECT 
							get_plan_contenido.elemento,
							get_plan_contenido.codigo, 
							get_plan_contenido.nombre, 
							sga_elementos.nombre_abreviado,
							get_plan_contenido.elemento_revision
				FROM		get_plan_contenido($plan_version, true, false)
							JOIN sga_elementos ON get_plan_contenido.elemento = sga_elementos.elemento
				WHERE		$where
				ORDER BY	get_plan_contenido.elemento
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_modulo_contenido($plan_version, $modulo)
	{
		$plan_version = toba::db()->quote($plan_version);
		$modulo = toba::db()->quote($modulo);
		$sql = "SELECT		get_elemento_contenido.elemento_revision
				FROM		get_elemento_contenido($modulo, $plan_version, true, true, false)
				WHERE		(get_elemento_contenido.entidad_tipo = '".elemento::tipo_actividad."' OR
							(get_elemento_contenido.entidad_tipo = '".elemento::tipo_modulo."' AND get_elemento_contenido.entidad_subtipo = '".elemento::subtipo_materia_generica."'))
				ORDER BY	get_elemento_contenido.elemento_revision
		";
		return guarani_db::consultar($sql);
	}

	function get_transiciones_correlativas_plan($plan_version, $condicion = false, $actividad = false)
	{
		$plan_version = toba::db()->quote($plan_version);
		$where_1 = "";
		$where_2 = "";
		
		if ($actividad) {
			$actividad = toba::db()->quote($actividad);
            $where_1 = " AND vw_elementos.elemento = $actividad ";
        }
		if ($condicion) {
			$condicion = toba::db()->quote($condicion);
            $where_2 = " AND vw_condiciones.condicion_tipo = $condicion ";
        }
		
		$sql = "
			SELECT DISTINCT 
				vw_condiciones.elemento			AS origen,
				vw_elementos.elemento			AS destino,
				vw_condiciones.operador_not,
				coalesce(vw_condiciones.requisito, sga_reglas.regla)			AS regla,
				coalesce(vw_condiciones.requisito_nombre, sga_reglas.nombre)	AS nombre
			FROM 
				vw_condiciones 
				LEFT JOIN sga_reglas	ON sga_reglas.regla = vw_condiciones.regla
				LEFT JOIN vw_elementos	ON vw_elementos.entidad = vw_condiciones.condicion_entidad
			WHERE 
				vw_condiciones.plan_version = $plan_version
				$where_1
				$where_2
			";
		return guarani_db::consultar($sql);
	}
	
	function get_elementos_plan_version($plan_version) 
	{
		$plan_version = toba::db()->quote($plan_version);
		
		$sql = "
			SELECT
				sga_elementos_revision.elemento,
				sga_elementos_revision.elemento_revision
			FROM
				sga_elementos_plan
				LEFT JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
			WHERE
				sga_elementos_plan.plan_version = $plan_version
			";
		return guarani_db::consultar($sql);
	}
}
?>