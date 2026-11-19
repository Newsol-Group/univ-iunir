<?php
class ci_edi_plan_condiciones extends guarani_pers_ci
{
	protected $s__pantalla = 'seleccion';

	function conf()
	{
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->get_pantalla() == 'seleccion') {
			$this->evento('cancelar')->set_etiqueta('&Volver');
		} else {
			$this->evento('cancelar')->set_etiqueta('&Cancelar');
		}
	}

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'),'info');
		} catch ( guarani_error $e ) {
			toba::notificacion()->agregar($this->get_mensaje('guardar_error'),'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function evt__cancelar()
	{
		$this->cn()->resetear_elemento();
		if (($this->get_pantalla() == 'edicion') || ($this->get_pantalla() == 'copia')) {
			$this->set_pantalla('seleccion');
		} else {
			$this->controlador->evt__cancelar();
		}
	}

	function get_pantalla()
	{
		return $this->s__pantalla;
	}

	function extender_objeto_js()
	{
		echo "
			{$this->objeto_js}.evt__cancelar = function() {
				return this.controlador.evt__cancelar();
			}

			{$this->objeto_js}.evt__guardar = function() {
				return this.controlador.evt__guardar();
			}
		;";
	}

}

?>