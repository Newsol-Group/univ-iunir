<?php

namespace siu\operaciones\saml;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("saml.titulo"));
    }
}