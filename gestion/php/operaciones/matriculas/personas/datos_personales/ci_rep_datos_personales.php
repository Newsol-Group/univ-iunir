<?php
/**
 *
 *
 * @package Matriculas
 * @subpackage Personas
 */
class ci_rep_datos_personales extends ci_rep_sin_listado_inicial
{
	protected $s__persona;
	
	function get_tabla_principal()
	{
		return 'mdp_personas';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_personas')->get_listado($filtro);
	}
	
	function set_persona_seleccionada($persona)
	{
		$this->s__persona = $persona;
	}
	
	function get_persona_seleccionada()
	{
		return $this->s__persona;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_persona_seleccionada(current($seleccion));
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- SALIDA XML -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// Se recuperan los datos para el título y encabezado
		$persona = $this->get_persona_seleccionada();
		$persona_datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
		$titulo = 'DATOS PERSONALES';

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto($titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();
		
		$filas = array();
		$filas[] = array(
						array('valor'=>'APELLIDO Y NOMBRE: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$persona_datos['nombre_completo'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left')
						);
		$filas[] = array(						
						array('valor'=>'TIPO Y NRO. DE IDENTIFICACIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$persona_datos['documento'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
						);

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
				
		if ($this->dep('ci_datos_per_reporte')->dep('cuadro_documentos')->get_datos()) {
			$xml .= $this->dep('ci_datos_per_reporte')->dep('cuadro_documentos')->vista_xml();
		}
		
		if ($this->dep('ci_datos_per_reporte')->dep('cuadro_contactos')->get_datos()) {
			$xml .= $this->dep('ci_datos_per_reporte')->dep('cuadro_contactos')->vista_xml();
		}

		if ($this->dep('ci_datos_per_reporte')->dep('cuadro_allegados')->get_datos()) {
			$xml .= $this->dep('ci_datos_per_reporte')->dep('cuadro_allegados')->vista_xml();
		}
		
		if ($this->dep('ci_datos_per_reporte')->dep('cuadro_estudios')->get_datos()) {
			$xml .= $this->dep('ci_datos_per_reporte')->dep('cuadro_estudios')->vista_xml();
		}
		
		if ($this->dep('ci_datos_per_reporte')->dep('cuadro_antecedentes')->get_datos()) {
			$xml .= $this->dep('ci_datos_per_reporte')->dep('cuadro_antecedentes')->vista_xml();
		}
		
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js() 
	{
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null, null, 'vista_xslfo', null, [{$this->objeto_js}._id]));
			return false;
		}
		";
	}
	
}
?>