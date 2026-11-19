<?php

namespace siu\errores;

use siu\operaciones\notas_cursada_comision\controlador;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class error_guarani_acta_incompleta extends error_guarani
{
	public function __construct($message, $comision)
	{
		
		$controlador = new controlador();
		$periodos_lectivos_vigentes = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes_ids');
		$periodos_lectivos_vigentes_ids = array_column($periodos_lectivos_vigentes, 'periodo_lectivo');
		$datos = $controlador->get_comisiones(null, $periodos_lectivos_vigentes_ids, null);
		
		$link = null;
		foreach ($datos as $actividades) {

			foreach ($actividades as $comisiones) {

				foreach ($comisiones as $fila) {
					
					if ($fila['comision'] == $comision) {
						$link = $fila['link'];
						break 3;
					}
				}
				
			}
		}
		
		$message .= '<br>';
		$message .= kernel::traductor()->trans('cierre_cursadas.link_carga_notas_cursada', ['%link%' => $link]);

		parent::__construct($message);
	}
}