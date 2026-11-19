<?php
namespace siu\operaciones\censo_docente;
use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_formulario extends pagelet
{
	protected $form;

	function get_nombre()
    {
        return 'formulario';
    }

//	function get_js_files()
//    {
//        $url = kernel::vinculador()->vinculo_recurso("js/jquery.checkForm.1.0.min.js", false);
//        return array_merge(array($url), parent::get_js_files());
//    }
	
	function set_form($form)
	{
		$this->form = $form;
	}
	
	function prepare()
	{
		// se limpia la memoria para borrar los viejos csrf keys
		$this->memoria()->limpiar();

		if (empty($this->form)) {
			throw new \siu\error_guarani('Debe especificar un form para usar el pagelet formulario');
		}
		$this->add_var_js('id_form', $this->form->get_idhtml());
		$this->add_var_js('url_provincias', kernel::vinculador()->crear('censo_docente', 'provincias'));
		$this->add_var_js('url_localidades', kernel::vinculador()->crear('censo_docente', 'localidades'));        
        $url_guardar = kernel::vinculador()->crear('censo_docente', 'guardar');
        //ajax
		$this->add_var_js('url_guardar', $url_guardar);
        //vista
        $this->data['url_guardar'] = $url_guardar;
		
		//$this->add_var_js('cambio_algo', false);
		$this->set_template($this->form->get_template());		
		$this->data['form'] = $this->form;
	}
}
?>