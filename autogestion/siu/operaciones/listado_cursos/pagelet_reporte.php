<?php

namespace siu\operaciones\listado_cursos;

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
		
		// Recupero los cursos de todas las propuestas de tipo curso del alumno.
		$propuestas= catalogo::consultar('cursos', 'get_cursos_academica', array('persona' => $persona_id, 'propuesta' => null));
		$propuestas_ordenado=[];
		if (empty($propuestas)) {
			$this->data['msg_sin_dato'] = \guarani_mensajes::get('800_sin_dato', array('encontrado', 'cursos'));
		} else {
                    foreach ($propuestas as $propuesta){
                       
                        $propuestas_ordenado[$propuesta['propuesta']]['propuesta'] = $propuesta['propuesta'];
                        $propuestas_ordenado[$propuesta['propuesta']]['propuesta_nombre'] = $propuesta['propuesta_nombre'];
                        $propuestas_ordenado[$propuesta['propuesta']]['cursos'][] = $propuesta;
                        
                    }
                $this->data['propuestas']= $propuestas_ordenado;
		}

	}

}
?>