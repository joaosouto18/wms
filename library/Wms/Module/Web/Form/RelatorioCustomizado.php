<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;

class RelatorioCustomizado extends Form
{
    public function init($assemblyData = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();

        $reportRepo = $em->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');

        $filters = array();
        $sort = array();

        if (isset($assemblyData) && ($assemblyData != null)) {
            $sort = $assemblyData['sort'];
            $filters = $assemblyData['filters'];
        }

        foreach ($filters as $filterOption) {
            $required = false;
            $type = $filterOption['type'];
            $name = $filterOption['name'];
            $label = $filterOption['label'];
            $params = array();

            if ($filterOption['required'] == "S") $required = true;

            if ($filterOption['type'] == "SQL") {
                $type = 'Select';
                $params['multiOptions'] = $reportRepo->getFilterContent($filterOption['params']);
            }

            $params['required'] = $required;
            $params['label'] = $label;

            $this->addElement($type, $name, $params);
        }

        if ($sort != null) {
            $srt = array();
            $firstOpt = "";
            foreach ($sort as $srtOption) {
                if ($firstOpt == "") $firstOpt = $srtOption['value'];
                $srt[$srtOption['value']] = $srtOption['label'];
            }

            $this->addElement('select', 'sort', array(
                'label' => 'Ordenação',
                'multiOptions' => $srt,
                'required' => true,
                'value' => $firstOpt
            ));
        }

        $this->addElement('submit', 'btnBuscar', array(
            'class' => 'btn',
            'label' => 'Buscar',
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
}
