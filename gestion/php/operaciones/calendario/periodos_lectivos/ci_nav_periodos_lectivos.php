<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */

class ci_nav_periodos_lectivos extends ci_navegacion
{
	//---- Configuraciones --------------------------------------------------------------
	
	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
	
	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial=false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function get_msg_eof($etiqueta = null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = $this->cn()->get_listado($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof('Períodos Lectivos'));
	}
	
	function evt__guardar()
	{
		// Se desactivan las transacciones para genererar una única que contenga los dos pasos: guardar y aplanar (si es necesario) el período de inscripción.
		$this->cn()->desactivar_transacciones_anidadas();

		try {
			toba::db()->abrir_transaccion();

			$this->cn()->guardar();

			if ($this->cn()->entidad()->hay_que_generar_aplanado()) {
				// Se ejecuta el proceso de aplanado del período de inscripción.
				$aplanador = toba::cn($this->cn()->get_proceso_aplanado());
				$periodos_insc = $this->cn()->entidad()->get_datos_per_inscr();
				foreach ($periodos_insc as $periodo) {
					$aplanador->procesar($periodo['periodo_inscripcion']);
				}
			}

			$this->cn()->resetear();
			$this->set_pantalla('seleccion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

}
?>