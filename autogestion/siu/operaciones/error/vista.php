<?php
namespace siu\operaciones\error;

use SIU\Chulupi\kernel;

class vista extends \SIU\Chulupi\interfaz\vistas\VistaPagelets
{
    function ini()
    {
	    kernel::pagina()->set_etiqueta('titulo', "Error 404");
    }
}
?>
