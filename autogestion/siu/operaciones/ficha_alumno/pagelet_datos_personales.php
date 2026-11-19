<?php
namespace siu\operaciones\ficha_alumno;

use SIU\Chulupi\interfaz\pagelet;
use siu\modelo\entidades\persona;

class pagelet_datos_personales extends pagelet
{
	function get_nombre()
	{
		return 'datos_personales';
	}

	function prepare()
	{
	    $datos_pers_alum = $this->controlador->get_rep_datos_personales();
        $datos_pers_alum['nombre_completo'] =  $datos_pers_alum['apellido_nombres_elegido'];

        $this->data['datos'] = $datos_pers_alum;
		$this->data['propuestas'] = $this->controlador->get_rep_datos_personales_propuestas();
	}
}
?>