<?php

namespace siu\modelo\entidades;

use siu\modelo\entidades\parametro;
use siu\modelo\datos\catalogo;

class docente
{

    const docente_visualiza_datos_contacto = 'docente_visualiza_datos_contacto';

    /**
     * Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono).
     *
     * @param int $comision Id de la comisión (sga_comisiones.comision)
     *
     * @return bool TRUE Si el docente puede visualizar los datos de contacto de los alumnos, FALSE en caso contrario.
     */
    static public function visualiza_datos_contacto_por_comision($comision)
    {
        // Recupero las Propuestas asociadas a la comisión
        $propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', ['comision' => $comision]);
        // Recupero las Responsables Académicas asociadas a las Propuestas
	$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', ['propuestas' => $propuestas]);
        // Recupero el valor del parámetro 'docente_visualiza_datos_contacto'
        $sq_usa_sanaviron = parametro::get_valor(static::docente_visualiza_datos_contacto, $ras);
        // Lo convierto a boolean
        return ($sq_usa_sanaviron == \comunes::si);
    }

    /**
     * Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono).
     *
     * @param int $llamado_mesa Id del llamado mesa (sga_llamados_mesa.llamado_mesa)
     *
     * @return bool TRUE Si el docente puede visualizar los datos de contacto de los alumnos, FALSE en caso contrario.
     */
    static public function visualiza_datos_contacto_por_llamado_mesa($llamado_mesa)
    {
        // Recupero las Propuestas asociadas al llamado mesa
        $propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', ['llamado_mesa' => $llamado_mesa]);
        // Recupero las Responsables Académicas asociadas a las Propuestas
	$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', ['propuestas' => $propuestas]);
        // Recupero el valor del parámetro 'docente_visualiza_datos_contacto'
        $sq_usa_sanaviron = parametro::get_valor(static::docente_visualiza_datos_contacto, $ras);
        // Lo convierto a boolean
        return ($sq_usa_sanaviron == \comunes::si);
    }
}