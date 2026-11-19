<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\ClaseDatos;
use siu\guarani;
use siu\errores\error_guarani;

class generar_autoload extends comando_guarani
{
	protected $dir;
	protected $clase_def = "_info_catalogo";
	protected $info = array();

	protected function configure()
	{
        $this
        ->setName('generar_autoload')
		->setDescription('Genera el Archivo de Autoload del nucloe de G3')
        /*
		->setDefinition(array(
            new InputArgument('x', InputArgument::REQUIRED, 'First addend'),
            new InputArgument('y', InputArgument::REQUIRED, 'Second addend'),
            new InputOption('round', null, null, 'Round result')
        ))*/
        ->setHelp(sprintf(
				'%sGenera el Archivo de Autoload del nucleo de G3 y el Archivo con la definición de la ubicación de las clases de consulta estén o no personalizadas.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }

	
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->bootstrap_kernel();
		//include_once 'lib/toba_extractor_clases.php';
		
		
		$punto_de_montaje = guarani::dir() . '/modelo_g3/nucleo';
		
		$param = array(
	 		$punto_de_montaje => array(
	 			'archivo_salida' => 'g3_autoload.php',
				'path_salida' => guarani::dir().'/modelo_g3',
	 			'dirs_excluidos' => array(),
				'extras' => array()
	 		)
		);
		
		$punto_de_montaje_pers = guarani::dir() . '/modelo_g3/nucleo_pers';
		$param[$punto_de_montaje_pers] = array(
											'archivo_salida' => 'g3_pers_autoload.php',
											'path_salida' => guarani::dir().'/modelo_g3',
											'dirs_excluidos' => array(),
											'extras' => array()
										);
		
		$extractor = new g3_extractor_clases($param);
		$extractor->generar();
		$extractor_clases_consulta = new g3_extractor_clases_consulta();
		$extractor_clases_consulta->generar();
        $output->write("OK\n");
    }
}