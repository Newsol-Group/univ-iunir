<?php
class ci_rep_comisiones_relacionadas_a_divisiones extends ci_rep_sin_listado_inicial
{
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}

	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_comisiones')->get_reporte_comisiones_relacionadas_a_divisiones($filtro);
	}

	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_sin_division = '';
			$filtro_modalidad = '';
			if (isset($datos['division']) && ($datos['division']['valor'] == 'N')) {
				// Condición para el caso de que se deseen traer comisiones sin división asignada.
				$filtro_sin_division .= $datos['division']['condicion'] == 'es_igual_a' ? " AND sga_comisiones.division IS NULL" : " AND sga_comisiones.division IS NOT NULL";
				$this->dep('filtro')->columna('division')->resetear_estado();
			}

			if (isset($datos['modalidad'])) {

				//Debe existir alguna comisión con la modalidad seleccionada
				$filtro_modalidad .= " AND EXISTS (	SELECT	*
													FROM	sga_comisiones_modalidad
													WHERE	sga_comisiones_modalidad.comision  = sga_comisiones.comision
													AND		sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}

			$this->s__filtro = $this->dep('filtro')->get_sql_where() . $filtro_sin_division . $filtro_modalidad;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

}
?>