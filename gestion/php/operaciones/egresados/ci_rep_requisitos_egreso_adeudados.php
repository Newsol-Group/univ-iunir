<?php
/**
 *
 * @package		Egresados
 * @subpackage	Reportes
 */
class ci_rep_requisitos_egreso_adeudados extends ci_impresion_popup
{
	protected $s__parametro = null;
		
	function ini()
	{
		parent::ini();
		if (empty($this->s__parametro)) {
			$this->s__parametro = toba::memoria()->get_parametro('nro_solicitud');
		}
		$filtro = "sga_certificados_otorg.nro_solicitud = {$this->s__parametro}";
		$datos = current(toba::consulta_php('co_tramites_certificacion')->get_listado($filtro));
		$this->ev_info()->set_datos($datos);
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_tramites_certificacion')->get_requisitos_egreso_adeudados($this->s__parametro);
		$cuadro->set_datos($datos);
	}

    function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombres', 'certificado_nombre'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('70px');
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno:');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado:');
		}
		return $this->ev_info;
	}
}