<?php
/**
 * 
 * @package 	Matriculas
 * @subpackage 	Reportes
 */
class ci_rep_creditos_otorgados extends ci_impresion 
{
	protected $s__titulo;	

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'CRÉDITOS OTORGADOS';
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('pdf');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_globales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_globales(toba_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos_globales();
		$cuadro->set_datos($datos);

		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_reconocimiento');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg);	
		} elseif (! $this->pantalla('pantalla_inicial')->existe_evento('pdf')) {
			$this->pantalla('pantalla_inicial')->agregar_evento('pdf');
		}
	}

	function conf_evt__cuadro_globales__ver_tramite(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_globales')->get_datos();
		$datos_fila = $datos[$fila];
		$evento->vinculo()->agregar_parametro('nro_tramite', utf8_encode($datos_fila['nro_tramite']));
	}

	function get_datos_globales()
	{
		$persona = toba::db()->quote($this->controlador()->controlador()->get_persona_seleccionada());
		$where = "	sga_alumnos.persona = $persona AND
				sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::cred_globales."' AND
				sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."'";
		$datos = toba::consulta_php('co_aprobacion_por_resolucion')->get_listado($where);
		return rs_ordenar_por_columna($datos, 'fecha');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades_modulos ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades_modulos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos_actividades_modulos();
		$cuadro->set_datos($datos);

		if (empty($datos)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_reconocimiento');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg);	
		} elseif (! $this->pantalla('pantalla_inicial')->existe_evento('pdf')) {
			$this->pantalla('pantalla_inicial')->agregar_evento('pdf');
		}
	}

	function conf_evt__cuadro_actividades_modulos__ver_tramite(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_actividades_modulos')->get_datos();
		$datos_fila = $datos[$fila];
		$evento->vinculo()->agregar_parametro('nro_tramite', utf8_encode($datos_fila['nro_tramite']));
	}

	function get_datos_actividades_modulos()
	{
		$persona = toba::db()->quote($this->controlador()->controlador()->get_persona_seleccionada());
		$where = "	sga_alumnos.persona = $persona AND
					sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::activ_modulos."' AND
					sga_reconocimiento_act.estado = '".aprobacion_por_resolucion::estado_actividad_activo."' AND
					sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."'";
		$datos = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_reconocimiento_tramite_aprobacion(null, $where, true);
		return rs_ordenar_por_columna($datos, 'fecha');
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

		if ($this->dep('cuadro_globales')->get_datos()) {
			$xml .= $this->dep('cuadro_globales')->vista_xml();
		}

		if ($this->dep('cuadro_actividades_modulos')->get_datos()) {
			$xml .= $this->dep('cuadro_actividades_modulos')->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();

		return $xml;
	}
}
?>
