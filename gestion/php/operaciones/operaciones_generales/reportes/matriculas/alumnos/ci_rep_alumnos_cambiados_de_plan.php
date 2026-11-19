<?php
class ci_rep_alumnos_cambiados_de_plan extends ci_impresion_popup
{
	//Componentes Visuales de información de selecciones previas
	protected $ev_info;
	
	function ini()
	{
		parent::ini();		
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('alumnos');
		// El reporte muestra los alumnos cambiados de plan-versión.
		if (!is_null($this->s__datos)) {
			$this->s__titulo = 'Reporte de Alumnos cambiados de Plan';
			$this->dep('cuadro')->eliminar_columnas(array('motivo'));
		} else {
			// El reporte muestra los alumnos que no se pudieron cambiar de plan-versión.
			$this->s__datos = guarani_datos_impresion::instancia()->get_datos('alumnos_no_copiados');
			$this->s__titulo = 'Reporte de Alumnos no cambiados de Plan';
			$this->dep('cuadro')->eliminar_columnas(array('plan_destino_nombre', 'version_destino_nombre'));
		}       
	}
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_conf);
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
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>3),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>3));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>6));
		
		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);
		
		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'PROPUESTA ORIGINAL: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['propuesta'],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'PLAN ORIGINAL: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['plan'], 'border'=>'none','font-weight'=>'bold','text-align'=>'left'),
						array('valor'=>'VERSIÓN ORIGINAL: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['version'], 'border'=>'none','font-weight'=>'bold','text-align'=>'left')
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

	//---- Componente visual para mostrar Información -----------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_titulo('Origen');
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(array('propuesta', 'plan', 'version'));
			$this->ev_info->set_columnas_fila(array('propuesta', 'plan', 'version'), 1);
			$this->ev_info->set_etiqueta('propuesta', 'Propuesta Oríginal');
			$this->ev_info->set_etiqueta('plan', 'Plan Original');
			$this->ev_info->set_etiqueta('version', 'Versión Original');
		}
		return $this->ev_info;
	}
}
?>