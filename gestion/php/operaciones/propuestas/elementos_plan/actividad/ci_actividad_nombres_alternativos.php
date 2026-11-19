<?php
class ci_actividad_nombres_alternativos extends guarani_pers_ci
{
	protected $s__actividad;

	function ini()
	{
		$parametro_actividad = toba::memoria()->get_parametro('actividad');
		if (!empty($parametro_actividad)) {
			$this->s__actividad = $parametro_actividad;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__actividad)) {
			$nombre_por_plan = true;
			$datos = toba::consulta_php('co_elementos')->get_actividad_nombres_alternativos($this->s__actividad, $nombre_por_plan);
			$cuadro->set_datos($datos);
		}
	}

	function extender_objeto_js()
	{
		$id_js_cuadro = $this->dependencia('cuadro')->get_id_objeto_js();
		echo "
			{$id_js_cuadro}.evt__cerrar = function() {
				window.close();
			}
		";
	}
}
?>