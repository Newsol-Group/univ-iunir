<?php
class ci_rep_readmision extends ci_rep_sin_listado_inicial
{
    protected $s__filtro;
    protected $s__titulo;
    protected $s__imprimir = false;

    function get_datos($filtro) {
        return toba::consulta_php('co_solicitudes_readmision')->get_listado($this->s__filtro);
    }

    function get_tabla_principal() {
        return 'sga_readmisiones_solicitud';
    }

    function conf() {
        if ($this->pantalla()->existe_evento('pdf')) {
            $datos = $this->cuadro()->get_datos();
            if (empty($datos)) {
                $this->pantalla()->eliminar_evento('pdf');
            }
        }
    }

    //---- cuadro -----------------------------------------------------------------------

    function conf__cuadro(toba_ei_cuadro $cuadro) {

        $cuadro->set_filtro_asociado($this->dep('filtro'));
        if (isset($this->s__filtro)) {
            $this->indx_msg_eof = '800_eof_cuadro';
            //El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
            $datos = $this->get_datos($this->s__filtro);
            foreach ($datos as $key => $dato) {
                if (!empty($dato['estado'])) {
                    switch ($dato['estado']) {
                        case propuesta::readmision_pendiente :
                            $datos[$key]['estado'] = propuesta::readmision_pendiente_descr;
                            break;
                        case propuesta::readmision_rechazada :
                            $datos[$key]['estado'] = propuesta::readmision_rechazada_descr;
                            break;
                        case propuesta::readmision_aceptada :
                            $datos[$key]['estado'] = propuesta::readmision_aceptada_descr;
                            break;
                    }
                }
            }
            $cuadro->set_datos($datos);
            $this->pantalla()->agregar_evento('pdf');
        }
        $cuadro->set_eof_mensaje($this->get_msg_eof());
    }

    function evt__filtro__filtrar($datos) {

        $this->s__filtro = $this->dep('filtro')->get_sql_where();
    }

    function evt__pdf() {
        $this->s__imprimir = true;
    }

    function extender_objeto_js() {
        if ($this->s__imprimir) {
            $this->s__imprimir = false;
            echo "
                  window.open(vinculador.get_url(null,null,'vista_jasperreports', [{$this->objeto_js}._id]));
                  ";
        }
    }

    function vista_jasperreports(toba_vista_jasperreports $report) {
        $this->s__titulo = 'Reporte de readmisiones';
       
	$report->set_parametro('titulo', 'S', $this->s__titulo);
        $this->set_titulo( $this->s__titulo);
        parent::vista_jasperreports($report);
    }

}
?>