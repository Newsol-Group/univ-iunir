<?php

namespace siu\operaciones\seguimiento_tesis;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends pagelet
{
	public function get_nombre()
	{
		return 'reporte';
	}

	function prepare()
	{

		$persona_id = kernel::persona()->get_id();

		$tesis_asociadas = catalogo::consultar('alumno', 'tesis', array('persona' => $persona_id));


		if (empty($tesis_asociadas)) {
			$this->data['msg_sin_dato'] = \guarani_mensajes::get('800_sin_dato', array('encontrado', 'tesis'));
		} else {
		
			foreach ($tesis_asociadas as $key => $titulo) {
				$tesis_asociadas[$key]['datos'] = catalogo::consultar('alumno', 'tesis_datos', array(
							'tesis' => $titulo['tesis'],
							'persona' => $persona_id
								)
				);
				$tesis_asociadas[$key]['estados'] = catalogo::consultar('alumno', 'tesis_estados', array(
							'tesis' => $titulo['tesis']
								)
				);
			}
		}

		$this->data['tesis_solicitadas'] = $tesis_asociadas;
		
	}

}
?>