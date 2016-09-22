<?php

use Core\Controller\Action,
    Core\Grid,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity;

class Web_IndexController extends Wms\Module\Web\Controller\Action {

    public function indexAction() {

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRessuprimentoRepo */
        $ondaRessuprimentoRepo = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $ondas = $ondaRessuprimentoRepo->getOndasEmAbertoCompleto(null, null, \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_DIVERGENTE);
        if (count($ondas) > 0) {
            $link = '<a href="/relatorio_relatorio-ondas?idProduto=&grade=&=operador=&expedicao=&dataInicial=&dataFinal=&status=546&submit=Buscar" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Relatório</a>';
            $this->addFlashMessage("info","Existe(m) " . count ($ondas) . " Os de Ressuprimento Marcadas para Análise " . $link);
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
            if (empty($datas['dataInicial1'])) {
                $dataI1 = new \DateTime;
                $datas['dataInicial1'] = '01/'.$dataI1->format('m/Y');
            }
            if ( empty($datas['dataInicial2'])) {
                $dataI2 = new \DateTime;
                $datas['dataInicial2'] = $dataI2->format('d/m/Y');
            }
            $form = new \Wms\Module\Web\Form\Index();

            $form->populate($datas);

            $this->view->form = $form;


            $dataInicial1 = str_replace("/", "-", $datas['dataInicial1']);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $datas['dataInicial2']);
            $dataI2 = new \DateTime($dataInicial2);

            $dql = $this->em->createQueryBuilder()
                ->select('s.id, s.sigla status, count(r) qtty')
                ->from('wms:Recebimento', 'r')
                ->innerJoin('r.status','s')
                ->where("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                //->where('s.id IN (454, 456, 457, 459)')
                //->andWhere("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2)
                ->groupBy('s')
                ->orderBy('s.referencia', 'ASC');


            $result = $this->ordernarByFluxo($dql->getQuery()->getResult(),'recebimento');
            if (empty($result['status']) && empty($result['qtty'])) {
                $result['status'] = false;
                $result['qtty'] = false;
            }

            $this->view->recebimentoStatus = json_encode($result['status'], JSON_NUMERIC_CHECK);
            $this->view->recebimentoData = json_encode($result['qtty'], JSON_NUMERIC_CHECK);
            $this->view->recebimentoTotal = json_encode($result['total'], JSON_NUMERIC_CHECK);

            $sql = $this->em->createQueryBuilder()
                ->select('s.id, s.sigla status, count(e2) qtty')
                ->from('wms:Expedicao', 'e2')
                ->innerJoin('e2.status','s')
                ->where("((TRUNC(e2.dataInicio) >= ?1 AND TRUNC(e2.dataInicio) <= ?2) OR e2.dataInicio IS NULL)")
                //->where('s.id IN (462,463,466,464,465)')
                //->andWhere("((TRUNC(e2.dataInicio) >= ?1 AND TRUNC(e2.dataInicio) <= ?2) OR e2.dataInicio IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2)
                ->groupBy('s')
                ->orderBy('s.referencia', 'ASC');

            $result = $this->ordernarByFluxo($sql->getQuery()->getResult(),'expedicao');
            if (empty($result['status']) && empty($result['qtty'])) {
                $result['status'] = false;
                $result['qtty'] = false;
            }

            $this->view->expedicaoStatus = json_encode($result['status'],JSON_NUMERIC_CHECK);
            $this->view->expedicaoData = json_encode($result['qtty'], JSON_NUMERIC_CHECK);
            $this->view->expedicaoTotal = json_encode($result['total'], JSON_NUMERIC_CHECK);

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

    private function ordernarByFluxo($arr, $op)
    {
        $arrStatus = null;
        
        /* Exemplo da estrutura do array de status vindo no parametro $arr
         *  array(
                array('id' => 454, 'status' => 'CRIADO', 'qtty' => '1'),
                array('id' => 457, 'status' => 'FINALIZADO', 'qtty' => '1'),
                array('id' => 460, 'status' => 'DESFEITO', 'qtty' => '3')
            );
        */

        /*
        STATUS_INTEGRADO => 462
        STATUS_EM_SEPARACAO => 463
        STATUS_EM_CONFERENCIA => 464
        STATUS_PRIMEIRA_CONFERENCIA => 551
        STATUS_SEGUNDA_CONFERENCIA => 552
        STATUS_PARCIALMENTE_FINALIZADO => 530
        STATUS_FINALIZADO => 465
        STATUS_CANCELADO => 466
        *///Array de IDs de status de expedição em ordem de processo;
        if ($op === 'expedicao')
            $arrStatus = array(462, 463, 464, 551, 552, 530, 465, 466);

        /*
        STATUS_INTEGRADO = 455;
        STATUS_CRIADO = 454;
        STATUS_INICIADO = 456;
        STATUS_FINALIZADO = 457;
        STATUS_CANCELADO = 458;
        STATUS_CONFERENCIA_CEGA = 459;
        STATUS_DESFEITO = 460;
        STATUS_CONFERENCIA_COLETOR = 461;
        *///Array de IDs de status de recebimento em ordem de processo;
        else if ($op === 'recebimento')
            $arrStatus  = array(454, 456, 461, 459, 457, 458, 460);

        $result = array(
            'status' => array(),
            'qtty' => array(),
            'total' => 0
        );
        if ($arrStatus) {
            foreach ($arrStatus as $idStatus) {
                foreach ($arr as $key => $item) {
                    if ($item['id'] === $idStatus) {
                        $result['status'][] = $item['status'];
                        $result['qtty'][] =  $item['qtty'];
                        $result['total'] += (int)$item['qtty'];
                        unset($arr[$key]);
                    }
                }
            }
        }

        return $result;
    }
}