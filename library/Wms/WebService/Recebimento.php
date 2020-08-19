<?php

use Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\NotaFiscal\Item as ItemNF;

class Recebimento
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var DateTime
     */
    public $dataInicial;

    /**
     * @var DateTime
     */
    public $dataFinal;

    /**
     * @var string
     */
    public $status;

    /**
     * @var Nota[]
     */
    public $notasFiscais;

}
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

class Nota
{

    /**
     * @var string 
     */
    public $numero;

    /**
     * @var string 
     */
    public $serie;

    /**
     * @var string
     */
    public $idFornecedor;

    /**
     * @var string
     */
    public $dataEmissao;

    /**
     * @var Item[]
     */
    public $itens;

}

class NotasFiscais
{

    /**
     * @var Nota[]
     */
    public $notas = array();

}

class Wms_WebService_Recebimento extends Wms_WebService
{

    /**
     * Retorna um Recebimento específico no WMS pelo seu ID
     *
     * @param string $idRecebimento ID do Recebimento
     * @throws Exception
     * @return Recebimento
     */
    public function buscar($idRecebimento)
    {
        $idRecebimento = trim ($idRecebimento);

        /** @var \Wms\Domain\Entity\Recebimento $recebimento */
        $recebimento = $this->__getServiceLocator()->getService('Recebimento')->find($idRecebimento);

        if ($recebimento == null)
            throw new \Exception('Recebimento não encontrado');

        $dataInicial = $recebimento->getDataInicial();
        $dataFinal = $recebimento->getDataFinal();

        $recebimentoObj = new Recebimento();
        $recebimentoObj->id = $idRecebimento;
        $recebimentoObj->dataInicial = !empty($dataInicial)? $dataInicial : "-";
        $recebimentoObj->dataFinal = !empty($dataFinal)? $dataFinal : "-";
        $recebimentoObj->status = $recebimento->getStatus()->getSigla();
        /** @var \Wms\Domain\Entity\NotaFiscal $notaEn */
        foreach ($recebimento->getNotasFiscais() as $notaEn) {
            $nota = new Nota();
            $nota->idFornecedor = $notaEn->getEmissor()->getId();
            $nota->dataEmissao = $notaEn->getDataEmissao()->format("d/m/Y");
            $nota->numero = $notaEn->getNumero();
            $nota->serie = $notaEn->getSerie();
            foreach ($notaEn->getItens() as $itemEn) {
                $item = new Item();
                $item->idProduto = $itemEn->getId();
                $item->grade = $itemEn->getGrade();
                $item->quantidade = $itemEn->getQuantidade();
                $item->peso = $itemEn->getNumPeso();
                $nota->itens[] = $item;
            }
            $recebimentoObj->notasFiscais[] = $nota;
        }


        return $recebimentoObj;
    }
    
    /**
     * Salva um Recebimento no WMS. Se o Recebimento não existe, insere, senão, altera 
     * 
     * @param string idRecebimento do recebimento
     * @param string idFilial da filial 
     * @param NotasFiscais notasFiscais Matriz com dados do recebimento
     * @return boolean Se o recebimento foi salvo com sucesso ou não
     */
    public function salvar($idRecebimento, $idFilial, $notasFiscais)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Recebimento');
        
        $em->beginTransaction();

        try {
            $recebimento = $service->find($idRecebimento);

            $salvarNotas = false;

            if ($recebimento == null) {
                $recebimento = new Recebimento;
                $recebimento->setStatus(Recebimento::STATUS_INTEGRADO, 1);
                $salvarNotas = true;
            }

            $filial = $em->getRepository('wms:Filial')->findOneBy(array('idExterno' => $idFilial));

            if ($filial == null)
                throw new \Exception('Filial não encontrada');

            $recebimento->setId($idRecebimento)
                    ->setFilial($filial);

            if ($salvarNotas && isset($notasFiscais->notas)) {
                ///itera nas notas fiscais enviadas
                foreach ($notasFiscais->notas as $dadosNota) {
                    $fornecedor = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('codExterno' => $dadosNota->idFornecedor));

                    if ($fornecedor == null)
                        throw new \Exception('Fornecedor não encontrado');

                    $data = \DateTime::createFromFormat('d/m/Y', $dadosNota->dataEmissao);

                    $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_EM_RECEBIMENTO);

                    //inserção de nova NF
                    $notaFiscal = new NotaFiscalEntity; //cria nova nota fiscal
                    $notaFiscal->setNumero($dadosNota->numero)
                            ->setSerie($dadosNota->serie)
                            ->setDataEmissao($data)
                            ->setRecebimento($recebimento)
                            ->setFornecedor($fornecedor)
                            ->setStatus($statusEntity);

                    if (isset($dadosNota->itens)) {
                        //itera nos itens das notas
                        foreach ($dadosNota->itens as $dadosItem) {
                            $produto = $em->getReference('wms:Produto', $dadosItem->idProduto);
                            $item = new ItemNF;
                            $item->setNotaFiscal($notaFiscal)
                                    ->setProduto($produto)
                                    ->setGrade((empty($dadosItem->grade) || $dadosItem->grade === "?") ? "UNICA" : trim($dadosItem->grade))
                                    ->setQuantidade($dadosItem->quantidade);

                            $notaFiscal->getItens()->add($item);
                        }
                    }

                    $recebimento->getNotasFiscais()->add($notaFiscal);
                }
            }

            $em->persist($recebimento);
            $em->flush();
            
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        
        return true;
    }

    /**
     * Lista todos os Recebimentos cadastrados no sistema
     * 
     * @return array
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('r.id as idRecebimento, r.dataInicial, r.dataFinal, r.status')
                ->from('wms:Recebimento', 'r')
                ->orderBy('r.dataInicial')
                ->getQuery()
                ->getArrayResult();

        return $result;
    }

    /**
     * Retorna o Status do  Recebimento específico no WMS pelo seu ID
     *
     * @param string $idRecebimento ID do Recebimento
     * @return string
     */
    public function status($idRecebimento)
    {
        $recebimento = $this->__getServiceLocator()->getService('Recebimento')->find($idRecebimento);

        if ($recebimento == null)
            throw new \Exception('Recebimento não encontrado');

        return $recebimento->getStatus()->getSigla();
    }

}