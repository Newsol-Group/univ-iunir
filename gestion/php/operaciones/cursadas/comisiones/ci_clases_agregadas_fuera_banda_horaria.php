<?php
class ci_clases_agregadas_fuera_banda_horaria extends guarani_pers_ci
{
	protected $s__comision;

	function ini()
	{
		$parametro_comision = toba::memoria()->get_parametro('comision');
		if (!empty($parametro_comision)) {
			$this->s__comision = $parametro_comision;
		}
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_clases');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));
		if (isset($this->s__comision)) {
			$filtro = 'sga_asignaciones.fecha_desde = sga_asignaciones.fecha_hasta';
			$datos = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones_comision($this->s__comision, $filtro);
			$cuadro->set_datos($datos);
		}
	}

	function extender_objeto_js()
	{
		echo "
			{$this->objeto_js}.evt__cerrar = function() {
				window.close();
			}
		";
	}
}
?>