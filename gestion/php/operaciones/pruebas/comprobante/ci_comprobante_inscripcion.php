<?php
class ci_comprobante_inscripcion extends guarani_pers_ci
{
	
	
	function evt__cancelar() {
		$this->set_pantalla('seleccion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del cuadro previo a la generación de la salida
	 * El formato de carga es de tipo recordset: array( array('columna' => valor, ...), ...)
	 */
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->get_listado_inscriptos());
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $seleccion Id. de la fila seleccionada
	 */
	function evt__cuadro__seleccion($seleccion)
	{
		$this->_memoria['prop_id'] =$seleccion['propuesta_aspira']; 
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__formulario(toba_ei_formulario $form)
	{
		$datos = $this->cn()->get_inscripto($this->_memoria['prop_id']);
		$form->set_datos($datos[0]);
	}
	
	
	function vista_xml($inicial = false, $xmlns = null) {
		
		$datos = $this->cn()->get_inscripto($this->_memoria['prop_id']);
		$datos = $datos[0];

		//Busco el path del logo.
		$path_logo = guarani::get_path_logo_impresion_chico();
		
		//Quito la cabecera y el pie de página
		$this->xml_set_incluir_cabecera(false);
		$this->xml_set_incluir_pie(false);
		
		//Modifico las dimensiones de la página y los márgenes
		$this->xml_set_dim_pagina('110mm','80mm');
		$this->xml_set_margenes(array('sup'=>'0.3cm', 'inf'=>'0.3cm', 'izq'=>'0.3cm', 'der'=>'0.3cm'));
		
		//Inicio el tag xml del ci
		$xml = $this->xml_get_tag_inicio();
		//Primera tabla (que reemplaza a la cabecera)
		
		//Seteo la primer columna a 30% y la segunda a 70% (se puede usar tanto column-width como width para eso... column-width es como 
		//verdaderamente se llama el atributo en el elemento table-column de xsl-fo)
		$cols[] = array('column-width'=>'30%');
		$cols[] = array('width'=>'70%');
		$this->xml_set_tabla_cols($cols);
		
		//Seteo los datos de la primer tabla.
		//En la primer fila va el logo (con row span) y el nombre de la universidad.
		//En la segunda fila va la facultad.
		$institucion = institucion::get_datos_gestionante();
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'number-rows-spanned'=>2, 'border'=>'none'),array('valor'=>$institucion['nombre'], 'border'=>'none'));
		$filas[] = array(array('valor'=>$datos['responsable_academica'], 'border'=>'none'));
		//Obtengo la primera tabla.
		$xml .= $this->xml_tabla($filas);
		
		//Agrego el texto subrayado 
		$xml .= $this->xml_texto('Inscripción a Propuesta',array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>10, 'margin-bottom'=>'0.5cm', 'text-align'=>'center'));
		
		//Segunda tabla (que contiene los datos)
		
		//reseteo las columnas de tablas y $fila
		$this->xml_set_tabla_cols();
		$filas = array();
		
		//Agrego las filas (varias con column span)
		$filas[] = array(array('valor'=>'Apellido y Nombre: '.$datos['nombre_persona'], 'number-columns-spanned'=>'2','border'=>'none'));
		$filas[] = array(array('valor'=>'Nro Documento: '.$datos['nro_documento'], 'number-columns-spanned'=>'2','border'=>'none'));
		$filas[] = array(array('valor'=>'Propuesta: '.$datos['propuesta'], 'number-columns-spanned'=>'2','border'=>'none'));
		$filas[] = array(array('valor'=>'Plan de Estudios: '.$datos['plan'],'border'=>'none'),array('valor'=>'Modalidad de Cursada: '.$datos['modalidad_cursada'],'border'=>'none', 'text-align'=>'right'));
		$filas[] = array(array('valor'=>'Ubicación: '.$datos['ubicacion'],'border'=>'none'),array('valor'=>'Estado Insc.: Pendiente','border'=>'none', 'text-align'=>'right'));
		$filas[] = array(array('valor'=>'Tipo de Ingreso: '.$datos['ingreso'], 'number-columns-spanned'=>'2','border'=>'none'));
		$filas[] = array(array('valor'=>'Convenio: '.$datos['convenio'], 'number-columns-spanned'=>'2','border-bottom'=>'thin solid black'));
		$filas[] = array(array('valor'=>'Fecha Inscripción: '.$datos['fecha_inscripcion'],'border'=>'none'),array('valor'=>'Nro Registro: '.$datos['nro_transaccion'],'border'=>'none', 'text-align'=>'right'));
		//Obtengo la segunda tabla
		$xml .= $this->xml_tabla($filas);
		
		//Cierro el tag xml del ci
		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}

?>