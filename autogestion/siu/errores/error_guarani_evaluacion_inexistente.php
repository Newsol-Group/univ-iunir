<?php
namespace siu\errores;

class error_guarani_evaluacion_inexistente
	extends error_guarani
{
	public function __construct()
	{
		parent::__construct(\SIU\Chulupi\kernel::traductor()->trans('evaluacion_inexistente'));
	}
}
?>