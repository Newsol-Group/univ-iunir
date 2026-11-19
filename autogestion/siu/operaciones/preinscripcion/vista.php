<?php

namespace siu\operaciones\preinscripcion;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
	// Pagelets del formulario de preinscripción
	static $FORMS = [
		'propuesta',
		'requisitos'
	];
	protected $form_activo;
	protected $id_form_activo;

	function ini()
	{
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("preinscripcion.titulo"));

		$clase = 'operaciones\preinscripcion\pagelet_lista_forms';
		$pl = kernel::localizador()->instanciar($clase, 'lista_forms');
		$this->add_pagelet($pl);

		$clase = 'operaciones\preinscripcion\pagelet_info';
		$pl = kernel::localizador()->instanciar($clase, 'info');
		$this->add_pagelet($pl);
	}

	//--------------------------------------------------------------------------

	function get_forms_visibles()
	{
		$salida = [];
		foreach (self::$FORMS as $seccion) {
                    $salida[] = $seccion;
		}

                $turnos = $this->controlador->modelo()->get_listado_turnos();
                // Si hay turnos de presentación de documentación muestro la solapa
                if (!empty($turnos)) {
                    $salida[] = 'turno_presentacion';
                }
                
		return $salida;
	}

	function get_forms()
	{
		return $this->get_forms_visibles();
	}

	function existe_form($id)
	{
		return in_array($id, self::$FORMS);
	}

	function get_id_form_activo()
	{
		return $this->id_form_activo;
	}

	function set_form_activo($pagelet)
	{
		$this->form_activo = $pagelet;
		$this->id_form_activo = $pagelet->get_id();
		$this->add_pagelet($pagelet, 0, 'formulario');
	}

	function pagelet($id)
	{
		if (isset($this->pagelets[$id])) {
			return parent::pagelet($id);
		}
		$clase = "operaciones\preinscripcion\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
		throw new \siu\errores\error_guarani_seguridad("El pagelet '$id' no existe");
	}

	function vista_doc_digital()
	{
		$clase = 'operaciones\preinscripcion\pagelet_doc_digital';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->add_pagelet($pl);
		return $pl;
	}

}