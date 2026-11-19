<?php

namespace iunir\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\modelo\util;
use siu\modelo\g3\g3;
use SIU\Chulupi\util\img;
use siu\operaciones\censo\pagelet_documentacion as pagelet_documentacion_base;

class pagelet_documentacion extends pagelet_documentacion_base
{
	protected $mensaje_error;

	function get_nombre()
	{
		return 'documentacion';
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		return $files;
	}

	function set_mensaje_error($mensaje)
	{
		$this->mensaje_error = $mensaje;
	}

	function prepare()
	{
		$datos_inscripcion = kernel::persona()->datos()->get_datos_inscripciones();
		$datos_persona = kernel::persona()->datos()->get_datos_formularios_preinsc();
		
		$this->data['datos_inscripcion'] = $datos_inscripcion;

		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('msj_confirmar_eliminar'));

		$requisitos_a_presentar = kernel::persona()->datos()->get_requisitos_a_presentar();

		$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();

		$this->data['requisitos'] = [];

		$extensiones = array_column(g3::entidad('extensiones')->get_extensiones_permitidas(), 'extension');
		$this->data['extensiones'] = implode(", ", $extensiones);

		$max_tamanio_archivo = preg_replace('/[^0-9\.]/', '', ini_get('upload_max_filesize'));
		$max_tamanio_post = preg_replace('/[^0-9\.]/', '', ini_get('post_max_size'));

		if ($max_tamanio_archivo < $max_tamanio_post) {
			$tamanio_max = $max_tamanio_archivo;
		} else {
			$tamanio_max = $max_tamanio_post;
		}
		$this->data['tamanio_maximo'] = $tamanio_max;
		$this->add_var_js('tamanio_maximo', $tamanio_max);
		$this->add_var_js('error_tamanio_maximo', kernel::traductor()->trans('doc.msj_error_tamanio'));

		if (kernel::sesion()->esta_seteada('error_extension')) {
			$this->data['error_extension'] = true;
			kernel::sesion()->borrar('error_extension');
		}

		if (kernel::sesion()->esta_seteada('error_tamanio')) {
			$this->data['error_tamanio'] = true;
			kernel::sesion()->borrar('error_tamanio');
		}
		
		if (isset($this->mensaje_error)) {
			$this->data['error'] = $this->mensaje_error;
		}

		$error_cnx_rdi = false;

		if (!empty($requisitos_a_presentar)) {
			foreach ($requisitos_a_presentar as $requisito) {
				$clave = $requisito['requisito'];

				$clave_encriptada = util::catalogo()->generar_id($clave);
				$conf = array();
				$conf['obligatorio_faltante'] ='';
				$conf['url_subir'] = kernel::vinculador()->crear('censo', 'guardar_documentacion', array('documentacion', $clave_encriptada));
				$conf['url_eliminar'] = kernel::vinculador()->crear('censo', 'eliminar_documentacion', array('documentacion', $clave_encriptada));
				$conf['url_modificar'] = kernel::vinculador()->crear('censo', 'modificar_documentacion', array('documentacion', $clave_encriptada));
				$conf['url_descargar'] = kernel::vinculador()->crear('censo', 'descargar_documentacion', array('documentacion', $clave_encriptada));

				$conf['header'] = $requisito['nombre'];
				$conf['id_form'] = 'form_' . $clave_encriptada;

				$conf['descripcion'] = $requisito['descripcion'];
				$conf['obligatorio'] = ($requisito['doc_digital_obligatorio_preinscripcion']=='S')?'(*)':'';

				$requisito_presentado = $this->buscar_requisito($requisitos_aspirante, $requisito['requisito']);
				
				try {
					if (!empty($requisito_presentado['id_documento'])) {
						$id_recurso = $requisito_presentado['id_documento'];
						
						$srv = kernel::proyecto()->rdi()->servicio($requisito['tipo_documento_digital']);
						
						$content = $srv->recuperarContenido($id_recurso);
						$url = img::url_imagen($content);
						
						$clave_modificacion = 'requisito_' . $clave;
						if (kernel::sesion()->esta_seteada($clave_modificacion)) {
							kernel::sesion()->borrar($clave_modificacion);
						} else {
							if (img::check_base64_image($content)) {
								$conf['imagen'] = $url;
							} else {
								$conf['imagen'] = kernel::vinculador()->vinculo_img('img_recurso.png');
							}
						}
					}
				} catch (\Exception $e) {
					kernel::log()->add_error($e);
					$error_cnx_rdi = true;
					break;
				}
				$this->data['requisitos'][$clave] = $conf;
			}
			$this->data['error_cnx_rdi'] = $error_cnx_rdi;

			if ($this->controlador->vista()->control_faltantes_activado()) {

				$this->marcar_faltantes();
			}
		}
	}
	
    function vinculo_recurso($path_relativo, $ns = false)
    {
		$ns = ($ns === false) ? kernel::localizador()->get_nombre_namespace_original() : $ns;
		$alias = kernel::proyecto()->get_alias();
		return "$alias/$path_relativo";
    }
	
	//--------------------------------------------------------------------------

	protected function buscar_requisito($requisitos, $buscado)
	{
		foreach ($requisitos as $requisito) {
			if ($requisito['requisito'] == $buscado) {
				return $requisito;
			}
		}
		return null;
	}

}