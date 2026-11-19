<?php
class ci_reporte_equiv_otorg_x_alu extends ci_reporte_equiv
{


	//---- cuadro -----------------------------------------------------------------------
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = array();
		
		// Se contabilizan las equivalencias otorgadas por actividad
		$eq_x_elem = array();
		foreach ($this->s__datos as $eq) {
			if (!isset($datos[$eq['alumno']])) {
				$datos[$eq['alumno']] = $eq;
			}
			if (trim($eq['observaciones']) != '') {
				$datos[$eq['alumno']]['no_otorg'] = (isset($datos[$eq['alumno']]['no_otorg'])) ? ($datos[$eq['alumno']]['no_otorg']+1) : 1;
				$datos[$eq['alumno']]['otorg'] = (isset($datos[$eq['alumno']]['otorg'])) ? ($datos[$eq['alumno']]['otorg']) : 0;
			} else {
				$datos[$eq['alumno']]['otorg'] = (isset($datos[$eq['alumno']]['otorg'])) ? ($datos[$eq['alumno']]['otorg']+1) : 1;
				$datos[$eq['alumno']]['no_otorg'] = (isset($datos[$eq['alumno']]['no_otorg'])) ? ($datos[$eq['alumno']]['no_otorg']) : 0;
			}
		}
		
		$cuadro->set_datos($datos);
		
	}

}
?>