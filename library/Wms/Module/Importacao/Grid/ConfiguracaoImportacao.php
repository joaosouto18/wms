<?php

namespace Wms\Module\Importacao\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Module\Web\Grid;

class ConfiguracaoImportacao extends Grid
{

    public function init()
    {
        /** @var EntityManager $em */
        $em = $this->getEntityManager();

        $source = $em->createQueryBuilder()
            ->select('a.id, a.tabelaDestino, a.nomeArquivo, a.caracterQuebra, a.cabecalho, a.sequencia, a.ativo, a.ultimaImportacao')
            ->from('wms:Importacao\Arquivo','a')
            ->orderBy('a.sequencia');

        $result = array();

        foreach ($source->getQuery()->getResult() as $arquivo){
            if (!empty($arquivo['ultimaImportacao'])) {
                $arquivo['ultimaImportacao'] = date_format($arquivo['ultimaImportacao'],'d/m/Y');
            } else {
                $arquivo['ultimaImportacao'] = 'S/ registro';
            }
            array_push($result,$arquivo);
        }

        $this->setAttrib('title','Configuração da importação');
        $this->setSource(new \Core\Grid\Source\ArraySource($result));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Ordem de importação',
                'index' => 'sequencia',
            ))->addColumn(array(
                'label' => 'Destino',
                'index' => 'tabelaDestino'
             ))
            ->addColumn(array(
                'label' => 'Arquivo',
                'index' => 'nomeArquivo',
            ))
            ->addColumn(array(
                'label' => 'Ultima Importação',
                'index' => 'ultimaImportacao'
            ))
            ->addColumn(array(
                'label' => 'Caracter de quebra',
                'index' => 'caracterQuebra',
            ))
            ->addColumn(array(
                'label' => 'Tem cabeçalho',
                'index' => 'cabecalho',
            ))
            ->addColumn(array(
                'label' => 'Está ativo',
                'index' => 'ativo',
            ))
            ->addAction(array(
                'label' => 'Alterar o status desta importacao',
                'actionName' => 'alterar-status',
                'pkIndex' => array('id'),
            ))
            ->addAction(array(
                'label' => 'Campos deste elemento',
                'actionName' => 'lista-campos-importacao',
                'pkIndex' => array('id')
            ));

        return $this;
    }

}
