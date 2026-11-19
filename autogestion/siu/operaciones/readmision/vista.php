<?php
namespace siu\operaciones\readmision;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("readmision"));
    }
}
?>