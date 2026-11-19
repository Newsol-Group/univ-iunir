<?php
namespace siu\operaciones\inscriptos_examenes;

class generador_pdf
	extends \siu\operaciones\_comun\operaciones\reporte\generador_pdf
{
	public function __construct($instancia = null, $top_margin = '80')
	{
		parent::__construct($instancia);
		$this->instancia->ezSetMargins('185', '50', '40', '40');
	}

	protected function generar_encabezado()
	{
		$yPos = (int) $this->instancia->ez['pageHeight'] - 40;
		$xPos = (int) $this->instancia->ez['pageWidth'] - $this->instancia->ez['leftMargin'] - $this->instancia->ez['rightMargin'] - 15;
		$this->instancia->ezSetY($yPos+5);
		$this->instancia->ezImage($this->encabezado["imagen"], 0, $this->encabezado["imagen_with"], "none", "left");
		$this->instancia->ezSetY($yPos+$this->pdf_fuente_titulo);
		$this->instancia->ezText($this->encabezado["titulo"], $this->pdf_fuente_titulo, array('left' => $this->encabezado["margen"], 'width' => $xPos, 'maxWidth' => $xPos));
		$this->instancia->ezSetY($yPos);
		$this->instancia->ezText($this->encabezado["subtitulo"], $this->pdf_fuente_subtitulo, array('left' => $this->encabezado["margen"], 'width' => $xPos, 'maxWidth' => $xPos));
	}

	public function no_hay_datos()
	{
		$lineas = (int)ceil(strlen(\SIU\Chulupi\kernel::traductor()->trans('nombre_horario').': '.end(explode(PHP_EOL, $this->encabezado['subtitulo'])))/61);
		if ($lineas < 3)
			$this->instancia->ezSetDy(-$lineas*15);
		$this->agregar_texto('No hay alumnos inscriptos a esta mesa.');
	}
}
?>