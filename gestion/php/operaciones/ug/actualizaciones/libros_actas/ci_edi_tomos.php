<?php
class ci_edi_tomos extends guarani_pers_ci
{
	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad()->puede_agregar_tomo()) {
			$pantalla->eliminar_evento('agregar_tomo');
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_tomos --------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_tomos(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_libros_tomos();
		$cuadro->set_datos($datos);
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__agregar_tomo()
	{
		$datos = $this->cn()->entidad()->get_libros_tomos();
		$max_nro_tomo = 0;
		foreach ($datos as $dato) {
			if ($dato['nro_tomo'] > $max_nro_tomo) {
				$max_nro_tomo = $dato['nro_tomo'];
			}
		}

		$nuevo_tomo = array(
			'nro_tomo' => $max_nro_tomo + 1,
			'cant_max_folios' => $this->cn()->entidad()->get_valor_parametro('ung_cant_max_folios_libro'),
			'cant_folios_usados' => '0'
		);

		$this->cn()->entidad()->agregar_tomo($nuevo_tomo);
	}

}
?>