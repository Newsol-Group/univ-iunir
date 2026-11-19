<?php
namespace siu\operaciones\notas_mesa_examen;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_folio extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_folio');

		$clase = 'operaciones\notas_mesa_examen\pagelet_cabecera';
		$pl = kernel::localizador()->instanciar($clase, 'cabecera');
		$this->add_pagelet($pl);

		$clase = 'operaciones\notas_mesa_examen\pagelet_herramientas';
		$pl = kernel::localizador()->instanciar($clase, 'herramientas');
		$this->add_pagelet($pl);

		$clase = 'operaciones\notas_mesa_examen\pagelet_actas';
		$pl = kernel::localizador()->instanciar($clase, 'actas');
		$this->add_pagelet($pl);

		$clase = 'operaciones\notas_mesa_examen\pagelet_renglones';
		$pl = kernel::localizador()->instanciar($clase, 'renglones');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_notas_mesas_examen"));
	}

}
?>