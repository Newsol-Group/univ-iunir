<?php
namespace siu\operaciones\encuestas_kolla;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\encuestas_kolla;

class pagelet_encuesta extends pagelet {
	
	public function get_nombre(){
        return 'encuesta';
    }

	function prepare() 
	{
		$url_kolla = $this->controlador->get_var('url_formulario');
		
		$this->add_var_js('dominio_kolla', encuestas_kolla::get_dominio_kolla());
		$this->add_var_js('alto_iframe_defecto', encuestas_kolla::ALTO_IFRAME_DEFECTO);
		$this->add_var_js('alto_iframe_comprobante', encuestas_kolla::ALTO_IFRAME_COMPROBANTE);
		
		$this->add_var_js('url_accion', kernel::vinculador()->crear('encuestas_kolla', 'encuesta_respondida') );
		if (! is_null($url_kolla)) {
			$this->data['url_encuesta'] = $url_kolla;
            $encuesta_seleccionada_id = $this->controlador->get_var('encuesta_seleccionada_id');
            $this->data['encuesta_seleccionada_id'] = "#kl_".$encuesta_seleccionada_id;
			kernel::log()->add_debug('url_encuesta', $url_kolla);
		}

		$this->data['es_protocolo_compatible'] = encuestas_kolla::es_protocolo_compatible();
		// Si el protocolo usado por Kolla es diferente al usado por Guaraní muestro un error
		if (!$this->data['es_protocolo_compatible']) {
			kernel::log()->add_error(new \Exception(kernel::traductor()->trans("encuestas_kolla.protocolos_incompatibles")));
		}

		$tipo_relevamiento = $this->controlador->get_var('encuesta_seleccionada_tipo_relevamiento');
		$this->add_var_js('tipo_relevamiento_becas', false);
		// Si la encuesta es de tipo Beca
		if ($tipo_relevamiento == \encuesta::tipo_relevamiento_becas) {
			$this->add_var_js('tipo_relevamiento_becas', true);
		}
	}
}