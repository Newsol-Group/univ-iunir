<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_ha_invalidada extends ci_impresion 
{
	protected $s__titulo;	
	protected $s__aprobadas;
	protected $s__regularizadas;	
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'HISTORIA ACADÉMICA INVALIDADA';
		$this->s__aprobadas = $this->get_datos_aprobadas();
		$this->s__regularizadas = $this->get_datos_regularizadas();
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_aprobadas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		if (empty($this->s__aprobadas) && empty($this->s__regularizadas)) {
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function conf__cuadro_aprobadas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__aprobadas);		
		if (empty($this->s__aprobadas)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('registros de Historia Académica Invalidada'));
			$cuadro->set_eof_mensaje($msg);	
		}
		
	}
	
	function get_datos_aprobadas()
	{
		$persona = toba::db()->quote( $this->controlador()->controlador()->get_persona_seleccionada());		
		$where = "vw_hist_academica_inv.persona = $persona";		
		$datos = toba::consulta_php('co_alumnos')->get_historia_academica_invalidada($where);
		return rs_ordenar_por_columna($datos, 'propuesta');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_regularizadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_regularizadas(toba_ei_cuadro $cuadro)
	{		
		$cuadro->set_datos($this->s__regularizadas);		
		if (empty($this->s__regularizadas)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('registros de Historia Académica Invalidada'));
			$cuadro->set_eof_mensaje($msg);
		}
		
	}
	
	function get_datos_regularizadas()
	{
		$persona = toba::db()->quote( $this->controlador()->controlador()->get_persona_seleccionada());		
		$where = "vw_regularidades_inv.persona = $persona";		
		$datos = toba::consulta_php('co_alumnos')->get_regularidades_invalidadas($where);
		return rs_ordenar_por_columna($datos, 'propuesta');
	}

	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('5cm');

		$this->xml_set_orientacion('landscape');
		
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
		
		$datos = toba::consulta_php('co_personas')->get_datos_persona($this->controlador()->controlador()->get_persona_seleccionada());
		
		$filas = array();
		$filas[] = array(
						array('valor'=>'APELLIDO Y NOMBRE: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left', 'number-columns-spanned'=>1),
						array('valor'=>$datos['nombre_completo'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left', 'number-columns-spanned'=>3)
						);
		$filas[] = array(						
						array('valor'=>'TIPO Y NRO. DE IDENTIFICACIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left', 'number-columns-spanned'=>1),
						array('valor'=>$datos['documento'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left', 'number-columns-spanned'=>3),
						);

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
				
		if ($this->dep('cuadro_aprobadas')->get_datos()) {
			$xml .= $this->dep('cuadro_aprobadas')->vista_xml();
		}
		
		if ($this->dep('cuadro_regularizadas')->get_datos()) {
			$xml .= $this->dep('cuadro_regularizadas')->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}

?>