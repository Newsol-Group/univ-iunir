<?php
class cuadro_mesas_de_examen extends guarani_pers_ei_cuadro
{
	function inicializar($parametros = array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_mesas_de_examen_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_mesas_de_examen_salida_excel');
	}

}
class cuadro_mesas_de_examen_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__mesa_catedra_instancias(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>
					Mesa: {$descripcion['mesa_nombre']} <br>
					Cátedra: {$descripcion['catedra_nombre']} <br>
					Instancia: {$descripcion['instancia_nombre']}
				</strong>
		";
		echo $texto;
	}

}
class cuadro_mesas_de_examen_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__mesa_catedra_instancias(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "Mesa: {$descripcion['mesa_nombre']} - Cátedra: {$descripcion['catedra_nombre']} - Instancia: {$descripcion['instancia_nombre']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}

}
?>