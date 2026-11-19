<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_finalizado extends pagelet
{

    function get_nombre()
	{
		return 'finalizado';
	}

    function get_datos_inscripciones()
	{
		return kernel::persona()->datos()->get_datos_inscripciones();
	}

	function get_action_reabrir()
	{
		return kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'reabrir');
	}

	function prepare()
	{
		
	}

}