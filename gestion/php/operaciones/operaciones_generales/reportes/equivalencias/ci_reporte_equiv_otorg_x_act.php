<?php
class ci_reporte_equiv_otorg_x_act extends ci_reporte_equiv
{


	//---- cuadro -----------------------------------------------------------------------
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		// Se contabilizan las equivalencias otorgadas por actividad
		$eq_x_elem = array();
		foreach ($this->s__datos as $datos) {
			if (trim($datos['observaciones']) != '') {
				$eq_x_elem[$datos['elemento']]['no_otorg'] = (isset($eq_x_elem[$datos['elemento']]['no_otorg'])) ? ($eq_x_elem[$datos['elemento']]['no_otorg']+1) : 1;
			} else {
				$eq_x_elem[$datos['elemento']]['otorg'] = (isset($eq_x_elem[$datos['elemento']]['otorg'])) ? ($eq_x_elem[$datos['elemento']]['otorg']+1) : 1;
			}
		}
		
		// Se arma la descripción del corte de control del cuadro.
		foreach ($this->s__datos as $clave => $datos) {
			$otorg = (isset($eq_x_elem[$datos['elemento']]['otorg'])) ? ($eq_x_elem[$datos['elemento']]['otorg']) : 0;
			$notorg = (isset($eq_x_elem[$datos['elemento']]['no_otorg'])) ? ($eq_x_elem[$datos['elemento']]['no_otorg']) : 0;
			$descr = 'Actividad: ' . $datos['actividad'] . 
						'        |          ' . 
					 "Eq. Otorgadas: $otorg  -  Eq. No Otorgadas: $notorg";
			$this->s__datos[$clave]['cc_desc'] = $descr;
		}
		
		$cuadro->set_datos($this->s__datos);
	}

}
?>