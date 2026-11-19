<?php
namespace siu\operaciones\asistencias_planilla;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{

		$controlador = kernel::localizador()->instanciar('operaciones\asistencias_planilla\controlador');
		$this->set_controlador($controlador);
		$this->controlador->load_params_for_vista();
		$info_zona = $this->controlador->get_info_zona();

		$tiene_banda_horaria_asignada = $this->controlador->modelo()->tiene_banda_horaria_asignada($info_zona['seleccion']['es_subcomision'], $info_zona['seleccion']['comision'], $info_zona['seleccion']['subcomision']);
		$this->agregar_a_contexto('tiene_banda_horaria_asignada', $tiene_banda_horaria_asignada);

		if ($tiene_banda_horaria_asignada) {
			$clase = 'operaciones\asistencias_planilla\pagelet_planilla';
			$pl = kernel::localizador()->instanciar($clase, 'planilla');
			$this->add_pagelet($pl);
		}

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_asistencias_planilla"));
	}

	function get_from_contexto($clave)
	{
		return $this->contexto[$clave];
	}

}
?>