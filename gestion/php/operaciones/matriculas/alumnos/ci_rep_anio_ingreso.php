<?php
class ci_rep_anio_ingreso extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$order = '	vw_periodos_insc_propuesta.anio_academico,
					sga_propuestas_aspira.ubicacion,
					sga_propuestas_aspira.propuesta,
					vw_personas.apellido,
					vw_personas.nombres';

		return guarani::co('co_inscripciones')->get_alumnos_inscriptos($filtro, $order);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Alumnos Año de Ingreso',0);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Alumnos Ubicación',1);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Alumnos Propuesta',2);
	}
}
?>