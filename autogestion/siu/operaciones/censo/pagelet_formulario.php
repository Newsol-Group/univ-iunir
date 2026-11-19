<?php
namespace siu\operaciones\censo;
use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_formulario extends pagelet
{
	protected $form;

	function get_nombre()
    {
        return 'formulario';
    }

	function get_js_files()
    {
        $url = kernel::vinculador()->vinculo_recurso("js/jquery.checkForm.1.0.min.js");
        return array_merge(array($url), parent::get_js_files());
    }
	
	function set_form($form)
	{
		$this->form = $form;
	}
	
	
	function form_localidades() 
	{
		$clase = "operaciones\\censo\\_forms\\form_localidades";
        $obj = kernel::localizador()->instanciar($clase, array('id'=>'localidades', 'datos'=>array()));
		$obj->setView(new \Zend_View());
		return $obj;
	}
	
	
	function prepare()
	{
		if (empty($this->form)) {
			throw new \siu\error_guarani('Debe especificar un form para usar el pagelet formulario');
		}
		$this->add_var_js('id_form', $this->form->get_idhtml());
		$this->add_var_js('url_provincias', kernel::vinculador()->crear('censo', 'provincias'));
		$this->add_var_js('url_localidades', kernel::vinculador()->crear('censo', 'localidades'));
        
        $url_guardar = kernel::vinculador()->crear('censo', 'guardar');
        //ajax
		$this->add_var_js('url_guardar', $url_guardar);
        //vista
        $this->data['url_guardar'] = $url_guardar;
        
		$this->data['form_template'] = $this->form->get_template();
		$this->data['form'] = $this->form;
		$this->add_var_js('localidades', kernel::load_template('formulario/localidades.twig')->render(array(
				'form' => $this->form_localidades()
			)));
		
		if ($this->controlador->modo_reinscripcion()) {
			$this->data['modo_reinscripcion'] = true;
			$this->add_var_js('modo_reinscripcion', true);
			$this->add_var_js('url_reinscripcion', kernel::vinculador()->crear('reinscripcion', 'reinscribir'));
			$this->data['reinscripcion_completa'] = $this->controlador->get_revision_completa();
		}
	}
}
?>