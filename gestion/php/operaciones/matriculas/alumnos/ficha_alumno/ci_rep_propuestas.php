<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_propuestas extends ci_impresion 
{
	protected $s__titulo;	
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'PROPUESTAS DEL ALUMNO';
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$persona = toba::db()->quote( $this->controlador()->controlador()->get_persona_seleccionada());		
		$where = "sga_propuestas_aspira.persona = $persona";		
		$this->s__datos = toba::consulta_php('co_alumnos')->get_propuestas_aspira($where);

		// Se muestra el campo de observaciones u observaciones_rechazo, dependiente del estado de la inscripción.
		$eliminar_columna_moroso = true;
		foreach ($this->s__datos as $clave => $valor) {
			if ($valor['resultado_asp'] == propuesta::inscripcion_rechazada) {
				$this->s__datos[$clave]['observaciones']  = "{$valor['fecha_rechazo_inscripcion']}: {$valor['observaciones_rechazo']}";
			}
			$ras = guarani::co('co_responsables_academicas')->get_propuestas_ras($valor['propuesta']);
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras, false);
			if ($usa_sq == comunes::si) {
				$eliminar_columna_moroso = false;
				$this->s__datos[$clave]['moroso_desde'] = toba::consulta_php('co_cobros')->es_alumno_moroso($valor['alumno']);
			} else {
				$this->s__datos[$clave]['moroso_desde'] = '---';
			}
		}
		if ($eliminar_columna_moroso) {
			$cuadro->eliminar_columnas(array('moroso_desde'));
		}
		$cuadro->set_datos($this->s__datos);
		
		if (empty($this->s__datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Propuestas'));
			$cuadro->set_eof_mensaje($msg);	
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}		
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		if ($this->s__datos[$fila]['resultado_asp'] != propuesta::inscripcion_rechazada) 
		{
			$evento->anular();
		} else {
			$evento->vinculo()->set_parametros(array('propuesta_aspira' => $this->s__datos[$fila]['propuesta_aspira']));
		}
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
				
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}