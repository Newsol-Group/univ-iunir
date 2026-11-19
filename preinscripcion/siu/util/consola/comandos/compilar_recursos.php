<?php

namespace siu\util\consola\comandos;

use siu\preinscripcion;
use Symfony\Component\Console;
use SIU\Chulupi\util\ManejadorArchivos as manejador_archivos;
use SIU\Chulupi\consola\comandos\CompilarRecursos;
use SIU\Chulupi\kernel;

class compilar_recursos extends CompilarRecursos
{
	protected $js = [
		'js/lib/jquery-1.12.4.min.js',
		'js/lib/jquery-migrate-1.2.1.min.js',
		
		'js/lib/history/uncompressed/history.adapter.jquery.js',
		'js/lib/history/uncompressed/history.js',
		'js/lib/history/uncompressed/history.html4.js',
		
		'js/lib/lazyload.js',
		
		'js/lib/kernel.loader.js',
		'js/lib/kernel.renderer.js',
		'js/lib/kernel.evts.js',
		'js/lib/kernel.ajax.js',
		'js/lib/kernel.historia.js',
		'js/lib/kernel.util.js',
		'js/lib/kernel.ui.js',
		'js/lib/kernel.log.js',
		
		'js/facebox.js',
		'js/preinscripcion.js',
		'js/jquery-ui.js',
		'js/bootstrap.js',
	];
	
	protected $css_browser = [
		'css/bootstrap.css',
		['pers' => true, 'nombre' => 'css/preinscripcion.css'],
		'css/facebox.css',
		'css/jquery-ui.css',
	];
	
	protected $css_not_browser = [
		'css/bootstrap.css',
		'css/bootstrap-responsive.css',
		'css/preinscripcion.css',
		'css/preinscripcion-responsive.css',
		'css/facebox.css',
		'css/jquery-ui.css',
	];

	function __construct()
	{
		$this->path_destino = preinscripcion::dir() . '/www/_comp';
		parent::__construct();
	}

	protected function get_namespaces()
	{
		$rs = array(
			'siu' => realpath(preinscripcion::dir() . '/..')
		);
		$base_pers_dir = realpath(preinscripcion::dir() . '/../pers');
		$pers = array_filter(glob("$base_pers_dir/*"), 'is_dir');
		foreach ($pers as $path) {
			$nombre = basename($path);
			$rs[$nombre] = dirname($path);
		}

		return $rs;
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->bootstrap_kernel();
		
		kernel::log_cli()->debug('=== Archivos JSs a mashear ===');
		kernel::log_cli()->debug(print_r($this->js, true));
		kernel::log_cli()->debug('=== Archivos CSSs Browser a mashear ===');
		kernel::log_cli()->debug(print_r($this->css_browser, true));
		kernel::log_cli()->debug('=== Archivos CSSs not Browser a mashear ===');
		kernel::log_cli()->debug(print_r($this->css_not_browser, true));
		
		$this->preparar_compilacion($input, $output);

		$this->mostrar_mensaje('Masheando archivos');
		$this->mash($this->js, 'preinscripcion_mash.js');
		$this->mash($this->css_browser, 'preinscripcion_mash.css');
		$this->mash($this->css_not_browser, 'preinscripcion_mash_not_browser.css');
	}

	protected function bootstrap_kernel($ua = false)
	{
		$_SERVER['PERFIL'] = 'consola';
		\siu\bootstrap::cargar(true, $ua);
	}

	protected function preparar_compilacion(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;

		$this->mostrar_mensaje('Borrando compilaciones anteriores');
		$this->borrar_directorio($this->path_destino, false);

		$this->mostrar_mensaje('Borrando cache de twig');
		$this->borrar_cache_twig();
		$this->path_destino = $input->getArgument('path');
		$namespaces = $this->get_namespaces();
		$recursos = array(
			'comun' => array(),
			'pagelets' => array()
		);

		foreach ($namespaces as $id => $dir) {
			$path_ns = $dir . "/$id";
			$recursos['www'][$id] = $this->compilar_www($path_ns);
			$recursos['comun'][$id] = $this->compilar_comun($path_ns);
			$recursos['pagelets'][$id] = $this->compilar_pagelets($path_ns);
		}

		$this->crear_compilacion($recursos);
		$this->mostrar_mensaje('Fin de compresion');
	}

	function borrar_directorio($dir, $borrar_carpeta = true)
	{
		if (!$dh = @opendir($dir)) {
			return;
		}
		while (false !== ($obj = readdir($dh))) {
			if ($obj == '.' || $obj == '..')
				continue;
			if (!@unlink($dir . '/' . $obj)) {
				$this->borrar_directorio($dir . '/' . $obj, true);
			}
		}
		closedir($dh);
		if ($borrar_carpeta) {
			@rmdir($dir);
		}
	}

	protected function mash($files, $filename)
	{
		$type = pathinfo($filename, \PATHINFO_EXTENSION);
		$siu_files = array();
		$archivos_por_pers = array();
		$personalizaciones = $this->get_namespaces();

		foreach ($files as $key => $file) {
			if (is_array($file)) {
				$siu_files[$key] = $this->path_destino.'/siu/'.$file['nombre'];
				$buscar_pers = isset($file['pers']) && $file['pers'];
				if ($buscar_pers) {
					foreach (array_keys($personalizaciones) as $id) {
						if ($id == 'siu') continue;
						$pers_file = "$this->path_destino/$id/{$file['nombre']}";
						if (file_exists($pers_file)) {
							$archivos_por_pers[$id][] = $pers_file;
						}
					}
				}
			} else {
				$siu_files[$key] = $this->path_destino.'/siu/'.$file;
			}
		}

		$file_list = implode(' ', $siu_files);
		$salida = "$this->path_destino/siu/$type/$filename";

		if (\SIU\Chulupi\util\ManejadorArchivos::es_windows()) {
			$salida = str_replace('/', "\\", $salida);
			$cmd = "type $file_list >> $salida";
			foreach ($files as $file) {
				$file = str_replace('/', "\\", $file);
				$cmd = "type $file >> $salida";
				exec($cmd);
			}
		} else {
			$cmd = "cat $file_list > $salida";
			exec($cmd);
		}


		if (!empty($archivos_por_pers)) {
			foreach ($archivos_por_pers as $pers => $archivos_pers) {
				$file_list = implode(' ', $archivos_pers);
				$salida = "$this->path_destino/$pers/$type/$filename";

				if (\SIU\Chulupi\util\ManejadorArchivos::es_windows()) {
					$salida = str_replace('/', "\\", $salida);
					$cmd = "type $file_list >> $salida";
					foreach ($files as $file) {
						$file = str_replace('/', "\\", $file);
						$cmd = "type $file >> $salida";
						exec($cmd);
					}
				} else {
					$cmd = "cat $file_list > $salida";
					exec($cmd);
				}
			}
		}
	}

	protected function guardar_archivo($archivo, $path_nuevo, $comprimir = true, $absoluto = false)
	{
		if (!manejador_archivos::es_windows()) {
			parent::guardar_archivo($archivo, $path_nuevo, $comprimir, $absoluto);
			return;
		}

		if (!$absoluto) {
			$archivo = kernel::localizador()->path_recurso($archivo);
		}

		manejador_archivos::crear_archivo_con_datos($path_nuevo, '');
		if ($comprimir) {

			$encoding = kernel::proyecto()->get_encoding();
			$cmd = "yuicompressor-2.4.2.jar $archivo --charset $encoding -o $path_nuevo";
			exec($cmd);
		} else {
			copy($archivo, $path_nuevo);
		}
	}

}