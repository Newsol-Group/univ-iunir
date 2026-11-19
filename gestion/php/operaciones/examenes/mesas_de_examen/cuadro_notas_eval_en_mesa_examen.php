<?php
class cuadro_notas_eval_en_mesa_examen extends guarani_pers_ei_cuadro
{
	function inicializar($parametros = array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_notas_eval_en_mesa_examen_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_notas_eval_en_mesa_examen_salida_excel');
	}
}

class cuadro_notas_eval_en_mesa_examen_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__mesa_llamado(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<strong>
					Mesa: {$descripcion['mesa_nombre']} <br>
					Llamado: {$descripcion['llamado_nombre']}
				</strong>
		";
		echo $texto;
	}
}

class cuadro_notas_eval_en_mesa_examen_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__mesa_llamado(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "Mesa: {$descripcion['mesa_nombre']} - Llamado: {$descripcion['llamado_nombre']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>