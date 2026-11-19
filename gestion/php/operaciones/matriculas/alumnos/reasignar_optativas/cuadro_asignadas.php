<?php
class cuadro_asignadas extends guarani_pers_ei_cuadro
{
	//---- Config. EVENTOS sobre fila ---------------------------------------------------

	function conf_evt__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->datos[$fila];
		$optativa =  '('.$datos['optativa_codigo'].') '. $datos['optativa_nombre']. ' de la';
		$generica = $datos['generica_asignada'];		
		$msj = guarani_mensajes::get('800MAT_reasignar_optativas', array('desasignar', $optativa, $generica));
		$evento->set_msg_confirmacion($msj);
	}
}
?>