<?php
namespace siu\util\consola\comandos;

use Symfony\Component\Console\Input\InputArgument,
	Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

class version_activar_control extends comando_guarani
{
	protected $clase_def = "_info_catalogo";
	protected $info = array();

	protected function configure()
	{
        $this
        ->setName('version:activar_control')
		->setDescription('Activa el control de versión en g3w')
		->addArgument(
			'version',
			InputArgument::REQUIRED,
			'La versión de la base que hay que controlar. <nro version app>-<ultimo script corrido> Ej: <options=bold>3.5.0-000329</options=bold>')
		->addOption(
               'no-svn',
               null,
               InputOption::VALUE_NONE,
               'Si está seteada no aplica los cambios al svn'
            )
		->setHelp(sprintf(
				'%sActiva el control de versión en g3w.%s',
				PHP_EOL, 
				PHP_EOL
		));
    }
	
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$version = $input->getArgument('version');
		if (! $this->check_format($version, $output)) {
			return;
		}
		$file_version = \siu\bootstrap::get_dir_raiz().'/version';
		file_put_contents($file_version, trim($version));
		
		if (! $input->getOption('no-svn')) {
			$cmd_svn = "svn add $file_version -q";
			$output->writeln("-- Agregando archivo '$file_version' al svn");
			exec($cmd_svn);
		}
		
		$ed = new \SIU\Chulupi\util\TobaEditorArchivos();
		$ed->agregar_sustitucion("/define\('CONTROLAR_VERSION_BASE',false\);/", "define('CONTROLAR_VERSION_BASE',true);");
		$ed->procesar_archivo(\siu\bootstrap::get_dir_siu().'/bootstrap.php');
		$output->writeln('<fg=green>Se activó el chequeo de versión exitosamente</fg=green>');
    }

	protected function check_format($version, $output)
	{
		$error_formato = '<fg=red>El formato de la versión es incorrecto.</fg=red> <nro version app>-<ultimo script corrido> Ej: <options=bold>3.5.0-000329</options=bold>';
		$res = explode('-', $version);
		if (!isset($res[0]) || !isset($res[1])) {
			$output->writeln($error_formato);
			return false;
		}
		
		$res = explode('.', $res[0]);
		if (!isset($res[0]) || !isset($res[1]) || !isset($res[2])) {
			$output->writeln($error_formato);
			return false;
		}
		
		if (!is_numeric($res[0]) || !is_numeric($res[1]) || !is_numeric($res[2])) {
			$output->writeln($error_formato);
			return false;
		}
		return true;
	}
	
	//----------------------------------------------------------

	

}