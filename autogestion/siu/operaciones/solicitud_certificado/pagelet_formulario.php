<?php
namespace siu\operaciones\solicitud_certificado;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_formulario extends pagelet {
	public function get_nombre(){
        return 'formulario';
    }

	function prepare() 
	{
		$this->add_var_js('url_carga_form', kernel::vinculador()->crear('solicitud_certificado', 'cargar_form'));
		$this->data['url_form'] = kernel::vinculador()->crear('solicitud_certificado', 'solicitar');
		$id_form = $this->controlador->get_id_certificado();
		if (!empty($id_form)){
			$this->data['id_form'] = $id_form;
			$argumentos = $this->controlador->modelo()->argumentos_certificado($id_form);
			$this->data['form_argumentos'] = $argumentos;
		}
	}
}
?>