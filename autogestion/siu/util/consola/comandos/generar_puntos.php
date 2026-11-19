<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\ClaseDatos;
use siu\guarani;

class generar_puntos extends comando_guarani {

	protected function configure()
	{
        $this
        ->setName('generar_puntos')
		->setDescription('Compilar puntos de control.')
        /*
		->setDefinition(array(
            new InputArgument('x', InputArgument::REQUIRED, 'First addend'),
            new InputArgument('y', InputArgument::REQUIRED, 'Second addend'),
            new InputOption('round', null, null, 'Round result')
        ))*/
        ->setHelp(sprintf(
				'%sCompila puntos de control del sistema.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
        $this->bootstrap_kernel();
		$this->input = $input;
		$this->output = $output;
		$this->compilar_puntos();
    }
	
	function compilar_puntos()
	{
		$sql ="SELECT * FROM par_puntos_control;";
		$puntos = kernel::db()->consultar($sql);
		foreach($puntos as $punto) {
			$usa_ua = false;
			$this->output->write($punto['PUNTO_DE_CONTROL'] . ' - ' . $punto['NOMBRE'],true);
			$pid = $punto['PUNTO_DE_CONTROL'];
			$sql ="SELECT * FROM par_punto_desc_par 
					WHERE punto_de_control = {$pid}
					ORDER BY orden;";
			//-- Procesar parametros --
			$params = kernel::db()->consultar($sql);	
			$lista_parametros = array();
			foreach($params as $id => $param) {
				$lista_parametros[] = $param['NOMBRE'];
				// hacer tipo mas accesible
				if (strpos($param['TIPO'],'varchar')!==false) {
					$temp=array();
					preg_match("/varchar\((?<largo>.*?)\)/",$param['TIPO'],$temp);
					$params[$id]['TIPO'] = 'varchar';
					$params[$id]['LARGO'] = $temp['largo'];
				}
				if ($param['NOMBRE'] == 'unidad_academica') {
					$usa_ua = true;
				}
			}
			$path =  guarani::dir() .  "/modelo/controles/puntos_control/punto_control_{$pid}.php";
			//$path = realpath($path);
			$clase = new ClaseDatos("punto_control_{$pid} extends \\siu\\modelo\\controles\\punto_control",
										null, 'siu\modelo\controles\puntos_control', false);
			$clase->agregar_metodo_datos('utiliza_unidad_academica', $usa_ua);
			$clase->agregar_metodo_datos('lista_parametros', $lista_parametros);
			$clase->agregar_metodo_datos('info', $punto);
			$clase->agregar_metodo_datos('info_parametros', $params);
			$clase->guardar($path);	
			//$this->output->write(' -- OK: ' . $path,true);
		}
	}
}