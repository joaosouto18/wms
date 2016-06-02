<?php

namespace Wms\Module\Importacao\Grid;

use Wms\Module\Web\Grid;

class ConfiguracaoImportacao extends Grid
{

    public function init()
    {

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('a.id, a.tabelaDestino, a.nomeArquivo, a.caracterQuebra, a.cabecalho, a.sequencia, a.ativo')
            ->from('wms:Importacao\Arquivo','a')
            ->orderBy('a.sequencia');

        $this->setAttrib('title','Configuração da importação');
        $this->setSource(new \Core\Grid\Source\Doctrine($source));
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
                'label' => 'Campos para deste elemento',
                'actionName' => 'lista-campos-importacao',
                'pkIndex' => array('id')
            ));

        return $this;
    }

}
