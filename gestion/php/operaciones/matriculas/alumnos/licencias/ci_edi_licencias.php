<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class ci_edi_licencias extends guarani_pers_ci
{
	protected $datos_temp;
	protected $s__datos_doc_temp;

	protected $s__path_doc_digital;

	function get_inscripciones_persona()
	{
		$persona = $this->controlador()->get_preseleccion();
		return guarani::co('co_personas')->get_propuestas_persona($persona);
	}
	
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

		// El EF "Trámite" se muestra sólo en modo modificación (sólo lectura).
		if ($this->cn()->entidad()->es_nueva()) {
			$form->desactivar_efs(array('licencia'));
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$datos['persona'] = $this->controlador()->get_preseleccion();
		$this->datos_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_documentos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_documentos(form_documentos $form)
	{
		$upload_max_filesize = ini_get('upload_max_filesize');
		$form->ef('nombre_archivo')->set_descripcion("Los formatos de archivo permitidos son: pdf, doc, docx, odt, ods, rtf, txt, jpg, jpeg, gif, png, bmp, xls, xlsx, zip y rar. Tamaño máximo de archivo: {$upload_max_filesize}B.");

		if (isset($this->s__datos_doc_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->s__datos_doc_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_doc();
		}

		if (isset($datos['nombre_archivo']) && isset($datos['licencia_doc'])) {
			$form->evento('descargar')->vinculo()->set_parametros(array('licencia_doc'=>$datos['licencia_doc']));
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
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}
}
?>