<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_req_ingreso extends ci_impresion
{
	protected $s__titulo;

	protected $s__requisitos_presentados;
	protected $s__requisitos_adeudados_exceptuados;
	protected $s__requisitos_tipo_proceso;

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_propuestas');
		$this->s__titulo = strtoupper($etiqueta->get_enf('plural'));
		$this->get_datos_requisitos();
	}

	function unset_vars()
	{
		unset($this->s__requisitos_presentados);
		unset($this->s__requisitos_adeudados_exceptuados);
		unset($this->s__requisitos_tipo_proceso);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_presentados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_presentados(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__requisitos_presentados);
		if (empty($this->s__requisitos_presentados)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_propuestas');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural') . ' documentales presentados o vencidos'));
			$cuadro->set_eof_mensaje($msg);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_pend_excep ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_pend_excep(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__requisitos_adeudados_exceptuados);
		if (empty($this->s__requisitos_adeudados_exceptuados)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_propuestas');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural' . ' documentales adeudados o exceptuados')));
			$cuadro->set_eof_mensaje($msg);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_procesos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_procesos(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__requisitos_tipo_proceso);
		if (empty($this->s__requisitos_tipo_proceso)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_propuestas');
			$msg = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg);
		}
	}

	function get_datos_requisitos()
	{
		$persona_seleccionada = $this->controlador()->controlador()->get_persona_seleccionada();
		if (!isset($this->s__requisitos_presentados)) {
			$datos = toba::consulta_php('co_requisitos_ingreso')->get_requisitos_presentados_y_vencidos($persona_seleccionada);
			$this->s__requisitos_presentados = rs_convertir_asociativo_matriz($datos, array('requisito'));
		}
		if (!isset($this->s__requisitos_adeudados_exceptuados)) {
			$datos = toba::consulta_php('co_requisitos_ingreso')->get_requisitos_ingreso_documentales_adeudados_o_exceptuados($persona_seleccionada);
			$this->s__requisitos_adeudados_exceptuados = $this->filtrar_cumplimiento_subordinados($datos);
		}
		if (!isset($this->s__requisitos_tipo_proceso)) {
			$this->s__requisitos_tipo_proceso = toba::consulta_php('co_requisitos_ingreso')->get_datos_requisitos_tipo_proceso($persona_seleccionada);
		}
	}

	/**
	 * No se deben listar los requisitos subordinados que no estén presentados y 
	 * cuyo requisito principal esté cumplido.
	 * 
	 * @param array $datos
	 * @return array
	 */
	protected function filtrar_cumplimiento_subordinados($datos)
	{
		$req_exceptuados = array(); 
		foreach ($datos as $dato) {
			if ($dato['exceptuado_valor'] == comunes::si) {
				$req_exceptuados[] = $dato;
			}
		}
		$salida = array();
		foreach ($datos as $dato) {
			if (!empty($dato['requisito_subordinado_de'])) {
				// Es subordinado de otro requisito, se verifica si su padre está presentado.
				$datos_presentacion_padre = $this->get_datos_presentacion_padre($dato['requisito_subordinado_de']);
				if (empty($datos_presentacion_padre)) {
					// El padre no está presentado, se verifica si está exceptuado en la misma propuesta.
					$datos_excepcion_padre = $this->get_datos_excepcion_padre_misma_propuesta($dato['requisito_subordinado_de'], $dato['propuesta'], $req_exceptuados);
					if ($datos_excepcion_padre['exceptuado_valor'] == comunes::no) {
						// El padre no está exceptuado, se muestra como pendiente.
						$salida[] = $dato;
					} else {
						// El padre está exceptuado, se muestra como exceptuado también.
						$dato['exceptuado'] = comunes::si_desc;
						$dato['fecha_excepcion'] = $datos_excepcion_padre['fecha_excepcion'];
						$dato['motivo_excepcion_nombre'] = "Subordinado del requisito {$datos_excepcion_padre['requisito_nombre']} (exceptuado).";
						$salida[] = $dato;
					}
				} elseif (!isset($this->s__requisitos_presentados[$dato['requisito']])) { // Puede aparecer más de una vez si la persona es alumna de más de una propuesta.
					$dato['estado_descr'] = "Subordinado del requisito {$datos_presentacion_padre['requisito_nombre']} (cumplido).";
					$dato['fecha_presentacion'] = $datos_presentacion_padre['fecha_presentacion'];
					$dato['fecha_vencimiento'] = $datos_presentacion_padre['fecha_vencimiento'];
					$this->s__requisitos_presentados[$dato['requisito']] = $dato;
				}
			} else {
				// No es subordinado.
				$salida[] = $dato;
			}
		}
		return $salida;
	}

	protected function get_datos_presentacion_padre($requisito)
	{
		foreach ($this->s__requisitos_presentados as $presentado) {
			if ($presentado['requisito'] == $requisito) {
				return $presentado;
			}
		}
		$datos_requisito = current(toba::consulta_php('co_requisitos')->get_datos_requisito($requisito));
		if (!empty($datos_requisito['requisito_subordinado_de'])) {
			return $this->get_datos_presentacion_padre($datos_requisito['requisito_subordinado_de']);
		} else {
			return null;
		}
	}

	protected function get_datos_excepcion_padre_misma_propuesta($requisito, $propuesta, $exceptuados)
	{
		// Busca si el requisito que esta subordinado de otro requisito que esta exceptuado en la misma propuesta
		foreach ($exceptuados as $excep) {
			if ($excep['requisito'] == $requisito && $excep['propuesta'] == $propuesta) {
				return $excep;
			}
		}
		$datos_requisito = current(toba::consulta_php('co_requisitos')->get_datos_requisito($requisito));
		if (!empty($datos_requisito['requisito_subordinado_de'])) {
			return $this->get_datos_excepcion_padre_misma_propuesta($datos_requisito['requisito_subordinado_de'], $propuesta, $exceptuados);
		} else {
			return array('exceptuado_valor' => comunes::no);
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

		//Seteo los datos de la tabla
		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'border' => 'none', 'number-columns-spanned' => 2), array('valor' => '', 'border' => 'none', 'number-columns-spanned' => 2));
		$filas[] = array(array('valor' => $this->xml_texto($this->s__titulo, array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center')), 'border-bottom' => 'solid black', 'text-align' => 'center', 'number-columns-spanned' => 4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$datos = toba::consulta_php('co_personas')->get_datos_persona($this->controlador()->controlador()->get_persona_seleccionada());

		$filas = array();
		$filas[] = array(
			array('valor' => 'APELLIDO Y NOMBRE: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left', 'number-columns-spanned' => 1),
			array('valor' => $datos['nombre_completo'], 'border' => 'none', 'font-weight' => 'normal', 'text-align' => 'left', 'number-columns-spanned' => 3)
		);
		$filas[] = array(
			array('valor' => 'TIPO Y NRO. DE IDENTIFICACIÓN: ', 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left', 'number-columns-spanned' => 1),
			array('valor' => $datos['documento'], 'border' => 'none', 'font-weight' => 'normal', 'text-align' => 'left', 'number-columns-spanned' => 3),
		);

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();

		$atts_titulos = array(
			'font-weight' => 'bold',
			'font-size' => 14,
			'margin-bottom' => '0.5cm',
			'margin-top' => '0.5cm',
			'text-align' => 'left'
		);

		if ($this->dep('cuadro_presentados')->get_datos()) {
			$xml .= $this->xml_texto("Requisitos de ingreso DOCUMENTALES presentados y vencidos", $atts_titulos);
			$xml .= $this->dep('cuadro_presentados')->vista_xml();
		}

		if ($this->dep('cuadro_pend_excep')->get_datos()) {
			$xml .= $this->xml_texto("Requisitos de ingreso DOCUMENTALES adeudados y exceptuados (por Propuesta)", $atts_titulos);
			$xml .= $this->dep('cuadro_pend_excep')->vista_xml();
		}

		if ($this->dep('cuadro_procesos')->get_datos()) {
			$xml .= $this->xml_texto("Otros requisitos de ingreso (por Propuesta)", $atts_titulos);
			$xml .= $this->dep('cuadro_procesos')->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();

		return $xml;
	}
}
?>