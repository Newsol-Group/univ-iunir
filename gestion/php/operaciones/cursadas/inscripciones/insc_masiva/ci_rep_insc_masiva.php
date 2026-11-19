<?php
class ci_rep_insc_masiva extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('datos');
		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');

		if ($reporte == pro_inscripcion_cursadas_masiva::reporte_inscriptos) {
			$this->dep('cuadro')->eliminar_columnas(array('error'));
		} else {
			$this->dep('cuadro')->eliminar_columnas(array('nro_transaccion'));
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_titulo($this->s__titulo);
		$cuadro->set_datos($this->s__datos);
	}

	//--------------------------------------------------------------------------
	//---- vista_xml -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = null)
	{
		$this->xml_set_alto_cabecera('4cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		//Seteo los datos de la tabla
		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'border' => 'none', 'number-columns-spanned' => 2), array('valor' => '', 'border' => 'none', 'number-columns-spanned' => 2));
		$filas[] = array(array('valor' => $this->xml_texto('Reporte de ' . $this->s__titulo, array('font-weight' => 'bold', 'font-size' => 12, 'text-align' => 'center')), 'border-bottom' => 'solid black', 'text-align' => 'center', 'number-columns-spanned' => 4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();

		$filas = array();

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

}
?>