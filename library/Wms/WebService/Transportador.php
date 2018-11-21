<?php

use Wms\Domain\Entity\Pessoa\Papel\Transportador as TransportadorEntity,
    Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity;

/**
 * 
 */
class Wms_WebService_Transportador extends Wms_WebService
{

    /**
     * Adiciona um Transportador no WMS, ao executar essa ação também é verificado 
     * o veiculo e a placa envolvidos, e caso necessário é feito a gravação dos mesmos
     * 
     * @param string $idTransportador ID 
     * @param string $cnpj Cnpj da empresa
     * @param string $razaoSocial Nome
     * @param string $nomeFantasia Nome Fantasia
     * @param string $placa Placa para adicionar ao veiculo
     * @return boolean|Exception se o Transportador foi inserido com sucesso ou não
     */
    public function salvar($idTransportador, $cnpj, $razaoSocial, $nomeFantasia, $placa)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        $em->beginTransaction();

        try {
            $service = $this->__getServiceLocator()->getService('Transportador');
            $transportadorEntity = $service->findOneBy(array('idExterno' => $idTransportador));

            if ($transportadorEntity == null) {

                $resultSet = $em->createQueryBuilder()
                        ->select('p.nome')
                        ->from('wms:Pessoa\Papel\Transportador', 't')
                        ->innerJoin('t.pessoa', 'p')
                        ->where('p.cnpj = :cnpj')
                        ->setParameter('cnpj', \Core\Util\String::toNumber($cnpj))
                        ->getQuery()
                        ->getOneorNullResult();

                if ($resultSet)
                    throw new \Exception('O CNPJ ' . $cnpj . ' já está vinculado ao transportador ' . $resultSet['nome'] . '.');

                $transportadorEntity = new TransportadorEntity;

                $values = array(
                    'pessoa' => array(
                        'tipo' => 'J',
                        'juridica' => array(
                            'idExterno' => $idTransportador,
                            'idTipoOrganizacao' => '114',
                            'idRamoAtividade' => '272',
                            'dataAbertura' => '01/01/1970',
                            'nome' => $razaoSocial,
                            'nomeFantasia' => $nomeFantasia,
                            'cnpj' => \Core\Util\String::toNumber($cnpj),
                            'isAtivo' => 'S',
                        )
                    )
                );

                $em->getRepository('wms:Pessoa\Papel\Transportador')
                        ->save($transportadorEntity, $values);

                $em->flush();
            }

            // veiculo
            $veiculoEntity = $em->getRepository('wms:Movimentacao\Veiculo')->find($placa);

            if ($veiculoEntity == null) {
                $veiculoEntity = new VeiculoEntity;

                $values = array(
                    'identificacao' => array(
                        'idTipo' => 1,
                        'idTransportador' => $transportadorEntity->getId(),
                        'id' => $placa,
                        'descricao' => 'VEICULO DO WEBPDV',
                        'indFrotaPropria' => 'S',
                    )
                );

                $em->getRepository('wms:Movimentacao\Veiculo')
                        ->save($veiculoEntity, $values);
            }

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Retorna uma matriz com todas as filiais cadastrados no WMS
     * 
     * @return array|Exception
     */
    public function listar()
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
                ->select('p.id, p.nome, p.cnpj')
                ->from('wms:Pessoa\Papel\Transportador', 't')
                ->innerJoin('t.pessoa', 'p')
                ->orderBy('p.nome')
                ->getQuery()
                ->getArrayResult();

        return $result;
    }

}