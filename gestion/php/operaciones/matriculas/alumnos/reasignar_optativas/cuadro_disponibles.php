<?php
class cuadro_disponibles extends guarani_pers_ei_cuadro
{
	//---- Config. EVENTOS sobre fila ---------------------------------------------------

	function conf_evt__asignar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->datos[$fila];
		/*		
		 * Si tiene generica asignada cambio el msj confirmacion y la etiqueta del evento.
		 */		
		$generica = $this->controlador()->cn()->get_generica();
		$evento->set_etiqueta('Asignar');
		$accion = 'asignar';
		$generica_param = '('.$generica['codigo'].') '. $generica['nombre'];
		if (isset($datos['generica_asignada'])) {		
			$generica_param = $datos['generica_asignada']. " a la Actividad Genérica ({$generica['codigo']}) {$generica['nombre']}";
			$evento->set_etiqueta('Reasignar');
			$accion = 'reasignar';
		}	
		if ($generica['certificado']==comunes::si) {
			$evento->desactivar();
		}	
		$optativa =  '('.$datos['optativa_codigo'].') '. $datos['optativa_nombre']. ' de la';		
		$msj = guarani_mensajes::get('800MAT_reasignar_optativas', array($accion, $optativa, $generica_param));	
		
		$evento->set_msg_confirmacion($msj);
	}

}
?>