<?php
namespace siu\util\consola;

use Symfony\Component\Console\Application,
	siu\util\consola\comandos;
use SIU\Chulupi\kernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class gadmin extends Application {

	public function __construct() {
    	parent::__construct('Guarani Web', '1.0');
    	
    	$this->addCommands(array(
			new comandos\cache(),
			new comandos\generar_catalogo(),
			new comandos\generar_puntos(),
			new comandos\casual(),
			new comandos\importar_fotos(),
			new comandos\datos_test(),
			new comandos\info(),
			new comandos\generar_reporte(),
			new comandos\generar_escalas_js(),
            new comandos\compilar_recursos(),
            new comandos\log(),
            new comandos\test_conexion(),
			new comandos\generar_autoload(),
			new comandos\limpiar_cache(),
			new comandos\version_activar_control(),
			new comandos\version_desactivar_control(),
            //new comandos\enviar_emails()
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

