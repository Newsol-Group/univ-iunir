<?php
class ci_reporte_prorrogas_registradas extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- Componente visual para mostrar Información -----------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 40%;');
			$this->ev_info->set_max_chars_por_columna(200);
			$this->ev_info->set_columnas_a_mostrar(array('requisito'));
			$this->ev_info->set_etiqueta('requisito', 'Requisito');
		}
		return $this->ev_info;
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}
	
	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('4cm');
		
		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------
	
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);
	
		//Tabla para el título y logo
		$this->xml_set_tabla_cols();
		
		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));
		
		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);
		
		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'REQUISITO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['requisito'],'number-columns-spanned'=>'5','border'=>'none','font-weight'=>'bold', 'text-align'=>'left')
						);
					
		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
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
	
}
?>