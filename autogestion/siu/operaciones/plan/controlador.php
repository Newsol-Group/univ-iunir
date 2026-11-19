<?php
namespace siu\operaciones\plan;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use SIU\Chulupi\util\validador;

class controlador extends controlador_g3w2
{
    protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 10;
    
    function modelo()
    {
        return guarani::plan_estudios();
    }

    function ini_request()
    {        
    }
	

    function accion__index()
    {		
		$plan_historia = $this->modelo()->get_plan_estudio_con_actividad_academica();
//		echo "<pre>";
//		print_r($plan_historia);
        $this->vista()->pagelet('info_plan')->data['plan'] = $plan_historia;
		$this->vista()->pagelet('info_plan')->data['datos_carrera'] = $plan_historia['datos_carrera'];
    }
    
    protected function crear_ezpdf(){
		$instancia = new \Cezpdf($this->pdf_hoja);
		return $instancia;
	}
	
	function accion__correlativas() 
	{
		$id = $this->validate_param('id', 'post', validador::TIPO_ALPHANUM);
		$correlativas = $this->modelo()->get_correlativas($id);
		$resultado = array();
		foreach ($correlativas as $key => $correlativa) {
			if ($correlativa['CONDICION'] == 'cursar') {
				$resultado['correlativas']['cursar'][$key] = $correlativa;
			} else {
				$resultado['correlativas']['aprobar'][$key] = $correlativa;
			}
		}
		$this->render_template('detalle.twig', $resultado);
		
	}
    
    function accion__generar_pdf()
	{
        $data_bruto = $this->modelo()->get_plan_estudio_con_actividad_academica();
        $data_carrera = $data_bruto['datos_carrera'];
        $data_fin = array();
        foreach($data_bruto['detalle'] as $key => $value){
			$data_fin[$key] = $value;			
            if (!empty($value['INFO_ACT_ACAD_MATERIA'])){
                $data_fin[$key]['estado'] = $value['INFO_ACT_ACAD_MATERIA']['NOTA']." (".$value['INFO_ACT_ACAD_MATERIA']['RESULTADO_DESCRIPCION'].") ";
            }
            else
            {
                $data_fin[$key]['estado'] = "";
            }
			unset($data_fin['INFO_ACT_ACAD_MATERIA']);
        }
        $data_bruto = $data_fin;
		
		$pdf = new generador_pdf($this->crear_ezpdf());
                
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_options(array('showHeadings'=>1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 500, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8,0.8,0.8),'cols' => array()));
		$pdf->set_text_options(array('left'=>5, 'top' => 20, 'maxWidth' => 500, 'width' => 500));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
        
        $this->pdf_encabezado = array("Plan de estudio", "");
		$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
		$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);
        
		$titulos = array('MATERIA' =>'Materia', 'NOMBRE_MATERIA' =>'Nombre de la materia', 'TIPO' =>'Tipo', 'ANIO' =>'Año', 'PERIODO' =>'Período', 'CREDITO' =>'Crédito', 'estado' =>'Estado');
		
        $pdf->agregar_texto("Unidad académica: ".$data_carrera['UNIDAD_ACADEMICA']);
        $pdf->agregar_texto("Carrera: ".$data_carrera['CARRERA']);
        $pdf->agregar_texto("Nombre carrera: ".$data_carrera['NOMBRE_CARRERA']);
        $pdf->agregar_texto("Plan: ".$data_carrera['PLAN']);
        $pdf->agregar_texto("Version: ".$data_carrera['VERSION']);
        $pdf->agregar_texto("");
        
        $pdf->agregar_tabla($data_bruto, $titulos, '');
		$pdf->generar();
    }
}