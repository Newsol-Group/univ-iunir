<?php
class cuadro_comisiones_asignadas_division extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_comisiones_asignadas_division_salida_html');
	}
}

class cuadro_comisiones_asignadas_division_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__division_turno_nombres(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$tag_inicio_font = '';
		$tag_fin_font = '';
		
		if ($descripcion['division_turno_nombres_desc'] == 'Sin división asociada') {
			$tag_inicio_font = "<font color='#1414B4'>";
			$tag_fin_font = '</font>';
		}
		
		echo $texto = "
					<table width=100%>
						<tr>
							<td>
								$tag_inicio_font <strong>{$descripcion['division_turno_nombres_desc']}</strong> $tag_fin_font
							</td>
						</tr>
					</table>
					";
	}
}

class cuadro_comisiones_asignadas_division_salida_excel extends toba_ei_cuadro_salida_excel
{
	function html_cabecera_cc_contenido__division_turno_nombres(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "{$descripcion['division_turno_nombres_desc']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>