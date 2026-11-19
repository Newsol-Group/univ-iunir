<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

use siu\guarani;
use SIU\Chulupi\kernel;
use siu\util\generador_escalas;

class generar_escalas_js extends comando_guarani
{
	protected $input; //Console\Input\InputInterface
	protected $output; //Console\Output\OutputInterface

	protected function configure()
	{
        $this
        ->setName('generar_escalas_js')
		->setDescription('Genera las escalas de notas en javascript para realizar validaciones.')
		->addArgument('acceso', InputArgument::REQUIRED, 'ID del bloque de acceso para el cuál se quiere generar las escalas')
        ->setHelp(sprintf(
				'%sGenera las escalas de notas en varios archivos js para que luego puedan ser pasadas a las vistas.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->bootstrap_kernel($input->getArgument('acceso'));
		$this->input = $input;
		$this->output = $output;
		
		generador_escalas::generar();
    }
}