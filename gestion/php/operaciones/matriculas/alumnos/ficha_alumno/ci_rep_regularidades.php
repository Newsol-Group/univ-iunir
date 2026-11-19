<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_regularidades extends ci_impresion 
{
	protected $s__titulo;
	
	protected $s__datos;
	protected $s__datos_filtrados;
	
	protected $s__filtros;

	// Constantes.
	const filtro_vigencia		= 'vigencia';
	const filtro_act			= 'actividades';
	const filtro_origen			= 'origen';
	
	const todas 				= '-- Todas --';
	
	const vigentes 				= 'Vigentes';
	const no_vigentes	 		= 'No Vigentes';
	
	const actividades_ap 		= 'Aprobadas';
	const actividades_sin_ap	= 'Sin Aprobar';
	
	const origen_cursada 		= 'Regularidad';
	const origen_equiv			= 'Equivalencia de Regularidad';
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'REGULARIDADES';
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
	
	function get_opciones_vigencia()
	{
		return array(array('valor' => apex_ef_no_seteado, 'descr' => self::todas),
					 array('valor' => self::vigentes, 'descr' => self::vigentes),
		             array('valor' => self::no_vigentes, 'descr' => self::no_vigentes)
		            );
	}
	
	function get_opciones_actividades()
	{
		return array(array('valor' => apex_ef_no_seteado, 'descr' => self::todas),
					 array('valor' => self::actividades_ap, 'descr' => self::actividades_ap),
		             array('valor' => self::actividades_sin_ap, 'descr' => self::actividades_sin_ap)
		            );
	}
	
	function get_opciones_origen()
	{
		return array(array('valor' => apex_ef_no_seteado, 'descr' => self::todas),
					 array('valor' => self::origen_cursada, 'descr' => self::origen_cursada),
		             array('valor' => self::origen_equiv, 'descr' => self::origen_equiv)
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
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Regularidades'));
			$cuadro->set_eof_mensaje($msg);
		} elseif (empty($this->s__datos_filtrados)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Regularidades con el filtro especificado'));
			$cuadro->set_eof_mensaje($msg);	
		} elseif (! $this->pantalla('pantalla_inicial')->existe_evento('pdf')) {
			$this->pantalla('pantalla_inicial')->agregar_evento('pdf');
		}
	}
	
	function get_datos($etiq=null)
	{
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$filtro = 'vw_regularidades.persona = ' . $persona;
		
        //Extraigo los alumnos que es la persona.
        $alumno = array_column(toba::consulta_php('co_alumnos')->get_listado(' vw_personas.persona = '. $persona), 'alumno');
        $aprobadas = array_column(toba::consulta_php('co_alumnos')->get_actividades_aprobadas_regularizadas($alumno, 'A'), 'elemento');
        
		// Recupero las actividades regularizadas por el alumno.
		$datos = toba::consulta_php('co_alumnos')->get_regularidades($filtro, '', true);
        foreach ($datos as $clave => $valor) {
            //Si la regular esta dentro de las aprobadas la marco con un asterisco
            if (in_array($valor['elemento'], $aprobadas)) {
                $datos[$clave]['marca_aprobada'] = '*';                
            }
		}
        return $datos;
	}

	function hay_datos()
	{
		return guarani_arreglos::count($this->s__datos_filtrados) > 0;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_provisorias -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_provisorias(toba_ei_cuadro $cuadro)
	{
		$filtro = 'sga_alumnos.persona = ' . $this->controlador()->controlador()->get_persona_seleccionada();
		$datos = toba::consulta_php('co_alumnos')->get_regularidades_provisorias($filtro);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800CUR_eof_act_provisoria');
			$cuadro->set_eof_mensaje($msg);
		} else {
			$cuadro->set_datos($datos);
			if (! $this->pantalla('pantalla_inicial')->existe_evento('pdf')) {
				$this->pantalla('pantalla_inicial')->agregar_evento('pdf');
			}
		}
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
		if (isset($this->s__filtros[self::filtro_vigencia])) {
			$datos = $this->filtrar_vigencia($datos);
		}
		if (isset($this->s__filtros[self::filtro_act])) {
			$datos = $this->filtrar_actividades($datos);
		}
		if (isset($this->s__filtros[self::filtro_origen])) {
			$datos = $this->filtrar_origen($datos);
		}	
		return $datos;
	}
	
	function filtrar_vigencia($datos)
	{
		$condicion = $this->s__filtros[self::filtro_vigencia];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($condicion == self::vigentes && $dato['es_vigente'] || $condicion == self::no_vigentes && !$dato['es_vigente']) {
				$resultado[] = $dato;
			}
		}
		return $resultado;
	}
	
	function filtrar_actividades($datos)
	{
		$condicion = $this->s__filtros[self::filtro_act];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($condicion == self::actividades_ap) {
				if (isset($dato['marca_aprobada'])) {
					$resultado[] = $dato;
				}
			} elseif ($condicion == self::actividades_sin_ap) {
				if (! isset($dato['marca_aprobada'])) {
					$resultado[] = $dato;
				}
			}
		}
		return $resultado;
	}
	
	function filtrar_origen($datos)
	{
		$condicion = $this->s__filtros[self::filtro_origen];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($dato['tipo'] == $condicion) {
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
			if (!empty($this->s__filtros[self::filtro_vigencia])) {
				$condiciones[] = array(
									array('valor'=>'Ver: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_vigencia], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			if (!empty($this->s__filtros[self::filtro_act])) {
				$condiciones[] = array(
									array('valor'=>'Actividades: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_act], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			if (!empty($this->s__filtros[self::filtro_origen])) {
				$condiciones[] = array(
									array('valor'=>'Origen: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_origen], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
								);
			}
			$xml .= $this->xml_tabla($condiciones, false);
		}		
		
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml();
			$xml .= $this->xml_texto('* Actividades aprobadas por Examen Final, Promoci\F3n, Equivalencia, Resoluci\F3n...', array('border'=>'none', 'font-style'=>'italic', 'text-align'=>'left'));
		}
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}
}