<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\errores\error_guarani;

class parametro
{
	/**
	 * parametros: parametro
	 * no_quote: parametro
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema($parametros)
	{
		return \guarani::parametros()->get_default($parametros['parametro']);
	}

	
	/**
	 * parametros: parametro, responsable_academica
	 * no_quote: parametro, responsable_academica
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema_ra($parametros)
	{
		return \guarani::parametros()->get_param_ra($parametros['parametro'], $parametros['responsable_academica']);
	}
		
	/**
	 * parametros: parametro, propuesta_tipo
	 * no_quote: parametro, propuesta_tipo	 
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema_propuesta_tipo($parametros)
	{
		return \guarani::parametros()->get_param_propuesta_tipo($parametros['parametro'], $parametros['propuesta_tipo']);
	}
	
	/**
	 * parametros: parametro, propuesta
	 * no_quote: parametro, propuesta	 
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema_propuesta($parametros)
	{
		return \guarani::parametros()->get_param_propuesta($parametros['parametro'], $parametros['propuesta']);
	}
	
	/**
	 * parametros: parametro, plan
	 * no_quote: parametro, plan
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema_plan($parametros)
	{
		return \guarani::parametros()->get_param_plan($parametros['parametro'], $parametros['plan']);
	}
	
	/**
	 * parametros: parametro, plan_version
	 * no_quote: parametro, plan_version
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function get_parametro_sistema_plan_version($parametros)
	{
		return \guarani::parametros()->get_param_plan($parametros['parametro'], $parametros['plan_version']);
	}
    
    /**
     * parametros: parametro, propuestas
     * no_quote: propuestas
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function get_parametro_sistema_propuesta_unico($parametros) 
	{
        $parametros['parametro'] = strtoupper($parametros['parametro']);
		$sql = "SELECT 	distinct(par_param_sist_valores.valor)
				FROM 	par_param_sist_valores,
						par_param_sist_valores_entidad,
						sga_propuestas
				WHERE	par_param_sist_valores.param_valor = par_param_sist_valores_entidad.param_valor AND
						par_param_sist_valores_entidad.entidad = sga_propuestas.entidad AND
						upper(par_param_sist_valores.parametro) = {$parametros['parametro']} AND
                        sga_propuestas.propuesta in ({$parametros['propuestas']})
               ";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
        if (\guarani_arreglos::count($datos) == 1){
            return $datos[0]['valor'];
        }
		return false;
	}

	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: n
	 */
	function inscripcion_cursada_calidad() 
	{
		$sql = "execute procedure sp_param_sistema('inscripcion_cursada_calidad')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}

	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: n
	 */
	function recursar_regularizadas() 
	{
		$sql = "execute procedure sp_param_sistema('recursar_regularizadas')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}

	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: n
	 */
	function filtro_materias_con_comision() 
	{
		$sql = "execute procedure sp_param_sistema('filtro_materias_con_comision')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}

	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function filtro_materias_con_mesa_exa() 
	{
		$sql = "execute procedure sp_param_sistema('filtro_materias_con_mesa_exa')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}

	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function reinscripcion_ingresante() 
	{
		$sql = "execute procedure sp_param_sistema('reinscripcion_ingresante')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}
	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function banda_horaria_cursado()
	{
		$sql = "select	min(hora_inicio) as hmin,
						max(hora_finalizacion) as hmax
				from	sga_asignaciones";
		$datos = kernel::db()->consultar($sql);
		$m = explode(':', $datos[0]['hmin']);
		$datos[0]['min'] = $m[0].':00:00';
		$m = explode(':', $datos[0]['hmax']);
		$datos[0]['max'] = ($m[1]+0!=0?$m[0]+1:$m[0]).':00:00';
		return $datos;
	}
	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */	
	function orientacion_tipo_eleccion()
	{
		$sql = "execute procedure sp_param_sistema('orientacion_tipo_eleccion')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;	
	}
	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function permitir_login_mail() 
	{
		$sql = "execute procedure sp_param_sistema('acceso_web_permitir_login_por_mail')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}
	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 */
	function registrar_log_ingresos() 
	{
		$sql = "execute procedure sp_param_sistema('registrar_log_ingresos')";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}
}

?>