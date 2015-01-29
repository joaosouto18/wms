<?php

namespace Wms\Module\Web\Form\Relatorio\Recebimento;

use Wms\Domain\Entity\Recebimento,
    Wms\Module\Web\Form;

/**
 * Description of FiltroListagemProdutosSintetico
 *
 * @author Jéssica Mayrink <jmayrinkfonseca@gmail.com.br>
 */
class FiltroListagemProdutosSintetico extends Form
{

    public function init()
    {
        $em = $this->getEm();

        //repositories
        $linhasSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();

        //form's attr
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'target' => '_blank',
            'id' => 'filtro-dados-logisticos-produtos-form',
        ));

        $this->addElement('select', 'idLinhaSeparacao', array(
                    'label' => 'Linha de Separação',
                    'multiOptions' => $linhasSeparacao,
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idLinhaSeparacao',  'submit'), 'identificacao', array('legend' => 'Busca')
        );
    }

}