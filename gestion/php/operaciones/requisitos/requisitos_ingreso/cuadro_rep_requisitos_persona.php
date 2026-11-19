<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cuadro_rep_requisitos_persona extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_rep_requisitos_persona_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_rep_requisitos_persona_salida_excel');
	}

	function vista_pdf(toba_vista_pdf $salida)
	{
		$salida->set_papel_orientacion('landscape');
		$salida->inicializar();
		parent::vista_pdf($salida);
	}
}

class cuadro_rep_requisitos_persona_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__persona(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>					
					{$descripcion['persona_nombre']} <br>
					Nro Documento: {$descripcion['persona_documento']}
				</strong>
		";
		echo $texto;
	}
	
	function html_cabecera_cc_contenido__propuesta(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>					
					{$descripcion['propuesta_nombre']} <br>
					Año de Ingreso: {$descripcion['propuesta_anio']}
				</strong>
		";
		echo $texto;
	}
}

class cuadro_rep_requisitos_persona_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__persona(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "{$descripcion['persona_nombre']} - Nro Documento: {$descripcion['persona_documento']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
	
	function excel_cabecera_cc_contenido__propuesta(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "{$descripcion['propuesta_nombre']} - Año de Ingreso: {$descripcion['propuesta_anio']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>