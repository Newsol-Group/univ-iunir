<?php
namespace siu\operaciones\_comun\operaciones\reporte;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
    function modelo(){
    }

    function ini_request(){
    }

	function accion__index() {
		kernel::log()->add_debug('test_1', 'sdfg');
		$pagelet = $this->vista()->pagelet('items');
		$pagelet->ver_pagina(1);
    }

	function accion__ver_pagina(){
		$pagina = kernel::url()->get_param('pagina','get');
		$columna = kernel::url()->get_param('col','get');
		$orden = kernel::url()->get_param('ord','get');
		
		$pagelet = $this->vista()->pagelet('items');
        $pagelet->ver_pagina($pagina, $columna, $orden);
        kernel::renderer()->add($pagelet);
    }

	function accion__generar_pdf(){
            list($path_archivo, $nombre_archivo) =  $this->vista()->pagelet('items')->generar_pdf();
            $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_excel(){
		$this->vista()->pagelet('items')->generar_excel();
	}

	function accion__generar_csv(){
		$this->vista()->pagelet('items')->generar_csv();
	}
}