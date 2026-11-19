<?php
class cuadro_comisiones extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		// Si existe el evento 'seleccion', se alinea a izquierda de las columnas y se genera la utilidad "Todos/Ninguno".
		if ($this->existe_evento('seleccion')) {
			$this->evento('seleccion')->set_alineacion_pre_columnas(true);
			$this->set_manejador_salida('html', 'cuadro_comisiones_salida_html');
		}
	}
		
	// Se redefine para que al generar el html del evento selección, se genere como si fuese evento diferido
	// aunque en este caso no sea diferido para agregar la dinámica de validación y pedido de código de comisión.
	function get_invocacion_evento_fila($evento, $fila, $clave_fila, $salida_como_vinculo = false, $param_extra = array())
	{
		$invoc_evt = '';
		$id = $evento->get_id();
		if( ! $evento->esta_anulado() ) { //Si el evento viene desactivado de la conf, no lo utilizo
			//1: Posiciono al evento en la fila
			$evento->set_parametros($clave_fila);
			if($evento->posee_accion_vincular()) {
				$parametros = $param_extra;
				$parametros[apex_ei_evento] = $id;
				$parametros['fila'] = $fila;
				$evento->vinculo(true)->set_parametros($parametros);
			}
			//2: Ventana de modificacion del evento por fila
			//- a - ¿Existe una callback de modificacion en el CONTROLADOR?
			$callback_modificacion_eventos_contenedor = 'conf_evt__' . $this->_parametros['id'] . '__' . $id;
			if (method_exists($this->controlador, $callback_modificacion_eventos_contenedor)) {
				$this->controlador->$callback_modificacion_eventos_contenedor($evento, $fila);
			} else {
				//- b - ¿Existe una callback de modificacion una subclase?
				$callback_modificacion_eventos = 'conf_evt__' . $id;
				if (method_exists($this, $callback_modificacion_eventos)) {
					$this->$callback_modificacion_eventos($evento, $fila);
				}
			}
			//3: Genero el boton o el js para el link
			if( ! $evento->esta_anulado() ) {
				if ($salida_como_vinculo) {								//Si es un vinculo lo que se envia
					$evento->set_en_botonera(false);
					$evento->set_nivel_de_fila(false);
					$evento->ocultar();
					$invoc_evt = $evento->get_invocacion_js($this->objeto_js, $this->_id);
				} else if ($evento->posee_accionar_diferido() || $evento->get_id() == 'seleccion') {		
					//Si es un evento que no dispara submit inmediatamente (solo para el cuadro por ahora)
					$invoc_evt = $evento->get_html_evento_diferido($id .$this->_submit, $fila, $this->objeto_js, $this->_id);
				} else {																				//Cualquier otro evento, inclusive los de multiple seleccion.
					$invoc_evt = $evento->get_html($this->_submit.$fila, $this->objeto_js, $this->_id);
				}
			} else {
				$evento->restituir();	//Lo activo para la proxima fila
			}
		}
		return $invoc_evt;
	}
}

class cuadro_comisiones_salida_html extends toba_ei_cuadro_salida_html
{
	function get_estilo_seleccion($clave_fila)
	{
		return 'ei-cuadro-fila';
	}
}
?>