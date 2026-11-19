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
						<td align='left'>
							<strong>{$descripcion['actividad_cod_nombre']} - {$descripcion['comision_nombre']}</strong>
						</td>
						<td align='right'>
							<strong>{$descripcion['descr_porcentaje_asistencia']}</strong> 
						</td>
					</tr>
                    <tr>
						<td align='left'>
							<strong>{$descripcion['descr_total_inasistencias']}</strong>&nbsp;&nbsp;
                            <strong>{$descripcion['descr_total_inasistencias_justificadas']}</strong>
						</td>
					</tr>
				</table>
		";
		echo $texto;
	}
}

class cuadro_inasistencias_salida_excel extends guarani_ei_cuadro_salida_excel
{
	function excel_cabecera_cc_contenido__actividad_comision(&$nodo)
	{
		$descripcion = $nodo['descripcion'];
		$alumno = !empty(current($this->_cuadro->get_datos())) ? current($this->_cuadro->get_datos())['alumno'] : "";
		if($alumno){
			$datos_alumnos = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($alumno);
			//toba::logger()->crit($datos_alumnos);
					$texto = "				
					Alumno/a: {$datos_alumnos['apellido_y_nombre']}
					Propuesta: {$datos_alumnos['propuesta_nombre']}
					Ubicación: {$datos_alumnos['ubicacion_nombre']}
					Legajo: {$datos_alumnos['legajo']}
					Plan: {$datos_alumnos['plan_nombre']}
					{$descripcion['actividad_cod_nombre']}
					{$descripcion['comision_nombre']}
					{$descripcion['descr_porcentaje_asistencia']}
					{$descripcion['descr_total_inasistencias']}
					{$descripcion['descr_total_inasistencias_justificadas']}
				";			
		} else {
			$texto = "{$descripcion['actividad_cod_nombre']} - {$descripcion['comision_nombre']} - {$descripcion['descr_porcentaje_asistencia']} - {$descripcion['descr_total_inasistencias']} - {$descripcion['descr_total_inasistencias_justificadas']}";
		}
		$this->_objeto_toba_salida->texto($texto, $this->_excel_cabecera_cc_0_opciones, $this->_cuadro->get_cantidad_columnas(), $this->_excel_cabecera_cc_0_altura);
	}
}
?>