<?php
namespace siu\operaciones\diploma_digital;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo_g3\arai_documentos;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;

class controlador extends controlador_g3w2
{

	/**
	 * @return \siu\modelo\transacciones\sidcer
	 */
	function modelo()
	{
		return guarani::sidcer();
	}
	
	function accion__index()
	{

	}

	function accion__descargar_documento()
	{
		try {
			$uid = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
			$path = arai_documentos::descargarDocumentoToFilesystem($uid);
			$this->descargar_archivo($path, "archivo.pdf", true);
		} catch (FinalizadorRequest $e) {
			// Si se logra descargar el PDF se lanza una excepción de tipo 'FinalizadorRequest', esto no se trata de un error.
			throw $e;
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
            $mensaje = kernel::traductor()->trans('diploma_digital.fallo_generico');
			kernel::sesion()->set('error_descarga', $mensaje);
			$operacion = kernel::ruteador()->get_id_operacion();
			$this->nuevo_request($operacion, 'index');
		}
		
	}
}