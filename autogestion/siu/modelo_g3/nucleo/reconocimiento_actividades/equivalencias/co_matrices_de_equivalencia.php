<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class co_matrices_de_equivalencia
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		$sql = "SELECT  	sga_equiv_matrices.matriz,
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.nombre		AS matriz_nombre,
							sga_propuestas.nombre			AS propuesta_origen_nombre,
							sga_planes.nombre				AS plan_origen_nombre,
							sga_documentos.documento_numero	AS nro_resolucion,
							sga_equiv_matrices.aplicacion_automatica,
							sga_equiv_matrices.activo,
							CASE 
								WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS aplicacion_automatica_descr,
							CASE 
								WHEN sga_equiv_matrices.activo = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS activo_descr
							
				FROM		sga_equiv_matrices
								LEFT JOIN sga_planes 	 ON sga_equiv_matrices.plan_origen 		= sga_planes.plan
								LEFT JOIN sga_propuestas ON sga_equiv_matrices.propuesta_origen = sga_propuestas.propuesta
								LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion 	= sga_documentos.documento
		
				$where
		
				ORDER BY	sga_equiv_matrices.nombre,
							sga_equiv_matrices.alcance
  				";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene las matrices de equivalencia con sus atributos y sus grupos de equivalencia asociados, si es que los tiene,
	 * según el filtro dado.
	 *
	 * @param str $where filtro sobre las matrices y grupos asociados a recuperar.
	 */
	function get_matrices_equivalencia_con_grupos($where)
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT  	sga_equiv_matrices.matriz,
							sga_equiv_matrices.nombre as matriz_nombre,
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.plan_version_destino,
							sga_equiv_grupos.grupo_equivalencia,
							sga_equiv_grupos.nombre as grupo_nombre
				FROM		sga_equiv_matrices
							LEFT JOIN sga_equiv_grupos ON sga_equiv_grupos.matriz = sga_equiv_matrices.matriz
				$where
				ORDER BY	sga_equiv_matrices.alcance, sga_equiv_matrices.nombre, sga_equiv_grupos.orden, sga_equiv_grupos.nombre
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los grupos correspondientes a la matriz de equivalencia dada, si es que los posee, si no retorna un
	 * arreglo vacío.
	 *
	 * @param integer $id_matriz id de la matriz de equivalencia para la cual se quieren recuperar los grupos.
	 */
	function get_grupos_matriz_equivalencia($id_matriz)
	{
		$id_matriz = toba::db()->quote($id_matriz);
		$sql = "SELECT  	sga_equiv_grupos.grupo_equivalencia,
							sga_equiv_grupos.nombre as grupo_nombre
				FROM		sga_equiv_matrices,
							sga_equiv_grupos
				WHERE		sga_equiv_grupos.matriz = sga_equiv_matrices.matriz AND
							sga_equiv_matrices.matriz = $id_matriz
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de la matriz de equivalencia dada.
	 *
	 * @param integer $id_matriz id de la matriz para la cual se quieren obtener los datos.
	 */
	function get_datos_matriz($id_matriz)
	{
		$id_matriz = toba::db()->quote($id_matriz);
		$sql = "SELECT  	sga_equiv_matrices.matriz,
							sga_equiv_matrices.nombre,
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.nro_resolucion,
							sga_equiv_matrices.fecha_entrada_vigencia,
							sga_equiv_matrices.plan_version_destino,
							sga_equiv_matrices.propuesta_origen,
							sga_equiv_matrices.plan_origen,
							sga_equiv_matrices.plan_version_origen,
							sga_equiv_matrices.aplicacion_automatica,
							sga_equiv_matrices.nro_resolucion_baja,
							sga_equiv_matrices.fecha_baja,
							sga_equiv_matrices.activo
				FROM		sga_equiv_matrices
				WHERE		sga_equiv_matrices.matriz = $id_matriz
  				;";
		
		$datos = guarani_db::consultar($sql);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
		
	/**
	 * Obtiene el nombre de una matriz de equivalencia dada.
	 *
	 * @param integer $id_matriz id de la matriz para la cual se quieren obtener la descripción.
	 */
	function get_nombre_matriz_equivalencia($id_matriz)
	{
		$id_matriz = toba::db()->quote($id_matriz);
	    $sql = "SELECT	sga_equiv_matrices.matriz as valor,
						sga_equiv_matrices.nombre || ' ' || sga_equiv_matrices.alcance as descr
				FROM 	sga_equiv_matrices
				WHERE	sga_equiv_matrices.matriz = $id_matriz
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['descr'];
	}
	
	/**
	 * Obtiene los datos de la matriz de equivalencia dada junto con las Propuestas Planes Versiones Origen y Destino.
	 *
	 * @param integer $matriz_equivalencia id de la matriz para la cual se quieren obtener los datos.
	 */
	function get_datos_matriz_con_props_planes_versiones($matriz_equivalencia)
	{
		$matriz_equivalencia = toba::db()->quote($matriz_equivalencia);
		
		$sql = "SELECT  	sga_equiv_matrices.matriz,
							sga_equiv_matrices.nombre,
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.nro_resolucion,
							sga_equiv_matrices.fecha_entrada_vigencia,
							sga_equiv_matrices.plan_version_destino,
							sga_equiv_matrices.propuesta_origen,
							sga_equiv_matrices.plan_origen,
							sga_equiv_matrices.plan_version_origen,
							sga_equiv_matrices.aplicacion_automatica,
							sga_equiv_matrices.nro_resolucion_baja,
							sga_equiv_matrices.fecha_baja,
							to_char(sga_equiv_matrices.fecha_baja,'".comunes::formato_fecha_visual."') AS fecha_baja_format,
							sga_equiv_matrices.activo,
							CASE 
								WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS aplicacion_automatica_descr,
							CASE 
								WHEN sga_equiv_matrices.activo = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS activo_descr,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado AS propuesta_origen_nombre,
							sga_planes.nombre											  			AS plan_origen_nombre,
							'(' || propuestas.codigo || ') ' || propuestas.nombre_abreviado 		AS propuesta_destino_nombre,
							planes.nombre												  			AS plan_destino_nombre,
							sga_planes_versiones.nombre 											AS plan_version_destino_nombre,
							sga_documentos.documento_numero											AS nro_resolucion_descr,
							doc.documento_numero													AS nro_resolucion_baja_descr,
							CASE 
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_general."'
									THEN 'General'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_propuestas."'
									THEN 'Entre Propuestas'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_planes_otra_prop."'
									THEN 'Entre Planes de otra Propuesta'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_planes_misma_prop."'
									THEN 'Entre Planes de la misma Propuesta'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_versiones."'
									THEN 'Entre Versiones'
							END AS alcance_descr
							
				FROM		sga_equiv_matrices
								LEFT JOIN sga_planes 		 ON sga_equiv_matrices.plan_origen 			= sga_planes.plan
								LEFT JOIN sga_documentos 	 ON sga_equiv_matrices.nro_resolucion 		= sga_documentos.documento
								LEFT JOIN sga_propuestas 	 ON sga_equiv_matrices.propuesta_origen 	= sga_propuestas.propuesta
								LEFT JOIN sga_documentos doc ON sga_equiv_matrices.nro_resolucion_baja	= doc.documento,
							sga_planes_versiones,
							sga_propuestas AS propuestas,
							sga_planes AS planes
				
				WHERE		sga_equiv_matrices.matriz 				= $matriz_equivalencia
				AND			sga_equiv_matrices.plan_version_destino = sga_planes_versiones.plan_version
				AND			sga_planes_versiones.plan				= planes.plan
				AND			planes.propuesta						= propuestas.propuesta
		
				ORDER BY	sga_equiv_matrices.nombre
  				";
		
		$datos = guarani_db::consultar($sql);
		
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	/**
	 * Obtiene las Actividades de los Grupos de la Matriz de Equivalencia dada.
	 *
	 * @param integer $matriz_equivalencia id de la matriz para la cual se quieren obtener sus actividades
	 */
	function get_datos_actividades_grupos_equivalencia($matriz_equivalencia)
	{
		$matriz_equivalencia = toba::db()->quote($matriz_equivalencia);

		$sql = "SELECT  	sga_equiv_actividades.actividad_equivalencia,
							sga_equiv_grupos.grupo_equivalencia,					
							'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre AS actividad_nombre,
							sga_equiv_grupos.traslada_nota,
							sga_equiv_actividades.tiene_vencimiento,
							sga_equiv_actividades.temas_a_rendir,
							sga_equiv_actividades.nota AS traslada_nota_actividad,
							sga_equiv_grupos.nombre AS grupo_nombre,
							COALESCE(sga_documentos.documento_numero,'') AS resolucion_alta_descr,
							CASE 
								WHEN sga_equiv_grupos.traslada_nota = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS traslada_nota_descr,
							CASE 
								WHEN sga_equiv_grupos.activo = '".comunes::si."'
									THEN 'Si' 
									ELSE 'No'
							END AS activo_descr,
							CASE 
								WHEN sga_equiv_actividades.origen_destino = '".equivalencia::grupo_activ_origen."'
									THEN 'Origen'
									ELSE 'Destino'
							END AS origen_destino_descr,
							CASE 
								WHEN sga_equiv_actividades.condicion = '".equivalencia::tipo_regularizada."' THEN 'Regularizada'
								WHEN sga_equiv_actividades.condicion = '".equivalencia::tipo_aprobada ."' THEN 'Aprobada'
								WHEN sga_equiv_actividades.condicion = '".equivalencia::tipo_parcial."' THEN 'Parcial'
								WHEN sga_equiv_actividades.condicion = '".equivalencia::tipo_total."' THEN 'Total'
							END AS condicion_descr,
							sga_equiv_grupos.activo,
							COALESCE(doc.documento_numero,'') AS resolucion_baja_descr,
							COALESCE(to_char(sga_equiv_grupos.fecha_baja,'".comunes::formato_fecha_visual."'),'') AS fecha_baja_format
				
				FROM		sga_elementos,
							sga_equiv_actividades,
							sga_equiv_grupos
								LEFT JOIN sga_documentos 	 ON sga_equiv_grupos.nro_resolucion_alta = sga_documentos.documento
								LEFT JOIN sga_documentos doc ON sga_equiv_grupos.nro_resolucion_baja = doc.documento
				
				WHERE		sga_equiv_grupos.matriz 				= $matriz_equivalencia
				AND			sga_equiv_grupos.grupo_equivalencia		= sga_equiv_actividades.grupo_equivalencia
				AND			sga_equiv_actividades.elemento			= sga_elementos.elemento
		
				ORDER BY	sga_equiv_grupos.nombre, 
							sga_equiv_actividades.origen_destino DESC, 
							sga_elementos.codigo, 
							sga_elementos.nombre
  				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_datos_grupo_equivalencia($grupo_equivalencia)
	{
		$grupo_equivalencia = toba::db()->quote($grupo_equivalencia);
		
		$sql = "SELECT  	sga_equiv_grupos.grupo_equivalencia,
							sga_equiv_grupos.traslada_nota,
							sga_equiv_grupos.nombre,
							sga_documentos.documento_numero											 AS resolucion_alta_descr,
							doc.documento_numero													 AS resolucion_baja_descr,
							to_char(sga_equiv_grupos.fecha_baja,'".comunes::formato_fecha_visual."') AS fecha_baja_format
				
				FROM		sga_equiv_grupos
								LEFT JOIN sga_documentos 	 ON sga_equiv_grupos.nro_resolucion_alta = sga_documentos.documento
								LEFT JOIN sga_documentos doc ON sga_equiv_grupos.nro_resolucion_baja = doc.documento
				
				WHERE		sga_equiv_grupos.grupo_equivalencia	= $grupo_equivalencia
  				";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los planes versiones, con los datos de planes y propuestas asociados, e indicando si los mismos tienen
	 * o no matrices de equivalencia asociadas (que los tengan como destino).
	 *
	 * @param str $where filtro sobre los planes versiones a recuperar.
	 */
	function get_propuestas_planes_versiones_activos_matrices_equivalencia($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  	sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.codigo as propuesta_codigo,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descr,
							sga_planes.plan,
							sga_planes.nombre as plan_nombre,
							sga_planes.codigo as plan_codigo,
							planes_estados.estado as plan_estado,
							planes_estados.descripcion as plan_estado_descr,
							sga_planes_versiones.plan_version,
							sga_planes_versiones.version as plan_version_nombre,
							sga_planes_versiones.nombre as plan_version_descr,
							sga_planes_versiones.fecha_entrada_vigencia,
							planes_versiones_estados.estado as plan_version_estado,
							planes_versiones_estados.descripcion as plan_version_estado_descr,
							sga_planes.version_actual as plan_version_actual,
							planes_versiones_actuales.version as plan_version_actual_nombre,
							planes_versiones_actuales.nombre as plan_version_actual_descr,
							CASE 
								WHEN (	SELECT 	count(sga_equiv_matrices.matriz)
										FROM 	sga_equiv_matrices
										WHERE	sga_equiv_matrices.plan_version_destino = sga_planes_versiones.plan_version
	        							) > 0
        							THEN 	'".comunes::si_desc."'
        							ELSE	'".comunes::no_desc."'       								
        					END as tiene_matrices
				FROM		sga_propuestas,
							sga_planes
							LEFT JOIN sga_planes_versiones as planes_versiones_actuales ON planes_versiones_actuales.plan_version = sga_planes.version_actual,                           
							sga_planes_estados as planes_estados, 
							sga_planes_versiones,
							sga_planes_estados as planes_versiones_estados
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta AND
							sga_planes.estado = planes_estados.estado AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes_versiones.estado = planes_versiones_estados.estado AND
							sga_planes_versiones.estado <> '" . planes::estado_baja . "'
				$where
				ORDER BY	sga_propuestas.nombre
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las propuestas - planes y versiones de planes de estudio en estado Nuevo, Activo
	 * ó Activo no Vigente, y cuyas propuestas tengan alguna Matriz de Equivalencia asociada.
	 *
	 * @param str $where filtro sobre los planes versiones a recuperar.
	 */
	function get_propuestas_planes_versiones_con_matriz_equivalencia($where)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT  	DISTINCT sga_planes_versiones.plan_version,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.codigo as propuesta_codigo,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descr,
							sga_planes.plan,
							sga_planes.nombre as plan_nombre,
							sga_planes.codigo as plan_codigo,
							planes_estados.estado as plan_estado,
							planes_estados.descripcion as plan_estado_descr,
							sga_planes_versiones.version as plan_version_nombre,
							sga_planes_versiones.nombre as plan_version_descr,
							sga_planes_versiones.fecha_entrada_vigencia,
							planes_versiones_estados.estado as plan_version_estado,
							planes_versiones_estados.descripcion as plan_version_estado_descr,
							sga_planes.version_actual as plan_version_actual,
							planes_versiones_actuales.version as plan_version_actual_nombre,
							planes_versiones_actuales.nombre as plan_version_actual_descr
				
				FROM		sga_propuestas,
							sga_planes
								LEFT JOIN sga_planes_versiones as planes_versiones_actuales ON planes_versiones_actuales.plan_version = sga_planes.version_actual,                           
							sga_planes_estados as planes_estados, 
							sga_equiv_matrices
								LEFT JOIN sga_planes_versiones ON sga_equiv_matrices.plan_version_destino = sga_planes_versiones.plan_version,
							sga_planes_estados as planes_versiones_estados
				
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta AND
							sga_planes.estado = planes_estados.estado AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes_versiones.estado = planes_versiones_estados.estado AND
							sga_planes_versiones.estado <> '".planes::estado_baja."'
							$where
		
				ORDER BY	sga_propuestas.nombre
  				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los diferentes tipos de alcances con los que se puede definir una matriz de equivalencias, para la carga
	 * de un combo.
	 *
	 */
	function get_opciones_combo_alcances_matrices()
	{
		return array(array('valor' => equivalencia::matriz_alcance_general,					'descr' => 'General'),
		             array('valor' => equivalencia::matriz_alcance_entre_propuestas,		'descr' => 'Entre Propuestas'),
		             array('valor' => equivalencia::matriz_alcance_entre_planes_otra_prop,	'descr' => 'Entre Planes de otra Propuesta'),
		             array('valor' => equivalencia::matriz_alcance_entre_planes_misma_prop,	'descr' => 'Entre Planes de la misma Propuesta'),
		             array('valor' => equivalencia::matriz_alcance_entre_versiones,			'descr' => 'Entre Versiones')
		             );
	}
	
	/**
	 * Obtiene los diferentes tipos de equivalencias que pueden definirse para una actividad en el Origen del 
	 * Grupo de Equivalencias.
	 *
	 */
	function get_tipos_equivalencia_origen()
	{
		return array(array('valor' => equivalencia::tipo_regularizada,	'descr' => 'Regularizada'),
		             array('valor' => equivalencia::tipo_aprobada ,		'descr' => 'Aprobada')
		             );
	}
	
	/**
	 * Obtiene los diferentes tipos de equivalencias que pueden definirse para una actividad en el Destino del 
	 * Grupo de Equivalencias.
	 *
	 */
	function get_tipos_equivalencia_destino()
	{
		return array(array('valor' => equivalencia::tipo_regularizada,	'descr' => 'Regularizada'),
		             array('valor' => equivalencia::tipo_total,			'descr' => 'Total'),
		             array('valor' => equivalencia::tipo_parcial,		'descr' => 'Parcial')
		             );
	}
	
	/**
	 * Obtiene, si existen, las equivalencias que se han otorgado a alumnos aplicando algún grupo de los componen la
	 * matriz. Esto se utiliza para poder comprobar si la matriz fue utilizada.
	 *
	 * @param integer $matriz id de la matriz para la cual se recuperan las equivalencias otorgadas.
	 */
	function get_matriz_equivalencias_otorgadas($matriz, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$matriz = toba::db()->quote($matriz);
		
		$sql = "SELECT  sga_equiv_otorgada.equivalencia
				FROM	sga_equiv_grupos,
						sga_equiv_otorgada
				WHERE	sga_equiv_grupos.grupo_equivalencia = sga_equiv_otorgada.grupo_equivalencia
						AND sga_equiv_grupos.matriz = $matriz
						$where
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene, si existen, las equivalencias que se han otorgado a alumnos aplicando el grupo de equivalencia dado. 
	 * Esto se utiliza para poder comprobar si el grupo fue utilizado.
	 *
	 * @param integer $grupo id del grupo para el cual se recuperan las equivalencias otorgadas.
	 */
	function get_grupo_equivalencias_otorgadas($grupo, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$grupo = toba::db()->quote($grupo);
		
		$sql = "SELECT  sga_equiv_otorgada.equivalencia
				FROM	sga_equiv_grupos,
						sga_equiv_otorgada
				WHERE	sga_equiv_grupos.grupo_equivalencia = sga_equiv_otorgada.grupo_equivalencia
						AND sga_equiv_grupos.grupo_equivalencia = $grupo
						$where
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_listado_con_prop_plan_version_origen_y_destino($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT  	sga_equiv_matrices.matriz,
							sga_equiv_matrices.nombre,
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.nro_resolucion,
							sga_equiv_matrices.fecha_entrada_vigencia,
							sga_equiv_matrices.plan_version_destino,
							sga_equiv_matrices.propuesta_origen,
							sga_equiv_matrices.plan_origen,
							sga_equiv_matrices.plan_version_origen,
							sga_equiv_matrices.aplicacion_automatica,
							sga_equiv_matrices.nro_resolucion_baja,
							sga_equiv_matrices.fecha_baja,
							sga_equiv_matrices.activo,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_origen_nombre,
							sga_planes.nombre											  AS plan_origen_nombre,
							'(' || propuestas.codigo || ') ' || propuestas.nombre 		  AS propuesta_destino_nombre,
							planes.nombre												  AS plan_destino_nombre,
							sga_planes_versiones.nombre 								  AS plan_version_destino_nombre,
							plan_version_origen.nombre									  AS plan_version_origen_nombre,
							sga_documentos.documento_numero								  AS nro_resolucion_descr,
							CASE 
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_general."'
									THEN 'General'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_propuestas."'
									THEN 'Entre Propuestas'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_planes_otra_prop."'
									THEN 'Entre Planes de otra Propuesta'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_planes_misma_prop."'
									THEN 'Entre Planes de la misma Propuesta'
								WHEN sga_equiv_matrices.alcance = '".equivalencia::matriz_alcance_entre_versiones."'
									THEN 'Entre Versiones'
							END AS alcance_descr
							
				FROM		sga_equiv_matrices
								LEFT JOIN sga_propuestas								ON sga_equiv_matrices.propuesta_origen = sga_propuestas.propuesta
								LEFT JOIN sga_planes									ON sga_equiv_matrices.plan_origen 		= sga_planes.plan
								LEFT JOIN sga_planes_versiones AS plan_version_origen	ON plan_version_origen.plan_version		= sga_equiv_matrices.plan_version_origen
								LEFT JOIN sga_documentos								ON sga_equiv_matrices.nro_resolucion 	= sga_documentos.documento,
							sga_planes_versiones,
							sga_propuestas AS propuestas,
							sga_planes AS planes
				
				WHERE		sga_equiv_matrices.plan_version_destino = sga_planes_versiones.plan_version
				AND			sga_planes_versiones.plan				= planes.plan
				AND			planes.propuesta						= propuestas.propuesta
							$where
		
				ORDER BY	sga_equiv_matrices.nombre
  				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_listado_matrices_aplicables_otorgar_equivalencia($alumno, $id_persona, $plan_version, $tipo_equiv, $prop_origen=null, $plan_origen=null, $version_origen=null)
	{
		$alumno = toba::db()->quote($alumno);
		$id_persona = toba::db()->quote($id_persona);
		$plan_version = toba::db()->quote($plan_version);
		
		if (!is_null($prop_origen)) {
			$prop_origen = toba::db()->quote($prop_origen);
		}
		if (!is_null($plan_origen)) {
			$plan_origen = toba::db()->quote($plan_origen);
		}
		if (!is_null($version_origen)) {
			$version_origen = toba::db()->quote($version_origen);
		}	
		
		$sql = " -- Matrices de aplicacion Generica, sin origen. se aplican para todo tipo de equivalencia.
				SELECT	sga_equiv_matrices.matriz, 
						sga_equiv_matrices.nombre,
						sga_equiv_matrices.alcance,
						sga_equiv_matrices.aplicacion_automatica,
						CASE 
							WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
						END AS aplicacion_automatica_descr,
						sga_documentos.documento_numero	AS nro_resolucion
				FROM	sga_equiv_matrices
						LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion = sga_documentos.documento
				WHERE	sga_equiv_matrices.plan_version_destino = $plan_version
						AND sga_equiv_matrices.activo = '" . comunes::si . "'
						AND sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_general . "'
				";
		if ($tipo_equiv == equivalencia::origen_int_prop) {
			// Equivalencia interna de Cambio de Propuesta.
			$sql .= "
					-- Matrices con otra propuesta del alumno
					UNION
					SELECT	sga_equiv_matrices.matriz, 
							sga_equiv_matrices.nombre, 
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.aplicacion_automatica,
							CASE 
							WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
							END AS aplicacion_automatica_descr,
							sga_documentos.documento_numero	AS nro_resolucion
					FROM	sga_equiv_matrices
							LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion = sga_documentos.documento,
							sga_alumnos,
							sga_planes_versiones
					WHERE	sga_equiv_matrices.plan_version_destino = $plan_version
							AND sga_equiv_matrices.activo = '". comunes::si . "' 
							AND sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_propuestas . "'
							AND sga_alumnos.persona = $id_persona
							AND sga_alumnos.alumno <> $alumno
							AND sga_alumnos.propuesta = $prop_origen
							AND sga_planes_versiones.plan_version = sga_alumnos.plan_version
							AND sga_equiv_matrices.propuesta_origen = sga_alumnos.propuesta  
							
					-- Contra otra propuesta y plan del alumno
					UNION            
					SELECT	sga_equiv_matrices.matriz, 
							sga_equiv_matrices.nombre, 
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.aplicacion_automatica,
							CASE 
							WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
							END AS aplicacion_automatica_descr,
							sga_documentos.documento_numero	AS nro_resolucion
					FROM	sga_equiv_matrices
							LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion = sga_documentos.documento,
							sga_alumnos, 
							sga_planes_versiones
					WHERE	sga_equiv_matrices.plan_version_destino = $plan_version
							AND sga_equiv_matrices.activo = '" . comunes::si . "' 
							AND sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_planes_otra_prop . "'
							AND sga_alumnos.persona = $id_persona
							AND sga_alumnos.alumno <> $alumno
							AND sga_alumnos.propuesta = $prop_origen
							AND sga_planes_versiones.plan_version = sga_alumnos.plan_version
							AND sga_equiv_matrices.propuesta_origen = sga_alumnos.propuesta
							AND sga_equiv_matrices.plan_origen = sga_planes_versiones.plan  
							AND sga_equiv_matrices.plan_origen = $plan_origen
					";
		} else if ($tipo_equiv == equivalencia::origen_int_plan) {
			$sql .= "-- Cambio de Plan de Estudios
					UNION 
					-- Matrices con el plan anterior del alumno o contra cualquier plan de estudios de la misma propuesta.
					SELECT	sga_equiv_matrices.matriz, 
							sga_equiv_matrices.nombre, 
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.aplicacion_automatica,
							CASE 
							WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
							END AS aplicacion_automatica_descr,
							sga_documentos.documento_numero	AS nro_resolucion
					FROM	sga_equiv_matrices
							LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion = sga_documentos.documento
					WHERE	sga_equiv_matrices.plan_version_destino = $plan_version
							AND sga_equiv_matrices.activo = '" . comunes::si . "' 
							AND sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_planes_misma_prop . "'
							AND sga_equiv_matrices.propuesta_origen = $prop_origen
							AND (sga_equiv_matrices.plan_origen IS NULL OR sga_equiv_matrices.plan_origen = $plan_origen)  
					";
		} else if ($tipo_equiv == equivalencia::origen_int_version) {
			$sql .= "-- Cambio de Version de Plan de Estudios
					-- Matrices contra cualquier otra version de plan de la propuesta o contra la version anterior del plan del alumno
					UNION
					SELECT	sga_equiv_matrices.matriz,
							sga_equiv_matrices.nombre, 
							sga_equiv_matrices.alcance,
							sga_equiv_matrices.aplicacion_automatica,
							CASE 
							WHEN sga_equiv_matrices.aplicacion_automatica = '".comunes::si."'
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
							END AS aplicacion_automatica_descr,
							sga_documentos.documento_numero	AS nro_resolucion
					FROM	sga_equiv_matrices
							LEFT JOIN sga_documentos ON sga_equiv_matrices.nro_resolucion = sga_documentos.documento
					WHERE	sga_equiv_matrices.plan_version_destino = $plan_version
							AND sga_equiv_matrices.activo = '" . comunes::si . "' 
							AND sga_equiv_matrices.alcance = '" . equivalencia::matriz_alcance_entre_versiones . "'
							AND sga_equiv_matrices.propuesta_origen = $prop_origen
							AND sga_equiv_matrices.plan_origen = $plan_origen
							AND (sga_equiv_matrices.plan_version_origen IS NULL OR sga_equiv_matrices.plan_version_origen = $version_origen)
					";
		}
		
		return guarani_db::consultar($sql);
	}
}
?>