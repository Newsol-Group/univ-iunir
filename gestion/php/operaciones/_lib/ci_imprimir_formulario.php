<?php

	/*
	 * Clase generica para implementar la impresion de formularios en JASPER.
	 * Particularmente, se esta utilizando en primer lugar en la impresion de los formularios de los circuitos de EGRESO.
	 * Recibe como parametro el nombre de la plantilla y obtiene de memoria los parametros (nomenclados como 
	 * "nombre_plantilla__parametros") a pasar al reporte (el logo del sistema se pasa automaticamente) 
	 * y genera la salida JASPER del componente.
	 * Lo ideal es asociarla a un componente (CI) que se llame con el servicio  'vista_jasperreports'
	 */

class ci_imprimir_formulario extends guarani_pers_ci
{	
	
	protected $plantilla;
	protected $parametros;
	
	// Se obtienen de la memoria los datos correspondientes a los parametros y al nombre de la plantilla
	function ini()
	{	
		// Obtengo el nombre de la plantilla y los parametros para el reporte
		$this->plantilla = toba::memoria()->get_parametro('plantilla');
		$this->parametros = toba::memoria()->get_dato("{$this->plantilla}__parametros");
	}
	
	//-----------------------------------------------------------------------------------
	//---- IMPRESION JASPER -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function vista_jasperreports(toba_vista_jasperreports $report) 
	{		
		$path = guarani::get_path_reporte_jasper($this->plantilla);
		$report->set_path_reporte($path);
		
		//Seteo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		
		foreach ($this->parametros as $param) {
			/** Los parámetros de tipo Fecha que se obtienen de la base (mce_campos en el circuito de egreso), 
			 *  se convierten a tipo string con el formato visual adecuado, ya que el set parametro de la salida 
			 *  jasper de Toba los pasa como bigdecimal a java, asumiendo que es un timestamp y en este caso, los
			 *  campos de mce_campos son de tipo date y no pueden pasarse con ese tipo en jasper.
			 */
			// Si es vacío lo paso como string vacío
			if (!isset($param['valor'])) {
				$param['valor'] = '';
				$param['tipo_de_dato'] = 'S';
			// Si es una fecha la convierto a string
			} elseif($param['tipo_de_dato'] == 'F') {
				$param['tipo_de_dato'] = 'S';
				$param['valor'] = guarani_fecha::convertir_fecha_formato_frontend($param['valor']);
			}
			
			$report->set_parametro($param['nombre'], $param['tipo_de_dato'] , $param['valor']);				
		}
	}	
		
}