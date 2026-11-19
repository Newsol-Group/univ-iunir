<?php

namespace siu\operaciones\inicio_docente;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_clases_ultimas extends pagelet
{
	const cant_comisiones_visibles = 5;

	function get_nombre()
	{
		return 'clases_ultimas';
	}

	function prepare()
	{
		$param['docente'] = kernel::persona()->get_id_docente();
		$param['filas'] = '10';
		$clases = catalogo::consultar('docente', 'get_clases_comisiones_docente', $param);
		$controlador = new controlador();
		foreach ($clases as $key => $clase) {
			$datos = $controlador->get_comisiones(['id' => $clase['comision'],'clase'=>$clase['clase']]);
			$clases[$key]['link'] = $datos[$clase['comision']]['LINK_ASISTENCIA'];

		}
		$this->data['items'] = $clases;
		$this->add_var_js('ver_mas', kernel::traductor()->trans('ver_mas'));
		$this->add_var_js('ocultar', kernel::traductor()->trans('ocultar'));
	}

	function get_cant_comisiones_visibles()
	{
		return self::cant_comisiones_visibles;
	}

}
?>
