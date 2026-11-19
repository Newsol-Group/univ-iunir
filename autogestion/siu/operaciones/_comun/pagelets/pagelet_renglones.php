<?php
namespace siu\operaciones\_comun\pagelets;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\guarani;
use siu\util\generador_escalas;

abstract class pagelet_renglones extends pagelet
{
	const MAX_CARACTERES_CORREGIDO_POR= 20;

	protected $encabezado;
	protected $renglon = false;
	/**
	 * @var \siu\errores\error_guarani_procesar_renglones 
	 */
	protected $errores = false;
	
	
	protected function get_encabezado()
	{
		if (! isset($this->encabezado)) {
			$this->encabezado = $this->controlador->get_encabezado();
		}
		
		return $this->encabezado;
	}
	
	protected function get_nombre_js_escala($escala)
	{
		return "escala_$escala.js";
	}
		
	protected function crear_escala_si_no_existe($escala)
	{
		$nombre_js = $this->get_nombre_js_escala($escala);
		$path = guarani::path_escalas_js()."/$nombre_js";
		if (! file_exists($path)) {
			kernel::log()->add_debug('path escalas', "el archivo '$path' no existe. Generando escalas...");
			generador_escalas::generar();
		}
	}
	
	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$escalas = $this->get_escalas();
		if (!is_array($escalas)) {
			$escalas = array($escalas);
		}
		$bloque_acceso = kernel::proyecto()->get_bloque_acceso();
		foreach ($escalas as $escala) {
			$this->crear_escala_si_no_existe($escala);	
			$nombre_js = $this->get_nombre_js_escala($escala);
			$archivos[] = kernel::vinculador()->vinculo_recurso("js/escalas/$bloque_acceso/$nombre_js");
		}
				
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_renglones.js");
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/escalas/$bloque_acceso/cond_res.js");
		
		return $archivos;
	}
	
	function set_highlight($renglon)
	{
		$this->renglon = $renglon;
	}
	
	function set_error_guarani_procesar_renglones(\siu\errores\error_guarani_procesar_renglones $e) 
	{
		$this->errores = $e;
	}
	abstract protected function get_escalas();
	abstract protected function get_url_guardar();
	
	
	protected function get_escala_notas()
	{
		$encabezado = $this->get_encabezado();
		return $this->controlador->modelo()->info__escala_notas($encabezado['escala_nota']);
	}
	
	function prepare() 
	{
		$this->data = array();
		$this->data['escala_notas'] = $this->get_escala_notas();
		$this->data['renglones'] = $this->get_renglones();
		$this->data['url_guardar'] = $this->get_url_guardar();
		$this->add_var_js('highlight_renglon', $this->renglon);
		$this->add_var_js('id_escala', $this->get_escala());
	}
}
?>
