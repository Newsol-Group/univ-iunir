<?php
class ci_vis_parametros extends guarani_pers_ci
{
	protected $params_operacion;
	protected $codigo_operacion;

	function ini__operacion()
	{
		parent::ini__operacion();

		$this->codigo_operacion = toba::memoria()->get_parametro('codigo_operacion');
		if (!empty($this->codigo_operacion)) {
			$this->params_operacion = guarani::parametros()->get_parametros_operacion($this->codigo_operacion);
			$this->formatear_parametros_operacion();
			$nombre_operacion = toba::memoria()->get_parametro('nombre_operacion');
			if (!empty($nombre_operacion)) {
				$titulo = $this->get_titulo() . ' "' . $nombre_operacion . '"';
				$this->set_titulo($titulo);
			}
		}
	}

	function formatear_parametros_operacion()
	{
		foreach ($this->params_operacion as $key => $dato) {
			if ((isset($dato['no_cargado_por_dependencia'])) && ($dato['no_cargado_por_dependencia'])) {
				$this->params_operacion[$key]['estilo'] = 'param-no-cargado-dep';
			} else {
				$this->params_operacion[$key]['no_cargado_por_dependencia'] = 0;
				if ((isset($dato['hubo_conflicto'])) && ($dato['hubo_conflicto'])) {
					if ($this->no_usar_resolucion_automatica_conflicto($dato['parametro'])) {
						$descripcion_nivel = parametro::get_descripcion_nivel($dato['nivel'], true);
						$this->params_operacion[$key]['valor_descr'] = '--';
						$this->params_operacion[$key]['observaciones'] = "No existe un único valor posible. Varía en algunos casos dependiendo de la selección de $descripcion_nivel.";
					} else {
						$this->params_operacion[$key]['estilo'] = 'param-conflicto-nivel';
					}
				}
			}
		}
		$this->params_operacion = rs_ordenar_por_columnas($this->params_operacion, array('nivel', 'no_cargado_por_dependencia', 'parametro'));
	}

	protected function no_usar_resolucion_automatica_conflicto($parametro)
	{
		return toba::consulta_php('co_parametros')->no_usar_resolucion_automatica_conflicto($parametro, $this->codigo_operacion);
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_manejador_salida('html', 'cuadro_parametros_salida_html');
		if (!empty($this->params_operacion)) {
			$cuadro->set_datos($this->params_operacion);
		}
	}

	//--------------------------------------------------------------------------
	//---- Extensión Javascript ------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}

}
?>