<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class ci_detalle_reglas_de_calculo_utilizadas extends guarani_pers_ci
{
	protected $s__ubicacion;
	protected $s__propuestas;
	protected $s__propuestas_sin_reglas;
	protected $ev_info;
	
	function ini()
	{
		$parametro_ubicacion = toba::memoria()->get_parametro('ubicacion');
		$parametro_propuestas = toba::memoria()->get_parametro('propuestas');
		$parametro_prop_sin_reglas = toba::memoria()->get_parametro('propuestas_sin_reglas');	
		if (!empty($parametro_ubicacion) && !empty($parametro_propuestas)) {
			$this->s__ubicacion = $parametro_ubicacion;
			$this->s__propuestas = explode(',', $parametro_propuestas);
			$this->s__propuestas_sin_reglas = ($parametro_prop_sin_reglas == '') ? '' : explode(',', $parametro_prop_sin_reglas);
		}
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!empty($this->s__ubicacion)) {
			$info_ubicacion = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($this->s__ubicacion);
			$this->ev_info()->set_datos($info_ubicacion);
		}
	}

	function conf__cuadro_reglas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		$cuadro->set_eof_mensaje('No hay propuestas con reglas definidas para la ubicación.');
		if (!empty($this->s__ubicacion) && !empty($this->s__propuestas)) {
			$datos = toba::consulta_php('co_readmisiones')->get_reglas_calculo_regularidad_propuestas_ubicacion($this->s__propuestas, $this->s__ubicacion);					
			$cuadro->set_datos($datos);
		}
	}

	function conf__cuadro_sin_reglas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje('No hay propuestas sin reglas definidas para la ubicación.');
		if (!empty($this->s__propuestas_sin_reglas)) {
			$filtro = guarani_sql::armar_condicion_in($this->s__propuestas_sin_reglas, 'sga_propuestas', 'propuesta');
			$datos = toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas($filtro);					
			$cuadro->set_datos($datos);
		}
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_columnas_a_mostrar(array('ubicacion_nombre'));
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
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