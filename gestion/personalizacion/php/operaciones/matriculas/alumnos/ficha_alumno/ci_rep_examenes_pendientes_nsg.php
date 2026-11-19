<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_examenes_pendientes_nsg extends ci_rep_examenes_pendientes
{
	protected $s__titulo;	
	
	protected $s__datos;
	protected $s__datos_filtrados;
	
	protected $s__filtros;

	// Constantes.
	const filtro_planes			= 'planes';	
	const filtro_act			= 'actividades';

	const todos 				= '-- Todos --';
	const todas 				= '-- Todas --';

	const plan_actual			= 'Plan Actual';
	const plan_anterior			= 'Plan Anterior';

	const actividades_ap 		= 'Aprobadas';
	const actividades_sin_ap	= 'Sin Aprobar';


	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'EXÁMENES PENDIENTES';
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
	
	function get_opciones_planes()
	{
		return array(array('valor' => apex_ef_no_seteado, 'descr' => self::todos),
				array('valor' => self::plan_actual, 'descr' => self::plan_actual),
				array('valor' => self::plan_anterior, 'descr' => self::plan_anterior)
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
			$msg = guarani_mensajes::get('800_eof_cuadro', array('exámenes pendientes'));
			$cuadro->set_eof_mensaje($msg);	
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}


	function get_datos($etiq=null)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$filtro = "sga_alumnos.persona = $persona";
		// Cursada APROBADA.
		$filtro .= " AND vw_regularidades.resultado = '".escalas_notas::resultado_aprobado."'";
		// Regularidad VIGENTE.
		$filtro .= " AND (vw_regularidades.fecha_vigencia IS NULL OR vw_regularidades.fecha_vigencia >= CURRENT_DATE)";
		// Actividad NO APROBADA.
		$filtro .= " AND vw_regularidades.elemento NOT IN (	SELECT	vw_hist_academica_basica.elemento
															FROM	vw_hist_academica_basica
															WHERE	vw_hist_academica_basica.alumno = sga_alumnos.alumno AND
																	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."')";
		$datos = toba::consulta_php('co_alumnos')->get_regularidades($filtro);
		return $datos;
	}

	function hay_datos()
	{
		return count($this->s__datos_filtrados) > 0;
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
		if (isset($this->s__filtros[self::filtro_planes])) {
			$datos = $this->filtrar_planes($datos);
		}
		return $datos;
	}
	
	function filtrar_planes($datos)
	{
		$condicion = $this->s__filtros[self::filtro_planes];
		$resultado = array();
		foreach ($datos as $key => $dato) {
			if ($condicion == self::plan_actual && $dato['plan_alumno'] == $dato['plan_codigo'] || $condicion == self::plan_anterior && $dato['plan_alumno'] != $dato['plan_codigo']) {
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
		
		if (!empty($this->s__filtros)) {
			$condiciones = array();
			$xml .= $this->xml_texto('FILTROS: ', array('border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'));
	
			if (!empty($this->s__filtros[self::filtro_planes])) {
				$condiciones[] = array(
									array('valor'=>'Planes: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
									array('valor'=>$this->s__filtros[self::filtro_planes], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left'),
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