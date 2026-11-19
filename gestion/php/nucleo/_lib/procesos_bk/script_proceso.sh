#!/bin/bash

#proceso parametrizado
export TOBA_DIR=$1
export TOBA_INSTALACION_DIR=$3
export TOBA_INSTANCIA=$4
export TOBA_PROYECTO=guarani
export USER=$8

export PATH=$PATH:${TOBA_INSTALACION_DIR}/../bin
echo toba item ejecutar -t $5 -u $8 $6 >> ${2}/00_comandos_ejecutados.txt
toba item ejecutar -t $5 -u $8 $6 >> ${7}
#exit