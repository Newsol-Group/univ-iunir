<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_reportes_ficha_alumno_nsg extends ci_reportes_ficha_alumno
{
	function unset_vars()
	{
		$this->dep('rep_regularidades')->unset_vars();
		$this->dep('rep_equiv_parciales')->unset_vars();
		$this->dep('rep_inscripciones')->unset_vars();
		$this->dep('rep_hist_acad')->unset_vars();
		$this->dep('rep_log_inscripciones')->unset_vars();
		$this->dep('rep_req_ingreso')->unset_vars();

	}
}	

?>