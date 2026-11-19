<?php
class cuadro_inasistencias extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('html', 'cuadro_inasistencias_salida_html');
		$this->set_manejador_salida('excel', 'cuadro_inasistencias_salida_excel');
	}
}

class cuadro_inasistencias_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera_cc_contenido__actividad_comision(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "
				<table width=100%>
					<tr>
						<td>
							<strong>Año: {$descripcion['anio_academico']}</strong>
						</td>
						<td>
							<strong>P.Lectivo: {$descripcion['periodo_nombre']}</strong>
						</td>
					</tr>
					<tr>
						<td>
							<strong>{$descripcion['actividad_descr']}</strong>
						</td>
						<td>
							<strong>Comisión: {$descripcion['comision_nombre']}</strong>
						</td>
						<td>
							<strong>Responsabilidad: {$descripcion['responsabilidad_nombre']}</strong>
						</td>
					</tr>
				</table>
		";
		echo $texto;
	}
}

class cuadro_inasistencias_salida_excel extends toba_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__actividad_comision(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$texto = "Año: {$descripcion['anio_academico']} - P.Lectivo: {$descripcion['periodo_nombre']} - {$descripcion['actividad_descr']} - Comisión: {$descripcion['comision_nombre']} - Responsabilidad: {$descripcion['responsabilidad_nombre']}";
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>