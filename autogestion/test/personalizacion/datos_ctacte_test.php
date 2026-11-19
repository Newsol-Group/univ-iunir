<?php
/**
 * Regresion aislada: datos ficticios, sin bootstrap, conexiones ni pagos reales.
 * Carga las clases modificadas y el catalogo SIU; sustituye servicios externos.
 * Requiere PHP CLI con OpenSSL para cifrar referencias con una clave ficticia.
 */
namespace SIU\Chulupi {
    class kernel {
        static function persona() { return \EntornoCtacte::$persona; }
        static function db() { return new \BaseCtacte; }
        static function log() { return new \LogCtacte; }
        static function localizador() { return new \LocalizadorCtacte; }
        static function proyecto() { return new \ProyectoCtacte; }
        static function sesion() { return new \SesionCtacte; }
        static function traductor() { return new \TraductorCtacte; }
        static function vinculador() { return new \VinculadorCtacte; }
    }
}
namespace SIU\Chulupi\util\cache {
    class AdminCache { static function esta_activo($tipo) { return false; } }
}
namespace SIU\Chulupi\util {
    class validador { const TIPO_INT = 'int'; }
}
namespace SIU\Chulupi\interfaz {
    class pagelet {
        public $data = [];
        public $js = [];
        public $controlador;
        function add_var_js($clave, $valor) { $this->js[$clave] = $valor; }
    }
}
namespace siu\extension_kernel {
    class log { const NIVEL_DEBUG = 1; }
    class controlador_g3w2 {
        public $respuesta;
        protected function get_param($clave, $metodo = 'post') {
            return \EntornoCtacte::$request[$metodo][$clave] ?? null;
        }
        protected function validate_param($clave, $metodo, $tipo, $opciones) {
            $valor = $this->get_param($clave, $metodo);
            if (is_null($valor)) { return $opciones['default']; }
            if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
                throw new \RuntimeException('Parametro entero invalido en fixture');
            }
            return (int) $valor;
        }
        protected function render_raw_json($datos) { $this->respuesta = $datos; }
    }
}
namespace nsg01\modelo\facturacion {
    // No cargar configuraciones ni claves de la integracion real.
    abstract class nsg_facturacion {
        protected $es_ambiente_prod;
        function __construct($produccion) { $this->es_ambiente_prod = $produccion; }
        protected function get_estadoPagoDescripcion($estado, $origen) { return null; }
        protected function get_mcpKey() { return str_repeat('x', 32); }
        public function get_mcpData() {
            return ['key' => $this->get_mcpKey(), 'comercio' => 'prueba', 'sucursal' => 'prueba'];
        }
        protected function get_mcpHash($clave, $comercio, $sucursal, $monto) { return 'hash-ficticio'; }
    }
}
namespace {
    use siu\modelo\datos\catalogo;
    use nsg01\modelo\facturacion\nsg_g3w3_facturacion;
    use nsg01\operaciones\datos_ctacte\controlador;
    use nsg01\operaciones\datos_ctacte\pagelet_reporte;

    set_error_handler(function ($nivel, $mensaje, $archivo, $linea) {
        throw new \ErrorException($mensaje, 0, $nivel, $archivo, $linea);
    });

    $raiz = dirname(__DIR__, 2);
    spl_autoload_register(function ($clase) use ($raiz) {
        foreach (['nsg01\\' => $raiz . '/pers/nsg01/', 'siu\\' => $raiz . '/siu/'] as $prefijo => $directorio) {
            if (strpos($clase, $prefijo) === 0) {
                $archivo = $directorio . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
                if (is_file($archivo)) { require_once $archivo; }
                return;
            }
        }
    });

    class EntornoCtacte {
        static $persona;
        static $propuestas = [];
        static $alumnos = [];
        static $movimientos = [];
        static $pagos = [];
        static $sesion = [];
        static $request = [];
        static $consultas_saldos = [];
        static $consultas_alumno = [];
    }
    class PersonaCtacte {
        public $id = 90001;
        public $perfil = 'Cursos';
        public $alumno = 91001;
        public $tiene_carreras = false;
        function get_id() { return $this->id; }
        function get_id_perfil_activo() { return $this->perfil; }
        function es_perfil_cursos() { return $this->perfil === 'Cursos'; }
        function tiene_propuestas() { return $this->tiene_carreras; }
        function get_id_alumno_activo() {
            if (!$this->tiene_carreras) { throw new \RuntimeException('No existen carreras'); }
            return $this->alumno;
        }
    }
    class BaseCtacte {
        function quote($valor) { return "'" . str_replace("'", "''", (string) $valor) . "'"; }
        function consultar($sql) {
            if (strpos($sql, 'FROM vw_alumnos') !== false &&
                strpos($sql, "persona = '" . EntornoCtacte::$persona->id . "'") !== false) {
                return EntornoCtacte::$propuestas;
            }
            throw new \RuntimeException('Consulta no prevista: no se permite acceso a una BD');
        }
        function consultar_fila($sql) {
            if (!preg_match('/persona\s*=\s*(\d+)\s+AND\s+propuesta\s*=\s*(\d+)/', $sql, $partes)) {
                throw new \RuntimeException('Consulta de alumno no prevista');
            }
            EntornoCtacte::$consultas_alumno[] = [$partes[1], $partes[2]];
            $id = EntornoCtacte::$alumnos[$partes[1]][$partes[2]] ?? null;
            return is_null($id) ? false : ['alumno' => $id];
        }
    }
    class LogCtacte {
        function es_nivel_activo($nivel) { return false; }
        function add_debug($mensaje, $datos = null) {}
    }
    class LocalizadorCtacte {
        function get_namespaces() { return ['nsg01' => '', 'siu' => '']; }
        function instanciar($clase) {
            $mapa = ['modelo\\datos\\db\\alumno' => 'nsg01\\modelo\\datos\\db\\alumno',
                     'modelo\\datos\\db\\cursos' => 'siu\\modelo\\datos\\db\\cursos'];
            if (!isset($mapa[$clase])) { throw new \RuntimeException('DAO no previsto'); }
            return new $mapa[$clase];
        }
    }
    class ProyectoCtacte { function get($clave) { return 'salt-solo-pruebas'; } }
    class SesionCtacte {
        function esta_seteada($clave) { return isset(EntornoCtacte::$sesion[$clave]); }
        function get($clave) { return EntornoCtacte::$sesion[$clave]; }
    }
    class TraductorCtacte { function trans($clave) { return $clave; } }
    class VinculadorCtacte { function crear($operacion, $accion) { return '/' . $operacion . '/' . $accion; } }
    class propuesta { const tipo_curso = 999; }
    class toba {
        static function consulta_php($clase) {
            if ($clase !== 'co_facturacion') { throw new \RuntimeException('Consulta Toba no prevista'); }
            return new FacturacionCtacte;
        }
    }
    class FacturacionCtacte {
        function get_ctacte_alumno($alumno) {
            EntornoCtacte::$consultas_saldos[] = $alumno;
            return EntornoCtacte::$movimientos[$alumno] ?? [];
        }
        function get_datos_pagos_guarani($alumno, $origen) { return EntornoCtacte::$pagos[$alumno] ?? []; }
    }

    $verificaciones = 0;
    function verificar($condicion, $descripcion) {
        global $verificaciones;
        if (!$condicion) { throw new \RuntimeException('FALLO: ' . $descripcion); }
        $verificaciones++;
    }
    function resolver($propuesta = null) {
        return catalogo::consultar('alumno', 'get_alumno_propuesta_cursos', ['propuesta' => $propuesta]);
    }
    function pantalla($propuesta = null) {
        EntornoCtacte::$request = ['get' => ['propuesta' => $propuesta]];
        $pagelet = new pagelet_reporte;
        $pagelet->controlador = new controlador;
        $pagelet->prepare();
        return $pagelet;
    }
    function movimiento($numero) {
        return ['vtrmvc_codapl' => 'TEST', 'vtrmvc_nroapl' => $numero, 'grcfor_descrp' => 'Prueba',
                'vtrmvc_fchvnc' => '01/01/2099', 'usr_vtmclh_nocrsa' => 'Curso ficticio',
                'link' => 'https://example.invalid/pago/' . $numero, 'saldo' => 100];
    }

    EntornoCtacte::$persona = new PersonaCtacte;
    $facturacion = new nsg_g3w3_facturacion(true);
    $controlador = new controlador;
    verificar(resolver() === null, 'Cursos sin propuestas');
    verificar($facturacion->get_datosFacturacion([]) === null && !EntornoCtacte::$consultas_saldos,
        'No consultar saldos sin alumno');
    verificar(pantalla()->data['mensaje_contexto'] === 'datos_ctacte.sin_alumno', 'Mensaje sin inscripcion');

    EntornoCtacte::$propuestas = [['propuesta' => 92001, 'propuesta_nombre' => 'Curso A']];
    EntornoCtacte::$alumnos[90001] = [92001 => 91001, 92002 => 91002];
    verificar(resolver() === 91001, 'Cursos con una propuesta y sin carrera');
    verificar(resolver('92001') === 91001, 'ID de propuesta recibido como texto');
    $antes = count(EntornoCtacte::$consultas_alumno);
    verificar(resolver(99999) === null && count(EntornoCtacte::$consultas_alumno) === $antes,
        'No buscar alumno de una propuesta ajena');
    verificar(resolver(['92001']) === null, 'Rechazar propuesta no escalar');
    verificar($facturacion->get_datosFacturacion([]) === [], 'Cuenta existente sin movimientos');
    verificar(pantalla()->data['mensaje_contexto'] === null, 'Distinguir sin movimientos de sin alumno');

    EntornoCtacte::$propuestas[] = ['propuesta' => 92002, 'propuesta_nombre' => 'Curso B'];
    verificar(resolver() === null, 'Varias propuestas requieren seleccion');
    verificar(resolver(92001) === 91001 && resolver(92002) === 91002, 'Respetar cada seleccion');
    verificar(pantalla()->data['mensaje_contexto'] === 'datos_ctacte.seleccionar_propuesta', 'Acceso directo con varias propuestas');
    EntornoCtacte::$sesion['__propuesta'] = 92002;
    verificar(pantalla()->data['propuesta'] === 92002, 'Precargar seleccion de Cursos validada');
    verificar(pantalla(92001)->data['propuesta'] === 92001, 'Seleccion explicita prevalece sobre la sesion');
    verificar(pantalla(99999)->data['mensaje_contexto'] === 'datos_ctacte.propuesta_invalida', 'Seleccion ajena no cambia de cuenta');
    EntornoCtacte::$sesion['__propuesta'] = 99999;
    verificar(pantalla()->data['propuesta'] === null, 'No usar oferta de cursos sin inscripcion');
    unset(EntornoCtacte::$alumnos[90001][92002]);
    verificar(resolver(92002) === null, 'Propuesta sin registro de alumno');
    EntornoCtacte::$alumnos[90001][92002] = 91002;

    EntornoCtacte::$persona->perfil = 'Alumno';
    verificar(resolver() === null, 'Alumno sin carreras devuelve ausencia controlada');
    EntornoCtacte::$persona->tiene_carreras = true;
    verificar(resolver(92002) === 91001, 'Perfil Alumno conserva su carrera activa');
    EntornoCtacte::$persona->perfil = 'Cursos';
    verificar(resolver(92002) === 91002, 'Perfil Cursos no usa la carrera de Alumno');

    EntornoCtacte::$movimientos = [91001 => [movimiento(1), movimiento(2)], 91002 => [movimiento(1)]];
    $cuenta = $facturacion->get_datosFacturacion(['propuesta' => 92001]);
    $referencia = $cuenta[0]['id_pago'];
    verificar($cuenta[0]['estado_pago'] === null, 'Comprobante sin referencia de pago');
    EntornoCtacte::$movimientos[91001] = array_reverse(EntornoCtacte::$movimientos[91001]);
    verificar($controlador->get_ctacte($referencia, $facturacion, 92001)['vtrmvc_nroapl'] === 1,
        'Reordenar filas no cambia el comprobante');
    verificar($controlador->get_ctacte($referencia, $facturacion, 92002) === null,
        'Mismo numero en otra cuenta no coincide');
    verificar($controlador->get_ctacte($referencia, $facturacion, 99999) === null, 'Pago de propuesta ajena');
    verificar($controlador->get_ctacte(0, $facturacion, 92001) === null, 'No aceptar indices antiguos');
    verificar($controlador->get_ctacte(str_repeat('0', 40), $facturacion, 92001) === null, 'Referencia inexistente');
    EntornoCtacte::$persona->perfil = 'Alumno';
    verificar($controlador->get_ctacte($referencia, $facturacion) === null, 'Cambio de perfil invalida boton anterior');
    $referencia_alumno = $facturacion->get_datosFacturacion([])[0]['id_pago'];
    EntornoCtacte::$persona->alumno = 91002;
    verificar($controlador->get_ctacte($referencia_alumno, $facturacion) === null, 'Cambio de carrera invalida boton anterior');
    EntornoCtacte::$persona->perfil = 'Cursos';
    EntornoCtacte::$persona->id = 90002;
    EntornoCtacte::$alumnos[90002] = [92001 => 91003];
    EntornoCtacte::$movimientos[91003] = [movimiento(1)];
    verificar($controlador->get_ctacte($referencia, $facturacion, 92001) === null, 'Otra persona no puede reutilizar el boton');
    EntornoCtacte::$persona->id = 90001;
    EntornoCtacte::$pagos[91001] = [['codapl' => 'TEST', 'nroapl' => 1, 'estado_pago' => 3, 'fecha_pago' => '01/01/2099']];
    verificar($controlador->get_ctacte($referencia, $facturacion, 92001) === null, 'Comprobante ya pagado');
    EntornoCtacte::$pagos = [];

    EntornoCtacte::$request = ['post' => ['comprobante' => $referencia, 'propuesta' => 92001, 'monto' => '50,00']];
    $controlador->accion__pagar_mp();
    verificar($controlador->respuesta['link'] === 'https://example.invalid/pago/1', 'MP devuelve el enlace del comprobante validado');
    $controlador->accion__pagar_mcp();
    verificar(strpos($controlador->respuesta['trans_id'], 'TEST_1_') === 0, 'MCP prepara el comprobante validado');
    EntornoCtacte::$request['post']['propuesta'] = 92002;
    $controlador->accion__pagar_mp();
    verificar(isset($controlador->respuesta['error']), 'MP rechaza cambio de cuenta');
    $controlador->accion__pagar_mcp();
    verificar(isset($controlador->respuesta['error']), 'MCP rechaza cambio de cuenta');

    $pagelet = pantalla(92001);
    verificar(count($pagelet->data['ctactes']) === 2 && $pagelet->js['propuesta'] === 92001,
        'Listado y botones conservan la misma propuesta');
    EntornoCtacte::$movimientos[91001] = [];
    verificar($controlador->get_ctacte($referencia, $facturacion, 92001) === null, 'Comprobante retirado del saldo');
    $metadata = \nsg01\modelo\datos\_info_catalogo::alumno__get_alumno_propuesta_cursos();
    verificar($metadata['cache'] === 'no' && in_array('propuesta', $metadata['param_null']),
        'Catalogo sin cache de identidad compartida y con propuesta opcional');
    echo 'OK: ' . $verificaciones . " verificaciones aisladas. Sin conexiones ni pagos reales.\n";
}
