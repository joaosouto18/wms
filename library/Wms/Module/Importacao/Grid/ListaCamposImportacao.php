<?php

namespace Wms\Module\Importacao\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Module\Web\Grid;

class ListaCamposImportacao extends Grid
{

    public function init($idArquivo)
    {
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $source = $em->createQueryBuilder()
            ->select('c.id, a.tabelaDestino, c.nomeCampo, c.posicaoTxt, c.tamanhoInicio, c.tamanhoFim, c.valorPadrao, c.preenchObrigatorio')
            ->from('wms:Importacao\Campos','c')
            ->innerJoin('c.arquivo', 'a')
            ->orderBy('c.posicaoTxt, c.id')
            ->where('c.arquivo = :idArquivo')
            ->setParameter('idArquivo',$idArquivo );

        $this->setAttrib('title','Configuração da importação');
        $this->setSource(new \Core\Grid\Source\Doctrine($source));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Destino',
                'index' => 'tabelaDestino'
            ))
            ->addColumn(array(
                'label' => 'Nome do campo',
                'index' => 'nomeCampo',
            ))
            ->addColumn(array(
                'label' => 'Posição no arquivo',
                'index' => 'posicaoTxt'
             ))
            ->addColumn(array(
                'label' => 'Início dos carcteres',
                'index' => 'tamanhoInicio',
            ))
            ->addColumn(array(
                'label' => 'Fim dos carcteres',
                'index' => 'tamanhoFim',
            ))
            ->addColumn(array(
                'label' => 'Valor padrão',
                'index' => 'valorPadrao',
            ))
            ->addColumn(array(
                'label' => 'Preenchimento obrigatório',
                'index' => 'preenchObrigatorio',
            ))
            ->addAction(array(
                'label' => 'Alterar este campo',
                'actionName' => 'editar-campo-importacao',
                'pkIndex' => array('id'),
            ));

        return $this;
    }

}
