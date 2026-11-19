<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage	Datos Censales
 * 
 */
class ci_nav_datos_censales extends ci_nav_sin_listado_inicial
{
	/**
	 * Se utiliza una variable diferente para el control de cambios en la relación, que se setea según un método del cn entidad,
	 * porque al cargar la relación se asignan datos iniciales que no deben considerarse como cambios (por lo tanto no puede
	 * utilizarse la funcion hay_cambios del datos relación).
	 */
	protected $s__cambios_relacion = false;

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$cuadro->set_eof_mensaje($this->get_msg_eof('Personas'));
	}

	//--------------------------------------------------------------------------
	//---- ev_info -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre_completo', 'documento'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('nombre_completo', 'Apellido y Nombres');
			$this->ev_info->set_etiqueta('documento', 'Identificación');
			$this->ev_info->set_td_etiqueta_width('120px');
		}
		return $this->ev_info;
	}

	function get_datos_ev_info()
	{
		return toba::consulta_php('co_personas')->get_datos_persona($this->s__seleccion['persona']);
	}

	//--------------------------------------------------------------------------
	//---- Pantallas  ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Se redefine el método de configuración de la pantalla de edición para invocar al método correspondiente del cn entidad,
	 * en lugar del hay_cambios del dato relación, ante el cambio de pantalla (de tabs).
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_datos_ev_info());

		if ($this->pantalla('edicion')->existe_evento('eliminar')) {
			if ($this->cn()->entidad_es_nueva()) {
				$pantalla->eliminar_evento('eliminar');
			} else {
				$this->evento('eliminar')->set_msg_confirmacion('¿Confirma que desea eliminar?');
			}
		}
		$this->s__cambios_relacion = $this->cn()->entidad()->hay_cambios_relacion();
	}

	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		if ($this->s__cambios_relacion) {
			$confirmar = 'var confirmar = true;';
		} else {
			$confirmar = 'var confirmar = toba.hay_cambios();';
		}
		echo $this->js_evt_cancelar();
		echo "
			{$this->objeto_js}.evt__guardar = function() {
				return confirmar_no_cambios();
			}

			function confirmar_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (confirmar) {
						resp = confirm('$msj_confirmacion');
						if (! resp) {
							return false;
						}
					}
				}
				return true;
			}

			function confirmar_no_cambios(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (! confirmar) {
						alert('$msj_sin_cambios');
						return false;
					}
				}
				return true;
			}

            toba.set_callback_menu(confirmar_cambios);
		";
	}

}
?>