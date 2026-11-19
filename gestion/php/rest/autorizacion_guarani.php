<?php

use SIUToba\rest\rest;
use SIUToba\rest\seguridad\proveedor_autorizacion;
use SIUToba\rest\seguridad\autorizacion\rest_error_autorizacion;

/**
 * Autoriza dependiendo el usuario y la ruta
 */
class autorizacion_guarani extends proveedor_autorizacion
{
	
	/**
     * Valida que el usuario tenga permisos para acceder a la API
     * 
     * @param SIUToba\rest\seguridad\rest_usuario $usuario
     * @param string $ruta
     *
     * @throws rest_error_autorizacion Si hay un error de autorización
     * 
     * @return bool
     */
    public function tiene_acceso($usuario, $ruta)
    {   
        $detalle = [];
        
        // Si $usuario es null es porque aún no se autenticó
        if (is_null($usuario)) {
            return false;
        }

        try {
            
            $metodo_http = rest::request()->get_method();
            $detalle['metodo_http'] = $metodo_http;
            
            $usuario_api = $usuario->get_usuario();
            $detalle['usuario'] = $usuario_api;
            
            $ruta_generica = static::get_ruta_generica($ruta);
            $detalle['ruta_generica'] = $ruta_generica;
            
			$recursos_usuarios = static::get_recursos_usuarios();
            
            // Si $recursos_usuarios es null es porque no existe el archivo
            if (is_null($recursos_usuarios)) {
                throw new \Exception("No se pudo encontrar el archivo 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php'");
            }
            
            // Si $recursos_usuarios no es un array es que falta return $recursos_usuarios;
            if (!is_array($recursos_usuarios)) {
                throw new \Exception("Falta agregar 'return' en el archivo 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php', ver 'php/rest/recursos_usuarios_template.php'");
            }
            
			// Si el recurso no esta definido en 'recursos_usuarios.php' NO se le permite el acceso
			if(!isset($recursos_usuarios[$ruta_generica][$metodo_http]['usuarios'])) {
                throw new \Exception("El endpoint no esta definido en el archivo 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php'");
			}
            
            // Obtengo los usuarios permitidos
			$usuarios = $recursos_usuarios[$ruta_generica][$metodo_http]['usuarios'];
			
			// Si $usuarios no es un array lo convierto en uno
			if (!is_array($usuarios)) {
				$usuarios = [$usuarios];
			}
            
			// El usuario actual debe estar en la lista de usuarios permitidos del recurso para poder acceder al mismo
			if(!in_array($usuario_api, $usuarios)) {
				throw new \Exception("El usuario no tiene permisos para acceder al endpoint, revise el archivo 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php'");
            }
            
            return true;
            
        } catch (\Exception $e) {
            throw new rest_error_autorizacion($e->getMessage(), $detalle);
        }
    }
	
	public static function get_ruta_generica($ruta)
	{
		// Remuevo el v1/ al principio si es que existe
		$ruta = preg_replace('/^v\d+\//', '', $ruta);
		
		$ruta_split = explode('/', $ruta);
		// El primer segmento es el recurso base
		$ruta_generica = $ruta_split[0];
		foreach ($ruta_split as $key => $segmento) 
		{
			// Si es el primer segmento lo salteo
			if ($key == 0) {
				continue;
			}
			
			// Si el segmento es par es un subrecurso
			if (($key % 2) == 0) {
				$ruta_generica .= "/$segmento";
			} else {// Si el segmento es impar es un id
				$ruta_generica .= "/id";
			}
		}
		
		return $ruta_generica;
	}
	
    /**
     * Devuelve el contenido del archivo 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php'
     * 
     * @return array|null Retorna array si puede cargar el archivo, null en caso contrario.
     */
	public static function get_recursos_usuarios()
	{
		$recursos_usuarios = null;
		$path_instalacion_proyecto = toba::instancia()->get_path_instalacion_proyecto('guarani');
		// Si existe 'instalacion/i__desarrollo/p__guarani/rest/recursos_usuarios.php' lo cargo, este archivo será siempre de la última versión
		if (is_file("{$path_instalacion_proyecto}/rest/recursos_usuarios.php")) {
			$recursos_usuarios = include("{$path_instalacion_proyecto}/rest/recursos_usuarios.php");
		}
		return $recursos_usuarios;
	}

	public static function get_version_api_usuarios_url()
	{
		$api_version = $_SERVER['REDIRECT_API_NOMBRE'] ?? rest::app()->config('api_version');
		return $api_version;
	}
}