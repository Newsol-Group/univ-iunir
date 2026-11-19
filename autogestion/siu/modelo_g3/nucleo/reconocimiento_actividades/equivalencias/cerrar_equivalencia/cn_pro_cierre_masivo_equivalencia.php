<?php
class cn_pro_cierre_masivo_equivalencia extends cn_procesos_bk 
{
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_cierre_masivo_equivalencia::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_equiv_otorgada');
		} else {
			parent::etiquetas($dep);
		}
	}
	
	function get_listado($filtro)
	{
		$where = "sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "'";
		$where .= " AND sga_equiv_tramite.tipo_tramite = '" . equivalencia::tipo_tramite_normal . "'";
		if (isset($filtro) && trim($filtro) != '') {
			$where .= ' AND ' . $filtro;
		}
		return toba::consulta_php('co_equivalencias')->get_listado_tramites($where);
	}
	
	function cargar_atributos_parametros($propuesta)
	{
		$operacion = guarani::get_id_operacion();
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
	}
}

?>