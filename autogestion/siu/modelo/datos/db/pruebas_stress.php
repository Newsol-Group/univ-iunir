<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;

class pruebas_stress
{
    /**
     * parametros:
     * cache: no
     * filas: 1
     */
    function limpiar_datos_comisiones_1er_cuatrimestre_2015($parametros)
    {
        //Borrar datos de las comisiones con las que se testeara.
        $sql = "
                DELETE FROM sga_eval_detalle_cursadas WHERE evaluacion IN (SELECT e.evaluacion FROM sga_evaluaciones as e, _ts_com as c WHERE c.entidad = e.entidad);
                DELETE FROM sga_eval_detalle WHERE evaluacion IN (SELECT e.evaluacion FROM sga_evaluaciones as e, _ts_com as c WHERE c.entidad = e.entidad);
        ";
        kernel::db()->ejecutar($sql);

        //Borro las inscripciones a cursadas
        $sql = "
                ALTER TABLE sga_insc_cursada_instancias DISABLE TRIGGER ALL;
                ALTER TABLE sga_insc_cursada DISABLE TRIGGER ALL;
                
                DELETE FROM sga_insc_cursada_instancias WHERE inscripcion IN 
                  (SELECT i.inscripcion FROM sga_insc_cursada as i , _ts_com as c WHERE c.comision = i.comision);
                DELETE FROM sga_insc_cursada WHERE comision IN (SELECT comision FROM _ts_com);
                
                ALTER TABLE sga_insc_cursada_instancias ENABLE TRIGGER ALL;
                ALTER TABLE sga_insc_cursada ENABLE TRIGGER ALL;
        ";
        kernel::db()->ejecutar($sql);

        //Actualizo el cupo en las comisiones
        $sql = "
                UPDATE sga_comisiones_cupo SET cant_inscriptos = 0 WHERE comision IN (SELECT comision FROM _ts_com);
        ";
        kernel::db()->ejecutar($sql);

        //Borro actas de cursadas
        $sql = "
                ALTER TABLE sga_actas_detalle DISABLE TRIGGER ALL;
                ALTER TABLE sga_actas DISABLE TRIGGER ALL;
                
                DELETE FROM sga_actas_folios WHERE id_acta IN (SELECT id_acta FROM sga_actas as a, _ts_com as b WHERE b.comision = a.comision);
                DELETE FROM sga_actas_detalle WHERE id_acta IN (SELECT id_acta FROM sga_actas as a, _ts_com as b WHERE b.comision = a.comision);
                DELETE FROM sga_actas WHERE id_acta IN (SELECT comision FROM _ts_com);
                
                ALTER TABLE sga_actas_detalle ENABLE TRIGGER ALL;
                ALTER TABLE sga_actas ENABLE TRIGGER ALL;
        ";
        kernel::db()->ejecutar($sql);
    }

}