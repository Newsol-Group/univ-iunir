<?php
class ci_reporte_actas_masivas_cursadas extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos();
		$this->s__titulo = $this->s__datos['titulo'] ?? 'Listado de Actas Generadas';
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_titulo($this->s__titulo);
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos['actas']);
	}
	
	function vista_xml($inicial = false, $xmlns = null)
	{
		// ----------------------------------------------------------
		//	Configuraciones Iniciales
		// ----------------------------------------------------------
		
		//Modifico las dimensiones de la página y los márgenes
		$this->xml_set_margenes(array('sup'=>'0.3cm', 'inf'=>'0.3cm', 'izq'=>'0.3cm', 'der'=>'0.3cm'));
		$this->xml_set_alto_cabecera('4cm');
		$this->xml_set_alto_pie('1cm');
		
		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------
		
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);
		
		//Tabla para el título y logo
		$this->xml_set_tabla_cols();
		
		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none'),array('valor'=>'', 'border'=>'none'));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>2));
		
		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas);
		
		//Se generan los datos básicos del acta y se resetean las columnas de tablas y $fila.
		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'AÑO ACADÉMICO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['anio_academico'],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'PERIODO LECTIVO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['nombre_periodo'],'border'=>'none','font-weight'=>'bold','text-align'=>'left')
						);
		$filas[] = array(array('valor'=>'', 'border'=>'none'));
		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas);
		$this->xml_set_cabecera($cabecera);
				
		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------
		
		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}
		
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre_periodo'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_periodo'));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
		}
		
		return $this->ev_info;
	}
}
?>