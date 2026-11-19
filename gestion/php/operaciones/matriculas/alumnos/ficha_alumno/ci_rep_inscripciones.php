<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_inscripciones extends ci_impresion 
{
	protected $s__titulo;
	
	protected $s__datos;
	protected $s__datos_filtrados;
	
	protected $s__filtros;
	
	//-----------------------------------------------------------------------------------
	//---- constantes -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	const filtro_inscripciones	= 'inscripciones';
	const filtro_vigencia		= 'vigencia';
	
	const todas 				= '-- Todas --';
	
	const vigentes 				= 'Vigentes';
	
	const insc_cursadas 		= 'Cursadas';
	const insc_examenes			= 'Exámenes';
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'INSCRIPCIONES A EXÁMENES Y ACTIVIDADES';
	}
	
	function ini__operacion()
	{
		// Seteo filtro por defecto.
		$this->set_filtro(self::filtro_vigencia, self::vigentes);
	}
	
	function unset_vars()
	{
		unset($this->s__datos);
		unset($this->s__datos_filtrados);
		//unset($this->s__filtros);
	}
	
	//-----------------------------------------------------------------------------------
	//---- opciones ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__opciones(toba_ei_formulario $form)
	{
		if (!empty($this->s__filtros)) {
			$form->set_datos($this->s__filtros);
		}
	}
	
	function get_opciones_inscripciones()
	{
		return array(array('valor' => 'nopar', 'descr' => self::todas),
					 array('valor' => 'C', 'descr' => self::insc_cursadas),
		             array('valor' => 'E', 'descr' => self::insc_examenes)
		            );
	}
	
	function get_opciones_vigencia()
	{
		return array(array('valor' => 'nopar', 'descr' => self::todas),
					 array('valor' => self::vigentes, 'descr' => self::vigentes)
		            );
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (empty($this->s__datos)) {
			$this->s__datos = $this->get_datos();
		}
		if (empty($this->s__datos_filtrados)) {
			$this->s__datos_filtrados = $this->filtrar_datos();
		}
		
		$cuadro->set_datos($this->s__datos_filtrados);
		
		if (empty($this->s__datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones'));
			$cuadro->set_eof_mensaje($msg);	
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		} elseif (empty($this->s__datos_filtrados)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones del Alumno con el filtro especificado'));
			$cuadro->set_eof_mensaje($msg);	
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function get_datos($etiq=null)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$datos = toba::consulta_php('co_alumnos')->get_inscripciones_persona($persona);	
		return $datos;
	}
	
	function hay_datos()
	{
		return guarani_arreglos::count($this->s__datos) > 0;
	}
	
	//-----------------------------------------------------------------------------------
	//---- comportamiento ajax ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ajax__set_opciones($parametros, toba_ajax_respuesta $respuesta)
	{
		$filtro = $parametros['opcion'];
		$valor = $parametros['valor'];
		$this->set_filtro($filtro, $valor);    	
		$this->s__datos_filtrados = $this->filtrar_datos();
		
		$respuesta->set(null);
	}
	
	function set_filtro($filtro, $valor)
	{
		if ($valor == 'nopar') {
			unset($this->s__filtros[$filtro]);
		} else {
			$this->s__filtros[$filtro] = $valor;
		}
	}
	
	function filtrar_datos()
	{
		$datos = $this->s__datos;		
		if (isset($this->s__filtros[self::filtro_vigencia])) {
			$datos = $this->filtrar_vigencia($datos);
		}
		if (isset($this->s__filtros[self::filtro_inscripciones])) {
			$datos = $this->filtrar_inscripciones($datos);
		}
		return $datos;
	}
	
	function filtrar_vigencia($datos)
	{
		$condicion = $this->s__filtros[self::filtro_vigencia];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($condicion == self::vigentes) {
				if ($dato['vigente'] == comunes::si) {
					$resultado[] = $dato;
				}
			}
		}
		return $resultado;
	}
	
	function filtrar_inscripciones($datos)
	{
		$condicion = $this->s__filtros[self::filtro_inscripciones];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($dato['tipo_inscripcion'] == $condicion) {
				$resultado[] = $dato;
			}
		}
		return $resultado;
	}
    
    //-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();
		
		$this->xml_set_orientacion('landscape');

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
		
		if (!empty($this->s__filtros)) {
			$condiciones = array();
			$xml .= $this->xml_texto('FILTROS: ', array('border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'));
			if (!empty($this->s__filtros[self::filtro_inscripciones])) {
				$condiciones[] = array(
									array('valor'=>'Inscripciones: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_inscripciones], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			if (!empty($this->s__filtros[self::filtro_vigencia])) {
				$condiciones[] = array(
									array('valor'=>'Ver: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_vigencia], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			$xml .= $this->xml_tabla($condiciones, false);
		}		
		
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml();
		}
		
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}
?>