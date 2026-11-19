<?php
class co_casos_testeo
{
	function get_listado($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 	ct_plan.caso_testeo, 
						ct_plan.plan_version, 
						ct_plan.nombre, 
						sga_planes.nombre||' - '||sga_planes_versiones.nombre as pv_nombre, 
						coalesce('('||sga_planes.codigo||') ','')||sga_planes.nombre as plan,
						coalesce('('||sga_planes_versiones.version||') ','')||sga_planes_versiones.nombre as version,
						coalesce('('||sga_propuestas.codigo||') ','')||sga_propuestas.nombre as propuesta,
						ct_plan.descripcion
				FROM	ct_plan,
						sga_planes_versiones,
						sga_planes,
						sga_propuestas 
				WHERE	sga_planes_versiones.plan_version = ct_plan.plan_version AND
						sga_planes.plan = sga_planes_versiones.plan AND
						sga_planes.propuesta = sga_propuestas.propuesta
						$where
		";
		return guarani_db::consultar($sql);
	}

	function get_datos_caso_testeo($caso_testeo)
	{
		if (is_array($caso_testeo)) {
			$filtro = guarani_sql::armar_condicion_in($caso_testeo, 'ct_plan', 'caso_testeo');
		} else {
			$caso_testeo = toba::db()->quote($caso_testeo);
			$filtro = "ct_plan.caso_testeo = $caso_testeo";
		}
		$sql = "SELECT	ct_plan.caso_testeo,
						ct_plan.nombre
				FROM	ct_plan
				WHERE	$filtro
		";
		return guarani_db::consultar_fila($sql);
	}

	function get_resultado_caso_testeo_certificado($caso_testeo, $certificado) 
	{
		$caso_testeo = toba::db()->quote($caso_testeo);
		$certificado = toba::db()->quote($certificado);
		$sql = "SELECT	ct_resultados_esperados.resultado_test,
						ct_resultados_esperados.cumplido,
						ct_resultados_esperados.observaciones
				FROM	ct_resultados_esperados
				WHERE	ct_resultados_esperados.caso_testeo = $caso_testeo AND
						ct_resultados_esperados.certificado = $certificado
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_test($plan_version=false, $join_ct=true, $join_cert=false)
	{
		$sql = "SELECT DISTINCT	v.plan_version,
								coalesce('('||pr.codigo||') ','')||pr.nombre||' | '||coalesce('('||p.codigo||') ','')||p.nombre||' | '||coalesce('('||v.version||') ','')||v.nombre as pv_nombre
				FROM			sga_planes_versiones v 
								INNER JOIN sga_planes p ON p.plan = v.plan
								INNER JOIN sga_propuestas pr ON pr.propuesta = p.propuesta
		";
		if ($join_ct) {
			$sql .= " INNER JOIN ct_plan c ON c.plan_version = v.plan_version";
		}
		if ($join_cert) {
			$sql .= " INNER JOIN sga_planes_certificados pc ON pc.plan_version = v.plan_version";
		}
		if ($plan_version) {
			$plan_version = toba::db()->quote($plan_version);
			$sql .= " WHERE v.plan_version = $plan_version";
		}
		return guarani_db::consultar($sql);
	}

	function get_planes_test_cert($plan_version = false) 
	{
		return $this->get_planes_test($plan_version, true, true);
	}
	
	function get_planes_test_sin_join($plan_version = false)
	{
		return $this->get_planes_test($plan_version, false);
	}

	function get_contenido_plan($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	get_plan_contenido.elemento as id_nodo,
						get_plan_contenido.nombre,
						$plan_version as plan_version,
						get_plan_contenido.elemento_padre as nodo_padre,
						get_plan_contenido.entidad_tipo,
						get_plan_contenido.orden
				FROM	get_plan_contenido($plan_version, true, true)
		";
		return guarani_db::consultar($sql);
	}

	function actividad_aprobada_caso_testeo($actividad, $caso_testeo)
	{
		$actividad = toba::db()->quote($actividad);
		$caso_testeo = toba::db()->quote($caso_testeo);
		$sql = "SELECT	ct_plan_actividades.aprobada
				FROM	ct_plan_actividades
				WHERE	ct_plan_actividades.elemento = $actividad AND
						ct_plan_actividades.caso_testeo = $caso_testeo
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['aprobada'] == comunes::si) ? true : false;
	}

	function actividad_cursada_caso_testeo($actividad, $caso_testeo)
	{
		$actividad = toba::db()->quote($actividad);
		$caso_testeo = toba::db()->quote($caso_testeo);
		$sql = "SELECT	ct_plan_actividades.cursada
				FROM	ct_plan_actividades
				WHERE	ct_plan_actividades.elemento = $actividad AND
						ct_plan_actividades.caso_testeo = $caso_testeo
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cursada'] == comunes::si) ? true : false;
	}

	function actividad_inscripto_cursada_caso_testeo($actividad, $caso_testeo)
	{
		$actividad = toba::db()->quote($actividad);
		$caso_testeo = toba::db()->quote($caso_testeo);
		$sql = "SELECT	ct_plan_actividades.inscripto_cursada
				FROM	ct_plan_actividades
				WHERE	ct_plan_actividades.elemento = $actividad AND
						ct_plan_actividades.caso_testeo = $caso_testeo
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['inscripto_cursada'] == comunes::si) ? true : false;
	}
}
?>