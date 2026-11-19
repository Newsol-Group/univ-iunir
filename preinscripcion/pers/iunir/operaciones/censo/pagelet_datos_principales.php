<?php

namespace iunir\operaciones\censo;

use SIU\Chulupi\kernel;

class pagelet_datos_principales extends \siu\operaciones\censo\pagelet_datos_principales
{
	function prepare()
	{
		parent::prepare();
		$this->add_mensaje_js('error_csrf', kernel::traductor()->trans('error_csrf'));
	}
}
