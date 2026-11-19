<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class ci_rep_comparacion_planificado_dictado extends ci_impresion 
{
	protected $datos_clase;
	protected $ev_info_clase;
	
	function ini()
	{
		toba::memoria()->desactivar_reciclado();
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info_clase ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_clase()
	{
		if (!isset($this->ev_info_clase)) {
			$this->ev_info_clase = guarani::ev_info_datos();
			$this->ev_info_clase->set_columnas_a_mostrar(array('fecha', 'hora_inicio', 'hora_finalizacion'));
			
			$this->ev_info_clase->set_columnas_fila(array('fecha', 'hora_inicio', 'hora_finalizacion'), 1);
			
			$this->ev_info_clase->set_max_columnas_por_fila(3);
			
			$this->ev_info_clase->set_etiqueta('fecha', 'Fecha');
			$this->ev_info_clase->set_etiqueta('hora_inicio', 'Inicio');
			$this->ev_info_clase->set_etiqueta('hora_finalizacion', 'Fin');
			
			$this->ev_info_clase->set_td_etiqueta_width('30px');
			$this->ev_info_clase->set_td_valores_width('200px');
		}
		return $this->ev_info_clase;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		$this->controlador()->ev_info()->set_datos($this->cn()->get_ev_info_datos());
		$this->ev_info_clase()->set_datos($this->cn()->get_ev_info_datos_clase());
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_temas_planificados ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_temas_planificados(toba_ei_formulario $form)
	{
		if (!isset($this->datos_clase)) {
			$this->datos_clase = $this->cn()->get_datos_clase();
		}
		$form->set_datos($this->datos_clase);
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_temas_dictados ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_temas_dictados(toba_ei_formulario $form)
	{
		if (!isset($this->datos_clase)) {
			$this->datos_clase = $this->cn()->get_datos_clase();
		}
		$form->set_datos($this->datos_clase);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_docentes_clase();
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_clases_docentes');
			$msg_eof = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	
	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('7cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto('Reporte: Comparación entre Temas Planificados y Dictados', array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$ev_info_datos = $this->cn()->get_ev_info_datos();
		$ev_info_datos_clase = $this->cn()->get_ev_info_datos_clase();
		$filas = array();
		$filas[] = array(
						array('valor'=>'AÑO ACADÉMICO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos['anio_academico_periodo'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'PERÍODO LECTIVO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos['nombre_periodo'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);
						
		$filas[] = array(
						array('valor'=>'COMISIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos['nombre'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'TURNO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos['nombre_turno'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'MODALIDAD: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos['nombre_modalidad'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);

		if (isset($ev_info_datos['subcomision_nombre'])) {
			$filas[] = array(
							array('valor'=>'SUBCOMISIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
							array('valor'=>$ev_info_datos['subcomision_nombre'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
							array('valor'=>'TIPO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
							array('valor'=>$ev_info_datos['tipo_clase_nombre'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
							);
		}
		
		$filas[] = array(
						array('valor'=>'FECHA: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos_clase['fecha'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'INICIO: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos_clase['hora_inicio'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>'FIN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left'),
						array('valor'=>$ev_info_datos_clase['hora_finalizacion'], 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left')
						);				

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		
		if ($this->dep('form_temas_planificados')->get_datos()) {
			$xml .= $this->dep('form_temas_planificados')->vista_xml(false, $xmlns);
		}
		
		if ($this->dep('form_temas_dictados')->get_datos()) {
			$xml .= $this->dep('form_temas_dictados')->vista_xml(false, $xmlns);
		}
		
		if ($this->dep('cuadro_docentes')->get_datos()) {
			$xml .= $this->dep('cuadro_docentes')->vista_xml(false, $xmlns);
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

}
?>