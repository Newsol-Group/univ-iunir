<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class co_equivalencias
{

	/**
	 * Obtiene los tramites de equivalencia según el filtro dado.
	 *
	 * @param str $where filtro
	 */
	function get_listado_tramites($where)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  	sga_equiv_tramite.equivalencia_tramite,
							sga_equiv_tramite.fecha,
							to_char(sga_equiv_tramite.fecha, '".comunes::formato_fecha_visual."') AS fecha_formato_visual,
							sga_equiv_tramite.tipo_tramite,
							sga_equiv_tramite.rectifica_a,
							sga_equiv_tramite.alumno,
							sga_equiv_tramite.observaciones,
							sga_equiv_tramite.plan_version,
							sga_equiv_tramite.generacion_automatica,
							sga_equiv_tramite.propuesta,
							sga_equiv_tramite.plan,
							sga_equiv_tramite.responsable_academica,
							sga_equiv_tramite.documento,
							sga_equiv_tramite.convenio,
							sga_equiv_tramite.origen,
							sga_equiv_tramite.estado,
							sga_equiv_tramite.id_documento_digital,
							sga_equiv_tramite.parametros_documento_digital,
							sga_equiv_tramite.estado_documento_digital,
							sga_equiv_tramite.digital,
							sga_equiv_tramite.nro_acta,
							sga_equiv_tramite_origen.nombre AS origen_nombre,
							sga_equiv_tramite_origen.tipo || ' - ' || sga_equiv_tramite_origen.nombre AS origen_descr,
							sga_equiv_tramite_origen.tipo	AS origen_tipo,
							vw_personas.apellido_nombres 	AS alumno_nombre_completo,
							vw_personas.tipo_nro_documento 	AS alumno_identificacion,
							sga_propuestas.nombre_abreviado AS propuesta_alumno,
							sga_propuestas.nombre			AS propuesta_nombre,
							sga_alumnos.propuesta			AS propuesta_alumno_id,
							sga_planes.plan					AS plan_alumno_id,
							sga_planes.nombre				AS plan_alumno,
							sga_planes_versiones.version	AS version_alumno,
							sga_alumnos.legajo,
							sga_documentos.documento_numero,
							sga_equiv_tramite.institucion,
							sga_instituciones.nombre AS institucion_nombre,
							sga_equiv_tramite_estados.nombre AS estado_nombre
				FROM		sga_equiv_tramite
								LEFT JOIN sga_documentos ON sga_equiv_tramite.documento = sga_documentos.documento
								LEFT JOIN sga_instituciones ON sga_equiv_tramite.institucion = sga_instituciones.institucion,
							sga_equiv_tramite_origen,
							sga_equiv_tramite_estados,
							sga_alumnos,
							vw_personas,
							sga_propuestas,
							sga_planes,
							sga_planes_versiones
				WHERE		sga_equiv_tramite.origen = sga_equiv_tramite_origen.origen AND
							sga_equiv_tramite.estado = sga_equiv_tramite_estados.estado AND
							sga_equiv_tramite.alumno = sga_alumnos.alumno AND
							sga_alumnos.persona = vw_personas.persona AND
							sga_equiv_tramite.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas.propuesta
							$where
				ORDER BY	sga_equiv_tramite.equivalencia_tramite
  				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * 
	 *
	 * @param integer $id_tramite id del tramite de equivalencia otorgado para el cual se desea obtener los datos.
	 */
	function get_datos_vista_tramite_equiv_otorgado($id_tramite)
	{
		$id_tramite = toba::db()->quote($id_tramite);
		$sql = "SELECT	vw_equivalencias.elemento,
					    vw_equivalencias.equivalencia,
					    vw_equivalencias.instancia,
						sga_instancias.nombre as instancia_nombre,
					    vw_equivalencias.fecha,
					    vw_equivalencias.escala_nota,
					    vw_equivalencias.nota,
					    vw_equivalencias.resultado,
					    vw_equivalencias.fecha_vigencia,
					    vw_equivalencias.temas_a_rendir,
					    vw_equivalencias.grupo_equivalencia,
					    vw_equivalencias.rectificado,
					    vw_equivalencias.rectifica_a,
					    vw_equivalencias.estado,
					    vw_equivalencias.nota || ' (' || vw_equivalencias.nota_descripcion || ')' as nota_descr,
					    vw_equivalencias.resultado_descripcion as resultado_descr,
						'(' || COALESCE(sga_elementos.codigo, 'Sin código') || ') ' || sga_elementos.nombre as actividad,
						sga_escalas_notas.nombre as escala_nota_nombre,
						sga_equiv_otorgada_estados.nombre as estado_nombre,
						CASE
							WHEN vw_equivalencias.estado = '".acta::detalle_estado_invalidado."' THEN '".comunes::si_desc."'
							ELSE '".comunes::no_desc."'
						END as invalidado
                FROM	vw_equivalencias
						LEFT JOIN sga_escalas_notas ON vw_equivalencias.escala_nota = sga_escalas_notas.escala_nota,
						sga_instancias,
						sga_elementos,
						sga_equiv_otorgada_estados
                WHERE	vw_equivalencias.instancia = sga_instancias.instancia AND
						vw_equivalencias.elemento = sga_elementos.elemento AND
						vw_equivalencias.estado = sga_equiv_otorgada_estados.estado AND
						vw_equivalencias.equivalencia_tramite_original = $id_tramite
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * 
	 *
	 * @param integer $id_tramite id del tramite de equivalencia otorgado para el cual se desea obtener los datos.
	 */
	function get_datos_tramite_equivalencia_otorgado($id_tramite)
	{
		$id_tramite = toba::db()->quote($id_tramite);
		$filtro = "sga_equiv_tramite.equivalencia_tramite = $id_tramite";
		$datos = $this->get_listado_tramites($filtro);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	/**
	 * Obtiene el detalle de las actividades otorgadas en el trámite dado, según el filtro dado.
	 *
	 * @param integer	id_tramite	id del trámite de equivalencia para el cual se desea recuperar el detalle de actividades.
	 * @param str 		$where 		filtro filtro sobre el detalle a recuperar.
	 */
	function get_detalle_actividades_tramite_equivalencia($id_tramite, $where='')
	{
		$id_tramite = toba::db()->quote($id_tramite);
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT  	sga_equiv_otorgada.equivalencia,
							sga_equiv_otorgada.elemento,
							'(' || COALESCE(sga_elementos.codigo, 'Sin código') || ') ' || sga_elementos.nombre as elemento_descr,
							sga_equiv_otorgada.instancia,
							sga_instancias.nombre AS instancia_nombre,
							sga_equiv_otorgada.fecha,
							sga_equiv_otorgada.escala_nota,
							sga_escalas_notas.nombre as escala_nota_nombre,
							sga_equiv_otorgada.nota,
							sga_escalas_notas_det.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
							sga_equiv_otorgada.resultado,
							sga_escalas_notas_resultado.descripcion as resultado_descr,
							sga_equiv_otorgada.fecha_vigencia,
                            to_char(sga_equiv_otorgada.fecha_vigencia, '".comunes::formato_fecha_visual."') AS fecha_vigencia_visual,
							CASE
								WHEN sga_equiv_otorgada.fecha_vigencia IS NULL THEN '".comunes::no_desc."'
								ELSE '".comunes::si_desc."'
							END AS tiene_vencimiento,	
							sga_equiv_otorgada.temas_a_rendir,
							sga_equiv_otorgada.grupo_equivalencia,
							sga_equiv_otorgada.rectificado,
							sga_equiv_otorgada.rectifica_a,
							sga_equiv_otorgada.estado,
							sga_equiv_otorgada_estados.nombre as estado_nombre
				FROM		sga_equiv_otorgada
								LEFT JOIN sga_escalas_notas ON sga_equiv_otorgada.escala_nota = sga_escalas_notas.escala_nota
								LEFT JOIN sga_escalas_notas_det ON (sga_equiv_otorgada.nota = sga_escalas_notas_det.nota AND sga_equiv_otorgada.escala_nota = sga_escalas_notas_det.escala_nota),
							sga_elementos,
							sga_instancias,
							sga_equiv_otorgada_estados,
							sga_escalas_notas_resultado
				WHERE		sga_equiv_otorgada.elemento = sga_elementos.elemento AND
							sga_equiv_otorgada.instancia = sga_instancias.instancia AND
							sga_equiv_otorgada.estado = sga_equiv_otorgada_estados.estado AND
							sga_equiv_otorgada.resultado = sga_escalas_notas_resultado.resultado AND
							sga_equiv_otorgada.equivalencia_tramite = $id_tramite
				$where
				ORDER BY	sga_elementos.nombre,
							sga_equiv_otorgada.fecha
  				;";

		return guarani_db::consultar($sql);
	}

	function get_datos_equiv_otorgada($equivalencia)
	{
		$equivalencia = toba::db()->quote($equivalencia);
		$sql = "SELECT	vw_equivalencias.equivalencia,
						vw_equivalencias.instancia,
						vw_equivalencias.fecha,
						vw_equivalencias.nota,
						vw_equivalencias.resultado,
						vw_equivalencias.fecha_vigencia,
						vw_equivalencias.temas_a_rendir
				FROM	vw_equivalencias
				WHERE	vw_equivalencias.equivalencia = $equivalencia
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Obtiene las equivalencias internas de los tramites de equivalencia
	 *
	 * @param integer	equivalencia	id de la equivalencia otorgada del tramite
	 */
	function get_tramite_equivalencias_internas($equivalencia='')
	{
		$where = '';
		if ($equivalencia) {
			$where = '	WHERE		sga_equiv_internas.equivalencia = ' . toba::db()->quote($equivalencia);
		}
		
		$sql = "SELECT  	sga_equiv_internas.equivalencia,
							sga_equiv_internas.equivalencia_interna,
							sga_equiv_internas.elemento,
							sga_equiv_internas.propuesta,
							sga_equiv_internas.fecha,
							sga_equiv_internas.nota,
							sga_equiv_internas.origen,
							sga_equiv_internas.resultado
				FROM		sga_equiv_internas
				$where
				ORDER BY	sga_equiv_internas.elemento
  				;";

		return guarani_db::consultar($sql);
	}
		

	/**
	 * Obtiene las equivalencias externas de los tramites de equivalencia
	 *
	 * @param integer	equivalencia	id de la equivalencia otorgada del tramite
	 */
	function get_tramite_equivalencias_externas($equivalencia='')
	{
		$where = '';
		if ($equivalencia) {
			$where = '	WHERE		sga_equiv_externas.equivalencia = ' . toba::db()->quote($equivalencia);
		}
		
		$sql = "SELECT  	sga_equiv_externas.equivalencia,
							sga_equiv_externas.equivalencia_externa,
							sga_equiv_externas.actividad,
							sga_equiv_externas.libro,
							sga_equiv_externas.fecha,
							sga_equiv_externas.nota,
							sga_equiv_externas.acta,
							sga_equiv_externas.folio
				FROM		sga_equiv_externas
				$where
				ORDER BY	sga_equiv_externas.actividad
  				;";

		return guarani_db::consultar($sql);
	}

	
	/**
	 * Obtiene los origenes de los tramites de equivalencia según el filtro dado.
	 *
	 * @param str $where filtro
	 */
	function get_listado_origenes_tipos($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT      
					sga_equiv_tramite_origen.origen as valor, 
					sga_equiv_tramite_origen.nombre || ' - ' || sga_equiv_tramite_origen.tipo as descr,
					sga_equiv_tramite_origen.nombre, 
					sga_equiv_tramite_origen.tipo 
                FROM        sga_equiv_tramite_origen
				$where
				ORDER BY	sga_equiv_tramite_origen.tipo 
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_tipos_actividades_origen_equivalencias_internas()
	{
		return array(array('valor' => equivalencia::int_origen_cursada, 'descr' => 'Cursada'),
		             array('valor' => equivalencia::int_origen_equivalencia, 'descr' => 'Equivalencia'),
		             array('valor' => equivalencia::int_origen_examen, 'descr' => 'Examen'),
					 array('valor' => equivalencia::int_origen_promocion, 'descr' => 'Promoción'),
					 array('valor' => equivalencia::int_origen_resolucion, 'descr' => 'Resolución')
		             );
	}
	
	/**
	 * Obtiene el listado de instancias que aplican a Equivalencias.
	 *
	 */
	function get_listado_instancias_equivalencias($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 		sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre
				FROM 		sga_instancias
				WHERE		sga_instancias.aplica_a = '" . equivalencia::inst_aplica_a_equiv . "'
							$where
				ORDER BY 	sga_instancias.orden";

		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de la instancia/s dada/s para la carga externa (masiva).
      *	 @param integer/array id de instancia o arreglo de ids de las instancias para los cuales se consultan las descr.
    */
	function get_instancia_equiv_nombre($instancia)
	{
		$datos = $this->get_listado_instancias_equivalencias();
		if (is_array($instancia)) {
			$ret = array();
			foreach ($datos as $dato) {
				if (in_array($dato['instancia'], $instancia)) {
					$ret[] =array('instancia' => $dato['instancia'], 'instancia_nombre' => $dato['instancia_nombre']);
				}
			}
			return $ret;
		} else {
			foreach ($datos as $dato) {
				if ($dato['instancia'] == $instancia) {
					return array('instancia_nombre' => $dato['instancia_nombre']);
				}			
			}
		}
	}
	
	/**
	 * Obtiene la cantidad de  equivalencias otorgadas (totales, regulares y parciales) de forma automática, en la fecha 
	 * dada, agrupadas por alumno, según el filtro dado.
	 *
	 * @param date	$fecha fecha en la cual se otorgaron las equivalencias a recuperar.
	 * @param str	$where filtro
	 */
	function get_equivalencias_automaticas_otorgadas_por_alumno($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 
					vw_personas.apellido_nombres as alumno_nombre,
					vw_personas.tipo_nro_documento as nro_identificacion,
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_total . "' THEN 1 ELSE 0 END) as cant_totales,   
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_regularidad . "' THEN 1 ELSE 0 END) as cant_regularidad,
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_parcial . "' THEN 1 ELSE 0 END) as cant_parciales
				FROM 
					sga_equiv_tramite,
					sga_equiv_otorgada,
					sga_alumnos,
					vw_personas,
					vw_actividades
				WHERE 
					sga_equiv_tramite.equivalencia_tramite = sga_equiv_otorgada.equivalencia_tramite
					AND sga_equiv_tramite.generacion_automatica = '" . comunes::si .  "' 
					AND sga_equiv_tramite.estado = '" . equivalencia::estado_cerrado . "' 
					AND sga_alumnos.alumno = sga_equiv_tramite.alumno
					AND vw_personas.persona = sga_alumnos.persona
					AND vw_actividades.elemento = sga_equiv_otorgada.elemento
					$where					
				GROUP BY 
					sga_alumnos.alumno, 
					vw_personas.apellido_nombres, 
					vw_personas.tipo_nro_documento
				ORDER BY 
					vw_personas.apellido_nombres		
  				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene la cantidad de  equivalencias otorgadas (totales, regulares y parciales) de forma automática, en la fecha 
	 * dada, agrupadas por actividad, según el filtro dado.
	 *
	 * @param date	$fecha fecha en la cual se otorgaron las equivalencias a recuperar.
	 * @param str	$where filtro
	 */
	function get_equivalencias_automaticas_otorgadas_por_actividad($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 
					vw_actividades.elemento,
					vw_actividades.nombre as actividad_nombre,
					vw_actividades.codigo as actividad_codigo,
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre as actividad_descr,
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_total . "' THEN 1 ELSE 0 END) as cant_totales,   
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_regularidad . "' THEN 1 ELSE 0 END) as cant_regularidad,
					SUM(CASE WHEN sga_equiv_otorgada.instancia = '" . equivalencia::equiv_instancia_parcial . "' THEN 1 ELSE 0 END) as cant_parciales
				FROM 
					sga_equiv_tramite,
					sga_equiv_otorgada,
					sga_alumnos,
					mdp_personas,
					vw_actividades
				WHERE 
					sga_equiv_tramite.equivalencia_tramite = sga_equiv_otorgada.equivalencia_tramite
					AND sga_equiv_tramite.generacion_automatica = '" . comunes::si .  "' 
					AND sga_equiv_tramite.estado = '" . equivalencia::estado_cerrado . "' 
					AND sga_alumnos.alumno = sga_equiv_tramite.alumno
					AND mdp_personas.persona = sga_alumnos.persona
					AND vw_actividades.elemento = sga_equiv_otorgada.elemento
					$where
				GROUP BY 
					vw_actividades.elemento,
					vw_actividades.codigo,
					vw_actividades.nombre
				ORDER BY 
					vw_actividades.nombre
  				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Opciones de completado para carga de equivalencias masivamente
	 *
	 */
	function get_opciones_combo_completar_equivalencias()
	{
		return array(array('valor' => equivalencia::autocompletar_campo_nota, 'descr' => 'Nota'),
                     array('valor' => equivalencia::autocompletar_campo_resultado , 'descr' => 'Resultado'),
                     array('valor' => equivalencia::autocompletar_campo_fecha , 'descr' => 'Fecha'),
                     array('valor' => equivalencia::autocompletar_campo_acta , 'descr' => 'Acta'),
                     array('valor' => equivalencia::autocompletar_campo_libro , 'descr' => 'Libro'),
                     array('valor' => equivalencia::autocompletar_campo_folio , 'descr' => 'Folio'));
	}
	
	function get_opciones_combo_para_equivalencias()
	{
		return array(array('valor' => equivalencia::autocompletar_para_todos , 'descr' => 'Todos los alumnos de la página'),
                     array('valor' => equivalencia::autocompletar_para_sin_datos , 'descr' => 'Alumnos de la página sin datos'));
	}
	
	function get_obciones_combo_estado_equivalencias()
	{
		$sql = "
				SELECT
					estado as valor,
					nombre as descr
				FROM
					sga_equiv_tramite_estados
				";
		return guarani_db::consultar($sql);
	}

	function get_opciones_procesar_matrices_de()
	{
		return array(	array('valor' => 'Examenes', 'descr' => 'Exámenes'),
						array('valor' => 'Cursadas', 'descr' => 'Regularidades/promociones'),
						array('valor' => 'Planes', 'descr' => 'Cambio de plan de estudios'),
						array('valor' => 'Versiones', 'descr' => 'Cambio de versión de plan de estudios'),
						array('valor' => 'Simultanea', 'descr' => 'Cursada simultánea de propuestas')
		);
	}
    
    /*
     * Trae los datos de una equivalencia parcial.
     */
    function get_equivalencia_parcial($equivalencia)
    {
        $sql =  "
                SELECT 
                    '(' || vw_equiv_parciales.actividad_codigo || ') ' || vw_equiv_parciales.actividad_nombre as actividad_codigo_y_nombre
                    ,vw_equiv_parciales.nota
                    ,vw_equiv_parciales.tipo as origen     
                    ,to_char(vw_equiv_parciales.fecha_vigencia,'".comunes::formato_fecha_visual."') as fecha_vigencia_formato
                FROM 
                    vw_equiv_parciales
                WHERE
                    equivalencia = $equivalencia
                ";            
        return guarani_db::consultar_fila($sql);
    }
	
	/**
	 * Obtiene los trámites de equivalencias cerrados que fallaron a la hora de crear el documento en Araí.
	 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	 */
	function get_equivalencias_cerradas_falla_documentos()
	{
		$estado_cerrado = toba::db()->quote(equivalencia::estado_cerrado);
		$estado_documento_digital_fallo = toba::db()->quote(arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO);
		$equiv_tramite_tipo_externa = toba::db()->quote(equivalencia::equiv_tramite_tipo_externa);
		
		$filtro = "sga_equiv_tramite.estado = $estado_cerrado";
		$filtro .= " AND sga_equiv_tramite.estado_documento_digital = $estado_documento_digital_fallo";
		$filtro .= " AND sga_equiv_tramite_origen.tipo = $equiv_tramite_tipo_externa";
		return $this->get_listado_tramites($filtro);
	}
	
   /**
	* Si existen equivalencias que fallaron en Araí.
	* @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
	* @return bool true si existen equivalencias que fallaron en Araí, false en caso contrario.
	*/
	function existen_equivalencias_fallaron_arai()
	{
		$estado_cerrado = toba::db()->quote(equivalencia::estado_cerrado);
		$estado_documento_digital_fallo = toba::db()->quote(arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO);
		$equiv_tramite_tipo_externa = toba::db()->quote(equivalencia::equiv_tramite_tipo_externa);

		$sql = "
				SELECT COUNT(*) AS cant_equivalencias_fallaron_arai
				FROM sga_equiv_tramite
				JOIN sga_equiv_tramite_origen ON (sga_equiv_tramite.origen = sga_equiv_tramite_origen.origen)
				WHERE sga_equiv_tramite.estado = $estado_cerrado
				AND sga_equiv_tramite.estado_documento_digital = $estado_documento_digital_fallo
				AND sga_equiv_tramite_origen.tipo = $equiv_tramite_tipo_externa;
				";
		$row = guarani_db::consultar_fila($sql);
		return ($row['cant_equivalencias_fallaron_arai'] > 0);
	}
	
	/**
	* Se carga un array con los dos tipos de tramite para equivalencias.
	* @see https://redmine.siu.edu.ar/redmine/issues/34073 
	*/
	function get_opciones_tipos_tramites()
	{
		return [
			['valor' => equivalencia::tipo_tramite_rectificativo, 'descr' => 'Rectificativo'],
			['valor' => equivalencia::tipo_tramite_normal, 'descr' => 'Normal']
		];
	}
}