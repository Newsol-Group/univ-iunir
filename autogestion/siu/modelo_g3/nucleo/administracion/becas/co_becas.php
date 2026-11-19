<?php
/**
 *
 * @package Administración
 * @subpackage Becas
 */
class co_becas
{
    function get_opciones_radio_becas()
    {
		return array(	array('valor' => beca::becas_manuel_belgrano, 'descr' => beca::becas_manuel_belgrano_descr),
						array('valor' => beca::becas_progresar, 'descr' => beca::becas_progresar_descr)
					);
    }

    function get_opciones_radio_periodo()
    {
		return array(	array('valor' => beca::periodo_anual, 'descr' => beca::periodo_anual_descr),
						array('valor' => beca::periodo_primer_semestre, 'descr' => beca::periodo_primer_semestre_descr)
					);
    }

    /**
     * Retorna los datos de una convocatoria a reabrir.
     *
     * @param int $becas_convocatoria
     * @param string $cuil
     * @return array Datos de una convocatoria a reabrir
     */
    function get_datos_convocatoria_reabrir($becas_convocatoria, $cuil)
    {

        $sql = "SELECT
                    gde_encuestas_pendientes.respuesta,
                    gde_encuestas_pendientes.fecha_respuesta,
                    gde_encuestas_pendientes.hash,
                    mdp_personas_documentos.persona
                FROM gde_encuestas_pendientes
                JOIN gde_formularios ON (gde_formularios.formulario = gde_encuestas_pendientes.formulario)
                JOIN gde_habilitaciones ON (gde_habilitaciones.habilitacion = gde_formularios.habilitacion)
                JOIN mdp_personas_documentos ON (mdp_personas_documentos.persona = gde_encuestas_pendientes.persona)
                WHERE gde_habilitaciones.becas_convocatoria = {$becas_convocatoria}
                AND mdp_personas_documentos.pais_documento = ".persona::pais_argentina."
                AND mdp_personas_documentos.tipo_documento = ".persona::doc_CUIL."
                AND mdp_personas_documentos.nro_documento = '{$cuil}'
                AND gde_encuestas_pendientes.fecha_respuesta IS NOT NULL
                AND gde_habilitaciones.tipo_relevamiento = '".encuesta::tipo_relevamiento_becas."'
                AND gde_habilitaciones.kolla_sincronizado = '".comunes::si."'
                LIMIT 1
        ";
                
        return guarani::db()->consultar_fila($sql);
    }
}