<?php
/**
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 *
 */
class ci_rep_deteccion_egresados extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('datos');
		$this->s__datos_conf = guarani_datos_impresion::instancia()->get_datos('ev_info_datos');

		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		// Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('propuesta', 'plan', 'plan_version', 'anios', 'certificado'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('propuesta', 'Propuesta');
			$this->ev_info->set_etiqueta('plan', 'Plan');
			$this->ev_info->set_etiqueta('plan_version', 'Versión');
			$this->ev_info->set_etiqueta('anios', 'Año Académico');
			$this->ev_info->set_etiqueta('certificado', 'Certificado');
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
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
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$filas[] = array(
						array('valor'=>'Propuesta: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['propuesta'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'Plan: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['plan'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'Versión: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['plan_version'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'Año Académico: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['anios'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
		$filas[] = array(
						array('valor'=>'Certificado: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$this->s__datos_conf['certificado'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
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