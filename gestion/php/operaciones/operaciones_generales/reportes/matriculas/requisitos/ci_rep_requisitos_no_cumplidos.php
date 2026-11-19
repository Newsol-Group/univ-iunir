<?php
class ci_rep_requisitos_no_cumplidos extends ci_impresion_popup
{
	protected $s__parametro = null;
			
	function ini()
	{
		parent::ini();
		if (empty($this->s__parametro)) {
			$this->s__parametro = toba::memoria()->get_parametro('propuesta_aspira');			
		}
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_propuestas')->get_requisitos_no_cumplidos($this->s__parametro);
		$cuadro->set_datos($datos);
	}
}

?>