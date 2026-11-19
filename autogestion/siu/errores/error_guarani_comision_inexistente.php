<?php
namespace siu\errores;

class error_guarani_comision_inexistente
	extends error_guarani
{
	public function __construct()
	{
		parent::__construct(\SIU\Chulupi\kernel::traductor()->trans('comision_inexistente'));
	}
}
?>