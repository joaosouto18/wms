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
        /*
         * INICIO COMENTARIO

          $metodo = 'salvar';
          $options = array(
          'soap_version' => SOAP_1_2,
          'exceptions' => true,
          'trace' => 1,
          'cache_wsdl' => WSDL_CACHE_NONE,
          'encoding' => 'ISO-8859-1'
          );
          // para produtos
          $params = array(
          "idProduto" => "191069",
          "descricao" => "BICICLETA X-BIKE 14 CARS 2 2325",
          "grade" => "UNICA",
          "idFabricante" => "191",
          "tipo" => 1,
          "idClasse" => "310101",
          );
          // para notas

          $itens = json_decode('[{"idProduto":"013052","quantidade":"14.0000","grade":"UNICA"}]');
          $itens = array(
          array(
          "idProduto" => "013052",
          "quantidade" => "14.0000",
          "grade" => "UNICA"
          )
          );

          $params = array(
          "idFornecedor" => "3858",
          "numero" => "113128",
          "serie" => "20",
          "dataEmissao" => "31/05/2012",
          "placa" => "AAA0000",
          "itens" => json_decode('[{"idProduto":"013052","quantidade":"14.0000","grade":"UNICA"}]'),
          );
         
          $client = new \Wms_WebService_Transportador();
          //var_dump($client->salvar(15, '81.184.749/0001-38', 'Teste LTDA', 'Teste', "AAA0000"));
          //        exit;
          //        $ws = new SoapClient('http://wmshomolog.moveissimonetti.com.br/soap/index/wsdl/service/notaFiscal', $options);
          //        var_dump($ws->__soapCall($metodo, $params));

          $itens = array(
          0 => array(
          "idProduto" => "164026",
          "quantidade" => "10.0000",
          "grade" => "UNICA",
          )
          );

          $params = array(
          "idFornecedor" => "2503",
          "numero" => "57643",
          "serie" => "55",
          "dataEmissao" => "14/05/2012",
          "placa" => "AAA0000",
          "itens" => $itens,
          );

         * FIM COMENTARIO
         */


        //$ws = new SoapClient('http://wmshomolog.moveissimonetti.com.br/soap/index/wsdl/service/notaFiscal', $options);
        //var_dump($ws->__soapCall($metodo, $params));
        //$client = new \Wms_WebService_NotaFiscal();
        //var_dump($client->salvar("2503", "57643", "55", "14/05/2012", "AAA0000", $itens));
        //$client->buscar($idFornecedor, $numero, $serie)
        //{"numero":"8263","serie":"1","dataEmissao":"27/04/2012","idFornecedor":"3857","placa":"AAA0000","itens":[{"idProduto":"078058","quantidade":"120.0000","grade":"UNICA"}]}'
        //var_dump($client->salvar("1069", "31.781.958/0001-90", "081.230.46-0", "IMADEL IBIRACU MADEIREIRA E MED LTDA"));
        //Ws NF
        //$client = new SoapClient('http://dev.wms/soap/index/wsdl/service/fornecedor');
        //var_dump($client->buscar('3857', '8263', '1')); exit;
        //var_dump($client->salvar('1069', '31.781.958/0001-90', '081.230.46-0', 'IMADEL IBIRACU MADEIREIRA E EMBALAG.LTDA'));
        //{"numero":"60573","serie":"1","dataEmissao":"24/04/2012","idFornecedor":"248","placa":"248","itens":[{"idProduto":"248120","quantidade":"100.0000","grade":"TAB/AMEN"},{"idProduto":"248120","quantidade":"150.0000","grade":"TAB/CINZ"},{"idProduto":"248120","quantidade":"200.0000","grade":"BR/TBDAR"}]}
        //var_dump($client->listar()); //retorna matriz de fornecedores
        //$client->buscar('ID'); //retorna ID
        //$client->excluir(123); //exclui fornecedor
//        $parametros = array('ID_RECEBIMENTO' => '87878');
//        $jasper = new Adl\Integration\RequestJasper();
//        header('Content-type: application/pdf');
//        echo $jasper->run('/reports/WMS/pallete', 'PDF', $parametros);
//        exit;
        //Locale::DEFAULT_LOCAL('pt_BR');
//        $locale = new Zend_Locale('pt_BR'); 
//        $locale = new Zend_Locale('en_US'); 
//        
//        Zend_Registry::set('Zend_Locale', $locale); // Onde está 'Zend_Locale' 
//        $data = new Zend_Date('07/08/2010', Zend_Registry::get('Zend_Locale')); 
//        
//
//        $data = new Zend_Date('07/08/2010'); 
//        //var_dump($data);exit;
//        //echo $data->toString(); // 07/08/2010 00:00:00 
//                echo "<br />";
//        echo $data->toString(Zend_Date::DATE_SHORT); // 07/08/2010 
//        $date = new \DateTime();
//        var_dump($date);exit;
        //$jasper = new Adl\Integration\RequestJasper();
        //header('Content-type: application/pdf');
        //echo $jasper->run('/reports/WMS/confCega', 'pdf', array());
        //exit;
        //To Save content to a file in the disk
        //The path where the file will be saved is registered into config/data.ini
        //$jasper->runReport('/reports/samples/AllAccounts','PDF', null, true);
        try {
            $dql = $this->em->createQueryBuilder()
                ->select('s.sigla status')
                ->addSelect('(SELECT COUNT(r) FROM wms:Recebimento r WHERE r.status = s.id) qtty
                        ')
                ->from('wms:Util\Sigla', 's')
                ->where('s.id IN (454, 456, 457, 459)')
                ->orderBy('s.referencia', 'ASC');

            $status = array();
            $data = array();

            foreach ($dql->getQuery()->getResult() as $row) {
                array_push($status, $row['status']);
                array_push($data, $row['qtty']);
            }

            $this->view->recebimentoStatus = json_encode($status, JSON_NUMERIC_CHECK);
            $this->view->recebimentoData = json_encode($data, JSON_NUMERIC_CHECK);

            $qtdProdutosGroupDadosLogisticos = $this->em->getRepository('wms:Produto')->buscarQtdProdutosDadosLogisticos();
            $produtosComDadosLogisticos = $qtdProdutosGroupDadosLogisticos['SIM'];
            $this->view->produtosComDadosLogisticos = (int) $produtosComDadosLogisticos;

            $produtosSemDadosLogisticos = $qtdProdutosGroupDadosLogisticos['NAO'];
            $this->view->produtosSemDadosLogisticos = (int) $produtosSemDadosLogisticos;

//            $query = $this->conn->query("
//                SELECT data, SUM(qtty) AS qtty, COUNT(*) AS qttyNF
//                FROM (
//                    SELECT to_char(nf.dat_emissao, 'YYYY-MM') AS data,
//                        (
//                            SELECT COUNT(*) 
//                            FROM nota_fiscal_item i
//                            WHERE i.cod_nota_fiscal = nf.cod_nota_fiscal
//                        ) qtty
//                    FROM nota_fiscal nf
//                    WHERE nf.dat_emissao >= ADD_MONTHS(SYSDATE, -3)
//                ) t
//                GROUP BY data
//                ORDER BY data ASC");
//
//            $data = array();
//            array_push($data, array('Mes', 'Notas Fiscais', 'Produtos'));
//
//            foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
//                array_push($data, array($row['DATA'], $row['QTTYNF'], $row['QTTY']));
//            }
//
//            $this->view->produtosPorMes = json_encode($data, JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

}