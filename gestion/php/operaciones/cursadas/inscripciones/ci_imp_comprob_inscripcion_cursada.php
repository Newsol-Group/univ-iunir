<?php
class ci_imp_comprob_inscripcion_cursada extends ci_impresion_popup
{
	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$reporte_con_tabla = false;

		// Se procesan los datos, generando el xml de la misma manera que Toba exporta un cuadro
		$xml = '<tabla><datos>';
		$xml_fila = '';
		$xml_tabla = '';
		foreach ($this->s__datos as $key => $dato) {
			if ($key === 'tabla') {
				// Se preparan los datos de la tabla
				$reporte_con_tabla = true;
				foreach ($dato as $orden => $fila) {
					if ($orden > 0) {
						$xml_tabla .= '<fila>';
						foreach ($dato[0] as $columna) {
							$valor = $fila[$columna['clave']];
							if (is_array($valor)) {
								$valor = implode('/', aplanar_matriz($valor, 'nombre'));
							}
							if (empty($valor)) {
								$valor = '';
							}
							$xml_tabla .= '<dato clave="' . $columna['clave'] . '" valor="' . $valor . '"/>';
						}
						$xml_tabla .= '</fila>';
					}
				}
			} else {
				
				$valor_xml = guarani_texto::reemplazar_caracteres_especiales_xml($dato['valor']);
				
				$xml_fila .= '<fila>
							<dato clave="etiq" valor="' . $dato['etiq'] . '"/>
							<dato clave="valor" valor="' . $valor_xml . '"/>
						</fila>';
			}
		}

		// Se establece la plantilla a usar
		if ($reporte_con_tabla) {
			$path = 'ComprobanteInscripcionActividad.jasper';
			$report->set_parametro('alumno', 'S', $this->s__datos_conf['alumno']);
			$report->set_parametro('identificacion', 'S', $this->s__datos_conf['identificacion']);
			$report->set_parametro('legajo', 'S', $this->s__datos_conf['legajo']);
			$report->set_parametro('propuesta', 'S', $this->s__datos_conf['propuesta_desc']);
			$report->set_parametro('plan', 'S', $this->s__datos_conf['plan_desc']);			
			$report->set_parametro('actividad', 'S', $this->s__datos_conf['actividad']);
			$report->set_parametro('fecha', 'S', $this->s__datos_conf['fecha']);
			$xml .= $xml_tabla;
		} else {
			$path = 'ComprobanteInscripcion.jasper';
			$report->set_parametro('titulo', 'S', $this->s__titulo);
			$xml .= $xml_fila;
		}
		$xml .= '</datos></tabla>';

		$path = guarani::get_path_reporte_jasper($path);
		$report->set_path_reporte($path);

		$institucion = institucion::get_datos_gestionante();
		$ra = guarani::co('co_propuestas')->get_listado_prop_ras_string("sga_propuestas.propuesta = {$this->s__datos_conf['propuesta']}");

		// Parámetros 
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('ra', 'S', $ra);
		$report->set_parametro('institucion', 'S', $institucion['nombre']);
		$report->set_parametro('nro_transaccion', 'S', $this->s__datos_conf['nro_transaccion']);

		//Se agregan los datos (fabrico el xml a mano de la misma manera que Toba exporta un cuadro)
		$report->set_xml(utf8_e_seguro($xml));
	}

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		//----------------------------------------------------------------------
		//---- Configuraciones Iniciales
		//----------------------------------------------------------------------
		
		// Se quita la cabecera y el pie de página
		$this->xml_set_incluir_cabecera(false);
		$this->xml_set_incluir_pie(false);

		// Se modifican las dimensiones de la página y los márgenes
		$this->xml_set_dim_pagina($this->s__ancho . 'mm', $this->s__alto . 'mm');
		$this->xml_set_margenes(array('sup' => '0.3cm', 'inf' => '0.3cm', 'izq' => '0.3cm', 'der' => '0.3cm'));

		// Se hace un recuadro para el comprobante
		$this->xml_set_atts_ei(array('border' => 'medium solid black', 'padding' => '0.2cm'));

		// Se inicia el tag xml del ci
		$xml = $this->xml_get_tag_inicio();

		//----------------------------------------------------------------------
		//---- ENCABEZADO
		//----------------------------------------------------------------------
		
		// Se busca el path del logo.
		$path_logo = guarani::get_path_logo_impresion_chico();

		// Primera tabla (que reemplaza a la cabecera)
		$cols[] = array('column-width' => '45%');
		$cols[] = array('width' => '55%');
		$this->xml_set_tabla_cols($cols);

		// Se setean los datos de la primera tabla.
		// En la primera fila va el logo (con row span) y el nombre de la universidad.
		// En la segunda fila va la facultad.
		$institucion = institucion::get_datos_gestionante();
		$ra = guarani::co('co_propuestas')->get_listado_prop_ras_string("sga_propuestas.propuesta = {$this->s__datos_conf['propuesta']}");

		$filas[] = array(array('valor' => $this->xml_imagen($path_logo), 'number-rows-spanned' => 2, 'border' => 'none'), array('valor' => $institucion['nombre'], 'border' => 'none', 'font-weight' => 'bold', 'font-size' => 11));
		$filas[] = array(array('valor' => $ra, 'border' => 'none', 'font-weight' => 'bold', 'font-size' => 10));
		// Se genera la primera tabla.
		$xml .= $this->xml_tabla($filas, false);

		//----------------------------------------------------------------------
		//---- TÍTULO
		//----------------------------------------------------------------------
		
		// Se agrega el texto subrayado 
		$xml .= $this->xml_texto($this->s__titulo, array('font-weight' => 'bold', 'text-decoration' => 'underline', 'font-size' => 12, 'margin-bottom' => '0.5cm', 'text-align' => 'center'));


		//----------------------------------------------------------------------
		//---- CUERPO
		//----------------------------------------------------------------------
		
		// Segunda tabla (que contiene los datos). Se resetean las columnas de tablas y $fila
		$cols[] = array('column-width' => '20%');
		$cols[] = array('width' => '45%');
		$cols[] = array('width' => '35%');
		$this->xml_set_tabla_cols();

		$filas = array();
		// Se agregan las filas (varias con column span)
		foreach ($this->s__datos as $key => $dato) {
			if (isset($dato['etiq']) && $dato['valor']) {
				$filas[] = array(array('valor' => $dato['etiq'] . ': ', 'border' => 'none', 'text-align' => 'right', 'font-size' => 11), array('valor' => $dato['valor'], 'border' => 'none', 'font-weight' => 'bold', 'text-align' => 'left', 'font-size' => 11));
			} else {
				if ($key == 'tabla') {
					$tabla = array();
					$tabla_cols = array();
					foreach ($dato as $orden => $fila) {
						foreach ($dato[0] as $columna) {
							if ($orden == 0) {
								$tabla_cols[] = array('titulo' => $columna['nombre']);
							} else {
								$valor = $fila[$columna['clave']];
								$style = array();
								if (isset($columna['style'])) {
									$style = $columna['style'];
								}
								if (is_array($valor)) {
									$valor = implode('/', aplanar_matriz($valor, 'nombre'));
								}
								if (empty($valor)) {
									$valor = '';
								}
								$tabla[$orden][] = array_merge(array('valor' => $valor), $style);
							}
						}
					}
					$this->xml_set_tabla_cols($tabla_cols);
					$filas[] = array(array('valor' => $this->xml_tabla($tabla), 'number-columns-spanned' => '3', 'border' => 'none', 'text-align' => 'center', 'font-size' => 8));
					$this->xml_set_tabla_cols();
				}
			}
		}
		$filas[] = array(array('valor' => 'Nro. Transacción: ' . $this->s__datos_conf['nro_transaccion'], 'number-columns-spanned' => '3', 'border-top' => 'thin solid black', 'text-align' => 'center', 'font-size' => 12, 'font-weight' => 'bold'));

		// Se genera la segunda tabla
		$xml .= $this->xml_tabla($filas);

		//----------------------------------------------------------------------
		//---- PIE
		//----------------------------------------------------------------------
		
		$xml .= $this->xml_texto($this->s__pie, array('font-size' => 10, 'margin-bottom' => '0.5cm', 'text-align' => 'center'));

		//----------------------------------------------------------------------
		//---- Configuraciones Finales
		//----------------------------------------------------------------------
		
		$xml .= $this->xml_get_tag_fin();

		return $xml;
	}

}
?>