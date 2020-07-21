<?php
namespace Wms\Domain\Entity\RelatorioCustomizado;

use Doctrine\ORM\EntityRepository;

class RelatorioCustomizadoRepository extends EntityRepository
{
    public function getFilterContent($query) {
        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $arrResult = array();
        foreach ($result as $r) {
            $arrResult[$r['VALUE']] = $r['LABEL'];
        }
        return $arrResult;
    }

    public function executeQuery($query, $conexaoEn) {
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->getEntityManager()->getRepository('wms:Integracao\ConexaoIntegracao');

        if ($conexaoEn == null) {
            $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = $conexaoRepo->runQuery($query, $conexaoEn, false);
        }

        return $result;
    }

    public function getDadosReport($idRelatorio) {
        $reportEn = $this->find($idRelatorio);

        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoSortRepository $reportRepo */
        $sortRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizadoSort');
        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoFilroRepository $filterRepo */
        $filterRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizadoFiltro');

        $sorts = $sortRepo->findBy(array('relatorio'=> $reportEn));
        $filters = $filterRepo->findBy(array('relatorio'=> $reportEn));

        $sortArr = array();
        $filtersArr = array();

        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoSort $s */
        foreach ($sorts as $s) {
            $sortArr[] = array(
                'DSC_TITULO' => $s->getTitulo(),
                'DSC_QUERY' => $s->getQuery()
            );
        }

        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoFiltro $f */
        foreach ($filters as $f) {
            $filtersArr[] = array(
                'NOME_PARAM' => $f->getNomeParam(),
                'DSC_TITULO' => $f->getTitulo(),
                'IND_OBRIGATORIO' => $f->getObrigatorio(),
                'TIPO' => $f->getTipo(),
                'PARAMS' => $f->getParams(),
                'TAMANHO' => $f->getTamanho(),
                'DSC_QUERY' => $f->getQuery()
            );
        }

        $result = array(
            'reportEn' => $reportEn,
            'filters' => $filtersArr,
            'sort' => $sortArr
        );

        return $result;

    }

    public function getRelatoriosDisponiveis() {
        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();

        $sql = "SELECT DISTINCT
                       R.COD_RELATORIO_CUSTOMIZADO as COD_RELATORIO,
                       R.DSC_TITULO_RELATORIO as DSC_TITULO,
                       R.DSC_GRUPO_RELATORIO as DSC_GRUPO
                  FROM RELATORIO_CUSTOMIZADO R
                  LEFT JOIN RELATORIO_CUST_PERFIL_USUARIO RP ON R.COD_RELATORIO_CUSTOMIZADO = RP.COD_RELATORIO_CUSTOMIZADO
                 WHERE R.DTH_INATIVACAO IS NULL
                   AND COD_PERFIL_USUARIO IN (SELECT COD_PERFIL_USUARIO 
                                                FROM usuario_perfil_usuario 
                                               WHERE COD_USUARIO = $idUsuario)";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

}