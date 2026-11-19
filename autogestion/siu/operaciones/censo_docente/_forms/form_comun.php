<?php
namespace siu\operaciones\censo_docente\_forms;
use siu\extension_kernel\form as form_base;
use SIU\Chulupi\kernel;
use siu\operaciones\censo_docente\modelo;
use siu\modelo\datos\catalogo;

abstract class form_comun extends form_base
{
	protected $modelo;
	abstract function get_template();
	abstract function get_idhtml();
	
	protected $datos;
	
	function __construct($params)
	{
		$id = $params['id'];
		if (isset($params['datos'])) {
			$this->datos = $params['datos'];
		}
		parent::__construct($id, $params);
	}
	
	public function setView(\Zend_View_Interface $view)
    {
        parent::setView($view);
        foreach ($this as $item){
            $item->setView($view);
        }
        return $this;
    }
	
	function modelo()
	{
		if (!isset($this->modelo)) {
			$this->modelo = new modelo();
		}		
		return $this->modelo;
	}
	
	function get_datos(){
		if (! kernel::sesion()->esta_seteada('form_datos_censales') )
		{
			$nro_inscripcion = kernel::persona()->get_nro_inscripcion();
			$datos = catalogo::consultar('datos_censales', 'get_datos',
									array('nro_inscripcion'=>$nro_inscripcion));
			kernel::sesion()->set('form_datos_censales', $datos);
		}
		else
		{
			$datos = kernel::sesion()->get('form_datos_censales');
		}
		return $datos;
	}

	function render(Zend_View_Interface $view = null)
	{
		return parent::render($view);
	}
}
?>