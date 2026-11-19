<?php
/**
 *
 * @package Operaciones Generales
 * @subpackage Comisiones/Reportes
 */

class ci_rep_distribucion_alumnos extends ci_impresion_popup
{
	//Componentes Visuales de información de selecciones previas
	protected $ev_info;
	
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('alumnos');

		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');
		if ($reporte != pro_distribuir_comisiones::reporte_alumnos_no_distribuidos) {
			$this->dep('cuadro')->eliminar_columnas(array('observaciones'));
		}
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
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
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));
		
		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);
		
		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'TIPO DE DISTRIBUCION: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['tipo_distribucion'],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'ACTIVIDAD: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['actividad'],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'AÑO ACADÉMICO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['anio'],'border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'PERIODO LECTIVO: ','border'=>'none','font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['periodo_lectivo'], 'border'=>'none','font-weight'=>'bold','text-align'=>'left')
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
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(array('tipo_distribucion', 'anio', 'periodo_lectivo', 'actividad'));
			$this->ev_info->set_columnas_fila(array('tipo_distribucion'), 1);
			$this->ev_info->set_columnas_fila(array('anio', 'periodo_lectivo', 'actividad'), 2);
			$this->ev_info->set_etiqueta('actividad', 'Actividad');
			$this->ev_info->set_etiqueta('anio', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('tipo_distribucion', 'Tipo de Distribucion');
		}
		return $this->ev_info;
	}
}
?>