<?php

namespace siu\util\consola\comandos;

use siu\guarani;
use Symfony\Component\Console;
use SIU\Chulupi\util\ManejadorArchivos;
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
		'js/lib/jquery.fix.clone-master/jquery.fix.clone.js',
		
		'js/facebox.js',
		'js/guarani.js',
		'js/jquery-ui.js',
		'js/jquery.ui.touch-punch.min.js',
		'js/jquery.mobile.custom.min.js',
		'js/bootstrap.js',
	];
	
	protected $css_browser = [
		'css/bootstrap.css',
		'css/bootstrap-responsive.css',
		['pers' => true, 'nombre' => 'css/guarani.css'],
		'css/guarani-responsive.css',
		'css/facebox.css',
		'css/jquery-ui.css',
		'css/font-awesome-4.6.3/css/font-awesome.min.css',
	];
	
	protected $css_not_browser = [
		'css/bootstrap.css',
		'css/bootstrap-responsive.css',
		['pers' => true, 'nombre' => 'css/guarani.css'],
		'css/guarani-responsive.css',
		'css/facebox.css',
		'css/jquery-ui.css',
		'css/font-awesome-4.6.3/css/font-awesome.min.css',
	];
	
	function __construct()
	{
		$this->path_destino = guarani::dir().'/www/_comp';
		parent::__construct();
	}
	
	protected function get_namespaces()
	{
		$rs = array(
			'siu' => realpath(guarani::dir().'/..')
		);
		$base_pers_dir = realpath(guarani::dir().'/../pers');
		$pers = array_filter(glob("$base_pers_dir/*"), 'is_dir');
		foreach ($pers as $path) {
			$nombre = basename($path);
			$rs[$nombre] = dirname($path);
		}
		
		return $rs;
	}
	
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
//		$this->input = $input;
//        $this->output = $output;
//		$this->mostrar_mensaje('Compilando escalas de notas');
//		generador_escalas::generar();
        \siu\bootstrap::cargar(true);
		parent::execute($input, $output);
		$this->mostrar_mensaje('Masheando archivos');
		$this->mash($this->js, 'guarani_mash.js');
		$this->mash($this->css_browser, 'guarani_mash.css');
		$this->mash($this->css_not_browser, 'guarani_mash_not_browser.css');
	}
	
	protected function compilar_pagelets($dir)
    {
        $rs = array();
        $tipos = array('js', 'css');
        $operaciones = $this->get_operaciones($dir);
		
        foreach ($operaciones as $op) {
			if ($op == '_comun') { // los pagelets están en /pagelets
				$pagelets = $this->get_pagelets($dir."/operaciones/_comun/pagelets");
				$pagelets = array_merge($pagelets, $this->get_pagelets($dir."/operaciones/_comun/zonas"));
			} else {
				$pagelets = $this->get_pagelets($dir."/operaciones/$op");
			}
			
            foreach ($pagelets as $pagelet) {
                foreach ($tipos as $tipo) {
                    $relativo = "operaciones/{$pagelet['relativo']}/{$pagelet['pagelet']}";
                    $archivos = $this->lista($dir."/$relativo", $tipo);
                    foreach ($archivos as $archivo) {
                        $rs[$pagelet['relativo']][$pagelet['pagelet']][] = "$dir/$relativo/$archivo";
                    }
                }
            }
        }
        return $rs;
    }
	
	protected function get_pagelets($dir)
    {
		if (! is_dir($dir)) { // puede que no exista. por ej en el caso de pers/operaciones/_comun/pagelets
			return array();
		}
		$paths = ManejadorArchivos::get_subdirectorios($dir);
        $rs = array();
		foreach ($paths as $path) {
			$rs[] = array('pagelet' => basename($path), 'relativo' => substr($dir, strrpos($dir,"/operaciones/")+13));
		}
		return $rs;
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
		if (! \SIU\Chulupi\util\ManejadorArchivos::es_windows()) {
			parent::guardar_archivo($archivo, $path_nuevo, $comprimir, $absoluto);
		} else {
			if (!$absoluto) {
				$archivo = kernel::localizador()->path_recurso($archivo);    
			}

			\SIU\Chulupi\util\ManejadorArchivos::crear_archivo_con_datos($path_nuevo, '');
			
			if ($comprimir) {
				$encoding = kernel::proyecto()->get_encoding();
				$cmd = "yuicompressor-2.4.2.jar $archivo --charset $encoding -o $path_nuevo";
				exec($cmd);            
			} else {
				copy($archivo, $path_nuevo);
			}
		}
    }
}