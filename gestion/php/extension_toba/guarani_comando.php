<?php

require_once(dirname(__FILE__) . '/../guarani_autoload.php');
require_once(dirname(__FILE__) . '/../../personalizacion/php/guarani_pers_autoload.php');
require_once(dirname(__FILE__) . '/../../personalizacion/php/guarani_autoload_clases_nuevas.php');

class guarani_comando extends toba_aplicacion_comando_base
{

        /*
         * https://www.php.net/manual/es/function.spl-autoload-register.php
         * https://www.php.net/manual/es/language.types.callable.php
         */
        function __construct()
        {
            spl_autoload_register(['guarani_autoload', 'cargar']);
            spl_autoload_register(['guarani_pers_autoload', 'cargar']);
            spl_autoload_register(['guarani_autoload_clases_nuevas', 'cargar']);

            // Ver como hace en php/extension_toba/contexto_ejecucion_guarani.php
            guarani::cargar_fabrica();
        }
        
	/**
	* Genera los archivos de cada elemento de la base que se encuentran el en archivo de generación en las carpetas correspondientes
	*/
	function opcion__parsear($parametros)
	{
		$this->modelo->parsear_sql_base($parametros);
	}

	/**
	* Crea las funciones de pgcrypto en las versiones 8.3 y 8.4 de Postgres en el esquema 'public'
	*/
	function opcion__aplicar_pgcrypto($parametros)
	{
		$this->modelo->aplicar_pgcrypto();
	}
        
	/**
	* Cambia el esquema de la BD de negocios de 'public' al esquema indicado (Si no se indica esquema, se utiliza 'negocio' por defecto)
	* @consola_parametros [-e esquema]
	*/
	function opcion__cambiar_esquema($parametros)
	{
		if (! array_key_exists('-e',$parametros)) {
                        $esquema = 'negocio';
                } else {
                        $esquema = $parametros['-e'];
                }
		
                $this->modelo->cambiar_esquema($esquema);
        }		
        
        
	/**
	* Migra la base de negocio a la versión que corresponde a la aplicación, o a la del modificador -h
	*/   
	function opcion__migrar_base($parametros)
	{
		$desde = $this->modelo->get_version_app_base();
		if (!$desde) {
			$this->manejador_interface->mensaje('No se pudo determinar la versión de la base del proyecto local.', true);
			exit;
		} else {
			$desde = new toba_version($desde);
		}
		if (! array_key_exists('-h',$parametros)) {
			$hasta = $this->modelo->get_version_actual();
		} else {
			$hasta = $parametros['-h'];
			$hasta = new toba_version($hasta);
		}	
		if (!$hasta) {
			$this->manejador_interface->mensaje('No se pudo determinar la versión del proyecto local.', true);
			exit;
		}
		$this->modelo->migrar_base($desde, $hasta);
	}

	/**
	* Brinda informacion de la version del sistema y las ultimas actualizaciones realizadas
	*/
	function opcion__info_version($parametros)
	{
		$this->modelo->info_version();
	}

	/**
	* Resguarda la BD en el directorio temp de la carpeta BD del proyecto bajo el nombre de archivo de datos_locales_SUFIJO.sql Si no se especifica [-s SUFIJO], entonces SUFIJO = Nro. de Version actual
	*/   
	function opcion__resguardar_base($parametros)
	{
            
		if (! array_key_exists('-s',$parametros)) {
			$v = $this->modelo->get_version_actual();
		} else {
			$v = $parametros['-s'];
		}	
		if (!$v) {
			$this->manejador_interface->mensaje('No se pudo determinar la versión del proyecto local.', true);
			exit;
		}
		$this->modelo->exportar_base($v);
	}
        
	/**
	 *  Dada una revision, actualiza el proyecto, y convierte la base de negocio a esa revision   
	 *  -r 'revision'
	 */
	
	function opcion__cargar_revision ($parametros)
	{
		if (isset($parametros["-r"]) && is_numeric($parametros["-r"])) {
			$revision = $parametros["-r"];
			$this->modelo->cargar_revision($revision);
		} else {
			$this->modelo->cargar_revision();
		}
		
	}
  

	/**
	 *  Dada una revision, actualiza la BD de negocio a esa revision   
	 *  -r 'revision'
	 */
	
	function opcion__actualizar_bd ($parametros)
	{
		if (isset($parametros["-v"]) && is_numeric($parametros["-v"])) {
			$v = $parametros["-v"];
			$this->modelo->actualizar_bd('HEAD',$v);
                        return;
		}
		if (isset($parametros["-r"]) && is_numeric($parametros["-r"])) {
			$revision = $parametros["-r"];
			$this->modelo->actualizar_bd($revision);
		} else {
			$this->modelo->actualizar_bd();
		}
		
	}

	/**
	* Exporta los datos locales de la instancia a la que pertenece el proyecto 
	*/   
	function opcion__instancia_exp_local($parametros)
	{            
		$this->modelo->instancia_exp_local();
	}
	
	/**
	 *  Migrar encriptacion de claves
	 */
	function opcion__migrar_claves ($parametros)
	{
        	$this->modelo->migrar_claves();
	}
	
 	 
	function opcion__foto_base()
	{
		$this->manejador_interface->mensaje('Generando una foto de la base actual en "'.$this->modelo->get_dir('tmp').'"');
		// Reviso si existe el directorio /tmp en la carpeta de la base del proyecto
		if(!file_exists($this->modelo->get_dir('tmp')))
			mkdir($this->modelo->get_dir('tmp'));

		$this->manejador_interface->mensaje('Obteniendo tablas del modelo ',false);
		// Recupero las tablas del modelo
		$this->manejador_interface->progreso_avanzar();
		$tablas = $this->modelo->get_tablas();
		$this->manejador_interface->progreso_fin();
		
		$this->manejador_interface->mensaje('Generando archivos de datos de las tablas',false);
		// Para cada tabla, exporto los datos en un archivo
		foreach($tablas as $tabla){
			// Exporto los datos
			$this->manejador_interface->progreso_avanzar();
			$archivo = $this->modelo->get_dir('tmp').'/'.$tabla['nombre'].'.old';
			$this->modelo->exportar_tabla_inserts($tabla['nombre'],$archivo);
		}
		$this->manejador_interface->progreso_fin();
	}


	/**
	 * Compara una foto anterior de la base con el estado actual
	 */
	function opcion__compara_foto_base()
	{
		$this->manejador_interface->mensaje('Comparando el estado actual de la base con la última foto generada en "'.$this->modelo->get_dir('tmp').'"');	
		$this->manejador_interface->mensaje('Obteniendo tablas del modelo ',false);
		// Recupero las tablas del modelo
		$this->manejador_interface->progreso_avanzar();
		$tablas = $this->modelo->get_tablas();
		$this->manejador_interface->progreso_fin();
	
		$this->manejador_interface->mensaje('Comparando datos actuales con los de la foto ',false);
		// abro el achivo donde van los resultados				
		$archivo_salida = $this->modelo->get_dir('log');
		$salida = fopen($archivo_salida,'w');
			
			
		$mostrar = '';
		
		// Para cada tabla hago la comparación
		foreach($tablas as $tabla){
			$this->manejador_interface->progreso_avanzar();
			
			// datos viejos: levanto del archivo y ordeno
			$archivo_old = $this->modelo->get_dir('tmp').'/'.$tabla['nombre'].'.old';
			$cont = file_get_contents($archivo_old);
			$datos_old = explode("\n",$cont);
			sort($datos_old);
			
			// datos nuevos, recupero y ordeno
			$datos = $this->modelo->exportar_tabla_inserts($tabla['nombre']);
			sort($datos);

			// Calculo la diferencia y muestro
			$diff = diff::PHPDiff($datos_old,$datos);

			if(guarani_arreglos::count($diff['I']) || guarani_arreglos::count($diff['D']) || guarani_arreglos::count($diff['U'])) {
				$linea = "==============================================\nTabla: {$tabla['nombre']}\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}
			
			if(guarani_arreglos::count($diff['I'])) {
				$linea = "----------------------------------------------\nInserts:\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}
			
			foreach($diff['I'] as $s){
				$linea = $s."\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}

			if(guarani_arreglos::count($diff['D'])) {
				$linea = "----------------------------------------------\nDeletes:\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}
			
			foreach($diff['D'] as $s){
				$linea = $s."\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}

			if(guarani_arreglos::count($diff['U'])) {
				$linea = "----------------------------------------------\nUpdates:\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}
			
			foreach($diff['U'] as $s){
				$linea = "_____\n".$s['D']."\n".$s['I']."\n_____\n";
				$mostrar .= $linea;
				fputs($salida,$linea);
			}
		}
		fclose($salida);
		
		$this->manejador_interface->progreso_fin();
		
		if ($mostrar != '') {
			$this->manejador_interface->mensaje('Resultado: ');
			$this->manejador_interface->mensaje($mostrar);
			$this->manejador_interface->mensaje('Los resultados quedaron guardados en "'.$this->modelo->get_dir('log').'"');	
		} else {
			$this->manejador_interface->mensaje('No se encontraron diferencias');	
		}
	}
	
	/**
	 * Genera la documentación del API en base a los tags phpdoc del código
	 */
	function opcion__doc_php()
	{
		$dir_base = $this->modelo->get_proyecto()->get_dir();
		$dest = $dir_base.'/doc/api';
		toba_manejador_archivos::crear_arbol_directorios($dest);

		$lista = toba_manejador_archivos::get_archivos_directorio($dest, "/\\.html/", true);
		foreach ($lista as $arch) {
			unlink($arch);
		}
		
		//--- Se incluye a phpdocumentor en el path
		$dir = toba_dir()."/php/3ros";
		$separador = (substr(PHP_OS, 0, 3) == 'WIN') ? ";.;" : ":.:";
		ini_set("include_path", ini_get("include_path"). $separador . $dir);
		
		global $_phpDocumentor_setting;
		$_phpDocumentor_setting['title'] = "API PHP";
		$_phpDocumentor_setting['directory'] = $dir_base . '/php/nucleo';
		$_phpDocumentor_setting['target'] = $dest;
		$_phpDocumentor_setting['output'] = "HTML:Smarty:toba_hands";
		$_phpDocumentor_setting['defaultpackagename'] = 'Centrales';
		$_phpDocumentor_setting['customtags'] = 'jsdoc,wiki,ventana';
		//$_phpDocumentor_setting['output'] = "HTML:frames:DOM/toba";
		$_phpDocumentor_setting['undocumentedelements'] = 1;
		require_once("PhpDocumentor/phpDocumentor/phpdoc.inc");

		//-- La clase toba es la clase inicial (como esta un nivel mas adentro hay que bajar un nivel menos)
		$indice = file_get_contents($dest.'/Centrales/guarani.html');
		$indice = str_replace('../../', '#BASE#', $indice);
		$indice = str_replace('../', '', $indice);
		$indice = str_replace('#BASE#', '../', $indice);
		file_put_contents($dest.'/index.html', $indice);
		
		//---Borra las ayudas a nivel global de los archivos
		$archivos = toba_manejador_archivos::get_archivos_directorio($dest, "/.*---.*\\.html/", true);
		foreach ($archivos as $arch) {
			unlink($arch);
		}
				
		$this->convertir_codificacion_dir($dest, "ISO-8859-1", "UTF-8");		
	}

	protected function convertir_codificacion($archivo, $desde, $hasta)
	{	
		$this->manejador_interface->progreso_avanzar();
		$utf8 = file_get_contents($archivo);
		$iso = iconv($desde, $hasta, $utf8);
		file_put_contents($archivo, $iso);
	}

	
	protected function convertir_codificacion_dir($destino, $desde="UTF-8", $hasta="ISO-8859-1")
	{
		//Se buscan los archivos .html del arbol de directorios
		$archivos = toba_manejador_archivos::get_archivos_directorio($destino, "/\\.html/", true);
		$cant = guarani_arreglos::count($archivos);
		$this->manejador_interface->mensaje("Convirtiendo $cant archivos de codificacion $desde a $hasta:");		
		foreach ($archivos as $archivo) {
			$this->convertir_codificacion($archivo, $desde, $hasta);
		}
		$this->manejador_interface->mensaje("Fin conversión");
	}		

// --------------------------------------------------------------------------------------------------------------------
// ----------------          PERSONALIZACIONES
// --------------------------------------------------------------------------------------------------------------------	

	/**
	 * Regenera el autoload del proyecto, autoload de clases huecas, autoload para consumo 3w y clases de consulta. Ejecutar cuando se crean nuevas claseee.
	 */
	
	function opcion__autoload($parametros) 
	{
		$this->modelo->generar_autoloads($parametros);
	}
	
	/**
	 * Regenera el autoload de las clases nuevas creadas por las universidades, definicion de las clases de consultas personalizadas.  Ejecutar después de personalizar.
	 */
	
	function opcion__pers_autoload($parametros) 
	{
		$this->modelo->generar_autoload_clases_nuevas($parametros);
	}
	
    /**
     * Prepara guarani para comenzar a ser personalizado
	function opcion__activar_pers($parametros)
	{
		$this->manejador_interface->mensaje('Preparando esquema de personalizaciones. Este proceso puede tardar varios minutos...');
		$p = $this->modelo->get_proyecto();
		$pers = new toba_personalizacion($p);
		$pers->iniciar();
		$this->modelo->setear_pers(true, $pers->get_schema_original(), $pers->get_schema_personalizacion());
		$this->manejador_interface->mensaje('Esquema de personalizaciones activado');
	}

	 // Desactiva el esquema de personalizaciones en guarani
	function opcion__desactivar_pers($parametros)
	{
		$p = $this->modelo->get_proyecto();
		$pers = new toba_personalizacion($p);
		$pers->desactivar();
		$this->modelo->setear_pers(false, $pers->get_schema_original(), $pers->get_schema_personalizacion());
		$this->manejador_interface->mensaje('Esquema de personalizaciones desactivado');
	}
	*/
	
	/**
	* Publica los recursos personalizados del proyecto
	*/
	function opcion__publicar_pers($parametros)
	{
		$this->modelo->publicar_recursos_pers();
		$this->manejador_interface->mensaje('OK. Debe reiniciar el servidor web para que los cambios tengan efecto.');		
	}
	
        
    /**
     * Baja la base anonimizada para poder ser enviada para el siu por algún problema puntual. Por defecto toda la base.
	 * -t => Dumpea y anonimiza toda la base de datos.
	 * -d => Escala de Debug: 0 al 9
     */
    function opcion__backup_anonimizado($parametros) 
    {
        $v = $this->modelo->get_version_actual();
        $this->modelo->preparar_base_a_anonimizar($v, $parametros);
    }
    
    /**
     * Chequea la configuracion de la instalacion (php, postgres, apache)
     */
    function opcion__chequear_configuracion()
    {
        $this->modelo->check_configuracion();
    }
	
	/**
	 * Notifica a sube los cambios en las solicitudes. Servicio sube: benefit-update.
	 * 
	 * Opcional: [--solo-bajas] Si dicha opción esta presente solo procesa bajas del beneficio de Boleto Estudiantil
	 */
	function opcion__notif_solicitudes_sube($parametros)
	{
		$this->modelo->notificar_sube($parametros);
	}
	
	/**
	 * Procesa el cumplimiento de las condiciones para las solicitudes existentes 
	 * (beneficio SUBE) y marca para actualizar las que tengan cambios.
	 * 
	 * Opcional: [--solo-bajas] Si dicha opción esta presente solo procesa bajas del beneficio de Boleto Estudiantil
	 */
	function opcion__pro_solicitudes_sube($parametros)
	{
		$this->modelo->procesar_sube($parametros);
	}
	
	/**
	 * Envia los emails encolados por el notificador
	 */
	function opcion__enviar_emails_notificador($parametros)
	{
		$this->modelo->enviar_emails_notificador($parametros);
	}
// --------------------------------------------------------------------------------------------------------------------
	
	/**
	 * Habilita el registro de los cambios de datos censales en las tablas historicas.
	 */
	function opcion__habilitar_hist_dc()
	{
		$this->modelo->habilitar_historico_datos_censales();
	}
	
	/**
	 * Deshabilita el registro de los cambios de datos censales en las tablas historicas.
	 * No elimina los datos existentes.
	 */
	function opcion__deshabilitar_hist_dc()
	{
		$this->modelo->deshabilitar_historico_datos_censales();
	}
	
	/**
	 * Crea o actualiza el esquema de auditoria sobre las tablas del negocio
	 * @consola_parametros Opcional: [-f] fuente [-s] Lista de schemas incluidos separada por coma [--force 1] Fuerza eliminacion de todos los triggers
	 */
	function opcion__crear_auditoria($parametros)
	{
		$fuerza_eliminacion_triggers = (isset($parametros['--force']) && ($parametros['--force'] == 1));
		// Si se fuerza la eliminación de triggers...
		if ($fuerza_eliminacion_triggers) {
			// Es necesario habilitar aquellos que están desactivados, caso contrario no se van a poder eliminar (sólo se eliminan los activos).
			$this->modelo->habilitar_triggers_auditoria_desactivados();
		}
		parent::opcion__crear_auditoria($parametros);
		$this->modelo->deshabilitar_triggers_auditoria_selectivo();
	}
	
	/**
	 * Genera el schema usado por rest-hooks
	 */
	function opcion__resthooks_crear_base($parametros)
	{
		$this->modelo->resthooks_crear_base($parametros);
	}
	
	/**
	 * Inicia los workers de rest-hooks
	 */
	function opcion__resthooks_iniciar_workers($parametros)
	{
		$this->modelo->resthooks_iniciar_workers($parametros);
	}
	
	/**
	 * Arma archivo JSON con las personas y cuentas para importar en arai-usuarios
	 *
	 * @param array $parametros
	 * 		array(
	 * 			-d => 'Path donde se guarda el archivo, por defecto -> $this->get_instalacion()->get_dir()/usersExportFiles/,
	 *			-f => Nombre del archivo donde se exportaran los usuarios, por defecto -> usuarios_ . date('YmdHis'),
	 *			-m => Nombre del responsable, por defecto -> toba,
	 *			-e => Mails del responsable,  por defecto -> toba@siu.edu.ar
	 * 		)
	 * @throws Exception
	 */
	function opcion__exportar_usuarios_arai($parametros)
        {
            // Si en los parámetros viene -c, sino --clave, si no vienen ninguna de las dos seteo null
            $clave = $parametros['-c'] ?? $parametros['--clave'] ?? null;
            define('EXPORTAR_USUARIOS_ARAI_CLAVE', $clave);

            // Si en los parámetros viene -r, sino --resolver-emails-repetidos
            $resolver_emails_repetidos = isset($parametros['-r']) || isset($parametros['--resolver-emails-repetidos']);
            define('EXPORTAR_USUARIOS_ARAI_RESOLVER_EMAILS_REPETIDOS', $resolver_emails_repetidos);
            
            parent::opcion__exportar_usuarios_arai($parametros);
        }

        /**
	 * Vincula personas de Guaraní con usuarios existentes en Moodle (haciendo un mapeo según los campos especificados)
         * 	 
         * @param array $parametros
	 * 		array(
	 *			-c / --cantidad => Cantidad paginado (Cantidad con la cual se paginará los usuarios a vincular), por defecto -> 500,
	 *			-m / --moodle => Plataforma [Plataforma Moodle], si no se pasa es interactivo,
	 *			-v / --vincular => Campos elegidos para la vinculación,(valores posibles combinables username - fullname - email - all [siempre separados por , ]) si no se pasa o hay valores erroneos es interactivo,
	 *			-s => Si incluye usuarios suspendidos, por defecto no los incluye
	 * 		)
	 */
	function opcion__vincular_usuarios_moodle($parametros)
	{            
		$this->modelo->vincular_usuarios_moodle($parametros);
	}

	/**
	 * Crea la tabla para encolar los documentos creados en SIU-Araí
	 */
	function opcion__crear_cola_documentos_arai($parametros)
	{
		$this->modelo->crear_cola_documentos_arai($parametros);
	}

	/**
	 * Sincroniza los documentos creados en SIU-Araí
	 */
	function opcion__sincronizar_documentos_arai($parametros)
	{
		$this->modelo->sincronizar_documentos_arai($parametros);
	}
	
	/**
	 * Notifica a los usuarios administradores sobre distintos eventos
	 */
	function opcion__notificar_administradores($parametros)
	{
		$this->modelo->notificar_administradores($parametros);
	}

	 
	/** 
	 * Exporta csv de alumnos y docentes de Guaraní a una plataforma de Moodle especificada
	 *
	 * @param array $parametros
	 * 		array(
  	 *		-c / --cantidad => Cantidad paginado [Cantidad con la cual se van a paginar los usuarios a exportar], por defecto -> 500,
	 * 		-m / --moodle => Plataforma [Plataforma Moodle], si no se pasa es interactivo)
	 */
	function opcion__exportar_usuarios_moodle($parametros)
	{
		$this->modelo->exportar_usuarios_moodle($parametros);
	}
	
	/**
	 * Vincula los usuarios de Araí con los usuarios de Guaraní.	 
	 */
	function opcion__vincular_usuarios_arai()
	{
		$this->modelo->vincular_usuarios_arai();
	}
	
	/**
	 * Resuelve la eliminación manual de usuarios y cursos en Moodle.
	 * 
	 * Opcional: [--moodle (-m)] Plataforma de Moodle (valor de la columna 'plataforma' en la tabla 'int_pv_plataformas'). 
	 * Por defecto procesa todas las plataformas activas
	 */
	function opcion__resolver_eliminacion_manual_moodle($parametros)
	{
		$this->modelo->resolver_eliminacion_manual_moodle($parametros);
	}
	/** 
     * Actualiza docentes de Comsiones asociadas y vinculadas a una plataforma de Moodle especifica
	 *
	 * @param array $parametros
	 * 		array(
	 * 		-m / --moodle => Plataforma [Plataforma Moodle], si no se pasa es interactivo)
	 */
	function opcion__actualizar_docentes_comision_moodle($parametros)
	{
		$this->modelo->actualizar_docentes_comision_moodle($parametros);
	}
	/** 
	 * Actualiza alumnos de Comsiones asociadas y vinculadas a una plataforma de Moodle especifica
	 *
	 * @param array $parametros
	 * 		array(
	 * 		-m / --moodle => Plataforma [Plataforma Moodle], si no se pasa es interactivo)
	 */
		function opcion__actualizar_alumnos_comision_moodle($parametros)
		{
			$this->modelo->actualizar_alumnos_comision_moodle($parametros);
	}

        /**
	 * Exporta todas las imagenes y reportes a SIU-Arai Reportes.
	 *
	 */
	/*function opcion__exportar_reportes_arai($parametros)
	{
            $this->modelo->exportar_reportes_arai($parametros);
	}*/
}
