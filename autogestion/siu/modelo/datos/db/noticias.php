<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;

class noticias
{

    /**
     * parametros: persona_id
     * cache: no
     * filas: n
     */
    function noticias_por_persona_id($parametros)
    {
        $sql = "SELECT not_noticias.html
                FROM not_noticias_personas
                JOIN not_noticias ON (not_noticias.id_noticia=not_noticias_personas.id_noticia)
                WHERE not_noticias_personas.persona={$parametros['persona_id']}
                AND (CURRENT_DATE BETWEEN not_noticias.vigencia_desde AND not_noticias.vigencia_hasta)
                ORDER BY not_noticias.vigencia_hasta";
        $datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
        return $datos;
    }

    /**
     * parametros: persona_id
     * cache: no
     * filas: 1
     */
    function tiene_noticias_por_persona_id($parametros)
    {
        $sql = "SELECT EXISTS(
                    SELECT 1
                    FROM not_noticias_personas
                    JOIN not_noticias ON (not_noticias.id_noticia=not_noticias_personas.id_noticia)
                    WHERE not_noticias_personas.persona={$parametros['persona_id']}
                    AND (CURRENT_DATE BETWEEN not_noticias.vigencia_desde AND not_noticias.vigencia_hasta)
                ) AS tiene_noticias";


        $fila = kernel::db()->consultar_fila($sql);
        return $fila['tiene_noticias'];
    }
}


