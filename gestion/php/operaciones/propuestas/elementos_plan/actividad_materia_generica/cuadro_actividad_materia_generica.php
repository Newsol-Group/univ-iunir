<?php
class cuadro_actividad_materia_generica extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_actividad_materia_generica_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_actividad_materia_generica_salida_excel');
	}
}

class cuadro_actividad_materia_generica_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__generica(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>
					({$descripcion['codigo_materia_generica']}) {$descripcion['nombre_materia_generica']} <br>
					Forma de Cumplimiento: {$descripcion['forma_cumplimiento']} <br>
					Origen de Créditos: {$descripcion['origen_credito']}
				</strong>
		";
		echo $texto;
	}
}

class cuadro_actividad_materia_generica_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__generica(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "({$descripcion['codigo_materia_generica']}) {$descripcion['nombre_materia_generica']} - Forma de Cumplimiento: {$descripcion['forma_cumplimiento']} - Origen de Créditos: {$descripcion['origen_credito']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>