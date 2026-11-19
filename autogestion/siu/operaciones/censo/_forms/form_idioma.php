<?php
namespace siu\operaciones\censo\_forms;
use SIU\Chulupi\kernel;

class form_idioma extends form_comun
{	
	
	protected $form='idioma';
	
	public function init()
    {
		$this->crear_elementos();
    }
}
?>