<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class ci_nav_enviar_mensajes extends ci_navegacion
{
	function evt__agregar()
	{
		parent::evt__agregar();
		$this->cn()->entidad()->resetear_adjuntos_temporales();
	}

	function evt__cancelar()
	{		
		$this->cn()->entidad()->resetear();
		$this->dep('editor')->resetear();		
		parent::evt__cancelar();
	}

	function evt__cuadro__seleccion($seleccion)
	{
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->entidad()->resetear_adjuntos_temporales();
	}

	function js_evt_cancelar()
	{
		$salida = '';
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');

		if ($this->hay_cambios) {
			$confirmar = 'var confirmar = true;';
		} else {
			$confirmar = 'var confirmar = toba.hay_cambios();';
			$salida = 'var adjuntos_modificados;';
		}

		$salida .= "
		
			{$this->objeto_js}.evt__cancelar = function() {
				return confirmar_cambios_o_adj_eliminados();
			}
			
			function confirmar_cambios_o_adj_eliminados(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (confirmar || adjuntos_modificados) {
						resp = confirm('$msj_confirmacion');
						if (! resp) {
							return false;
						}
					}
				}
				return true;
			}
			";

		return $salida;
	}
	
	function get_listado_alcances()
	{
		return $this->cn()->get_alcances();
	}

}
?>