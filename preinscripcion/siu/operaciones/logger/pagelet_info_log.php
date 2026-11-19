<?php

namespace siu\operaciones\logger;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_info_log extends pagelet
{
	protected $path;

	public function get_nombre()
	{
		return 'info_log';
	}

	public function get_js_files()
	{
		
	}

	public function set_archivo_log($path)
	{
		$this->path = $path;
	}

	public function prepare()
	{
		$this->data = array();
		$this->data['carpeta_log'] = kernel::proyecto()->get_dir_log();
		$this->data['url_limpiar'] = kernel::vinculador()->crear_sin_chequeo('logger/limpiar_logs');

		if ($this->estado == 'log_seleccionado') {
			$this->data['info'] = file_get_contents($this->path);
		}
	}

}
?>
