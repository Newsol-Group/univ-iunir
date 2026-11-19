<?php
class ci_nav_docentes extends ci_nav_sin_listado_inicial
{
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['roles'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_docentes_resp WHERE sga_docentes.docente = sga_docentes_resp.docente AND sga_docentes_resp.responsabilidad = '{$datos['roles']['valor']}')";
				$this->dep('filtro')->columna('roles')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

}