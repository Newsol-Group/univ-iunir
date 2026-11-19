rem proceso parametrizado
set toba_dir=%1
set toba_instalacion_dir=%3
set TOBA_INSTANCIA=%4
set TOBA_PROYECTO=guarani
set USER=%8

set PATH=%PATH%;%toba_instalacion_dir%\..\bin
echo toba item ejecutar -t %5 -u %8 %6 >> %2\00_comandos_ejecutados.txt
toba item ejecutar -t %5 -u %8 %6 >> %7
exit