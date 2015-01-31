<?php

namespace Wms\Domain\Entity\MapaSeparacao;

use Doctrine\ORM\EntityRepository;


class ModeloSeparacaoRepository extends EntityRepository
{

    public function findModeloSeparacaoById($idModeloSeparacao) {


        $query = "SELECT ms
                FROM wms:MapaSeparacao\ModeloSeparacao ms
                WHERE ms.id = $idModeloSeparacao
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    /**
     * getTipoSeparacao: Retorna os tipos de separacao para cadastro e consulta
     * @return $arrayRetorno : Array Com dados das colunas TIPO_SEPARACAO_FRACIONADO E NAOFRACIONADO
     *
     */
    public function getTipoSeparacao(){
        $arrayRetorno['ETIQUETA']='ETIQUETA';
        $arrayRetorno['MAPA']='MAPA';

        return $arrayRetorno;

    }

    /**
     * getTipoQuebra: Retorna os tipos de quebra para cadastro e consulta
     * @return $arrayRetorno : Array Com dados das colunas TIPO_QUEBRA_FRACIONADO E NAOFRACIONADO
     *
     */
    public function getTipoQuebra(){
        $arrayRetorno['RUA']='RUA';
        $arrayRetorno['PRACA']='PRAÇA';
        $arrayRetorno['CLIENTE']='CLIENTE';
        $arrayRetorno['SEM QUEBRA']='SEM QUEBRA';
        $arrayRetorno['LINHA DE SEPARACAO']='LINHA DE SEPARAÇÃO';

        return $arrayRetorno;

    }

    public function salvar($valores){
        $entity= new \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao();

        $entity->setTipoSeparacaoFracionado($valores['identificacao']['tipoSFracionado']);
        $entity->setTipoSeparacaoNaofracionado($valores['identificacao']['tipoSNfracionado']);
        $entity->setTipoQuebraFracionado($valores['identificacao']['tipoQFracionado']);
        $entity->setTipoQuebraNaofracionado($valores['identificacao']['tipoQNfracionado']);
        $entity->setQuebraColetor($valores['identificacao']['quebraColetor']);
        $entity->setEmitirEtiquetaMae($valores['identificacao']['emitirEtiquetaMae']);
        $entity->setEmitirEtiquetaMapa($valores['identificacao']['emitirEtiquetaMapa']);
        $entity->setConversaoFatorProduto($valores['identificacao']['conversaoFatorProduto']);
        $this->getEntityManager()->persist($entity);

        $this->getEntityManager()->flush();
       // $this->getEntityManager()->commit();
    }

    public function editar($entity,$valores){
        $this->repository->save($entity,$valores);
        $this->getEntityManager()->flush();
        // $this->getEntityManager()->commit();
    }



}