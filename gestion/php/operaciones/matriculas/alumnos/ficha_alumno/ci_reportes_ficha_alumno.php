<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_reportes_ficha_alumno extends guarani_pers_ci
{
	function unset_vars()
	{
		$this->dep('rep_req_ingreso')->unset_vars();
		$this->dep('rep_regularidades')->unset_vars();
		$this->dep('rep_equiv_parciales')->unset_vars();
		$this->dep('rep_inscripciones')->unset_vars();
		$this->dep('rep_hist_acad')->unset_vars();
	}
	
	function conf()
	{
		// Se eliminan los tabs de reportes todavía no implementados		
		$this->pantalla()->eliminar_tab('actividades_x_cursar');
		
		parent::conf();
	}
}

?>