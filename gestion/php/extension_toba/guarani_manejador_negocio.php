<?php
class guarani_manejador_negocio extends manejador_negocio
{
	// Schema donde se generará la BD de negocios
	protected $schema;
	// Clase de configuracion de parametros y datos de la BD de negocios
	protected $conf;
	// Booleano que indica si la carga de los juegos de datos es acumulativa
	protected $incluir_juegos_anteriores;
	
	protected $build;

	function __construct($conexion, $logger, $definicion_base, $path_proyecto)
	{
		parent::__construct($conexion, $logger, $definicion_base, $path_proyecto);

		$this->schema = inst::configuracion()->get('base', 'schema');
		$this->build = 0;
		// Por defecto se setea que se incluyan acumulativamente por 'orden' los juegos de datos
		$this->incluir_juegos_anteriores = true;
	}
	
	/**
	 * Crear la base/s de negocio durante la instalación del sistema 
	 * @param $version Versión del sistema que se está instalando
	 * @param $grupo_datos Grupo de datos seleccionado durante la instalación
	 */
	function crear_negocio($version, $grupo_datos)
	{
		//Carga la clase con la configuracion de los directorios y archivos para la generacion de la BD
		$this->cargar_conf();
		
		//Crea la estructura de la base y carga los datos del nucleo y los de prueba seleccionados
		$this->aplicar_pgcrypto();
		$this->crear_estructura_predatos();
		$this->cargar_datos($grupo_datos);
		$this->crear_estructura_posdatos();
		$this->insertar_version_app_base($version);
		$this->logger->debug('La BD de negocios se creó correctamente');		
	}	
	
	/**
	 * Migrar la bases/s de negocio. Puede ser tanto durante la instalación inicial (ya sea con una base nueva o existente) como la actualización
	 * @param $version Versión del sistema que se está instalando
	 * @param $es_base_nueva Indica si la migración es sobre una base de datos existente o sobre una nueva
	 */
	function migrar_negocio($version, $es_base_nueva)
	{
		if(!$es_base_nueva){

			//Carga la clase con la configuracion de los directorios y archivos para la generacion de la BD
			$this->cargar_conf();

			db_manager::abrir_transaccion($this->conexion);
			
			// Obtengo la versión actual del modelo. Si es la misma que $version, no migro nada
			$v = $this->get_version_app_base();
			if(!$v){
				throw new Exception('No se encuentra el numero de versión en la BD.');
			}

			$desde = new inst_version($v['version_actual']);
			$hasta = new inst_version($version);
						
			if ($hasta->es_menor_igual($desde)) {
				throw new Exception("La nueva versión ya está instalada.");			
				return;
			}

			//Ejecutar scripts en carpetas entre versiones
			// Se obtiene la lista historica de versiones desde "historico_versiones" de proyecto.ini
			// y se ejecutan todos los scripts necesarios para actualizar la version
			$str_versiones = inst::configuracion()->get('proyecto', 'historico_versiones');
			// Elimino corchetes y armo el arreglo partiendo de la version "desde"
			$str_versiones = explode($desde->__toString(), $str_versiones);
			if (isset($str_versiones[1])) {
				$str_versiones = $str_versiones[1];
			} else {
				$str_versiones = substr(trim($str_versiones[0]),1);
			}
			$str_versiones = substr(trim($str_versiones),0,-1);
			$versiones = explode(',',$str_versiones);

			$directorio = $this->path_proyecto.'/'.$this->conf->get_dir('diferenciales');
			$error = 0;

			$this->logger->debug('Migrando la base de la versión '.$desde->__toString().' hasta la versión '.$hasta->__toString());

			try {
					if (!empty($versiones)) {

							// Auditoria ----------------------------------------------
							// Si existe el esquema de auditoria, borro los triggers (desactivo) para ejecutar la actualizacion de la BD y evitar conflictos.
							// Los triggers se regeneran al final al ejecutar la migracion del esquema de auditoria.
							//--- Tablas de auditoría
							$auditoria = inst::cargador_clases('auditoria_tablas_postgres');
							$auditoria = new auditoria_tablas_postgres($this->conexion, $this->schema, $this->schema.'_auditoria', 'toba_guarani' );
							if ($auditoria->existe()) {
								$this->logger->debug('Desactivando esquema de auditoria durante la migración');
								// Borro triggers
								$auditoria->desactivar();
							}
							// --------------------------------------------------------


							foreach ($versiones as $version) { 

								if ($version) {

									$version = new inst_version($version);
									$scripts = array();

									$msj = 'Actualizando BD de negocios a version '. $version->__toString();
									$this->logger->debug($msj);

									$this->actualizar_version_app_base($version->__toString());

									$version_dir = $directorio.'/'.$version->__toString();
									if (is_dir($version_dir)) {
											$scripts = scandir($version_dir);

											$this->ordeno_arreglo($scripts,'orden','A');

											if ($scripts) {
													foreach($scripts as $script){
														$archivo = $version_dir.'/'.$script;
														if($this->conf->archivo_valido($archivo)) {
																$msj = 'Ejecutando Archivo: '.$script;
																$this->logger->debug($msj);
																db_manager::ejecutar_archivo($this->conexion, $archivo);
																// actualizar version base
																$script = str_replace("\\","\\\\",$script);
																$this->actualizar_version_base($version->__toString(),$version->get_build(),$archivo);
														}
													}	
											}
									}
								}
							}

							// Finalmente actualizo las tablas de permisos y conceptos que no se incluyen en los scripts
							$this->actualizar_tabla_conceptos();
							$this->actualizar_permisos_operaciones();

							// Auditoria ----------------------------------------------
							// Actualizo el esquema de auditoria si corresponde
							if ((!is_null($auditoria)) && ($auditoria->existe())) {
								$this->logger->debug('Actualizando esquema de auditoria ');
								$auditoria->reset_tablas();		
								$auditoria->agregar_tablas();		
								$auditoria->migrar();		

								db_manager::set_schema($this->conexion, $this->schema_auditoria);

								foreach ($versiones as $version) { 

									if ($version) {

										$version = new inst_version($version);
										$scripts = array();

										$msj = 'Actualizando esquema de auditoria a version '. $version->__toString();
										$this->logger->debug($msj);

										$version_dir = $directorio.'/'.$version->__toString();
										if (is_dir($version_dir)) {
												$version_dir .= '/Auditoria';
												if (is_dir($version_dir)) {
													$scripts = scandir($version_dir);

													$this->ordeno_arreglo($scripts,'orden','A');

													if ($scripts) {
															foreach($scripts as $script){
																$archivo = $version_dir.'/'.$script;
																if($this->conf->archivo_valido($archivo)) {
																		$msj = 'Ejecutando Archivo: '.$script;
																		$this->logger->debug($msj);
																		db_manager::ejecutar_archivo($this->conexion, $archivo);
																}
															}	
													}
												}
										}                
									}
								}
							}
							// --------------------------------------------------------        
						}
					} catch (Exception $e) {
							db_manager::abortar_transaccion($this->conexion);
							echo $e->getMessage();
					}			

			db_manager::cerrar_transaccion($this->conexion);
		
			$this->logger->debug('La BD de negocios se actualizó correctamente');
			
		}
		
	}
	
	protected function actualizar_tabla_conceptos() 
	{
		$msj = 'Actualizando Tabla de Conceptos';
		$this->logger->debug($msj);

		try {
			// Borro la tabla de conceptos
			$msj = ' - Borro la tabla de Conceptos';
			$this->logger->debug($msj);
			$sql = "DELETE FROM adm_conceptos;";
			db_manager::ejecutar($this->conexion, $sql);
		
			// Actualizo la tabla
			$nucleo = $this->conf->get_dir_datos_nucleo('nucleo');
			$directorio = $this->path_proyecto.'/'.$nucleo;
			// Archivo Tabla de Conceptos
			$archivo = $directorio.'/adm_conceptos.dca';
			$msj = ' - Inserto todos los campos nuevamente';
			$this->logger->debug($msj);
			db_manager::ejecutar_archivo($this->conexion, $archivo);
		} catch (Exception $e) {
			db_manager::abortar_transaccion($this->conexion);
			echo $e->getMessage();
		}
			
	}
	
	protected function actualizar_permisos_operaciones() 
	{
		try {
			// Borro la tabla de permisos
			$msj = 'Borro la tabla de Permisos y vuelvo a insertar todos los permisos para las operaciones';
			$this->logger->debug($msj);
			$sql = "DELETE FROM adm_permisos_db;";
			db_manager::ejecutar($this->conexion, $sql);
	
			$nucleo = $this->conf->get_dir_datos_nucleo('nucleo_permisos');
			$directorio = $this->path_proyecto.'/'.$nucleo;
			$archivos = scandir($directorio);
			$msj = 'Cargando Datos de los permisos';
			$this->logger->debug($msj);
			foreach($archivos as $archivo){
				$ruta = $directorio.'/'.$archivo;
				if($this->conf->archivo_valido($ruta)) {
					db_manager::ejecutar_archivo($this->conexion, $ruta);
				}	
			}
		} catch (Exception $e) {
			db_manager::abortar_transaccion($this->conexion);
			echo $e->getMessage();
		}
	}

	
	
	function post_instalacion($es_base_nueva)
	{
		// Obtengo los datos seteados en la pantalla de configuración del instalador
		$datos = $_SESSION['datos_configuracion'];
		
		// Creo el usuario o actualizo los datos
		if ($this->existe_usuario($datos['usuario_id'])) {
			$this->actualizar_clave($datos['usuario_id'], $datos['usuario_clave']);
		} else {
			$this->crear_persona($datos['usuario_id'], $datos['usuario_clave'], $datos['usuario_nombre'], $datos['usuario_email']);
			$this->asociar_grupo_acceso();
		}
		
		// Seteo la institución de Instalación
		$this->actualizar_institucion_install($datos['institucion']);
		
		// FOP
		$path_fop = $_SESSION['path_instalacion'] . "/aplicacion/php/3ros/fop095/fop";
		if(inst::archivos()->es_windows()) $path_fop= inst::archivos()->path_a_windows($path_fop);
		$archivo_instalacion_ini = $_SESSION['path_instalacion'] . "/instalacion/instalacion.ini";
		if(!file_exists($archivo_instalacion_ini)){
			throw new Exception("No existe el archivo $archivo_instalacion_ini");
		}
		// Se modifica instalacion.ini
		$f = fopen($archivo_instalacion_ini, 'a');
		$texto = "\n[xslfo]\nfop = $path_fop\n";
		fwrite($f, $texto);
		fclose($f);
		// Aplico permisos al ejecutable del fop
		$permisos = 0774;		
		chmod($path_fop, $permisos);
		
	}

	function existe_usuario($usuario)
	{
		$sql = "SELECT * FROM {$this->schema}.mdp_personas WHERE usuario = '$usuario'";
		$rs = db_manager::consultar_fila($this->conexion, $sql);
		return !empty($rs);
	}

	function actualizar_clave($usuario, $clave)
	{
		$clave = md5($clave);		
		$sql = "UPDATE	{$this->schema}.mdp_personas
				SET		clave = '$clave'
				WHERE	usuario = '$usuario'";
		db_manager::ejecutar($this->conexion, $sql);
	}

	function crear_persona($usuario, $clave, $nombre, $email=null)
	{		 
		$nombre = substr($nombre, 0, 60);
		$clave = md5($clave);
		$sql = "INSERT INTO {$this->schema}.mdp_personas
				(
					persona,
					apellido,
					nombres,
					sexo,
					fecha_nacimiento,
					nacionalidad,
					usuario,
					clave
				)
					VALUES
					(
						2,
						'$nombre',
						'$nombre',
						'M',
						'2010-01-01',
						1,
						'$usuario',
						'$clave'
					)";
		db_manager::ejecutar($this->conexion, $sql);

		$sql = "INSERT INTO {$this->schema}.mdp_personas_documentos
				(
					documento,
					persona,
					pais_documento,
					tipo_documento,
					nro_documento
				)
				VALUES
				(
					2,
					2,
					54,
					0,
					'2'
				)";
		db_manager::ejecutar($this->conexion, $sql);
	}

	function asociar_grupo_acceso()
	{
		$sql = "INSERT INTO {$this->schema}.mdp_personas_grupo_acc
				(
					persona,
					tipo_usuario,
					usuario_grupo_acc,
					grupo_acceso_default
				)
				VALUES
				(
					2,
					'Gestion',
					'admin',
					'S'
				)";
		db_manager::ejecutar($this->conexion, $sql);
	}
	
	function actualizar_institucion_install($nombre_abrev)
	{
		db_manager::abrir_transaccion($this->conexion);
	
		// Actualizo la institucion de instalacion, se cambia el nombre de acuerdo al ingresado y se define por el momento
		// q la institucion donde se instale el sistema siempre tendra el ID=1
		$sql = "UPDATE	{$this->schema}.sga_instituciones
				SET		nombre = '$nombre_abrev', nombre_abreviado = '$nombre_abrev'
				WHERE	institucion = 1";
		db_manager::ejecutar($this->conexion, $sql);
		
		db_manager::cerrar_transaccion($this->conexion);		
	}
	
	
	function cargar_conf()
	{
		$clase_conf = "guarani_instalacion_conf";
		$archivo_conf = $this->path_proyecto."/php/extension_toba/".$clase_conf.".php";
		
		require_once($archivo_conf);
		$this->conf = new $clase_conf($this);
	}

	
	/**
	 * Ordena un arreglo asosicativo por una de sus claves
	 */
	function ordeno_arreglo(&$arreglo,$clave,$forma='A')
	{	
		if (! is_array($arreglo) || $arreglo == array()) return;
		// Ordeno los arreglos por el orden
		foreach ($arreglo as $key => $fila) {
		    $orden[$key] = $fila[$clave];
		}
		
		if ($forma == 'D') {
			array_multisort($orden, SORT_DESC, $arreglo);
		} else {
			array_multisort($orden, SORT_ASC, $arreglo);
		}	
	}	
	
		
	/**
	 * Crea la estructura de la base previa a la carga de los datos (objetos con orden negativo)
	 */
	function crear_estructura_predatos()
	{
		//Obtengo los directorios
		$objetos = $this->conf->get_objetos();
		$ddls = $this->conf->get_ddls();
		
		//Los ordeno por 'orden'
		$this->ordeno_arreglo($ddls,'orden');   
		$this->ordeno_arreglo($objetos,'orden');
		
		//Ejecuto los archivos de los directorios respectivos
		foreach ($objetos as $objeto) {
			if ($objeto['orden'] < 0 && $objeto['cargar']) {
				foreach ($ddls as $ddl) {
					if ($ddl['cargar']) {
						$directorio = $this->path_proyecto.'/'.$this->conf->get_dir('ddl').'/'.$ddl['dir'].'/'.$objeto['dir'];
						$archivos = false;
						if (is_dir ($directorio)) $archivos = scandir($directorio);
						$this->logger->debug('Creando '.$objeto['nombre'].' de '.$ddl['nombre'].' ');
						if ($archivos) {
							foreach($archivos as $archivo){
								$ruta = $directorio.'/'.$archivo;
								if($this->conf->archivo_valido($ruta)) {
									db_manager::ejecutar_archivo($this->conexion, $ruta);
								}
							}
						}
					}
				}	
			}
		}	
		
	}

	/**
	 * Crea la estructura de la base posterior a la carga de los datos (objetos con orden positivo)
	 */
	function crear_estructura_posdatos()
	{
		//Obtengo los directorios
		$objetos = $this->conf->get_objetos();
		$ddls = $this->conf->get_ddls();
		
		//Los ordeno por 'orden'
		$this->ordeno_arreglo($ddls,'orden');   
		$this->ordeno_arreglo($objetos,'orden');
		
		//Ejecuto los archivos de los directorios respectivos
		foreach ($objetos as $objeto) {
			if ($objeto['orden'] >= 0 && $objeto['cargar']) {
				foreach ($ddls as $ddl) {
					if ($ddl['cargar']) {
						$directorio = $this->path_proyecto.'/'.$this->conf->get_dir('ddl').'/'.$ddl['dir'].'/'.$objeto['dir'];
						$archivos = false;
						if (is_dir ($directorio)) $archivos = scandir($directorio);
						$this->logger->debug('Creando '.$objeto['nombre'].' de '.$ddl['nombre'].' ');
						if ($archivos) {
							foreach($archivos as $archivo){
								$ruta = $directorio.'/'.$archivo;
								if($this->conf->archivo_valido($ruta)) {
									db_manager::ejecutar_archivo($this->conexion, $ruta);
								}	
							}
						}
					}	
				}	
			}
		}
		
	}

	
	/**
	 * Carga los datos del núcleo en la base y luego los de prueba si fueron seleccionados
	 */
	function cargar_datos($juego_datos)
	{
		$datos_nucleo = $this->conf->get_datos_nucleo();
		$this->ordeno_arreglo($datos_nucleo,'orden');
		
		//Cargo los datos del núcleo
		foreach ($datos_nucleo as $nucleo) {
			$this->cargar_datos_nucleo($nucleo);
		}

		// Si existe $juego_datos que no sean los basicos cargo los datos de prueba
		if ((isset($juego_datos)) && ($juego_datos != 'basicos')) {
			
			if ($this->incluir_juegos_anteriores) {
				// Se deben cargar tambien los juegos de datos con 'orden' menor al del juego indicado
				$datos_prueba = $this->conf->get_datos_prueba();
				$this->ordeno_arreglo($datos_prueba, 'orden');
				
				if (is_string($juego_datos)) $juego_datos = $datos_prueba[$juego_datos];
				$orden = $juego_datos['orden'];
				
				foreach ($datos_prueba as $prueba) {
					if ($prueba['orden'] <= $orden) {
						$this->cargar_datos_prueba($prueba);
					}
				}
			} else {
				// Incluir solo el juego de datos indicado
				$this->cargar_datos_prueba($juego_datos);
			}
		}
	
	}
	
	function cargar_datos_nucleo($nucleo)
	{
		if (is_string($nucleo)) {
			$datos_nucleo = $this->conf->get_datos_nucleo();
			$nucleo = $datos_nucleo[$nucleo];
		}
		
		if ($nucleo['cargar']) {
			$directorio = $this->path_proyecto.'/'.$this->conf->get_dir('datos').'/'.$nucleo['dir'];
			$archivos = scandir($directorio);
			$this->logger->debug('Cargando Datos de "'.$nucleo['nombre'].'" ');
			foreach($archivos as $archivo){
				$ruta = $directorio.'/'.$archivo;
				if($this->conf->archivo_valido($ruta)) {
					db_manager::ejecutar_archivo($this->conexion, $ruta);
				}	
			}
		}	
	}
	
	function cargar_datos_prueba($juego=null)
	{
		if (!isset($juego)) return;
		
		if (is_string($juego)) {			
			$datos_prueba = $this->conf->get_datos_prueba();
			$juego = $datos_prueba[$juego];
		}
		
		$directorio = $this->path_proyecto.'/'.$this->conf->get_dir('datos').'/'.$juego['dir'];
		$archivos = scandir($directorio);
		$this->logger->debug('Cargando Juego de Datos de Prueba "'.$juego['nombre'].'" ');
		foreach($archivos as $archivo){
			$ruta = $directorio.'/'.$archivo;
			if($this->conf->archivo_valido($ruta)) {
				db_manager::ejecutar_archivo($this->conexion, $ruta);
			}	
		}
	}
	

	function insertar_version_app_base($version) 
	{
		
		$version = new inst_version($version);
 		$version_app = $version->get_string_partes('.');
		$build = $version->get_build();
		$this->actualizar_version_app_base($version_app);
		//Obtengo la revision del proyecto
		$this->insertar_version_base($version_app,$build,'Creacion de la Base');
		return;								
	}
	

	function insertar_version_base($version, $build, $script='', $obs=null) 
	{
		if(!is_null($obs)) {
			$obs = "'$obs'";
		} else {
			$obs = 'NULL';
		}
        if (!isset($build)) $build = 0;
			
		// Actualizo version de la aplicacion si es que el desde y hasta de los builds
		// pasan a traves de varias versiones de la app.	
		$this->actualizar_version_app_base($version);
		$sql = "INSERT INTO {$this->schema}.app_versiones_base (version_app, 
		 													 	 build_base,
															 	 ultimo_script_corrido,
															 	 observaciones) 
				VALUES ('$version',
						$build,
						'$script',
						$obs)";
		db_manager::ejecutar($this->conexion, $sql);		
	}
	
	function actualizar_version_app_base($version) 
	{
		$sql = "SELECT count(*) as cant 
								 FROM {$this->schema}.app_versiones
								 WHERE version_app = '$version'";
		$res = db_manager::consultar($this->conexion, $sql);

		if(isset ($res[0]['cant']) && $res[0]['cant'] == 0)
		{
			$sql = "INSERT INTO {$this->schema}.app_versiones (version_app,fecha_actualizacion)
					VALUES ('$version',now())";
			db_manager::ejecutar($this->conexion, $sql);
		}
	}
	

	function actualizar_version_base($version, $build, $script='', $obs=0) 
	{
        if (!isset($build)) $build = 0;
		$sql = "INSERT INTO {$this->schema}.app_versiones_base (version_app, 
		 													 	 build_base,
															 	 ultimo_script_corrido,
															 	 observaciones) 
				VALUES ('$version',
						$build,
						'$script',
						$obs)";
		db_manager::ejecutar($this->conexion, $sql);		
	}	
	
	function get_version_app_base()
	{
		$sql = "SELECT		trim(version_app) as version_actual 
				FROM		{$this->schema}.app_versiones_base
				ORDER BY	id_conversion DESC
				LIMIT		1
		";
		return db_manager::consultar_fila($this->conexion, $sql);
	}

	function aplicar_pgcrypto() 
	{
		$sql = 'SET SEARCH_PATH TO public;';
		db_manager::ejecutar($this->conexion, $sql);		
		$sql = 'CREATE EXTENSION pgcrypto;';
		db_manager::ejecutar($this->conexion, $sql);		
		$sql = 'SET SEARCH_PATH TO ' . $this->schema .';';
		db_manager::ejecutar($this->conexion, $sql);		
	}

}
?>
