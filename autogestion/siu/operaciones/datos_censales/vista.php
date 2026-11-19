<?php

namespace siu\operaciones\datos_censales;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\transacciones\becas;

class vista extends vista_g3w2
{
	// Pagelets del formulario de preinscripción
	static $FORMS = [
		'datos_personales',
		'domicilio',
		'datos_contacto',
		'datos_estudios',
		'financiamiento',
		'datos_laborales',
		'tecnologia',
		'deportes',
		'idiomas',
		'datos_salud',
		'preferencias',
		'actuacion_docente',
		'actuacion_profesional',
		'allegados',
		'discapacidad'
	];
	
	protected $form_activo;
	protected $id_form_activo;	
	
	protected $campos_pagelet;
	protected $campos_faltantes;
	
    function ini()
    {
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_datos_censales'));
		
		$clase = 'operaciones\datos_censales\pagelet_lista_forms';
		$pl = kernel::localizador()->instanciar($clase, 'lista_forms');
		$this->add_pagelet($pl);
	}
	
	function get_action_confirmar() 
	{
		return kernel::vinculador()->crear('datos_censales', 'confirmar', array($this->get_id_form_activo()));
	}
	
	function get_msg_sin_cambios()
	{
		return kernel::traductor()->trans('datos_censales.sin_cambios_por_hacer', array(
					'%1%' => kernel::vinculador()->crear('datos_censales', 'confirmar', array($this->get_id_form_activo()))
				));
	}
	
	function control_faltantes_activado()
	{
		return true;
	}
	
	//--------------------------------------------------------------------------
	
	function hay_faltantes()
	{
		return $this->controlador->hay_datos_faltantes();
	}

	function hay_datos_faltantes_convocatoria()
	{
		return $this->controlador->hay_datos_faltantes_convocatoria();
	}

	function viene_de_convocatorias_becas()
	{
		return becas::viene_de_convocatorias_becas();
	}

	function set_error_confirmar()
	{
		kernel::sesion()->set('dc.error_confirmar', true);
	}
	
	function datos_error_control()
	{
		$salida = kernel::sesion()->esta_seteada('dc.error_confirmar');
		kernel::sesion()->borrar('dc.error_confirmar');
		return $salida;
	}
	
	function set_datos_confirmados()
	{
		kernel::sesion()->set('dc.datos_censales.confirmados', true);
	}
	
	function datos_confirmados()
	{
		$confirmados = kernel::sesion()->esta_seteada('dc.datos_censales.confirmados');
		kernel::sesion()->borrar('dc.datos_censales.confirmados');
		return $confirmados;
	}
	
	function datos_vigentes()
	{
		return $this->controlador->datos_vigentes();
	}
	
	//--------------------------------------------------------------------------
	
	function get_forms_visibles()
	{
            $viene_de_convocatorias_becas = becas::viene_de_convocatorias_becas();

            // Si viene de la operación "Convocatorias a Becas"
            if ($viene_de_convocatorias_becas && kernel::sesion()->esta_seteada(becas::CLAVE_SECCIONES_DATOS_CENSALES_SESION)) {
                return kernel::sesion()->get(becas::CLAVE_SECCIONES_DATOS_CENSALES_SESION);
            }
            
            $salida = [];
            foreach (self::$FORMS as $seccion) {
                    if (util_form_censo::hay_visibles_seccion($seccion)) {
                            $salida[] = $seccion;
                    }
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
        
		if ($this->existe_form($id)) {
			$clase = "operaciones\datos_censales\pagelet_$id";
			return kernel::localizador()->instanciar($clase, $id);
		}
		
		throw new \siu\errores\error_guarani_seguridad("El pagelet '$id' no existe");
    }
	
	function get_campos_pagelet()
	{
		return util_form_censo::get_campos_secciones();
	}
	
}