<?php
class ci_edi_doc_draft extends guarani_pers_ci
{
	function get_url_preview_doc_digital_draft()
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		return $this->cn()->entidad()->get_url_preview_doc_digital_draft($requisito);
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__pant_documento(toba_ei_pantalla $pantalla)
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		$path = $this->cn()->entidad()->get_path_descarga_doc_digital_draft($requisito);
		$pantalla->evento('descargar')->vinculo()->set_parametros(array('path_descarga' => $path));
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->controlador()->set_pantalla('pant_edicion');
	}

	function evt__guardar()
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		if ($this->datos['aceptar_rechazar'] == 'A') {
			$this->cn()->entidad()->aceptar_requisito_draft($requisito);
		} else {
			$this->cn()->entidad()->rechazar_requisito_draft($requisito, $this->datos['observaciones']);
		}
		$this->controlador()->set_pantalla('pant_edicion');
	}


	//-----------------------------------------------------------------------------------
	//---- formulario_doc_draft ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario_doc_draft(form_draft $form)
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		$persona = $this->cn()->entidad()->get_persona_seleccionada();
		$datos_requisito_digital_draft_detalle = $this->cn()->entidad()->get_datos_requisito_digital_draft_detalle($requisito, $persona);
		if (!empty($datos_requisito_digital_draft_detalle)) {
			$form->ef('observaciones')->set_estado($datos_requisito_digital_draft_detalle['observaciones']);
			if (!empty(trim($datos_requisito_digital_draft_detalle['observaciones']))) {
				$form->ef('aceptar_rechazar')->set_estado('R');
			}	
		}
	}

	function evt__formulario_doc_draft__modificacion($datos)
	{
		$this->datos = $datos;
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_accion_documento()
	{
		return array(	array('valor' => 'A','descr' => 'Aceptar documento digital'),
						array('valor' => 'R','descr' => 'Rechazar documento digital'));
	}
		
	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_aceptar = guarani_mensajes::get('800REQ_aceptar_requisito_digital');
		$msj_rechazar = guarani_mensajes::get('800REQ_rechazar_requisito_digital');
		echo "
			//---- Eventos ---------------------------------------------
			
			{$this->objeto_js}.evt__guardar = function()
			{
				var form = this.dep('formulario_doc_draft');
				var accion = form.ef('aceptar_rechazar').get_estado();
				if (accion == 'A') {
					return confirm('$msj_aceptar');
				} else {
					return confirm('$msj_rechazar');
				}
			}
		";
	}
}