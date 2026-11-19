<?php
namespace siu\util\consola\comandos;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\DJJob;
use SIU\Chulupi\util\DJWorker;
use Symfony\Component\Console;


class enviar_emails extends comando_guarani
{

    protected function configure()
    {
        $this
            ->setName('enviar_emails')
            ->setDescription('Envia todos los emails encolados')
            ->setHelp(sprintf(
                '%sEnvia todos los emails encolados.%s',
                PHP_EOL,
                PHP_EOL
            ));
    }

    //https://github.com/seatgeek/djjob
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {

        $this->bootstrap_kernel();

        $output->writeln('<comment>El proceso que envia los emails encolados ha sido disparado...</comment>');

        //Traigo todos los bloques de acceso
        $accesos = kernel::proyecto()->get_accesos();

        foreach($accesos as $key => $acceso){

            $this->bootstrap_kernel($key);

            $output->writeln("<comment>Enviando los emails encolados para la Unidad de Gestión: '{$acceso['ua']}'...</comment>");

            $cant_emails_a_enviar_por_corrida_cron = kernel::proyecto()->get_cant_emails_a_enviar_por_corrida_cron();

            $bloque_acceso = $acceso['database'];
            $pdo = new \PDO("pgsql:host={$bloque_acceso['host']};port={$bloque_acceso['port']};dbname={$bloque_acceso['dbname']};user={$bloque_acceso['pdo_user']};password={$bloque_acceso['pdo_passwd']}");

            //Seteo encoding
            $encoding = kernel::proyecto()->get_encoding();
            $encoding = $pdo->quote($encoding);
            $pdo->exec("SET CLIENT_ENCODING TO $encoding");

            //Seteo esquema
            $pdo->exec("SET search_path TO {$bloque_acceso['schema']}");

            DJJob::setConnection($pdo);
            DJJob::setJobsTable('jobs');
            $worker = new DJWorker(array(
                'queue' => 'email',
                "run_forever" => false,
                "count" => $cant_emails_a_enviar_por_corrida_cron
                ));
            $worker->start();
        }

        $output->writeln("<comment>Fin del proceso de envío de emails encolados, ante cualquier inconveniente verifique la tabla jobs de la base de datos.</comment>");

    }

}