<?php
/**
 *
 * 
 * @package Cursadas
 * @subpackage Actualizaciones
 * 
 */
class ci_edi_criterios_prioridad_ingresantes extends guarani_pers_ci
{
	protected $criterios_seleccionados;

	// Parámetros de retorno por se llega a la Operación desde otra.
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	
	function ini__operacion()
	{
		$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
		$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
		$this->cn()->cargar_criterio_prioridad_ingresantes();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- ml_asociados -----------------------------------------------------------------
	
	function conf__ml_asociados(toba_ei_formulario_ml $form_ml)
	{
		$criterios_activos = $this->cn()->get_criterios(true);
		$form_ml->set_datos($criterios_activos);
	}

	function evt__ml_asociados__eliminar($seleccion)
	{
		$this->cn()->eliminar_criterio($seleccion);
	}

	function evt__ml_asociados__modificacion($datos)
	{
		try {
			$this->cn()->procesar_criterios($datos);
		} catch (guarani_error $e) {
			throw guarani::error($e->getMessage());
		}
	}
	
	//---- cuadro_disponibles -----------------------------------------------------------
	
	function conf__cuadro_disponibles(cuadro_seleccion_multiple $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_criterios_prioridad_ing');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$criterios_inactivos = $this->cn()->get_criterios(false);
		$cuadro->set_datos($criterios_inactivos);
	}

	function evt__cuadro_disponibles__seleccion($seleccion)
	{
		$this->criterios_seleccionados = $seleccion;
	}
	
	function evt__cuadro_disponibles__agregar()
	{
		if (empty($this->criterios_seleccionados)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un registro'));
			throw guarani::error($mje);
		}
		foreach ($this->criterios_seleccionados as $criterio) {
			$this->cn()->agregar_criterio($criterio);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->cn()->resetear();
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			$parametros = array();
			$parametros['retorno'] = true;
			echo toba_js::abrir();
			echo "document.location.href='".
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, $parametros, array('celda_memoria'=>$this->s__celda_a_restaurar))."'\n";
			echo toba_js::cerrar();
		}
	}

	function evt__procesar()
	{
		try {
			$this->cn()->sincronizar();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
			if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
				$parametros = array();
				$parametros['retorno'] = true;
				echo toba_js::abrir();
				echo "document.location.href='".
				toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, $parametros, array('celda_memoria'=>$this->s__celda_a_restaurar))."'\n";
				echo toba_js::cerrar();
			}
		} catch (guarani_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
		}
	}
}
?>