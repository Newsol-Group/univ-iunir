<?php

class ci_impresion_popup extends ci_impresion 
{
	/*
	* La idea de esta clase es que extiendan de la misma los popups para impresiones de forma tal que el comportamiento estandar
	* ya esté modelado. Particularmente, esta basada en la impresion de los Comprobantes para Inscripcion a Cursadas,Propuestas, etc...
	* 
	*/
	
	// Determina la orientacion del reporte. 
	// Por defecto no esta definida y se determina de acuerdo a la cantidad de columnas a imprimir
	protected $reporte_vertical;
        
	function ini()
	{
		parent::ini();
		$this->s__datos_imp = guarani_datos_impresion::instancia()->recuperar_datos_imp();
		$this->s__ancho = guarani_datos_impresion::instancia()->get_ancho();
		$this->s__alto = guarani_datos_impresion::instancia()->get_alto();
		$this->s__datos_conf = guarani_datos_impresion::instancia()->get_conf();
		$this->s__titulo = guarani_datos_impresion::instancia()->get_titulo();
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos();
		$this->s__pie = guarani_datos_impresion::instancia()->get_pie();
	}
	
	
	function vista_xml($inicial = false, $xmlns = NULL) 
	{
		
		// ----------------------------------------------------------
		//	Configuraciones Iniciales
		// ----------------------------------------------------------
		//Quito la cabecera y el pie de página
		$this->xml_set_incluir_cabecera(false);
		$this->xml_set_incluir_pie(false);
		
		//Modifico las dimensiones de la página y los márgenes
		$this->xml_set_dim_pagina('100mm');
		$this->xml_set_margenes(array('sup'=>'0.3cm', 'inf'=>'0.3cm', 'izq'=>'0.3cm', 'der'=>'0.3cm'));

		//Hago un recuadro para el comprobante
		$this->xml_set_atts_ei(array('border'=>'medium solid black', 'padding'=>'0.2cm'));		

		//Inicio el tag xml del ci
		$xml = $this->xml_get_tag_inicio();
		
			
		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------
		//Busco el path del logo.
		$path_logo = guarani::get_path_logo_impresion_chico();
		
		//Primera tabla (que reemplaza a la cabecera)
		//Seteo la primer columna a 30% y la segunda a 70% (se puede usar tanto column-width como width para eso... column-width es como 
		//verdaderamente se llama el atributo en el elemento table-column de xsl-fo)
		$cols[] = array('column-width'=>'30%');
		$cols[] = array('width'=>'70%');
		$this->xml_set_tabla_cols($cols);
		//Seteo los datos de la primer tabla.
		//En la primer fila va el logo (con row span) y el nombre de la universidad.
		//En la segunda fila va la facultad.
		$institucion = institucion::get_datos_gestionante();
		$ra = guarani::co('co_propuestas')->get_listado_prop_ras_string("sga_propuestas.propuesta = {$this->s__datos_conf['propuesta']}");

		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'number-rows-spanned'=>2, 'border'=>'none'),array('valor'=>$institucion['nombre'], 'border'=>'none', 'font-weight'=>'bold', 'font-size'=>8));
		$filas[] = array(array('valor'=>$ra, 'border'=>'none', 'font-weight'=>'bold', 'font-size'=>8));
		// Genero/Obtengo la primera tabla.
		$xml .= $this->xml_tabla($filas, false);
		
		// ----------------------------------------------------------
		// 	TITULO
		// ----------------------------------------------------------
		//Agrego el texto subrayado 
		$xml .= $this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>8, 'margin-bottom'=>'0.5cm', 'text-align'=>'center'));
		
		
		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------
		//Segunda tabla (que contiene los datos)
	
		//reseteo las columnas de tablas y $fila
		//Seteo la primer columna a 30% y la segunda a 70% (se puede usar tanto column-width como width para eso... column-width es como 
		//verdaderamente se llama el atributo en el elemento table-column de xsl-fo)
		$cols[] = array('column-width'=>'20%');
		$cols[] = array('width'=>'80%');
		$this->xml_set_tabla_cols($cols);
		
		$filas = array();
		//Agrego las filas (varias con column span)
		foreach ($this->s__datos as $dato) {
			$filas[] = array(array('valor'=>$dato['etiq'].': ','border'=>'none'),array('valor'=>$dato['valor'],'border'=>'none','font-size'=>8,'font-weight'=>'bold', 'text-align'=>'left'));
		}
		$filas[] = array(array('valor'=>'Nro. Transacción: '.$this->s__datos_conf['nro_transaccion'], 'number-columns-spanned'=>'2','border-top'=>'thin solid black', 'text-align'=>'center','font-size'=>12,'font-weight'=>'bold'));

		// Genero/Obtengo la segunda tabla
		$xml .= $this->xml_tabla($filas);

		// ----------------------------------------------------------
		//	PIE
		// ----------------------------------------------------------
		//$xml .= $this->xml_texto('Recordá que el Nro. de Transacción te será útil ante cualquier consulta en la Oficina de Alumnos',array('font-size'=>8, 'margin-bottom'=>'0.5cm', 'text-align'=>'center'));
		$xml .= $this->xml_texto($this->s__pie, array('font-size'=>8, 'margin-bottom'=>'0.5cm', 'text-align'=>'center'));
		
		// ----------------------------------------------------------
		//	Configuraciones Finales
		// ----------------------------------------------------------
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}
	
	//---- Salida JASPER-PDF ----------------------------------------------------------------

	// ------------------------------------------------------------------------------------------
	/* Se definen 2 metodos para obtener los datos del componente para la impresion del reporte.
	* Estos metodos son 'cuadro()' y 'encabezado()'. La idea es que se redefinan estos metodos 
	* con los objetos pertinentes para obtener la informacion a imprimir.
	* Con respecto a cuadro(), se refiere a un objeto toba_ei_cuadro q contiene los datos para
	* el detalle del reporte.
	* En cuanto a datos_encabezado, puede ser tanto un objeto toba_ei_filtro como un ev_info_datos.
	* La idea es que al solicitar el metodo (independientemente del objeto que utiliza el CI para
	* obtener el encabezado del reporte - filtro o ev) se obtengan los datos del encabezado con
	* el siguiente formato:
	* array (
	* 			fila_1 => 	(
	*							campo_1 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*							campo_2 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*						.....		 
	*						)
	*
	* 			fila_2 => 	(
	*							campo_1 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*							campo_2 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*						.....		 
	*						)
	*		)
	*
	*/

	function cuadro() 
	{
		return $this->dep('cuadro');
	}
	
	function datos_encabezado() 
	{
		if ($this->dependencia_cargada('filtro')) {
			$datos = $this->dep('filtro')->get_datos_impresion();
		} else {
			// Esto se podira implementar para pasar el arreglo de datos de un ev como encabezado
			if (isset($this->ev_info)) {
				$datos = $this->ev_info()->get_datos();
				
				$datos_originales = $this->ev_info()->get_datos(false, true);
				foreach ($datos_originales as $clave => $valor) {
					if (is_array($valor)) {
						$datos_originales[$clave] = implode(', ', $valor);
					}
				}
				
				foreach ($datos as $k1 => $d1) {
					foreach ($d1 as $k2 => $d2){
						foreach ($d2 as $k3 => $d3){
							if ($k3 == 'etiqueta') {
								$datos[$k1][$k2][$k3] = str_replace(':','',strip_tags($d3));
							}
							if ($k3 == 'valor') {
								$datos[$k1][$k2][$k3] = str_replace(':','',strip_tags($datos_originales[$k2]));
							}
						}
					}
				}
			} else {
				$datos = array();                            
			}
		}
		return $datos;
	}
	
	function ancho_etiquetas_encabezado()
	{
		return 100;
	}
	
	// ------------------------------------------------------------------------------------------

	function vista_jasperreports(toba_vista_jasperreports $report) 
	{
		//Creo la plantilla (jrxml) para la creacion del reporte
		$jasper = new guarani_jasper();
		// Parametros 
		$parametro['nombre'] = 'logo';
		$parametro['tipo'] = 'S';
		$pars[] = $parametro;
		$jasper->set_parametros($pars);
		// Campos 
		$columnas = $this->cuadro()->get_columnas();
		$datos_fields =  array();
		foreach ($columnas as $columna) {
			$datos_fields[] = array('nombre' => $columna['clave'], 'titulo' => $columna['titulo'], 'grupo' => $columna['grupo']);
		}
		$jasper->set_fields($datos_fields);
                
		// Si la variable $this->reporte_vertical esta seteada tomo la orientación de la misma.
		if (isset($this->reporte_vertical)) {
			$reporte_vertical = $this->reporte_vertical;
		} else {// Orientación (Si cantidad de columnas > 5 se cambia la orientación a horizontal)
			$reporte_vertical = (guarani_arreglos::count($columnas) < 6);
		}
		$jasper->set_orientacion_vertical($reporte_vertical);
		
		// Grupos 
		$grupos = $this->cuadro()->get_indice_cortes();
		$datos =  array();
		if (isset($grupos)) {
			foreach ($grupos as $g => $cols) {
				$cols_aux_id = explode(',', $cols['columnas_id']);
				foreach($cols_aux_id as $field) {
					$campo = trim($field);
					$jasper->add_fields_st(array('nombre' => $campo));
				}
				$cols_aux_desc = explode(',',$cols['columnas_descripcion']);
				foreach($cols_aux_desc as $field) {
					$campo = trim($field);
					$jasper->add_fields_st(array('nombre' => $campo));
				}
				$datos[] = array (
									'nombre'    => $cols['identificador'],
									'campo'     => $cols_aux_id,
									'campo_desc'=> $cols_aux_desc,
									'titulo'    => $cols['descripcion'],
									'pie'       => ($cols['pie_contar_filas'] == '1')
						  );
			}
		}
		$jasper->set_grupos($datos);
		// Titulo 
		$titulo = $this->get_titulo();
		$titulo = (empty($titulo)) ? 'Reporte' : $titulo;
		$jasper->set_titulo($titulo);
		// Encabezado
		$jasper->set_ancho_etiquetas_encabezado($this->ancho_etiquetas_encabezado());
		$jasper->set_datos_encabezado($this->datos_encabezado());
		//Obtengo el archivo jrxml y lo paso como plantilla al reporte
		$archivo = toba::proyecto()->get_path_temp() .'/'. guarani::get_id_operacion() . '.jrxml';
		$jasper->set_jrxml_archivo($archivo);
		
		//Reporte
		//Paso la plantilla 
		$report->compilar_reporte($archivo);
		//Obtengo los datos del cuadro y se lo paso al reporte 
		$xml = $this->cuadro()->vista_xml();
		
		// Queda comentado esto por si a futuro se decide guardar los datos pasados al reporte, como debug en un archivo
		$archivo = toba::proyecto()->get_path_temp() .'/'. guarani::get_id_operacion() . '_datos.xml';
		//Escribo el xml con los datos
		if (file_put_contents($archivo, utf8_encode($xml)) === false) {
			throw new toba_error("Error al guardar archivo de datos xml", "No es posible escribir en ".$archivo);
		}		
		$report->set_archivo_xml($archivo);
		
		$report->set_xml(utf8_e_seguro($xml));
		
		//Paso los parametros
		//Obtengo el logo a imprimir
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
			
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
                
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null,null,'vista_jasperreports', null, [{$this->objeto_js}._id])); 
			return false;
		}
		";                  
                 
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}

}