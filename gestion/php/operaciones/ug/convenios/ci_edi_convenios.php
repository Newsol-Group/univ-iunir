<?php
class ci_edi_convenios extends guarani_pers_ci
{
	protected $datos_temp;
	
	//-----------------------------------------------------------------------------------
	//---- form_datos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos(toba_ei_formulario $form)
	{
		$datos['emite_certificado'] = comunes::no;
		$datos['realizado_con'] = convenio::realizado_con_nacionales;
		$form->set_datos_defecto($datos);

		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_princ_conv());
		}
	}

	function evt__form_datos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_princ_conv($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna el conjunto de propuestas de tipo "Convenio".
	 * 
	 * @return array
	 */
	function get_propuestas()
	{
		$filtro = 'sga_propuestas.propuesta_tipo = ' . propuesta::tipo_convenio;
		return toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas($filtro);
	}

	/**
	 * Retorna el conjunto de estados válidos para un convenio.
	 * 
	 * @return array
	 */
	function get_estados()
	{
		$estados = toba::consulta_php('co_convenios')->get_listado_estados();
		// Si se trata de un alta se excluye el estado "Baja".
		if ($this->cn()->entidad()->es_nueva()) {
			foreach ($estados as $clave => $valor) {
				if ($valor['valor'] == convenio::estado_baja) {
					unset($estados[$clave]);
				}
			}
		}
		return $estados;
	}
}
?>