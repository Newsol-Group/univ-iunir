<?php
class cn_comprobante_inscripcion extends guarani_pers_cn
{
private $sql = "SELECT
   pa.propuesta_aspira, 
   v.apellido || ' ' || v.nombres as nombre_persona,
   v.desc_tipo_documento  || ' ' ||  v.nro_documento as nro_documento,
   p.nombre as propuesta,
   pl.nombre as plan,  
   u.nombre as ubicacion,
   ti.nombre as ingreso,
   mc.nombre as modalidad_cursada,
   c.nombre as convenio,
   pa.fecha_inscripcion,
   pa.nro_transaccion,
   ra.nombre as responsable_academica
   
 FROM sga_propuestas_aspira as pa
 JOIN vw_personas as v ON v.persona = pa.persona
 JOIN sga_propuestas as p ON p.propuesta = pa.propuesta
 JOIN sga_ubicaciones as u ON u.ubicacion = pa.ubicacion
 JOIN sga_planes_versiones as pv ON pv.plan_version = pa.plan_version
 JOIN sga_planes as pl ON pl.plan = pv.plan_version
 JOIN sga_modalidad_cursada as mc ON mc.modalidad = pa.modalidad
 JOIN sga_responsables_academicas AS ra ON TRUE 
 LEFT JOIN sga_tipos_ingreso as ti ON pa.tipo_ingreso = ti.tipo_ingreso
 LEFT JOIN sga_convenios as c ON pa.convenio = c.convenio
 WHERE ra.responsable_academica = get_ra_inst_araucano_propuesta(pa.propuesta, 'RA')
   ";
	
	/*function set_id($id) {
		$this->s__id = $id;
	}*/
	
	function get_listado_inscriptos() {
		//$sql = $this->sql.' limit '.$tam_pagina.' offset '.(($nro_pag-1)*$tam_pagina);
		return guarani_db::consultar($this->sql);
	}
	
	function get_inscripto($id) {
		$id = toba::db()->quote($id);
		$sql = $this->sql.' AND pa.propuesta_aspira ='.$id;
		return guarani_db::consultar($sql);
	}
	
}
  
?>