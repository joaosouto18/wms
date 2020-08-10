<?php

namespace Wms\Service;

use Core\Util\String;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizado;

class RelatorioCustomizadoService extends AbstractService {

    /** @var RelatorioCustomizado _em */
    protected $_reportEn;
    protected $_filters;
    protected $_sort;
    /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoRepository $_reportRepo */
    protected $_reportRepo;

    /**
     * @return mixed
     */
    public function getReportEn()
    {
        return $this->_reportEn;
    }

    /**
     * @param mixed $reportEn
     */
    public function setReportEn($reportEn)
    {
        $this->_reportEn = $reportEn;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * @param mixed $filters
     */
    public function setFilters($filters)
    {
        $this->_filters = $filters;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->_sort;
    }

    /**
     * @param mixed $sort
     */
    public function setSort($sort)
    {
        $this->_sort = $sort;
    }

    private function populate($idRelatorio) {
        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoRepository $reportRepo */
        $reportRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');

        //$mock = $reportRepo->getExpedicaoReportMock();
        $dados = $reportRepo->getDadosReport($idRelatorio);

        $this->_reportRepo = $reportRepo;
        $this->_reportEn = $dados['reportEn'];
        $this->_filters = $dados['filters'];
        $this->_sort = $dados['sort'];
    }

    public function getAssemblyDataReport($idRelatorio) {

        $this->populate($idRelatorio);

        $title = $this->_reportEn->getTitulo();
        $query = $this->_reportEn->getQuery();
        $allowXLS = $this->_reportEn->getAllowXLS();
        $allowPDF = $this->_reportEn->getAllowPDF();
        $allowSearch = $this->_reportEn->getAllowSearch();

        $filterArr = array();
        foreach ($this->_filters as $f){
            $filterArr[] = array(
                'name' => $f['NOME_PARAM'],
                'label' => $f['DSC_TITULO'],
                'required' => $f['IND_OBRIGATORIO'],
                'query' => $f['DSC_QUERY'],
                'type' => $f['TIPO'],
                'size' => $f['TAMANHO'],
                'params' => $f['PARAMS']
            );
        }

        $sortArr = array();
        foreach ($this->_sort as $s) {
            $sortArr[] = array(
                'label' => $s['DSC_TITULO'],
                'value' => $s['DSC_QUERY']
            );
        }

        $result = array (
            'title' => $title,
            'query' => $query,
            'filters' => $filterArr,
            'sort' => $sortArr,
            'allowXLS' => $allowXLS,
            'allowPDF' => $allowPDF,
            'allowSearch' => $allowSearch,
        );

        return $result;
    }

    public function executeReport($idRelatorio, $params) {
        $this->populate($idRelatorio);
        $query = $this->_reportEn->getQuery();

        foreach ($this->_filters as $filterObj) {
            $filterValue = "";
            if (isset($params[$filterObj['NOME_PARAM']]) && $params[$filterObj['NOME_PARAM']] != null)
                $filterValue = $params[$filterObj['NOME_PARAM']];

            if ($filterValue != '') {
                $filterValue = str_replace(':value' , $filterValue, $filterObj['DSC_QUERY']);
            }

            $query = str_replace(":" . $filterObj['NOME_PARAM'], $filterValue, $query );
        }

        if (isset($params['sort']) && $params['sort'] != null) {
            $query .= " ORDER BY " . $params['sort'];
        }

        $result = $this->_reportRepo->executeQuery($query, $this->_reportEn->getConexao());
        return $result;
    }

    public function getReports() {

        /** @var \Wms\Domain\Entity\RelatorioCustomizado\RelatorioCustomizadoRepository $reportRepo */
        $reportRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');
        $result = $reportRepo->getRelatoriosDisponiveis();

        $gruposRelatorios = array();

        foreach ($result as $r) {
            $k = null;
            $arrRelatorios = array();
            foreach ($gruposRelatorios as $key => $g) {
                if ($g['descricao'] == $r['DSC_GRUPO']) {
                    $k = $key;
                    $arrRelatorios = $g['relatorios'];
                }
            }

            $arrRelatorios[] = array(
                'id' => $r['COD_RELATORIO'],
                'titulo' => $r['DSC_TITULO']
            );

            $grupoRelatorio = array(
                'descricao' => $r['DSC_GRUPO'],
                'relatorios' => $arrRelatorios
            );

            if ($k === null) {
                $gruposRelatorios[] = $grupoRelatorio;
            } else {
                $gruposRelatorios[$k] = $grupoRelatorio;
            }
        }

        $result = array (
            'tipos' => $gruposRelatorios
        );

        return $result;
    }

}