<?php

namespace siu\operaciones\inicio_docente;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{

		/*
		 * @see https://redmine.siu.edu.ar/redmine/issues/31002 Ticket #31002.
		 */
		$usuario_arai = kernel::persona()->get_usuario_arai();
		$this->agregar_a_contexto('documentos_digitales_pendientes', false);
		$this->agregar_a_contexto('msg_documentos_digitales_pendientes', '');
		$this->agregar_a_contexto('msg_error', '');
		// Si tiene un usuarios asociado en Araí
		if (!is_null($usuario_arai)) {
			try{
				// URL a SIU-Araí Huarpe, sección 'Documentos pendientes de autorización'.
				$url_documentos = kernel::proyecto()->get_huarpe_url_bandeja();
				// Solo si tiene documentos pendientes creados desde Guaraní
				$documentos_digitales_pendientes = \persona::tiene_documentos_pendientes_autorizacion($usuario_arai, true);

                                $es_saml_login = kernel::persona()->es_saml_login();
                                // Si es login por SAML
                                if ($es_saml_login) {
                                    $msg_documentos_digitales_pendientes = kernel::traductor()->trans('inicio_docente.documentos_digitales_pendientes_con_saml', ['%url_arai_documentos%' => $url_documentos]);
                                    $this->agregar_a_contexto('msg_documentos_digitales_pendientes', $msg_documentos_digitales_pendientes);
                                // Si NO es login por SAML
                                } else {
                                    $msg_documentos_digitales_pendientes = kernel::traductor()->trans('inicio_docente.documentos_digitales_pendientes_sin_saml');
                                    $this->agregar_a_contexto('msg_documentos_digitales_pendientes', $msg_documentos_digitales_pendientes);
                                }
                                
				$this->agregar_a_contexto('documentos_digitales_pendientes', $documentos_digitales_pendientes);
			} catch (\Exception $e) {
				kernel::log()->add_error($e);
				$this->agregar_a_contexto('msg_error', kernel::traductor()->trans("inicio_docente.fallo_conexion_arai_documentos"));
			}
		}
            
		$clase = 'operaciones\inicio_docente\pagelet_clases_ultimas';
		$pl = kernel::localizador()->instanciar($clase, 'clases_ultimas');
		$this->add_pagelet($pl);
                $clase = 'operaciones\inicio_docente\pagelet_mesas_examen';
		$pl = kernel::localizador()->instanciar($clase, 'mesas_examen');
		$this->add_pagelet($pl);
		$clase = 'operaciones\inicio_docente\pagelet_lista_encuestas_pendientes';
		$pl = kernel::localizador()->instanciar($clase, 'lista_encuestas_pendientes');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inicio_docente"));
	}

}