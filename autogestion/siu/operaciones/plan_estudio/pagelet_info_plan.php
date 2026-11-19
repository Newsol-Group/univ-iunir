<?php
/**
 * Created by PhpStorm.
 * User: leonel
 * Date: 23/02/15
 * Time: 15:47
 */

namespace siu\operaciones\plan_estudio;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_info_plan extends pagelet
{
	function get_nombre()
	{
		return 'info_plan';
	}

	function prepare()
	{
		$controlador = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url_correlativas', kernel::vinculador()->crear($controlador, 'correlativas'));
		$this->add_var_js('url_optativas', kernel::vinculador()->crear($controlador, 'optativas'));
		$this->add_var_js('url_pdf', kernel::vinculador()->crear($controlador, 'generar_pdf'));
		$this->add_var_js('url_xls', kernel::vinculador()->crear($controlador, 'generar_excel'));
		$this->add_var_js('url_verificar_correlativas', kernel::vinculador()->crear($controlador, 'verificar_correlativas'));
		$this->add_var_js('url_verificar_cumplimientos', kernel::vinculador()->crear($controlador, 'verificar_cumplimientos'));

		$this->add_var_js('ver', kernel::traductor()->trans('plan_estudio.correlativas_ver'));
		$this->add_var_js('ocultar', kernel::traductor()->trans('plan_estudio.correlativas_ocultar'));

		$this->data['url_reasignar_optativa'] = kernel::vinculador()->crear('reasignar_optativa');
	}

}