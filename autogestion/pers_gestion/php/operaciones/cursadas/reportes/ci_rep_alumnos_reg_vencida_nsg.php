<?php
/**
 *
 * @package Cursadas
 * @subpackage Reportes
 */
class ci_rep_alumnos_reg_vencida_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_insc_cursada';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_con_reg_vencida($filtro);
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}
	
	/**
	 * Redefinición del evento filtrar, para que arme una cadena WHERE acorde con la
	 * nueva estructura de modalidades de cursada de comisión.
	 */
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>