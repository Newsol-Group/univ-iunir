<?php
class ci_reporte_mesas_llamados extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	//Parámetro que indica el índice del reporte a recuperar y columnas a mostrar.
	protected $s__mostrar_obs;
	
	function ini()
	{
		parent::ini();
		$this->s__datos	= guarani_datos_impresion::instancia()->get_datos(mesa_de_examen::ind_mesas_llamados);
		/**
		 * Se chequea si está seteada como true la condición de mostrar observaciones de las Mesas creadas en los 
		 * parámetros del popup. Esto permite ocultar o mostrar las columnas que correspondan, ya que este item se 
		 * utiliza desde más de una operación y para varios reportes.
		 */
		$param_mostrar_obs = toba::memoria()->get_parametro(mesa_de_examen::mostrar_observaciones);
		if (!empty($param_mostrar_obs)) {
			$this->s__mostrar_obs = $param_mostrar_obs;
		}
		if ($this->s__mostrar_obs) {
			$this->s__titulo = 'Reporte de Mesas creadas con Observaciones';
		} else {
			$this->s__titulo = 'Reporte de Mesas creadas correctamente';
			$this->dependencia('cuadro')->eliminar_columnas(array('observaciones'));
		}
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- Componente visual para mostrar Información ------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');	
			$this->ev_info->set_columnas_a_mostrar(array(mesa_de_examen::ind_encab_anio, mesa_de_examen::ind_encab_turno, mesa_de_examen::ind_encab_instancias));
			$this->ev_info->set_columnas_fila(array(mesa_de_examen::ind_encab_anio, mesa_de_examen::ind_encab_turno, mesa_de_examen::ind_encab_instancias), 1);
			$this->ev_info->set_etiqueta(mesa_de_examen::ind_encab_anio, 'Año Académico');
			$this->ev_info->set_etiqueta(mesa_de_examen::ind_encab_turno, 'Turno');
			$this->ev_info->set_etiqueta(mesa_de_examen::ind_encab_instancias, 'Instancias');
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
		
		//Se generan los datos básicos de las mesas y se resetean las columnas de tablas y $fila.
		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'AÑO ACADÉMICO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf[mesa_de_examen::ind_encab_anio],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'TURNO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf[mesa_de_examen::ind_encab_turno], 'border'=>'none','font-weight'=>'bold','text-align'=>'left')
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