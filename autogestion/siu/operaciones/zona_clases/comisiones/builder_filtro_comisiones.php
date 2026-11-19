<?php
namespace siu\operaciones\zona_clases\comisiones;

use SIU\Chulupi\kernel;

class builder_filtro_comisiones extends \siu\operaciones\zona_comisiones\comisiones\builder_filtro_comisiones
{
	function get_action()
	{
		return kernel::vinculador()->crear('zona_clases', 'index');
	}

}
?>