<?php

namespace siu\util\consola\comandos;

use siu\preinscripcion;
use Symfony\Component\Console;

class user_admin extends comando_preinscripcion
{
	/** @var Console\Input\InputInterface */
	protected $input;
	/** @var Console\Output\OutputInterface */
	protected $output;
	
	protected function configure()
	{
		$this
			->setName('user_admin')
			->setDescription('Genera un usuario administrador')
			->setHelp(sprintf(
				'%sGenera un usuario administrador.%s',
				PHP_EOL,
				PHP_EOL
			));
	}	
	
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;
		
		$this->bootstrap_kernel();
		
		$helper = $this->getHelper('question');
		
		// Nombre de usuario
		do {
			$question_username = new Console\Question\Question('Ingrese el nombre del usuario administrador (ENTER asume "admin"): ', 'admin');
			$username = $helper->ask($input, $output, $question_username);
		} while (!$this->usuario_valido($username));
		
		// Clave
		do {
			$question_password = new Console\Question\Question('Ingrese el PASSWORD del usuario administrador: ');
			$password = $helper->ask($input, $output, $question_password);
		} while (!$this->clave_valida($username, $password));
		
		// ID pto de acceso
		do {
			$question_id_pto_acceso = new Console\Question\Question('Ingrese el ID DE PUNTO DE ACCESO en el que operara del usuario administrador (ante la duda, verificar el valor de "SetEnv ID_PTO_ACCESO" en alias.conf): ');
			$id_pto_acceso = $helper->ask($input, $output, $question_id_pto_acceso);
		} while (empty($id_pto_acceso));
		
		// Se genera usuario
		preinscripcion::admin()->crear_usuario($username, $password, $id_pto_acceso);
		
		// Mensajes finales
		$output->writeln("---------------------------------------------------------");
		$output->writeln("Se ha creado el siguiente usuario administrador:");
		$output->writeln(" - username: " . $username);
		$output->writeln(" - password: " . $password);
		$output->writeln(" - ID_PTO_ACCESO: " . $id_pto_acceso);
		$output->writeln("---------------------------------------------------------");
	}
	
	protected function usuario_valido($username)
	{
		try {
			preinscripcion::admin()->usuario_valido($username);
		} catch (\siu\errores\error_preinscripcion_usuario $ex) {
			$this->output->writeln("<error>" . $ex->get_mensaje() . "</error>");
			$this->output->writeln("");
			return false;
		}
		return true;
	}
	
	protected function clave_valida($username, $password)
	{
		try {
			preinscripcion::admin()->clave_valida($username, $password);
		} catch (\siu\errores\error_preinscripcion_clave $ex) {
			$this->output->writeln("<error>" . $ex->get_mensaje() . "</error>");
			$this->output->writeln("");
			return false;
		}
		return true;
	}

}
?>
