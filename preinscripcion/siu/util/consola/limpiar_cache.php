<?php
namespace siu\util\consola\comandos;
use Symfony\Component\Console;
class limpiar_cache extends comando_guarani
{
	protected $input; //Console\Input\InputInterface
	protected $output; //Console\Output\OutputInterface

	protected function configure()
	{
        $this
        ->setName('limpiar_cache')
		->setDescription('Limpia la cache de APC')
        ->setHelp(sprintf(
				'%sLimpia la cache de APC.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;
		
		if (! extension_loaded('curl')) {
			die("la extensión curl no está instalada. En sistemas basados en debian ejecute:\n apt-get install php5-curl\n");
		}
		
		$path_archivo_alias = realpath(__DIR__.'/../../../../../instalacion/alias.conf');
		
		if ($path_archivo_alias !== false) {
			preg_match_all('/ACC_ALIAS=(.*)/i', file_get_contents($path_archivo_alias), $result);
		}
				
		if ($path_archivo_alias === false || empty($result[1])) {
			echo "No se encontró el archivo de alias, no se puede armar la url.\n";
			$this->die_error();
		}
		
		$path_clearapc = __DIR__.'/../../../www/clearapc.php';
		file_put_contents($path_clearapc, "<?php
if (in_array(@\$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
  apcu_clear_cache();
  echo 'EXITO. se limpió la cache.';
}

?>");
		$exito = false;
		foreach ($result[1] as $alias) {
			$url = "http://localhost$alias/clearapc.php";
			$ch = curl_init();			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			if (strpos($output, 'EXITO') !== false) {
				$exito = true;
				break;
			}
		}
		
		unlink(realpath($path_clearapc));
		
		if ($exito) {
			echo "La cache se limpió correctamente\n";
		} else {
			echo "No se pudo llegar a ninguna de estas urls: \n";
			foreach ($result[1] as $alias) {
				$url = "http://localhost$alias/clearapc.php";
				echo "\t* $url\n";
			}
			$this->die_error();
		}
    }
	
	protected function die_error()
	{
		echo "Para limpiar la cache manualmente debe realizar los siguientes pasos\n";
		echo "\t1. cd /path/to/guarani/\n";
		echo "\t2. cp src/siu/var/clearapc.php src/siu/www\n";
		echo "\t3. acceder a través del navegador a http://url_guarani/alias_guarani/clearapc.php\n";
		echo "\t4. rm src/siu/var/clearapc.php\n";
		echo "RECUERDE borrar el archivo (4)!\n";
		die();
	}
}