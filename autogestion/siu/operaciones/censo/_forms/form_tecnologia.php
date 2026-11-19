<?php
namespace siu\operaciones\censo\_forms;
use SIU\Chulupi\kernel;

class form_tecnologia extends form_comun
{	
	protected $form='tecnologia';
	
	public function init()
    {
		$this->crear_elementos();
    }
}
?>