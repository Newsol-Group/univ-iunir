<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_regularidades_nsg extends ci_rep_regularidades
{
	protected $s__titulo;
	
	protected $s__datos;
	protected $s__datos_filtrados;
	
	protected $s__filtros;

	// Constantes.
	const filtro_vigencia		= 'vigencia';
	const filtro_act			= 'actividades';
	const filtro_origen			= 'origen';
	const filtro_planes			= 'planes';	

	const todas 				= '-- Todas --';
	const todos 				= '-- Todos --';

	const vigentes 				= 'Vigentes';
	const no_vigentes	 		= 'No Vigentes';

	const plan_actual			= 'Plan Actual';
	const plan_anterior			= 'Plan Anterior';
	
	const actividades_ap 		= 'Aprobadas';
	const actividades_sin_ap	= 'Sin Aprobar';
	
	const origen_cursada 		= 'Regularidad';
	const origen_equiv			= 'Equivalencia de Regularidad';
	
	//-----------------------------------------------------------------------------------
	//---- opciones ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	
	function get_opciones_planes()
	{
		return array(array('valor' => apex_ef_no_seteado, 'descr' => self::todos),
				array('valor' => self::plan_actual, 'descr' => self::plan_actual),
				array('valor' => self::plan_anterior, 'descr' => self::plan_anterior)
		            );
	}
	
	//-----------------------------------------------------------------------------------
	//---- comportamiento ajax ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
		
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
			if ($condicion == self::plan_actual && $dato['plan_alumno'] == $dato['codigo_plan'] || $condicion == self::plan_anterior && $dato['plan_alumno'] != $dato['codigo_plan']) {
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
			$xml .= $this->xml_texto('* Actividades aprobadas por Examen Final, Promoci\F3n, Equivalencia, Resoluci\F3n...', array('border'=>'none', 'font-style'=>'italic', 'text-align'=>'left'));
		}
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}
}
?>