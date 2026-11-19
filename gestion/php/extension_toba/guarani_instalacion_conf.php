<?php
class guarani_instalacion_conf
{
	protected $dir = array();
	protected $ddls = array();
	protected $datos_nucleo = array();
	protected $datos_prueba = array();
	protected $datos_custom = array();
	protected $objetos = array();
	protected $extensiones_validas = array();
	protected $conversiones = array();
	protected $manejador;

	function __construct($manejador)
	{
		$this->manejador = $manejador;

		// Se actualiza el arreglo con las conversiones de versiones del sistema
		$this->set_conversiones();

		// Directorios raíces a utilizar
		$this->dir = array(
			'bd' => 'BD',
			'modelo' => 'Modelo',
			'ddl' => 'Ddl',
			'datos' => 'Datos',
			'diferenciales' => 'Diferenciales',
			'auditoria' => 'Diferenciales/Auditoria',
			'tmp' => 'tmp',
			'log' => 'resultados.log'
		);

		// Extensiones de archivos válidas para ejecutar como sentencias de sql		
		$this->extensiones_validas = array('sql', 'dca');
		
		// Tipos de objetos de la base que se utilizará para parsear el archivo sql del modelo
		// Los de orden negativo se ejecutan antes de cargar los datos, y los de orden positivo se ejecutrán después 
		// 'parsear'			indica si se debe o no buscar este tipo de objeto (o sea, cortar las sentencias correspondientes)
		// 'cargar'			indica se se debe o no cargar este tipo de objeto
		// Otras propiedades son:
		// 'regexp'			es la expresión regular que se quisiera utilizar para encontrar este tipo de sentencia
		// 're_pos_nombre'	es la posición del nombre del objeto dentro de la expresión regular
		// 'prefijo'			cadena de caracteres que se quisiera agregar al comienzo del nombre de los archivos que se generen para este tipo de objeto 
		// 'sufijo'			cadena de caracteres que se quisiera agregar al final del nombre de los archivos que se generen para este tipo de objeto 
		$this->objetos = array(
			'tipos' => array('nombre' => 'Tipos', 'dir' => 'Tipos', 'orden' => -60, 'parsear' => false, 'cargar' => true),
			'secuencias' => array('nombre' => 'Secuencias', 'dir' => 'Secuencias', 'orden' => -55, 'parsear' => true, 'cargar' => true),
			'tablas' => array('nombre' => 'Tablas', 'dir' => 'Tablas', 'orden' => -50, 'parsear' => true, 'cargar' => true),
			'lenguajes' => array('nombre' => 'Lenguajes', 'dir' => 'Lenguajes', 'orden' => -45, 'parsear' => false, 'cargar' => false),
			'funciones' => array('nombre' => 'Funciones', 'dir' => 'Funciones', 'orden' => -40, 'parsear' => false, 'cargar' => true),
			'vistas' => array('nombre' => 'Vistas', 'dir' => 'Vistas', 'orden' => -35, 'parsear' => false, 'cargar' => true),
			'indices' => array('nombre' => 'Indices', 'dir' => 'Indices', 'orden' => -30, 'parsear' => true, 'cargar' => true),
			'procedures' => array('nombre' => 'Stored Procedures', 'dir' => 'Stored Procedures', 'orden' => -25, 'parsear' => false, 'cargar' => false),
			'triggers' => array('nombre' => 'Triggers', 'dir' => 'Triggers', 'orden' => -20, 'parsear' => false, 'cargar' => true),
			'permisos' => array('nombre' => 'Permisos', 'dir' => 'Permisos', 'orden' => -15, 'parsear' => true, 'cargar' => true),
			'valores_secuencias' => array('nombre' => 'Valores Secuencias', 'dir' => 'Setval', 'orden' => 5, 'parsear' => true, 'cargar' => true),
			'valores_reservados_secuencias' => array('nombre' => 'Valores Reservados de Secuencias', 'dir' => 'SetVal_reservado', 'orden' => 10, 'parsear' => true, 'cargar' => true),
			'ck' => array('nombre' => 'Constraints', 'dir' => 'CK', 'orden' => 1, 'parsear' => true, 'cargar' => true),
			'pk' => array('nombre' => 'Primary Keys', 'dir' => 'CK', 'orden' => 2, 'parsear' => true, 'cargar' => false),
			'fk' => array('nombre' => 'Claves Foráneas', 'dir' => 'FK', 'orden' => 3, 'parsear' => true, 'cargar' => true)
		);
		
		// Arreglo de carpetas que contienen los scripts para la generación de la base de datos
		// 'nucleo' es obligatoria. En su directorio se crearán los archivos que corte el parser
		$this->ddls = array(
			'nucleo' => array('nombre' => 'Ddls del núcleo', 'dir' => '_nucleo', 'orden' => 1, 'cargar' => true)
		);

		//Arreglo de carpetas que contienen los datos básicos para el funcionamiento de la aplicación
		$this->datos_nucleo = array(
			'nucleo' => array('nombre' => 'Datos del núcleo', 'dir' => '_nucleo/Datos', 'orden' => 1, 'cargar' => true),
			'nucleo_Localidades' => array('nombre' => 'Localidades', 'dir' => '_nucleo/Localidades', 'orden' => 2, 'cargar' => true),
			'nucleo_Colegios' => array('nombre' => 'Colegios', 'dir' => '_nucleo/ColegiosSecundarios', 'orden' => 3, 'cargar' => true),
			'nucleo_permisos' => array('nombre' => 'Datos de los permisos', 'dir' => '_nucleo/Permisos', 'orden' => 4, 'cargar' => true),
			'nucleo_parametros' => array('nombre' => 'Parámetros', 'dir' => '_nucleo/Parametros', 'orden' => 5, 'cargar' => true),
			'nucleo_parametros_etiquetas' => array('nombre' => 'Etiquetas de Parámetros', 'dir' => '_nucleo/ParametrosEtiquetas', 'orden' => 6, 'cargar' => true),
			'nucleo_controles_puntos_control' => array('nombre' => 'Controles - Puntos de Control', 'dir' => '_nucleo/Controles/PuntosDeControl', 'orden' => 7, 'cargar' => true),
			'nucleo_controles_acciones' => array('nombre' => 'Controles - Acciones', 'dir' => '_nucleo/Controles/Acciones', 'orden' => 8, 'cargar' => true),
			'nucleo_controles_reglas' => array('nombre' => 'Controles - Reglas', 'dir' => '_nucleo/Controles/Reglas', 'orden' => 9, 'cargar' => true),
			'nucleo_controles_requisitos' => array('nombre' => 'Controles - Requisitos', 'dir' => '_nucleo/Controles/Requisitos', 'orden' => 10, 'cargar' => true),
			'nucleo_usuarios' => array('nombre' => 'Usuario Default', 'dir' => '_nucleo/Usuarios', 'orden' => 11, 'cargar' => true)
		);

		//Arreglo de carpetas con los distintos juegos de datos de prueba que haya.
		//Esto puede atomizarse de la manera que se desee
		$this->datos_prueba = array(
			'pre-planes' => array('nombre' => 'Datos basicos para la pre-carga de planes.', 'dir' => 'juegos_de_datos/pre_planes', 'orden' => 1, 'cargar' => false),
			'plan-1' => array('nombre' => 'Datos del primer plan.', 'dir' => 'juegos_de_datos/1er_Plan', 'orden' => 2, 'cargar' => false),
			'plan-2' => array('nombre' => 'Datos del segundo plan.', 'dir' => 'juegos_de_datos/2do_Plan', 'orden' => 3, 'cargar' => false),
			'pos-planes' => array('nombre' => 'Datos finalización de planes.', 'dir' => 'juegos_de_datos/pos_planes', 'orden' => 4, 'cargar' => false),
			'actividades' => array('nombre' => 'Actividades', 'dir' => 'juegos_de_datos/Actividades', 'orden' => 5, 'cargar' => false),
			'catedras' => array('nombre' => 'Datos básicos para carga de Cátedras Equivalentes.', 'dir' => 'juegos_de_datos/catedras', 'orden' => 6, 'cargar' => false),
			'requisitos-por-accion' => array('nombre' => 'Datos básicos para carga de Requisitos por Acción.', 'dir' => 'juegos_de_datos/requisitos_por_accion', 'orden' => 7, 'cargar' => false),
			'personas' => array('nombre' => 'Insertar 1000 personas', 'dir' => 'juegos_de_datos/Personas', 'orden' => 8, 'cargar' => false),
			'periodos-lectivos-comisiones' => array('nombre' => 'Datos básicos para carga de Períodos Lectivos y Comisiones (es necesaria la carga de los juegos plan-1 y plan-2)', 'dir' => 'juegos_de_datos/periodos_lectivos_comisiones', 'orden' => 9, 'cargar' => false),
			'turnos-llamados-mesas' => array('nombre' => 'Turnos, Llamados y Mesas de Exámen', 'dir' => 'juegos_de_datos/turnos_llamados_mesas', 'orden' => 10, 'cargar' => false),
			'egresados' => array('nombre' => 'Egresados', 'dir' => 'juegos_de_datos/egresados', 'orden' => 11, 'cargar' => false),
			'equivalencias' => array('nombre' => 'Equivalencias', 'dir' => 'juegos_de_datos/Equivalencias', 'orden' => 12, 'cargar' => false),
			'encuestas' => array('nombre' => 'Encuestas', 'dir' => 'juegos_de_datos/Encuestas', 'orden' => 13, 'cargar' => false)
			// Se comenta la importacion de localidades resumido hasta tanto se pueda ver de no cargar los datos de las tablas \datos\_nucleo\mug_xxxxx.dat
			//'MUG_resumido'	      => array('nombre' => 'Localidades - Resumido'													, 'dir' => 'juegos_de_datos/MUG_resumido'					, 'orden' => 0	, 'cargar' => false),
			//	'testeo'			=> array('nombre' => 'Datos básicos testeo.'																, 'dir' => 'juegos_de_datos/testeo'			, 'orden' => 9	, 'cargar' => false)
		);
		
		//@todo: Faltaría agregar la opción de recorrer o no subdirectorios 
	}

	function get_dir($cual)
	{
		switch (strtolower($cual)) {
			case 'bd';
				return $this->dir[$cual];
			case 'log':
				return $this->get_dir('tmp') . '/' . $this->dir[$cual];
			default:
				return $this->dir['bd'] . '/' . $this->dir[$cual];
		}
	}

	function get_dir_ddl($cual)
	{
		return $this->get_dir('ddl') . '/' . $this->ddls[$cual]['dir'];
	}

	function get_dir_datos_nucleo($cual)
	{
		return $this->get_dir('datos') . '/' . $this->datos_nucleo[$cual]['dir'];
	}

	function get_dir_datos_prueba($cual)
	{
		return $this->get_dir('datos') . '/' . $this->datos_prueba[$cual]['dir'];
	}

	function get_ddls()
	{
		return $this->ddls;
	}

	function get_datos_nucleo()
	{
		return $this->datos_nucleo;
	}

	function get_datos_prueba()
	{
		return $this->datos_prueba;
	}

	function get_objetos()
	{
		return $this->objetos;
	}

	function get_extensiones_validas()
	{
		return $this->extensiones_validas;
	}

	function get_datos_custom()
	{
		return $this->datos_custom;
	}

	/**
	 * Determina si el archivo es válido para ejecutar
	 */
	function archivo_valido($archivo)
	{
		// Se verifica que el archivo contenga una extensión válida. En cualquier otro caso falla
		$info = pathinfo($archivo);
		if ((isset($info['extension'])) && (in_array($info['extension'], $this->extensiones_validas))) {
			return true;
		}
		return false;
	}
	
	function get_conversiones()
	{
		return $this->conversiones;
	}

	/**
	 *  Actualiza el arreglo de conversiones entre versiones del sistema
	 */
	function set_conversiones()
	{
		$version = '3.0.0';
		$version = intval(str_replace('.', '', $version));
		$this->conversiones['320'] = array('000014.sql');
		$this->conversiones['330'] = array('000015.sql');
		$this->conversiones['330_auditoria'] = array('000001.sql');
	}
        
    static function get_appUniquesId()
    {
        $archivo_instalacion_ini = toba::instalacion()->get_path_carpeta_instalacion().'/instalacion.ini';
        $parse = parse_ini_file($archivo_instalacion_ini);
        if (!isset($parse['appUniqueIdGestion']) || !isset($parse['appUniqueId3w'])) {
            throw guarani::error('Debe definir las appUniqueId de Gestion y de 3w');
        }
        $valores = [];
        $valores['appUniqueIdGestion'] = $parse['appUniqueIdGestion'];
        $valores['appUniqueId3w'] = $parse['appUniqueId3w'];
        return $valores;
    }    
	

	/**
	 * Recupera el parámetro definido en el archivo instalacion.ini
	 * 
	 * @param string $parametro 
	 * @param bool $obligatorio
	 * @param mixed $default
	 * @param string $subseccion
	 * @return mixed
	 * @throws guarani_error
	 */
	static function get_parametro_instalacion($parametro, $obligatorio = false, $default = null, $subseccion = null)
	{
		$valor = $default;
		if (toba::config()->existe_valor('instalacion', $subseccion, $parametro)) {
			$valor = toba::config()->get_parametro('instalacion', $subseccion, $parametro);
		} elseif ($obligatorio) {
			throw guarani::error("Debe definir el parámetro '$parametro' en el archivo instalacion.ini");
		} 
		return $valor;
	}

}