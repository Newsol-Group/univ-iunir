<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Cursos
 * 
 */
class ci_completar_pagos_pendientes extends ci_navegacion
{
	function get_msg_eof($dep = null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array('registros'));
	}

	function evt__cuadro__pagar($seleccion)
	{
		// Verifica si la solicitud sigue activa. Si sigue activa devuelve 
		// el mismo token, si expiró genera una nueva suscripción derivada de la 
		// original y retorna el nuevo token
		$token_sq = $seleccion['sq_token'];
		$resultado_sq = cobro::actualizar_solicitud_consumo_externo($seleccion['sq_token']);
		$nuevo_token_sq = $resultado_sq['token'];

		if ($token_sq != $nuevo_token_sq) {
			//Si el token expiró se lo reemplaza
			guarani::act('act_inscripcion_cursadas')->reemplazar_SQ_token($token_sq, $nuevo_token_sq);
		}
		
		$url = guarani::parametros()->get_valor('sq_url_pagos') . $seleccion['sq_token'];
		echo toba_js::abrir();
		echo "document.location.href='" . $url . "'\n";
		echo toba_js::cerrar();
	}

}
?>