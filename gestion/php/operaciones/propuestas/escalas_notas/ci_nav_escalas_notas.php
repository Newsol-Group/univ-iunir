<?php
/**
 *
 *
 * @package		Propuestas
 * @subpackage	EscalaDeNotas
 * 
 */
class ci_nav_escalas_notas extends ci_navegacion
{
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf_evt__cuadro__copiar_escala(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['tipo'] == escalas_notas::tipo_discreta) {
			$evento->activar();
			// Confirmación
			$mensaje = guarani_mensajes::get('800_aviso_copiar', array($datos[$fila]['nombre']));
			$evento->set_msg_confirmacion($mensaje);
		} else {
			$evento->anular();
		}
	}

	function evt__cuadro__copiar_escala($seleccion)
	{
		$id_nueva_escala = $this->cn()->copiar_escala($seleccion['escala_nota']);
		$condicion = array('escala_nota' => $id_nueva_escala);
		$this->evt__cuadro__seleccion($condicion);
	}

}
?>