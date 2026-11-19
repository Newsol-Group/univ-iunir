<?php
/**
 * 
 * @package Componentes
 * @subpackage Filtro
 */
class guarani_filtro_columna_fecha extends toba_filtro_columna_fecha
{
	function get_pdf_valor()
	{
		$this->_ef->ir_a_fila();	
		$valor = $this->_ef->get_descripcion_estado('pdf');
		if ($this->es_compuesto() && $this->_estado['condicion'] == 'entre') {
			$estado_anterior = $this->_ef->get_estado();
			$this->_ef->ir_a_fila('extra');
			if (isset($this->_estado) && isset($this->_estado['valor']['hasta'])) {
				$this->_ef->set_estado($this->_estado['valor']['hasta']);
				$valor .= ' & ' . $this->_ef->get_descripcion_estado('pdf');
			}			
			$this->_ef->ir_a_fila();
			$this->_ef->set_estado($estado_anterior);
		}
		return $valor;
	}
}