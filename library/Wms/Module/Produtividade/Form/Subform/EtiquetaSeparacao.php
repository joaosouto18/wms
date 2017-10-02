<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 20/07/2016
 * Time: 15:18
 */

namespace Wms\Module\Produtividade\Form\Subform;

use Core\Form\SubForm;

class EtiquetaSeparacao extends SubForm
{
    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
        ))
            ->addElement('cpf', 'pessoa', array(
                'size' => 15,
                'label' => 'CPF Funcionário',
                'style' => 'width:190px;',
                'class' => 'inptText',
            ))
            ->addElement('text', 'etiquetaInicial', array(
                'size' => 17,
                'label' => 'Etiqueta Inicial',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text', 'etiquetaFinal', array(
                'size' => 17,
                'label' => 'Etiqueta Final',
                'class' => 'inptText inptEtiqueta',
            ))
            ->addElement('text','showIntervalo', array(
                'label' => 'Intervalo',
                'class' => 'inptText',
                'id' => 'txtIntervalo',
                'size' => 4,
                'readonly' => true,
                'disabled' => true,
            ))->addElement('text','qtdConferentes', array(
                'label' => 'Qtd. Funcionários',
                'class' => 'inptText',
                'id' => 'qtdConferentes',
                'size' => 4,
            ))
            ->addElement('date', 'dataInicial', array(
                'label' => 'Data Inicio',
                'id' => 'dataInicial',
                'size' => 20,
                'class' => 'inptData',
            ))
            ->addElement('date', 'dataFinal', array(
                'label' => 'Data Fim',
                'class' => 'inptData',
                'id' => 'dataFinal',
                'size' => 20,
            ))
            ->addElement('button', 'buscar', array(
                'label' => 'Buscar',
                'class' => 'btn btnSearch',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('cpf', 'cpfBusca', array(
                'size' => 15,
                'label' => 'CPF Funcionário',
                'style' => 'width:190px;',
                'id' => 'cpfBusca',
                'class' => 'inptText',
            ))
            ->addDisplayGroup(array('qtdConferentes','etiquetaInicial','etiquetaFinal','showIntervalo','pessoa'), 'identificacao', array('legend' => 'Vincular Etiqueta Separação'))
            ->addDisplayGroup(array('dataInicial','dataFinal','cpfBusca','buscar'), 'consulta', array('legend' => 'Consulta'));

        $this->getElement('etiquetaInicial')->setAttrib('onkeydown','gotoFinal(event)');
        $this->getElement('etiquetaFinal')->setAttrib('onkeydown','gotoPessoa(event)');
        $this->getElement('pessoa')->setAttrib('onkeydown','gotoBuscar(event)');
    }

    public function getApontamentos($cpf, $dataInicio, $dataFim){
        $where = '';
        $sql = "SELECT
                    P.NOM_PESSOA,
                    PF.NUM_CPF,
                    (EP.ETIQUETA_INICIAL || ' - ' || EP.ETIQUETA_FINAL) AS INTERVALO,
                    ((EP.ETIQUETA_FINAL - EP.ETIQUETA_INICIAL) + 1) AS TOTAL
                FROM
                  EQUIPE_SEPARACAO EP 
                  INNER JOIN PESSOA P ON (EP.COD_USUARIO = P.COD_PESSOA)
                  INNER JOIN PESSOA_FISICA PF ON (EP.COD_USUARIO = PF.COD_PESSOA);
                WHERE 1 = 1
                $where
                    ";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $key => $line){

        }


        return $result;
    }
}