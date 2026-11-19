<?php
class ci_imp_comp_insc_curso extends ci_impresion_popup
{
	function vista_jasperreports(toba_vista_jasperreports $report)
	{	
		// Se procesan los datos, construyendo el xml de la misma manera que Toba exporta un cuadro
		$xml = '<tabla><datos>';
		$xml_fila = '';
		foreach ($this->s__datos as $key => $dato) {
			$xml_fila .= '
				<fila>
					<dato clave="etiq" valor="' . $dato['etiq'] . '"/>
					<dato clave="valor" valor="'. $dato['valor'] . '"/>
				</fila>';
		}
		
		$report->set_parametro('titulo', 'S', $this->s__titulo);			
		$xml .= $xml_fila;
		$xml .= '</datos></tabla>';

		$path = guarani::get_path_reporte_jasper('ComprobanteInscripcion.jasper');
		$report->set_path_reporte($path);
		
		$institucion = institucion::get_datos_gestionante();
		$ra = guarani::co('co_propuestas')->get_listado_prop_ras_string("sga_propuestas.propuesta = {$this->s__datos_conf['propuesta']}");

		// Parámetros 
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('ra', 'S', $ra);	
		$report->set_parametro('institucion', 'S', $institucion['nombre']);
		$report->set_parametro('nro_transaccion', 'S', $this->s__datos_conf['nro_transaccion']);

		//Se agregan los datos (fabrico el xml a mano de la misma manera que Toba exporta un cuadro)
		$report->set_xml(utf8_e_seguro($xml));
	}	
}
?>