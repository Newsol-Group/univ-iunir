<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

class version_desactivar_control extends comando_guarani
{
	protected $clase_def = "_info_catalogo";
	protected $info = array();

	protected function configure()
	{
        $this
        ->setName('version:desactivar_control')
		->setDescription('Desactiva el control de versión en g3w')
		->addOption(
               'no-svn',
               null,
               InputOption::VALUE_NONE,
               'Si está seteada no aplica los cambios al svn'
            )
		->setHelp(sprintf(
				'%sDesactiva el control de versión en g3w.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }
	
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$file_version = \siu\bootstrap::get_dir_raiz().'/version';
		if (! $input->getOption('no-svn')) {
			$cmd_svn = "svn remove $file_version --force -q";
			$output->writeln("-- Borrando archivo '$file_version' del svn");
			exec($cmd_svn);
		} else {
			unlink($file_version);
		}
		$ed = new \SIU\Chulupi\util\TobaEditorArchivos();
		$ed->agregar_sustitucion("/define\('CONTROLAR_VERSION_BASE',true\);/", "define('CONTROLAR_VERSION_BASE',false);");
		$ed->procesar_archivo(\siu\bootstrap::get_dir_siu().'/bootstrap.php');
		$output->writeln('<fg=green>Se desactivó el chequeo de versión exitosamente</fg=green>');
    }
}