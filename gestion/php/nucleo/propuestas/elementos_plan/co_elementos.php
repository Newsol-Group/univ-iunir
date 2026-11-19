<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class co_elementos
{
	function get_listado($tipo, $subtipo = null, $filtro='')
	{
		if ($filtro == array()) $filtro = '';
		$tipo = toba::db()->quote($tipo);	
		$from = " FROM	sga_elementos,
						sga_g3entidades_subtipos,
						sga_g3entidades_tipos,
						sga_elementos_estados,
						sga_g3entidades";
		$where = " WHERE sga_g3entidades_subtipos.entidad_tipo = $tipo ";
		if (isset($subtipo)) {
			$subtipo = toba::db()->quote($subtipo);
			$where .=	 " AND sga_elementos.entidad_subtipo = $subtipo ";
		}	
		$where .=	"AND sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
					AND sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
					AND	sga_elementos.estado = sga_elementos_estados.estado
					AND	sga_elementos.entidad = sga_g3entidades.entidad ";

		if ($filtro) $where .= 'AND '.$filtro;
		$sql = "SELECT 	sga_elementos.elemento,
						sga_elementos.codigo,
						sga_elementos.entidad,
						sga_elementos.nombre,
						sga_elementos.nombre_abreviado,
						sga_g3entidades_tipos.entidad_tipo,
						sga_g3entidades_tipos.nombre 		as tipo_desc,
						sga_g3entidades_subtipos.entidad_subtipo,
						sga_g3entidades_subtipos.nombre 	as subtipo_desc,
						sga_elementos.estado 				as estado,
						sga_elementos_estados.descripcion 	as estado_desc,
						sga_g3entidades.entidad_subtipo,
						sga_g3entidades.entidad
				$from 
				$where 
				ORDER BY sga_elementos.codigo
						;";
		return guarani_db::consultar($sql);
	}
		
	/**
	 * Retorna todas las materias genéricas con las correpondientes materias optativas que la componen en el orden dado,
	 * ordenadas alfabéticamente por el nombre de la materia genérica.
	 */
	function get_materias_genericas_con_sus_componentes($filtro)
	{
		if ((is_null($filtro)) || ($filtro == '')) {
			$filtro = '';
		} else {
			$filtro = ' AND ' . $filtro;
		}

		$sql = "SELECT  generica.elemento     				as materia_generica,
					    rev_padre.elemento_revision     	as revision_materia_generica,
					    generica.codigo            			as codigo_materia_generica, 
					    generica.entidad            		as entidad_materia_generica, 
					    generica.nombre            			as nombre_materia_generica,
					    get_regla_mensaje(rev_padre.regla, rev_padre.parametros) as forma_cumplimiento,
					    creditos_padre.nombre 				as origen_credito,
					    sga_elementos_comp.puntaje     		as puntaje_materia_optativa,
					    sga_elementos_comp.orden,
					    sga_elementos_estados.descripcion 	as desc_estado_materia_optativa, 
					    optativa.codigo            			as codigo_materia_optativa,
					    optativa.entidad            		as entidad_materia_optativa,
					    rev_hijo.elemento_revision         	as revision_materia_optativa,
					    sga_g3entidades_subtipos.nombre		as optativa_tipo,
					    optativa.nombre            			as nombre_materia_optativa,
					    '" . comunes::si . "'				as optativa_confirmada 
				FROM	sga_elementos 						as generica, 
            			sga_elementos_revision 				as rev_padre
                 		LEFT JOIN sga_reglas as regla_padre ON rev_padre.regla = regla_padre.regla, 
            			sga_elementos_origen_creditos as creditos_padre, 
            			sga_elementos_comp, 
            			sga_elementos_estados, 
            			sga_elementos as optativa,
            			sga_g3entidades_subtipos,
            			sga_elementos_revision as rev_hijo
            			
   				WHERE  	rev_padre.elemento = generica.elemento 
        				AND generica.entidad_subtipo = ".elemento::subtipo_materia_generica."
           				AND rev_padre.origen_creditos = creditos_padre.origen_creditos 
           				AND sga_elementos_comp.elemento_padre = rev_padre.elemento_revision
           				AND sga_elementos_comp.elemento_hijo = rev_hijo.elemento_revision 
           				AND optativa.elemento = rev_hijo.elemento
           				AND optativa.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
           				AND optativa.estado = sga_elementos_estados.estado 
           				$filtro
    
           		ORDER BY  	generica.nombre,
       						sga_elementos_comp.orden
 						;";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna todas las materias optativas que componen a la materia genérica dada, en el orden indicado.
	 */
	function get_optativas_materia_generica($mat_generica)
	{
		$mat_generica = toba::db()->quote($mat_generica);	
		$filtro = "generica.elemento = $mat_generica";
		return $this->get_materias_genericas_con_sus_componentes($filtro);	
	}
	
	/**
	 * Retorna las materias optativas temporales, que todavía no han sido confirmadas, y están asociadas a la
	 * actividad genérica.
	 *
	 * @param integer $mat_generica Id del elemento que identifica a la materia genérica. Su elemento revisión 
	 * asociado es único.
	 */
	function get_optativas_temporales_materia_generica($mat_generica)
	{
		$mat_generica = toba::db()->quote($mat_generica);
		
		$sql = "SELECT 		sga_elementos_comp_aux.elemento_comp,
							sga_elementos_comp_aux.elemento_hijo AS elemento_revision_optativa,
							sga_elementos_comp_aux.puntaje	AS puntaje_optativa,
							sga_elementos_comp_aux.orden AS orden_optativa

				FROM		sga_elementos_comp_aux
          
   				WHERE		sga_elementos_comp_aux.elemento_padre = $mat_generica
    
           		ORDER BY	sga_elementos_comp_aux.orden
       		;";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna true si la optativa (elemento revision) dada, se encuentra asociada de manera temporal a alguna genérica
	 * en un plan versión dado.
	 */
	function optativa_temporal_de_generica_esta_en_plan_version($elem_revision, $plan_version, $where='')
	{
		$elem_revision = toba::db()->quote($elem_revision);
		$plan_version = toba::db()->quote($plan_version);
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 		sga_elementos_comp_aux.elemento_comp,
							sga_elementos_comp_aux.elemento_padre,
							sga_elementos_comp_aux.elemento_hijo AS elemento_revision_optativa
							
				FROM		sga_elementos_comp_aux,
							sga_elementos_plan_aux
          
   				WHERE		sga_elementos_comp_aux.elemento_hijo = $elem_revision
							AND sga_elementos_plan_aux.elemento_revision = sga_elementos_comp_aux.elemento_hijo
							AND sga_elementos_plan_aux.plan_version = $plan_version
							$where
       		;";

		$result = guarani_db::consultar($sql);
		return !empty($result);
	}

	/**
	 * Determina si la actividad es parte de una materia genérica en el plan-versión
	 * 
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return boolean
	 */
	function es_optativa_de_generica_en_plan_version($elemento, $plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	COUNT(1) as cant
				FROM	sga_elementos_comp
						JOIN vw_elementos_plan as elementos_plan_generica ON sga_elementos_comp.elemento_padre = elementos_plan_generica.elemento_revision
						JOIN vw_elementos_plan as elementos_plan_optativa ON sga_elementos_comp.elemento_hijo = elementos_plan_optativa.elemento_revision
				WHERE	elementos_plan_generica.plan_version = elementos_plan_optativa.plan_version AND
						elementos_plan_optativa.elemento = $elemento AND
						elementos_plan_optativa.plan_version = $plan_version AND
						elementos_plan_generica.entidad_subtipo = ".elemento::subtipo_materia_generica."
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] > 0;
	}
	
	function get_atributos_optativas_temporales_en_planes_versiones_materia_generica($mat_generica)
	{
		$mat_generica = toba::db()->quote($mat_generica);
		
		$sql = "SELECT 		sga_elementos_comp_aux.elemento_hijo AS elemento_revision,
							sga_elementos_comp_aux.puntaje	AS puntaje_optativa,
							sga_elementos_comp_aux.orden AS orden_optativa,
							sga_elementos_plan_aux.elemento_plan,
							sga_elementos_plan_aux.plan_version,
							sga_elementos_plan_aux.nombre,
							sga_elementos_plan_aux.nombre_abreviado,
							sga_elementos_plan_aux.anio_de_cursada,
							sga_elementos_plan_aux.periodo_de_cursada,
							sga_elementos_plan_aux.escala_nota_cursada_regular,
							sga_elementos_plan_aux.escala_nota_cursada_promocion,
							sga_elementos_plan_aux.escala_nota_examen,
							sga_elementos_plan_aux.escala_nota_equivalencia,
							sga_elementos_plan_aux.creditos,
							sga_elementos_plan_aux.creditos_min,
							sga_elementos_plan_aux.creditos_max,
							sga_elementos_plan_aux.coeficiente,
							sga_elementos_plan_aux.horas_semanales,
							sga_elementos_plan_aux.horas_totales,
							sga_elementos_plan_aux.requiere_cursada,
							sga_elementos_plan_aux.requiere_examen,
							sga_elementos_plan_aux.permite_promocion,
							sga_elementos_plan_aux.permite_rendir_libre,
							sga_elementos_plan_aux.aprobacion_x_resolucion,
							sga_elementos_plan_aux.vigencia,
							sga_elementos_plan_aux.plazo,
							sga_elementos_plan_aux.promediable,
							sga_elementos_plan_aux.sale_listado,
							sga_elementos_plan_aux.cantidad_de_aplazos,
							sga_elementos_plan_aux.cobrable,
							sga_elementos_plan_aux.optativa_existe_en_plan

				FROM		sga_elementos_comp_aux,
							sga_elementos_plan_aux
          
   				WHERE		sga_elementos_comp_aux.elemento_padre = $mat_generica
							AND sga_elementos_comp_aux.elemento_hijo = sga_elementos_plan_aux.elemento_revision
							AND sga_elementos_plan_aux.plan_version IN (SELECT	sga_elementos_plan.plan_version
																		FROM	sga_elementos_plan
																		WHERE	sga_elementos_plan.elemento_revision = $mat_generica
																		)
    
           		ORDER BY	sga_elementos_comp_aux.orden
       		;";

		return guarani_db::consultar($sql);
	}

	function get_atributos_optativas_en_planes_versiones_editables_materia_generica($materia_generica)
	{
		$materia_generica = toba::db()->quote($materia_generica);
		$sql = "SELECT		sga_elementos_comp.elemento_hijo as elemento_revision,
							sga_elementos_comp.puntaje as puntaje_optativa,
							sga_elementos_comp.orden as orden_optativa,
							sga_elementos_plan.elemento_plan,
							sga_elementos_plan.plan_version,
							sga_elementos_plan.nombre,
							sga_elementos_plan.nombre_abreviado,
							sga_elementos_plan.anio_de_cursada,
							sga_elementos_plan.periodo_de_cursada,
							sga_elementos_plan.escala_nota_cursada_regular,
							sga_elementos_plan.escala_nota_cursada_promocion,
							sga_elementos_plan.escala_nota_examen,
							sga_elementos_plan.escala_nota_equivalencia,
							sga_elementos_plan.creditos,
							sga_elementos_plan.creditos_min,
							sga_elementos_plan.creditos_max,
							sga_elementos_plan.coeficiente,
							sga_elementos_plan.horas_semanales,
							sga_elementos_plan.horas_totales,
							sga_elementos_plan.requiere_cursada,
							sga_elementos_plan.requiere_examen,
							sga_elementos_plan.permite_promocion,
							sga_elementos_plan.permite_rendir_libre,
							sga_elementos_plan.aprobacion_x_resolucion,
							sga_elementos_plan.vigencia,
							sga_elementos_plan.plazo,
							sga_elementos_plan.promediable,
							sga_elementos_plan.sale_listado,
							sga_elementos_plan.cantidad_de_aplazos,
							sga_elementos_plan.cobrable
				FROM		sga_elementos_comp
							JOIN sga_elementos_plan ON sga_elementos_comp.elemento_hijo = sga_elementos_plan.elemento_revision
							JOIN sga_planes_versiones ON sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
   				WHERE		sga_elementos_comp.elemento_padre = $materia_generica AND
							sga_elementos_plan.plan_version IN (SELECT	sga_elementos_plan.plan_version
																FROM	sga_elementos_plan
																WHERE	sga_elementos_plan.elemento_revision = $materia_generica) AND
							(sga_planes.tipo_plan = '".planes::tipo_estructurado."' AND sga_planes.administra_cursos = '".comunes::si."' AND sga_planes_versiones.estado <> '".planes::estado_baja."' OR
							sga_planes.tipo_plan = '".planes::tipo_estructurado."' AND sga_planes.administra_cursos = '".comunes::no."' AND sga_planes_versiones.estado = '".planes::estado_nuevo."' OR
							sga_planes.tipo_plan IN ('".planes::tipo_personalizado."', '".planes::tipo_convenio."') AND sga_planes_versiones.estado <> '".planes::estado_baja."')
				ORDER BY	sga_elementos_comp.orden
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Se verifica si la actividad dada es parte de una actividad generica en el plan versión dado,
	 * y no tiene creada comisiones, mesas de examen ni equivalencias otorgadas para dicho plan versión.
	 * Si es así se permite asignarle correlativas, sin importar el estado del plan.
	 * @param integer $entidad entidad asociada al elemento, actividad optativa, para el cual se quiere verificar la condición.
	 * @param integer $plan_version plan version en el que se encuentra la actividad dada
	 * @return boolean retorna true si el elemento dado corresponde a una actividad Optativa de una Genérica en el Plan
	 * Versión dado, que aún no posee abiertas comisiones ni mesas de examen.
	 */
	function es_optativa_de_generica_sin_mesas_comisiones($entidad, $plan_version)
	{
		$entidad = toba::db()->quote($entidad);
		
		$sql = "SELECT	COUNT(1) as cant
				FROM	vw_elementos_plan as optativa,
						sga_elementos,
						vw_elementos_plan as generica,
						sga_elementos_comp
				WHERE	optativa.plan_version = $plan_version
						AND optativa.elemento = sga_elementos.elemento
						AND sga_elementos.entidad = $entidad
						AND generica.plan_version = optativa.plan_version
						AND generica.entidad_subtipo = ". elemento::subtipo_materia_generica ."-- Generica
						AND sga_elementos_comp.elemento_padre = generica.elemento_revision
						AND sga_elementos_comp.elemento_hijo  = optativa.elemento_revision
						AND NOT EXISTS (SELECT	1
										FROM	sga_comisiones as c, 
												sga_comisiones_propuestas as cp, 
												sga_planes_versiones as pv 
										WHERE	c.elemento = optativa.elemento
												AND cp.comision = c.comision
												AND pv.plan = cp.plan
												AND pv.plan_version = optativa.plan_version
										)
						AND NOT EXISTS (SELECT	1
										FROM	sga_mesas_examen as m, 
												sga_mesas_examen_propuestas as mep, 
												sga_planes_versiones as pv
										WHERE	m.elemento = optativa.elemento
												AND mep.mesa_examen = m.mesa_examen
												AND pv.plan = mep.plan
												AND pv.plan_version = optativa.plan_version
										)
						AND NOT EXISTS (SELECT	1
										FROM	sga_equiv_tramite as et
												JOIN sga_equiv_otorgada as eo ON eo.equivalencia_tramite = et.equivalencia_tramite
										WHERE	et.plan_version = optativa.plan_version
												AND eo.elemento = optativa.elemento)
       		;";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant'] != 0;
	}
	
	function get_datos_basicos_actividad($actividad)
	{
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT	vw_actividades.elemento,
						vw_actividades.codigo,
						vw_actividades.nombre,
						COALESCE('(' || vw_actividades.codigo || ') ', '') || vw_actividades.nombre AS actividad_descr,
						vw_actividades.entidad_subtipo AS tipo_actividad,
						vw_actividades.entidad_subtipo_nombre AS tipo_actividad_nombre,
						sga_elementos_atrib.escala_nota_examen
				FROM	vw_actividades,
						sga_elementos_atrib
				WHERE	vw_actividades.elemento = $actividad AND
						sga_elementos_atrib.elemento = vw_actividades.elemento
				;";
		
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Verifica si el elemento dado permite promoción para alguno de las versiones activas o vigentes de los
	 * planes dados.
	 */
	function actividad_promocionable_en_planes_versiones_activas($id_elemento, $planes)
	{
		$id_elemento = toba::db()->quote($id_elemento);
		$filtro_planes = ' AND ' . guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
		
		$sql = "SELECT	COUNT(*) as cantidad
				FROM	sga_elementos_revision,		
						sga_elementos_plan, 
						sga_planes_versiones 
				WHERE	sga_elementos_revision.elemento = $id_elemento 
						AND sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision 
						AND sga_planes_versiones.plan_version = sga_elementos_plan.plan_version 
						AND sga_planes_versiones.estado IN ('" . planes::estado_activo . "','" . planes::estado_vigente . "')
						AND sga_elementos_plan.permite_promocion = '" . comunes::si . "'
						$filtro_planes
				;";
		
		$result = guarani_db::consultar_fila($sql);
		return $result['cantidad'] > 0;
	}
	
	/**
	 * Obtiene los datos y atributos básicos asociados a una actividad optativa dada (id de elemento-revision).
	 *
	 */
	function get_datos_atributos_actividad($elemento_revision)
	{
		$elemento_revision = toba::db()->quote($elemento_revision);
		$sql = "SELECT	vw_actividades.elemento_revision,
						vw_actividades.nombre,
						vw_actividades.nombre_abreviado,
						sga_elementos_atrib.escala_nota_cursada_regular,
						sga_elementos_atrib.escala_nota_cursada_promocion,
						sga_elementos_atrib.escala_nota_examen,
						sga_elementos_atrib.escala_nota_equivalencia,
						sga_elementos_atrib.creditos,
						sga_elementos_atrib.creditos_min,
						sga_elementos_atrib.creditos_max,
						sga_elementos_atrib.coeficiente,
						sga_elementos_atrib.horas_semanales,
						sga_elementos_atrib.horas_totales,
						sga_elementos_atrib.requiere_cursada,
						sga_elementos_atrib.requiere_examen,
						sga_elementos_atrib.permite_promocion,
						sga_elementos_atrib.permite_rendir_libre,
						sga_elementos_atrib.aprobacion_x_resolucion,
						sga_elementos_atrib.vigencia,
						sga_elementos_atrib.promediable,
						sga_elementos_atrib.sale_listado,
						sga_elementos_atrib.cobrable,
						sga_elementos_atrib.cantidad_de_aplazos
				FROM	vw_actividades,
						sga_elementos_atrib
				WHERE	vw_actividades.elemento_revision = $elemento_revision AND
						sga_elementos_atrib.elemento = vw_actividades.elemento
				;";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Si una actividad (elemento-revision) está definida para el plan versión dado, retorna el conjunto de 
	 * atributos y modalidades configuradas para la misma.
	 *
	 */
	function get_datos_actividad_en_plan_version($elemento_revision, $plan_version)
	{
		$elemento_revision = toba::db()->quote($elemento_revision);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_plan.elemento_plan,
						sga_elementos_plan.elemento_revision,
						sga_elementos_plan.nombre,
						sga_elementos_plan.nombre_abreviado,
						sga_elementos_plan.anio_de_cursada,
						sga_elementos_plan.periodo_de_cursada,
						sga_elementos_plan.escala_nota_cursada_regular,
						sga_elementos_plan.escala_nota_cursada_promocion,
						sga_elementos_plan.escala_nota_examen,
						sga_elementos_plan.escala_nota_equivalencia,
						sga_elementos_plan.creditos,
						sga_elementos_plan.creditos_min,
						sga_elementos_plan.creditos_max,
						sga_elementos_plan.coeficiente,
						sga_elementos_plan.horas_semanales,
						sga_elementos_plan.horas_totales,
						sga_elementos_plan.requiere_cursada,
						sga_elementos_plan.requiere_examen,
						sga_elementos_plan.permite_promocion,
						sga_elementos_plan.permite_rendir_libre,
						sga_elementos_plan.aprobacion_x_resolucion,
						sga_elementos_plan.vigencia,
						sga_elementos_plan.plazo,
						sga_elementos_plan.promediable,
						sga_elementos_plan.sale_listado,
						sga_elementos_plan.cobrable,
						sga_elementos_plan.cantidad_de_aplazos,
						sga_propuestas.nombre as propuesta_nombre,
						sga_planes_versiones.nombre as plan_version_nombre
				FROM	sga_elementos_plan
				JOIN	sga_planes_versiones ON sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				JOIN	sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN	sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				WHERE	sga_elementos_plan.elemento_revision = $elemento_revision AND
						sga_elementos_plan.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Si una actividad optativa (elemento-revision) está definida en los datos temporales para el plan versión dado, 
	 * sin confirmar, retorna el conjunto de atributos y modalidades configuradas para la misma.
	 *
	 */
	function get_datos_temporales_optativa_en_plan_version($actividad, $plan_version)
	{
		$actividad = toba::db()->quote($actividad);
		$plan_version = toba::db()->quote($plan_version);
		
		$sql = "SELECT	sga_elementos_plan_aux.elemento_plan,
						sga_elementos_plan_aux.plan_version,
						sga_elementos_plan_aux.elemento_revision,
						sga_elementos_plan_aux.nombre,
						sga_elementos_plan_aux.nombre_abreviado,
						sga_elementos_plan_aux.anio_de_cursada,
						sga_elementos_plan_aux.periodo_de_cursada,
						sga_elementos_plan_aux.escala_nota_cursada_regular,
						sga_elementos_plan_aux.escala_nota_cursada_promocion,
						sga_elementos_plan_aux.escala_nota_examen,
						sga_elementos_plan_aux.escala_nota_equivalencia,
						sga_elementos_plan_aux.creditos,
						sga_elementos_plan_aux.creditos_min,
						sga_elementos_plan_aux.creditos_max,
						sga_elementos_plan_aux.coeficiente,
						sga_elementos_plan_aux.horas_semanales,
						sga_elementos_plan_aux.horas_totales,
						sga_elementos_plan_aux.requiere_cursada,
						sga_elementos_plan_aux.requiere_examen,
						sga_elementos_plan_aux.permite_promocion,
						sga_elementos_plan_aux.permite_rendir_libre,
						sga_elementos_plan_aux.aprobacion_x_resolucion,
						sga_elementos_plan_aux.vigencia,
						sga_elementos_plan_aux.plazo,
						sga_elementos_plan_aux.promediable,
						sga_elementos_plan_aux.sale_listado,
						sga_elementos_plan_aux.cantidad_de_aplazos,
						sga_elementos_plan_aux.cobrable,
						sga_elementos_plan_aux.optativa_existe_en_plan
				FROM	sga_elementos_plan_aux
				WHERE	sga_elementos_plan_aux.elemento_revision = $actividad
						AND sga_elementos_plan_aux.plan_version = $plan_version
				;";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 */
	function get_datos_basicos_elemento ($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.elemento,
						sga_elementos.entidad,
						sga_elementos.nombre,
						sga_elementos.nombre_abreviado,
						sga_elementos.compartible,
						sga_elementos_atrib.creditos,
						sga_elementos_atrib.creditos_min,
						sga_elementos_atrib.creditos_max,
						sga_elementos_atrib.coeficiente,
						sga_elementos_atrib.vigencia,
						sga_elementos_atrib.cantidad_de_aplazos,
						sga_elementos_atrib.escala_nota_cursada_regular,
						sga_elementos_atrib.escala_nota_cursada_promocion,
						sga_elementos_atrib.escala_nota_examen,
						sga_elementos_atrib.escala_nota_equivalencia,
						sga_elementos_atrib.cobrable,
						sga_elementos_atrib.horas_semanales,
						sga_elementos_atrib.horas_totales,
						sga_elementos_atrib.requiere_cursada,
						sga_elementos_atrib.requiere_examen,
						sga_elementos_atrib.permite_promocion,
						sga_elementos_atrib.permite_rendir_libre,
						sga_elementos_atrib.aprobacion_x_resolucion,
						sga_elementos_atrib.promediable,
						sga_elementos_atrib.sale_listado,
						sga_elementos_atrib.insc_cursada_por_prioridad,
						sga_elementos_atrib.prioridad_cant_maxima
				FROM	sga_elementos LEFT JOIN sga_elementos_atrib ON sga_elementos.elemento = sga_elementos_atrib.elemento
				WHERE 	sga_elementos.elemento IN (" . $elemento . ")";
		return guarani_db::consultar_fila($sql);
	}
	
    /**
     * Retorna en un array los valores y descripciones de los estados de los elementos
     *
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                 .
     * 				   .
     *                 array('valor' => 'valorN' , 'descr' => 'descrN')
     *                )
     */
	function get_elementos_estados()
	{
		$sql = "SELECT 	sga_elementos_estados.estado 		as valor,
                       	sga_elementos_estados.descripcion 	as descr 
  				FROM  	sga_elementos_estados";
		return guarani_db::consultar($sql);
	}

	 /**
	 * Retorna las opciones de origen de créditos de un elemento
     *
     * @return Array (
     *                 array('valor' => 'valor1' , 'descr' => 'descr1'),
     *                 array('valor' => 'valor2' , 'descr' => 'descr2')
     *                )
    */
	function get_elementos_origen_creditos()
	{
		$sql = "SELECT 	sga_elementos_origen_creditos.origen_creditos 	as valor,
					   	sga_elementos_origen_creditos.nombre 			as descr
				FROM  	sga_elementos_origen_creditos";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los tipos de elementos existentes
	 */
	function get_elementos_tipos()
	{
		$sql = "SELECT 	sga_g3entidades_tipos.entidad_tipo 	as valor,
        				sga_g3entidades_tipos.nombre 		as descr
  				FROM 	sga_g3entidades_tipos
  				WHERE  	sga_g3entidades_tipos.es_elemento = 'S';
  						;";
		return guarani_db::consultar($sql);
	}

	/**
  	*/
	function get_actividades($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento as 						id,
						sga_elementos.nombre as 						nombre, 
						sga_elementos.codigo as							codigo,
						sga_elementos.codigo || ' - ' || sga_elementos.nombre as descr,
						sga_elementos.entidad as 						entidad,
						sga_elementos.estado as 						estado,
						sga_elementos_estados.descripcion as			estado_nombre,
						sga_g3entidades_subtipos.icono as				icono,
						sga_g3entidades_subtipos.entidad_tipo as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre,
						sga_elementos_revision.elemento_revision as 	elemento_revision 
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos,
				  		sga_elementos_revision,
				  		sga_elementos_estados 
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo
				 AND	sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 AND 	sga_elementos_revision.elemento			=  sga_elementos.elemento 
				 AND 	sga_elementos.estado 					= sga_elementos_estados.estado 
				 ".$where."
				 ORDER BY nombre
				 		;"; 
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);	
	}

	/**
	 * Obtiene los nombres y subtipos de todas las actividades existentes en la Unidad Académica.
	 * @param str	$where	filtro sobre las actividades a recuperar.
  	*/
	function get_listado_actividades($where='')
	{
		if ($where) $where = ' AND '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento,
						sga_elementos.nombre as actividad_nombre,
						sga_elementos.codigo,
						COALESCE (sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre as actividad_descr,
						sga_elementos.entidad,
						sga_g3entidades_subtipos.entidad_subtipo,
						sga_g3entidades_subtipos.nombre as subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo AND
				 		sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 " . $where . "
				 ORDER BY actividad_nombre
				 ;"; 
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Obtiene los nombres y subtipos de todas las actividades existentes en los planes de una propuesta
	 * @param int	$propuesta	filtro de actividades de una propuesta
  	*/
	function get_listado_actividades_propuesta($propuesta)
	{
		// Listado de Actividades de los planes de una propuesta	
		$sql = "SELECT 	DISTINCT 
						vw_actividades_plan.elemento,
						vw_actividades_plan.nombre as actividad_nombre,
						vw_actividades_plan.codigo,
						COALESCE(vw_actividades_plan.codigo, 'Sin código') || ' - ' || vw_actividades_plan.nombre as actividad_descr,
						vw_actividades_plan.entidad,
						vw_actividades_plan.entidad_subtipo,
						vw_actividades_plan.entidad_subtipo_nombre as subtipo_nombre
				  FROM 	vw_actividades_plan 
				 WHERE 	vw_actividades_plan.propuesta = $propuesta
				 ORDER BY vw_actividades_plan.nombre
				 ;"; 
		return guarani_db::consultar($sql);	
	}
		
	/**
	 * Obtiene todos los elementos de tipo Actividad y sus subtipos asociados a un plan versión, según el filtro dado.
	 *
	 * @param integer	$id_plan_version	id del plan versión para el cual se recuperan todas las actividades asociadas.
	 * @param str		$where				filtro sobre las actividades a recuperar.
	 * @param str		$order				columnas que establecen el orden de la consulta
	 */
	function get_actividades_plan_version($id_plan_version, $where='', $order = 'actividad_nombre')
	{
		$id_plan_version = toba::db()->quote($id_plan_version);
		if ($where) {
			$where = ' and '.$where.' ';
		}
		$sql = "SELECT 	sga_elementos.elemento,
						sga_elementos.nombre as actividad_nombre, 
						sga_elementos.codigo,
						COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre as actividad_descr,
						sga_elementos.entidad,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre 
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos,
				  		sga_elementos_revision,
				  		sga_elementos_plan
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo
				 AND	sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 AND 	sga_elementos_revision.elemento			= sga_elementos.elemento 
				 AND	sga_elementos_plan.elemento_revision	= sga_elementos_revision.elemento_revision
				 AND	sga_elementos_plan.plan_version			= $id_plan_version 
					$where 
				 ORDER BY 
					$order 
				 		;"; 
		return guarani_db::consultar($sql);	
	}

	/**
	 * Obtiene todos los elementos de tipo Actividad para una propuesta dada.
	 *
	 * @param integer	$propuesta	propuesta para la cual se recuperan todas las actividades asociadas.
	 */
	function get_actividades_propuesta($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT 	elemento as valor,
						nombre as descr 
				  FROM 	vw_actividades_plan
				 WHERE 	propuesta = $propuesta
				 ORDER BY nombre;"; 
		return guarani_db::consultar($sql);	
	}
    
	function get_actividades_propuesta_plan($propuesta, $plan = null, $where = '')
	{
		$propuesta = toba::db()->quote($propuesta);
        if ($where) {
            $where = ' AND '.$where;
        }        
		if ($plan) {            
            $where .= ' AND plan = '.$plan;
        }
		$sql = "SELECT 	DISTINCT elemento as valor,
						nombre as descr,
                        codigo as codigo
				  FROM 	vw_actividades_plan
				 WHERE 	propuesta = $propuesta
                        $where
				 ORDER BY nombre;"; 
		return guarani_db::consultar($sql);	
	}

	/**
	 * Obtiene todos los elementos de tipo Actividad según el filtro indicado sobre Propuestas, Planes, 
	 * Responsable Académica y Ubicación donde se ofertan. Se utiliza un Distinct para no traer repetidas
	 * aquellas actividades que se ofertan por más de una Ra o en más de una ubicación.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_actividades_propuesta_plan_con_ra_ubicacion($where)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		// Se lista el nombre generico de la actividad. Y un campo que indica cuantos nombres diferentes tiene.
		// El campo "tiene_nombres_alternativos" es para poder mostrar un boton en el cuadro y ver los diferentes nombres de la actividad en los planes donde se encuentra.
		$sql = "SELECT		vw_actividades_plan.elemento,
							vw_actividades_plan.codigo,
							sga_elementos.nombre,
							vw_actividades_plan.entidad_subtipo_nombre as tipo_actividad,
							SUM(CASE WHEN vw_actividades_plan.nombre <> sga_elementos.nombre THEN 1 ELSE 0 END) as tiene_nombres_alternativos
				FROM		vw_actividades_plan
							JOIN sga_elementos ON vw_actividades_plan.elemento = sga_elementos.elemento
							JOIN sga_elementos_plan ON vw_actividades_plan.elemento_plan = sga_elementos_plan.elemento_plan
							JOIN sga_planes ON vw_actividades_plan.plan = sga_planes.plan
							JOIN sga_planes_versiones ON vw_actividades_plan.plan_version = sga_planes_versiones.plan_version
							JOIN sga_elementos_ra ON vw_actividades_plan.elemento = sga_elementos_ra.elemento
							JOIN sga_propuestas_oferta ON vw_actividades_plan.propuesta = sga_propuestas_oferta.propuesta
				WHERE		true
							$where
				GROUP BY	vw_actividades_plan.elemento,
							vw_actividades_plan.codigo,
							sga_elementos.nombre,
							vw_actividades_plan.entidad_subtipo_nombre
				ORDER BY	sga_elementos.nombre
		";
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Obtiene las actividades (elementos) con sus datos asociados en un plan, cuya propuesta se oferte
	 * en la ubicación dada.
	 * @param string $where filtro sobre las actividades
	 */
	function get_actividades_plan_en_ubicacion($ubicacion, $where)
	{
		$ubicacion = toba::db()->quote($ubicacion);
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT		DISTINCT 
							vw_actividades_plan.elemento,
							vw_actividades_plan.codigo,
							vw_actividades_plan.nombre,
							COALESCE('(' || vw_actividades_plan.codigo || ') ', '') || vw_actividades_plan.nombre as actividad_descr,
							vw_actividades_plan.entidad_subtipo_nombre as tipo_actividad
				FROM		vw_actividades_plan
				WHERE		vw_actividades_plan.propuesta IN 
															(	SELECT	sga_propuestas_oferta.propuesta 
																FROM	sga_propuestas_oferta 
																WHERE	sga_propuestas_oferta.ubicacion = $ubicacion)
							$where
				ORDER BY	vw_actividades_plan.nombre
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna todas las actividades que requieran cursada, con sus datos y cantidad de comisiones asociadas, según el filtro
	 * dado. Los filtros pueden aplicarse sobre las actividades a recuperar, o sobre las comisiones de dichas actividades.
	 * 
	 * @param str	$filtro_comisiones	condiciones que deben cumplir las comisiones que se cuentan para cada actividad 
	 * que se recupera. Pueden restringirse los atributos de comisiones y períodos lectivos.
	 * @param str	$filtro_actividades condiciones que deben cumplir las actividades que se recuperan. Pueden imponerse
	 * restricciones sobre los atributos de las Actividades, Propuestas que las contienen, Planes en que se incluyen dichas
	 * propuestas, Responsables Académicas que ofrecen las Propuestas que contienen las Actividades, Ubicaciones en las que 
	 * se ofrecen dichas Propuestas, etc.
	 */
	function get_actividades_propuestas_con_comisiones($filtro_comisiones='', $filtro_actividades='', $order_by='')
	{
		if ($filtro_actividades) {
			$filtro_actividades = ' AND '. $filtro_actividades;
		}
		if ($filtro_comisiones) {
			$filtro_comisiones = ' AND ' . $filtro_comisiones;
		}
		if (!$order_by) {
			$order_by = 'vw_actividades.codigo';
		}
		
		$sql = "SELECT		DISTINCT
							vw_actividades.elemento,
							vw_actividades.codigo,
							vw_actividades.nombre,
							(SELECT	COUNT(*)
							 FROM	sga_elementos_plan
							 WHERE	sga_elementos_plan.elemento_revision = vw_actividades.elemento_revision AND
									sga_elementos_plan.nombre <> vw_actividades.nombre) as tiene_nombres_alternativos,
							vw_actividades.entidad_subtipo_nombre as tipo_actividad,
							vw_actividades.entidad_subtipo,
							(SELECT	count(sga_comisiones.comision)
						 	 FROM	sga_comisiones, 
									sga_periodos_lectivos,
									sga_periodos
						 	 WHERE	sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
									sga_comisiones.elemento = vw_actividades.elemento AND
									sga_periodos_lectivos.periodo = sga_periodos.periodo
									$filtro_comisiones
							) as cantidad_comisiones,
							vw_actividades.nombre AS actividad_nombre
				FROM		vw_actividades,
							sga_elementos_ra,
							sga_elementos_plan,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas,
							sga_propuestas_oferta
				WHERE		vw_actividades.elemento = sga_elementos_ra.elemento
				AND			vw_actividades.elemento_revision = sga_elementos_plan.elemento_revision
				AND			sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				AND			sga_planes_versiones.plan = sga_planes.plan
				AND			sga_planes.propuesta = sga_propuestas.propuesta
				AND			sga_propuestas.propuesta = sga_propuestas_oferta.propuesta
				AND 		sga_elementos_plan.requiere_cursada = '".comunes::si."'
							$filtro_actividades
				ORDER BY 	$order_by
				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_actividades_propuestas_con_comisiones_gen($filtro_comisiones='', $filtro_actividades='')
	{
		if ($filtro_actividades) {
			$filtro_actividades = ' AND '. $filtro_actividades;
		}
		if ($filtro_comisiones) {
			$filtro_comisiones = ' AND ' . $filtro_comisiones; 
		}
	
		$sql = "SELECT		DISTINCT
							vw_actividades.elemento,
							vw_actividades.codigo,
							vw_actividades.nombre,
							vw_actividades.entidad_subtipo_nombre as tipo_actividad,
							(SELECT	count(sga_comisiones.comision) 
						 	 FROM	sga_comisiones, 
									sga_periodos_lectivos
						 	 WHERE	sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
									AND sga_comisiones.elemento = vw_actividades.elemento
									$filtro_comisiones
							) as cantidad_comisiones,
							sga_planes.plan,
							sga_propuestas.propuesta
				FROM		vw_actividades,
							sga_elementos_plan,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas,
							sga_propuestas_ra,
							sga_propuestas_oferta
				WHERE		vw_actividades.elemento_revision = sga_elementos_plan.elemento_revision AND
							sga_elementos_plan.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas.propuesta AND
							sga_propuestas.propuesta = sga_propuestas_oferta.propuesta AND
							sga_propuestas_ra.propuesta = sga_propuestas_oferta.propuesta
				$filtro_actividades
				ORDER BY 	vw_actividades.codigo			
				;"; 
		
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Recupera el listado de Actividades que tienen alguna Comisión creada en el Año Académico, 
	 * según el filtro dado.
	 *
	 * @param integer	$anio_academico Año académico para el cual se desean recuperar las actividades con comisiones.
	 * @param str		$where filtro sobre el nombre o código de las Actividades a recuperar para un combo editable
	 * o períodos lectivos.
	 */
	function get_actividades_con_comision($filtro = '', $anio_academico, $propuesta = null)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		// Filtro por nombre o codigo de la actividad.
		$where = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$where = ' AND ('.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.codigo::varchar)', $filtro);
			$where .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.nombre::varchar)', $filtro).')';
		}
		// Solo actividades de la propuesta.
		$where_propuesta = '';
		if ($propuesta) {
			$where_propuesta = " AND vw_actividades.elemento IN (SELECT DISTINCT elemento FROM vw_actividades_plan WHERE propuesta = $propuesta) ";
		}
		$sql = "SELECT		vw_actividades.elemento,
							vw_actividades.codigo,
							vw_actividades.nombre,
							'(' || COALESCE(vw_actividades.codigo, 'Sin código') || ') ' || vw_actividades.nombre as actividad_descr
				FROM		vw_actividades
							JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
				WHERE		vw_periodos_lectivos.anio_academico = $anio_academico
							$where
							$where_propuesta
				ORDER BY	vw_actividades.nombre
		";
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Recupera el listado de Actividades que tienen alguna Comisión en la Ubicación y período lectivo seleccionados.
	 * El dato provisto como "valor" es el id del elemento, y como "descr" se concatena el código de la actividad junto con el nombre de la misma.
	 *
	 * @param integer	$ubicacion Ubicación en la cual se desean recuperar las comisiones.
	 * @param integer	$periodo_lectivo Período lectivo en el cual se desean recuperar las comisiones.
	 */
	function get_actividades_con_comision_por_periodo_lectivo($ubicacion, $periodo_lectivo)
	{
		$ubicacion = toba::db()->quote($ubicacion);
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);

		$sql = "SELECT DISTINCT	
						sga_comisiones.elemento		AS valor,
						'(' || COALESCE(vw_actividades.codigo, 'Sin código') || ') ' || vw_actividades.nombre as descr,
						vw_actividades.nombre
				FROM	vw_actividades
						JOIN sga_comisiones ON vw_actividades.elemento = sga_comisiones.elemento
				WHERE	sga_comisiones.periodo_lectivo = $periodo_lectivo AND
						sga_comisiones.ubicacion = $ubicacion
				ORDER BY vw_actividades.nombre;";
		
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Retorna todas las actividades, con sus datos y cantidad de mesas asociadas en el turno dado como parámetro, según el 
	 * filtro dado. Los filtros pueden aplicarse sobre las actividades a recuperar, o sobre las mesas de dichas actividades.
	 * 
	 * @param str	$filtro_mesas	condiciones que deben cumplir las mesas que se cuentan para cada actividad 
	 * que se recupera. Pueden restringirse los atributos de mesas y de llamados y turno de la mesa.
	 * @param str	$filtro_actividades condiciones que deben cumplir las actividades que se recuperan. Pueden imponerse
	 * restricciones sobre los atributos de las Actividades, Propuestas que las contienen, Planes en que se incluyen dichas
	 * propuestas, Responsables Académicas que ofrecen las Propuestas que contienen las Actividades, Ubicaciones en las que 
	 * se ofrecen dichas Propuestas, etc.
	 */
	function get_actividades_propuestas_con_mesas_turno($turno_examen=null, $filtro_mesas='', $filtro_actividades='')
	{
		$where_mesas = '';
		if (!is_null($turno_examen)) {
			$turno_examen = toba::db()->quote($turno_examen);
			$where_mesas .= " AND sga_llamados_turno.turno_examen = $turno_examen ";
		}
		if ($filtro_mesas) {
			$where_mesas .= ' AND ' . $filtro_mesas; 
		}
		if ($filtro_actividades) {
			$filtro_actividades = ' AND '. $filtro_actividades;
		}
	
		$sql = "SELECT		DISTINCT
							vw_actividades.elemento,
							vw_actividades.codigo,
							vw_actividades.nombre,
							(SELECT	COUNT(*)
							 FROM	sga_elementos_plan
							 WHERE	sga_elementos_plan.elemento_revision = vw_actividades.elemento_revision AND
									sga_elementos_plan.nombre <> vw_actividades.nombre) as tiene_nombres_alternativos,
							vw_actividades.entidad_subtipo_nombre AS tipo_actividad,
							(SELECT	COUNT(distinct sga_mesas_examen.mesa_examen) 
						 	 FROM	sga_mesas_examen, 
									sga_llamados_mesa,
									sga_llamados_turno
						 	 WHERE	sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
						 	 		sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
									sga_mesas_examen.elemento = vw_actividades.elemento
									$where_mesas
							) AS cantidad_mesas
				FROM		vw_actividades,
							sga_elementos_ra,
							sga_elementos_plan,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas,
							sga_propuestas_oferta
				WHERE		vw_actividades.elemento = sga_elementos_ra.elemento
				AND			vw_actividades.elemento_revision = sga_elementos_plan.elemento_revision
				AND			sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				AND			sga_planes_versiones.plan = sga_planes.plan
				AND			sga_planes.propuesta = sga_propuestas.propuesta
				AND			sga_propuestas.propuesta = sga_propuestas_oferta.propuesta
				AND			sga_elementos_plan.requiere_examen = '".comunes::si."'
							$filtro_actividades
				ORDER BY 	vw_actividades.codigo
				";
		
		return guarani_db::consultar($sql);	
	}
	
	function get_actividades_y_materias_genericas_certificados($where='')
	{
		return $this->get_actividades_y_materias_genericas_condiciones($where);
	}
	
	function get_actividades_y_materias_genericas_condiciones($where='')
	{
		if ($where) $where = ' and '.$where.' ';
		
		$sql = "SELECT 	sga_elementos.elemento 					as 	id,
						sga_elementos_plan.nombre 				as 	nombre,
						sga_elementos.codigo 					as	codigo,
						sga_elementos.entidad 					as	entidad,
						sga_g3entidades_subtipos.icono 			as	icono,
						sga_g3entidades_subtipos.entidad_tipo 	as	entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as	entidad_subtipo,
						sga_g3entidades_subtipos.nombre 		as	subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos,
				  		sga_elementos_revision,
						sga_elementos_plan,
				  		sga_planes_versiones
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo
				 AND	(sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 OR		(sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_modulo."
				 AND	sga_elementos.entidad_subtipo 			= ".elemento::subtipo_materia_generica."))
				 AND 	sga_elementos.elemento = sga_elementos_revision.elemento
				 AND	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				 AND    sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				 		".$where."
				 ORDER BY nombre";
		
		return guarani_db::consultar($sql);
	}

	/**
  	*/
	function get_actividades_y_materias_genericas($where='')
	{
		if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento as 						id,
						sga_elementos.nombre as 						nombre, 
						sga_elementos.codigo as							codigo,
						sga_elementos.entidad as						entidad,
						sga_g3entidades_subtipos.icono as				icono,
						sga_g3entidades_subtipos.entidad_tipo as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo
				 AND	( sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_actividad."
				 OR		(sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_modulo."
				 AND	sga_elementos.entidad_subtipo 			= ". elemento::subtipo_materia_generica."))	
				 ".$where."
				 ORDER BY nombre";
		return guarani_db::consultar($sql);	
	}

  /**
  */
	function get_materias($where='')
	{
		if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento as 						id,
						sga_elementos.nombre as 						nombre, 
						sga_elementos.codigo as							codigo,
						sga_elementos.entidad as						entidad,
						sga_g3entidades_subtipos.icono as				icono,
						sga_g3entidades_subtipos.entidad_tipo as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos 
				 WHERE 	sga_elementos.entidad_subtipo 				= sga_g3entidades_subtipos.entidad_subtipo
				 AND	sga_g3entidades_subtipos.entidad_tipo 		= ".elemento::tipo_actividad."
				 AND	sga_g3entidades_subtipos.entidad_subtipo 	= ". elemento::subtipo_materia."
				 ".$where."
				 ORDER BY nombre"; 
		return guarani_db::consultar($sql);	
	}

	function get_materias_genericas($where='')
	{
		if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento as 						id,
						sga_elementos.elemento,
						sga_elementos.nombre as 						nombre, 
						sga_elementos.codigo as							codigo,
						sga_elementos.entidad as						entidad,
						sga_g3entidades_subtipos.icono as				icono,
						sga_elementos_estados.descripcion as			estado_desc,
						sga_g3entidades_subtipos.entidad_tipo as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_elementos_estados,
				  		sga_g3entidades_subtipos 
				 WHERE 	sga_elementos.entidad_subtipo 				= sga_g3entidades_subtipos.entidad_subtipo
				 AND	sga_elementos.estado 						= sga_elementos_estados.estado
				 AND	(sga_g3entidades_subtipos.entidad_tipo 		= ".elemento::tipo_modulo."
				 AND	sga_g3entidades_subtipos.entidad_subtipo 	= ". elemento::subtipo_materia_generica.")
				 ".$where."
				 ORDER BY nombre";
		return guarani_db::consultar($sql);	
	}
	
	function get_materias_incluyendo_genericas()
	{
		if ($where) $where = ' and '.$where.' ';
		$sql = "SELECT 	sga_elementos.elemento 						as 		id,
						sga_elementos.nombre 						as		nombre,
						sga_elementos.codigo 						as		codigos,
						sga_elementos.entidad 						as		entidad,						
						sga_g3entidades_subtipos.icono 				as		icono,
						sga_g3entidades_subtipos.entidad_tipo 		as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo 	as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre 			as		subtipo_nombre
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos 
				 WHERE 	sga_elementos.entidad_subtipo 				= sga_g3entidades_subtipos.entidad_subtipo
				 AND	((sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 AND	sga_g3entidades_subtipos.entidad_subtipo 	= ". elemento::subtipo_materia.")
				 OR 	(sga_g3entidades_subtipos.entidad_tipo 		= ".elemento::tipo_modulo."
				 AND	sga_g3entidades_subtipos.entidad_subtipo 	= ". elemento::subtipo_materia_generica."))
				 ORDER BY nombre"; 
		return guarani_db::consultar($sql);	
	}
	
	function get_modulos_certificados($where)
	{
		return $this->get_modulos_condiciones('CERTIFICADOS', $where);
	}
	
	function get_modulos_correlativas($where)
	{
		return $this->get_modulos_condiciones('CORRELATIVAS', $where);
	}
	
	/**
	 * Tipo de Condición 'CORRELATIVAS', 'CERTIFICADOS'
	 *  
	 * @param type $tipo_condicion
	 * @param string $where
	 * @return type 
	 */
	function get_modulos_condiciones($tipo_condicion, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		switch ($tipo_condicion){
			case 'CERTIFICADOS':
			$where .= "	AND sga_g3entidades_subtipos.para_condiciones = '".comunes::si."'
						AND sga_g3entidades_subtipos.entidad_subtipo NOT IN (".g3entidad::subtipo_materia_generica.", ".g3entidad::subtipo_modulo_correlativas.")";
			break;
			case 'CORRELATIVAS':
			$where .= "	AND sga_g3entidades_subtipos.para_condiciones = '".comunes::si."'
						AND sga_g3entidades_subtipos.entidad_subtipo NOT IN (".g3entidad::subtipo_materia_generica.", ".g3entidad::subtipo_raiz.", ".g3entidad::subtipo_modulo_certificados.")";
			break;
		}
		$sql = "SELECT 	sga_elementos.elemento as id,
						sga_elementos_plan.nombre,
						sga_elementos.codigo,
						sga_elementos.entidad,
						sga_elementos.entidad_subtipo as tipo,
						sga_g3entidades_subtipos.icono,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo,
						sga_g3entidades_subtipos.nombre as subtipo_nombre,
						sga_planes_versiones.plan_version,
						sga_planes_versiones.version,
						sga_planes_versiones.nombre as version_nombre,
						sga_planes.plan,
						sga_planes.nombre as plan_nombre,
						sga_planes.codigo as plan_codigo,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.codigo as propuesta_codigo
				FROM 	sga_elementos
						JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
						JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
						JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_planes.propuesta
				WHERE	sga_g3entidades_subtipos.entidad_tipo = ".g3entidad::tipo_modulo."
						$where
		";
		return guarani_db::consultar($sql);	
	}

	function get_modulos($where='',$incluir_modulos_raiz=false, $incluir_rtfs = false)
	{
		if ($where) $where = ' and '.$where.' ';

		$condicion_de_tipos = "sga_g3entidades_subtipos.entidad_subtipo = ".elemento::subtipo_agrupador_modulos;
		if ($incluir_modulos_raiz) {
			$condicion_de_tipos = "( $condicion_de_tipos OR sga_g3entidades_subtipos.entidad_subtipo = ".elemento::subtipo_raiz." )" ;
		}
		if ($incluir_rtfs) {
			$condicion_de_tipos = "( $condicion_de_tipos OR sga_g3entidades_subtipos.entidad_subtipo = ".elemento::subtipo_modulo_rtf . " )" ;
		}

		$sql = "SELECT 	sga_elementos.elemento 						as 		id,
						sga_elementos_plan.nombre 					as		nombre,
						sga_elementos.codigo 						as		codigo,
						sga_elementos.entidad 						as		entidad,
						sga_elementos.entidad_subtipo 				as 		tipo,
						sga_g3entidades_subtipos.icono 				as		icono,
						sga_g3entidades_subtipos.entidad_tipo 		as		entidad_tipo,
						sga_g3entidades_subtipos.entidad_subtipo 	as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre 			as		subtipo_nombre,
						sga_planes_versiones.plan_version			as		plan_version,
						sga_planes_versiones.version				as		version,
						sga_planes_versiones.nombre					as		version_nombre,
						sga_planes.plan								as		plan,
						sga_planes.nombre							as		plan_nombre,
						sga_planes.codigo							as		plan_codigo,
						sga_propuestas.propuesta					as		propuesta,
						sga_propuestas.nombre						as		propuesta_nombre,
						sga_propuestas.codigo						as		propuesta_codigo
				 FROM 	sga_elementos,
				  		sga_g3entidades_subtipos,
				  		sga_elementos_revision,
						sga_elementos_plan,
				  		sga_planes_versiones,
				  		sga_planes,
				  		sga_propuestas
				 WHERE 	sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				 AND	$condicion_de_tipos
				 AND 	sga_elementos.elemento = sga_elementos_revision.elemento
				 AND	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				 AND    sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				 AND    sga_planes_versiones.plan = sga_planes.plan
				 AND	sga_planes.propuesta = sga_propuestas.propuesta
				 		$where
						;";
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Obtiene el catálogo de módulos RTF.
	 * @param string $where filtro sobre los módulos a retornar.
	 * @return array arreglo de módulos rtf con sus atributos asociados.
	 */
	function get_modulos_rtf($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT	sga_modulos_rtf.codigo AS id,
						sga_modulos_rtf.nombre,
						sga_modulos_rtf.codigo,
						sga_modulos_rtf.nombre_abreviado,
						sga_modulos_rtf.creditos,
						sga_g3entidades_subtipos.entidad_subtipo,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_g3entidades_subtipos.icono,
						sga_g3entidades_subtipos.nombre AS subtipo_nombre
				FROM	sga_modulos_rtf,
						sga_g3entidades_subtipos
				WHERE	sga_g3entidades_subtipos.entidad_subtipo = "  . elemento::subtipo_modulo_rtf . "
				 		$where
						;";
		return guarani_db::consultar($sql);	
	}

	/**
	 * Obtiene los datos de un módulo RTF según el código dado.
	 * @param str $modulo_rtf código del módulo rtf para el cual se quieren obtener los datos.
	 */
	function get_datos_modulo_rtf($modulo_rtf)
	{
		$modulo_rtf = toba::db()->quote($modulo_rtf);
		$filtro = "sga_modulos_rtf.codigo = $modulo_rtf";
		$datos = $this->get_modulos_rtf($filtro);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	function get_actividades_tipos()
	{
		$sql = "SELECT 	sga_g3entidades_subtipos.entidad_subtipo as			id,
						sga_g3entidades_subtipos.nombre as 				nombre   
				  FROM 	sga_g3entidades_subtipos 
				 WHERE 	sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_actividad."";
		return guarani_db::consultar($sql);	
	}

	//Son los tipos de actividades para el ABM de actividades
	function get_actividades_tipos_abm()
	{
		 $sql = "SELECT 	sga_g3entidades_subtipos.entidad_subtipo 	as id,
     						sga_g3entidades_subtipos.nombre 			as nombre
    			 FROM 		sga_g3entidades_subtipos
    			 WHERE  	sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_actividad."
    			 ORDER BY	sga_g3entidades_subtipos.nombre"; 
		return guarani_db::consultar($sql);	
	}
	
	function get_actividades_tipos_abm_adminplan()
	{
		$sql = "SELECT  sga_g3entidades_subtipos.entidad_tipo 		as tipo,
						sga_g3entidades_subtipos.entidad_subtipo 	as subtipo,
      					sga_g3entidades_subtipos.nombre 			as nombre   
      			FROM  	sga_g3entidades_subtipos
     			WHERE  	sga_g3entidades_subtipos.entidad_tipo 		= ".elemento::tipo_actividad." 
     			OR  	sga_g3entidades_subtipos.entidad_subtipo 	= ".elemento::subtipo_materia_generica."
       					;"; 
		return guarani_db::consultar($sql);	
	}
	
	/**
	*/
	function get_info_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
	    $sql = "SELECT 	sga_elementos.elemento 							as id,
	       			   	sga_elementos.nombre 							as nombre,
  						sga_elementos.codigo 							as codigo,
  						sga_elementos.estado 							as estado,
  						sga_elementos.entidad 							as entidad,
  						sga_elementos.compartible						as compartible, 	       			   	
	       			   	sga_g3entidades_subtipos.entidad_tipo 			as entidad_tipo,
  						sga_g3entidades_subtipos.entidad_subtipo		as entidad_subtipo,
  						sga_g3entidades_subtipos.nombre 				as entidad_subtipo_nombre,
	       			   	sga_g3entidades_subtipos.php_clase_gui			as php_clase_gui,
	       			   	sga_g3entidades_subtipos.php_clase_negocio		as php_clase_negocio,
	       			   	sga_g3entidades_subtipos.php_clase_edicion		as php_clase_edicion,
	       			   	sga_g3entidades_subtipos.icono					as icono,
	       			   	sga_g3entidades_subtipos.modificable			as modificable,
	       			   	sga_g3entidades_subtipos.revisionable			as revisionable,
	       			   	sga_elementos.codigo || ' - ' || sga_elementos.nombre as descr
	            FROM 	sga_elementos,
				 		sga_g3entidades_subtipos 
				WHERE 	sga_elementos.entidad_subtipo 	= sga_g3entidades_subtipos.entidad_subtipo
	            AND		sga_elementos.elemento 			= $elemento 
	            		;";
    	return guarani_db::consultar_fila($sql);	
	}
	/**
	 *  Retorna en un array la informacion de un elemento en un determinado plan
	 */
	function get_info_elemento_plan($plan_version, $elemento)
	{
		$plan_version = toba::db()->quote($plan_version);
		$elemento = toba::db()->quote($elemento);
	    $sql = "SELECT 	sga_elementos.elemento 						as id,
	   					sga_elementos_plan.nombre 					as nombre,
	   					sga_elementos_plan.nombre_abreviado 		as nombre_abreviado,
						sga_elementos_plan.anio_de_cursada,
						sga_elementos_plan.requiere_cursada,
	   					sga_g3entidades_subtipos.php_clase_gui 		as php_clase_gui,
	   					sga_g3entidades_subtipos.entidad_tipo 		as entidad_tipo,
						sga_elementos.entidad_subtipo 				as entidad_subtipo,
						sga_elementos.codigo 						as codigo,
						sga_elementos.entidad 						as entidad,
						sga_elementos.estado 						as estado,
						sga_elementos_revision.elemento_revision 	as elemento_revision,
						sga_elementos_revision.regla				as regla,
						sga_elementos_revision.parametros 			as parametros,
						sga_elementos_revision.origen_creditos 		as origen_creditos,
	       			   	'(' || sga_elementos.codigo || ' ) ' || sga_elementos.nombre as descr
				FROM 	sga_elementos
 						JOIN sga_g3entidades_subtipos ON sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
        				JOIN sga_elementos_revision ON sga_elementos.elemento = sga_elementos_revision.elemento
						JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				WHERE	sga_elementos.elemento = $elemento AND
						sga_elementos_plan.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los créditos que otorga un elemento en un plan-versión.
	 *
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return float
	 */
	function get_creditos_elemento_plan_version($elemento, $plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_plan.creditos
				FROM	sga_elementos_revision,
						sga_elementos_plan
				WHERE	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision AND
						sga_elementos_revision.elemento = $elemento AND
						sga_elementos_plan.plan_version = $plan_version
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}

	/**
	 * Retorna el origen de créditos para un elemento en un plan-versión.
	 * 
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return char
	 */
	function get_origen_creditos_elemento_plan_version($elemento, $plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_revision.origen_creditos
				FROM	sga_elementos_revision,
						sga_elementos_plan
				WHERE	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision AND
						sga_elementos_revision.elemento = $elemento AND
						sga_elementos_plan.plan_version = $plan_version
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['origen_creditos'];
	}

	/**
	 * Retorna los créditos mínimos y máximos para un elemento en un plan-versión dados.
	 *
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return float
	 */
	function get_creditos_min_max_elemento_plan_version($elemento, $plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_plan.creditos_max, 
						sga_elementos_plan.creditos_min
				FROM	sga_elementos_plan,
						sga_elementos_revision
				WHERE	sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_revision.elemento = $elemento AND
						sga_elementos_plan.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los créditos mínimos y máximos para todos los elementos de un plan-versión dado.
	 *
	 * @param integer $plan_version
	 * @return array
	 */
	function get_creditos_min_max_elementos_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_revision.elemento,
						sga_elementos_plan.creditos_max, 
						sga_elementos_plan.creditos_min
				FROM	sga_elementos_plan,
						sga_elementos_revision
				WHERE	sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_plan.plan_version = $plan_version
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la escala de notas de equivalencia para un elemento en un plan-versión dados, siempre que esté ACTIVA.
	 *
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return float
	 */
	function get_escala_nota_equivalencia_elemento_plan_version($elemento, $plan_version)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_elementos_plan.escala_nota_equivalencia
				FROM	sga_elementos_plan
						JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
						JOIN sga_escalas_notas ON sga_elementos_plan.escala_nota_equivalencia = sga_escalas_notas.escala_nota
				WHERE	sga_elementos_revision.elemento = $elemento AND
						sga_elementos_plan.plan_version = $plan_version AND
						sga_escalas_notas.estado = '".comunes::estado_activo."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return empty($rs) ? null : $rs['escala_nota_equivalencia'];
	}

	/**
	 * Retorna la escala de notas de equivalencia para un grupo de elementos en un plan-versión dados.
	 *
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return array
	 */
	function get_escala_nota_equivalencia_elementos_plan_version($plan_version, $filtro='TRUE')
	{
		$plan_version = toba::db()->quote($plan_version);
		
		$sql = "SELECT	sga_elementos_revision.elemento,
						sga_elementos_plan.escala_nota_equivalencia as escala_nota,
						sga_escalas_notas.nombre as escala_nota_nombre,
						sga_escalas_notas.descripcion as escala_nota_desc,
						sga_elementos.nombre as actividad_nombre, 
						COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre as actividad_descr
				FROM	sga_elementos_plan,
						sga_elementos_revision,
						sga_elementos,
						sga_escalas_notas
				WHERE	sga_elementos_plan.plan_version = $plan_version AND
						sga_elementos_plan.escala_nota_equivalencia = sga_escalas_notas.escala_nota AND
						sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_revision.elemento = sga_elementos.elemento AND
						$filtro
		";
		$rs = guarani_db::consultar($sql);
		return $rs;
	}
	
	function get_codigo_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT 	sga_elementos.codigo
  				FROM 	sga_elementos
				WHERE 	sga_elementos.elemento = $elemento ";
		$datos = guarani_db::consultar_fila($sql);
		return $datos['codigo'];
	}	
	
	//-----------------------------------------------------------
	//-----------------------------------------------------------

	function get_subtipos()
	{
		$sql = "SELECT 	*
				  FROM 	sga_g3entidades_subtipos
				 WHERE 	sga_g3entidades_subtipos.es_elemento = 'S' "; 
		return guarani_db::consultar($sql);	
	}

	function get_subtipos_reglas_composicion($subtipo_padre=null)
	{
		$filtro = 'true';
		if (!empty($subtipo_padre)) {
			$subtipo_padre = toba::db()->quote($subtipo_padre);
			$filtro = "sga_elementos_reglas_composicion.entidad_subtipo_padre = $subtipo_padre"; 
		}
		$sql = "SELECT  sga_elementos_reglas_composicion.entidad_subtipo_padre,
      					sga_elementos_reglas_composicion.entidad_subtipo_hijo
				  FROM 	sga_elementos_reglas_composicion
				  WHERE $filtro
				  ORDER BY sga_elementos_reglas_composicion.entidad_subtipo_padre"; 
		return guarani_db::consultar($sql);	
	}

	function get_ultima_revision_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT 	MAX(sga_elementos_revision.elemento_revision) as elemento_revision
	    		FROM 	sga_elementos_revision
	    		WHERE 	sga_elementos_revision.elemento = $elemento ";
		$datos = guarani_db::consultar_fila($sql);
		if($datos) {
			return $datos['elemento_revision'];
		}
	}

	function get_cantidad_revisiones_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT 	COUNT(*) as cantidad
	    		FROM 	sga_elementos_revision
	    		WHERE 	sga_elementos_revision.elemento = $elemento ";
		$datos = guarani_db::consultar_fila($sql);
		if($datos) {
			return $datos['cantidad'];
		}
	}

	function get_cantidad_planes_revision($revision)
	{
		$revision = toba::db()->quote($revision);
		$sql = "SELECT 	COUNT(*) as sga_elemento_plan.cantidad
	    		FROM 	sga_elemento_plan
	    		WHERE 	sga_elemento_plan.elemento_revision = $revision ";
		$datos = guarani_db::consultar_fila($sql);
		if($datos) {
			return $datos['cantidad'];
		}
	}
	
	/**
        Obtiene los datos de un Elemento
    */
	function get_datos_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.nombre as nombre_actividad,
						sga_elementos.nombre_abreviado,
						COALESCE('(' || sga_elementos.codigo || ') ', '') || sga_elementos.nombre AS elemento_descr,
						sga_elementos.entidad,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_elementos.entidad_subtipo,
						sga_elementos.codigo as legajo_actividad,
						sga_elementos.estado, 
						sga_elementos_estados.descripcion AS estado_descr						
  				FROM 	sga_elementos,
  						sga_g3entidades_subtipos,
  						sga_elementos_estados 
  				WHERE 	sga_elementos.elemento = $elemento
  				AND 	sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
  				AND		sga_elementos.estado = sga_elementos_estados.estado
						;";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de un elemento-revision.
	 */
	function get_datos_basicos_elemento_revision($elemento_revision, $where='')
	{
		$elemento_revision = toba::db()->quote($elemento_revision);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	vw_elementos.nombre,
						vw_elementos.entidad_subtipo_nombre,
						vw_elementos.codigo,
						sga_elementos.estado,
						vw_elementos.regla,
						sga_reglas.nombre AS regla_nombre,
						vw_elementos.parametros,
						sga_elementos_estados.descripcion as estado_desc					
  				FROM 	vw_elementos
  						LEFT JOIN sga_reglas ON vw_elementos.regla = sga_reglas.regla,
  						sga_elementos,
  						sga_elementos_estados
  				WHERE 	vw_elementos.elemento_revision = $elemento_revision AND
  						vw_elementos.elemento = sga_elementos.elemento AND
  						sga_elementos.estado = sga_elementos_estados.estado
						$where
		";
		return guarani_db::consultar_fila($sql, null, false);
	}
	
		/**
	 * Obtiene los datos de un elemento-revision.
	 */
	function get_datos_basicos_elemento_plan($elemento_plan)
	{
		$elemento_plan = toba::db()->quote($elemento_plan);
		
		$sql = "SELECT	vw_elementos_plan.elemento,
						vw_elementos_plan.nombre,
						vw_elementos_plan.codigo,
						vw_elementos_plan.entidad_subtipo_nombre,
						vw_elementos_plan.plan,
						vw_elementos_plan.plan_codigo,
						vw_elementos_plan.plan_nombre,
						vw_elementos_plan.plan_version
  				FROM 	vw_elementos_plan
  				WHERE 	vw_elementos_plan.elemento_plan = $elemento_plan
						;";

		return guarani_db::consultar_fila($sql);
	}
	
	// -- Dejo el siguiente método comentado mientras el filtro de actividad quede como combo fijo
	/*
	function get_actividad_descripcion ($id)
	{
		$datos = $this->get_lista_actividades(array('elemento' => $id));
		if (!empty($datos)){
			return $datos[0]['descr'];
		}
		return '';
	}
	*/
	
	/**
	 * Obtiene la lista de actividades para filtro.
	 */
	function get_lista_actividades ($filtro=null) 
	{
		// -- Dejo el siguiente trozo de código comentado mientras el filtro de actividad quede como combo fijo
		/*
		$where = array();
		if (isset($filtro['elemento'])) $where[] = " sga_elementos.elemento = " . quote($filtro['elemento']);
		if (isset($filtro['nombre'])) $where[] = " sga_elementos.nombre ILIKE " . quote("%{$filtro['nombre']}%");
		*/
		
		$sql = "SELECT 		elemento,
  							codigo,
  							nombre,
  							nombre || ' (' || codigo || ')'as codigo_nombre
  				FROM		vw_actividades
  				ORDER BY	nombre
				";
		
		/*
		if (! empty($where)){
			$sql = sql_concatenar_where($sql, $where);
		}
		*/
				
		return guarani_db::consultar($sql);
		
	}
	
	/**
     *   Retorna el legajo de una actividad.
     */
	function get_actividad_legajo($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.codigo as legajo_actividad
  				FROM 	sga_elementos
  				WHERE 	sga_elementos.elemento = $elemento
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna el nombre de una actividad.
    */
	function get_elemento_nombre($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.nombre,
						sga_elementos.codigo
  				FROM 	sga_elementos
  				WHERE 	sga_elementos.elemento = $elemento
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna el nombre genérico de una actividad.
    */
	function get_actividad_nombre($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.nombre as nombre_actividad,
						COALESCE (sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre AS codigo_y_nombre
  				FROM 	sga_elementos
  				WHERE 	sga_elementos.elemento = $elemento
				;";
		return guarani_db::consultar($sql);
	}

	function get_actividad_nombres_alternativos($elemento, $nombre_por_plan = true)
	{
		$elemento = toba::db()->quote($elemento);
		if ($nombre_por_plan) {
			// Se listan las columnas del nombre de la propuesta y el plan de estudios.
			$columnas_propuesta_plan = ",sga_propuestas.nombre as propuesta, sga_planes.nombre as plan";
			$order_by = " ORDER BY sga_propuestas.nombre, sga_planes.nombre, sga_elementos_plan.nombre ";							
		} else {
			$columnas_propuesta_plan = "";
			$order_by = " ORDER BY sga_elementos_plan.nombre";							
		}										
		
		$sql = "SELECT	DISTINCT 
						sga_elementos_plan.nombre as nombre
						$columnas_propuesta_plan
  				FROM 	sga_elementos_plan
				JOIN	sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				JOIN	sga_elementos ON sga_elementos.elemento = sga_elementos_revision.elemento
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
				JOIN	sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				JOIN	sga_propuestas ON sga_propuestas.propuesta = sga_planes.propuesta
  				WHERE 	sga_elementos_revision.elemento = $elemento
				  AND	sga_elementos_plan.nombre <> sga_elementos.nombre
				$order_by	
		";

		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_planes'));
		return guarani_db::consultar($sql, null, false);
	}

	/**
        Retorna el nombre de una actividad.
    */
	function get_actividades_descr($where)
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT	sga_elementos.elemento,
						sga_elementos.nombre as elemento_nombre,
						'(' || COALESCE(sga_elementos.codigo, 'Sin código') || ') ' || sga_elementos.nombre as actividad_descr
  				FROM 	sga_elementos
  				$where
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna los nombres y códigos de Actividades, según el filtro dado para la carga de un combo editable.
    */
	function get_nombres_actividades($filtro)
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = '('.$filtro_condicion->get_sql('f_limpiar_acentos(sga_elementos.codigo::varchar)', $filtro);
			$condicion .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(sga_elementos.nombre::varchar)', $filtro).')';
		}
		return $this->get_listado_actividades($condicion);
	}
	
	/**
 	  *  Retorna el código y nombre de el/los elementos dados para la carga externa (masiva).
      *	 @param integer/array	id de elemento o arreglo de ids de los elementos para los cuales se consultan las descr.
    */
	function get_elementos_descr($elemento)
	{
		if (is_array($elemento)) {
			$filtro = guarani_sql::armar_condicion_in($elemento, 'sga_elementos', 'elemento');
			$datos = $this->get_actividades_descr($filtro);
			return $datos;
		} else {
			$elemento = toba::db()->quote($elemento);
			$filtro = "sga_elementos.elemento = $elemento";
			$datos = $this->get_actividades_descr($filtro);
			if (!empty($datos)) {
				return current($datos);
			}
		}
	}
	
	/**
	 * Retorna los tipos asociados a elementos
	 */
	function get_tipos_elementos($where)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 	sga_elementos.elemento,
						sga_g3entidades_tipos.entidad_tipo as elemento_tipo,
						sga_g3entidades_subtipos.entidad_subtipo as elemento_subtipo,
						CASE
	   						WHEN sga_g3entidades_tipos.entidad_tipo = '" . elemento::tipo_actividad . "' 
	   							THEN sga_g3entidades_subtipos.nombre
	   						WHEN sga_g3entidades_tipos.entidad_tipo = '" . elemento::tipo_modulo . "'
	   							THEN sga_g3entidades_tipos.nombre
	   					END as tipo_nombre
  				FROM 	sga_elementos,
  						sga_g3entidades_subtipos,
  						sga_g3entidades_tipos
  				WHERE  	sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
  						sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
  				$where
  				;";
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna las descripciones de los tipos asociados a el/los elementos dados para la carga externa (masiva).
 	  *  Si el elemento dado es un Módulo se obtiene su tipo asociado. Si el elemento dado es una Actividad se obtiene
 	  *  su subtipo asociado.
      *	 @param integer/array	id de elemento o arreglo de ids de los elementos para los cuales se consultan las descr.
    */
	function get_elementos_tipos_descr($elemento)
	{
		if (is_array($elemento)) {
			$filtro = guarani_sql::armar_condicion_in($elemento, 'sga_elementos', 'elemento');
			$datos = $this->get_tipos_elementos($filtro);
			return $datos;
		} else {
			$elemento = toba::db()->quote($elemento);
			$filtro = "sga_elementos.elemento = $elemento";
			$datos = $this->get_tipos_elementos($filtro);
			if (!empty($datos)) {
				return current($datos);
			}
		}
	}
	
	function get_elemento_de_entidad($entidad) 
	{
		$datos_entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($entidad);
		return $datos_entidad['elemento'];
	}	
	
	function get_elementos($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT 	DISTINCT
						sga_elementos.elemento,
						sga_elementos.nombre,
						sga_elementos.codigo,
						'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre AS elemento_descr,
						sga_elementos_atrib.requiere_cursada,
						sga_elementos_atrib.permite_promocion,
						sga_elementos_atrib.escala_nota_cursada_regular,
						sga_elementos_atrib.escala_nota_cursada_promocion,
						vw_elementos_plan.entidad_subtipo_nombre AS subtipo_nombre
				FROM 	vw_elementos_plan,
						sga_elementos_plan,
						sga_elementos_revision,
						sga_elementos,
						sga_elementos_atrib
				WHERE	vw_elementos_plan.elemento_plan			= sga_elementos_plan.elemento_plan
				AND		vw_elementos_plan.elemento_revision 	= sga_elementos_revision.elemento_revision
				AND		sga_elementos_revision.elemento			= sga_elementos.elemento
				AND		sga_elementos_atrib.elemento			= sga_elementos.elemento
						$where
                        ORDER BY
                            sga_elementos.nombre
				";            
		return guarani_db::consultar($sql);
	}

	function get_nombre_modulo($modulo, $con_codigo=false)
	{
		$datos = current($this->get_elemento_nombre($modulo));
		if (! empty($datos)) {
			if (isset($datos['codigo'])) {
				$codigo = '('.$datos['codigo'].') ';
			}
			return $con_codigo ? $codigo . $datos['nombre'] : $datos['nombre'];
		}
	}
	
	
	/**
        Retorna el nombre de una actividad como string
    */
	static function get_nombre_actividad($elemento)
	{
		$datos = toba::consulta_php('co_elementos')->get_actividad_nombre($elemento);
		if (! empty($datos)) {
			return $datos[0]['nombre_actividad'];
		}
	}
	
	
	
	/**
        Retorna el nombre y el código de una actividad como string
    */
	static function get_nombre_y_codigo_actividad($elemento)
	{
		$datos = toba::consulta_php('co_elementos')->get_actividad_nombre($elemento);
		if (! empty($datos)) {
			return $datos[0]['codigo_y_nombre'];
		}
	}

	/**
        
    */
	/**
	 * Retorna el nombre y el código de una actividad/módulo en una versión del plan de estudios.
	 * Si no la encuentra recupera el nombre genérico.
	 * 
	 * @param integer $elemento
	 * @param integer $plan_version
	 * @return array
	 */
	static function get_datos_basicos_elemento_en_plan_version($elemento, $plan_version=null)
	{
		$elemento = toba::db()->quote($elemento);
		if (!is_null($plan_version)) {
			$plan_version = toba::db()->quote($plan_version);
			// Recupera el nombre del elemento en la versión del plan de estudios. Si no la encuentra retorna el dato genérico.
			$sql = "SELECT	COALESCE(sga_elementos_plan.nombre, sga_elementos.nombre) as nombre,
							COALESCE(sga_elementos_plan.nombre_abreviado, sga_elementos.nombre_abreviado) as nombre_abreviado,
							COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || COALESCE(sga_elementos_plan.nombre, sga_elementos.nombre) as codigo_nombre,
							COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || COALESCE(sga_elementos_plan.nombre_abreviado, sga_elementos.nombre_abreviado) as codigo_nombre_abreviado,
							sga_elementos.codigo as codigo
					FROM	sga_elementos,
							sga_elementos_revision,
							sga_elementos_plan
					WHERE	sga_elementos.elemento = sga_elementos_revision.elemento AND
							sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision AND
							sga_elementos.elemento = $elemento AND
							sga_elementos_plan.plan_version = $plan_version
			";
			$datos = guarani_db::consultar_fila($sql);
		} 
		if (empty($datos)) {
			// Recupera el nombre genérico del elemento.
			$sql = "SELECT	sga_elementos.nombre,
							sga_elementos.nombre_abreviado,
							COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre as codigo_nombre,
							COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre_abreviado as codigo_nombre_abreviado,
							sga_elementos.codigo as codigo
					FROM	sga_elementos
					WHERE	sga_elementos.elemento = $elemento
			";
			$datos = guarani_db::consultar_fila($sql);
		}
		return $datos;
	}
	
	//--------- VALIDACIONES -------------------------------------------------

	function validar_codigo_nombre_materia($codigo, $nombre, $elemento=null)
	{
		$codigo = toba::db()->quote($codigo);
		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	COUNT(sga_elementos.elemento) AS cant
				FROM	sga_elementos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_elementos.codigo', $codigo).' AND
						'.guarani_sql::armar_condicion_compara_cadenas('sga_elementos.nombre', $nombre);

		if (!is_null($elemento)) {
			$elemento = toba::db()->quote($elemento);
			$sql .= " AND sga_elementos.elemento <> $elemento ";
		}

		$res = guarani_db::consultar_fila($sql, null, false);
		return ($res['cant'] == 0);
	}

	/**
	 * Obtiene todos los elementos de tipo Actividadde primer año para un conjunto de propuestas dadas.
	 *
	 * @param array	$propuestas	Arreglo de propuestas para filtrar actividades
	 */
	function get_actividades_propuestas_primer_anio($propuestas)
	{		
		$filtro = guarani_sql::armar_condicion_in($propuestas, 'sga_propuestas', 'propuesta');
		
		$sql = "SELECT 
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_planes.plan,
						sga_planes.nombre as plan_nombre,
						sga_planes_versiones.plan_version,
						sga_elementos_plan.elemento_plan,
						sga_elementos_plan.elemento_revision,
						sga_elementos.elemento as actividad,
						sga_elementos.codigo,
						'(' || COALESCE (sga_elementos.codigo, 'Sin código') || ') ' || sga_elementos_plan.nombre as actividad_nombre,
						sga_elementos_plan.nombre,
						sga_elementos_plan.nombre_abreviado,
						sga_elementos.estado,
						(SELECT COUNT(DISTINCT sga_comisiones.comision)
						   FROM sga_comisiones
						   JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						   JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						   JOIN sga_comisiones_propuestas ON sga_comisiones.comision = sga_comisiones_propuestas.comision
						   JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
						   LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision
						  WHERE sga_comisiones.elemento = sga_elementos.elemento 
						    AND sga_comisiones.inscripcion_habilitada = 'S'
						    AND sga_comisiones_cupo.cant_inscriptos < sga_comisiones_cupo.cupo
						    AND COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado) >= CURRENT_DATE    
						    AND sga_comisiones_propuestas.plan = sga_planes.plan
						) as cant_comisiones_disponibles						
				 FROM sga_propuestas
				 JOIN sga_planes ON sga_planes.propuesta = sga_propuestas.propuesta
				 JOIN sga_planes_versiones ON sga_planes_versiones.plan = sga_planes.plan
				 JOIN sga_elementos_plan ON sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				 JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				 JOIN sga_elementos ON sga_elementos.elemento = sga_elementos_revision.elemento
				 JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
				 WHERE sga_planes_versiones.estado = '" . planes::estado_activo_vigente . "' 
				   AND sga_elementos_plan.anio_de_cursada = 1 
				   AND $filtro 
				   AND sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_actividad.";"; 
		
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Se obtienen las actividades y sus comisiones respectivas con inscripcion habilitada, para el alumno y su modalidad, 
	 * considerando ademas el año academico del periodo de inscripcion elegido y la ubicacion seleccionada, chequeando tambien que el alumno ya no esté inscripto en las mismas.
	 * Actividades del plan de estudios de 1er año.
	 * Usado en inscripción automatica a actividades de primer año para ingresantes.
	 *
	 * @alumno	integer 		ID de alumno
	 * @plan_version	integer	ID de version de plan del alumno
	 * @modalidad	integer		Modalidad de Cursada
	 * @anio	integer			Año académico. Para recuperar periodos lectivos de este año academico
	 * @ubicacion	integer		ID de Ubicacion. Para recuperar comisiones en esta ubicación.
	 */
	function get_actividades_con_comisiones_proc_insc_ingresantes($alumno, $plan_version, $modalidad, $anio, $ubicacion)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$modalidad = toba::db()->quote($modalidad);
		$anio = toba::db()->quote($anio);
		$ubicacion = toba::db()->quote($ubicacion);
		
		$sql = "SELECT 
                        sga_elementos.elemento as actividad,
                       '(' || COALESCE (sga_elementos.codigo, 'Sin código') || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_nombre,
                        sga_comisiones.comision as comision,
                        sga_comisiones.nombre as comision_nombre,
						sga_comisiones.periodo_lectivo,
                        sga_periodos.nombre as periodo_lectivo_nombre,
                        sga_comisiones.turno,
                        COALESCE(sga_turnos_cursadas.nombre, 'Sin turno') as turno_nombre
                 FROM sga_planes_versiones
                 JOIN sga_elementos_plan ON sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
                 JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
                 JOIN sga_elementos ON sga_elementos.elemento = sga_elementos_revision.elemento
                 JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
                 JOIN sga_comisiones ON sga_comisiones.elemento = sga_elementos.elemento
                 JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
                 JOIN sga_periodos  ON sga_periodos.periodo = sga_periodos_lectivos.periodo
                 -- Comision definida para el Plan del Alumno 
                 JOIN sga_comisiones_propuestas ON (sga_comisiones_propuestas.comision = sga_comisiones.comision AND sga_comisiones_propuestas.plan = sga_planes_versiones.plan)
                 JOIN sga_comisiones_modalidad ON (sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = $modalidad)
				 LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				 LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision
				 
                 WHERE sga_planes_versiones.plan_version = $plan_version
                   AND sga_elementos_plan.anio_de_cursada = 1
                   AND sga_g3entidades_subtipos.entidad_tipo  = ".elemento::tipo_actividad. "
                   AND sga_comisiones.inscripcion_habilitada = 'S'
                   AND sga_comisiones.ubicacion = $ubicacion 
				   
				   -- Periodos lectivos que aun no iniciaron y que son del año academico.				
                   AND COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado) >= CURRENT_DATE    
                   AND sga_periodos.anio_academico = $anio
                   
				   -- Actividades que el alumno no aprobó 
                   AND sga_elementos.elemento NOT IN (SELECT elemento FROM vw_hist_academica_basica WHERE alumno = $alumno AND resultado = 'A')

                   -- Actividades que el alumno no regularizó (cursada o equivalencia de regularidad vigentes)
                   AND sga_elementos.elemento NOT IN (SELECT elemento FROM vw_regularidades_basica WHERE alumno = $alumno AND resultado = 'A' AND es_vigente = 1)

                   -- Actividades que el alumno no esta inscripto para cursar en periodos lectivos del año academico
                   AND sga_elementos.elemento NOT IN (SELECT elemento 
                                                        FROM vw_insc_cursada as v 
                                                        JOIN sga_comisiones as c ON v.comision = c.comision
                                                        JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo
                                                        JOIN sga_periodos as p ON p.periodo = pl.periodo
                                                       WHERE v.alumno = $alumno  
                                                         AND pl.fecha_inicio_dictado >= CURRENT_DATE
                                                         AND p.anio_academico = $anio) 
               ORDER BY actividad_nombre, comision_nombre;";
		
			return guarani_db::consultar($sql);		
	}	

	function get_anio_de_cursada($elemento, $plan_version, $anio_desde, $anio_hasta)
	{
		$elemento = toba::db()->quote($elemento);
		$plan_version = toba::db()->quote($plan_version);
		
		$where = '';
		if (!empty($anio_desde) && !empty($anio_hasta)) {
			$anio_desde = toba::db()->quote($anio_desde);
			$anio_hasta = toba::db()->quote($anio_hasta);
			$where = "AND sga_elementos_plan.anio_de_cursada BETWEEN $anio_desde AND $anio_hasta";
		}
		
		$sql = "SELECT 	sga_elementos_plan.anio_de_cursada
				FROM 	sga_elementos_plan,
						sga_elementos_revision
				WHERE	sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
						AND sga_elementos_revision.elemento = $elemento
						AND sga_elementos_plan.plan_version = $plan_version
						$where
				";
		$res = guarani_db::consultar_fila($sql);
		return $res['anio_de_cursada'];
	}

	function get_anios_de_cursadas()
	{
		return array(array('valor' => 1,'descr' => 1),
		             array('valor' => 2,'descr' => 2),
		             array('valor' => 3,'descr' => 3),
		             array('valor' => 4,'descr' => 4),
		             array('valor' => 5,'descr' => 5),
		             array('valor' => 6,'descr' => 6),
		             array('valor' => 7,'descr' => 7),
		             array('valor' => 8,'descr' => 8),
		             array('valor' => 9,'descr' => 9),
		             array('valor' => 10,'descr' => 10),
		             );
	}
	
	/**
	 * Retorna las escalas de notas por defecto para un elemento en un conjunto no vacío de planes de estudio.
	 *
	 * @param integer $elemento
	 * @param array $planes
	 * @param string $columna nombre de columna de la tabla 'sga_elementos_plan' que almacena la escala de notas a recuperar.
	 * @return array
	 */
	function get_escalas_notas_elemento_planes($elemento, $planes, $columna)
	{
		$elemento = toba::db()->quote($elemento);
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes', 'plan');
		$sql = "SELECT	DISTINCT sga_elementos_plan.$columna
				FROM	sga_planes,
						sga_elementos_plan,
						sga_elementos_revision
				WHERE	sga_planes.version_actual = sga_elementos_plan.plan_version AND
						sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_plan.$columna IS NOT NULL AND
						sga_elementos_revision.elemento = $elemento AND
						$filtro_planes
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna las escalas de notas por defecto para un elemento en el conjunto de planes de estudio
	 * asociado a la Mesa de Examen dada.
	 *
	 * @param integer $elemento
	 * @param array $mesa_examen
	 * @param string $columna nombre de columna de la tabla 'sga_elementos_plan' que almacena la escala de notas a recuperar.
	 * @return array
	 */
	function get_escalas_notas_elemento_planes_mesa_examen($elemento, $mesa_examen, $columna)
	{
		$elemento = toba::db()->quote($elemento);
		
		$sql = "SELECT	DISTINCT sga_elementos_plan.$columna
				FROM	sga_planes,
						sga_elementos_plan,
						sga_elementos_revision
				WHERE	sga_planes.version_actual = sga_elementos_plan.plan_version AND
						sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_plan.$columna IS NOT NULL AND
						sga_elementos_revision.elemento = $elemento AND
						sga_planes.plan IN
										(SELECT sga_mesas_examen_propuestas.plan
										 FROM	sga_mesas_examen_propuestas
										 WHERE	sga_mesas_examen_propuestas.mesa_examen = $mesa_examen)
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Reorna las opciones para el combo de disponibilidades para una actividad.
	 *
	 */
	function get_opciones_combo_disponible_para()
	{
		return array(array('valor' => elemento::para_mesas_de_examen_y_comisiones, 	'descr' => 'Para todas las operaciones'),
                     array('valor' => elemento::solo_para_creacion_comisiones, 		'descr' => 'Solo para creación de Comisiones'),
                     array('valor' => elemento::solo_para_creacion_mesas_de_examen, 'descr' => 'Solo para creación de Mesas de Examen'));
	}
	
	/**
	 * Obtiene un listado de actividades que permiten inscripciones a cursada
	 * por prioridad
	 * 
	 * @param int $periodo_lectivo
	 * @param string $where
	 */
	function get_actividades_insc_prioridad($periodo_lectivo, $where='')
	{
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);
		
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT	DISTINCT
						sga_elementos.elemento,
						sga_elementos.nombre,
						sga_elementos.nombre_abreviado,
						sga_elementos.codigo,
						'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre AS actividad_codigo_y_nombre
				FROM 	sga_elementos,
						sga_elementos_atrib,
						sga_comisiones
				WHERE	sga_elementos.elemento = sga_elementos_atrib.elemento
						AND sga_elementos_atrib.insc_cursada_por_prioridad = '".comunes::si."'
						AND sga_comisiones.elemento = sga_elementos.elemento
						AND sga_comisiones.periodo_lectivo = $periodo_lectivo
						$where
				ORDER BY sga_elementos.codigo
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Verifica si la propuesta contiene alguna de las actividades dadas.
	 * 
	 * @param integer $propuesta
	 * @param array $actividades
	 * @return boolean
	 */
	function propuesta_incluye_actividades($propuesta, $actividades)
	{
		$propuesta = toba::db()->quote($propuesta);
		$actividades = guarani_sql::armar_condicion_in($actividades, 'vw_actividades_plan', 'elemento', 'elemento');
		$sql = "SELECT	COUNT(*) as cant
				FROM	vw_actividades_plan
				WHERE	vw_actividades_plan.propuesta = $propuesta AND
						$actividades
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] == 0) ? false : true;
	}

	/**
	 * Retorna el listado de actividades asociables a convenios (filtrables por propuesta y plan).
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_listado_actividades_convenio($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		DISTINCT vw_actividades_plan.elemento,
							'(' || COALESCE (vw_actividades_plan.codigo, 'Sin código') || ') ' || vw_actividades_plan.nombre as actividad_descr,
							vw_actividades_plan.codigo
				FROM		vw_actividades_plan
				$where
				ORDER BY	vw_actividades_plan.codigo
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * 
	 * Obtiene el valor por defecto del campo 'cobrable' para comisiones.
	 * 
	 * Este campo debe presentar por defecto el valor que tenga la actividad en 
	 * los planes en las que se encuentra y, en caso de tener diferentes valores 
	 * en los planes, indicar el que tiene la actividad en su definición. 
	 * 
	 * @param int $elemento
	 * @param arrat $planes
	 * @return string S / N
	 */
	function get_valor_defecto_cobrable($elemento, $planes)
	{
		$elemento = toba::db()->quote($elemento);
		$filtro_planes = ' AND ' . guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
		
		$sql = "SELECT 	
					DISTINCT sga_elementos_plan.cobrable
				FROM  	
					sga_elementos_plan,
					sga_elementos_revision,
					sga_planes_versiones
				WHERE
					sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
					AND sga_elementos_revision.elemento = $elemento
					AND sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
					$filtro_planes";
		
		$res = guarani_db::consultar($sql, null, false);
		
		if (guarani_arreglos::count($res) == 1) {
			/**
			 * Si en todos los planes toma el mismo valor, se retorna ese
			 */
			$salida = $res[0]['cobrable'];
		} else {
			$sql = "SELECT	sga_elementos_atrib.cobrable
					FROM	sga_elementos_atrib
					WHERE	sga_elementos_atrib.elemento = $elemento;
					";
			$res2 = guarani_db::consultar_fila($sql);
			$salida = $res2['cobrable'];
		}
		
		return $salida;
	}
	
	function get_elementos_excepcion_regularidad($where = '')
	{
		if ($where) {
			$where = " AND ".$where;
		}		
		$sql = "
				SELECT
					vw_elementos.nombre,
					vw_elementos.codigo,
					vw_elementos.entidad_subtipo_nombre,
					vw_elementos.entidad_subtipo,
					CASE WHEN (sga_excep_regularidad.vigencia_cursada > 1) THEN sga_excep_regularidad.vigencia_cursada::varchar || ' ' || 'meses'
						 WHEN (sga_excep_regularidad.vigencia_cursada = 1) THEN sga_excep_regularidad.vigencia_cursada::varchar || ' ' || 'mes'
					ELSE
					   'No' END as vigencia_cursada,
					CASE WHEN (sga_excep_regularidad.vigencia_revalida > 1) THEN sga_excep_regularidad.vigencia_revalida::varchar || ' ' || 'meses'
						 WHEN (sga_excep_regularidad.vigencia_revalida = 1) THEN sga_excep_regularidad.vigencia_revalida::varchar || ' ' || 'mes'
					ELSE
					   'No' END as vigencia_revalida,					
					sga_excep_regularidad.excep_regularidad
				FROM
					vw_elementos,
					sga_excep_regularidad
				WHERE
					vw_elementos.elemento = sga_excep_regularidad.elemento
					$where
				";
		return guarani_db::consultar($sql);
	}
	
	function get_planes_excepcion_regularidad($where = '')
	{
		if ($where) {
			$where = " AND ".$where;
		}		
		$sql = "
				SELECT
					sga_excep_regularidad_planes.excep_regularidad,
					sga_excep_regularidad_planes.plan,
					sga_excep_regularidad.vigencia_cursada,
					sga_excep_regularidad.vigencia_revalida
				FROM
					sga_excep_regularidad,
					sga_excep_regularidad_planes
				WHERE
					sga_excep_regularidad.excep_regularidad = sga_excep_regularidad_planes.excep_regularidad
					$where
				";
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si un módulo dado es del subtipo "Materia Genérica".
	 * 
	 * @param type $modulo
	 * @return boolean
	 */
	function es_materia_generica($modulo)
	{
		$modulo = toba::db()->quote($modulo);
		$sql = "SELECT	sga_elementos.entidad_subtipo
				FROM	sga_elementos
				WHERE	sga_elementos.elemento = $modulo
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['entidad_subtipo'] == g3entidad::subtipo_materia_generica);
	}
	
	/*
	 * Devuelve las materias optativas compartidas entre genericas en su plan_version
	 */
	function get_optativas_de_genericas($alumno, $plan_version, $filtro = '', $join = 'LEFT')
	{
		$elemento_estado_activo = quote(elemento::estado_activo);
        $si = quote(comunes::si);
        $no = quote(comunes::no);
        $resultado_aprobado = quote(escalas_notas::resultado_aprobado);
        $aprobada = 'Aprobado';
        $regularizada = 'Regularizada';
		
		if ($filtro) {
			$filtro = " AND ".$filtro;
		}
		
		$sql = "
				SELECT
					vw_optativas_plan.elemento AS optativa_elemento,
					vw_optativas_plan.codigo AS optativa_codigo,
					vw_optativas_plan.nombre AS optativa_nombre,
					vw_optativas_plan.nombre_abreviado AS optativa_nombre_abreviado,
					vw_optativas_plan.puntaje AS puntaje,
					vw_optativas_plan.creditos AS creditos,
					vw_optativas_plan.elemento_generica AS generica_elemento,
					vw_optativas_plan.codigo_generica AS generica_codigo,
					vw_optativas_plan.nombre_generica AS generica_nombre,
					(SELECT '(' || gen_plan.codigo || ') ' ||gen_plan.nombre 
                       FROM sga_alumnos_optativas 
                       JOIN vw_elementos_plan as gen_plan ON (gen_plan.plan_version = sga_alumnos_optativas.plan_version AND
                                                              gen_plan.elemento = sga_alumnos_optativas.generica)
                       WHERE sga_alumnos_optativas.alumno = $alumno AND
					    sga_alumnos_optativas.plan_version = $plan_version  AND 
					    sga_alumnos_optativas.optativa = vw_optativas_plan.elemento 
                      LIMIT 1
                    ) as generica_asignada,
					vw_optativas_plan.estado as estado 
				FROM 
					vw_optativas_plan
				$join JOIN sga_alumnos_optativas ON 
					(sga_alumnos_optativas.alumno = $alumno AND
					 sga_alumnos_optativas.plan_version = $plan_version  AND 
					 sga_alumnos_optativas.optativa = vw_optativas_plan.elemento AND 
					 sga_alumnos_optativas.generica = vw_optativas_plan.elemento_generica)
					
				WHERE 
					vw_optativas_plan.plan_version = $plan_version 
					AND f_optativa_es_compartida(vw_optativas_plan.elemento, $plan_version, NULL, NULL) = 1
					$filtro
				ORDER BY	
					optativa_nombre, 
					optativa_elemento,
					generica_nombre, 
					generica_elemento
				";
		return guarani::db()->consultar($sql);
	}

	/**
	 * Retorna los datos básicos de las optativas asociadas a una materia genérica dada.
	 * Si recibe un plan-versión, el nombre y los créditos devueltos serán los correspondientes al mismo.
	 * 
	 * @param int $materia_generica
	 * @param int $plan_version
	 * @param int $alumno
	 * @return array
	 */
	function get_optativas_generica($materia_generica, $plan_version=null, $alumno=null)
	{
		$materia_generica = toba::db()->quote($materia_generica);
		
		// Actividades optativas de una genérica
		if (is_null($plan_version)) {
			// Muestra datos de las optativas de una generica. Optativas con el nombre generico.
			$sql = "SELECT
						vw_optativas.elemento as elemento,  -- optativa
						vw_optativas.elemento_revision,
						vw_optativas.codigo as codigo,      -- codigo optativa  
						vw_optativas.nombre as nombre,      -- nombre optativa 
						vw_optativas.nombre_abreviado as nombre_abreviado,  -- nombre abreviado optativa
						vw_optativas.orden as orden,        -- orden
						vw_optativas.puntaje as puntaje,    -- puntaje  
						cast(null as decimal(8,2)) as creditos, 
						cast(null as decimal(8,2)) as horas,
						vw_optativas.entidad_tipo,
						vw_optativas.entidad_subtipo,
						1 as es_optativa,
						0 as optativa_compartida
				FROM 	vw_optativas
				WHERE 	vw_optativas.elemento_generica = $materia_generica
				ORDER BY orden	";
		} else {
			// Muestra datos de las optativas de una generica de una version de plan de estudios.
			$plan_version = toba::db()->quote($plan_version);
			
			$alumno = (is_null($alumno))? 'NULL' : toba::db()->quote($alumno);
			
			$sql = "SELECT
						vw_optativas_plan.elemento,
						vw_optativas_plan.elemento_revision,
						vw_optativas_plan.codigo,
						vw_optativas_plan.nombre,
						vw_optativas_plan.nombre_abreviado,
						vw_optativas_plan.orden,
						vw_optativas_plan.puntaje,
						vw_optativas_plan.creditos,
						vw_optativas_plan.horas,
						vw_optativas_plan.entidad_tipo,
						vw_optativas_plan.entidad_subtipo,
						1 as es_optativa, 
						CASE 
						  WHEN $alumno IS NULL THEN 0
						  ELSE f_optativa_es_compartida(vw_optativas_plan.elemento, $plan_version, $alumno, $materia_generica)
						END as optativa_compartida
				FROM vw_optativas_plan					 
				WHERE vw_optativas_plan.elemento_generica = $materia_generica
				  AND vw_optativas_plan.plan_version      = $plan_version
				ORDER BY orden ";
		}
		
		return guarani::db()->consultar($sql);
	}

	/**
	 * Retorna el conjunto de optativas que fueron parte de una materia genérica pero no deberían estar asociadas al plan-versión.
	 * Están presentes en 'sga_elementos_plan' pero no en 'sga_elementos_comp'.
	 * 
	 * @param int $materia_generica
	 * @param int $plan_version
	 * @return array
	 */
	function get_optativas_huerfanas_de_generica_plan_version($materia_generica, $plan_version)
	{
		$materia_generica = toba::db()->quote($materia_generica);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	vw_optativas.elemento,
						vw_optativas.elemento_revision,
						vw_optativas.entidad
				FROM	vw_optativas					 
				WHERE	vw_optativas.elemento_generica = $materia_generica AND
						vw_optativas.elemento_revision NOT IN (	SELECT	vw_componentes_modulo_plan.elemento_revision
																FROM	vw_componentes_modulo_plan
																WHERE	vw_componentes_modulo_plan.plan_version = $plan_version)
		";
		return guarani::db()->consultar($sql);
	}

	/**
	 * Lista todas las actividades activas que se encuentren en dos o más planes activos/vigentes de diferentes propuestas activas.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_actividades_varios_planes($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_actividades_plan.elemento,
							vw_actividades_plan.codigo as elemento_codigo,
							vw_actividades_plan.nombre as elemento_nombre,
							vw_actividades_plan.propuesta,
							sga_propuestas.codigo as propuesta_codigo,
							sga_propuestas.nombre as propuesta_nombre,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descripcion, 
							vw_actividades_plan.plan,
							sga_planes.codigo as plan_codigo,
							sga_planes.nombre as plan_nombre,
							'(' || sga_planes.codigo || ') ' || sga_planes.nombre as plan_descripcion, 
							(
							SELECT	COUNT(1) 
							FROM	sga_elementos_no_comunes
							WHERE	sga_elementos_no_comunes.elemento = vw_actividades_plan.elemento AND
									sga_elementos_no_comunes.plan_origen = vw_actividades_plan.plan
							) as cant_excepciones
				FROM		vw_actividades_plan
							JOIN sga_propuestas ON vw_actividades_plan.propuesta = sga_propuestas.propuesta
							JOIN sga_planes ON vw_actividades_plan.plan = sga_planes.plan
				WHERE		vw_actividades_plan.estado = '".elemento::estado_activo."' AND
							sga_propuestas.estado = '".propuesta::estado_activo."' AND
							sga_planes.estado IN ('".planes::estado_activo."', '".planes::estado_activo_vigente."') AND
							-- Actividades que estan en dos o mas propuestas.
							vw_actividades_plan.elemento IN (	SELECT		vw_actividades_plan.elemento
																FROM		vw_actividades_plan
																			JOIN sga_propuestas ON vw_actividades_plan.propuesta = sga_propuestas.propuesta
																			JOIN sga_planes ON vw_actividades_plan.plan = sga_planes.plan
																WHERE		sga_propuestas.estado = '".propuesta::estado_activo."' AND
																			sga_planes.estado IN ('".planes::estado_activo."', '".planes::estado_activo_vigente."')
																GROUP BY	vw_actividades_plan.elemento
																HAVING		COUNT(DISTINCT sga_propuestas.propuesta) > 1)
							$where
				ORDER BY	vw_actividades_plan.nombre,
							vw_actividades_plan.codigo, 
							propuesta_descripcion, 
							plan_descripcion
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Lista de propuestas y planes activos de una actividad compartida dada.
	 * 
	 * @param integer $actividad Actividad compartida por varios planes
	 * @return array
	 */
	function get_planes_by_actividad_compartida($actividad)
	{
		
		$propuesta_activo = toba::db()->quote(propuesta::estado_activo);
		$plan_activo = toba::db()->quote(planes::estado_activo);
		$plan_activo_vigente = toba::db()->quote(planes::estado_activo_vigente);
		
		$sql = "
			SELECT	DISTINCT
					vw_planes.propuesta						AS propuesta,
					vw_planes.propuesta_codigo				AS propuesta_codigo,
					vw_planes.propuesta_nombre				AS propuesta_nombre,
					vw_planes.propuesta_nombre_abreviado	AS propuesta_nombre_abreviado,
					vw_planes.plan							AS plan,
					vw_planes.plan_codigo					AS plan_codigo,
					vw_planes.plan_nombre					AS plan_nombre

			FROM vw_elementos_plan
			JOIN vw_planes ON (vw_planes.plan_version = vw_elementos_plan.plan_version)
			WHERE	vw_elementos_plan.elemento = $actividad
			AND		vw_planes.propuesta_estado = $propuesta_activo
			AND		vw_planes.plan_estado IN ($plan_activo, $plan_activo_vigente)
			ORDER BY vw_planes.propuesta_nombre, vw_planes.plan_nombre
		";
		
		return guarani_db::consultar($sql, null, false);
	}
	
	/**
	 * Lista de planes de estudios activos (vigentes y no vigentes) donde se encuentra la actividad seleccionada (excepto el plan de origen).
	 * Lista planes de otras propuetas y planes de la misma propuesta exepto plan origen donde se encuentra la actividad.
	 *
	 * @param integer $actividad Actividad compartida por varios planes
	 * @param integer $plan_origen Plan origen seleccionado
	 * @return array
	 */
	function get_otros_planes_by_actividad_compartida($actividad, $plan_origen)
	{
		$actividad = toba::db()->quote($actividad);
		$plan_origen = toba::db()->quote($plan_origen);
		$sql = "SELECT	DISTINCT 
						vw_elementos_plan.elemento,
						$plan_origen as plan_origen,
						vw_planes.propuesta,
						vw_planes.propuesta_codigo,
						vw_planes.propuesta_nombre,
						vw_planes.propuesta_nombre_abreviado,
						vw_planes.plan as plan_destino,
						vw_planes.plan_codigo,
						vw_planes.plan_nombre,
						vw_planes.plan_estado,
						sga_planes_estados.descripcion as plan_estado_descr,
						(CASE WHEN sga_elementos_no_comunes.elemento IS NOT NULL THEN 1 ELSE 0 END) AS exceptuado
				FROM vw_elementos_plan
				JOIN vw_planes ON vw_planes.plan_version = vw_elementos_plan.plan_version
				JOIN sga_planes_estados ON sga_planes_estados.estado = vw_planes.plan_estado
				LEFT JOIN sga_elementos_no_comunes ON (sga_elementos_no_comunes.elemento = vw_elementos_plan.elemento AND 
				                                       sga_elementos_no_comunes.plan_destino = vw_planes.plan AND
				                                       sga_elementos_no_comunes.plan_origen = $plan_origen)
				WHERE vw_elementos_plan.elemento = $actividad
				  AND vw_planes.plan_estado IN ('".planes::estado_activo_vigente."', '".planes::estado_activo_no_vigente."')
				  AND vw_elementos_plan.plan <> $plan_origen
				ORDER BY vw_planes.propuesta_nombre, vw_planes.plan_nombre
		";
		return guarani_db::consultar($sql, null, false);
	}
	
	function get_opciones_con_sin_comisiones()
	{
		return [
			['valor' => 'S', 'descr' => 'Con comisiones'],
			['valor' => 'N', 'descr' => 'Sin comisiones']
		];
	}
	
	function get_opciones_con_sin_mesas()
	{
		return [
			['valor' => 'S', 'descr' => 'Con mesas de examen'],
			['valor' => 'N', 'descr' => 'Sin mesas de examen']
		];
	}
	
}