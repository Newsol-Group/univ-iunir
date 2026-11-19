<?php
namespace siu\operaciones\portal_pagos;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo_g3\cobro;
use siu\modelo\entidades\parametro;

class controlador extends controlador_g3w2
{
	public function modelo()
	{
		
	}
	
    function accion__index()
    {
		$ras = \toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion();
		$sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', array_column($ras, 'valor'));
		
		if (($sq_usa_sanaviron == \comunes::si)) {
			$body = array(
				"tipo_documento" => kernel::persona()->get_tipo_documento(),
				"numero_documento" => kernel::persona()->get_nro_documento(),
			);

			$respuesta_SQ = cobro::portal_pagos_gco_jwt($body);
			$token_SQ = $respuesta_SQ['token'];
			$url_sq_pagos = cobro::get_url_portal_SQ_pagos($token_SQ);
			$res['url_sq_pagos'] = $url_sq_pagos;
		}
		//header('Location: http://www.example.com/');
		header('Location: '.$url_sq_pagos);
		
    }

}
?>