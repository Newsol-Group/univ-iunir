<?php

namespace siu\util\consola;

use SIU\Chulupi\kernel;
use Symfony\Component\Console\Application,
	siu\util\consola\comandos;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class gadmin extends Application
{
	public function __construct()
	{
		parent::__construct('Preinscripción 3');

		$this->addCommands(array(
			new comandos\compilar_recursos(),
			new comandos\user_admin(),
		));
	}

	public function run(InputInterface $input = null, OutputInterface $output = null)
	{
		try {
			$status = parent::run();
			kernel::log()->guardar();
			exit($status);
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::log()->guardar();
		}
	}

}