<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Certificados
 */
class co_certificados
{
 	/**
        Retorna un listado de certificados según el filtro
    */
	function get_listado($where='', $order='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
			$where = "AND $where";
		}
		if (!$order) {
			$order = 'codigo, certificado_tipo, titulo_nivel';
		}
		
		$sql = "SELECT 	sga_certificados.certificado, 
						sga_certificados.nombre, 
						sga_certificados.nombre_femenino,
						sga_certificados.nombre_no_binario,
						sga_certificados.nombre || COALESCE(' (' || sga_titulos_niveles.descripcion || ') ', '') as certificado_descr,
						sga_certificados_tipos.certificado_tipo as certificado_tipo_codigo,
						sga_certificados_tipos.descripcion as certificado_tipo,
						sga_titulos_niveles.descripcion as titulo_nivel,
						sga_certificados.disciplina,
						sga_certificados.titulo_araucano,
						".guarani_sql::get_case('sga_certificados','estado').",
				       	sga_certificados.codigo,
				       	sga_g3entidades.entidad
 				FROM 	sga_certificados
						LEFT JOIN sga_titulos_niveles ON sga_certificados.titulo_nivel = sga_titulos_niveles.titulo_nivel
						LEFT JOIN sga_g3entidades ON sga_certificados.entidad = sga_g3entidades.entidad,
						sga_certificados_tipos
				WHERE 	sga_certificados.certificado_tipo = sga_certificados_tipos.certificado_tipo
  				 		$where
  			ORDER BY 	$order
		"; 
  				 
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}
	
	 /**
        Retorna las tipos de certificados
    */
    function get_certificados_tipos($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
		$sql = "SELECT ct.certificado_tipo as valor, ct.descripcion as descr
  				FROM sga_certificados_tipos ct 
				WHERE ct.descripcion ILIKE $filtro
				";			

		return guarani_db::consultar($sql);
    }
    
    /**
     * Determina si un Tipo de Certificado en particular 
     *
     * @param int $certificado_tipo
     */
    function es_certificado_tipo_titulo($certificado_tipo)
    {
    	$certificado_tipo = toba::db()->quote($certificado_tipo);
    	
    	$sql = "SELECT 
					sga_certificados_tipos.es_titulo
				FROM 
					sga_certificados_tipos
				WHERE
					sga_certificados_tipos.certificado_tipo = $certificado_tipo
				";
    	
    	$rs = guarani_db::consultar_fila($sql);
    	return $rs['es_titulo'] == comunes::si;
    }

	 /**
        Retorna las ramas disciplinares
    */
    function get_ramas_disciplinares($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
		$sql = "SELECT rd.rama_disciplinar as valor,
						ra.nombre || '/' || di.nombre  || '/' || ar.nombre as descr
  				FROM sga_ramas_disciplinares rd, sga_rd_ramas ra, sga_rd_areas ar, sga_rd_disciplinas di 
				WHERE rd.rama = ra.rama 
				AND rd.area = ar.area
				AND rd.disciplina = di.disciplina 
				AND (ra.nombre ILIKE $filtro OR 
					ar.nombre ILIKE $filtro OR 
					di.nombre ILIKE $filtro)
				LIMIT 50
				";			

		return guarani_db::consultar($sql);
    }

	 /**
        Retorna la rama disciplinar
    */
    function get_rama_disciplinar($id=null)
    {
		if (! isset($id) || trim($id) == '') {
					return '';
		}    	

		$id = toba::db()->quote($id);
				
		$sql = "SELECT rd.rama_disciplinar as valor,
						ra.nombre || '/' || di.nombre  || '/' || ar.nombre as descr
  				FROM sga_ramas_disciplinares rd, sga_rd_ramas ra, sga_rd_areas ar, sga_rd_disciplinas di 
				WHERE rd.rama_disciplinar = $id 
				AND rd.rama = ra.rama 
				AND rd.area = ar.area
				AND rd.disciplina = di.disciplina 
				";			

		$result = guarani_db::consultar($sql);
		
		if (! empty($result)) {
			return $result[0]['descr'];
		}

    }

	 /**
        Retorna los tipos de títulos
    */
    function get_titulos_tipos($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
		$sql = "SELECT tt.titulo_tipo as valor, tt.descripcion as descr
  				FROM sga_titulos_tipos tt 
				WHERE tt.descripcion ILIKE $filtro
				";			

		return guarani_db::consultar($sql);
    }

	 /**
        Retorna los niveles de títulos
    */
    function get_titulos_niveles($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
		$sql = "SELECT tt.titulo_nivel as valor, tt.descripcion as descr
  				FROM sga_titulos_niveles tt 
				WHERE tt.descripcion ILIKE $filtro
				";			

		return guarani_db::consultar($sql);
    }

	 /**
        Retorna los niveles de títulos
    */
    function get_titulos_categ_coneau($filtro = '')
    {
    	$filtro = toba::db()->quote("%$filtro%");
    	
		$sql = "SELECT cc.categorizacion_coneau as valor, cc.descripcion as descr
  				FROM sga_titulos_categ_coneau cc 
				WHERE cc.descripcion ILIKE $filtro
				";			

		return guarani_db::consultar($sql);
    }

    function get_disciplinas()
    {
		$sql = "SELECT 	sga_rd_disciplinas.disciplina as 	valor,
						sga_rd_disciplinas.nombre as 		descr
  				FROM 	sga_rd_disciplinas 
  				ORDER BY 2";

    		return guarani_db::consultar($sql);
    	
    }

	/**
	 * Retorna el listado de títulos SIU-Araucano teniendo en cuenta el filtro (por código o nombre) de un combo editable.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_int_arau_titulos($filtro='')
	{
		$where = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$where = ' WHERE ('.$filtro_condicion->get_sql('f_limpiar_acentos(int_arau_titulos.nombre::varchar)', $filtro);
			$where .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(int_arau_titulos.titulo_araucano::varchar)', $filtro).')';
		}
		$sql = "SELECT		int_arau_titulos.titulo_araucano as valor,
							int_arau_titulos.nombre || ' (' || int_arau_titulos.titulo_araucano || ')' as descr
				FROM		int_arau_titulos
				$where		
				ORDER BY	2
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el nombre de un título SIU-Araucano cuyo identificador se da como parámetro,
	 * retornando el string para el método descripción de un combo editable.
	 * 
	 * @param int $titulo_araucano
	 * @return string
	 */
	static function get_descripcion_int_arau_titulo($titulo_araucano=null)
	{
		if (!isset($titulo_araucano)) {
			return '';
		}
		$titulo_araucano = toba::db()->quote($titulo_araucano);
		$sql = "SELECT	int_arau_titulos.titulo_araucano as valor,
						int_arau_titulos.nombre || ' (' || int_arau_titulos.titulo_araucano || ')' as descr
				FROM	int_arau_titulos
				WHERE	int_arau_titulos.titulo_araucano = $titulo_araucano
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['descr'];
	}

	function get_certificados()
	{
		$sql = "SELECT		sga_certificados.certificado as valor,
							'(' || sga_certificados.codigo || ') ' || sga_certificados.nombre as descr,
							sga_certificados.nombre
				FROM		sga_certificados
				ORDER BY	sga_certificados.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_certificado($certificado, $filtrar_perfil_de_datos=true)
	{
		if(! isset($certificado)) return false;
		
		$certificado = toba::db()->quote($certificado);
		
		$sql = "SELECT certificado, 
						nombre, 
						nombre_femenino,
						nombre_no_binario,
						codigo, 
						certificado_tipo, 
       					titulo_araucano, 
       					disciplina, 
       					estado,
       					entidad,
       					circuito,
       					titulo_nivel,
						'(' || codigo || ') ' || nombre AS nombre_codigo
  				FROM sga_certificados
  				WHERE certificado = $certificado";
  				
		return guarani_db::consultar_fila($sql, null, $filtrar_perfil_de_datos);	  				

	}
	
 	/**
        Certificados asignados a un Plan_version
    */
	function get_certificados_plan_version($plan_version, $where='', $asignados=true, $filtrar_perfil_de_datos=true)
	{
		$plan_version = toba::db()->quote($plan_version);
		if ($where) {
			$where = "AND $where";
		}
		$not = '';
		if (! $asignados) {
			$not = 'NOT ';
		}
		$where = "	$not EXISTS (	SELECT	1 
									FROM 	sga_planes_certificados 
									WHERE	sga_planes_certificados.plan_version = $plan_version AND
											sga_planes_certificados.certificado = sga_certificados.certificado)
					$where
		";
		return $this->get_listado($where, '', $filtrar_perfil_de_datos);
	}

	/**
	 * Certificados que el alumno aún no ha tramitado
	 */
	function get_certificados_no_tramitados_por_alumno($plan_version, $alumno)
	{
		$plan_version = toba::db()->quote($plan_version);
		$alumno	= toba::db()->quote($alumno);
        $sql = "-- Listado de certificaciones del plan de estudios aun no solictadas por el alumno
				SELECT		sga_certificados.certificado as certificado,
							CASE mdp_personas.sexo
								WHEN '".persona::sexo_femenino."' THEN COALESCE(sga_certificados.nombre_femenino, sga_certificados.nombre)
								WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_certificados.nombre_no_binario, sga_certificados.nombre)
								ELSE sga_certificados.nombre								
							END as nombre,
							'(' || sga_certificados.codigo || ') ' || sga_certificados.nombre AS nombre_codigo
				FROM		sga_planes_certificados
							JOIN sga_certificados ON sga_planes_certificados.certificado = sga_certificados.certificado
							JOIN sga_alumnos ON sga_alumnos.alumno = $alumno
							JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
				 WHERE		sga_planes_certificados.plan_version = $plan_version AND
							NOT EXISTS (SELECT	1
										FROM	sga_certificados_otorg
										WHERE	sga_certificados_otorg.alumno = sga_alumnos.alumno AND
												sga_certificados_otorg.certificado = sga_certificados.certificado AND
												sga_certificados_otorg.anulado = 0) AND
							sga_certificados.circuito IS NOT NULL
				ORDER BY	nombre
        ";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna la lista de certificados asociados a un plan-versión y de un tipo de certificado en particular.
	 *
	 * @param integer $plan_version
	 * @param integer $certificado_tipo
	 * @return array
	 */
	function get_certificados_plan_version_tipo($plan_version, $certificado_tipo)
	{
		$plan_version = toba::db()->quote($plan_version);
		$certificado_tipo = toba::db()->quote($certificado_tipo);
		$sql = "SELECT	sga_certificados.certificado as valor,
						sga_certificados.nombre as descr
				FROM	sga_certificados,
						sga_planes_certificados,
						sga_condiciones
				WHERE	sga_certificados.certificado = sga_planes_certificados.certificado AND
						sga_certificados.entidad = sga_condiciones.entidad AND
						sga_planes_certificados.plan_version = sga_condiciones.plan_version AND
						sga_condiciones.condicion_tipo = '".condicion::tipo_certificado."' AND
						sga_planes_certificados.plan_version = $plan_version AND
						sga_certificados.certificado_tipo = $certificado_tipo
		";
		return guarani_db::consultar($sql);
	}

 	/**
        Certificados no asignados a un Plan_version
    */
	function get_certificados_disponibles_plan_version($plan_version,$where='')
	{
		return $this->get_certificados_plan_version($plan_version,$where,false);
	}

	/**
	 * Retorna la lista de actividades que intervienen en el cumplimiento de un certificado.
	 * Busca en profundidad todas las actividades de los módulos.
	 *
	 * @param int $certificado
	 * @param int $plan_version
	 * @param string $where
	 * @return array
	 */
	function get_actividades_certificado_plan_version($certificado, $plan_version, $where='')
	{
		$certificado = toba::db()->quote($certificado);
		$plan_version = toba::db()->quote($plan_version);
		
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$sql = "SELECT	sga_elementos.elemento,
						sga_elementos.codigo,
						sga_elementos.nombre,
						sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre
				FROM	get_actividades_certificado_plan_version($certificado, $plan_version),
						sga_elementos,
						sga_g3entidades,
						sga_g3entidades_subtipos
				WHERE	get_actividades_certificado_plan_version = sga_elementos.elemento AND
						sga_elementos.entidad = sga_g3entidades.entidad AND
						sga_g3entidades.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
						$where
		";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todos los certificados de un determinado tipo.
	 *
	 */
	function get_certificados_por_tipo($certificado_tipo)
	{
		$certificado_tipo = toba::db()->quote($certificado_tipo);
		
		$sql = "SELECT 
					sga_certificados.certificado AS valor,
					sga_certificados.nombre AS descr
				FROM 
					sga_certificados
				WHERE
					sga_certificados.certificado_tipo = $certificado_tipo
				";
		
		return guarani_db::consultar($sql);
	}

 	/**
        Valida si un código se puede usar o no
    */
	function validar_codigo_certificado($codigo,$certificado=null)
	{
		$codigo = toba::db()->quote($codigo);
		if (isset($certificado)) $certificado = toba::db()->quote($certificado);
		$sql = "SELECT 	count(*) as cant 	
                FROM 	sga_certificados
				WHERE 	sga_certificados.codigo = $codigo
				";
		if (isset($certificado)) $sql .= " AND sga_certificados.certificado <> $certificado ";
		$res = guarani_db::consultar_fila($sql);
		return ( $res['cant'] == 0);
	}
	
	/**
	 * Obtiene un listado de alcances según filtro especificado.
	 *
	 */
	function get_listado_alcances($where='')
	{
		if ($where) {
			$where 	= 'WHERE '.$where;
		}
		
		$sql = "SELECT 		sga_alcances.alcance,
							sga_alcances.nombre,
							sga_alcances.activo
				FROM 		sga_alcances
				$where
				ORDER BY	sga_alcances.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de alcances según filtro especificado.
	 *
	 */
	function get_listado_alcances_activos($where='')
	{
		if ($where) {
			$where 	.= ' AND ';
		}
		
		$si = toba::db()->quote(comunes::si);
		$where .= "sga_alcances.activo = $si";		
		
		return $this->get_listado_alcances($where);
	}
	
	/**
	 * Obtiene un listado de alcances asociados a un certificado específico.
	 *
	 */
	function get_alcances_x_certif($certificado, $where='')
	{
		$certificado = toba::db()->quote($certificado);
		
		if ($where) {
			$where 	= ' AND '.$where;
		}
		
		$sql = "SELECT 
					sga_certificados_alcances.certificado,
					sga_certificados_alcances.alcance,
				    sga_alcances.nombre as alcance_descr
				FROM 
					sga_certificados_alcances,
				    sga_alcances
				WHERE
					sga_certificados_alcances.certificado = $certificado
					AND sga_alcances.alcance = sga_certificados_alcances.alcance
					$where
				ORDER BY
					sga_alcances.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Valida que un nombre de alcance no se esté usando ya en el sistema
	 *
	 * @param string $nombre
	 * @param int	 $alcance
	 */
	function validar_nombre_alcance($nombre, $alcance) 
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_alcances.alcance) as cant
                FROM 	sga_alcances
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_alcances.nombre', $nombre).'
				';
		
		if (!is_null($alcance)) {
			$alcance = toba::db()->quote($alcance);
			$sql .= " AND sga_alcances.alcance <> $alcance ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
	 * Obtiene un listado de escalas de cumplimiento según filtro especificado.
	 *
	 */
	function get_listado_escalas_cumplimiento($where='')
	{		
		if ($where) {
			$where 	= ' WHERE '.$where;
		}
		
		$sql = "SELECT 
					sga_escalas_cumplimiento.escala_cumplimiento,
					sga_escalas_cumplimiento.nombre,
					sga_escalas_cumplimiento.activo
				FROM 
					sga_escalas_cumplimiento
				$where
				ORDER BY
					sga_escalas_cumplimiento.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de Escala de Cumplimiento se puede usar o no.
     */
	function validar_nombre_escala_cumplimiento($nombre, $clave=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(1) as cant
                FROM 	sga_escalas_cumplimiento
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_escalas_cumplimiento.nombre', $nombre).'
				';
		
		if (!is_null($clave)) {
			$clave = toba::db()->quote($clave);
			$sql .= " AND sga_escalas_cumplimiento.escala_cumplimiento <> $clave ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
	
	function get_escalas_cumplimiento_por_campo($campo_disciplinar, $where='')
	{
		$campo_disciplinar = toba::db()->quote($campo_disciplinar);
		
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$sql = "SELECT 
					sga_escalas_cumplimiento.escala_cumplimiento AS valor,
					sga_escalas_cumplimiento.nombre AS descr
				FROM 
					sga_escalas_cumplimiento,
					sga_escalas_cumplimiento_campo
				WHERE
					sga_escalas_cumplimiento_campo.escala_cumplimiento = sga_escalas_cumplimiento.escala_cumplimiento
				    AND sga_escalas_cumplimiento_campo.campo_disciplinar = $campo_disciplinar
					$where
				ORDER BY
					sga_escalas_cumplimiento.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_escalas_cumplimiento_activas_por_campo($campo_disciplinar)
	{
		$where = "sga_escalas_cumplimiento.activo = '".comunes::si."'";		
		return $this->get_escalas_cumplimiento_por_campo($campo_disciplinar, $where);
	}
	
	function get_escalas_cumplimiento_det_por_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		
		$sql = "SELECT 
					sga_escalas_cumplimiento_det.nivel_escala AS valor,
				    sga_escalas_cumplimiento_det.codigo AS descr
				
				FROM 
					sga_planes_versiones,
				    sga_planes,
				    sga_propuestas,
					sga_escalas_cumplimiento_det			
				    
				WHERE 
					sga_planes_versiones.plan_version = $plan_version
				    AND sga_planes.plan = sga_planes_versiones.plan
				    AND sga_propuestas.propuesta = sga_planes.propuesta
				    AND sga_escalas_cumplimiento_det.escala_cumplimiento = sga_propuestas.escala_cumplimiento
				    
				ORDER BY
					sga_escalas_cumplimiento_det.nivel_escala
			";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos del conjunto de orientaciones asociadas al cumplimiento de un certificado en un plan-versión.
	 *
	 * @param integer $certificado
	 * @param integer $plan_version
	 * @return array
	 */
	function get_orientaciones_certificado($certificado, $plan_version)
	{
		$certificado = toba::db()->quote($certificado);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	DISTINCT get_orientaciones_certificado.elemento,
						sga_elementos_revision.regla,
						sga_elementos_revision.parametros
				FROM	get_orientaciones_certificado($certificado, $plan_version),
						sga_elementos_revision
				WHERE	get_orientaciones_certificado.elemento_revision = sga_elementos_revision.elemento_revision
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos del certificado al que corresponde una entidad dada.
	 * 
	 * @param integer $entidad
	 * @return array
	 */
	function get_certificado_por_entidad($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		$sql = "SELECT	sga_certificados.certificado,
						sga_certificados.nombre
				FROM	sga_certificados
				WHERE	sga_certificados.entidad = $entidad
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el listado de certificados asociados a un conjunto de propuestas.
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	function get_certificados_por_propuestas($propuestas)
	{
		$where = '';
		if (!empty($propuestas)) {
			$where = ' AND ' . guarani_sql::armar_condicion_in($propuestas, 'sga_planes', 'propuesta');
		}
		$sql = "SELECT		DISTINCT sga_certificados.certificado,
							sga_certificados.nombre || COALESCE(' (' || sga_titulos_niveles.descripcion || ') ', '') as certificado_descr
				FROM		sga_certificados
							LEFT JOIN sga_titulos_niveles ON sga_certificados.titulo_nivel = sga_titulos_niveles.titulo_nivel,
							sga_planes_certificados,
							sga_planes_versiones,
							sga_planes
				WHERE		sga_certificados.certificado = sga_planes_certificados.certificado AND
							sga_planes_certificados.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan
							$where
				ORDER BY	certificado_descr
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de certificados asociados a un conjunto de responsables académicas.
	 * 
	 * @param array $responsables_academicas
	 * @return array
	 */
	function get_certificados_por_responsables_academicas($responsables_academicas)
	{
		$where = '';
		if (!empty($responsables_academicas)) {
			$where = ' AND ' . guarani_sql::armar_condicion_in($responsables_academicas, 'sga_propuestas_ra', 'responsable_academica');
		}
		$sql = "SELECT		DISTINCT sga_certificados.certificado,
							sga_certificados.nombre || COALESCE(' (' || sga_titulos_niveles.descripcion || ') ', '') as certificado_descr
				FROM		sga_certificados
							LEFT JOIN sga_titulos_niveles ON sga_certificados.titulo_nivel = sga_titulos_niveles.titulo_nivel,
							sga_planes_certificados,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas_ra
				WHERE		sga_certificados.certificado = sga_planes_certificados.certificado AND
							sga_planes_certificados.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas_ra.propuesta
							$where
				ORDER BY	certificado_descr
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de certificados asociados a un conjunto de tipos de propuesta.
	 * 
	 * @param array $tipos_propuesta
	 * @return array
	 */
	function get_certificados_por_tipos_propuesta($tipos_propuesta)
	{
		$where = '';
		if (!empty($tipos_propuesta)) {
			$where = ' AND ' . guarani_sql::armar_condicion_in($tipos_propuesta, 'sga_propuestas', 'propuesta_tipo');
		}
		$sql = "SELECT		DISTINCT sga_certificados.certificado,
							sga_certificados.nombre || COALESCE(' (' || sga_titulos_niveles.descripcion || ') ', '') as certificado_descr
				FROM		sga_certificados
							LEFT JOIN sga_titulos_niveles ON sga_certificados.titulo_nivel = sga_titulos_niveles.titulo_nivel,
							sga_planes_certificados,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas
				WHERE		sga_certificados.certificado = sga_planes_certificados.certificado AND
							sga_planes_certificados.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas.propuesta
							$where
				ORDER BY	certificado_descr
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de certificados asociados a un alumno.
	 * 
	 * @param int $alumno ID del alumno
	 * @return array
	 */
	function get_certificados_por_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$sql = "-- Listado de certificaciones solicitadas por el alumno y su estado actual.
				SELECT	sga_certificados.nombre AS certificado,
						CASE mdp_personas.sexo
							WHEN '".persona::sexo_femenino."' THEN COALESCE(sga_certificados.nombre_femenino, sga_certificados.nombre)
							WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_certificados.nombre_no_binario, sga_certificados.nombre)
							ELSE sga_certificados.nombre
						END as certificado_nombre,
						sga_certificados_otorg.nro_solicitud,
						to_char(sga_certificados_otorg.fecha_inicio_tramite, $formato_fecha_visual) AS fecha_inicio_tramite,
						mce_estados.nombre AS estado_actual_nombre,
						to_char(sga_certificados_otorg.fecha_cambio_estado, $formato_fecha_visual) AS fecha_cambio_estado,
						sga_certificados_otorg.estado_requisitos,
						CASE sga_certificados_otorg.estado_requisitos
							WHEN '".tramite_certificacion::estado_requisitos_abierto."' THEN '".tramite_certificacion::estado_requisitos_abierto_descr."'
							WHEN '".tramite_certificacion::estado_requisitos_pendiente."' THEN '".tramite_certificacion::estado_requisitos_pendiente_descr."'
							WHEN '".tramite_certificacion::estado_requisitos_aceptado."' THEN '".tramite_certificacion::estado_requisitos_aceptado_descr."'
						END AS estado_requisitos_descr,
						(
							SELECT COUNT(1)
							FROM sga_requisitos_certificado
							WHERE sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud
						) AS cant_requistos_requeridos
				FROM	sga_certificados_otorg
						JOIN sga_certificados ON sga_certificados_otorg.certificado = sga_certificados.certificado
						LEFT JOIN mce_estados ON sga_certificados_otorg.estado = mce_estados.estado
						JOIN mdp_personas ON sga_certificados_otorg.persona = mdp_personas.persona
				WHERE	sga_certificados_otorg.alumno = $alumno AND
						sga_certificados_otorg.anulado = 0
		";
		return guarani_db::consultar($sql, null, false);
	}
	
	function existe_certificado($certificado)
	{
		$sql = "
				SELECT
					1
				FROM
					sga_certificados
				WHERE
					certificado = $certificado
				";
		return toba::db()->hay_datos($sql);
	}

    function get_resolucion_tipos()
    {
		return array(	array('valor' => planes::resolucion_tipo_disposicion_spu, 'descr' => planes::resolucion_tipo_disposicion_spu_descr),
						array('valor' => planes::resolucion_tipo_coneau, 'descr' => planes::resolucion_tipo_coneau_descr),
						array('valor' => planes::resolucion_tipo_ministerial, 'descr' => planes::resolucion_tipo_ministerial_descr),
						array('valor' => planes::resolucion_tipo_spu, 'descr' => planes::resolucion_tipo_spu_descr),
					);
	}
    
    /*
     * Estados para la operacion de diploma y analitico digital.
     */
    function get_estados_digital()
    {
        return [
			['valor' => sidcer::ESTADO_NO_GENERADO, 'descr' => sidcer::ESTADO_NO_GENERADO_DESC],
			['valor' => sidcer::ESTADO_PENDIENTE_FIRMA, 'descr' => sidcer::ESTADO_PENDIENTE_FIRMA_DESC],
			['valor' => sidcer::ESTADO_FIRMADO, 'descr' => sidcer::ESTADO_FIRMADO_DESC],
			['valor' => sidcer::ESTADO_ENVIADO_SIDCER, 'descr' => sidcer::ESTADO_ENVIADO_SIDCER_DESC]
        ];
    }

}