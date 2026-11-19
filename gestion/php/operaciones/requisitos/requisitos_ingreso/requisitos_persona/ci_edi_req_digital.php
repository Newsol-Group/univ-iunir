<?php
class ci_edi_req_digital extends guarani_pers_ci
{
	protected $s__temp_upload;

	function resetear()
	{
		unset($this->s__temp_upload);
	}

	function documento_temp_cargado()
	{
		return (!empty($this->s__temp_upload));
	}

	function get_descr_estado()
	{
		if ($this->documento_temp_cargado()) {
			return 'Documento subido. Presione "Aceptar" para prepararlo para enviar al Repositorio.';
		} else {
			$requisito = $this->controlador()->get_requisito_seleccionado();
			return $this->cn()->entidad()->get_descr_estado_rdi($requisito);
		}
	}

	function get_url_preview_doc_digital()
	{
		if (!empty($this->s__temp_upload)) {
			// Hay carga temporal no aceptada
			if ($this->cn()->entidad()->es_imagen($this->s__temp_upload['tmp_name'])) {
				// Es imagen
				$archivo = toba::proyecto()->get_www_temp($this->s__temp_upload['filename']);
				$url = $archivo['url'];
			} else {
				// No es es imagen
				$archivo = toba::proyecto()->get_www("img/doc_cargado.png");
				$url = $archivo['url'];
			}
		} else {
			// No hay carga temporal -> se busca en Repositorio Digital
			$requisito = $this->controlador()->get_requisito_seleccionado();
			$url = $this->cn()->entidad()->get_url_preview_doc_digital($requisito);
		}
		return $url;
	}

	function aceptado_desde_draft()
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		return $this->cn()->entidad()->aceptado_draft($requisito);
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__pant_documento(toba_ei_pantalla $pantalla)
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();

		// Comportamiento del botón "Aceptar"
		if (!$this->documento_temp_cargado()) {
			$pantalla->evento('aceptar')->desactivar();
		} else {
			$confirmacion = 'Ha realizado una carga temporal que será descartada. ¿Desea continuar?';
			$pantalla->evento('cancelar')->set_msg_confirmacion($confirmacion);
			$pantalla->evento('eliminar')->desactivar();
			$pantalla->evento('descargar')->desactivar();
		}
		// Comportamiento de los botones "Eliminar" y "Descargar"
		if (!$this->cn()->entidad()->doc_digital_cargado($requisito)) {
			$pantalla->evento('eliminar')->desactivar();
			$pantalla->evento('descargar')->desactivar();
		} else {
			$requisito = $this->controlador()->get_requisito_seleccionado();
			$path = $this->cn()->entidad()->get_path_descarga_doc_digital($requisito);
			$pantalla->evento('descargar')->vinculo()->set_parametros(array('path_descarga' => $path));
			$pantalla->evento('eliminar')->set_msg_confirmacion('¿Desea eliminar el documento digital?');
		}
		if ($this->cn()->entidad()->aceptado_draft($requisito)) {
			$pantalla->evento('eliminar')->desactivar();
			$pantalla->evento('eliminar')->set_msg_ayuda('El documento ha sido aceptado desde Preinscripción');
		}
	}

	//--------------------------------------------------------------------------
	//---- formulario ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__formulario(guarani_pers_ei_formulario $form)
	{
		
	}

	function evt__formulario__modificacion($datos)
	{
		if (!empty($datos['documento_digital'])) {
			$temp_nombre = md5(uniqid(time()));
			$temp_archivo = toba::proyecto()->get_www_temp($temp_nombre);

			$extension_partes = explode(".", $datos['documento_digital']['name']);
			$cant_partes = guarani_arreglos::count($extension_partes);
			$extension = $extension_partes[$cant_partes - 1];
			if (!toba::consulta_php('co_configuracion')->es_rdi_extension_permitida($extension)) {
				throw new toba_error('La extensión ' . $extension . ' no está permitida');
			}

			move_uploaded_file($datos['documento_digital']['tmp_name'], $temp_archivo['path']);

			$this->s__temp_upload = current($datos);
			$this->s__temp_upload['tmp_name'] = $temp_archivo['path'];
			$this->s__temp_upload['filename'] = $temp_nombre;
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->resetear();
		$this->controlador()->set_pantalla('pant_edicion');
	}

	function evt__eliminar()
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		// Desmarco al requisito como "¿Presentado?" y borro la "Fecha Presentación".
		$this->cn()->entidad()->eliminar_requisito($requisito);
		$this->evt__cancelar();
	}

	function evt__aceptar()
	{
		$requisito = $this->controlador()->get_requisito_seleccionado();
		$archivo = $this->s__temp_upload;
		$this->cn()->entidad()->cargar_documento($requisito, $archivo);
		$this->evt__cancelar();
	}

}