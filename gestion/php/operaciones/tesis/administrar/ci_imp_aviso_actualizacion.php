<?php
/**
 * 
 *
 * @package 	Tesis
 * @subpackage 	-
 * 
 */
class ci_imp_aviso_actualizacion extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('4cm');

		// Se modifican las dimensiones de la página y los márgenes
		$this->xml_set_dim_pagina($this->s__ancho . 'mm', $this->s__alto . 'mm');
		$this->xml_set_margenes(array('sup' => '1cm', 'inf' => '1cm', 'izq' => '1cm', 'der' => '1cm'));

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// Tabla para el título y logo
		$this->xml_set_tabla_cols();

		// Datos de la tabla
		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'border' => 'none', 'number-columns-spanned' => 2), array('valor' => '', 'border' => 'none', 'number-columns-spanned' => 2));
		$filas[] = array(array('valor' => $this->xml_texto($this->s__titulo, array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center')), 'border-bottom' => 'solid black', 'text-align' => 'center', 'number-columns-spanned' => 4));

		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();
		$texto_encabezado = "Sr. {$this->s__datos_conf['apellido_y_nombres']}, le informamos que su trámite de solicitud de certificación ha sido actualizado.";
		$filas[] = array(
			array('valor' => $texto_encabezado, 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left')
		);

		// Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();

		$xml .= $this->xml_texto('DETALLES: ');

		$filas = array();
		// Se agregan las filas (varias con column span)
		foreach ($this->s__datos as $key => $dato) {
			if (isset($dato['etiq']) && $dato['valor']) {
				$filas[] = array(array('valor' => $dato['etiq'] . ': ', 'border' => 'none', 'text-align' => 'right', 'font-size' => 11), array('valor' => $dato['valor'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left', 'font-size' => 11));
			}
		}
		$xml .= $this->xml_tabla($filas);

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

}
?>