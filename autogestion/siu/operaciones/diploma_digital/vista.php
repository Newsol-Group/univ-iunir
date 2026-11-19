<?php
namespace siu\operaciones\diploma_digital;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;
use siu\modelo_g3\arai_documentos;
use siu\modelo_g3\sidcer;

class vista extends vista_g3w2
{
	function ini()
	{

		$arai_documentos_ok = arai_documentos::isConexionOk();
		$sidcer_ok = sidcer::isConexionOk();

		// Si falla la conexión con SIU-Araí Documentos o SIDCer
		if (!$arai_documentos_ok || !$sidcer_ok) {

			$clase = 'operaciones\diploma_digital\pagelet_error';
			$pl = kernel::localizador()->instanciar($clase, 'error');
			
			$pl->data['error'] = "";
			// Si falla la conexión con SIU-Araí Documentos
			if (!$arai_documentos_ok) {
				$pl->data['error'] = '<h4>'.kernel::traductor()->trans('diploma_digital.fallo_generico').'</h4>';
				kernel::log()->add_error(new \Exception(kernel::traductor()->trans('diploma_digital.fallo_conexion_arai')));
			}
			// Si falla la conexión con SIDCer
			if (!$sidcer_ok) {
				$pl->data['error'] = '<h4>'.kernel::traductor()->trans('diploma_digital.fallo_generico').'</h4>';
				kernel::log()->add_error(new \Exception(kernel::traductor()->trans('diploma_digital.fallo_conexion_sidcer')));
			}
			
			$this->add_pagelet($pl);
			$this->set_template('template_error');

		} else {

			$clase = 'operaciones\diploma_digital\pagelet_lista_tramites';
			$pl = kernel::localizador()->instanciar($clase, 'lista_tramites');
			$this->add_pagelet($pl);
			$this->set_template('template');

		}

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("diploma_digital.titulo"));
	}

}