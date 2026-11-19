<?php
class ci_rep_req_vencidos extends ci_impresion_popup
{
	protected $s__datos;
	protected $s__datos_requisito;

	function ini()
	{
		parent::ini();
		$persona = toba::memoria()->get_parametro('persona');
		$requisito = toba::memoria()->get_parametro('requisito');
		$this->s__datos = toba::consulta_php('co_requisitos')->get_requisitos_vencidos_persona($persona, $requisito);
	}

	/**
	 * Configuración parametrizada que determina si se utiliza Repositorio Digital 
	 * Integrado (RDI) para almacenar los archivos asociados a requisitos documentales.
	 * 
	 * @return boolean
	 */
	protected function usa_repositorio_digital()
	{
		return guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}

	function conf_evt__cuadro__descargar(toba_evento_usuario $evento, $fila)
	{
		if ($this->usa_repositorio_digital()) {
			$datos = $this->dep('cuadro')->get_datos();
			if (empty($datos[$fila]['id_documento'])) {
				$evento->anular();
			} else {
				$evento->vinculo()->set_parametros(array('requisito_digital' => $datos[$fila]['requisito_digital']));
			}
		} else {
			$evento->anular();
		}
	}

}
?>