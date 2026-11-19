<?php
namespace siu\util\consola\comandos;

use siu\bootstrap;
use siu\modelo_g3\encuesta_3w;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\alumno_foto;
use SIU\Chulupi\util\ManejadorArchivos;

class importar_fotos extends comando_guarani
{
	protected $input; //Console\Input\InputInterface
	protected $output; //Console\Output\OutputInterface
	protected $foto_count = 0;
    protected $ug;

	protected static $IMAGEN_DEFAULT = 'www/img/iconos/mm_medio.png';

	protected function configure()
	{
        $this
        ->setName('importar_fotos')
		->setDescription('Importador de fotos.')
        ->setHelp(sprintf(
                '%sImporta las fotos desde la BD al sistema de archivos del G3W, también las sincroniza con Kolla.%sDebes configurar los parametros <options=bold>imagen_alumno_dir</options=bold> e <options=bold>imagen_alumno_url</options=bold> en el archivo <options=bold>instalacion/config.php</options=bold>%s',
				PHP_EOL,
				PHP_EOL,
                PHP_EOL
		))
        ->addArgument('acceso', InputArgument::REQUIRED, 'ID del bloque de acceso para el cual se quiere importar fotos')
        ->addOption('todas', 't', InputOption::VALUE_NONE, "Si se establece esta opción se importaran todas las fotos incluyendo las que ya fueron importadas")
        ->addOption('no-interactivo', null, InputOption::VALUE_NONE, "Si se establece esta opcion el comando sera no interactivo, con lo cual no debera responder a los mensajes de confirmacion");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
	{

		try{
			
			$acc_id = $input->getArgument('acceso');
			if ($acc_id) {
				$this->bootstrap_kernel($acc_id);
			}

                        $imagen_alumno_dir = kernel::proyecto()->get('imagen_alumno_dir');

                        // Toma la opción --no-interactivo
                        $opcion_no_interactivo = $input->getOption('no-interactivo');
                        $no_interactivo = ($opcion_no_interactivo)? true : false;

                        // Si es no interactivo no le hago responder preguntas
                        if (!$no_interactivo) {

                            $helper = $this->getHelper('question');
                            $question = new ConfirmationQuestion('<question>Las fotos se guardaran en <options=bold>"'.$imagen_alumno_dir.'"</options=bold>. Desea continuar? (y/n)</question>', false);

                            if (!$helper->ask($input, $output, $question)) {
                                    return;
                            }

                        }

                        kernel::log_cli()->debug("=== Configuración 'imagen_alumno_dir' ===");
                        $contexto = [
                                'imagen_alumno_dir' => $imagen_alumno_dir,
                                'archivo' => __FILE__,
                                'linea' => __LINE__,
                        ];
                        kernel::log_cli()->debug(print_r($contexto, true));

			$this->ug = \guarani::param()->get_default('enc_kolla_unidad_gestion');
			kernel::log_cli()->debug("=== Parámetro 'enc_kolla_unidad_gestion' ===");
			$contexto = [
				'enc_kolla_unidad_gestion' => $this->ug,
				'archivo' => __FILE__,
				'linea' => __LINE__,
			];
			kernel::log_cli()->debug(print_r($contexto, true));

			$opcion_todas = $input->getOption('todas');

			$importar_fotos_todas = ($opcion_todas)? true : false;

			$this->crear_imagen_alumno_dir();
			$this->actualizar_url_imagenes($output);
			$this->copiar_imagen_default();

			$this->actualizar_url_imagenes_base_habilitaciones_kolla($output);

			$fotos = $this->get_fotos($importar_fotos_todas);
			kernel::log_cli()->debug('=== $fotos ===');
			kernel::log_cli()->debug(print_r($fotos, true));
			
			$cant_fotos = \guarani_arreglos::count($fotos);
			if ($cant_fotos == 0) {
				$output->writeln("Las fotos se encuentran sincronizadas.");
				return;
			} else {
				$output->writeln("Se van a sincronizar $cant_fotos fotos.");
			}

			$i = 0;
			$elementos_rest = array();

			//Por cada imagen a actualizar...
			foreach ($fotos as $foto) {
				if ($i % 50 == 0) {
					$output->writeln("$i de $cant_fotos fotos procesadas...");
				}
				$i++;
				$formato = strtolower(substr($foto['nombre_imagen'], strrpos($foto['nombre_imagen'], '.') + 1));
				if ($formato == 'bmp') {
					// No se soporta el formato bmp porque se usa GD.
					$output->writeln("No se soporta el formato bmp. Ignorado imagen - ". $foto['nombre_imagen'] . " de persona ". $foto['persona']);
					continue;
				}
				alumno_foto::importar_foto_blob($foto['persona'], $foto['imagen'], $formato);

				//Obtengo el id de la imagen
				$id_imagen = $this->get_id_imagen_por_persona($foto['persona']);
				$id_imagen = ($id_imagen)? $id_imagen : null;
				//Obtengo la URL relativa de la imagen para asociar a los elementos de Kolla.
				$url_relativa_imagen = alumno_foto::url_imagen_relativa_kolla($id_imagen);

				$elementos_kolla = $this->get_elementos_kolla_por_persona($foto['persona']);
				
				//Por cada elemento en Kolla asociado a la persona le cambio la URL de la imagen
				foreach($elementos_kolla as $elemento_kolla){
					$elementos_rest[] = array(
						"elemento" => $elemento_kolla['elemento'],
						"url_img" => $url_relativa_imagen,
						"descripcion" => $elemento_kolla['descripcion']
					);
				}

			}
			$output->writeln("$i de $cant_fotos fotos procesadas...");

			$this->sincronizar_fotos_elementos_kolla($elementos_rest, $output);

			$output->writeln("<info>Proceso finalizado.</info>");
			
		} catch (\Exception $e) {
			
			$output->writeln("<error>Falló la importación de fotos.</error>");
			
			kernel::log_cli()->error('=== Falló el comando "importar_fotos" ===');
			$contexto = [
				'exception_codigo' => $e->getCode(),
				'exception_mensaje' => $e->getMessage(),
				'exception_archivo' => $e->getFile(),
				'exception_linea' => $e->getLine(),
			];
			kernel::log_cli()->error(print_r($contexto, true));
			
		}
    }

	protected function get_fotos($importar_fotos_todas = false)
	{
		$sql = "SELECT	persona,
						imagen,
						nombre_imagen
				FROM	mdp_personas_foto
				WHERE	nombre_imagen IS NOT NULL";

        if(!$importar_fotos_todas) $sql .= " AND sincronizada = 0";

		return kernel::db()->consultar($sql);
	}

    protected function get_id_imagen_por_persona($persona)
    {
        $persona = kernel::db()->quote($persona);

        $sql = "SELECT id_imagen
                FROM mdp_personas
                WHERE persona = $persona";

        $fila = kernel::db()->consultar_fila($sql);
        return $fila['id_imagen'];
    }

    protected function get_elementos_kolla_por_persona($persona)
    {
        $persona = kernel::db()->quote($persona);
        $si = kernel::db()->quote(\comunes::si);
        $activo = kernel::db()->quote(\comunes::estado_activo);
		
		// Para el caso de encuestas migradas desde Guarani 2, debe devolverse el dato "elemento_g2"
        $sql = "SELECT	COALESCE(i.elemento_g2, i.item) AS elemento, i.titulo AS descripcion
                FROM sga_docentes as d
                JOIN gde_items as i ON (d.docente = i.docente)
                WHERE i.estado = $activo
                  AND i.generado_en_kolla = $si
                  AND d.persona = $persona
				";

        return kernel::db()->consultar($sql);
    }

    protected function get_habilitaciones_kolla()
    {
        $sql = "SELECT kolla_id_habilitacion
                FROM gde_habilitaciones
                WHERE kolla_sincronizado = 'S'
                AND kolla_id_habilitacion IS NOT NULL";

        return kernel::db()->consultar($sql);
    }

	protected function actualizar_url_imagenes($output){
		$dir = kernel::proyecto()->get('imagen_alumno_url');
		$url_fotos = kernel::db()->quote($dir);

		$sql = "UPDATE par_configuraciones SET valor = $url_fotos WHERE nombre = 'url_fotos' AND valor <> $url_fotos";
		$actualizado = kernel::db()->ejecutar($sql);

		if ($actualizado) {
			$output->writeln("URL base de las fotos actualizada a [$dir].");
		} else {
			$output->writeln("La URL base de las fotos se encuentra actualizada [$dir].");
		}
	}

	protected function crear_imagen_alumno_dir(){
		$imagen_alumno_dir = kernel::proyecto()->get('imagen_alumno_dir');
		ManejadorArchivos::crear_arbol_directorios($imagen_alumno_dir);
	}
	
	protected function copiar_imagen_default(){
		$origen = bootstrap::get_dir_siu(). "/" .self::$IMAGEN_DEFAULT;
		$destino = kernel::proyecto()->get('imagen_alumno_dir') . "/" . basename($origen);
		copy($origen, $destino);
	}

    /*
     * Actualiza la "url_imagenes_base" de cada habilitación en Kolla
     */
    protected function actualizar_url_imagenes_base_habilitaciones_kolla($output){

        $url_base_imagenes = kernel::proyecto()->get('imagen_alumno_url');
        $habilitaciones_kolla = $this->get_habilitaciones_kolla();
        $cant_habilitaciones = \guarani_arreglos::count($habilitaciones_kolla);

        if(!empty($habilitaciones_kolla)){
            $output->writeln("<comment>Actualizando la 'url_imagenes_base' de las habilitaciones en Kolla ({$cant_habilitaciones} en total)...</comment>");

            $cant_habilitaciones_exitosas = 0;
            $cant_habilitaciones_fallidas = 0;

            foreach($habilitaciones_kolla as $habilitacion_kolla){

                $kolla_id_habilitacion = $habilitacion_kolla['kolla_id_habilitacion'];

                $datos_habilitacion = array(
                    "habilitacion" => $kolla_id_habilitacion,
                    "url_imagenes_base" => $url_base_imagenes
                );

                try {
					
                    encuesta_3w::actualizar_habilitacion($datos_habilitacion, $this->ug);
                    $output->writeln("<info>Actualización de la habilitación '{$kolla_id_habilitacion}' exitosa.</info>");
                    $cant_habilitaciones_exitosas++;
					
                } catch (\Exception $e) {
					
                    $output->writeln("<error>Actualización de la habilitación '{$kolla_id_habilitacion}' fallida.</error>");
					$cant_habilitaciones_fallidas++;

					kernel::log_cli()->error("=== Falló la actualización de la habilitación '{$kolla_id_habilitacion}' en el comando 'importar_fotos' ===");
					$contexto = [
						'habilitacion' => $kolla_id_habilitacion,
						'datos_habilitacion' => $datos_habilitacion,
						'ug' => $this->ug,
						'exception_codigo' => $e->getCode(),
						'exception_mensaje' => $e->getMessage(),
						'exception_archivo' => $e->getFile(),
						'exception_linea' => $e->getLine(),
					];
					kernel::log_cli()->error(print_r($contexto, true));
					
				}
            }
			
            $output->writeln("<comment>Se actualizaron exitosamente {$cant_habilitaciones_exitosas} habilitaciones, fallaron {$cant_habilitaciones_fallidas}!!!</comment>");
			
        }
    }


    /*
     * Sincroniza las fotos de los elementos en Kolla
     */
    protected function sincronizar_fotos_elementos_kolla($elementos_kolla, $output){

        if(!empty($elementos_kolla)){
			
            $output->writeln("<comment>Sincronizando fotos con Kolla (sincronización masiva de elementos)...</comment>");

            try {
				
                $resultado_rest = encuesta_3w::actualizar_masivo($elementos_kolla, 'elementos', $this->ug);

                if (empty($resultado_rest)) { // Si sale bien la actualización masiva de elementos
					
                    $output->writeln("<info>Sincronización masiva de elementos exitosa...</info>");
					
                } else{ //si hay errores
					
                    $output->writeln("<error>Falló la sincronización masiva de elementos con Kolla.</error>");
					
					kernel::log_cli()->error("=== Falló la sincronización masiva de elementos con Kolla ===");
					$contexto = [
						'elementos' => $elementos_kolla,
						'ug' => $this->ug,
						'resultado_rest' => $resultado_rest,
						'archivo' => __FILE__,
						'linea' => __LINE__,
					];
					kernel::log_cli()->error(print_r($contexto, true));
					
				}

            } catch (\Exception $e) {//Si lanza una excepcion
				
                $output->writeln("<error>Falló la sincronización masiva de elementos con Kolla.</error>");

				kernel::log_cli()->error("=== Falló la sincronización masiva de elementos con Kolla ===");
				$contexto = [
					'elementos' => $elementos_kolla,
					'ug' => $this->ug,
					'exception_codigo' => $e->getCode(),
					'exception_mensaje' => $e->getMessage(),
					'exception_archivo' => $e->getFile(),
					'exception_linea' => $e->getLine(),
				];
				kernel::log_cli()->error(print_r($contexto, true));
				
			}
			
        }

    }

}