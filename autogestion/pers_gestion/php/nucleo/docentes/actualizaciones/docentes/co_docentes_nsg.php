<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class co_docentes_nsg extends co_docentes
{
	/** Newsol - Se agregaron los datos de responsabilidad docente
        Obtiene una lista de docentes según el filtro recibido
    	*/
	function get_listado($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
	    	$where = ' AND '.$where;		
		}
		
		$sql = "SELECT DISTINCT		sga_docentes.docente,
				  			sga_docentes.legajo,
				  			sga_docentes.persona,"
				  			.guarani_sql::get_case('sga_docentes', 'estado', 'estado_docente').",
				  			vw_personas.apellido 			AS apellido,
				  			vw_personas.nombres 			AS nombres,
				  			vw_personas.apellido_nombres 	AS nombre_persona,
							vw_personas.nombres || ' ' || vw_personas.apellido AS nombre_apellido_persona,
							vw_personas.tipo_nro_documento 	AS documento,
							mdp_personas_sexo.descripcion 	AS sexo,
				   			vw_personas.usuario 			AS usuario,
				   			mdp_nacionalidades.descripcion 	AS nacionalidad,
							sga_docentes.es_docente_externo,
							sga_documentos.documento_numero as resolucion
				FROM 		vw_personas,
							mdp_personas_sexo,
							mdp_nacionalidades,
						sga_docentes
						LEFT JOIN negocio_nsg.sga_docentes_ext as sga_docentes_ext ON sga_docentes_ext.id_docente = sga_docentes.docente
						LEFT JOIN sga_documentos ON sga_docentes_ext.documento = sga_documentos.documento
				WHERE 		sga_docentes.persona = vw_personas.persona AND
							mdp_personas_sexo.sexo 	= vw_personas.sexo AND
							mdp_nacionalidades.nacionalidad = vw_personas.nacionalidad
				$where
				ORDER BY 	vw_personas.apellido,
							vw_personas.nombres
				";
		
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}
	

	/**Newsol (NUEVO) - Obtiene una lista de docentes asignados a comisión según el filtro recibido
    	*/
	function get_listado_docentes_comision($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
	    	$where = ' AND '.$where;		
		}
		
		$sql = "SELECT 		sga_docentes.docente,
				  			sga_docentes.legajo,
				  			sga_docentes.persona,"
				  			.guarani_sql::get_case('sga_docentes', 'estado', 'estado_docente').",
							vw_personas.apellido 			AS apellido,
				  			vw_personas.nombres 			AS nombres,
				  			vw_personas.apellido_nombres 	AS nombre_persona,
							vw_personas.nombres || ' ' || vw_personas.apellido AS nombre_apellido_persona,
							vw_personas.tipo_nro_documento 	AS tipo_nro_documento,
							mdp_personas_sexo.descripcion 	AS sexo,
							(select m.email from mdp_personas_contactos m where vw_personas.persona = m.persona and m.contacto_tipo in ('MP', 'MI') and email is not null limit 1) as email,
				   			vw_personas.usuario 			AS usuario,
				   			mdp_nacionalidades.descripcion 	AS nacionalidad,
							sga_comisiones.comision,
							sga_comisiones.nombre as comision_nombre,
							sga_elementos.nombre as materia_nombre,
							sga_periodos.anio_academico,
							sga_periodos_lectivos.periodo_lectivo,
							sga_periodos.nombre as periodo_nombre,
							sga_propuestas.nombre as propuesta_nombre,
							sga_documentos.documento_numero as resolucion	
				FROM 		vw_personas,
							mdp_personas_sexo,
							mdp_nacionalidades,
							sga_propuestas,
						sga_docentes
						LEFT JOIN negocio_nsg.sga_docentes_ext as sga_docentes_ext ON sga_docentes_ext.id_docente = sga_docentes.docente
						LEFT JOIN sga_documentos ON sga_docentes_ext.documento = sga_documentos.documento
						LEFT JOIN sga_docentes_comision ON sga_docentes.docente = sga_docentes_comision.docente
						JOIN sga_comisiones ON sga_docentes_comision.comision = sga_comisiones.comision
						JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
						JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				WHERE 		sga_docentes.persona = vw_personas.persona AND
							mdp_personas_sexo.sexo 	= vw_personas.sexo AND
							mdp_nacionalidades.nacionalidad = vw_personas.nacionalidad AND
							substr(sga_elementos.codigo,1,length(sga_propuestas.codigo)) = sga_propuestas.codigo				
				$where
				ORDER BY 	vw_personas.apellido,
							vw_personas.nombres
				";
		
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**Newsol (NUEVO) - Reporte que muestra los docentes con sus asignación de responsabilidades
	 */
	function get_listado_docente_resp($where = '')
	{
		if ($where) {
			$where = "AND $where";
		}
	
		$sql = "SELECT 				sga_docentes_resp.docente,
				  			sga_docentes.legajo,
				  			sga_docentes.persona,
				  			vw_personas.apellido 			AS apellido,
				  			vw_personas.nombres 			AS nombres,
				  			vw_personas.apellido_nombres 	AS nombre_persona,
							vw_personas.nombres || ' ' || vw_personas.apellido AS nombre_apellido_persona,
							vw_personas.tipo_nro_documento 	AS tipo_nro_documento,
							sga_docentes_resp.responsabilidad,
							sga_docentes_responsabilidades.nombre as nombre_resp,
							sga_docentes_resp.fecha_desde, 
							sga_docentes_resp.fecha_hasta,
							sga_documentos.documento_numero as resolucion
				FROM 	vw_personas
					JOIN sga_docentes ON sga_docentes.persona = vw_personas.persona
					LEFT JOIN negocio_nsg.sga_docentes_ext as sga_docentes_ext ON sga_docentes_ext.id_docente = sga_docentes.docente
					JOIN sga_docentes_resp ON sga_docentes_resp.docente = sga_docentes.docente
					JOIN sga_docentes_responsabilidades ON sga_docentes_responsabilidades.responsabilidad = sga_docentes_resp.responsabilidad
					LEFT JOIN sga_documentos ON sga_docentes_ext.documento = sga_documentos.documento
				WHERE sga_docentes.estado = 'A'
				$where
				ORDER BY vw_personas.apellido,
						vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
        Valida si un ya existe la combinación docente-responsabilidad .
    	*/
	function validar_si_ya_existe_asignacion($docente, $responsabilidad, $fecha_desde, $fecha_hasta=null)
	{
		if($fecha_desde)
			$fecha_desde = toba::db()->quote($fecha_desde);
		if($fecha_hasta)
			$fecha_hasta = toba::db()->quote($fecha_hasta);

		$sql = 'SELECT	count(*) as cant
                	FROM 	sga_docentes_resp';
	
		$sql .= " WHERE sga_docentes_resp.responsabilidad = $responsabilidad ";
		$sql .= " AND sga_docentes_resp.docente = $docente ";
		$sql .= " AND sga_docentes_resp.fecha_desde = $fecha_desde ";
		
		if ($fecha_hasta)
			$sql .= " AND sga_docentes_resp.fecha_hasta <> $fecha_hasta ";
		else
			$sql .= " AND sga_docentes_resp.fecha_hasta is null ";

		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

}
?>