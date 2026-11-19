<?php
/**
 *
 *
 * @package Unidades de Gestión
 * @subpackage Convenios
 */
class ci_nav_convenios extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('institucion')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_institucion = '';
			if (isset($datos['institucion'])) {
				$filtro_institucion .= " AND EXISTS (SELECT * FROM sga_convenios_instituciones WHERE sga_convenios_instituciones.convenio = sga_convenios.convenio AND sga_convenios_instituciones.institucion = '{$datos['institucion']['valor']}')";
				$this->dep('filtro')->columna('institucion')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_institucion";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>