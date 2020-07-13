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

    public function getDadosReport($idRelatorio) {

        $reportRepo = $this->_em->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');
        $mock = $reportRepo->getProdutosReportMock();
        $this->_filters = $mock['filters'];
        $this->_sort = $mock['sort'];
        $this->_reportEn = $mock['reportEn'];

        $title = $this->_reportEn->getTitulo();
        $query = $this->_reportEn->getQuery();

        $filterArr = array();
        foreach ($this->_filters as $f){
            $filterArr[] = array(
                'name' => $f['NOME_PARAM'],
                'label' => $f['DSC_TITULO'],
                'required' => $f['IND_OBRIGATORIO'],
                'query' => $f['DSC_QUERY'],
                'type' => $f['TIPO']
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
            'sort' => $sortArr
        );

        return $result;
    }

    public function buildQuery() {

    }

}
