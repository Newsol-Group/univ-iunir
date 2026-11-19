<?php
namespace nsg01\operaciones\inscriptos_cursadas;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use siu\guarani;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;

class controlador extends \siu\operaciones\inscriptos_cursadas\controlador
{
	
	function get_info_comision()
	{
		$datos = $this->modelo()->info__comision($this->comision);

                $i = 1;
                $msj_sin_definir = kernel::traductor()->trans('inscriptos_cursadas.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['estado_inscripcion'] = $dato['estado_inscripcion'] ?? $msj_sin_definir;
			$datos[$key]['instancias'] = $dato['instancias'];
			$datos[$key]['email'] = $dato['email'] ?? $msj_sin_definir;
			$datos[$key]['telefono'] = $dato['telefono'] ?? $msj_sin_definir;
			$datos[$key]['moroso'] = $dato['moroso'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;

		}

                return $datos;
	}
}