<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\EtiquetaConferencia;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class EquipeSeparacaoRepository extends EntityRepository
{

    public function save($etiquetaInicial,$etiquetaFinal,$usuarioEn,$numFunc, $save = true)
    {
        $equipeSeparacao = new Expedicao\EquipeSeparacao();
        $equipeSeparacao->setCodUsuario($usuarioEn->getId());
        $equipeSeparacao->setDataVinculo(new \DateTime());
        $equipeSeparacao->setEtiquetaInicial($etiquetaInicial);
        $equipeSeparacao->setEtiquetaFinal($etiquetaFinal);
        $equipeSeparacao->setNumFuncionario($numFunc);
        $this->getEntityManager()->persist($equipeSeparacao);

        if($save===true)
            $this->getEntityManager()->flush();
    }

    /**
     * Retorna os intervalos das Etiquetas do Usuário
     * @param $usuarioEn EquipeSeparacao
     *
     * @return array
     */
    public function getIntervaloEtiquetaUsuario($usuarioEn) {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select("es.etiquetaInicial, es.etiquetaFinal")
            ->from("wms:Expedicao\EquipeSeparacao","es")
            ->where("es.codUsuario = :codUsuario ")
            ->addOrderBy("es.etiquetaInicial", "ASC")
            ->setParameter('codUsuario', $usuarioEn->getId());

        return $sql->getQuery()->getResult();
    }

    public function getApontamentosProdutividade($cpf, $dataInicio, $dataFim){
        $where = '';
        if (isset($dataInicio) && (!empty($dataInicio))) {
            $where .= " AND EP.DTH_VINCULO >= TO_DATE('$dataInicio','DD-MM-YYYY')";
        }
        if (isset($dataFim) && (!empty($dataFim))) {
            $where .= " AND EP.DTH_VINCULO <= TO_DATE('$dataFim','DD-MM-YYYY')";
        }
        if (isset($cpf) && (!empty($cpf))) {
            $where .= " AND PF.NUM_CPF = $cpf";
        }
        $sql = "SELECT
                    P.NOM_PESSOA,
                    EP.COD_EQUIPE_SEPARACAO,
                    (EP.ETIQUETA_INICIAL || ' - ' || EP.ETIQUETA_FINAL) AS INTERVALO,
                    ((EP.ETIQUETA_FINAL - EP.ETIQUETA_INICIAL) + 1) AS TOTAL,
                    DECODE(PF.NUM_CPF, NULL,NULL,
                    TRANSLATE(TO_CHAR(PF.NUM_CPF/100,'000,000,000.00'),',.','.-')) CPF
                FROM
                  EQUIPE_SEPARACAO EP
                  INNER JOIN PESSOA P ON (EP.COD_USUARIO = P.COD_PESSOA)
                  INNER JOIN PESSOA_FISICA PF ON (EP.COD_USUARIO = PF.COD_PESSOA)
                WHERE 1 = 1
                $where ";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
