<?php
namespace siu\operaciones\diploma_digital;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_lista_tramites extends pagelet
{
	
	public function get_nombre()
	{
        return 'lista_tramites';
    }

	function prepare() 
	{
		$this->data['tramites'] = $this->controlador->modelo()->info__analiticos_diplomas_digitales();
		$operacion = kernel::ruteador()->get_id_operacion();
                
		foreach ($this->data['tramites'] as $key => $tramite) {
			
			// Si se puede descargar genero la url, sino le pongo '#'
			if ($tramite['analitico']['id_documento_digital']) {
				$url_analitico = kernel::vinculador()->crear($operacion, 'descargar_documento', $tramite['analitico']['id_documento_digital']);
			} else {
				$url_analitico = '#';
			}
			
			// Si se puede descargar genero la url, sino le pongo '#'
			if ($tramite['diploma']['id_documento_digital']) {
				$url_diploma = kernel::vinculador()->crear($operacion, 'descargar_documento', $tramite['diploma']['id_documento_digital']);
			} else {
				$url_diploma = '#';
			}
			
			$this->data['tramites'][$key]['analitico']['url'] = $url_analitico;
			$this->data['tramites'][$key]['diploma']['url'] = $url_diploma;
		}

		$this->data['error_descarga'] = false;
		if (kernel::sesion()->esta_seteada('error_descarga')) {
			$this->data['error_descarga'] = kernel::sesion()->get('error_descarga');
			kernel::sesion()->borrar('error_descarga');
		}
	}
}