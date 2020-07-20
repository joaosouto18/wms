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

    private function populate($idRelatorio) {
        $reportRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');
        $mock = $reportRepo->getExpedicaoReportMock();

        $this->_reportEn = $mock['reportEn'];
        $this->_filters = $mock['filters'];
        $this->_sort = $mock['sort'];
    }

    public function getAssemblyDataReport($idRelatorio) {

        $this->populate($idRelatorio);

        $title = $this->_reportEn->getTitulo();
        $query = $this->_reportEn->getQuery();

        $filterArr = array();
        foreach ($this->_filters as $f){
            $filterArr[] = array(
                'name' => $f['NOME_PARAM'],
                'label' => $f['DSC_TITULO'],
                'required' => $f['IND_OBRIGATORIO'],
                'query' => $f['DSC_QUERY'],
                'type' => $f['TIPO'],
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
            'sort' => $sortArr
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

        $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getReports() {
        $reportRepo = $this->getEntityManager()->getRepository('wms:RelatorioCustomizado\RelatorioCustomizado');
        $result = $reportRepo->getRelatoriosDisponiveisMock();

        $tiposRelatorios = array();

        foreach ($result as $r) {
            $k = null;
            $arrRelatorios = array();
            foreach ($tiposRelatorios as $key => $t) {
                if ($t['descricao'] == $r['TIPO']) {
                    $k = $key;
                    $arrRelatorios = $t['relatorios'];
                }
            }

            $arrRelatorios[] = array(
                'id' => $r['COD_RELATORIO'],
                'titulo' => $r['DSC_TITULO']
            );

            $tipoRelatorio = array(
                'descricao' => $r['TIPO'],
                'relatorios' => $arrRelatorios
            );

            if ($k === null) {
                $tiposRelatorios[] = $tipoRelatorio;
            } else {
                $tiposRelatorios[$k] = $tipoRelatorio;
            }
        }

        $result = array (
            'tipos' => $tiposRelatorios
        );

        return $result;
    }

}
