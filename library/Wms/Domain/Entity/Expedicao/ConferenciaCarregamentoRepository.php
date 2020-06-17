<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\Expedicao;

class ConferenciaCarregamentoRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return ConferenciaCarregamento
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var ConferenciaCarregamento $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getConfsAndamento()
    {
        $statusGerado = ConferenciaCarregamento::STATUS_GERADO;
        $statusPendente = ConferenciaCarregamento::STATUS_EM_ANDAMENTO;

        $sql = "SELECT 
                    CONF_CARREG.COD_CONF_CARREG ID_CONF, 
                    CONF_CARREG.COD_EXPEDICAO,
                    COUNT(DISTINCT CCC.COD_CLIENTE) N_CLIENTES
                FROM CONFERENCIA_CARREGAMENTO CONF_CARREG
                INNER JOIN CONF_CARREG_CLIENTE CCC on CONF_CARREG.COD_CONF_CARREG = CCC.COD_CONF_CARREG
                WHERE CONF_CARREG.COD_STATUS IN ($statusGerado, $statusPendente)
                GROUP BY CONF_CARREG.COD_CONF_CARREG, CONF_CARREG.COD_EXPEDICAO";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function getExpedicoesToConf()
    {
        $tipoConfExp = ModeloSeparacao::TIPO_CONF_CARREG_EXP;
        $expFinalizada = Expedicao::STATUS_FINALIZADO;

        $sql = "SELECT DISTINCT
                    E.COD_EXPEDICAO,
                    P.COD_PESSOA 
                 FROM EXPEDICAO E
                 INNER JOIN MODELO_SEPARACAO MS ON MS.COD_MODELO_SEPARACAO = E.COD_MODELO_SEPARACAO
                 INNER JOIN CARGA C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                 INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                 INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                 WHERE PP.QUANTIDADE > NVL(PP.QTD_CORTADA, 0) AND MS.TIPO_CONF_CARREG = '$tipoConfExp' AND E.COD_STATUS = $expFinalizada
                 AND E.COD_EXPEDICAO NOT IN (SELECT COD_EXPEDICAO FROM CONFERENCIA_CARREGAMENTO)";

        $result = $this->_em->getConnection()->query($sql)->fetchAll();
        $return = [];
        foreach ($result as $row) {
            if (empty($return[$row['COD_EXPEDICAO']])) {
                $return[$row['COD_EXPEDICAO']] = [
                    'codExpedicao' => $row['COD_EXPEDICAO'],
                    'clientes' => [$row['COD_PESSOA'] => [
                        'id' => $row['COD_PESSOA']
                    ]]
                ];
            } else {
                if (empty($return[$row['COD_EXPEDICAO']]['clientes'][$row['COD_PESSOA']])) {
                    $return[$row['COD_EXPEDICAO']]['clientes'][$row['COD_PESSOA']] = [
                        'id' => $row['COD_PESSOA']
                    ];
                }
            }
            $return[$row['COD_EXPEDICAO']]['nClientes'] = count($return[$row['COD_EXPEDICAO']]['clientes']);
        }

        return $return;
    }

    public function checkConferenciaAberta($idConf)
    {
        $confCarregEn = $this->find($idConf);
        if (empty($confCarregEn))
            throw new \Exception("Conferência nº $idConf não encontrada");

        if ($confCarregEn->isFinalizado())
            throw new \Exception("A conferência nº $idConf já foi finalizada");

        return true;
    }

    /**
     * @param $params
     * @throws \Exception
     */
    public function verifyConditionNewConfCarreg($params)
    {
        $arrClientes = [];
        foreach ($params['clientes'] as $cliente) {
            $arrClientes[] = $cliente['id'];
        }
        $strCliente = implode(", ", $arrClientes);

        $sql = "SELECT 
                    CONFCARREG.COD_CONF_CARREG, P.NOM_PESSOA, CL.COD_CLIENTE_EXTERNO AS ID_CLIENTE
                FROM CONFERENCIA_CARREGAMENTO CONFCARREG
                INNER JOIN CONF_CARREG_CLIENTE CCC on CONFCARREG.COD_CONF_CARREG = CCC.COD_CONF_CARREG
                INNER JOIN CLIENTE CL ON CL.COD_PESSOA = CCC.COD_CLIENTE
                INNER JOIN PESSOA P ON P.COD_PESSOA = CCC.COD_CLIENTE
                WHERE CONFCARREG.COD_EXPEDICAO = $params[codExpedicao] AND CCC.COD_CLIENTE IN ($strCliente)";

        $verify = $this->_em->getConnection()->query($sql)->fetchAll();
        if (!empty($verify)) {
            $arrTxt = [];
            foreach ($verify as $item) {
                $arrTxt[] = "Cliente $item[ID_CLIENTE] - $item[NOM_PESSOA] já está na conferência de carregamento $item[COD_CONF_CARREG]";
            }
            throw new \Exception("Os itens abaixo impedem de iniciar esta conferência: <br /> ". implode("<br />", $arrTxt));
        }
    }

    /**
     * @param $keypass
     * @throws \Exception
     */
    public function getInfoToConfCarregByDanfe($keypass)
    {
        $sql = "SELECT 
                    C.COD_EXPEDICAO, 
                    CL.COD_PESSOA, 
                    CL.NOM_PESSOA, 
                    NFS.COD_CHAVE_ACESSO, 
                    NFS.NUMERO_NOTA || '-(' || NFS.SERIE || ')' AS NOTA
                FROM NOTA_FISCAL_SAIDA NFS
                INNER JOIN NOTA_FISCAL_SAIDA_PEDIDO NFSP on NFS.COD_NOTA_FISCAL_SAIDA = NFSP.COD_NOTA_FISCAL_SAIDA
                INNER JOIN PEDIDO PED ON PED.COD_PEDIDO = NFSP.COD_PEDIDO
                INNER JOIN CARGA C ON C.COD_CARGA = PED.COD_CARGA
                INNER JOIN PESSOA CL ON CL.COD_PESSOA = PED.COD_PESSOA
                INNER JOIN (SELECT C2.COD_EXPEDICAO, PED2.COD_PESSOA FROM NOTA_FISCAL_SAIDA NFS2
                            INNER JOIN NOTA_FISCAL_SAIDA_PEDIDO NFSP2 on NFS2.COD_NOTA_FISCAL_SAIDA = NFSP2.COD_NOTA_FISCAL_SAIDA
                            INNER JOIN PEDIDO PED2 ON PED2.COD_PEDIDO = NFSP2.COD_PEDIDO
                            INNER JOIN CARGA C2 ON C2.COD_CARGA = PED2.COD_CARGA
                            WHERE NFS2.COD_CHAVE_ACESSO = '$keypass'
                    ) KEY ON KEY.COD_PESSOA = PED.COD_PESSOA AND KEY.COD_EXPEDICAO = C.COD_EXPEDICAO
                WHERE NOT EXISTS(SELECT * FROM CONFERENCIA_CARREGAMENTO CONF_CARREG
                                INNER JOIN CONF_CARREG_CLIENTE CCC on CONF_CARREG.COD_CONF_CARREG = CCC.COD_CONF_CARREG 
                                WHERE CONF_CARREG.COD_EXPEDICAO = C.COD_EXPEDICAO AND CCC.COD_CLIENTE = PED.COD_PESSOA
                    )
              ";

        $result = $this->_em->getConnection()->query($sql)->fetchAll();
        if (empty($result)) throw new \Exception("Nenhuma nota pendente de conferência foi encontrada por este código $keypass");

        $expInfo = [];
        foreach ($result as $item) {
            if (empty($expInfo['codExpedicao'])) $expInfo['codExpedicao'] = $item['COD_EXPEDICAO'];

            if (empty($expInfo['clientes'][$item['COD_PESSOA']])) {
                $expInfo['clientes'][$item['COD_PESSOA']] = [
                    'id' => $item['COD_PESSOA'],
                    'nome' => $item['NOM_PESSOA'],
                    'totalDanfes' => 1,
                    'checked' => 0,
                    'danfes' => [
                        $item['COD_CHAVE_ACESSO'] => ['status' => false, 'nota' => $item['NOTA']]
                    ]
                ];
            } else {
                $expInfo['clientes'][$item['COD_PESSOA']]['danfes'][$item['COD_CHAVE_ACESSO']] = ['status' => false, 'nota' => $item['NOTA']];
            }
        }

        return $expInfo;
    }
}