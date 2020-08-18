<?php

namespace Wms\Module\Web\Form;

use Wms\Domain\Entity\Pessoa\Telefone\Tipo;
use Wms\Module\Web\Form;

class Query extends Form
{
    public function init($conexoes = null)
    {
        if ($conexoes != null) {
            $this->addElement('select', 'conexao', array(
                'label' => 'Conexão',
                'multiOptions' => $conexoes,
                'class' => 'focus',
                'required' => true
            ));

        $this->addElement('textarea', 'query', array(
            'label' => 'Query',
            'rows' => 20,
            'cols' => 20,
            'style' => 'height: 350px; width: 1000px;'

        ));
        $this->addElement('submit', 'btnBuscar', array(
            'class' => 'btn',
            'label' => 'Executar',
            'decorators' => array('ViewHelper'),
        ));

        $this->addElement('submit', 'btnPDF', array(
            'class' => 'btn',
            'label' => 'PDF',
            'decorators' => array('ViewHelper'),
        ));
        $this->addElement('submit', 'btnXLS', array(
            'class' => 'btn',
            'label' => 'EXCEL',
            'decorators' => array('ViewHelper'),
        ));
        $this->addDisplayGroup($this->getElements(), 'filtro', array('legend' => 'Filtro'));
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $reportRepo = $em->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');

    }
}
