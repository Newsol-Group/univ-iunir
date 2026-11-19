<?php
class ci_comprobante_inscripcion extends ci_impresion_popup
{
	// Archivo PDF
	protected $archivo = '';
	
	// Mail
	protected $s__datos_mail;
	protected $envio_ok = false;
	protected $s__evento_mail = false;

	function ini()
	{
		parent::ini();
		toba::solicitud()->set_datos_item(array('item_nombre' => $this->s__titulo));
	}

	function conf()
	{
		// Se genero el Comprobante
		$pdf = new guarani_pdf(array($this));
		$this->archivo = $pdf->generar_con_jasper();

		// Si esta seteado id_persona se debe implementar el envio de mail del comprobante
		if (!is_null($this->get_conf('id_persona'))) {

			$habilitar_envio = (!is_null($this->get_conf('envio_autom'))) ? true : false;
			$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($this->get_conf('propuesta'));
			
			// Guarda los datos en memoria para el envio de mail
			toba::memoria()->set_dato('ra', $ra);
			toba::memoria()->set_dato('id_persona', $this->get_conf('id_persona'));
			toba::memoria()->set_dato('path_comp', $this->archivo);
			$asunto = $this->s__titulo . ' - SIU-Guarani 3';
			toba::memoria()->set_dato('asunto', $asunto);
			
			// Se debe enviar el mail automáticamente?
			$envio_mail_autom = guarani::parametros()->get_valor('email_envio_automatico_comprobante', $ra);
			if (($envio_mail_autom == comunes::si) && $habilitar_envio) {
				$this->enviar_mail($this->get_conf('id_persona'), $asunto);
			} else {
				// Se setea el flag para el envio del mail manual
				$this->s__evento_mail = true;
			}
		}
	}

	function conf__pant_comprobante(toba_ei_pantalla $pantalla)
	{
		if (!$this->s__evento_mail) {
			$this->evento('mail')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- REPORTE PDF ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function servicio__pdf()
	{
		$archivo_pdf = file_get_contents($this->archivo);
		$this->enviar_archivo($archivo_pdf);
	}

	/**
	 * @ignore
	 */
	function enviar_archivo($archivo)
	{
		$this->cabecera_http(strlen(ltrim($archivo)));
		echo ltrim($archivo);
	}

	protected function cabecera_http($longuitud)
	{
		header("Cache-Control: private");
		header("Content-type: application/pdf");
		header("Content-Length: $longuitud");
		header("Content-Disposition: attachment; filename=recibo.pdf");
		header("Pragma: no-cache");
		header("Expires: 0");
	}

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		// Se establece la plantilla a usar
		$path = guarani::get_path_reporte_jasper('ComprobanteInscripcion.jasper');
		$report->set_path_reporte($path);

		$institucion = institucion::get_datos_gestionante();
		$ra = guarani::co('co_propuestas')->get_listado_prop_ras_string("sga_propuestas.propuesta = {$this->s__datos_conf['propuesta']}");

		// Parámetros 
		$report->set_parametro('titulo', 'S', $this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('ra', 'S', $ra);
		$report->set_parametro('institucion', 'S', $institucion['nombre']);
		$report->set_parametro('nro_transaccion', 'S', $this->s__datos_conf['nro_transaccion']);
		$report->set_parametro('pie', 'S', $this->s__pie);

		// Se agregan los datos 
		$xml = '<tabla><datos>';
		foreach ($this->s__datos as $dato) {
			
			$valor_xml = guarani_texto::reemplazar_caracteres_especiales_xml($dato['valor']);
			
			$xml .= '<fila>
						<dato clave="etiq" valor="' . $dato['etiq'] . '"/>
						<dato clave="valor" valor="' . $valor_xml . '"/>
					</fila>';
		}
		$xml .= '</datos></tabla>';
		$report->set_xml(utf8_e_seguro($xml));
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}

	//-----------------------------------------------------------------------------------
	//---- Envío de Mail ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function enviar_mail($persona, $asunto)
	{
		$this->s__datos_mail = null;

		$mail = new guarani_mail($persona, null, $asunto);
		$mail->set_html(true);

		$mail->agregar_adjunto("Comprobante.pdf", $this->archivo, 'base64', 'pdf');

		try {
			$this->s__datos_mail = $mail->get_datos_mail();
			$mail->ejecutar();
			$this->envio_ok = true;
		} catch (toba_error $e) {
			$this->envio_ok = false;
		}
	}

	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}

	function envio_ok()
	{
		return $this->envio_ok;
	}

}
?>
