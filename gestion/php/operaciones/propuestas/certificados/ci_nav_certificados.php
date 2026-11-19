<?php
/**
 *
 *
 * @package     Propuestas Formativas
 * @subpackage  Certificados
 * 
 */
class ci_nav_certificados extends ci_navegacion 
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = '';
			if ((isset($datos['responsable_academica']))) {
				$ra = toba::db()->quote($datos['responsable_academica']['valor']);
				$filtro = " AND EXISTS(	SELECT 	1 
										FROM 	sga_certificados_ra
										WHERE	sga_certificados_ra.certificado = sga_certificados.certificado
												AND sga_certificados_ra.responsable_academica = $ra)";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

}

?>