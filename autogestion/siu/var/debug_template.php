<?php
/**
 * Esta clase se utiliza como hook de debug
 * para que funcione hay que ponerla en "/src/siu"
 * y activar el flag "ini_debug: true" en el archivo de configuracion
 */
namespace siu;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\cache\AdminCache;
use SIU\Chulupi\nucleo\agente;

class debug 
{
    function ini()
    {
//		AdminCache::set_activo(false);
		\siu\modelo\entidades\parametro::set_usa_asignacion_horaria(true);
//		\siu\modelo\entidades\parametro::set_incripcion_usar_sedes(true);
		\siu\modelo\controles\proceso_control::usar_cache(false);
		kernel::persona()->add_derecho('root');
//		kernel::agente()->forzar_tipo(agente::TIPO_TABLET);
//		
//		AdminCache::forzar_tipo('archivo');
//		kernel::usa_pretty_urls(true);
    }
}