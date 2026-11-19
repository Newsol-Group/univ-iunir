<?php
class ci_vis_evaluaciones_relacionadas extends ci_navegacion
{
	protected $s__evaluacion;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';

		toba::memoria()->desactivar_reciclado();
		$datos = toba::memoria()->get_parametros();
		if (isset($datos['evaluacion'])) {
			$this->s__evaluacion = $datos['evaluacion'];
		}
		if (isset($this->s__evaluacion)) {
			$datos_eval = guarani::co('co_evaluaciones')->get_datos_evaluaciones($this->s__evaluacion);
			$this->set_titulo('Evaluaciones Relacionadas a "'.$datos_eval['nombre'].'"');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$datos = guarani::co('co_evaluaciones')->get_detalles_evaluaciones_relacionadas($this->s__evaluacion, $this->get_filtro_condicion());
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_eval_detalle');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural'))));
	}

	//-----------------------------------------------------------------------------------
	//---- Javascript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_evaluaciones_relacionadas()
	{
		return guarani::co('co_evaluaciones')->get_evaluaciones_padre($this->s__evaluacion);
	}
}
?>