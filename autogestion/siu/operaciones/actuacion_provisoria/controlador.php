<?php

namespace siu\operaciones\actuacion_provisoria;

use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
    
    function modelo()
    {
        return null;
    }

    function accion__index()
    {
    }
    
    function accion__generar_pdf_cursadas(){
        list($path_archivo, $nombre_archivo) = $this->vista()->pagelet('cursa')->generar_pdf();
        $this->descargar_archivo($path_archivo, $nombre_archivo, true);
    }
    
    function accion__generar_excel_cursadas(){
		$this->vista()->pagelet('cursa')->generar_excel();
    }
    
    function accion__generar_pdf_promociones(){
        list($path_archivo, $nombre_archivo) = $this->vista()->pagelet('promo')->generar_pdf();
        $this->descargar_archivo($path_archivo, $nombre_archivo, true);
    }
    
    function accion__generar_excel_promociones(){
		$this->vista()->pagelet('promo')->generar_excel();
    }
    
    function accion__generar_pdf_examenes(){
        list($path_archivo, $nombre_archivo) = $this->vista()->pagelet('exa')->generar_pdf();
        $this->descargar_archivo($path_archivo, $nombre_archivo, true);
    }
    
    function accion__generar_excel_examenes(){
		$this->vista()->pagelet('exa')->generar_excel();
    }

}