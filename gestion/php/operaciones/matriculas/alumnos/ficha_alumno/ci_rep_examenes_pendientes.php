<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_examenes_pendientes extends ci_impresion 
{
	protected $s__titulo;	
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'EXÁMENES PENDIENTES';
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos();
		$cuadro->set_datos($datos);
		
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('exámenes pendientes'));
			$cuadro->set_eof_mensaje($msg);	
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function get_datos($etiq=null)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		// Regularidades de la persona.
		// Se filtra por vw_regularides que es mas optimo que por sga_alumnos.
		$filtro = "vw_regularidades.persona = $persona";
		
		// Cursada APROBADA.
		$filtro .= " AND vw_regularidades.resultado = '".escalas_notas::resultado_aprobado."'";
		
		// Regularidad VIGENTE.
		$filtro .= " AND vw_regularidades.es_vigente = 1";
		
		// Actividad NO APROBADA por examen final, promocion, equivalencia total, resolución.
		$filtro .= " AND vw_regularidades.elemento NOT IN (SELECT vw_hist_academica_basica.elemento
															 FROM vw_hist_academica_basica
															WHERE vw_hist_academica_basica.persona = $persona 
															  AND vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
														 )";
		$datos = toba::consulta_php('co_alumnos')->get_regularidades($filtro);
		return $datos;
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
				
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}

?>