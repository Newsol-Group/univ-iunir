<?php
class ci_nav_propuestas_formativas extends ci_navegacion
{
	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_ra = '';
			$filtro_ubicacion = '';
			if (isset($datos['responsable_academica'])) {
				$responsable_academica = toba::db()->quote($datos['responsable_academica']['valor']);
				$filtro_ra = ' AND sga_propuestas.propuesta IN ';
				$filtro_ra .= "(SELECT 	sga_propuestas_ra.propuesta
								FROM 	sga_propuestas_ra
								WHERE	sga_propuestas_ra.responsable_academica = $responsable_academica)";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']['valor']);
				$filtro_ubicacion = ' AND sga_propuestas.propuesta IN ';
				$filtro_ubicacion .= "(	SELECT 	sga_propuestas_oferta.propuesta
										FROM 	sga_propuestas_oferta
										WHERE	sga_propuestas_oferta.ubicacion = $ubicacion)";
				$this->dep('filtro')->columna('ubicacion')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_ra $filtro_ubicacion";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}