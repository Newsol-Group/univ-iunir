<?php

namespace siu\operaciones\preinscripcion;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_requisitos extends \siu\operaciones\requisitos_ingreso\pagelet_reporte
{
	function get_datos()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$preinscripciones = $this->controlador->modelo()->get_datos_preinscripciones();
		$requisitos = array();
		$requisito ['propuesta_nombre'] = array();
		foreach ($preinscripciones as $keyp => $preinscripcion) {
			if ($preinscripcion['vigente']) {
				$propuestas[] = $preinscripcion['propuesta'];
				$propuestas_nombre[] = $preinscripcion['propuesta_nombre'];
				//requisitos documentales de la persona en todas las propuesta preinscriptas

				$preinscripcion['requisitos'] = $this->controlador->modelo()->get_requisitos_persona_propuesta($preinscripcion['propuesta']);

				foreach ($preinscripcion['requisitos'] as $key => $requisito) {
					$a = array_search($requisito['requisito'], array_column($requisitos, 'requisito'));

					if (!is_bool($a)) {
						$requisitos[$a]['propuesta'] = $requisitos[$a]['propuesta'] . ', ' . $preinscripcion['propuesta'];
						$requisitos[$a]['propuesta_nombre'] = $requisitos[$a]['propuesta_nombre'] . ', ' . $preinscripcion['propuesta_nombre'];
					} else {
						$requisito ['url_doc_digital'] = kernel::vinculador()->crear($operacion, 'doc_digital', array($requisito[catalogo::id]));
						$requisito ['propuesta'] = $preinscripcion['propuesta'];
						$requisito ['propuesta_nombre'] = $preinscripcion['propuesta_nombre'];
						$requisitos[] = $requisito;
					}
				}
			}
		}
                
		$this->data['datos']['preinscripciones'] = $preinscripciones;
		$this->data['datos']['requisitos'] = $requisitos;
		return $this->data['datos'];
	}

}