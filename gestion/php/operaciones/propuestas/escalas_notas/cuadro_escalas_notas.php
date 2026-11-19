<?php
class cuadro_escalas_notas extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_escalas_notas_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_escalas_notas_salida_excel');
	}
}

class cuadro_escalas_notas_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__escala_nota(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>
					Escala: {$descripcion['nombre_escala']} <br>
					Descripción: {$descripcion['descripcion_escala']} <br>
					Estado: {$descripcion['escala_estado_descr']}
				</strong>
		";
		
		echo $texto;
	}
}

class cuadro_escalas_notas_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__escala_nota(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "Escala: {$descripcion['nombre_escala']} - Descripción: {$descripcion['descripcion_escala']} - Estado: {$descripcion['escala_estado_descr']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>