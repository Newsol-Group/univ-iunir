<?php 
class ci_edi_plan_correlativas extends ci_edi_plan_condiciones
{

	function evt__cancelar()
	{
		$this->dep('correlativas')->evt__cancelar();
		$this->dep('editor_copia')->unset_vars();
		parent::evt__cancelar();
	}
}

?>