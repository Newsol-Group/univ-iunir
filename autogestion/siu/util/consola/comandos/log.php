<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\ManejadorArchivos;

class log extends comando_guarani
{
	protected $input; //Console\Input\InputInterface
    /**
     * @var Console\Output\OutputInterface    
     */
    protected $output; 
    

	protected function configure()
	{
        $this
        ->setName('log')
		->setDescription('Muestra el último log disponible para el bloque de acceso seleccionado')
		->addArgument('acceso', InputArgument::REQUIRED, 'ID del bloque de acceso del log a mostrar')
        ->setHelp(sprintf(
				'%sMuestra el último log disponible para el bloque de acceso seleccionado.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
        $this->input = $input;
        $this->output = $output;
		$this->bootstrap_kernel($input->getArgument('acceso'));
		
		$archivos = ManejadorArchivos::get_archivos_directorio(kernel::proyecto()->get_dir_log());
        rsort($archivos);
		
		if (empty($archivos)) {
			$this->output->writeln('No hay logs para este bloque de acceso');
			return;
		}
		
		$this->output->writeln(file_get_contents(current($archivos)));
    }
}

?>
