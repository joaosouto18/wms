<?php

use Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\NotaFiscal\Item as ItemNF;

class Item
{

    /**
     * @var string
     */
    public $idProduto;

    /**
     * @var string
     */
    public $grade;

    /**
     * @var integer
     */
    public $quantidade;

}

class Itens
{

    /**
     * @var Item[]
     */
    public $itens = array();

}

class Wms_WebService_NotaFiscal extends Wms_WebService
{

    /**
     * Retorna uma Nota Fiscal específico no WMS pelo seu ID.
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param integer $idStatus Codigo do status da nota fiscal no wms
     * @return array
     */
    public function buscar($idFornecedor, $numero, $serie, $dataEmissao, $idStatus)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Codigo de Fornecedor invalido');

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')->findOneBy(array(
            'fornecedor' => $fornecedorEntity->getId(),
            'numero' => $numero,
            'serie' => $serie,
            'dataEmissao' => \DateTime::createFromFormat('d/m/Y', $dataEmissao),
            'status' => $idStatus,
                ));

        if ($notaFiscalEntity == null)
            throw new \Exception('NotaFiscal não encontrada');

        $itemsNF = $em->getRepository('wms:NotaFiscal')->getConferencia($fornecedorEntity->getId(), $numero, $serie, $dataEmissao, $idStatus);
        $itens = array();

        foreach ($itemsNF as $item) {
            $itens[] = array(
                'idProduto' => $item['COD_PRODUTO'],
                'quantidade' => $item['QTD_ITEM'],
                'grade' => $item['DSC_GRADE'],
                'quantidadeConferida' => $item['QTD_CONFERIDA'],
                'quantidadeAvaria' => $item['QTD_AVARIA'],
                'motivoDivergencia' => $item['DSC_MOTIVO_DIVER_RECEB'],
            );
        }

        //verifica se existe recebimento, senao seta 0 no codigo do recebimento
        $idRecebimento = ($notaFiscalEntity->getRecebimento()) ? $notaFiscalEntity->getRecebimento()->getId() : 0;

        $dataEntrada = ($notaFiscalEntity->getDataEntrada()) ? $notaFiscalEntity->getDataEntrada()->format('d/m/Y') : '';

        return array(
            'idRecebimento' => $idRecebimento,
            'idFornecedor' => $notaFiscalEntity->getFornecedor()->getId(),
            'numero' => $notaFiscalEntity->getNumero(),
            'serie' => $notaFiscalEntity->getSerie(),
            'dataEmissao' => $notaFiscalEntity->getDataEmissao()->format('d/m/Y'),
            'placa' => $notaFiscalEntity->getPlaca(),
            'status' => $notaFiscalEntity->getStatus()->getSigla(),
            'dataEntrada' => $dataEntrada,
            'bonificacao' => $notaFiscalEntity->getBonificacao(),
            'itens' => $itens
        );
    }

    /**
     * Salva uma Nota Fiscal no WMS
     * 
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $placa Placa do veiculo vinculado à nota fiscal formato esperado: XXX0000
     * @param array  $itens
     * @param string $bonificacao Indica se a nota fiscal é ou não do tipo bonificação, Por padrão Não (N).
     * @return boolean
     */
    public function salvar($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao)
    {

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();

        try {
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

            if ($fornecedorEntity == null)
                throw new \Exception('Fornecedor não encontrado');

            $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
                    ->getAtiva($fornecedorEntity->getId(), $numero, $serie, $dataEmissao);

            if ($notaFiscalEntity != null)
                throw new \Exception('Nota fiscal já se encontra cadastrada');

            // caso haja um veiculo vinculado a placa
            if (empty($placa) || (strlen($placa) != 7))
                $placa = $em->getRepository('wms:Sistema\Parametro')->getValor(5, 'PLACA_PADRAO_NOTAFISCAL');

            $service = $this->__getServiceLocator()->getService('Veiculo');
            $veiculo = $service->get($placa);

            if ($veiculo == null)
                throw new \Exception('Veiculo de placa ' . $placa . ' não encontrado');

            if (!in_array($bonificacao, array('S', 'N')))
                throw new \Exception('Indicação de bonificação inválida. Deve ser N para não ou S para sim.');

            $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_INTEGRADA);

            //inserção de nova NF
            $notaFiscalEntity = new NotaFiscalEntity;
            $notaFiscalEntity->setNumero($numero)
                    ->setSerie($serie)
                    ->setDataEntrada(new \DateTime)
                    ->setDataEmissao(\DateTime::createFromFormat('d/m/Y', $dataEmissao))
                    ->setFornecedor($fornecedorEntity)
                    ->setBonificacao($bonificacao)
                    ->setStatus($statusEntity)
                    ->setPlaca($placa);

            if (count($itens) > 0) {
                //itera nos itens das notas
                foreach ($itens as $item) {
                    $produtoEntity = $em->getRepository('wms:Produto')->findOneBy(array('id' => $item['idProduto'], 'grade' => $item['grade']));

                    if ($produtoEntity == null)
                        throw new \Exception('Produto de código  ' . $item['idProduto'] . ' e grade ' . $item['grade'] . ' não encontrado');

                    $itemEntity = new ItemNF;
                    $itemEntity->setNotaFiscal($notaFiscalEntity)
                            ->setProduto($produtoEntity)
                            ->setGrade($item['grade'])
                            ->setQuantidade($item['quantidade']);

                    $notaFiscalEntity->getItens()->add($itemEntity);
                }
            }

            $em->persist($notaFiscalEntity);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Retorna Nota fiscal ativa no WMS ( Integrada, Em Recebimento ou Recebida )
     * 
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @return array
     */
    public function status($idFornecedor, $numero, $serie, $dataEmissao)
    {

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Codigo de Fornecedor invalido');

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
                ->getAtiva($fornecedorEntity->getId(), $numero, $serie, $dataEmissao);

        if ($notaFiscalEntity == null)
            throw new \Exception('Nota Fiscal não encontrada');

        return array(
            'id' => $notaFiscalEntity->getStatus()->getId(),
            'descricao' => $notaFiscalEntity->getStatus()->getSigla()
        );
    }

    /**
     * Descarta uma nota desvinculando ela do recebimento. 
     * Ação pode ser executada em qualquer status em que a nota esteja.
     * 
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal descartada
     * @return boolean
     */
    public function descartar($idFornecedor, $numero, $serie, $dataEmissao, $observacao)
    {
        $dataEmissao = \DateTime::createFromFormat('d/m/Y', $dataEmissao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Codigo de Fornecedor invalido');

        $notaFiscalEntity = $this->__getServiceLocator()->getService('NotaFiscal')->findOneBy(array(
            'fornecedor' => $fornecedorEntity->getId(),
            'numero' => $numero,
            'serie' => $serie,
            'dataEmissao' => $dataEmissao,
                ));

        $em->getRepository('wms:NotaFiscal')->descartar($notaFiscalEntity->getId(), $observacao);

        return true;
    }

    /**
     * Desfazer uma nota, basicamente ela é cancelada. Caso o recebimento não possua mais notas ele também é cancelado
     * Ação pode ser executada em qualquer status válido ( Integrada, Em Recebimento ou Recebida ) em que a nota esteja.
     * 
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal foi desfeita
     * @return boolean
     */
    public function desfazer($idFornecedor, $numero, $serie, $dataEmissao, $observacao)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));

        if ($fornecedorEntity == null)
            throw new \Exception('Codigo de Fornecedor invalido');

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
                ->getAtiva($fornecedorEntity->getId(), $numero, $serie, $dataEmissao);

        if (!$notaFiscalEntity) 
            throw new \Exception('Não há Nota Fiscal válida para ser cancelada');

        $em->getRepository('wms:NotaFiscal')->desfazer($notaFiscalEntity->getId(), $observacao);

        return true;
    }

}

