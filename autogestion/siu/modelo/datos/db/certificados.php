<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;

class certificados
{
	/**
	 * parametros: _ua, nro_inscripcion
	 * cache: no
	 * filas: n
	 */
	function certificados_habilitados($parametros)
	{
		$sql = "execute procedure sp_certif_habilit ({$parametros['_ua']}, {$parametros['nro_inscripcion']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}
	
	/**
	 * parametros: nro_certificado
	 * cache: no
	 * cache_expiracion: 300
	 * filas: n
	 */
	function certificados_argumentos($parametros)
	{
		$sql = "execute procedure sp_certif_argum ({$parametros['nro_certificado']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}
	
	/**
	 * parametros: _ua, certificado, nro_inscripcion, cantidad_copias
	 * cache: no
	 * filas: n
	 */
	function certificados_solicitar($parametros)
	{
		$sql = "execute procedure sp_i_CertifPedidos ({$parametros['certificado']}, {$parametros['_ua']}, {$parametros['nro_inscripcion']}, {$parametros['cantidad_copias']},3)";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;
	}
	
	/**
	 * parametros: indice_certificado, orden, valor
	 * cache: no
	 * filas: n
	 */
	function guardar_argumentos($parametros)
	{
		$sql = "execute procedure sp_i_certifpedarg ({$parametros['indice_certificado']}, {$parametros['orden']}, {$parametros['valor']})";
		kernel::db()->ejecutar($sql, db::FETCH_NUM);
	}
	/**
     * parametros: documento, codigo_validador
     * no_quote: documento, codigo_validador
     * cache: memoria
     */
    function recuperar_certificado_web($parametros)
    {
            $solicitud_vigente = \toba::consulta_php('co_constancias')->get_solicitud_vigente($parametros['documento'], $parametros['codigo_validador']);
			
			if ($solicitud_vigente) {
				return \toba::consulta_php('co_constancias')->get_datos_solicitud($solicitud_vigente);
			}else{
				return false;
			}
    }
}
?>
