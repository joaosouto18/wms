<?php

use Core\Controller\Action,
    Core\Grid,
    Wms\Entity\Pessoa,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Web_IndexController extends Wms\Module\Web\Controller\Action {

    public function indexAction() {

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRessuprimentoRepo */
        $ondaRessuprimentoRepo = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $result = $ondaRessuprimentoRepo->getOndasEmAbertoCompleto(null, null, \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_DIVERGENTE);
        if (count($result) > 0) {
            $link = '<a href="/relatorio_relatorio-ondas?idProduto=&grade=&=operador=&expedicao=&dataInicial=&dataFinal=&status=546&submit=Buscar" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Relatório</a>';
            $this->addFlashMessage("info","Existe(m) " . count ($result) . " Os de Ressuprimento Marcadas para Análise " . $link);
        }

        $params = array(
            'idRecebimento'=>'',
            'classe'=>'',
            'idLinhaSeparacao'=>'',
            'idTipoComercializacao'=>'',
            'indDadosLogisticos'=>'',
            'codigoBarras'=>'',
            'normaPaletizacao'=>'',
            'enderecoPicking'=>'N',
            'estoquePulmao'=>'S',
            'submit'=>'Buscar'
        );
        $produtos = $this->getEntityManager()->getRepository('wms:NotaFiscal')->relatorioProdutoDadosLogisticos($params);
        if (count($produtos) >0) {
            $link = '<a href="/relatorio_dados-logisticos-produto?idRecebimento=&classe=&idLinhaSeparacao=&idTipoComercializacao=&indDadosLogisticos=&codigoBarras=&normaPaletizacao=&enderecoPicking=N&estoquePulmao=S&submit=Buscar" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Relatório</a>';
            $this->addFlashMessage("info","Existe(m) produto(s) no pulmão sem picking cadastrado " . $link);
        }

        try {

            $datas = $this->_getAllParams();
            if (empty($datas['dataInicial1']) && empty($datas['dataInicial2'])) {
                $dataI1 = new \DateTime;
                $dataI2 = new \DateTime;

                $datas = array(
                    'dataInicial1' => '01/'.$dataI1->format('m/Y'),
                    'dataInicial2' => $dataI2->format('d/m/Y')
                );
            }
            $this->view->datas = $datas;

            $dataInicial1 = str_replace("/", "-", $datas['dataInicial1']);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $datas['dataInicial2']);
            $dataI2 = new \DateTime($dataInicial2);

            $dql = $this->em->createQueryBuilder()
                ->select('s.sigla status, count(r) qtty')
                ->from('wms:Recebimento', 'r')
                ->innerJoin('r.status','s')
                ->where('s.id IN (454, 456, 457, 459)')
                ->andWhere("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2)
                ->groupBy('s')
                ->orderBy('s.referencia', 'ASC');

            $status = array();
            $data = array();

            foreach ($dql->getQuery()->getResult() as $row) {
                array_push($status, $row['status']);
                array_push($data, $row['qtty']);
            }
            if (empty($status) && empty($data)) {
                $status = false;
                $data = false;
            }

            $this->view->recebimentoStatus = json_encode($status, JSON_NUMERIC_CHECK);
            $this->view->recebimentoData = json_encode($data, JSON_NUMERIC_CHECK);

            $sql = $this->em->createQueryBuilder()
                ->select('s.sigla status, count(e2) qtty')
                ->from('wms:Expedicao', 'e2')
                ->innerJoin('e2.status','s')
                ->where('s.id IN (462,463,466,464,465)')
                ->andWhere("((TRUNC(e2.dataInicio) >= ?1 AND TRUNC(e2.dataInicio) <= ?2) OR e2.dataInicio IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2)
                ->groupBy('s')
                ->orderBy('s.referencia', 'ASC');

            $statusExpedicao = array();
            $dados = array();
            foreach ($sql->getQuery()->getResult() as $value) {
                array_push($statusExpedicao, $value['status']);
                array_push($dados, $value['qtty']);
            }

            if (empty($statusExpedicao) && empty($dados)) {
                $statusExpedicao = false;
                $dados = false;
            }

            $this->view->expedicaoStatus = json_encode($statusExpedicao,JSON_NUMERIC_CHECK);
            $this->view->expedicaoData = json_encode($dados, JSON_NUMERIC_CHECK);

            $qtdProdutosGroupDadosLogisticos = $this->em->getRepository('wms:Produto')->buscarQtdProdutosDadosLogisticos();
            $produtosComDadosLogisticos = $qtdProdutosGroupDadosLogisticos['SIM'];
            $this->view->produtosComDadosLogisticos = (int) $produtosComDadosLogisticos;

            $produtosSemDadosLogisticos = $qtdProdutosGroupDadosLogisticos['NAO'];
            $this->view->produtosSemDadosLogisticos = (int) $produtosSemDadosLogisticos;

        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

}