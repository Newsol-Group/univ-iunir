<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_equiv_parciales extends ci_impresion 
{
	protected $s__titulo;
	
	protected $s__datos;
	protected $s__datos_filtrados;
	
	protected $s__filtros;

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'EQUIVALENCIAS PARCIALES';
	}
	
	function unset_vars()
	{
		unset($this->s__datos);
		unset($this->s__datos_filtrados);
		unset($this->s__filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('pdf');
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
	
	function get_opciones_propuesta()
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_alumno($this->controlador()->controlador()->get_persona_seleccionada());
	}

	function get_opciones_resultado()
	{
		return toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_sin_ausente();
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
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Equivalencias Parciales'));
			$cuadro->set_eof_mensaje($msg);
		} elseif (empty($this->s__datos_filtrados)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Equivalencias Parciales con el filtro especificado'));
			$cuadro->set_eof_mensaje($msg);	
		} elseif (! $this->pantalla('pantalla_inicial')->existe_evento('pdf')) {
			$this->pantalla('pantalla_inicial')->agregar_evento('pdf');
		}
	}
	
	function get_datos($etiq=null)
	{
		$filtro = 'vw_equiv_parciales.persona = ' . $this->controlador()->controlador()->get_persona_seleccionada();
		return toba::consulta_php('co_alumnos')->get_equivalencias_parciales($filtro);
	}

	function hay_datos()
	{
		return guarani_arreglos::count($this->s__datos_filtrados) > 0;
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
		if ($valor == apex_ef_no_seteado) {
			unset($this->s__filtros[$filtro]);
		} else {
			$this->s__filtros[$filtro] = $valor;
		}
	}

	function filtrar_datos()
	{
		$datos = $this->s__datos;
		if (isset($this->s__filtros['propuesta'])) {
			$datos = $this->filtrar_propuesta($datos);
		}
		if (isset($this->s__filtros['resultado'])) {
			$datos = $this->filtrar_resultado($datos);
		}
		return $datos;
	}

	function filtrar_propuesta($datos)
	{
		$condicion = $this->s__filtros['propuesta'];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($dato['propuesta'] == $condicion) {
				$resultado[] = $dato;
			}
		}
		return $resultado;
	}

	function filtrar_resultado($datos)
	{
		$condicion = $this->s__filtros['resultado'];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($dato['resultado'] == $condicion) {
				$resultado[] = $dato;
			}
		}
		return $resultado;
	}

    //-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el t\EDtulo y logo
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
						array('valor'=>'TIPO Y NRO. DE IDENTIFICACI\D3N: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left', 'number-columns-spanned'=>1),
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
			if (!empty($this->s__filtros['propuesta'])) {
				$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($this->s__filtros['propuesta']);
				$condiciones[] = array(
									array('valor'=>'Propuesta: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$datos_propuesta['nombre'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			if (!empty($this->s__filtros['resultado'])) {
				$datos_resultado = current(toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_nombre($this->s__filtros['resultado']));
				$condiciones[] = array(
									array('valor'=>'Resultado: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$datos_resultado['resultado_nombre'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
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
