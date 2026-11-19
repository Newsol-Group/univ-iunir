<?php

namespace nsg01\operaciones\datos_censales;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\transacciones\becas;

class vista extends \siu\operaciones\datos_censales\vista
{

	// Pagelets del formulario de preinscripción
	static $FORMS = [
		'datos_personales',
		'domicilio',
		'datos_contacto',
		'datos_estudios',
		'datos_laborales',
		'tecnologia',
		'deportes',
		'idiomas',
		'datos_salud',
		'actuacion_profesional',
		'discapacidad'
	];


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
}