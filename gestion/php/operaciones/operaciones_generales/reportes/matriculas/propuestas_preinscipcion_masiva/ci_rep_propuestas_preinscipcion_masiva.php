<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class ci_rep_propuestas_preinscipcion_masiva extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');		
		$this->s__datos_conf = guarani_datos_impresion::instancia()->get_datos('ev_info_datos');
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('datos');
		
		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');
		if ($reporte != pro_generacion_legajos::reporte_no_generados) {
			$this->dep('cuadro')->eliminar_columnas(array('observaciones'));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// Seteo de datos en ev
		 $this->ev_info()->set_datos($this->s__datos_conf);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_columnas_a_mostrar(array('fuente_de_datos', 'periodo', 'propuestas', 'tipo_ingreso'));
			
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_max_chars_por_columna(80);
			
			$this->ev_info->set_etiqueta('fuente_de_datos', 'Fuente de Datos:');
			$this->ev_info->set_etiqueta('periodo', 'Período de Inscripción:');
			$this->ev_info->set_etiqueta('propuestas', 'Propuestas:');
			$this->ev_info->set_etiqueta('tipo_ingreso', 'Tipo de Ingreso:');
			
			$this->ev_info->set_style('width: 100%');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_titulo($this->s__titulo);
		$cuadro->set_datos($this->s__datos);
	}

	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('6cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto('Reporte de '.$this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		
		$filas[] = array(
						array('valor'=>'FUENTE DE DATOS: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['fuente_de_datos'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'PERÍODO DE INSCRIPCIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['periodo'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'PROPUESTAS: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['propuestas'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'TIPO DE INGRESO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['tipo_ingreso'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
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