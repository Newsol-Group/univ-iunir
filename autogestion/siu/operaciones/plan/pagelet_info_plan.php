<?php
namespace siu\operaciones\plan;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;

class pagelet_info_plan extends pagelet
{
    function get_nombre()
    {
        return 'info_plan';
    }

	function prepare()
    {
		$this->add_var_js('url_correlativas', kernel::vinculador()->crear('plan', 'correlativas'));
        $this->data['url_pdf'] = kernel::vinculador()->crear('plan', 'generar_pdf');
    }
}
?>
