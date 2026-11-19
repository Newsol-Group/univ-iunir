<?php
/**
 * 
 * @package Encuestas
 */
class ci_nav_vincular_encuestas extends ci_navegacion
{
	protected $s__encuestas_kolla;

	function ini__operacion()
	{
		if (!encuesta::conexion_kolla_ok()) {
			$mje = guarani_mensajes::get('800ENC_servicio_web_kolla');
			throw guarani::error($mje);
		}
	}

	/**
	 * Obtiene un conjunto de encuestas desde Kolla para la Unidad de Gestión especificada.
	 * Si no se envía un valor, se obtiene el default (parametrizado).
	 * 
	 * @param int $ug
	 * @return array
	 */
	function get_encuestas_kolla($ug = null)
	{
		try {
			if (is_null($ug)) {
				$ug = encuesta::get_ug_default_kolla();
			}
			return encuesta::get_encuestas($ug);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return array(array('encuesta' => 'nopar', 'nombre' => 'Unidad de Gestión Kolla inválida.'));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (!encuesta::kolla_utiliza_ug()) {
			$filtro->eliminar_columnas(array('kolla_unidad_gestion'));
		}
		parent::conf__filtro($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado($this->get_filtro_condicion());
		$encuestas_kolla = rs_convertir_asociativo($this->s__encuestas_kolla, array('encuesta'), 'nombre');
		foreach ($datos as $clave => $valor) {
			if (isset($valor['kolla_id_encuesta']) && isset($encuestas_kolla[$valor['kolla_id_encuesta']])) {
				$datos[$clave]['titulo_kolla'] = $encuestas_kolla[$valor['kolla_id_encuesta']];
			}
		}
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

}
?>