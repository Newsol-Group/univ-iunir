<?php
/**
 *
 *
 * @package		Egresados
 * @subpackage	Actualizaciones
 * 
 */
class cn_ent_anular_tramite_certificacion extends cn_entidad
{
	
	//------------------------------------------------------------------------
	//-- Trámite de Certificado
	//------------------------------------------------------------------------
 
	function get_datos_tramite()
	{
		return $this->tabla('certificado_otorg')->get();
	}
	
	function set_datos_tramite($datos)
	{
		$datos['fecha_anulacion'] = guarani_fecha::get_hoy();
		$datos['anulado'] = '1';
		$this->tabla('certificado_otorg')->set($datos);
	}
}
?>