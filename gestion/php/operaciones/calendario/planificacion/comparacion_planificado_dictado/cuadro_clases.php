<?php

class cuadro_clases extends cuadro_salida_ev_info
{
	//------------------------------------------------------------------------
	//---- SALIDA EXCEL ------------------------------------------------------
	//------------------------------------------------------------------------
	
	function vista_excel(toba_vista_excel $salida )
	{
		$datos_cuadro = $this->get_datos();
		foreach ($datos_cuadro as $clave => $dato) {			
			if (array_key_exists('tema_planificado_bk', $dato)) {
				$datos_cuadro[$clave]['tema_planificado'] = $dato['tema_planificado_bk'];
			}
			if (array_key_exists('tema_dictado_bk', $dato)) {
				$datos_cuadro[$clave]['tema_dictado'] = $dato['tema_dictado_bk'];
			}
		}
		$this->set_datos($datos_cuadro);
		$this->generar_salida("excel", $salida);
	}
}

