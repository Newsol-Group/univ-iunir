<?php

use SIU\Guarani\RestHooks\RestHookSanaviron;
use SIU\AraiJsonMigrator\Entities\Person;
use SIU\AraiJsonMigrator\Util\Documento;
use League\Csv\CharsetConverter;

class guarani_modelo extends toba_aplicacion_modelo_base 
{
	protected $ddls					=	array();
	protected $datos_nucleo			=	array();
	protected $datos_prueba			=	array();
	protected $datos_custom			=	array();
	protected $objetos				=	array();
	protected $modificadores		=	array();

	protected $conversiones 		=	array();
	protected $vers = 0;

	protected $plataforma_elegida = 0;
	protected $columnas_elegidas = '';
	protected $columnas_existen = 'N';
	protected $conf;

	protected $cant_usuarios_paginado       = 0;
	protected $incluye_suspendidos          = false;
	protected $plataforma_educacion_virtual	= null;
	protected $columnas_vinculacion_moodle	= [
		'username'	=> 'Nombre de usuario', 
		'fullname'	=> 'Nombre completo',
		'email'		=> 'Dirección de correo',
		'all'		=> 'Todas'
	];
	protected $columnas_vinculacion_moodle_seleccionadas = [];

	protected static $usuarios_arai = null;
	
	// Datos relacionados a Moodle.
	protected $s__alumnos;
	protected $s__alumnos_eliminados;
	protected $s__alumnos_curso_virtual_guarani;
	protected $s__alumnos_curso_virtual_moodle;
	protected $s__docentes;
	protected $s__docentes_curso_virtual_guarani;
	protected $s__docentes_curso_virtual_moodle;
	protected $s__datos_plataforma;
	protected $s__plataforma_seleccionada = null;
	protected $s__roles_docentes;
	/**
	 * @var moodle $moodle_obj Objeto de la clase moodle
	 */
	protected $moodle_obj = null;
	protected $curso_externo = null;
	protected $s__datos_curso_virtual;
	protected $info_curso_moodle = null;
	protected $personas_para_crear = [];
	protected $alumnos_para_matricular = [];
	protected $alumnos_para_desmatricular = [];
	protected $docentes_para_matricular = [];
	protected $docentes_para_desmatricular = [];
	protected $actualizar_alumnos_ok;
	protected $actualizar_docentes_ok;

	protected $datos_form_vincular;
	

        function set_entorno($manejador_interface, toba_modelo_instalacion $instalacion, toba_modelo_instancia $instancia, toba_modelo_proyecto $proyecto, $default_schema='public')
	{
//		$id_def_base = $proyecto->construir_id_def_base('guarani');
//		if ($instalacion->existe_base_datos_definida($id_def_base)) {			
//			$parametros = $instalacion->get_parametros_base($id_def_base);
//			if (isset($parametros['schema']) &&  $parametros['schema'] == 'public') {
//				echo "\n ERROR: El Esquema de Guarani no puede ser 'public' \n";
//				echo " Por favor ejecute el comando toba proyecto cambiar_esquema -p guarani \n";
//				exit(222);
//			}
//		}
		parent::set_entorno($manejador_interface, $instalacion, $instancia, $proyecto, 'negocio');
		if ($this->objetos != array()) return;
		
		global $argv;
		
		if ($argv[0] == 'proyecto' && isset($argv[1]) && $argv[1] == 'instalar') {
			for($a=2;$a<guarani_arreglos::count($argv);$a++) {
				if (preg_match("/^-/",$argv[$a])) { //Es un modificador
					$pila_modificadores[$a] = $argv[$a];
					$this->modificadores[$pila_modificadores[$a]] = '';
				} else {	//Es la asignacion de un modificador
					if(isset($pila_modificadores[$a-1])) {
						$this->modificadores[$pila_modificadores[$a-1]] = $argv[$a];
					} else {
						
						echo "\n ERROR PARSEANDO PARAMETROS: Los modificadores no pueden contener espacios\n";
						echo " El error ha ocurrido entre las cadenas: \"" . $argv[$a-1] ."\" y \"". $argv[$a] ."\"\n";
						echo " (Si esta definiendo una REGEXP utilice \"/s\")\n";
						exit(222);
					}
				}	
			}
		}	

		$clase_conf = $this->proyecto->get_id()."_instalacion_conf_personal";
		$archivo_conf = $this->proyecto->get_dir()."/php/extension_toba/".$clase_conf.".php";
		if (!file_exists($archivo_conf)) {
			$clase_conf = $this->proyecto->get_id()."_instalacion_conf";
			$archivo_conf = $this->proyecto->get_dir()."/php/extension_toba/".$clase_conf.".php";
		}
		
		require_once($archivo_conf);
		$this->conf = new $clase_conf($this);

		$this->objetos 				= $this->conf->get_objetos();
		$this->ddls					= $this->conf->get_ddls();
		$this->datos_nucleo			= $this->conf->get_datos_nucleo();
		$this->datos_prueba			= $this->conf->get_datos_prueba();
		$this->datos_custom			= $this->conf->get_datos_custom();
		$this->conversiones			= $this->conf->get_conversiones();

		$this->ordeno_arreglo($this->ddls			,'orden');   
		$this->ordeno_arreglo($this->datos_nucleo	,'orden');
		$this->ordeno_arreglo($this->datos_prueba	,'orden');
		$this->ordeno_arreglo($this->datos_custom	,'orden');
		$this->ordeno_arreglo($this->objetos		,'orden');
	}
	

	/**
	 * Ordena un arreglo asosicativo por una de sus claves
	 */
	function ordeno_arreglo(&$arreglo,$clave,$forma='A')
	{	
		if (! is_array($arreglo) || $arreglo == array()) return;
                
		$orden = array();
                
		// Ordeno los arreglos por el orden
		foreach ($arreglo as $key => $fila) {
			if (isset($fila[$clave])) {
				$orden[$key] = $fila[$clave];
			} else {
				$orden[$key] = $fila;
			}
		}
		
		if ($forma == 'D') {
			array_multisort($orden, SORT_DESC, $arreglo);
		} else {
			array_multisort($orden, SORT_ASC, $arreglo);
		}	
	}	
	
	/**
	 * Crea la estructura de la base y carga los datos del nucleo y los de prueba seleccionados
	 */
	function cargar_modelo_datos($base)
	{
		$base->abrir_transaccion();

		$this->aplicar_pgcrypto();

		if (! $base->existe_schema($this->schema_modelo)) {
			$base->crear_schema($this->schema_modelo);
		}
		$base->set_schema($this->schema_modelo);
		
		$base->retrazar_constraints();
		
		$this->crear_estructura_predatos($base);
		$this->cargar_datos($base);
		$this->crear_estructura_posdatos($base);
		
		$this->insertar_version_app_base($base);
		
		$base->cerrar_transaccion();
	}

	function get_base()
	{
		$fuentes = $this->proyecto->get_indice_fuentes();
		
		//Obtengo la posicion de la fuente guarani y luego la utilizo para conectarme		
		$posicion = array_search('guarani', $fuentes);
		$id_def_base = $this->proyecto->construir_id_def_base($fuentes[$posicion]);
		$this->parametros = $this->instalacion->get_parametros_base($id_def_base);
		$base = $this->instalacion->conectar_base($id_def_base);
		return $base;
	}
	
	function get_id_base()
	{
		$param_db = $this->instancia->get_parametros_db();
		return $param_db['base'];		
	}

	
	function get_id_def_base()
	{
		$fuentes = $this->proyecto->get_indice_fuentes();
		$id_def_base = $this->proyecto->construir_id_def_base(current($fuentes));
		return $id_def_base;
	}

	function get_tablas()
	{
		$base = $this->get_base();	
		$tablas = $base->get_lista_tablas();
		return $tablas;
	}

	/**
	 * Crea la estructura de la base sin carga datos 
	 */
	function crear_estructura(toba_db $base)
	{
		crear_estructura_predatos($base);
		crear_estructura_posdatos($base);
	}

	/**
	 * Migra la base de una versión a otra
	 */
	function migrar_base(toba_version $desde, toba_version $hasta)
	{
		$tablas_sin_auditoria = null;

		// Controlar si corresponde migrar o no de acuerdo a la version actual en la BD contra
		// la version indicada en proyecto.ini
		if ($hasta->es_menor_igual($desde)) {
			$this->manejador_interface->mensaje('La versión de la base está actualizada a la versión del proyecto.', true);
			return;
		}

		$base = $this->get_base();
		$id_def_base = $this->get_id_def_base();
		
		// Ejecutar scripts en carpetas entre versiones
		// Se obtiene la lista historica de versiones desde "historico_versiones" de proyecto.ini
		// y se ejecutan todos los scripts necesarios para actualizar la version
		$str_versiones = $this->get_historico_versiones();

		// Se eliminan corchetes y se arma el arreglo partiendo de la version "desde"
		$str_versiones = explode($desde->__toString(), $str_versiones);
		if (isset($str_versiones[1])) {
			$str_versiones = $str_versiones[1];
		} else {
			$str_versiones = substr(trim($str_versiones[0]), 1);
		}
		$str_versiones = substr(trim($str_versiones), 0, -1);
		$versiones = explode(',', $str_versiones);
		
		$directorio = $this->get_dir('diferenciales');
		$error = 0;

		$this->manejador_interface->mensaje('Migrando la base de la versión ' . $desde->__toString() . ' hasta la versión ' . $hasta->__toString(), true);

		try {
			if (!empty($versiones)) {
				$base->abrir_transaccion();
				$base_audit = $this->proyecto->get_db_negocio();

				//---- Acciones iniciales sobre auditorí­a ----------------------
				
				/** @var toba_auditoria_tablas_postgres */
				$auditoria = $base_audit->get_manejador_auditoria($this->schema_modelo, $this->schema_auditoria, $this->schema_toba);

				if ((!is_null($auditoria)) && ($auditoria->existe())) {
					// Se obtienen las tablas configuradas para no manejar auditorí­a
					$tablas_sin_auditoria = $auditoria->get_tablas_triggers_desactivados();
					$this->manejador_interface->mensaje('Desactivando esquema de auditoria durante la migración ', false);
					// Se eliminan los triggers de auditorí­a
					$auditoria->desactivar();
					$this->manejador_interface->progreso_fin();
				}

				//---- Ejecución de diferenciales de actualización  -------------
				
				foreach ($versiones as $version) {
					if ($version) {
						$version = new toba_version($version);
						$scripts = array();

						$msj = 'Actualizando BD de negocios a version ' . $version->__toString();
						$this->manejador_interface->mensaje($msj, true);
						toba_logger::instancia()->debug($msj);

						$this->actualizar_version_app($version->__toString(), $base);

						$version_dir = $directorio . '/' . $version->__toString();
						if (is_dir($version_dir)) {
							$scripts = scandir($version_dir);
							$this->ordeno_arreglo($scripts, 'orden', 'A');
							if ($scripts) {
								foreach ($scripts as $script) {
									$archivo = $version_dir . '/' . $script;
									if ($this->conf->archivo_valido($archivo)) {
										$msj = 'Ejecutando Archivo: ' . $script;
										$this->manejador_interface->mensaje($msj, true);
										toba_logger::instancia()->debug($msj);
										$base->ejecutar_archivo($archivo);
										$this->manejador_interface->progreso_avanzar();
										// Se actualiza versión base
										$script = str_replace("\\", "\\\\", $script);
										$this->actualizar_version_base($version->__toString(), $version->get_build(), $archivo, $base);
									}
								}
							}
						}
						$this->manejador_interface->enter();
					}
				}

				// Se actualizan las tablas de permisos y conceptos que no se incluyen en los scripts
				$this->actualizar_tabla_conceptos();
				$this->actualizar_permisos_operaciones();

				$base->cerrar_transaccion();

				//---- Acciones finales sobre auditorí­a ------------------------

				// Actualización del esquema de auditorí­a si corresponde
				$base->abrir_transaccion();
				if ((!is_null($auditoria)) && ($auditoria->existe())) {
					$this->manejador_interface->mensaje('Actualizando esquema de auditoria ', false);
					$this->manejador_interface->progreso_avanzar();
					$this->manejador_interface->progreso_avanzar();
					$auditoria->reset_tablas();
					$auditoria->agregar_tablas();
					$auditoria->migrar();

					if (!empty($tablas_sin_auditoria)) {
						// Se desactivan las tablas configuradas sin auditorí­a
						$this->manejador_interface->mensaje('Restaurando configuracion de auditoria', false);
						$this->manejador_interface->progreso_avanzar();
						$auditoria->set_estado_activacion_triggers($tablas_sin_auditoria, $this->schema_modelo, false);
					}

					$this->manejador_interface->progreso_avanzar();
					$this->manejador_interface->enter();

					$base->set_schema($this->schema_auditoria);

					foreach ($versiones as $version) {
						if ($version) {
							$version = new toba_version($version);
							$scripts = array();

							$msj = 'Actualizando esquema de auditoria a version ' . $version->__toString();
							$this->manejador_interface->mensaje($msj, true);
							toba_logger::instancia()->debug($msj);

							$version_dir = $directorio . '/' . $version->__toString();
							if (is_dir($version_dir)) {
								$version_dir .= '/Auditoria';
								if (is_dir($version_dir)) {
									$scripts = scandir($version_dir);

									$this->ordeno_arreglo($scripts, 'orden', 'A');

									if ($scripts) {
										foreach ($scripts as $script) {
											$archivo = $version_dir . '/' . $script;
											if ($this->conf->archivo_valido($archivo)) {
												$msj = 'Ejecutando Archivo: ' . $script;
												$this->manejador_interface->mensaje($msj, true);
												toba_logger::instancia()->debug($msj);
												$base->ejecutar_archivo($archivo);
												$this->manejador_interface->progreso_avanzar();
											}
										}
									}
								}
							}
							$this->manejador_interface->enter();
						}
					}
				}
				
				$base->cerrar_transaccion();
			}
		} catch (Exception $e) {
			$error += 1;
			$base->abortar_transaccion();
			echo $e->getMessage();
		}

		if (!$error) {
			$this->manejador_interface->enter();
		} else {
			$this->manejador_interface->mensaje('Hubo errores en la migración.', false);
			$this->manejador_interface->enter();
			return false;
		}
		
		return true;
	}
	
	/**
	 * Migra la base de una revisión a otra
	 */
	function migrar_base_revisiones($desde, $hasta)
	{
		$tablas_sin_auditoria = null;
		
		if ($desde >= $hasta) {
			$this->manejador_interface->mensaje('Rango incorrecto de migración de revisiones', true);
			return;
		}

		$base = $this->get_base();
		$id_def_base = $this->get_id_def_base();

		// Se obtiene la lista de scripts a correr entre revisiones
		$scripts = $this->get_scripts_conversion($desde, $hasta);

		// Se ejecutan los scripts
		$error = 0;
		$this->manejador_interface->mensaje('Migrando la base de la revisión ' . $desde . ' hasta la revisión ' . $hasta, true);
		$this->manejador_interface->enter();

		if (!empty($scripts)) {
			$this->ordeno_arreglo($scripts, 'orden', 'A');

			$base->abrir_transaccion();

			$version = new toba_version($this->get_version_actual());
			$version_app = $version->get_string_partes('.');
			$build = $version->get_build();
			if (!isset($build)) {
				$build = 0;
			}

			try {
				//---- Acciones iniciales sobre auditorí­a ----------------------
				
				// Si existe el esquema de auditoria, se desactivan los triggers para ejecutar la actualizacion de la BD y evitar conflictos.
				// Los triggers se regeneran al final al ejecutar la migracion del esquema de auditoria.
				$base_audit = $this->proyecto->get_db_negocio();
				
				/** @var toba_auditoria_tablas_postgres */				
				$auditoria = $base_audit->get_manejador_auditoria($this->schema_modelo, $this->schema_auditoria, $this->schema_toba);
				
				if ((!is_null($auditoria)) && ($auditoria->existe())) {
					// Se obtienen las tablas configuradas para no manejar auditorí­a
					$tablas_sin_auditoria = $auditoria->get_tablas_triggers_desactivados();
					$this->manejador_interface->mensaje('Desactivando esquema de auditoria durante la migración ', false);
					// Se eliminan triggers
					$auditoria->desactivar();
					$this->manejador_interface->progreso_fin();
				}
				// --------------------------------------------------------

				foreach ($scripts as $script) {
					if ($this->conf->archivo_valido($script)) {
						$msj = 'Ejecutando Archivo: ' . $script;
						$this->manejador_interface->mensaje($msj, true);
						toba_logger::instancia()->debug($msj);
						$base->ejecutar_archivo($script);
						// actualizar version base
						$script = str_replace("\\", "\\\\", $script);

						$this->actualizar_version_app($version_app, $base);
						$this->actualizar_version_base($version_app, $build, $script, $base, $hasta);
					}

					$this->manejador_interface->enter();
				}

				// Finalmente se actualizan las tablas de permisos y conceptos que no se incluyen en los scripts
				$this->actualizar_tabla_conceptos();
				$this->actualizar_permisos_operaciones();
			} catch (toba_error $e) {
				$error += 1;
				$base->abortar_transaccion();
				echo $e->getMessage();
			}

			$base->cerrar_transaccion();

			try {
				//---- Auditorí­a -----------------------------------------------
				// Se actualiza el esquema de auditorí­a si corresponde
				$base->abrir_transaccion();
				if ((!is_null($auditoria)) && ($auditoria->existe())) {
					$this->manejador_interface->mensaje('Actualizando esquema de auditoria ', false);
					$this->manejador_interface->progreso_avanzar();
					$this->manejador_interface->progreso_avanzar();
					$auditoria->reset_tablas();
					$auditoria->agregar_tablas();
					$auditoria->migrar();
					$this->manejador_interface->progreso_avanzar();
					$this->manejador_interface->enter();
					
					if (!empty($tablas_sin_auditoria)) {
						// Se desactivan las tablas configuradas sin auditorí­a
						$this->manejador_interface->mensaje('Restaurando configuracion de auditoria', false);
						$this->manejador_interface->progreso_avanzar();
						$auditoria->set_estado_activacion_triggers($tablas_sin_auditoria, $this->schema_modelo, false);
					}

					// Se obtiene la lista de scripts a correr entre revisiones
					$scripts = $this->get_scripts_conversion_auditoria($desde, $hasta);

					if (!empty($scripts)) {
						$base->set_schema($this->schema_auditoria);
						foreach ($scripts as $script) {
							if ($this->conf->archivo_valido($script)) {
								$msj = 'Ejecutando Archivo: ' . $script;
								$this->manejador_interface->mensaje($msj, true);
								toba_logger::instancia()->debug($msj);
								$base->ejecutar_archivo($script);
							}
							$this->manejador_interface->enter();
						}
					}
				}
			} catch (toba_error $e) {
				$error += 1;
				$base->abortar_transaccion();
				echo $e->getMessage();
			}

			$base->cerrar_transaccion();
		} else {
			$this->manejador_interface->mensaje('No es necesario actualizar la BD. No se han realizado cambios entre las revisiones ' . $desde . '-' . $hasta, false);
			$this->manejador_interface->enter();
		}

		if (!$error) {
			$this->manejador_interface->enter();
		} else {
			$this->manejador_interface->mensaje('Hubo errores en la migración.', false);
			$this->manejador_interface->enter();
			return false;
		}
		
		return true;
	}
		
	function restaurar_backups($id_def_base,$backups)
	{
		$error = false;
		$this->manejador_interface->mensaje('Restaurando su base inicial', false);
		if (!$id_def_base) $id_def_base = $this->get_id_def_base();
		if (!$this->existe_base_datos($id_def_base)) $this->crear_base($id_def_base);
		foreach($backups as $schema => $archivo) {
			$this->manejador_interface->mensaje('Restaurando el esquema "'.$schema.'"', false);
		 	if (!$this->importar_base($id_def_base, $archivo, false)) $error = true;
		}	
		if (!$error) {
			$this->manejador_interface->progreso_fin();
		} else {
			$this->manejador_interface->mensaje('Lamentablemente hubo problemas al intentar restaurar su base incial. Inténtelo manualmente.', false);
		}	
		return;
	}
	
	
	function insertar_version_app_base(toba_db $base) 
	{
		$version = new toba_version($this->get_version_actual());
 		$version_app = $version->get_string_partes('.');
		$build = $version->get_build();
		$this->actualizar_version_app($version_app,$base);
		//Obtengo la revision del proyecto
		$rev = $this->obtener_revision_proyecto();
		$this->insertar_version_base($version_app,$build,'Creacion de la Base',$base,$rev);
		return;						
	}
	
	/**
	 * Actualiza la versión de la aplicacion en la base de datos
	 * @param $version
	 * @return unknown_type
	 */

	function actualizar_version_app($version,toba_db $base=null) {
		
		if($base == null) $base = $this->get_base();
		$res = $base->consultar ("SELECT count(*) as cant 
								 FROM app_versiones
								 WHERE app_versiones.version_app = '$version'");

		if(isset ($res[0]['cant']) && $res[0]['cant'] == 0)
		{
			$res = $base->ejecutar ("INSERT INTO app_versiones (version_app,fecha_actualizacion)
	     							VALUES ('$version',now())");
		}
	}
	
	
	function insertar_version_base($version,$build,$script='',toba_db $base=null, $obs=null) 
	{
		if($base == null)
			$base = $this->get_base();
		if(!is_null($obs))
			$obs = "'$obs'";
		else {
			$obs = 'NULL';
		}
                if (!isset($build)) $build = 0;
			
		// Actualizo version de la aplicacion si es que el desde y hasta de los builds
		// pasan a traves de varias versiones de la app.	
		$this->actualizar_version_app($version,$base);
		$res = $base->ejecutar ("INSERT INTO app_versiones_base (version_app, 
		 													 	 build_base,
															 	 ultimo_script_corrido,
															 	 observaciones) 
									VALUES ('$version',
  											$build,'$script',$obs)");
  		return;									
		
	}

	function actualizar_version_base($version,$build,$script,toba_db $base=null, $obs=0) 
	{
		if(is_null($base)) $base = $this->get_base();
        if (!isset($build)) $build = 0;
			
		$res = $base->ejecutar ("INSERT INTO app_versiones_base (version_app, 
			 													 	 build_base,
																 	 ultimo_script_corrido,
																 	 observaciones,fecha_actualizacion)
										VALUES ('$version',
	  											$build,'$script','$obs',now())");
		return;						
	}
	

	function aplicar_pgcrypto() 
	{
		$base = $this->get_base();
		$base->set_schema('public');
		try {                
				$msj = 'Creando extension PGCRYPTO';
				$this->manejador_interface->mensaje($msj, true);
				toba_logger::instancia()->debug($msj);
				$sql = 'CREATE EXTENSION pgcrypto;';
				$base->ejecutar_transaccion($sql);
				$this->manejador_interface->progreso_fin();

			} catch (toba_error $e) {
					$base->abortar_transaccion();
					echo $e->getMessage();
			}
	}

	/**
	 * Dada una revision, actualiza el proyecto, y convierte la base de negocio a esa revision   
	 *  
	 */
	function cargar_revision($revision = 'HEAD')
	{
		global $argv;

		$rev_proyecto = $this->obtener_revision_proyecto();
		$this->manejador_interface->titulo('Revision Actual del Proyecto : '.$rev_proyecto);	
		$rev_actual = $this->obtener_revision_bd();
		if ($rev_actual == 0) {
			$rev_actual = $rev_proyecto;
		}
		$this->manejador_interface->titulo('Revision Actual de la BD : '.$rev_actual);	
		
		//Chequeo que la revisión a cargar no sea menor a la actual (No se realizan migraciones hacia atras)
		if (($revision != 'HEAD') && ($revision < $rev_actual)) {
			$this->manejador_interface->mensaje('Imposible cargar la revisión indicada. La revisión indicada es menor a la revisión actual de la BD.', true);
			return;
		}
		
		//Exporto los datos locales previos a la actualización  del proyecto
		$this->proyecto->exportar();
		$this->instancia->exportar_local();

		//Actualizo el proyecto (código y BD) y obtengo la nueva revision del proyecto
		$this->manejador_interface->titulo( "UPDATE DEL PROYECTO" );
		try {
			$svn = new toba_svn();		
			$path = $this->proyecto->get_dir();
			$resultado_update = $svn->update($path,$revision);
			$last_rev = $svn->get_revision($path);
		} catch (Exception $e) {
			$this->manejador_interface->mensaje($e->getMessage());	
			return;
		}
		$this->manejador_interface->mensaje($resultado_update);
		
		if ($revision == 'HEAD') {
			$revision = $last_rev;
		}
		
		//Chequeo que la revisión a cargar este dentro del rango
		$revision = intval($revision);
		$last_rev = intval($last_rev);
		if (!(($revision >= $rev_actual) && ($revision <= $last_rev))) {
			$this->manejador_interface->mensaje('Imposible cargar la revisión indicada. Chequee la revisión del proyecto y servidor', true);
			return;			
		}
		//Chequeo que el proyecto no está en la última  revisión (no es necesario regenerar)
		if ($rev_actual == $last_rev) {
			$this->manejador_interface->mensaje('La revisión de la base está actualizada a la ultima revisión del proyecto.', true);
			return;
		}
		
		//Regenero metadatos del proyecto
		$this->get_proyecto()->regenerar();

		//Convertir o regenerar la BD segun decisión del usuario
		$this->manejador_interface->titulo( "ADMINISTRACION DE LA BASE DE DATOS" );
		$opciones = Array("c"=>"Convertir Base","i"=>"Instalar Base");
		$respuesta = $this->manejador_interface->dialogo_lista_opciones($opciones,
									"Compatibilización de la Aplicación con la Base de Datos",
									false,'Opción Deseada', true );
		switch ($respuesta) {
    		case "c":
    			$this->migrar_base_revisiones($rev_actual,$revision);
        		break;
    		case "i":
    			$argv[0] = 'proyecto'; 
	    		$argv[1] = 'instalar';
	    		$this->modificadores['-j'] = '';
	        	$parametros = $this->get_servidor_defecto();
				$this->instalar($parametros);
        		break;
		}
	}

	/**
	 * Dada una revision, actualiza el proyecto, y convierte la base de negocio a esa revision   
	 */
	function actualizar_bd($revision = 'HEAD', $vers = 0)
	{
		if ($vers) {
			$this->vers = $vers;
		}

		// Se obtiene la revisión actual de la BD
		$rev_actual = $this->obtener_revision_bd();
		$this->manejador_interface->titulo('Revision Actual de la BD : ' . $rev_actual);

		// Se chequea que la revisión a cargar no sea menor a la actual (no se realizan migraciones hacia atrás)
		if (($revision != 'HEAD') && ($revision < $rev_actual)) {
			$this->manejador_interface->mensaje('Imposible cargar la revisión indicada. La revisión indicada es menor a la revisión actual de la BD.', true);
			return;
		}

		// Se actualiza la carpeta de scripts de actualización  del proyecto
		$svn = new toba_svn();
		$directorio = $this->get_dir('diferenciales');
		$this->manejador_interface->mensaje('Actualizando el directorio de Scripts de actualización  de la BD...', true);
		try {
			$resultado_update = $svn->update($directorio, $revision);
			$last_rev = $svn->get_revision($directorio);
		} catch (Exception $e) {
			$this->manejador_interface->mensaje($e->getMessage());
			return;
		}
		$this->manejador_interface->mensaje($resultado_update);

		if ($revision == 'HEAD') {
			$revision = $last_rev;
		}

		// Se chequea que la revisión a cargar este dentro del rango
		$revision = intval($revision);
		$last_rev = intval($last_rev);
		if (!(($revision >= $rev_actual) && ($revision <= $last_rev))) {
			$this->manejador_interface->mensaje('Imposible cargar la revisión indicada. Chequee la revisión del proyecto y servidor', true);
			return;
		}
		
		// Se chequea que el proyecto no está en la última  revisión (no es necesario regenerar)
		if ($rev_actual == $last_rev) {
			$this->manejador_interface->mensaje('La revisión de la base está actualizada a la ultima revisión del proyecto.', true);
			return;
		}

		$this->manejador_interface->titulo('Nro. revision a actualizar la BD : ' . $revision);

		$this->migrar_base_revisiones($rev_actual, $revision);
	}
	
	/**
	 * Crea la estructura de la base previa a la carga de los datos (objetos con orden negativo)
	 */
	function crear_estructura_predatos(toba_db $base)
	{
		$this->crear_lenguaje_procedural($base);
		foreach ($this->objetos as $objeto) {
			if ($objeto['orden'] < 0 && $objeto['cargar']) {
				foreach ($this->ddls as $ddl) {
					if ($ddl['cargar']) {
						$directorio = $this->get_dir('ddl').'/'.$ddl['dir'].'/'.$objeto['dir'];
						$archivos = false;
						if (is_dir ($directorio)) $archivos = scandir($directorio);
						$this->manejador_interface->mensaje('Creando '.$objeto['nombre'].' de '.$ddl['nombre'].' ', false);
						if ($archivos) {
							foreach($archivos as $archivo){
								$ruta = $directorio.'/'.$archivo;
								if($this->conf->archivo_valido($ruta)) {
									$this->manejador_interface->progreso_avanzar();
									$base->ejecutar_archivo($ruta);
								}	
							}
						} else {
							$this->manejador_interface->mensaje('(no hay) ', false);
						}	
						$this->manejador_interface->progreso_fin();
					}	
				}	
			}
		}	
	}

	/**
	 * Crea la estructura de la base posterior a la carga de los datos (objetos con orden positivo)
	 */
	function crear_estructura_posdatos(toba_db $base)
	{
		foreach ($this->objetos as $objeto) {
			if ($objeto['orden'] >= 0 && $objeto['cargar']) {
				foreach ($this->ddls as $ddl) {
					if ($ddl['cargar']) {
						$directorio = $this->get_dir('ddl').'/'.$ddl['dir'].'/'.$objeto['dir'];
						$archivos = false;
						if (is_dir ($directorio)) $archivos = scandir($directorio);
						$this->manejador_interface->mensaje('Creando '.$objeto['nombre'].' de '.$ddl['nombre'].' ', false);
						if ($archivos) {
							foreach($archivos as $archivo){
								$ruta = $directorio.'/'.$archivo;
								if($this->conf->archivo_valido($ruta)) {
									$this->manejador_interface->progreso_avanzar();
									$base->ejecutar_archivo($ruta);
								}	
							}
						} else {
							$this->manejador_interface->mensaje('(no hay) ', false);
						}	
						$this->manejador_interface->progreso_fin();
					}	
				}	
			}
		}	
	}

	/**
	 * Carga los datos del núcleo en la base y luego los de prueba si fueron seleccionados
	 */
	function cargar_datos(toba_db $base)
	{
		//Cargo los datos del núcleo
		foreach ($this->datos_nucleo as $nucleo) {
			$this->cargar_datos_nucleo($base,$nucleo,true);
		}	
		
		//Hay datos Custom ?
		if (isset($this->datos_custom) && $this->datos_custom != array()) {
			if (! array_key_exists('-j',$this->modificadores)) {//No usó el modificador -j
				//Cargo los datos custom con cargar == true
				//Cargo los juegos de datos customizados
				foreach ($this->datos_custom as $key => $juego_custom) {
					$this->cargar_datos_custom($base,$juego_custom,true);
				}
			} else { //Usó el modificador -j
				//Cargó valor ?
				$valoror = $this->modificadores['-j'];
				if (isset($valoror) && $valor != null) {
					if (! $this->existe_datos_custom ($valor)) {//No existe
						$this->manejador_interface->mensaje('No se encuentra el Juego de Datos personalizado solicitado "'.$valor.'"', false);
						exit;
					}
					//Cargo el conjunto de datos solicitado
					
					//Valido el juegos de datos customizado
					if (! $this->validar_datos_custom($valor)) {
						$this->manejador_interface->mensaje('Hay un error en la definición del Juego de Datos personalizado "'.$valor.'"', false);
						exit;
					}	
					
					$this->cargar_datos_custom($base,$valor);
					
				} else {//No seteó valor
					//Muestro lista para que elija
					/*
					$this->manejador_interface->dialogo_lista_opciones( $opciones, $texto, $multiple_seleccion = false, 
									$titulo = 'VALORES', $obligatorio = true, 
									$defecto = null, $defecto_texto = '' )
						*/			
					$seleccion = $this->manejador_interface->dialogo_lista_opciones( 
											$this->get_lista_datos_custom(), 
											'Elija el o los Juegos de Datos personalizados que desea cargar', 
											true,'Juego de Datos personalizados', true );

					//Cargo los Juegos de Datos CustomSeleccionados
					foreach ($seleccion as $juego_id) {
						$this->cargar_datos_custom($base,$juego_id);
					}
					
				} //Fin de No seteó valor
			} //Fin de //Usó el modificador -j
		} else {//No hay datos Custom
			
			if (! array_key_exists('-j',$this->modificadores)) {//No usó el modificador -j
				//Carga Datos de prueba con cargar = true
				foreach ($this->datos_prueba as $juego) {
					$this->cargar_datos_prueba($base,$juego,true);		
				}
			} else { //Usó el modificador -j
				//Cargó valor ?
				$valor = $this->modificadores['-j'];
				if (isset($valor) && $valor != null) {
					if (! $this->existe_datos_prueba($valor)) {//No existe
						$this->manejador_interface->mensaje('No se encuentra el Juego de Datos de Prueba solicitado "'.$valor.'"', false);
						exit;
					}
					//Cargo juego de datos de prueba seteado por lí­nea de comando
					$this->cargar_datos_prueba($base,$valor);
			
				} else {//No seteó valor
						//Muestro lista para que elija
						$lista_datos_prueba = $this->get_lista_datos_prueba();
						$seleccion = $this->manejador_interface->dialogo_lista_opciones( 
												$lista_datos_prueba,//Pasar array asociativo clave => valor 
												'Elija el o los Juegos de Datos de Prueba que desea cargar', 
												true, 'Juego de Datos de Prueba', true, array_keys($lista_datos_prueba), 'todos');

						//Cargo los Juegos de datos seleccionados
						foreach ($seleccion as $juego_id) {
							$this->cargar_datos_prueba($base,$juego_id);
						}

				} //Fin de No seteó valor
			} //Fin de Usó el modificador -j
		}	//Fin de No hay datos Custom
	}
	
	// Exporta los datos de la tabla
	function exportar_tabla_inserts($tabla,$archivo = null)
	{
		//Se dumpean los datos de la tabla dada
		$comando = "pg_dump -a -i -t $tabla -h {$this->parametros['profile']} -U {$this->parametros['usuario']} {$this->parametros['base']}";

		// Se exporta la password por si la pide
		if ($parametros['clave'] != '') {
			$clave = 'PGPASSWORD='.$parametros['clave'];
			// Se setea el pass en la variable de entorno
			putenv($clave);
		}

		$salida = array();
		exec($comando, $salida, $exito);

		// Se resetea el pass de la variable de entorno por seguridad
		putenv('PGPASSWORD');

		// chequea si hubo error
		if ($exito > 0) {
			throw new guarani_error('No se pudo exportar correctamente los datos');
		}

		// guarda en el archivo si vino como parámetro
		if($archivo){
			$arch = fopen($archivo,"w");
			fputs($arch,implode("\n",$salida));
			fclose($arch);
		}

		return $salida;
	}
		
	function regenerar_modelo_datos($base, $id_def_base)
	{
		if (! $this->permitir_exportar_modelo) {
			$this->manejador_interface->mensaje('Ya existe un modelo de datos del proyecto cargado previamente.');
			return;
		}
		$this->manejador_interface->enter();
		$reemplazar = $this->manejador_interface->dialogo_simple("Ya existe la base de datos, ".
							"Â¿Desea reemplazar el esquema (". $this->schema_modelo .") donde se ecuentra la base? (borra el esquema y lo vuelve a generar)", 's');
		if (! $reemplazar) {
			return;
		}
		$exportar = $this->manejador_interface->dialogo_simple("Antes de borrar el esquema/base. Â¿Desea exportar su contenido actual?", 's');
		if ($exportar) {
			$build_base = $this->obtener_revision_bd();
			//Se Resguarda la base antes por si hay error
			$backups = $this->exportar_base($build_base);
			if (! $backups) {
				if (! $this->manejador_interface->dialogo_simple("No pudo resguardarse la base actual. Desea continuar ?", 'n')) {
					return;	
				}
			} 
		}

		//--- Borra y Crea el esquema de Guarani3. ----------------------------------------------------
		//--- Deja la Base de Datos por si hay otros esquemas... 
		if ($base->existe_schema($this->schema_modelo)) {
			$this->manejador_interface->mensaje('Eliminando Esquema...', false);
			$base->borrar_schema($this->schema_modelo);
		}
		// Crea el esquema de la base de datos de negocio.
		$this->manejador_interface->mensaje('Creando Esquema...', false);
		$base->crear_schema($this->schema_modelo);
		//--- Fin Creacion Esquema de Guarani ---------------------------------------------------------

		
		$this->manejador_interface->progreso_avanzar();
		$this->manejador_interface->progreso_fin();		
		
		//--- Carga nuevamente el modelo de datos
		$base = $this->instalacion->conectar_base($id_def_base);
		$this->cargar_modelo_datos($base);	
	}		
	
	/**
	* Genera los archivos de cada elemento de la base que se encuentran en el archivo de generación en las carpetas correspondientes
	*/
	function parsear_sql_base($eliminar_todo=false)
	{
		$nucleo = 'nucleo';
		$ddl = $this->ddls[$nucleo];
		
		if ($eliminar_todo) {
			//Borrar archivos anteriores
			$this->manejador_interface->mensaje('Eliminando archivos anteriores de creación  de elementos de la base');

			foreach ($this->objetos as $objeto) {
					if ($objeto['parsear']) {
						$directorio = $this->get_dir('ddl').'/'.$ddl['dir'].'/'.$objeto['dir'];
						if (is_dir ($directorio)) $archivos = scandir($directorio);
						$this->manejador_interface->mensaje('Eliminando '.$objeto['nombre'].' de '.$ddl['nombre'].' ',false);
						$this->manejador_interface->progreso_avanzar();
						if ($archivos) {
							foreach($archivos as $archivo){
								$ruta = $directorio.'/'.$archivo;
								if($this->conf->archivo_valido($ruta)) {
									$this->manejador_interface->progreso_avanzar();
									unlink($ruta);
								}	
							}
						} 	
						$this->manejador_interface->progreso_fin();
					}	
			}
		}	

		$this->manejador_interface->mensaje('Generando nuevos archivos',false);
		$modelo = $this->get_dir('modelo').'/'.$nucleo.'.sql';
		if (! is_file($modelo)) {
			$this->manejador_interface->mensaje('No se encuentra el archivo de generación de la base "'.$modelo.'"');
		} else {
			new parserSQL($modelo , $this->get_dir('ddl').'/'.$ddl['dir'] , $this->objetos, $this->manejador_interface);
			$this->manejador_interface->progreso_avanzar();
			$this->manejador_interface->progreso_fin();		
		}	
	}
	
	
	private function set_modificadores($modificadores) {
		$this->modificadores = $modificadores;
	}
	
	function existe_datos_prueba($juego) {
		if (! array_key_exists($juego,$this->datos_prueba)) return false;
		return true;
	}
	
	function get_lista_datos_prueba() 
	{
		$lista = array();
		foreach ($this->datos_prueba as $key => $datos) {
			$lista = array_merge($lista, array($key => $datos['nombre']));
		}
		return $lista;
	}
	
	function get_lista_datos_custom() 
	{
		$lista = array();
		foreach ($this->datos_custom as $key => $datos) {
			$lista = array_merge($lista, array($key => $datos['nombre']));
		}
		return $lista;
	}
	
	function cargar_datos_nucleo($base,$nucleo,$validar=false)
	{
		if (is_string($nucleo)) $nucleo = $this->datos_nucleo[$nucleo];
		if ((! $validar) || $nucleo['cargar']) {
				$directorio = $this->get_dir('datos').'/'.$nucleo['dir'];
				$archivos = scandir($directorio);
				$this->manejador_interface->mensaje('Cargando Datos de "'.$nucleo['nombre'].'" ', false);
				foreach($archivos as $archivo){
					$ruta = $directorio.'/'.$archivo;
					if($this->conf->archivo_valido($ruta)) {
						$this->manejador_interface->progreso_avanzar();
						$base->ejecutar_archivo($ruta);
					}	
				}
				$this->manejador_interface->progreso_fin();
		}	
	}
	
	function cargar_datos_prueba($base,$juego,$validar=false)
	{
		if (is_string($juego)) $juego = $this->datos_prueba[$juego];
		if ((! $validar) || $juego['cargar']) {
				$directorio = $this->get_dir('datos').'/'.$juego['dir'];
				$archivos = scandir($directorio);
				$this->manejador_interface->mensaje('Cargando Juego de Datos de Prueba "'.$juego['nombre'].'" ', false);
				foreach($archivos as $archivo){
					$ruta = $directorio.'/'.$archivo;
					if($this->conf->archivo_valido($ruta)) {
						$this->manejador_interface->progreso_avanzar();
						$base->ejecutar_archivo($ruta);				
					}	
				}
				$this->manejador_interface->progreso_fin();
		}	
	}
	
	
	function cargar_datos_custom($base,$juego,$validar=false)
	{
		//Validar en true obliga a que esté seteado para cargar
		if (is_string($juego)) $juego = $this->datos_custom[$juego];
		if ((! $validar) || $juego['cargar']) {
			$this->manejador_interface->mensaje('Cargando Juego de Datos personalizado "'.$juego['nombre'].'" ');
			if (! $this->validar_datos_custom($juego)) {
				$this->manejador_interface->mensaje('Hay un error en la definición del Juego de Datos personalizado "'.$key.'"', false);
				exit;
			}
			foreach ($juego['datos'] as $juego_prueba) {
					$this->cargar_datos_prueba($base,$juego_prueba,false);
			}		
		}
	}		
	
	private function existe_datos_custom($juego) 
	{
		if (! array_key_exists($juego,$this->datos_custom)) return false;
		return true;
	}
	
	private function validar_datos_custom($juego) 
	{
		if (is_string($juego)) $juego = $this->datos_custom[$juego];
		foreach ($juego['datos'] as $datos_prueba) {
			if (! array_key_exists($datos_prueba,$this->datos_prueba)) return false;
		}
		return true;
	}
	
	function get_dir($cual) 
	{
		return $this->proyecto->get_dir().'/'.$this->conf->get_dir($cual);
	}
	
	private function get_dir_ddl($cual) 
	{
		return $this->proyecto->get_dir().'/'.$this->conf->get_dir_ddl($cual);
	}
	
	private function get_dir_nucleo($cual) 
	{
		return $this->proyecto->get_dir().'/'.$this->conf->get_dir_datos_nucleo($cual);
	}
	
	private function get_dir_prueba($cual) 
	{
		return $this->proyecto->get_dir().'/'.$this->conf->get_dir_datos_prueba($cual);
	}
		
	function existe_base_datos($base_name=null) 
	{
		if (!$base_name) {
			$id_base = $this->get_id_def_base();
			$base_name = $this->instalacion->get_parametros_base($id_base);
		}	
		return $this->instalacion->existe_base_datos($base_name);
	}
	
	function exportar_base($version)
	{
		$archivos = array();
		$id_def_base = $this->get_id_def_base();
		//-- Esquema principal
		$archivo = $this->get_dir('tmp').'/datos_locales_'.$version.'.sql';
		if (! is_dir($this->get_dir('tmp'))) mkdir($this->get_dir('tmp'));
		$this->manejador_interface->mensaje('Exportando la base de negocio existente');
		$this->manejador_interface->mensaje('en '.$archivo);
		if (!$this->exportar_esquema_base($id_def_base, $archivo, false, $this->schema_modelo)) {
			return false;
		}
		$archivos[$this->schema_modelo] = $archivo; 
		//-- Esquema auditoria
		$archivo = $this->get_dir('bd').'/datos_auditoria_'.$version.'.sql';
		$this->manejador_interface->mensaje('Exportando el esquema de auditorí­a');
		$this->exportar_esquema_base($id_def_base, $archivo, false, $this->schema_auditoria);
		if (file_exists($archivo)) $archivos[$this->schema_auditoria] = $archivo; 
		return $archivos;
	}
	
	function resguardar_archivo($archivo,$idx=0)
	{
		$max = 5;
		if ($idx === $max) return;
		$resguardo = $archivo.'.'.$idx.'.old';
		if ($idx == 0) {
			$resguardo_ant = $archivo;
		} else {
			$resguardo_ant = $archivo.'.'.($idx-1).'.old';	
		}	
		if (file_exists($resguardo) || $idx === ($max - 1)) {
			$this->resguardar_archivo($archivo,$idx + 1);
		}
		if (file_exists($resguardo)) unlink($resguardo);
		rename($resguardo_ant, $resguardo);
		//copy($resguardo_ant, $resguardo);
		return;
	}
	
	function exportar_esquema_base($id_def_base, $archivo, $obligatorio, $esquema=null)
	{
		$parametros = $this->instalacion->get_parametros_base($id_def_base);
		if (file_exists($archivo)) {
			$this->resguardar_archivo($archivo);
		}
		$esquema = (! is_null($esquema)) ? " -n $esquema " : '';
		if (!isset ($parametros['puerto'])) $parametros['puerto'] = '5432';
		$comando = "pg_dump -Ox --inserts $esquema -h {$parametros['profile']} -U {$parametros['usuario']} -p {$parametros['puerto']} -f \"$archivo\" {$parametros['base']}";
		if ($parametros['clave'] != '') {
			$clave = 'PGPASSWORD='.$parametros['clave'];
			// Se setea el pass en la variable de entorno
			putenv($clave);
		}
		$this->manejador_interface->mensaje("Ejecutando: $comando");
		$salida = array();
		echo exec($comando, $salida, $exito);
		echo implode("\n", $salida);
		// Se resetea el pass de la variable de entorno por seguridad
		putenv('PGPASSWORD');
		if ($obligatorio && ($exito > 0)) {
			throw new guarani_error('No se pudieron exportar correctamente los datos');
		}
		return ($exito == 0);
	}	

	function importar_base($id_def_base, $archivo, $obligatorio, $mostrar_salida=false)
	{
		$log = $archivo.'.log';
		//psql  -U postgres  -c "ALTER DATABASE negocio SET DateStyle=dmy;" portalg3
		$parametros = $this->instalacion->get_parametros_base($id_def_base);
		if (!isset($parametros['puerto'])) $parametros['puerto'] = '5432';
		$comando = "psql -L {$log} -q -n -h {$parametros['profile']} -U {$parametros['usuario']} -p {$parametros['puerto']} -f \"$archivo\" {$parametros['base']}";
		if ($parametros['clave'] != '') {
			$clave = 'PGPASSWORD='.$parametros['clave'];
			// Se setea el pass en la variable de entorno
			putenv($clave);
		}
		$this->manejador_interface->mensaje("Los resultados se guardarán en: $log");
		$salida = array();
		echo exec($comando, $salida, $exito);
		if ($mostrar_salida) echo implode("\n", $salida);
		// Se resetea el pass de la variable de entorno por seguridad
		putenv('PGPASSWORD');
		if ($obligatorio && $exito > 0) {
			throw new guarani_error('No se pudieron importar correctamente los datos');
		}
		return ($exito == 0);
	}
	
	// Obtiene la lista de scripts a correr entre 2 revisiones svn
	function get_scripts_conversion($desde, $hasta)
	{
                // Si esta seteada la version para una conversion especifica obtengo el arreglo de 
                // scripts fijos de el archivo de configuracion. Sino obtengo la lista de scripts por svn
                $scripts = array();
                $v = $this->vers;
                if (($v) && (isset($this->conversiones[$v]))) {
                    $conv = $this->conversiones[$v];
                    $directorio = $this->get_dir('diferenciales');                     
                    for ($i=0;$i<guarani_arreglos::count($conv);$i++) { 
                        $scripts[$i] = $directorio . '/' . $conv[$i]; 
                    }                     
                } else {
                    //Obtengo la lista de scripts a correr entre revisiones
                    $directorio = $this->get_dir('diferenciales');
                    $cmd = "svn diff -r $desde:$hasta  $directorio --no-diff-deleted --summarize --xml --non-interactive --depth=immediates";
                    try {		
                            $error = '';
                            $xml = '';
                            toba::logger()->info("Ejecutando: ".$cmd."\n");
                            toba_manejador_archivos::ejecutar($cmd, $xml, $error);
                            if ($error != '') {
                                    toba::logger()->error($error);
                                    return;
                            }
                            $xml = simplexml_load_string($xml);
                            if (isset($xml->paths->path)) {
                                    foreach ($xml->paths->path as $paty) {
                                            //Atributos
                                            $atts = $paty->attributes();
                                            //Si es un archivo sql lo almaceno en la lista de scripts a correr
                                            if ((((string) $atts['kind']) == "file") && (((string) $atts['item']) != "deleted")) {
                                                    $scripts[] = (string) $paty;
                                            }
                                    }
                            }
                    } catch(toba_error $e) {
                            toba::logger()->error($e);
                            return;
                    }
                }
		return $scripts;
	}

	// Obtiene la lista de scripts de auditoria a correr 
	function get_scripts_conversion_auditoria($desde, $hasta)
	{
            //Obtengo la lista de scripts a correr entre revisiones
            $directorio = $this->get_dir('auditoria');
            $scripts = array();
            // Si esta seteada la version para una conversion especifica obtengo el arreglo de 
            // scripts fijos de el archivo de configuracion de auditoria. A futuro se cambiara este esquema 
            $v = $this->vers;
            if (($v) && (isset($this->conversiones[$v . '_auditoria']))) {
                    $scripts = $this->conversiones[$v . '_auditoria'];
                    for ($i=0;$i<guarani_arreglos::count($scripts);$i++) { 
                        $scripts[$i] = $directorio . '/' . $scripts[$i]; 
                    }                                     
            } else {
                //Obtengo la lista de scripts a correr entre revisiones
                $cmd = "svn diff -r $desde:$hasta  $directorio --no-diff-deleted --summarize --xml --non-interactive --depth=immediates";
                try {		
                        $error = '';
                        $xml = '';
                        toba::logger()->info("Ejecutando: ".$cmd."\n");
                        toba_manejador_archivos::ejecutar($cmd, $xml, $error);
                        if ($error != '') {
                                toba::logger()->error($error);
                                return;
                        }
                        $xml = simplexml_load_string($xml);
                        if (isset($xml->paths->path)) {
                                foreach ($xml->paths->path as $paty) {
                                        //Atributos
                                        $atts = $paty->attributes();
                                        //Si es un archivo sql lo almaceno en la lista de scripts a correr
                                        if ((((string) $atts['kind']) == "file") && (((string) $atts['item']) != "deleted")) {
                                                $scripts[] = (string) $paty;
                                        }
                                }
                        }
                } catch(toba_error $e) {
                        toba::logger()->error($e);
                        return;
                }
            }
            return $scripts;
	}
        
	// Obtiene la revision del proyecto 
	protected function obtener_revision_proyecto()
	{
		$svn = new toba_svn();
		$path = $this->proyecto->get_dir();
		
		try {
			$rev_actual = $svn->get_revision($path);
		} catch (Exception $e) {
			//Si falla el svn o no se puede determinar la revision del mismo, se asume que la BD está en
			//la rev. 6949, la previa al comienzo de generacion de scripts de actualizacion
			$rev_actual = 6949;
			$this->manejador_interface->mensaje("No se pudo determinar la revisión (SVN) del proyecto se asume: 6949");
			/*
			$this->manejador_interface->mensaje($e->getMessage());	
			return;
			*/
		}
		return intval($rev_actual);
	}
	
	protected function obtener_revision_bd() 
	{
		$base = $this->get_base();

		$res = $base->consultar ("SELECT  CAST(observaciones as int) as rev 
									FROM app_versiones_base
								 	ORDER BY rev DESC");

		//Si no se puede determinar la revision de la BD, se asume que la BD está en
		//la rev. 6949, la previa al comienzo de generacion de scripts de actualización 
		$rev = 6949;
		if (isset($res[0]['rev'])) {
			if (!is_null($res[0]['rev'])) {
				$rev = intval($res[0]['rev']);
			}
		} 
		return $rev;
	}

	function get_version_app_base()
	{
		$base = $this->get_base();
		$base->set_schema($this->schema_modelo);
		$sql = "SELECT		trim(version_app) as version_actual 
				FROM		app_versiones_base
				ORDER BY	id_conversion DESC
				LIMIT		1
		";
		$datos = $base->consultar_fila($sql);
		return $datos['version_actual'];
	}

	function get_datos_app_versiones()
	{
		$base = $this->get_base();
		$base->set_schema($this->schema_modelo);
		$sql = "SELECT	version_app,
						fecha_actualizacion 
				FROM app_versiones 
				ORDER BY fecha_actualizacion DESC LIMIT 5";
		$datos = $base->consultar($sql);
		return $datos;
	}

	function get_datos_app_versiones_base()
	{
		$base = $this->get_base();
		$base->set_schema($this->schema_modelo);
		$sql = "SELECT	*
				FROM app_versiones_base
				ORDER BY 1 DESC LIMIT 5";
		$datos = $base->consultar($sql);
		return $datos;
	}
	
	/**
	 * Retorna el conjunto de versiones para realizar migraciones entre las mismas desde la clave 'historico_versiones' de proyecto.ini
	 * @return array
	 */	
	function get_historico_versiones()
	{
		if (file_exists($this->proyecto->get_dir().'/proyecto.ini')) {
			$ini = new toba_ini($this->proyecto->get_dir().'/proyecto.ini');
                        return $ini->get('proyecto', 'historico_versiones');
		}
		return array();
	}

	function publicar_recursos_pers()
	{
		$this->proyecto->publicar_pers();
	}
	
	function cambiar_esquema($esquema)
	{
		$cambiar_auditoria = false;
		$base_audit = $this->proyecto->get_db_negocio();
		$auditoria = $base_audit->get_manejador_auditoria($this->schema_modelo, $this->schema_auditoria, $this->schema_toba);
		if ((!is_null($auditoria)) && ($auditoria->existe())) {
			$cambiar_auditoria = true;
		}
		$base = $this->get_base();
		$base->abrir_transaccion();
		try {
				$msj = "Cambiando Esquema 'public' a '$esquema'";
				$this->manejador_interface->mensaje($msj, true);
				toba_logger::instancia()->debug($msj);
				$this->manejador_interface->progreso_avanzar();
				$base->renombrar_schema('public', $esquema);
				$this->manejador_interface->progreso_fin();

				if ($cambiar_auditoria) {
					$msj = "Cambiando Esquema 'public_auditoria' a '$esquema" . "_auditoria'";
					$this->manejador_interface->mensaje($msj, true);
					toba_logger::instancia()->debug($msj);
					$this->manejador_interface->progreso_avanzar();
					$base->renombrar_schema('public_auditoria', $esquema.'_auditoria');
					$this->manejador_interface->progreso_fin();
				}

				$msj = "Creando Esquema 'public' vacio";
				$this->manejador_interface->mensaje($msj, true);
				toba_logger::instancia()->debug($msj);
				$this->manejador_interface->progreso_avanzar();
				$base->crear_schema('public');
				$this->manejador_interface->progreso_fin();

		} catch (toba_error $e) {
			$base->abortar_transaccion();
			echo $e->getMessage();
		}
			
		$base->cerrar_transaccion();

		// Cambiar bases.ini
		$inst = $this->get_instalacion();
		$id_base = $this->get_id_def_base();
		$info_db = $inst->get_parametros_base($id_base);
		$nuevos_parametros = array('schema' => $esquema);
		$info_db = array_merge($info_db, $nuevos_parametros);				
		$inst->actualizar_db($id_base, $info_db);                
	}
        
	protected function actualizar_tabla_conceptos() 
	{
		$base = $this->get_base();

		$base->abrir_transaccion();
		
		$msj = 'Actualizando Tabla de Conceptos';
		$this->manejador_interface->mensaje($msj, true);
		toba_logger::instancia()->debug($msj);

		try {
			// Borro la tabla de conceptos
			$msj = ' - Borro la tabla de Conceptos';
			toba_logger::instancia()->debug($msj);
			$base->ejecutar ("DELETE FROM adm_conceptos;");
			
			// Actualizo la tabla
			$nucleo = $this->datos_nucleo['nucleo'];
			// Archivo Tabla de Conceptos
			$archivo = $this->get_dir('datos').'/'.$nucleo['dir'].'/adm_conceptos.dca';
			$msj = ' - Inserto todos los campos nuevamente';
			toba_logger::instancia()->debug($msj);
			$base->ejecutar_archivo($archivo);
		} catch (toba_error $e) {
			$base->abortar_transaccion();
			echo $e->getMessage();
		}
			
		$base->cerrar_transaccion();
	}
	
	protected function actualizar_permisos_operaciones() 
	{
		$base = $this->get_base();
		
		$base->abrir_transaccion();
		
		try {
			// Borro la tabla de permisos
			$msj = 'Borro la tabla de Permisos y vuelvo a insertar todos los permisos para las operaciones';
			toba_logger::instancia()->debug($msj);
			$base->ejecutar ("DELETE FROM adm_permisos_db;");
	
			$nucleo = $this->datos_nucleo['nucleo_permisos'];
			$directorio = $this->get_dir('datos').'/'.$nucleo['dir'];
			$archivos = scandir($directorio);
			$this->manejador_interface->mensaje('Cargando '.$nucleo['nombre'], false);
			foreach($archivos as $archivo){
				$ruta = $directorio.'/'.$archivo;
				if($this->conf->archivo_valido($ruta)) {
					$this->manejador_interface->progreso_avanzar();
					$base->ejecutar_archivo($ruta);
				}	
			}
			$this->manejador_interface->progreso_fin();
		} catch (toba_error $e) {
			$base->abortar_transaccion();
			echo $e->getMessage();
		}
			
		$base->cerrar_transaccion();
	}
	
	
	function generar_autoload_clases_nuevas($parametros)
	{
		/// Autoload del proyecto y pers
		$proyecto = $this->get_proyecto();
		$id_proyecto = $proyecto->get_id();
		$param = array();
		$param_consultas = array(
			'archivo_salida' => 'guarani_clases_consultas_nuevas.php',
			'path_salida' => $proyecto->get_dir() . '/' . toba_personalizacion::dir_personalizacion . '/php',
			'solo_personalizados' => true
		);
		$extractor_consultas = new guarani_extractor_clases_consultas($param_consultas, $this->get_base());
		$extractor_consultas->generar();
		
		// Autoload personalizacion Gestión
		$montaje_personalizacion = $proyecto->get_dir() . '/' . toba_personalizacion::dir_personalizacion . '/php';
		$param[$montaje_personalizacion] = array(
			'archivo_salida' => $id_proyecto . '_autoload_clases_nuevas.php',
			'extras' => array(),
		);
		
		$extractor = new toba_extractor_clases($param);
		$extractor->generar();
		
		$clases_repetidas = $extractor->get_clases_repetidas();
		$pms_no_encontrados = $extractor->get_pms_no_encontrados();
		
		if (isset($parametros['-v'])) {
			if (guarani_arreglos::count($pms_no_encontrados) > 0) {
				$this->manejador_interface->separador('Puntos de montaje no encontrados');
			}

			if (guarani_arreglos::count($clases_repetidas) > 0) {
				foreach ($clases_repetidas as $montaje => $clase) {
					$this->manejador_interface->separador("Clases repetidas de '[$montaje]'");

					foreach ($clase as $key => $paths) {
						$this->manejador_interface->mensaje("\n[$key]");
						foreach ($paths as $path) {
							$this->manejador_interface->mensaje($path, true);
						}
					}
				}
			}
		} else {
			$hubo_error = false;
			if (guarani_arreglos::count($pms_no_encontrados) > 0) {
				$hubo_error = true;
				$this->manejador_interface->mensaje("Hubo puntos de montaje no encontrados.");
			}

			if (guarani_arreglos::count($clases_repetidas) > 0) {
				$hubo_error = true;
				$this->manejador_interface->mensaje("Existen clases repetidas.");
			}

			if ($hubo_error) {
				$this->manejador_interface->mensaje('Ejecute el comando con la opción  -v para más información.');
			} else {
				$this->manejador_interface->mensaje("Se generaron los archivos correctamente");
			}
		}
	}
	
	function generar_autoloads($parametros)
	{
		/// Autoload del proyecto y pers
		$proyecto = $this->get_proyecto();
		$id_proyecto = $proyecto->get_id();
		$param = array();
		
		// Autoload Consultas se hace antes asi luego aparece en el autoload del proyecto
		$param_consultas = array(
			'archivo_salida' => 'g3w_clases_consultas.php',
			'path_salida' => $proyecto->get_dir().'/php/nucleo'
		);

		$extractor_consultas = new guarani_extractor_clases_consultas($param_consultas, $this->get_base());
		$extractor_consultas->generar();
		
	
		// Autoload Gestión
		$montaje_proyecto = $proyecto->get_dir().'/php';
		$param[$montaje_proyecto] = array(
				'archivo_salida' => $id_proyecto . '_autoload.php',
				'dirs_excluidos' => array(),
				'extras' => array(),
		);
		
		// Autoload personalizacion Gestión
		$montaje_personalizacion = $proyecto->get_dir() . '/' . toba_personalizacion::dir_personalizacion . '/php';
		$param[$montaje_personalizacion] = array(
			'archivo_salida' => $id_proyecto . '_pers_autoload.php',
			'dirs_excluidos' => array(),
			'extras' => array(),
		);

		// Autoload Para 3w
		$montaje_proyecto_3w = $proyecto->get_dir().'/php/nucleo';
		$param[$montaje_proyecto_3w] = array(
			'archivo_salida' => 'g3w_autoload.php',
			'dirs_excluidos' => array(),
			'extras' => array('guarani_cn' => '../guarani_cn.php'),
		);

		
		$extractor = new toba_extractor_clases($param);
		$extractor->generar();
		
		$clases_repetidas = $extractor->get_clases_repetidas();
		$pms_no_encontrados = $extractor->get_pms_no_encontrados();
		
		if (isset($parametros['-v'])) {
			if (guarani_arreglos::count($pms_no_encontrados) > 0) {
				$this->manejador_interface->separador('Puntos de montaje no encontrados');
			}

			if (guarani_arreglos::count($clases_repetidas) > 0) {
				foreach ($clases_repetidas as $montaje => $clase) {
					$this->manejador_interface->separador("Clases repetidas de '[$montaje]'");

					foreach ($clase as $key => $paths) {
						$this->manejador_interface->mensaje("\n[$key]");
						foreach ($paths as $path) {
							$this->manejador_interface->mensaje($path, true);
						}
					}
				}
			}
		} else {
			$hubo_error = false;
			if (guarani_arreglos::count($pms_no_encontrados) > 0) {
				$hubo_error = true;
				$this->manejador_interface->mensaje("Hubo puntos de montaje no encontrados.");
			}

			if (guarani_arreglos::count($clases_repetidas) > 0) {
				$hubo_error = true;
				$this->manejador_interface->mensaje("Existen clases repetidas.");
			}

			if ($hubo_error) {
				$this->manejador_interface->mensaje('Ejecute el comando con la opción  -v para más información.');
			} else {
				$this->manejador_interface->mensaje("Se generaron los archivos correctamente");
			}
		}
	}

	function migrar_claves()
	{
		$base = $this->get_base();
		try {
			require_once(dirname(__FILE__).'/../3ros/bcrypt.php');
			$enc = new bcrypt(10);
			$base->abrir_transaccion();
			$this->manejador_interface->mensaje('Migrando las contraseñas...', true);
			$this->manejador_interface->enter();
			$sql = "SELECT usuario, clave FROM mdp_personas WHERE clave NOT LIKE ('$2a%')";
			$datos = $base->consultar($sql);
			foreach ($datos as $dato) {
				$clave = $enc->hash($dato['clave']);
				$sql = "UPDATE mdp_personas SET clave = '$clave' WHERE usuario = '{$dato['usuario']}'\n";
				$base->ejecutar($sql);
				$this->manejador_interface->progreso_avanzar();
			}
			$this->manejador_interface->enter();
			$this->manejador_interface->enter();
			$this->manejador_interface->mensaje('La migración fue realizada con éxito.', true);
			$this->manejador_interface->enter();
			$base->cerrar_transaccion();
		} catch (Exception $e) {
			$base->abortar_transaccion();
			$this->manejador_interface->mensaje($e->getMessage());
		}
	}
	
	function setear_pers($iniciar = true, $schema_original = 'desarrollo', $schema_pers = 'toba_guarani_pers')
	{
		$base = $this->get_base();
		$base->set_schema($this->schema_modelo);
		$valor_sch = ($iniciar) ? $schema_pers : $schema_original;
		$sql = "UPDATE par_configuraciones SET valor = '$valor_sch' WHERE nombre = 'schema_toba';";
		$base->ejecutar($sql);
		$this->manejador_interface->enter();
	}

	function instancia_exp_local()
	{
		$this->instancia->exportar_local();
		$this->manejador_interface->enter();
	}
	
	function info_version()
	{		
		$v_bd = $this->get_version_app_base();
		if (!$v_bd) {
			$this->manejador_interface->mensaje('No se pudo determinar la versión de la base del proyecto local.', true);
			exit;
		} else {
			$v_bd = new toba_version($v_bd);
		}
		
		$v_proy = $this->get_version_actual();
		
		$this->manejador_interface->titulo( "Informacion sobre SIU-GUARANI 3");
		$this->manejador_interface->mensaje("Version de la aplicacion: $v_proy \n");
		$this->manejador_interface->mensaje("Version de la BD: $v_bd \n");	
		try {
			$svn = new toba_svn();		
			$path = $this->proyecto->get_dir();
			$last_rev = $svn->get_revision($path);
		} catch (Exception $e) {
			$this->manejador_interface->mensaje($e->getMessage());	
			return;
		}
		$this->manejador_interface->mensaje("Revision SVN: $last_rev");
		
		$this->manejador_interface->separador();
		
		// DATOS app_versiones
		$this->manejador_interface->subtitulo('TABLA app_versiones');
		$this->manejador_interface->tabla( $this->get_datos_app_versiones() , array( 'Version', 'Fecha') );

		$this->manejador_interface->separador();
		
		// DATOS app_versiones_base
		$this->manejador_interface->subtitulo('TABLA app_versiones_base');
		$this->manejador_interface->tabla( $this->get_datos_app_versiones_base() , array('ID', 'Version', 'Build', 'Script Corrido' , 'Fecha', 'Obs.') );
	}
        
    //Toma el manejo del script y dumpea los esquemas de negocio y de auditoria si es que el mismo lo posee
    function preparar_base_a_anonimizar($version, $parametros) 
    {   		
		$esquema = "-n ".$this->schema_modelo;
		$solo_negocio = true;
		if (is_array($parametros) && !empty($parametros)) {
			if (isset($parametros['-t'])) {				
				$solo_negocio = false;
			} else {
				echo "El parametro -t (todos) es el unico valido";
				return;
			}
		}
		
		$id_def_base = $this->get_id_def_base();		
		$archivo = $this->get_dir('tmp').'/'.'backup_anonimizado_'.$version.'.sql';
		if (! is_dir($this->get_dir('tmp'))) mkdir($this->get_dir('tmp'));

        if (file_exists($archivo)) {
            $this->resguardar_archivo($archivo);
        }
		$this->manejador_interface->mensaje('Exportando la base anonimizada');		
        $base = $this->get_base();
        //Agrega las funciones y tablas que ayudan en el proceso de anonimizacion                
        $this->ejecutar_mask($base);
        // Esquema principal
		if (!$this->exportar_anonimizacion($id_def_base, $archivo, false, $esquema)) {
			return false;
		}
		
		//Si no se pasa el parametro -all se anonimiza también el esquema de auditoria y personalizacion
		if (!$solo_negocio) {
			//Si existe auditoria tambien se la anonimiza
			$auditoria = $base->get_manejador_auditoria($this->schema_modelo, $this->schema_auditoria);
			if ((!is_null($auditoria)) && ($auditoria->existe())) {
				$this->manejador_interface->mensaje("\n Exportando el esquema de auditoria anonimizada");
				$esquema = " -n ".$this->schema_auditoria;
				if (!$this->exportar_anonimizacion($id_def_base, $archivo, false, $esquema))
						return false;
			}
			//Si hay esquema de personalizacion tambien lo va a exportar en el mismo archivo
			$id_def_base = $this->proyecto->construir_id_def_base('personalizacion');
			$parametros = $this->instalacion->get_parametros_base($id_def_base);
			$esquema = " -n ".$parametros['schema'];
			if ($this->instalacion->existe_base_datos_definida($id_def_base)) {        
				$this->manejador_interface->mensaje("\n Exportando el esquema de personalizacion");				
				if (!$this->exportar_anonimizacion($id_def_base, $archivo, false, $esquema))
					return false;
			}
		}
        //Elimina el schema mask
        $base->ejecutar_archivo($this->proyecto->get_dir() ."/BD/Anonimizacion/sql/__drop_mask.sql");
		return $this->manejador_interface->mensaje("\n La base fue correctamente anonimizada y exportada");
    }
                
    function ejecutar_mask($base) 
    {
        $archivos = array();
        //Me paro en el directorio donde estan los sql.
        //recorro el directorio y lo guardo en un array. Luego los ordeno y ejecuto los sql.                
        $dir = $this->proyecto->get_dir() ."/BD/Anonimizacion/sql";
        $open_dir = opendir($dir);
        while ($archivo = readdir($open_dir)) {  
            //Si es alguna carpeta o archivo que se haya abierto no lo guardo
            if (!($archivo== "." || $archivo== ".." || strpos($archivo, '~') || $archivo == '__drop_mask.sql' || $archivo == '.svn')){ 
                $archivos[$archivo] = $archivo;
            }
        }   
        ksort ($archivos);                
        foreach ($archivos as $archivo) {                    
            $base->ejecutar_archivo($dir.'/'.$archivo);
        }                
    }
        
    function exportar_anonimizacion($id_def_base, $archivo, $obligatorio, $esquema = null) 
    {
        $parametros = $this->instalacion->get_parametros_base($id_def_base);
        if (!isset ($parametros['puerto'])) $parametros['puerto'] = '5432';
        $path_bin = $this->proyecto->get_dir().'/BD/Anonimizacion/bin/';
        //Preparo el comando para correrlo con el script que anonimiza
        $comando = "pg_dump -O $esquema -h {$parametros['profile']} -U {$parametros['usuario']} -p {$parametros['puerto']} {$parametros['base']} | ".$path_bin."mask >> $archivo";
        //Para que corra el programa que anonimiza debe tener en las 
        //variables de entorno los parametros para la conexión
        $profile = 'DBNAME='.$parametros['base'];
        $usuario = 'DBUSER='.$parametros['usuario'];
        $puerto = 'DBPORT='.$parametros['puerto'];
        $base = 'DBHOST='.$parametros['profile'];
        $mask_config = 'MASKCONFIG='.$path_bin.'mask.conf.guarani';
        $debug = 'DEBUG=9';
        if ($parametros['clave'] != '') {
            $clave = 'DBPASSWD='.$parametros['clave'];			
            putenv($clave);
        }                
        putenv($profile);
        putenv($usuario);
        putenv($puerto);
        putenv($base);
        putenv($mask_config);
        putenv($debug);
        $this->manejador_interface->mensaje("Ejecutando: $comando");
        $salida = array();
        echo exec($comando, $salida, $exito);
        echo implode("\n", $salida);
        if ($obligatorio && ($exito > 0)) {
            throw new guarani_error('No se pudieron exportar correctamente los datos');
        }
        return ($exito == 0);
    }
    
    function get_mensaje_color($msj, $tipo)
    {        
        switch ($tipo) {
            //Si es de tipo error color rojo
            case 'E':
                $color = "\e[0;31m";
                break;
            //Si es de tipo informativo color verde
            case 'I':
                $color = "\e[1;32m";
                break;
            //Si es de tipo subtitulo color azul
            case 'S':
                $color = "\e[1;34m";
                break;
            case 'N':
                $color = "\e[1;33m";
        }
        return $color.$msj."\e[0m";
    }
    
    public function check_extensiones()
    {
        $this->manejador_interface->subtitulo($this->get_mensaje_color("\n Chequeo de extensiones de PHP", 'S'));
        $msj = "";
        $errores = false;
        $string = '';
        foreach(comunes::req_php_ext as $extension) {
            if (!extension_loaded($extension)) {
                $msj .= $this->get_mensaje_color("La extension '$extension' no esta instalada.\n", 'E');
                $errores = true;
            }
            $string .= $extension. ', ';
        }
        if (!$errores) {
            $msj .= $this->get_mensaje_color("Las extensiones requeridas (".substr($string, 0, -2).") estan instaladas Correctamente :)", 'I');
        } else {
            $msj .= $this->get_mensaje_color("Para un correcto funcionamiento es necesario instalar estas extensiones.", 'E');
        }
        $this->manejador_interface->mensaje($msj, true);
    }
    public function check_apache($code_httpd, $code_apache2)
    {
        //Se verifica la versión del apache
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando Apache", 'S'));
        $msj = $this->get_mensaje_color('No hay instalado un apache en el servidor actual', 'E');
        if ($code_httpd == 0) {
            exec("httpd -version | awk '{print $3}'", $v);

            $rewrite = "ls -la /usr/lib/apache2/ | grep rewrite.so | awk '{print $9}'";
            $setenvif = "ls -la /usr/lib/apache2/ | grep setenvif.so | awk '{print $9}'";

            $msj = $this->check_version_apache($v, $rewrite, $setenvif);
        } else {
            if ($code_apache2 == 0) {
                exec("sudo apache2 -version | awk '{print $3}'", $v);

                $rewrite = "ls -la /etc/apache2/mods-available/ | grep rewrite.load | awk '{print $9}'";
                $setenvif = "ls -la /etc/apache2/mods-available/ | grep setenvif.load | awk '{print $9}'";

                $msj = $this->check_version_apache($v, $rewrite, $setenvif);
            }
        }


        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_apache($v, $rewrite, $setenvif)
    {
        //Chequea la versión de Apache
        $version = explode('/', $v[0])[1];
        $msj = $this->get_mensaje_color("La version $version de Apache es correcta", 'I');
        $final_apache_version = implode(".", array_slice(explode(".", $version), 0, 2));

        $this->check_modulo_apache($rewrite);
        $this->check_modulo_apache($setenvif);

        if (!in_array($final_apache_version, comunes::req_apache_ver)) {
            $msj = $this->get_mensaje_color("La version $version de Apache no esta soportada", 'E');
        }

        return $msj;
    }

    public function check_modulo_apache($modulo)
    {
        //Chequea si existe el módulo determinado
        $modulo = exec($modulo);
        $msj = $this->get_mensaje_color("El modulo $modulo esta correctamente cargado", 'I');
        if ($modulo == '') {
            $msj = $this->get_mensaje_color("El modulo $modulo no esta cargado", 'E');
        }
        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_yuicompressor()
    {
        //Se verifica la versión de yuicompressor
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando yuicompressor", 'S'));

        if (exec("command -v yuicompressor")) {
            exec("yuicompressor -V 2>&1", $version, $code);
        } else {
            exec("yui-compressor -V 2>&1", $version, $code);
        }

        if ($code == 0) {
            $msj = $this->get_mensaje_color("La version $version[0] de yuicompressor es correcta", 'I');
            if (version_compare($version[0], comunes::req_yuicompressor_ver, '<')) {
                $msj = $this->get_mensaje_color("La version $version[0] de yuicompressor no esta soportada", 'E');
            }
        } else {
            $msj = $this->get_mensaje_color('Yuicompressor no se encuentra instalado o no se reconoce como comando en el sistema', 'E');
        }

        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_yarn()
    {
        //Se verifica la versión de Yarn
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando Yarn", 'S'));

        //Chequea la versión de Yarn
        exec("yarn -v 2>&1", $version, $code);

        if ($code == 0) {
            $msj = $this->get_mensaje_color("La version $version[0] de Yarn es correcta", 'I');
            if (version_compare($version[0], comunes::req_yarn_ver, '<')) {
                $msj = $this->get_mensaje_color("La version $version[0] de Yarn no esta soportada", 'E');
            }
        } else {
            $msj = $this->get_mensaje_color('Yarn no se encuentra instalado o no se reconoce como comando en el sistema', 'E');
        }

        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_php()
    {
        //Se verifica la versión de PHP
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando PHP", 'S'));

        $php_version = explode('-', PHP_VERSION)[0];
        $final_php_version = implode(".", array_slice(explode(".", $php_version), 0, 2));

        $msj = $this->get_mensaje_color("La version $php_version de PHP es correcta", 'I');
        if (version_compare($final_php_version, comunes::req_php_ver, '!=')) {
            $msj = $this->get_mensaje_color("La version $php_version de PHP no esta soportada", 'E');
        }
        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_postgres()
    {
        //Se verifica la versión de Postgres
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando Postgres", 'S'));

        $base = $this->get_base();
        $postgres = current($base->consultar("SHOW server_version"))['server_version'];
        $postgres = explode(" ", $postgres)[0];
        $msj = $this->get_mensaje_color("La version $postgres de Postgres es correcta ", 'I');

        $final_pg_version = implode(".", array_slice(explode("(", $postgres), 0, 2));

        if (version_compare($final_pg_version, comunes::req_pg_ver, '!=')) {
            $msj = $this->get_mensaje_color("La version $postgres de Postgres no esta soportada", 'E');
        }
        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_version_graphviz()
    {
        //Se verifica la versión de Graphviz
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando Graphviz", 'S'));
        $msj = $this->get_mensaje_color('La librería Graphivz (comando dot) no está instalada', 'E');
        if (!empty(exec("which dot"))) {
            $graphviz = exec("dot -V 2>&1 | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+'");
            $msj = $this->get_mensaje_color("La version $graphviz de Graphviz es correcta", 'I');
        }
        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_instalacion($code_httpd, $code_apache2)
    {
        $this->check_apache($code_httpd, $code_apache2);

        $this->check_version_php();

        $this->check_version_postgres();

        $this->check_version_yuicompressor();

        $this->check_version_yarn();

        $this->check_version_graphviz();


    }

    public function check_permisos_carpeta($code_httpd)
    {
        $this->manejador_interface->enter();
        $this->manejador_interface->subtitulo($this->get_mensaje_color("Validando Permisos de la carpeta \"instalacion\"", 'S'));

        //Obtengo el usuario apache y el grupo de apache que estan configurado
        $usuario_apache = "";
        $grupo_apache = "";
        if ($code_httpd == 0) {
            $usuario_apache = exec("cat /etc/apache2/httpd.conf | grep 'User ' | awk '{print $2}'");
            $grupo_apache = exec("cat /etc/apache2/httpd.conf | grep 'Group ' | awk '{print $2}'");
        } else {
            $usuario_apache = exec("cat /etc/apache2/envvars | grep APACHE_RUN_USER | awk -F \"=\" '{print $2}'");
            $grupo_apache = exec("cat /etc/apache2/envvars | grep APACHE_RUN_GROUP | awk -F \"=\" '{print $2}'");
        }

        //Obtengo la carpeta toba del guarani
        $carpeta = toba::proyecto()->get_path();
        $usuario_carpeta = exec("ls -l $carpeta | grep instalacion | awk '{print $3}'");
        $grupo_carpeta = exec("ls -l $carpeta | grep instalacion | awk '{print $4}'");

        //tomo los permisos de la carpeta instalacion
        $permisos = fileperms(toba::proyecto()->get_path().'/instalacion');
        $user = $group = $other = true;

        //Compara el usuario el grupo y los otros grupos.
        //Verifica los permisos de cada tripla de octetos para poder validar la escritura
        if (strcmp($usuario_apache, $usuario_carpeta) == 0) {
            if (!($permisos & 0x0080)) {
                $user = false;
            }
        } else {
            $user = false;
        }
        if (strcmp($grupo_apache, $grupo_carpeta) == 0) {
            if (!($permisos & 0x0010)) {
                $group = false;
            }
        } else {
            $group = false;
        }
        if (!($permisos & 0x0002)) {
            $other = false;
        }

        $msj = $this->get_mensaje_color("La carpeta instalacion posee permisos de escritura :)", 'I');
        if (!($user || $group || $other)) {
            $msj = $this->get_mensaje_color("La carpeta instalacion no posee permisos de escritura por favor modifique esto.", 'E');
        }

        $this->manejador_interface->mensaje($msj, true);
    }

    public function check_configuracion()
    {
        exec("command -v apache2", $apache2, $code_apache2);
        exec("command -v httpd", $httpd, $code_httpd);
        $this->check_instalacion($code_httpd, $code_apache2);
        $this->check_extensiones();
        $this->check_permisos_carpeta($code_httpd);

        $ini = new toba_ini($this->proyecto->get_dir().'/proyecto.ini');
        $version = $ini->get('proyecto', 'version');

        $msj = "\nPor cualquier duda que posea puede consultar la siguiente url: \n";
        $msj .= "http://documentacion.siu.edu.ar/wiki/SIU-Guarani/version$version/instalacion_desde_cero/requisitos_previos/linux";
        $this->manejador_interface->titulo($this->get_mensaje_color($msj, 'N'));

    }
	
	function notificar_sube($parametros)
	{	
		
		$solo_bajas = false;
		// Si se pasa la opción  --solo-bajas solo procesa bajas del beneficio de Boleto Estudiantil
		if (array_key_exists('--solo-bajas', $parametros)) {
			$solo_bajas = true;
		}
		
		// Cantidad de solicitudes que habrá por grupo de notificaciones
		$tamano_grupo = 2;
		//Traigo solicitudes pendientes.
		$solicitudes = toba::consulta_php('co_boleto_estudiantil')->get_solicitudes_pendientes($solo_bajas);
        if (0 == $cant_tramites = guarani_arreglos::count($solicitudes)) {
			//Si la cantidad de solicitudes es 0 se notifica que no se va enviar nada.
            $this->manejador_interface->mensaje($this->get_mensaje_color("\nSin solicitudes que notificar a sube.", 'N'), true);
        } else {
			$this->manejador_interface->mensaje($this->get_mensaje_color("\nSe enviaran $cant_tramites solicitud/es a sube.", 'N'));			
			$grupos = static::agrupar_solicitudes($solicitudes, $tamano_grupo);
			$error_general = false;
            foreach ($grupos as $grupo_solicitudes) {
				$sol = $this->notificar_solicitudes($grupo_solicitudes);
                if (!$this->actualizar_solicitudes($sol)) {
                    $error_general = true;
                }
            }
			if (!$error_general) {
	            $this->manejador_interface->mensaje($this->get_mensaje_color("\nTodos las solicitudes pendientes se enviaron correctamente! ", 'I'));
			} else {
				$this->manejador_interface->mensaje($this->get_mensaje_color("\nAlgunas solicitudes fallaron ",'E'));
	        }
		}		
	}

	/*
	 * Teniendo cuales son las solicitudes pendientes actualiza 
	 */

	function actualizar_solicitudes($solicitudes)
	{
		$exito = true;
		if (is_array($solicitudes)) {
			foreach ($solicitudes as $solicitud) {
				if ($solicitud['exito']) {
					guarani::act('act_boleto_estudiantil')->actualizar_beneficio($solicitud['id'], $solicitud['msg']);
				} else {
					$this->manejador_interface->mensaje('Error con la solicitud id=' . $solicitud['id'] . ': ' . $solicitud['msg'] . ' (' . $solicitud['msg_id'] . ')');
					$exito = false;
				}
			}
		}
		return $exito;
	}

	/*
	 * Agrupa para enviar de a $tamano_grupo solicitudes cuando hace el pedido
	 */
	protected static function agrupar_solicitudes($solicitudes, $tamano_grupo)
    {
        $grupos = array();
        $i = 0;
        foreach ($solicitudes as $fila => $solicitud)
        {
            if (($fila % $tamano_grupo) == 0)
            {
                $i++;
                $grupos[$i] = array();
            }
            $grupos[$i][] = $solicitud;
        }
        
        return $grupos;
    }
	
	/*
	 * Notifica las bajas de las solicitudes y luego actualiza esto en la BD de sube
	 */
	protected function notificar_solicitudes($solicitudes)
    {
		//Hago una incorporación de la clase para obtener todos los WS.
		$archivo_conf = $this->proyecto->get_dir()."/php/extension_toba/guarani_ws_boleto.php";
		require_once($archivo_conf);
		$respuesta = array();
        foreach ($solicitudes as $solicitud)
        {
			$repuesta[] = guarani_ws_boleto::agregar_novedad($solicitud['tipo_documento'], $solicitud['nro_documento'], $solicitud['id'], $solicitud['operacion']);			
        }
		return guarani_ws_boleto::actualizar_beneficio($repuesta);
    }
	
	function procesar_sube($parametros)
	{
		$solo_bajas = false;
		// Si se pasa la opción  --solo-bajas solo procesa bajas del beneficio de Boleto Estudiantil
		if (array_key_exists('--solo-bajas', $parametros)) {
			$solo_bajas = true;
		}
		
		// Si está en true solo procesa las solicitudes que están dadas de alta (si no cumplen las da de baja)
		$solicitudes = toba::consulta_php('co_boleto_estudiantil')->get_solicitudes_procesadas($solo_bajas);
		$cant_solicitudes = guarani_arreglos::count($solicitudes);
		$error_general = false;
        if ($cant_solicitudes == 0) {
            $this->manejador_interface->mensaje($this->get_mensaje_color('Sin solicitudes que procesar.', 'N'));
        } else {
			$this->manejador_interface->mensaje($this->get_mensaje_color('Se van a procesar ' . $cant_solicitudes . ' solicitudes.', 'N'));
			foreach ($solicitudes as $solicitud)
            {
				try
                {
					$mantiene_beneficio = $this->procesar_solicitud($solicitud);
					if (!$mantiene_beneficio)
					{
						$this->manejador_interface->mensaje($this->get_mensaje_color('No mantiene el beneficio: '.'','I'));
					}
				} catch (\Exception $e) {
                    $error_general = true;
					$this->manejador_interface->mensaje($this->get_mensaje_color('Error'));
                }
			}
			if (!$error_general) {
				$this->manejador_interface->mensaje($this->get_mensaje_color('Todos las solicitudes se procesaron correctamente!', 'I'));
			}
			 
		}
	}
	
	protected function procesar_solicitud($solicitud)
	{
		
		$operacion_a = boleto_estudiantil::OPERACION_A;
		$operacion_b = boleto_estudiantil::OPERACION_B;
		
		// Tomo el ultimo fragmento que contiene el ID de Trámite
		$fragmentos = explode(";", $solicitud['hash']);
		$tid = end($fragmentos);
		
		//Valido si el usuario es Regular y Calidad Activo		
		$cumple_condiciones_beneficio = boleto_estudiantil::verificar_beneficio($solicitud['persona']);
		// Si posee Beneficio de Boleto Estudiantil pero no cumple condiciones se lo doy de baja
		if ($solicitud['operacion'] == $operacion_a && !$cumple_condiciones_beneficio) {
			$mensaje = "Perdiste el beneficio porque dejaste de ser alumno regular y activo en al menos una carrera";
                        guarani::act('act_boleto_estudiantil')->dar_baja_beneficio($solicitud['persona'], $solicitud['id'], $tid, $solicitud['url_formulario_sube'], $mensaje);
			return false;
		// Si posee Beneficio de Boleto Estudiantil dado de baja pero cumple condiciones se lo vuelvo a activar
		} elseif ($solicitud['operacion'] == $operacion_b && $cumple_condiciones_beneficio) {
			$mensaje = "Recuperaste el beneficio porque volviste a ser alumno regular y activo en al menos una carrera";
                        $cant_propuestas_alumno_regular_activo = toba::consulta_php('co_boleto_estudiantil')->get_cant_propuestas_alumno_regular_activo($solicitud['persona']);
			$condicion = ($cant_propuestas_alumno_regular_activo == 1 ? boleto_estudiantil::CONDICION_INGRESANTE : boleto_estudiantil::CONDICION_NO_INGRESANTE);
                        guarani::act('act_boleto_estudiantil')->dar_alta_beneficio($solicitud['persona'], $solicitud['id'], $tid, $solicitud['url_formulario_sube'], $mensaje, $condicion);
			return false;
		}
		return true;
	}

	function enviar_emails_notificador($parametros)
	{
		
		$cantidad_emails_desencolar = 0;
		
		if(isset($parametros['-c']))			$cantidad_emails_desencolar = $parametros['-c'];
		if(isset($parametros['--cantidad']))	$cantidad_emails_desencolar = $parametros['--cantidad'];
		
		if(!preg_match('/^[0-9]+$/', $cantidad_emails_desencolar)) {
			$this->manejador_interface->mensaje($this->get_mensaje_color("El argumento '-c' o '--cantidad' debe ser un valor entero", 'E'), true);
			return false;
		}
		
		DJJobSIU::setConnection(toba::db()->get_pdo());
        DJJobSIU::setJobsTable(mail_job::jobs_tabla);
        $worker = new DJWorkerSIU(array(
            'queue' => mail_job::cola_notificador,
            "run_forever" => false,
            "count" => $cantidad_emails_desencolar
        ));
        $worker->start();

	}

	//--------------------------------------------------------------------------
	//---- Histórico de datos censales -----------------------------------------
	//--------------------------------------------------------------------------
		
	function habilitar_historico_datos_censales()
	{
		$tablas = array('mdp_datos_censales', 'mdp_datos_personales', 'mdp_datos_economicos', 
			'mdp_datos_actividades', 'mdp_eleccion_propuesta', 'mdp_eleccion_institucion');
		$sql = '';
		foreach ($tablas as $tabla) {
			$sql .= "ALTER TABLE $tabla ENABLE TRIGGER tuah_$tabla; ";
			$sql .= "ALTER TABLE $tabla ENABLE TRIGGER tiah_$tabla; ";
		}
		$this->get_base()->ejecutar($sql);
		echo 'Triggers habilitados.';
	}
	
	function deshabilitar_historico_datos_censales()
	{
		$tablas = array('mdp_datos_censales', 'mdp_datos_personales', 'mdp_datos_economicos', 
			'mdp_datos_actividades', 'mdp_eleccion_propuesta', 'mdp_eleccion_institucion');
		$sql = '';
		foreach ($tablas as $tabla) {
			$sql .= "ALTER TABLE $tabla DISABLE TRIGGER tuah_$tabla; ";
			$sql .= "ALTER TABLE $tabla DISABLE TRIGGER tiah_$tabla; ";
		}
		$this->get_base()->ejecutar($sql);
		echo 'Triggers deshabilitados.';
	}
	
	/**
	 * Deshabilita 
	 */
	function deshabilitar_triggers_auditoria_selectivo()
	{
		$tablas = array('mdp_datos_censales', 'mdp_datos_personales', 'mdp_datos_economicos', 
			'mdp_datos_actividades', 'mdp_eleccion_propuesta', 'mdp_eleccion_institucion',
			'jobs', 'mdp_personas_foto', 'his_insc_cursada', 'his_insc_cursada_log',
			'his_insc_cursada_instancias', 'his_insc_cursada_instancias_log', 
			'his_insc_cursada_log_requisitos', 'his_insc_examen', 'his_insc_examen_log',
			'his_insc_examen_log_requisitos', 'int_arau_nominal_alumnos',
			'int_arau_nominal_oferta', 'int_arau_nominal_actividades', 'int_arau_nominal_act_x_certificado',
			'men_destinatarios', 'aud_login_autogestion');
		
		$sql = '';
		foreach ($tablas as $tabla) {
			$sql .= "ALTER TABLE $tabla DISABLE TRIGGER tauditoria_$tabla; ";
		}
		
		$this->get_base()->ejecutar($sql);
	}

	function habilitar_triggers_auditoria_desactivados()
	{
		$base = $this->proyecto->get_db_negocio();
		$auditoria = $base->get_manejador_auditoria($this->schema_modelo, $this->schema_auditoria, $this->schema_toba);
		if (!is_null($auditoria) && $auditoria->existe()) {
			$tablas_sin_auditoria = $auditoria->get_tablas_triggers_desactivados();
			if (!empty($tablas_sin_auditoria)) {
				$auditoria->set_estado_activacion_triggers($tablas_sin_auditoria, $this->schema_modelo, true);
			}
		}
	}
	
	//--------------------------------------------------------------------------
	//---- rest-hooks ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function resthooks_crear_base($parametros) {

		// Crear esquema 'rest-hooks' en la db
		$backend = RestHookSanaviron::getRestMessenger()->getBackend();
		$className = "\\SIU\\RestHooks\\Backends\\Postgres";
		if ($backend instanceof $className) {
			$backend->createDb();
		}
		
	}
	
	function resthooks_iniciar_workers($parametros) {

		RestHookSanaviron::getRestMessenger()->runWorkers();
		
	}
	
	/**
	 * @param array $datosUsuario
	 * @param Person $person
	 * @return Account
     */
	public function generateAccountApp($datosUsuario, $person)
	{
		if (!toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION)) {
			throw new toba_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_GESTION ."' en 'instalacion/instalacion.ini'");
		}
		
		if (!toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W)) {
			throw new toba_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_3W ."' en 'instalacion/instalacion.ini'");
		}
		
		$account = parent::generateAccountApp($datosUsuario, $person);
		
		$appGestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION);
		$app3w = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W);
		
		// Si el tipo de usuario es Gestión entonces se le setea 'appUniqueIdGestion', si es 3W se le setea 'appUniqueId3w'.
		$appUniqueId = ($datosUsuario['tipo_usuario'] == persona::perfil_gestion)? $appGestion : $app3w;
		$account->setAppUniqueId($appUniqueId);
		return $account;
	}
	
	/*
	 * Funcion que extrae los usuarios que se van a exportar 
	 * Para los emails se uso esto: https://www.postgresql.org/message-id/20080202160436.GA32408@a-kretschmer.de
	 */
	function getDatosUsuarios($tokens = [], $sin_bloqueados = false)
	{
            
                $autentificacion = persona::arai_password_algorithm_crypt;
                $clave = '';
                // Si se pasa clave por defecto para todos los usuarios
                if (!is_null(EXPORTAR_USUARIOS_ARAI_CLAVE)) {
                    $autentificacion = persona::arai_password_algorithm_plano;
                    $clave = EXPORTAR_USUARIOS_ARAI_CLAVE;
                }
                
		$where = '';
		
		if ($sin_bloqueados) {
			$where = ' AND p.bloqueado = 0';
		}
		
		$orden_tipo_email = persona::orden_tipo_email();
		
		$sql = "
				SELECT 
					DISTINCT vp.usuario,
					vp.nombres_elegido as nombres,
					vp.apellido_elegido as apellidos,
					vp.apellido_nombres_elegido as nombre_completo,
					vp.sexo,
					vp.tipo_documento,
					vp.desc_tipo_documento,
					vp.nro_documento,
					p.bloqueado::varchar(1),
					array_to_string(array(
							SELECT mdp_personas_contactos.email
							FROM mdp_personas_contactos
							WHERE mdp_personas_contactos.persona = p.persona
							AND contacto_tipo IN ('".persona::mail_primario."', '".persona::mail_secundario."', '".persona::mail_institucional."')
							ORDER BY $orden_tipo_email
					), ',') AS emails,
					'{$clave}' AS clave,
					'{$autentificacion}' AS autentificacion,
					ga.tipo_usuario
				FROM 
					vw_personas as vp
					JOIN mdp_personas as p ON (vp.persona = p.persona)
					JOIN mdp_personas_grupo_acc as ga ON (ga.persona = p.persona)
				WHERE
					ga.tipo_usuario IN('".persona::perfil_docente."', '".persona::perfil_gestion."')
					AND vp.usuario IS NOT NULL
					$where
				ORDER BY
					ga.tipo_usuario DESC, vp.usuario
				";

		$usuarios = $this->get_base()->consultar($sql);
		
		$exportar_usuarios_sin_email = $this->manejador_interface->dialogo_simple('¿Desea exportar personas sin email asignado?', 's');
		
		// Si eligió que no desea personas sin email asignado elimino las mismas del listado
		if (!$exportar_usuarios_sin_email) {
			
			$usuarios_excluidos = [];
		
			foreach ($usuarios as $key => $usuario) {
				// Se quitan los usuarios que no tienen email asignado
				if (empty($usuario['emails'])) {
					unset($usuarios[$key]);
					$usuarios_excluidos[] = "{$usuario['nombre_completo']} ({$usuario['desc_tipo_documento']} {$usuario['nro_documento']})";
				} else {
					$emails = explode(",", $usuario['emails']);
					// Se validan los emails
					foreach ($emails as $key2 => $email) {
						if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
							unset($emails[$key2]);
						}
					}

					// Si la persona no tiene ningún email válido la quito
					if (empty($emails)) {
						unset($usuarios[$key]);
						$usuarios_excluidos[] = "{$usuario['nombre_completo']} ({$usuario['desc_tipo_documento']} {$usuario['nro_documento']})";
					} else {
						$usuarios[$key]['emails'] = implode(',', $emails);
					}
				}
			}
			
			// Si hay personas sin email asignado muestro un aviso
			if (!empty($usuarios_excluidos)) {
				$usuarios_excluidos = array_unique($usuarios_excluidos);
				$this->manejador_interface->mensaje($this->get_mensaje_color("No se exportaran las siguientes personas: ".implode(' - ', $usuarios_excluidos), 'S'), false);
				$this->manejador_interface->enter();
				$this->manejador_interface->mensaje($this->get_mensaje_color("Revisar en la operacion 'Administrar Personas' solapa 'Datos principales' que dichas personas tengan asignado uno o mas emails validos.", 'S'), false);
				$this->manejador_interface->enter();
			}
		}

                // Si se debe resolver los emails repetidos
                if (EXPORTAR_USUARIOS_ARAI_RESOLVER_EMAILS_REPETIDOS) {

                    $pattern = "/^(.*)@(.*)$/i";
                    $emails_usados = [];
                    $personas_procesadas = [];
                    
                    // Recorro cada persona
                    foreach ($usuarios as $key_usuario => $usuario) {

                        // Si no tiene emails asignados lo salteo
                        if (empty($usuario['emails'])) {
                            continue;
                        }

                        // Si la persona ya fue procesada no le vuelvo a cambiar el email, ej: una misma persona que es Docente y Admin
                        if (array_key_exists($usuario['usuario'], $personas_procesadas)) {
                            $usuarios[$key_usuario]['emails'] = $personas_procesadas[$usuario['usuario']];
                            continue;
                        }

                        // Puede venir un email solo o varios separados por coma
                        $emails_persona = explode(',',  $usuario['emails']);

                        $hubo_cambios = false;
                        $emails_cambiados = [];

                        // Recorro los emails de la persona
                        foreach ($emails_persona as $key_email => $email) {

                            // Si el email ya esta usado lo cambio
                            if (in_array($email, $emails_usados)) {
                                preg_match($pattern, $email, $matches);
                                $random = rand();
                                $email = "{$matches[1]}+{$random}@{$matches[2]}";
                                $hubo_cambios = true;
                            }

                            $emails_cambiados[$key_email] = $email;
                            $emails_usados[] = $email;

                        }

                        // Si hubo cambios en los emails hago el reemplazo
                        if ($hubo_cambios) {
                            $usuarios[$key_usuario]['emails'] = implode(",", $emails_cambiados);
                        }

                        $personas_procesadas[$usuario['usuario']] = $usuarios[$key_usuario]['emails'];
                    }
                    
                }

		return $usuarios;
	}
	
	/**
	 * @param array $datosUsuario
	 * @return Person
	 */
	public function generatePerson(array $datosUsuario)
	{
		/* @var Person $person */
		$person = new Person();
		// nombre y apellido
		if (isset($datosUsuario['nombres'])) {
			$person->setGivenName($datosUsuario['nombres']);
		}
		if (isset($datosUsuario['apellidos'])) {
			$person->setSn($datosUsuario['apellidos']);
		}
		if (isset($datosUsuario['nombre_completo'])) {
			$person->setCn($datosUsuario['nombre_completo']);
		}
		// persona bloqueada
		if (isset($datosUsuario['bloqueado'])) {
			$person->setBloqueada($datosUsuario['bloqueado']);
		}
		// Email de la persona
		if (!empty($datosUsuario['emails'])) {
			$person->setMail($datosUsuario['emails']);
		}
		// Género de la persona
		if (!empty($datosUsuario['sexo'])) {
			$person->setGenero($datosUsuario['sexo']);
		}
		// Documento de la persona
		if (!empty($datosUsuario['nro_documento'])) {
			// Si es CUIL/CUIT lo pongo como 'Extra'
			if ($datosUsuario['tipo_documento'] == persona::doc_CUIL) {
				$person->setExtra(['cuit' => $datosUsuario['nro_documento']]);
			} else {
				$tipo_documento_arai = persona::get_tipo_documento_arai($datosUsuario['tipo_documento']);
				$documento = new Documento($tipo_documento_arai, $datosUsuario['nro_documento']);
				$person->setDocumento($documento);
			}
		}
		
		return $person;
	}

	//--------------------------------------------------------------------------
	//---- Moodle ----------------------------------------------------------
	//--------------------------------------------------------------------------

	
	/**
	 * @return moodle Conexión a Moodle.
	 */
	protected function obtener_conexion_moodle()
	{
		return new moodle($this->plataforma_educacion_virtual['host'], $this->plataforma_educacion_virtual['url'], $this->plataforma_educacion_virtual['token']);
	}

	/*
	 * Devuelve los usuarios de Moodle que aún no han sido vinculados con Guaraní
	 */
	protected function obtener_usuarios_moodle_a_vincular()
	{
		$sql = "DROP TABLE IF EXISTS _temp_usuarios_moodle;";
		$sql .= "CREATE TABLE _temp_usuarios_moodle (
                            id bigint, 
                            username VARCHAR(100), 
                            firstname VARCHAR(100), 
                            lastname VARCHAR(100), 
                            fullname VARCHAR(255), 
                            email VARCHAR(100)
                        );";
        $this->get_base()->ejecutar($sql);
                
		$moodle = $this->obtener_conexion_moodle();

		$alfabeto = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
		
		foreach ($alfabeto as $letra) {

			$letra = utf8_encode($letra);
			$criteria = [['key' => 'lastname', 'value' => "{$letra}%"]];
			$this->manejador_interface->mensaje("Obteniendo usuarios de Moodle cuyo apellido comienza con '{$letra}'...", true);
			$usuarios_moodle = $moodle->getUsuarios($criteria);

			// Si falla la obtención de usuarios de Moodle muestro error.
			if ($usuarios_moodle == -1) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("Falló la obtención de usuarios de Moodle cuyo apellido comienza con '{$letra}' (ver logs para más información).", 'E'), true);
				continue;
			}

			$this->manejador_interface->mensaje($this->get_mensaje_color("Se obtuvieron ". count($usuarios_moodle)." usuarios de Moodle cuyo apellido comienza con '{$letra}'.", 'S'), true);
			// Si no hay usuarios para una letra dada salto a la siguiente letra 
			if (empty($usuarios_moodle)) {
				continue;
			}

			$bloques_usuarios = array_chunk($usuarios_moodle, $this->cant_usuarios_paginado);
			foreach ($bloques_usuarios as $key => $bloque_usuario) {

				$values = [];
				foreach ($bloque_usuario as $key => $usuario_moodle) {

					// Si el usuario no esta suspendido según la opción  -s
					if (!$this->incluye_suspendidos && $usuario_moodle['suspended']) continue;		

					$id		= $usuario_moodle['id'];
					$username	= pg_escape_string($usuario_moodle['username']);
					$firstname	= pg_escape_string($usuario_moodle['firstname']);
					$lastname	= pg_escape_string($usuario_moodle['lastname']);
					$fullname	= pg_escape_string($usuario_moodle['fullname']);
					$email          = (isset($usuario_moodle['email']))? $usuario_moodle['email'] : '';
					$email          = pg_escape_string($email);
					$values[] = "($id, '$username', '$firstname', '$lastname', '$fullname', '$email')";

				}

				if (!empty($values)) {
					$this->manejador_interface->mensaje("Guardando ".count($values)." usuario/s en la tabla '_temp_usuarios_moodle'...", true);
					$sql = "INSERT INTO _temp_usuarios_moodle (id, username, firstname, lastname, fullname, email) VALUES ".implode(", ", $values).";";
					$this->get_base()->ejecutar($sql);
					$this->manejador_interface->mensaje("Se guardaron ".count($values)." usuario/s en la tabla '_temp_usuarios_moodle'.", true);
				}

			} 

		}
	
		// Borro los usuarios de Moodle que ya están sincronizados con usuarios de Guaraní­.
		$sql_delete = "DELETE FROM _temp_usuarios_moodle 
		                 WHERE id IN (SELECT id_usuario_externo FROM int_pv_usuarios WHERE plataforma = {$this->plataforma_educacion_virtual['plataforma']});";
		$this->get_base()->ejecutar($sql_delete);
        
		$columna_todas = null;
		$columna_username = null;
		$columna_fullname = null;
		$columna_email = null;
		
		$columna_todas = in_array('all', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_username = in_array('username', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_fullname = in_array('fullname', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_email = in_array('email', $this->columnas_vinculacion_moodle_seleccionadas);
		
					
		//aca termina el cambio 1004
		
		$sql_campos = "";
		if ($columna_username || $columna_todas) {
			$sql_campos .= "username";
		}
		
		if ($columna_fullname || $columna_todas) {
			if ($sql_campos == "") {
			  $sql_campos .= "fullname";
			} else {
			  $sql_campos .= ", fullname";
			}  
		}
		
		if ($columna_email || $columna_todas) {
			if ($sql_campos == "") {
			  $sql_campos .= "email";
			} else {
			  $sql_campos .= ", email";
			}  
		}
		$sql_create_index = "CREATE INDEX idx__temp_usuarios_moodle ON _temp_usuarios_moodle ($sql_campos);";
                
		// Creo los í­ndices a la tabla _temp_usuarios_moodle.		
		$this->get_base()->ejecutar($sql_create_index);       
	}

          


	protected function vincular_usuarios()
	{
            
		$this->manejador_interface->mensaje("Paginado: {$this->cant_usuarios_paginado} usuario/s por bloque.", true);
		if ($this->incluye_suspendidos) {
			$this->manejador_interface->mensaje("Incluye usuarios suspendidos en Moodle.", true);
		} else {
			$this->manejador_interface->mensaje("No incluye usuarios suspendidos en Moodle.", true);
		}

		$this->obtener_usuarios_moodle_a_vincular();
		$sql = "SELECT COUNT(*) AS cant FROM _temp_usuarios_moodle;";
		$usuarios_moodle_a_vincular = $this->get_base()->consultar_fila($sql);
		if ($usuarios_moodle_a_vincular['cant'] == 0) {
			$this->manejador_interface->mensaje("No se encontraron usuarios a vincular en Moodle.", true);
			exit();
		}
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se intentaran vincular {$usuarios_moodle_a_vincular['cant']} usuario/s de Moodle.", 'S'), true);

		$this->obtener_personas_guarani_a_vincular();
		$sql = "SELECT COUNT(*) AS cant FROM _temp_vinculaciones_guarani_moodle;";
		$vinculaciones_guarani_moodle = $this->get_base()->consultar_fila($sql);
		$cant_vinculaciones_guarani_moodle = $vinculaciones_guarani_moodle['cant'];
		if ($cant_vinculaciones_guarani_moodle == 0) {
			$this->manejador_interface->mensaje("No se encontraron usuarios a vincular en Guaraní.", true);
			exit();
		}
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se matchearon {$cant_vinculaciones_guarani_moodle} usuario/s de Guaraní-Moodle.", 'S'), true);

		$sql = "SELECT * FROM _temp_usuarios_moodle WHERE id NOT IN (SELECT usuario_moodle FROM _temp_vinculaciones_guarani_moodle);";
		$usuarios_sin_match = $this->get_base()->consultar($sql);
		$this->manejador_interface->mensaje($this->get_mensaje_color("Hay " . count($usuarios_sin_match) . " usuario/s sin matchear.", 'S'), true);
		toba_logger::instancia()->debug("Usuarios que no matchearon: " . print_r($usuarios_sin_match, true));

		$cant_iteraciones = intval(floor($cant_vinculaciones_guarani_moodle / $this->cant_usuarios_paginado));
        if (($cant_vinculaciones_guarani_moodle % $this->cant_usuarios_paginado) > 0) {
            $cant_iteraciones++;
        }

        $cant_vinculaciones_realizadas = 0;
		for ($i = 0; $i < $cant_iteraciones; $i++) {
			$offset = $i * $this->cant_usuarios_paginado;
			$sql = "SELECT DISTINCT * FROM _temp_vinculaciones_guarani_moodle LIMIT $this->cant_usuarios_paginado OFFSET $offset;";
			$vinculaciones_guarani_moodle = $this->get_base()->consultar($sql);
			$cant_vinculaciones_realizadas += count($vinculaciones_guarani_moodle);
			$this->manejador_interface->mensaje("Vinculando usuarios en Moodle ({$cant_vinculaciones_realizadas} de {$cant_vinculaciones_guarani_moodle})...", true);
			$modificacionOk = $this->actualizar_usuarios_en_moodle($vinculaciones_guarani_moodle);
			if ($modificacionOk) $this->vincular_usuarios_en_db($vinculaciones_guarani_moodle);
		}
	}

	protected function obtener_personas_guarani_a_vincular()
	{
        $sql = "DROP TABLE IF EXISTS _temp_vinculaciones_guarani_moodle;";
		$sql .= "CREATE TABLE _temp_vinculaciones_guarani_moodle (
                            plataforma integer, 
                            persona_guarani integer,
                            usuario_moodle bigint
                        );";
		$this->get_base()->ejecutar($sql);

		$plataforma = $this->plataforma_educacion_virtual['plataforma'];
        
		$columna_todas = null;
		$columna_username = null;
		$columna_fullname = null;
		$columna_email = null;
	
		$columna_todas = in_array('all', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_username = in_array('username', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_fullname = in_array('fullname', $this->columnas_vinculacion_moodle_seleccionadas);
		$columna_email = in_array('email', $this->columnas_vinculacion_moodle_seleccionadas);
	
				
		//aca termina el cambio 1004
		
		
		$where_username = "";
		if ($columna_username || $columna_todas) {
			$where_username = " AND lower(trim(vw_personas.usuario)) = lower(trim(_temp_usuarios_moodle.username))";
		}

		$where_fullname = "";
		if ($columna_fullname || $columna_todas) {
			$where_fullname = " AND lower(trim(vw_personas.nombres_elegido || ' ' || vw_personas.apellido_elegido)) = lower(trim(_temp_usuarios_moodle.firstname || ' ' || _temp_usuarios_moodle.lastname))";
		}

		$join_email = "";
		$where_email = "";
		if ($columna_email || $columna_todas) {
			$join_email = " JOIN mdp_personas_contactos ON (mdp_personas_contactos.persona = vw_personas.persona)";
			$where_email = " AND trim(mdp_personas_contactos.email) = trim(_temp_usuarios_moodle.email)";
		}

		$sql = "INSERT INTO _temp_vinculaciones_guarani_moodle 
                        (SELECT DISTINCT $plataforma AS plataforma,
                                vw_personas.persona AS persona_guarani,
                                _temp_usuarios_moodle.id AS usuario_moodle
                        FROM vw_personas
                        $join_email
                        JOIN _temp_usuarios_moodle ON (
                        true
                        $where_username
                        $where_fullname
                        $where_email
                        )
                        WHERE NOT EXISTS (SELECT 1 FROM int_pv_usuarios WHERE int_pv_usuarios.persona = vw_personas.persona AND int_pv_usuarios.plataforma = $plataforma));";

		$this->get_base()->ejecutar($sql);
	}
	
	protected function vincular_usuarios_en_db($usuarios)
	{	
		$valores = [];
		foreach ($usuarios as $key => $usuario) {
			$valores[] = "({$usuario['plataforma']}, {$usuario['persona_guarani']}, {$usuario['usuario_moodle']})";
		}

		if (!empty($valores)) {
			$valores = implode(", ", $valores);
		}

		$sql = "INSERT INTO int_pv_usuarios (plataforma, persona, id_usuario_externo) VALUES $valores";

		try {
			$this->manejador_interface->mensaje("Guardando " . count($usuarios) . " usuario/s en la tabla 'int_pv_usuarios'...", true);
			$afectados = $this->get_base()->ejecutar($sql);
			$this->manejador_interface->mensaje("Se guardaron {$afectados} usuario/s en la tabla 'int_pv_usuarios'.", true);
		} catch (Exception $e) {
			$this->manejador_interface->error($this->get_mensaje_color("Falló el guardado en la tabla 'int_pv_usuarios'.", 'E'));
		}
	}

	protected function actualizar_usuarios_en_moodle($usuarios)
	{
		$moodle = $this->obtener_conexion_moodle();
		$usuarios_modificacion = [];
		foreach ($usuarios as $key => $usuario) {
			$usuarios_modificacion[] = ['id' => $usuario['usuario_moodle'], 'idnumber' => $usuario['persona_guarani']];
		}

		$this->manejador_interface->mensaje("Actualizando " . count($usuarios) . " usuario/s en Moodle...", true);
		$modificacionOk = $moodle->modificarUsuarios($usuarios_modificacion);

		if ($modificacionOk) {
			$modificados = count($usuarios_modificacion);
			$this->manejador_interface->mensaje("Se actualizaron {$modificados} usuario/s en Moodle.", true);
		} else {
			$this->manejador_interface->error($this->get_mensaje_color("Falló la actualización  en Moodle.", 'E'));
		}

		return $modificacionOk;
	}

	function crear_cola_documentos_arai($parametros)
	{
		
		$arai_documentos_file = $this->proyecto->get_dir()."/instalacion/arai_documentos.ini";
		if (!file_exists($arai_documentos_file)) {
			$this->manejador_interface->error($this->get_mensaje_color("Debe crear y configurar el archivo 'instalacion/arai_documentos.ini'.", 'E'));
			exit();
		}
		
		try {
			$docCli = arai_documentos::getInstancia();
			// Crea la tabla en la base de datos
			$docCli->getAsyncQueue()->getAsyncDBALQueue()->initDatabase();
			$this->manejador_interface->mensaje($this->get_mensaje_color("La tabla para encolar los documentos se creo exitosamente.", 'I'), true);
		} catch (Exception $e) {
			$this->manejador_interface->error($this->get_mensaje_color("Falló la creación  de la tabla para encolar los documentos.", 'E'));
		}
	}

	/**
	  * @see https://gitlab.siu.edu.ar/siu-arai/documentos-cli#async-queues
	  * @see https://gitlab.siu.edu.ar/siu-arai/documentos-cli/-/blob/develop/src/SIU/DocumentosCli/Templates/runDaemon.php
	  */
	function sincronizar_documentos_arai($parametros) 
	{

		$arai_documentos_file = $this->proyecto->get_dir()."/instalacion/arai_documentos.ini";
		if (!file_exists($arai_documentos_file)) {
			$this->manejador_interface->error($this->get_mensaje_color("Debe crear y configurar el archivo 'instalacion/arai_documentos.ini'.", 'E'));
			exit();
		}
		
		$docCli = arai_documentos::getInstancia();
		$ms = $docCli->getAsyncQueueWorker();

		$ms->onSuccess(function ($params, $payload) 
		{
			$this->manejador_interface->mensaje($this->get_mensaje_color("El documento se creó correctamente en SIU-Araí (CALLBACK onSuccess):", 'I'), true);
			
			$id = 0;
			$tabla = "";
			$columna = "";
			
			if (isset($params['id_acta'])) 
			{
				$id = $params['id_acta'];
				$tabla = "sga_actas";
				$columna = "id_acta";
			}
			
			if (isset($params['equivalencia_tramite'])) 
			{
				$id = $params['equivalencia_tramite'];
				$tabla = "sga_equiv_tramite";
				$columna = "equivalencia_tramite";
			}
			
			$this->vincular_documento($id, arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO, $tabla, $columna, $payload['uid_documento']);

			// Si el documento se encolo desde Autogestión se debe borrar el archivo para evitar que se acumulen
			if (isset($params['url_file'])) {
				$this->borrar_documento($params['url_file']);
			}
		});

		$ms->onFailure(function ($params, $error) 
		{
			$this->manejador_interface->error($this->get_mensaje_color("Falló la creación  del documento en SIU-Araí(CALLBACK onFailure):", 'E'));
			$this->manejador_interface->error($this->get_mensaje_color("Error: ". print_r($error, true), 'E'));
			
			$id = 0;
			$tabla = "";
			$columna = "";
			
			if (isset($params['id_acta'])) 
			{
				$id = $params['id_acta'];
				$tabla = "sga_actas";
				$columna = "id_acta";
			}
			
			if (isset($params['equivalencia_tramite'])) 
			{
				$id = $params['equivalencia_tramite'];
				$tabla = "sga_equiv_tramite";
				$columna = "equivalencia_tramite";
			}
			
			$parametros['parametros'] = $params['parametros'];
			$parametros['ras'] = $params['ras'];
			
			$this->vincular_documento($id, arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO, $tabla, $columna, null, $parametros);

			// Si el documento se encolo desde Autogestión se debe borrar el archivo para evitar que se acumulen
			if (isset($params['url_file'])) {
				$this->borrar_documento($params['url_file']);
			}
		});

		$ms->setFileGeneratorCallback(function ($params) {
			return file_get_contents($params['url_file']);
		});


		$ms->run();

	}

	protected function vincular_documento($id, $estado_documento_digital, $tabla, $columna, $uid_documento = null, $parametros = null)
	{
		try {
                        toba::db()->abrir_transaccion();
			$this->manejador_interface->mensaje("Vinculando el documento {$uid_documento} en la tabla '{$tabla}' columna '{$columna}' = {$id}...", true);

                        // Vinculo el documento de SIU-Araí a la tabla correspondiente.
			guarani::act('act_arai_documentos')->vincular_documento($id, $estado_documento_digital, $tabla, $columna, $uid_documento, $parametros);

                        // En caso de éxito
                        if ($estado_documento_digital == arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO) {
                            // Actualizo el estado del documento
                            guarani::act('act_arai_documentos')->actualizar_estado_documento($uid_documento, false, arai_documentos::SOLICITUD_AUTORIZADA);
                            
                            // Recupero los movimientos del documento
                            $movimientos_documento = arai_documentos::getMovimientosDocumento($uid_documento);
                            // Tomo las autorizaciones
                            $autorizaciones = arai_documentos::getAutorizacionesByMovimientos($movimientos_documento);

                            // Registro las autorizaciones del documento
                            if (!empty($autorizaciones)) {
                                guarani::act('act_arai_documentos')->eliminar_autorizaciones_documento($uid_documento);
                                guarani::act('act_arai_documentos')->agregar_autorizaciones_documento($uid_documento, $autorizaciones);
                            }
                        }
                        
                        toba::db()->cerrar_transaccion();
			$this->manejador_interface->mensaje($this->get_mensaje_color("Vinculación exitosa.", 'I'), true);
		} catch (Exception $e) {
                        toba::db()->abortar_transaccion();
			$this->manejador_interface->error($this->get_mensaje_color("Falló la vinculación en la tabla '{$tabla}'.", 'E'));
		}
	}
	
	protected function borrar_documento($url_file)
	{
		$url_file_delete = str_replace("descargar_adjunto", "borrar_adjunto", $url_file);
		file_get_contents($url_file_delete);
	}

	function notificar_administradores($parametros) 
	{
		$path = $this->proyecto->get_dir();
		$comando = "cd $path && bin/toba item ejecutar -t 89000016 -u toba";
		$this->manejador_interface->mensaje("Ejecutando: $comando");
		$output = [];
		exec($comando, $output, $return_var);
		// Es el "echo" dentro de la clase "php/comandos/notificar_administradores/notificar_administradores.php"
		$mensaje = end($output);
		// Si sale todo bien
		if ($return_var === 0) {
			$this->manejador_interface->mensaje($this->get_mensaje_color($mensaje, 'I'), true);
		} elseif ($return_var === 1) {// Si falla
			$this->manejador_interface->error($this->get_mensaje_color($mensaje, 'E'));
		} else {// Si no hay nada para notificar
			$this->manejador_interface->mensaje($mensaje, true);
		}
	}
	
	function unique_multidim_array($array, $key)
	{
		$arreglo = [];
		$i = 0;
		$claves_arreglo = [];

		foreach ($array as $valor) {
			if (!in_array($valor[$key], $claves_arreglo)) {
				$claves_arreglo[$i] = $valor[$key];
				$arreglo[$i] = $valor;
			}
			$i++;
		}
		return $arreglo;
	}

	function buscar_usuarios_arai($user)
	{
		$appUniquesIds = guarani_instalacion_conf::get_appUniquesId();
		if (empty($appUniquesIds)) {
			return [];
		}

		$responseGestion = $user->getClienteRest()->get("aplicaciones/{$appUniquesIds['appUniqueIdGestion']}/cuentas");
		$response3w = $user->getClienteRest()->get("aplicaciones/{$appUniquesIds['appUniqueId3w']}/cuentas");

		$usuarios3w = rest_decode(guarani_rest::get_response_json($response3w));
		$usuariosGestion = rest_decode(guarani_rest::get_response_json($responseGestion));
		//Unimos los usuarios de ambos proyectos y luegos aplicamos la funcion unique personalizada
		$usuarios = array_merge($usuarios3w, $usuariosGestion);
		$usuarios = $this->unique_multidim_array($usuarios, 'identificador_usuario');

		return $usuarios;
	}

	/**
	 * El comando vincula los usuarios de arai, con los usuarios de guarani
	 */
	function vincular_usuarios_arai()
	{
		// Ejecuta el codigo dentro del archivo "php/comandos/vincular_usuarios_arai/vincular_usuarios_arai.php"
		$path = $this->proyecto->get_dir();
		$comando = "cd $path && bin/toba item ejecutar -t 72000010 -u toba";
		$this->manejador_interface->mensaje("Ejecutando: $comando");
		$output = null;
		$return_var = null;
		exec($comando, $output, $return_var);
		
		// Muestro los mensajes devueltos por el Procesos batch
		$this->mostrar_output_exec($output);
		
		return $return_var;
	}

	function exportar_usuarios_moodle($parametros)
	{
		if (array_key_exists('-c', $parametros)) {
		 	if (!preg_match('/^[0-9]+$/', $parametros['-c'])) {
		 		$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-c' debe ser un número entero", 'E'), true);
		 		exit();
		 	}
			$this->cant_usuarios_paginado = $parametros['-c'];
		} elseif (array_key_exists('--cantidad', $parametros)) {
				if(!preg_match('/^[0-9]+$/', $parametros['--cantidad'])) {
			    	$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--cantidad' debe ser un número entero", 'E'), true);
					exit();
				}
				$this->cant_usuarios_paginado = $parametros['--cantidad'];
    	} else {
				$this->cant_usuarios_paginado = moodle::CANT_PAGINADO_USUARIOS;
	 		
		}
		if (array_key_exists('-m', $parametros)) {
			if(!preg_match('/^[0-9]+$/', $parametros['-m'])) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-m' debe ser un número entero", 'E'), true);
				exit();
			}
			$this->plataforma = $parametros['-m'];
		} elseif (array_key_exists('--moodle', $parametros)) {
			    if(!preg_match('/^[0-9]+$/', $parametros['--moodle'])) {
		   			$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--moodle' debe ser un número entero", 'E'), true);
		   			exit();
				}
				$this->plataforma = $parametros['--moodle'];
		} else {		
				//$this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron plataformas de educación virtual.", 'S'), true);
				$this->plataforma_exportar = $this->elegir_plataforma();
				$this->plataforma = $this->plataforma_educacion_virtual['plataforma'];	
		}
		$this->manejador_interface->enter();
		$this->obtener_usuarios_moodle_a_exportar($this->plataforma);
		$dir_name = toba::proyecto()->get_path_temp().'/exportacion_usuarios_moodle/';
		$this->generar_exportacion_usuarios_moodle_lib();
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se realizó la exportación en el directorio '{$dir_name}'", 'S'), true);
	}
				
	
	protected function obtener_usuarios_moodle_a_exportar()
	{ 
		$sql = "DROP TABLE IF EXISTS _temp_usuarios_moodle_exportar;";
		$sql .= "CREATE TABLE _temp_usuarios_moodle_exportar(
	               	username VARCHAR(100), 
	               	firstname VARCHAR(100), 
	               	lastname VARCHAR(100), 
	               	email VARCHAR(100),
	               	auth  VARCHAR(20),
	               	lang VARCHAR(2),
	               	timezone VARCHAR(100),
	               	country VARCHAR(2)
		        )
				";
	    $this->get_base()->ejecutar($sql);	
	    $auth =  pg_escape_string(moodle::AUTH_MANUAL);
	    $lang =  pg_escape_string(moodle::LANG_ES);
	    $timezone =  pg_escape_string(moodle::TIMEZONE_BSAS);
	    $country = pg_escape_string(moodle::COUNTRY_AR);
		$orden_tipo_email = persona::orden_tipo_email();

		$sql2 = "INSERT INTO _temp_usuarios_moodle_exportar
					SELECT 
		 				DISTINCT LOWER(vp.usuario) as username,
		 	 			vp.nombres_elegido as firstname,
		 				vp.apellido_elegido as lastname,
		 				COALESCE((
		  					SELECT mdp_personas_contactos.email
		  					FROM mdp_personas_contactos
		  					WHERE mdp_personas_contactos.persona = vp.persona
		  					AND contacto_tipo IN ('".persona::mail_primario."', '".persona::mail_secundario."', '".persona::mail_institucional."')
 							ORDER BY $orden_tipo_email LIMIT 1)) AS email,
							'{$auth}' as auth, '{$lang}' as lang, '{$timezone}' as timezone, '{$country}' as country
		  				FROM vw_personas as vp
		 				JOIN mdp_personas_grupo_acc as ga on ga.persona = vp.persona
						JOIN vw_alumnos as a on a.persona= vp.persona
						JOIN mdp_personas_contactos as con on con.persona = vp.persona
						JOIN sga_propuestas_ra  pa on pa.propuesta = a.propuesta  
						JOIN int_pv_plataformas_ra pra on pra.responsable_academica = pa.propuesta
		 				WHERE
		 					ga.tipo_usuario IN('".persona::perfil_alumno."')
		 					AND vp.persona NOT IN (SELECT persona FROM int_pv_usuarios WHERE plataforma = $this->plataforma  )
		 					AND vp.usuario IS NOT NULL AND con.email IS NOT NULL
		 					AND a.calidad = '". alumno::calidad_activo."'
		 					AND pra.plataforma = $this->plataforma 
		 			UNION
		 			SELECT 
		 				DISTINCT LOWER(vp.usuario) as username,
		 				vp.nombres_elegido as firstname,
		 				vp.apellido_elegido as lastname,
		 				COALESCE((
		  				    SELECT mdp_personas_contactos.email
		  				    FROM mdp_personas_contactos
		  				    WHERE mdp_personas_contactos.persona = vp.persona
		  				    AND contacto_tipo IN ('".persona::mail_primario."', '".persona::mail_secundario."', '".persona::mail_institucional."')
							ORDER BY $orden_tipo_email LIMIT 1)) AS email,
							'{$auth}' as auth, '{$lang}' as lang, '{$timezone}' as timezone, '{$country}' as country
		  				FROM vw_personas as vp
						JOIN mdp_personas_grupo_acc as ga on ga.persona = vp.persona
						JOIN mdp_personas_contactos as con on con.persona= vp.persona
						JOIN sga_docentes as d on d.persona= vp.persona
						JOIN sga_docentes_ra as dra  on dra.docente= d.docente 
						JOIN int_pv_plataformas_ra pra on pra.responsable_academica= dra.responsable_academica
		 				WHERE
		 					ga.tipo_usuario IN('".persona::perfil_docente."')
		 					AND vp.persona NOT IN (SELECT persona FROM int_pv_usuarios WHERE plataforma = $this->plataforma   )
		 					AND vp.usuario IS NOT NULL AND con.email IS NOT NULL
		 					AND d.estado ='".comunes::estado_activo."'
		 					AND pra.plataforma =$this->plataforma 
		 				ORDER BY firstname,lastname";
		$this->get_base()->ejecutar($sql2);	
	}
	        

 		

		
	function generar_exportacion_usuarios_moodle_lib()
	{
		$dir_name = toba::proyecto()->get_path_temp().'/exportacion_usuarios_moodle/';
		toba_manejador_archivos::crear_arbol_directorios($dir_name);
		// -------------------------------------------------------
		// GENERO ARCHIVOS DE TEXTO.
		// -------------------------------------------------------
		
		$sql = "SELECT COUNT(*) AS cant FROM _temp_usuarios_moodle_exportar;";
		$exportaciones_guarani_moodle = $this->get_base()->consultar_fila($sql);
		$cant_exportaciones_guarani_moodle = $exportaciones_guarani_moodle['cant'];


		if ($cant_exportaciones_guarani_moodle == 0) {
			$this->manejador_interface->mensaje("No se encontraron usuarios a exportar en Guaraní.", true);
			exit();
		}
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se obtuvieron {$cant_exportaciones_guarani_moodle} usuario/s de Guaraní para exportar a Moodle.", 'S'), true);
		$this->manejador_interface->mensaje($this->get_mensaje_color("Cada Archivo generado tendrá cómo máximo {$this->cant_usuarios_paginado} personas.", 'S'), true);

        // Ejemplo: $cant_exportaciones_guarani_moodle = 31 y $this->cant_usuarios_paginado = 30
        $cant_iteraciones = intval(floor($cant_exportaciones_guarani_moodle / $this->cant_usuarios_paginado));
        // Hasta acá $cant_iteraciones = 1
        if (($cant_exportaciones_guarani_moodle % $this->cant_usuarios_paginado) > 0) {
            // Hasta acá $cant_iteraciones = 2 ya que se le suma 1 por que el resto es mayor a cero
            $cant_iteraciones++;
        }
        
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se obtendrán {$cant_iteraciones} archivos.", 'S'), true);
		

		$offset = 0;
		$cant_exportaciones_realizadas = 0;
		$date_time = date('YmdHis', time());	 		
		for ($i = 1; $i <= $cant_iteraciones; $i++) {
            $filename = "{$dir_name}exp_g3_moodle_{$date_time}_{$i}.csv";
			$encoder = (new CharsetConverter())
			->inputEncoding('iso-8859-1')
			->outputEncoding('utf-8')
			;
			
			$csv = \League\Csv\Writer::createFromPath($filename, 'w');
    		$csv->setDelimiter(',');
			$csv->addFormatter($encoder);

    		$encabezado= ['username','firstname','lastname','email','auth','lang','timezone','country']; 
				
			$csv->insertOne($encabezado);
			$sth = "SELECT  DISTINCT * FROM _temp_usuarios_moodle_exportar LIMIT $this->cant_usuarios_paginado OFFSET $offset;";

			$exportaciones_guarani = $this->get_base()->consultar($sth);

			$csv->insertAll($exportaciones_guarani);
								
			$cant_exportaciones_realizadas += count($exportaciones_guarani_moodle);
			$this->manejador_interface->mensaje("Exportando usuarios para Moodle ({$i} de {$cant_iteraciones})...", true);
			$offset = $i * $this->cant_usuarios_paginado;					
		}
		$sql_b= "DROP TABLE _temp_usuarios_moodle_exportar;";
		$this->get_base()->ejecutar($sql_b);	
				
	}
		
	function vincular_usuarios_moodle($parametros)
	{            
		if (array_key_exists('-c', $parametros)) {
			if(!preg_match('/^[0-9]+$/', $parametros['-c'])) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-c' debe ser un número entero", 'E'), true);
				exit();
			}
			$this->cant_usuarios_paginado = $parametros['-c'];
		} elseif (array_key_exists('--cantidad', $parametros)) {
				if(!preg_match('/^[0-9]+$/', $parametros['--cantidad'])) {
					$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--cantidad' debe ser un número entero", 'E'), true);
					exit();
				}
				$this->cant_usuarios_paginado = $parametros['--cantidad'];
		} else {
				$this->cant_usuarios_paginado = moodle::CANT_PAGINADO_USUARIOS;
		}

		if (array_key_exists('-s', $parametros)) {
			$this->incluye_suspendidos = true;
		}
				
		if (array_key_exists('-m', $parametros)) {
			if(!preg_match('/^[0-9]+$/', $parametros['-m'])) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-m' debe ser un número entero", 'E'), true);
				exit();
			}
			$this->plataforma_elegida = $parametros['-m'];
		} elseif (array_key_exists('--moodle', $parametros)) {
		    	if(!preg_match('/^[0-9]+$/', $parametros['--moodle'])) {
				    $this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--moodle' debe ser un número entero", 'E'), true);
					exit();
				}
				$this->plataforma_elegida = $parametros['--moodle'];
	 	} else {		
		       $this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron plataformas de educación virtual enviadas como parámetro.", 'S'), true);	
		}
		              
	    if (array_key_exists('-v', $parametros)) {
	    	$this->columnas_elegidas =$parametros['-v'];
		}elseif (array_key_exists('--vincular', $parametros)) {
			 	$this->columnas_elegidas =$parametros['--vincular'];
		} else {   
				$this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron columnas de vinculación enviados como parámetros.(valores posibles: all o una combinación de: username - fullname - email )", 'S'), true);
				$this->columnas_elegidas = '';
		} 
		$this->manejador_interface->mensaje("Vinculando usuarios de Moodle...", true);
		$this->manejador_interface->enter();
		$this->elegir_plataforma();
		$this->manejador_interface->enter();
		$this->elegir_columnas_vinculacion_moodle();
		$this->vincular_usuarios();
		$this->manejador_interface->enter();
		$this->manejador_interface->mensaje("Fin de la vinculacion.", true);
	}
		
	protected function elegir_plataforma()
	{
	// si vino como parametro
		if ($this->plataforma_elegida != 0) {
			$where = 'int_pv_plataformas.activa = TRUE and int_pv_plataformas.plataforma = '.$this->plataforma_elegida ;
			$plataformas = toba::consulta_php('co_configuracion')->get_plataformas_educacion_virtual($where);
			if (empty($plataformas)) {
					$this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron plataformas de educación virtual.", 'S'), true);
			exit();
			}

			foreach ($plataformas as $key => $plataforma ) {
					
				if ($plataforma['plataforma'] == $this->plataforma_elegida ) {
					$this->plataforma_educacion_virtual = $plataforma;
					break;
				}
			}
		}else {
		
			// Obtengo plataformas de educación virtual activas
			$where = 'int_pv_plataformas.activa = TRUE';
			$plataformas = toba::consulta_php('co_configuracion')->get_plataformas_educacion_virtual($where);
				
			// Si no existen plataformas de educación virtual activas salgo
			if (empty($plataformas)) {
				$this->manejador_interface->mensaje('No se encontraron plataformas de educación virtual.', true);
				exit();
			}
		
			$dialogo_plataformas = [];
			foreach ($plataformas as $key => $plataforma) {
				$dialogo_plataformas[$plataforma['plataforma']] = "{$plataforma['nombre']} ({$plataforma['host']})";
			}
		
			$seleccion = $this->manejador_interface->dialogo_lista_opciones($dialogo_plataformas,'Elija una plataforma de educación virtual', false, 'Plataformas de educación virtual', true);
		
			foreach ($plataformas as $key => $plataforma) {
				
				if ($plataforma['plataforma'] == $seleccion) {
					$this->plataforma_educacion_virtual = $plataforma;
					break;
				}
		
			}
		}
	}
	
	protected function elegir_columnas_vinculacion_moodle()
    {
        // Si no se pasó la opción -v / --vincular
        if (empty($this->columnas_elegidas)) {
            $this->columnas_vinculacion_moodle_seleccionadas = $this->manejador_interface->dialogo_lista_opciones($this->columnas_vinculacion_moodle, 'Elija una o mas columnas para la vinculación', true, 'Columnas para la vinculación', true);
        } else {// Si se pasó la opción -v / --vincular
            $columnas = explode(",", $this->columnas_elegidas);
            foreach ($columnas as $key => $columna) {
                $columna = trim($columna);
                // Valido que la opción -v / --vincular sea uno de los siguientes valores 'username', 'fullname', 'email' o 'all'
                if (in_array($columna, ['username', 'fullname', 'email', 'all'])) {
                    $columnas[$key] = $columna;
                } else {
                    $this->manejador_interface->mensaje($this->get_mensaje_color("La opción '-v' / '--vincular' es inválida, se paso '{$columna}', posibles valores 'username', 'fullname', 'email' o 'all'.", 'E'), true);
                    exit();
                }
            }
            $this->columnas_vinculacion_moodle_seleccionadas = $columnas;
        }

        // Elimino columnas duplicadas, ej: -v username, email, username, email
        $this->columnas_vinculacion_moodle_seleccionadas = array_unique($this->columnas_vinculacion_moodle_seleccionadas);
    }

    function resolver_eliminacion_manual_moodle($parametros)
	{
		$moodle = "";
		// Si se pasa la opción  --moodle
		if (array_key_exists('--moodle', $parametros)) {
			$moodle = "--moodle {$parametros['--moodle']}";
		// Si se pasa la opción  corta -m
		} elseif (array_key_exists('-m', $parametros)) {
			$moodle = "--moodle {$parametros['-m']}";
		}
		
		// Ejecuta el código dentro del archivo "php/comandos/resolver_eliminacion_manual_moodle/resolver_eliminacion_manual_moodle.php"
		$path = $this->proyecto->get_dir();
		$comando = "cd $path && bin/toba item ejecutar -t 89000014 -u toba {$moodle}";
		$this->manejador_interface->mensaje("Ejecutando: $comando");
		$output = null;
		$return_var = null;
		exec($comando, $output, $return_var);
		
		// Muestro los mensajes devueltos por el Procesos batch
		$this->mostrar_output_exec($output);
		
		return $return_var;
		
	}

	protected function mostrar_output_exec($output)
	{
		foreach ($output as $mensaje) {
			
			// Si es mensaje de error se muestra en rojo
			if (strpos($mensaje, comunes::consola_tipo_msg_error) !== false) {
				$mensaje = str_replace(comunes::consola_tipo_msg_error, "", $mensaje);
				toba::logger()->error($mensaje);
				$this->manejador_interface->error($this->get_mensaje_color($mensaje, 'E'), true);
			// Si es mensaje de info se muestra en amarillo
			} elseif (strpos($mensaje, comunes::consola_tipo_msg_info) !== false) {
				$mensaje = str_replace(comunes::consola_tipo_msg_info, "", $mensaje);
				toba::logger()->info($mensaje);
				$this->manejador_interface->mensaje($this->get_mensaje_color($mensaje, 'N'), true);
			// Si es mensaje de éxito se muestra en verde
			} elseif (strpos($mensaje, comunes::consola_tipo_msg_exito) !== false) {
				$mensaje = str_replace(comunes::consola_tipo_msg_exito, "", $mensaje);
				toba::logger()->info($mensaje);
				$this->manejador_interface->mensaje($this->get_mensaje_color($mensaje, 'I'), true);
			// Si es mensaje de subtí­tulo se muestra en azul
			} elseif (strpos($mensaje, comunes::consola_tipo_msg_subtitulo) !== false) {
				$mensaje = str_replace(comunes::consola_tipo_msg_subtitulo, "", $mensaje);
				toba::logger()->info($mensaje);
				$this->manejador_interface->mensaje($this->get_mensaje_color($mensaje, 'S'), true);
			// Sino se muestra en blanco
			} else {
				toba::logger()->info($mensaje);
				$this->manejador_interface->mensaje($mensaje, true);
			}
			
		}
	}
		
 

    function actualizar_docentes_comision_moodle($parametros)
	{	
			 		
		if (array_key_exists('-m', $parametros)) {
			if(!preg_match('/^[0-9]+$/', $parametros['-m'])) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-m' debe ser un número entero", 'E'), true);
				exit();
			}
			$this->plataforma = $parametros['-m'];
		} elseif (array_key_exists('--moodle', $parametros)) {
		    	
				if(!preg_match('/^[0-9]+$/', $parametros['--moodle'])) {
			    	$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--moodle' debe ser un número entero", 'E'), true);
					exit();
				}
				$this->plataforma = $parametros['--moodle'];
	 	} else {		
		 		
		    	//$this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron plataformas de educación virtual.", 'S'), true);
		 		$this->plataforma_exportar = $this->elegir_plataforma();
		    	$this->plataforma = $this->plataforma_educacion_virtual['plataforma'];	
		}
		$this->cant_usuarios_paginado = moodle::CANT_PAGINADO_USUARIOS;
    	$this->manejador_interface->enter();
    	$this->obtener_comisiones_a_actualizar();
    	$this->generar_actualizacion_docentes_moodle();
    	$this->manejador_interface->mensaje($this->get_mensaje_color("Se realizó la actualización docentes en Moodle", 'S'), true);
    }
    
    function actualizar_alumnos_comision_moodle($parametros)
    {	
    	if (array_key_exists('-m', $parametros)) {
    		if(!preg_match('/^[0-9]+$/', $parametros['-m'])) {
    			$this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '-m' debe ser un número entero", 'E'), true);
    			exit();
    		}
    		$this->plataforma = $parametros['-m'];
    	} elseif (array_key_exists('--moodle', $parametros)) {
      		    if(!preg_match('/^[0-9]+$/', $parametros['--moodle'])) {
    			    $this->manejador_interface->mensaje($this->get_mensaje_color("La opción  '--moodle' debe ser un número entero", 'E'), true);
    				exit();
    		    }
    		    $this->plataforma = $parametros['--moodle'];
    		} else {		
    		 		
    		    //$this->manejador_interface->mensaje($this->get_mensaje_color("No se encontraron plataformas de educación virtual.", 'S'), true);
    		 	$this->plataforma_exportar = $this->elegir_plataforma();
    		 	$this->plataforma = $this->plataforma_educacion_virtual['plataforma'];	
    	}
		$this->cant_usuarios_paginado = moodle::CANT_PAGINADO_USUARIOS;
		$this->manejador_interface->enter();
        $this->obtener_comisiones_a_actualizar();
        $this->generar_actualizacion_alumnos_moodle();
        $this->manejador_interface->mensaje($this->get_mensaje_color("Se realizó la actualización de alumnos en Moodle", 'S'), true);
    }
    
    /// desde acá funciones
	/*
		Se recuperan comisiones con fecha tope de vigencia mayor a hoy y se cargan en la tabla _temp_comisiones_moodle_actualizar
		para luego actualizar los alumnos y docentes en los cursos de comisiones vigentes.
	*/				
	
    function obtener_comisiones_a_actualizar()
 	{
 		$sql = "DROP TABLE IF EXISTS _temp_comisiones_moodle_actualizar; ";
 		$sql .= "CREATE TABLE _temp_comisiones_moodle_actualizar(
                    comision INTEGER, 
                    curso INTEGER, 
                    id_curso_externo INTEGER, 
                    plataforma INTEGER
                    );
					";
	    $this->get_base()->ejecutar($sql);	
	       
		$sql2 = "INSERT INTO _temp_comisiones_moodle_actualizar (comision, curso, id_curso_externo, plataforma)
				SELECT 
					vw_comisiones.comision, 
					int_pv_cursos_comisiones.curso, 
					int_pv_cursos.id_curso_externo, 
					int_pv_cursos.plataforma 
				FROM vw_comisiones
				JOIN int_pv_cursos_comisiones ON int_pv_cursos_comisiones.comision = vw_comisiones.comision
				JOIN int_pv_cursos ON int_pv_cursos.curso = int_pv_cursos_comisiones.curso
				JOIN int_pv_plataformas ON int_pv_plataformas.plataforma = int_pv_cursos.plataforma
				WHERE vw_comisiones.fecha_tope_movimientos >= CURRENT_DATE
				  AND int_pv_plataformas.activa is true
				  AND int_pv_plataformas.plataforma = $this->plataforma
			    ";
		 $this->get_base()->ejecutar($sql2);	
	}

 		
    protected function generar_actualizacion_docentes_moodle()
	{
		// -------------------------------------------------------
		// RECORRO DATOS DE ACTUALIZACION.
		// -------------------------------------------------------
			
					
		$sql = "SELECT COUNT(*) AS cant FROM _temp_comisiones_moodle_actualizar;";
		$sql2 ="SELECT comision, curso, id_curso_externo, plataforma FROM _temp_comisiones_moodle_actualizar;"; 
		$comisiones_guarani_moodle = $this->get_base()->consultar_fila($sql);
		$cant_comisiones_guarani_moodle = $comisiones_guarani_moodle['cant'];
		if ($cant_comisiones_guarani_moodle == 0) {
			 $this->manejador_interface->mensaje("No se encontraron comisiones a actualizar en Guaraní.", true);
			 exit();
			 }
		$this->manejador_interface->mensaje($this->get_mensaje_color("Se obtuvieron {$cant_comisiones_guarani_moodle} comision/es de Guaraní que podrían tener datos para actualizar en Moodle.", 'S'), true);
		$filas = $this->get_base()->consultar($sql2);
		$i = 1;
		foreach ($filas as $fila) {
			$comision= $fila['comision'];
      		$this->comision=$fila['comision'];
      		$curso = $fila['curso'];
      		$this->curso=$fila['curso'];
     		$curso_externo = $fila['id_curso_externo'];
      		$this->curso_externo = $fila['id_curso_externo'];
      		$plataforma = $fila['plataforma'];
      		$this->plataforma = $fila['plataforma'];
      		     	        
      		// $this->manejador_interface->mensaje($this->get_mensaje_color("COMISION {$this->comision} -- CURSO {$this->curso}-- MOODLE {$this->curso_externo}", 'S'), true);       
            try{ 
                toba::db()->abrir_transaccion();
                // Busco datos de conexión
                $this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($comision);
                $this->s__datos_plataforma['plataforma'] = $this->s__datos_curso_virtual['plataforma'];
                $this->s__datos_plataforma['nombre'] = $this->s__datos_curso_virtual['plataforma_nombre'];
                $this->s__datos_plataforma['url'] = $this->s__datos_curso_virtual['plataforma_url'];
                $this->s__datos_plataforma['host'] = $this->s__datos_curso_virtual['plataforma_host'];
                $this->s__datos_plataforma['token'] = $this->s__datos_curso_virtual['plataforma_token'];
                $this->s__datos_plataforma['rol_alumno'] = $this->s__datos_curso_virtual['plataforma_rol_alumno'];
                $this->s__datos_plataforma['rol_alumno_curso'] = $this->s__datos_curso_virtual['plataforma_rol_alumno_curso'];
                $this->s__datos_plataforma['categoria_curso_default'] = $this->s__datos_curso_virtual['plataforma_categoria_curso_default'];
				$this->manejador_interface->mensaje($this->get_mensaje_color("Procesando comisión {$i} ...", 'S'), true);
				$i++;
 
                // Actualizo docentes en Moodle
                $this->s__docentes = [];
                $this->s__docentes =toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($this->comision, $plataforma);
				$this->s__roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($plataforma, 'int_pv_roles_docentes.responsabilidad IS NOT NULL');
                $this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($curso, 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
                $this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
                
                $this->actualizar_docentes_ok = $this->actualizar_docentes_moodle();
                toba::db()->cerrar_transaccion();
            } catch (Exception $e) {
			    // Si los docentes NO se actualizaron bien
			    if (!$this->actualizar_docentes_ok) {
                    $this->manejador_interface->mensaje($this->get_mensaje_color("No se han podido actualizar los docentes de comisión:{$comision} - curso:{$curso} - id_curso_externo: {$curso_externo} - plataforma: {$plataforma}", 'S'), true);
				    toba::db()->abortar_transaccion();
		     	    return false;
	   		    }
            }
	    		           	
		}
		$sql_b= "DROP TABLE _temp_comisiones_moodle_actualizar;";
		$this->get_base()->ejecutar($sql_b);
    }	
      
	protected function generar_actualizacion_alumnos_moodle()
    {
    	// -------------------------------------------------------
    	// RECORRO DATOS DE ACTUALIZACION.
    	// -------------------------------------------------------
    	$sql = "SELECT COUNT(*) AS cant FROM _temp_comisiones_moodle_actualizar;";
    	$sql2 ="SELECT comision, curso, id_curso_externo, plataforma FROM _temp_comisiones_moodle_actualizar;"; 
    	$comisiones_guarani_moodle = $this->get_base()->consultar_fila($sql);
    	$cant_comisiones_guarani_moodle = $comisiones_guarani_moodle['cant'];
    	if ($cant_comisiones_guarani_moodle == 0) {
    		$this->manejador_interface->mensaje("No se encontraron comisiones a actualizar en Guaraní.", true);
      		 exit();
      	}
      	$this->manejador_interface->mensaje($this->get_mensaje_color("Hay {$cant_comisiones_guarani_moodle} comision/es de Guaraní que podrían tener datos para para actualizar en Moodle.", 'S'), true);
      	$filas = $this->get_base()->consultar($sql2);
		$i = 1;
      	foreach ($filas as $fila) {
      		$comision= $fila['comision'];
      		$this->comision=$fila['comision'];
      		$curso = $fila['curso'];
      		$this->curso=$fila['curso'];
     		$curso_externo = $fila['id_curso_externo'];
			$this->curso_externo = $fila['id_curso_externo'];
      		$plataforma = $fila['plataforma'];
      		$this->plataforma = $fila['plataforma'];

      		try{            		        
                toba::db()->abrir_transaccion();
                // Busco datos de conexión
                $this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($comision);
                $this->s__datos_plataforma['plataforma'] = $this->s__datos_curso_virtual['plataforma'];
                $this->s__datos_plataforma['nombre'] = $this->s__datos_curso_virtual['plataforma_nombre'];
                $this->s__datos_plataforma['url'] = $this->s__datos_curso_virtual['plataforma_url'];
                $this->s__datos_plataforma['host'] = $this->s__datos_curso_virtual['plataforma_host'];
                $this->s__datos_plataforma['token'] = $this->s__datos_curso_virtual['plataforma_token'];
                $this->s__datos_plataforma['rol_alumno'] = $this->s__datos_curso_virtual['plataforma_rol_alumno'];
                $this->s__datos_plataforma['rol_alumno_curso'] = $this->s__datos_curso_virtual['plataforma_rol_alumno_curso'];
                $this->s__datos_plataforma['categoria_curso_default'] = $this->s__datos_curso_virtual['plataforma_categoria_curso_default'];
				$this->manejador_interface->mensaje($this->get_mensaje_color("Procesando comisión {$i} ...", 'S'), true);
				$i++;
               
                // Actualizo alumnos en Moodle

	            $this->s__alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($this->comision);
    	        $this->s__alumnos_eliminados = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($this->comision);
        	    $this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->curso);
				$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
            	$this->actualizar_alumnos_ok = $this->actualizar_alumnos_moodle();

				toba::db()->cerrar_transaccion();

            } catch (Exception $e) {
      		    // Si los alumnos NO se actualizaron bien
      	        if (!$this->actualizar_alumnos_ok) {
                    $this->manejador_interface->mensaje($this->get_mensaje_color("No se han podido actualizar los alumnos de comisión:{$this->comision} - curso:{$this->curso} - id_curso_externo: {$this->curso_externo} - plataforma: {$this->plataforma }", 'S'), true);
      	    	    toba::db()->abortar_transaccion();
      	    	    return false;
                }
      	           	
      	    }
      	}
		$sql_b= "DROP TABLE _temp_comisiones_moodle_actualizar;";
		$this->get_base()->ejecutar($sql_b);					
    }	
      
/// agregado porque no se pueden llamar a funciones del ci

     
    function get_docentes_curso_virtual()
	{
		$roles_docentes = array_unique(array_column($this->s__roles_docentes, 'rol_externo'));
		return $this->get_moodle_obj()->getDocentesByCurso($this->curso_externo, $roles_docentes);
	}

	/**
	 * @return moodle Objeto de la clase moodle.
	 */
	 
    function get_alumnos_curso_virtual()
	{
		$rol_alumno= $this->s__datos_plataforma['rol_alumno'];
		$rol_curso= $this->s__datos_plataforma['rol_alumno_curso'];

		return $this->get_moodle_obj()->getAlumnosByCurso($this->curso_externo, $rol_alumno, $rol_curso);
	}	 
	
    function get_moodle_obj()
    {
		if (!isset($this->moodle_obj)) {
	  		$this->moodle_obj = new moodle($this->s__datos_plataforma['host'], $this->s__datos_plataforma['url'], $this->s__datos_plataforma['token']);
		}
		return $this->moodle_obj;
    }

    function coinciden_docentes_moodle()
	{
		list($coinciden_docentes_moodle,
			$this->personas_para_crear,
			$this->docentes_para_matricular,
			$this->docentes_para_desmatricular) = moodle::coincidenDocentesMoodle(
			$this->s__docentes,
			$this->s__docentes_curso_virtual_guarani,
			$this->s__docentes_curso_virtual_moodle);
               
		return $coinciden_docentes_moodle;
	}

    function actualizar_docentes_moodle()
	{
		//toba::db()->abrir_transaccion();
	
		// Si no hay que actualizar en Moodle
		if ($this->coinciden_docentes_moodle()) {
			$this->manejador_interface->mensaje($this->get_mensaje_color("No hay docentes para matricular en la comision", 'S'), true);
			return true;
	
		} else { // sino
	
			// Si hay usuarios para crear en Moodle
			if (!empty($this->personas_para_crear)) {
	
				$personas_ids = array_column($this->personas_para_crear, 'persona');
				$emails_personas =  toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);
				
				$usuarios = [];
				$personas_usuarios = [];
				$personas_sin_email = [];
	
				foreach ($this->personas_para_crear as $key => $persona) {

					// Si la persona no tiene email asignado no lo matriculo en Moodle
					if (empty($emails_personas[$persona['persona']]['email'])) {
						$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
						continue;
					}

					$usuarios[$key]['username'] = $persona['usuario'];
					$usuarios[$key]['firstname'] = $persona['nombres'];
					$usuarios[$key]['lastname'] = $persona['apellido'];
					$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
					$usuarios[$key]['idnumber'] = $persona['persona'];

					$personas_usuarios[$persona['usuario']] = $persona;
					
				}
	
				// Si hay personas sin email asignado muestro mensaje y salgo
				if (!empty($personas_sin_email)) {
			        $this->manejador_interface->mensaje($this->get_mensaje_color("Hay Docente/s sin email asignado, estos docentes serán ingnorados", 'S'), true);
					return false;
				}
                
				$lote_usuarios = array_chunk($usuarios,$this->cant_usuarios_paginado, true);

				foreach ($lote_usuarios as $key => $usuarios) {

				    $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

	                // Si hay usuarios a guardar en la DB
	                if (!empty($usuarios_moodle)) {
                        $usuarios_pv = [];
                        foreach ($usuarios_moodle as $key => $usuario_moodle) {
                            // Agrego los usuarios para matricular y luego los guardo en la DB
							$usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'],$usuario_moodle['id']];
	                        $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
	                        $this->docentes_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];
                        }
							
						$this->insertar_usuarios_pv_masivo($usuarios_pv);
                    }

                }
            }
			
			// Si hay usuarios para matricular en Moodle
			if (!empty($this->docentes_para_matricular)) {

				$matriculaciones = [];
				$matriculaciones_grupo = [];
				$cant_docentes_m = count($this->docentes_para_matricular);
				$this->manejador_interface->mensaje($this->get_mensaje_color("Se matricularon {$cant_docentes_m} docente/s en la comision", 'S'), true);

				foreach ($this->docentes_para_matricular as $key => $docente_para_matricular) {
					$matriculaciones[$key]['roleid'] = $docente_para_matricular['rol_moodle'];
					$matriculaciones[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
					$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
					$matriculaciones_grupo[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
									}
                $lote_matriculaciones = array_chunk($matriculaciones,$this->cant_usuarios_paginado, true);
				foreach ($lote_matriculaciones as $key => $matriculaciones) {
					$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
                }

				$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo,$this->cant_usuarios_paginado, true);
				foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
					toba::logger()->debug("TRAIGO DOCENTES GRUPO DEL CURSO");
					toba::logger()->var_dump($this->s__datos_curso_virtual['grupo']);
					if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
						$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
					}
				}

			} else{$this->manejador_interface->mensaje($this->get_mensaje_color("No hay docentes para matricular en la comision ", 'S'), true);}
			
			// Si hay usuarios para desmatricular en Moodle
			if (!empty($this->docentes_para_desmatricular)) {
                
				$cant_docentes_d = count($this->docentes_para_desmatricular);
				$this->manejador_interface->mensaje($this->get_mensaje_color("Se desmatricularon {$cant_docentes_d} docente/s en la comision {$this->s__datos_curso_virtual['comision']}", 'S'), true);
				$desmatriculaciones = [];
				foreach ($this->docentes_para_desmatricular as $key => $docente_para_desmatricular) {
					$desmatriculaciones[$key]['userid'] = $docente_para_desmatricular;
					$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					
				}
                $lote_desmatriculaciones = array_chunk($desmatriculaciones,$this->cant_usuarios_paginado, true);
                foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) {
			    	$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
	            }
			}else{$this->manejador_interface->mensaje($this->get_mensaje_color("No hay docentes para desmatricular en la comision ", 'S'), true);}


		}
	
		//toba::db()->cerrar_transaccion();
		return true;
	}



	function coinciden_alumnos_moodle()
	{

		list(	$coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle(
							$this->s__alumnos,
							$this->s__alumnos_eliminados,
							$this->s__alumnos_curso_virtual_guarani,
							$this->s__alumnos_curso_virtual_moodle);
		
		return $coinciden_alumnos_moodle;
	}

	function actualizar_alumnos_moodle()
	{
		//toba::db()->abrir_transaccion();

		try {
	
			// Si no hay que actualizar en Moodle
			if ($this->coinciden_alumnos_moodle()) {
				$this->manejador_interface->mensaje($this->get_mensaje_color("No hay alumnos para matricular en la comision", 'S'), true);

				return true;

			} else { // sino

				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {

						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							continue;
						}

						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado muestro mensaje y salgo
					if (!empty($personas_sin_email)) {
						$this->manejador_interface->mensaje($this->get_mensaje_color("Hay Alumnos/s sin email asignado, estos alumnos serán ingnorados", 'S'), true);
						return false;
					}
					$lote_usuarios = array_chunk($usuarios,1000, true);
                                        
                    foreach ($lote_usuarios as $key => $usuarios) { 
                                       
					     $usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);
					
                        // Si hay usuarios a guardar en la DB
                        if (!empty($usuarios_moodle)) {
                            $usuarios_pv = [];
                            foreach ($usuarios_moodle as $key => $usuario_moodle) {

                                // Agrego los usuarios para matricular y luego los guardo en la DB
                                $usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
                                $personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
                                $this->alumnos_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

                            }

	                        $this->insertar_usuarios_pv_masivo($usuarios_pv);

                        }
                    }


				}

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->alumnos_para_matricular)) {

					$matriculaciones = [];
					$matriculaciones_grupo = [];
				
					$cant_alumnos_m = count($this->alumnos_para_matricular);
					
					$this->manejador_interface->mensaje($this->get_mensaje_color("Se matricularon {$cant_alumnos_m } alumno/s en la comision", 'S'), true);

					foreach ($this->alumnos_para_matricular as $key => $alumno_para_matricular) {

						// Si se trata de una propuesta tipo curso
						if ($alumno_para_matricular['propuesta_tipo'] == propuesta::tipo_curso) {
							$role_moodle = $this->s__datos_plataforma['rol_alumno_curso'];
						} else { // sino
							$role_moodle = $this->s__datos_plataforma['rol_alumno'];
						}

						$matriculaciones[$key]['roleid'] = $role_moodle;
						$matriculaciones[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
						$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
						$matriculaciones_grupo[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
					}
                    $lote_matriculaciones = array_chunk($matriculaciones,1000, true);
                    foreach ($lote_matriculaciones as $key => $matriculaciones) { 
						$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);
					}
					$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo,1000, true);
					foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
						if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
							$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
						}
					}
                }
				
				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->alumnos_para_desmatricular)) {

					$desmatriculaciones = [];
					
					$cant_alumnos_d = count($this->alumnos_para_desmatricular);
					$this->manejador_interface->mensaje($this->get_mensaje_color("Se desmatricularon {$cant_alumnos_d } alumno/s en la comision", 'S'), true);

					foreach ($this->alumnos_para_desmatricular as $key => $alumno_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $alumno_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					}
                        $lote_desmatriculaciones = array_chunk($desmatriculaciones,1000, true);
                        foreach ($lote_desmatriculaciones as $key => $desmatriculaciones) { 
							$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);
                        }
				}

			}

			//toba::db()->cerrar_transaccion();

			return true;

		} catch (Exception $e) {

			//toba::db()->abortar_transaccion();
			
			toba::logger()->error("Ocurrió un error al actualizar alumnos en Moodle: ".$e->getMessage());

			toba::notificacion()->error(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
			return false;

		}

	}
    
	function insertar_usuarios_pv_masivo($usuarios)
    {

        $valores = "";
        $num_items = guarani_arreglos::count($usuarios);
        $i = 0;
        foreach ($usuarios as $key => $usuario) {
            $valores .= "(" . implode(", ", $usuario) . ")";
            // Si no es el último item agrego una coma
            if (++$i != $num_items) {
                $valores .= ", ";
            }
        }

        $sql = "INSERT INTO int_pv_usuarios 
				(
					plataforma,
					persona,
					id_usuario_externo
				) 
				VALUES
				$valores";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se insertó en int_pv_usuarios con éxito. ************');
    }

    static function armar_condicion_in($array, $tabla='', $campo='', $clave='', $es_not=false)
	{
		// En caso que el array es vacio la condición es Verdadera
		if (!is_array($array)) {
			$array = array($array);
		}
		if (empty($array)) {
			return ($es_not ? ' true ' : ' false');
		}
		// Si el array no tiene formato de Carga Masiva de Columnas Externas de DT fomrateo el array
		if (!(is_int(key($array)) && !is_array(current($array))))  {
			//Si no tengo la clave, entonces utilizo 'campo' como 'clave'
			if ($clave == '') {
				$clave = $campo;
			}
			$array = static::_formatear_array($array,$clave);
			//Se verifica nuevamente que el arreglo no sea vací­o, ya que puede haber quedado vací­o por aplanarlo sin
			//considerar campos nulos. En este caso la condicion siempre es Verdadera
			if (empty($array)) {
				return ($es_not ? ' true ' : ' false');
			}
		} else {
			// en caso que tenga formato de carga masiva elimino los nulos
			$array = guarani_arreglos::matriz_sin_nulos($array);
			if (empty($array)) {
				return ($es_not ? ' true ' : ' false');
			}
		}
		$condicion = ' ';
		if ($tabla != '') {
			$condicion .= $tabla.'.';
		}
		$condicion .= $campo;
		if ($es_not) {
			$condicion .= ' NOT ';
		}
		$condicion .= ' IN ('.implode(',', toba::db()->quote($array)).') ';
		return $condicion;
	}	
	
  	static function count($dato) {
		return (is_array($dato) || $dato instanceof Countable) ? count($dato) : 0;
	}
	
        function exportar_reportes_arai($parametros)
	{
		// Ejecuta el código dentro del archivo "php/comandos/exportar_reportes_arai/exportar_reportes_arai.php"
		$path = $this->proyecto->get_dir();
		$comando = "cd $path && bin/toba item ejecutar -t 89000020 -u toba";
		$this->manejador_interface->mensaje("Ejecutando: $comando");
		$output = null;
		$return_var = null;
		exec($comando, $output, $return_var);

		// Muestro los mensajes devueltos por el Procesos batch
		$this->mostrar_output_exec($output);

		return $return_var;

	}
	
}
