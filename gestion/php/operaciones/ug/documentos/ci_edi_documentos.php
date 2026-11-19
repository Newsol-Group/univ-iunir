<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Documentos
 * 
 */
class ci_edi_documentos extends guarani_pers_ci
{
	protected $datos_temp;
	protected $datos_temp_ras;
	protected $s__datos_doc_temp;

	protected $s__path_doc_digital;

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
		if (! $this->cn()->entidad()->validar_docs_rel()) {
			$form->set_solo_lectura(array('documento_numero'));
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->datos_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_documentos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_documentos(form_documentos $form)
	{
		$upload_max_filesize = ini_get('upload_max_filesize');
		$form->ef('nombre_archivo')->set_descripcion("Los formatos de archivo permitidos son: pdf, doc, docx, odt, ods, rtf, txt, jpg, jpeg, gif, png, bmp, xls, xlsx, zip y rar. Tamaño máximo de archivo: $upload_max_filesize.");

		if (isset($this->s__datos_doc_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->s__datos_doc_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_doc();
		}

		if (isset($datos['nombre_archivo']) && isset($datos['documento'])) {
			$form->evento('descargar')->vinculo()->set_parametros(array('documento'=>$datos['documento']));
		} else {
			$form->evento('descargar')->anular();
			$form->evento('eliminar')->anular();
		}

		$form->set_datos($datos);
	}

	function evt__form_documentos__modificacion($datos)
	{
		if (! empty($datos['nombre_archivo']['tmp_name'])) {
			$this->eliminar_doc_digital();
			toba_manejador_archivos::crear_arbol_directorios(toba::proyecto()->get_path_temp().'/archivos');
			$this->s__path_doc_digital = toba::proyecto()->get_path_temp().'/archivos/'.uniqid();
			rename($datos['nombre_archivo']['tmp_name'], $this->s__path_doc_digital);
			$datos['nombre_archivo']['tmp_name'] = $this->s__path_doc_digital;
			$this->s__datos_doc_temp = $datos;
		}
	}

	function evt__form_documentos__eliminar()
	{
		$this->cn()->entidad()->eliminar_datos_doc();
		$this->eliminar_doc_digital();
	}

	//-----------------------------------------------------------------------------------
	//---- form_responsables_academicas -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_responsables_academicas(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_ras)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_ras);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_responsables_academicas(), 'responsable_academica');
			$form->set_datos(array('responsable_academica' => $datos));
		}
	}

	function evt__form_responsables_academicas__modificacion($datos)
	{
		$this->datos_temp_ras = $datos;
	}

	function eliminar_doc_digital()
	{
		if (file_exists($this->s__path_doc_digital)) {
			unlink($this->s__path_doc_digital);
			unset($this->s__path_doc_digital);
			unset($this->s__datos_doc_temp);
		}
	}

	function post_eventos()
	{
		try {
			if (isset($this->datos_temp)) {
				$this->cn()->entidad()->set_datos($this->datos_temp);
				unset($this->datos_temp);
			}
			if (isset($this->s__datos_doc_temp)) {
				$this->cn()->entidad()->set_datos_doc($this->s__datos_doc_temp);
				unset($this->s__datos_doc_temp);
			}
			if (isset($this->datos_temp_ras)) {
				$this->cn()->entidad()->set_datos_responsables_academicas($this->datos_temp_ras['responsable_academica']);
				unset($this->datos_temp_ras);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}
}
?>