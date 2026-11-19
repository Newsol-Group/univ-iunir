<?php
namespace siu\operaciones\censo\_forms;
use SIU\Chulupi\kernel;

class form_padres extends form_comun
{	
	protected $form = 'padres';
	
	public function init()
    {
		$this->crear_elementos();

    }
}
?>