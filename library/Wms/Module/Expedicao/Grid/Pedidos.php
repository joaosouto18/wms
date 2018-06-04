<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class Pedidos extends Grid
{
    public function init($idAcao, $params)
    {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
        $acaoEn = $acaoIntRepo->find($idAcao);
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['submit']);
        unset($params['salvar']);
        $result = $acaoIntRepo->processaAcao($acaoEn, $params);
        $this->setAttrib('title','pedidos-erp');

        $this->setSource(new \Core\Grid\Source\ArraySource($result))

            ->addColumn(array(
                'label' => 'Selecionar',
                'index' => 'CARGA',
                'render' => 'Checkbox'
            ))
            ->addColumn(array(
                'label' => 'Cod. Carga',
                'index' => 'CARGA',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Produto',
                'index' => 'QTD',
            ));

        $this->setShowExport(false);
        $this->setButtonForm('Salvar');
        return $this;
    }

}

