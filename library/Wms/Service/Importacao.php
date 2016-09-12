<?php

namespace Wms\Service;

use Core\Grid\Exception;
use Core\Util\String;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\Entity;
use Wms\Domain\Entity\Armazenagem\Unitizador;
use Wms\Domain\Entity\CodigoFornecedor\Referencia;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Fabricante;
use Wms\Domain\Entity\Filial;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Pessoa\Fisica;
use Wms\Domain\Entity\Pessoa\Juridica;
use Wms\Domain\Entity\Pessoa\Papel\Cliente;
use Wms\Domain\Entity\Pessoa\Papel\Fornecedor;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Produto\Classe;
use Wms\Domain\Entity\Util\SiglaRepository;
use Wms\Module\Web\Controller\Action;
use Wms\Util\CodigoBarras;
use Zend\Stdlib\Configurator;
use Wms\Util\Endereco as EnderecoUtil;

class Importacao
{

    public function saveClasse($em, $idClasse, $nome, $idClassePai = null, $repositorios)
    {
        try {
            /** @var \Wms\Domain\Entity\Produto\ClasseRepository $classeRepo */
            $classeRepo = $repositorios['classeRepo'];
            $entityClasse = $classeRepo->save((int)$idClasse, $nome, (int)$idClassePai, false);
            return $entityClasse;
        }catch (\Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * @param $em EntityManager
     * @param $arrDados
     * @throws \Exception
     */
    public function savePessoa($em, $arrDados)
    {
        //Configura a pessoa de acorodo  o seu tipo
        if ($arrDados['tipo'] == 'J') { //pessoa jurídica
            //retorna uma pessoa existente ou cria uma nova
            if (isset($arrDados['id']) && (int) $arrDados['id'] > 0) {
                $pessoa = $em->getRepository('wms:Pessoa\Juridica')->findOneBy(array("id"=>$arrDados["id"]));
            } else {
                $pessoa = new Juridica();
            }
            //transforma as datas de string ara DateTime
            if ($arrDados['dataAbertura'] != null) {
                $data = \DateTime::createFromFormat('d/m/Y', $arrDados['dataAbertura']);
                $arrDados['dataAbertura'] = $data;
            }

            $arrDados['cnpj'] = String::retirarMaskCpfCnpj($arrDados['cnpj']);

            if ($arrDados['idTipoOrganizacao'] != null) {
                $tipoOrganizacao = $em->getReference('wms:Pessoa\Organizacao\Tipo', $arrDados['idTipoOrganizacao']);
                $pessoa->setTipoOrganizacao($tipoOrganizacao);
            }

            if ($arrDados['idRamoAtividade'] != null) {
                $tipoRamoAtividade = $em->getReference('wms:Pessoa\Atividade\Tipo', $arrDados['idRamoAtividade']);
                $pessoa->setTipoRamoAtividade($tipoRamoAtividade);
            }

            $pessoa->setNome($arrDados['nome']);
            $pessoa->setNomeFantasia($arrDados['nome']);

            //configura através de um array de opções
            Configurator::configure($pessoa, $arrDados);
        } elseif ($arrDados['tipo'] == 'F') { //pessoa física

            //verifica se ja foi cadastrado o cpf informado
            $cpf = String::retirarMaskCpfCnpj($arrDados['cpf']);

            $pessoaFisicaEntity = $em->getRepository('wms:Pessoa\Fisica')->findOneBy(array('cpf' => $cpf));

            if ($pessoaFisicaEntity != null) {
                throw new \Exception('CPF ' . $pessoaFisicaEntity->getCpf() . ' já cadastrado.');
            }

            /** @var \Wms\Domain\Entity\Pessoa\Fisica $pessoa */
            $pessoa = new Fisica();

            //transforma as datas de string ara DateTime
            if (isset($arrDados['dataAdmissaoEmprego'])) {
                foreach (array('dataAdmissaoEmprego', 'dataExpedicaoRg', 'dataNascimento') as $item) {
                    $data = \DateTime::createFromFormat('d/m/Y', $arrDados[$item]);
                    if ($data) {
                        $arrDados[$item] = $data;
                    } else {
                        unset($arrDados[$item]);
                    }
                }
            }

            //configura através de um array de opções
            Configurator::configure($pessoa, $arrDados);
        } else { //tipo inválido
            throw new \Exception('Tipo de Pessoa inválido');
        }

        $em->persist($pessoa);

    }

    public function savePessoaEmCliente($em, $pessoa, $codExterno)
    {
        try {
            $entity = new Cliente();
            $entity->setPessoa($pessoa);
            $entity->setId($pessoa->getId());
            $entity->setCodClienteExterno($codExterno);
            $em->persist($entity);
            return $entity;
        }catch (\Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * @param $em EntityManager
     * @param $pessoa Pessoa
     * @param $codExterno string
     * @return bool|string
     */
    public function savePessoaEmFornecedor($em, $pessoa, $codExterno)
    {
        try {
            $entity = new Fornecedor();
            $entity->setPessoa($pessoa);
            $entity->setId($pessoa->getId());
            $entity->setIdExterno($codExterno);
            $em->persist($entity);
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }

    }

    public function saveCliente($em, $cliente)
    {
        try {
            $repositorios = array(
                'clienteRepo' => $em->getRepository('wms:Pessoa\Papel\Cliente'),
                'pessoaJuridicaRepo' => $em->getRepository('wms:Pessoa\Juridica'),
                'pessoaFisicaRepo' => $em->getRepository('wms:Pessoa\Fisica'),
                'siglaRepo' => $em->getRepository('wms:Util\Sigla'),
            );

            /** @var \Wms\Domain\Entity\Pessoa\Papel\ClienteRepository $ClienteRepo */
            $ClienteRepo = $repositorios['clienteRepo'];
            if (isset($cliente['codClienteExterno'])) {
                $entityCliente = $ClienteRepo->findOneBy(array('codClienteExterno' => $cliente['codClienteExterno']));
            } else {
                $entityCliente = null;
            }
            $entityPessoa = null;

            if ($entityCliente == null) {

                switch ($cliente['tipoPessoa']) {
                    case 'J':
                        $cliente['pessoa']['tipo'] = 'J';

                        $PessoaJuridicaRepo = $repositorios['pessoaJuridicaRepo'];
                        $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => String::retirarMaskCpfCnpj($cliente['cpf_cnpj'])));
                        if ($entityPessoa) {
                            break;
                        }

                        $cliente['pessoa']['juridica']['dataAbertura'] = null;
                        $cliente['pessoa']['juridica']['cnpj'] = $cliente['cpf_cnpj'];
                        $cliente['pessoa']['juridica']['idTipoOrganizacao'] = null;
                        $cliente['pessoa']['juridica']['idRamoAtividade'] = null;
                        $cliente['pessoa']['juridica']['nome'] = $cliente['nome'];
                        break;
                    case 'F':

                        $PessoaFisicaRepo = $repositorios['pessoaFisicaRepo'];
                        $entityPessoa = $PessoaFisicaRepo->findOneBy(array('cpf' => String::retirarMaskCpfCnpj($cliente['cpf_cnpj'])));
                        if ($entityPessoa) {
                            break;
                        }

                        $cliente['pessoa']['tipo'] = 'F';
                        $cliente['pessoa']['fisica']['cpf'] = $cliente['cpf_cnpj'];
                        $cliente['pessoa']['fisica']['nome'] = $cliente['nome'];
                        break;
                }

                $SiglaRepo = $repositorios['siglaRepo'];
                $entitySigla = $SiglaRepo->findOneBy(array('referencia' => $cliente['uf']));

                $cliente['cep'] = (isset($cliente['cep']) && !empty($cliente['cep']) ? $cliente['cep'] : '');
                $cliente['enderecos'][0]['acao'] = 'incluir';
                $cliente['enderecos'][0]['idTipo'] = \Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA;

                if (isset($cliente['complemento']))
                    $cliente['enderecos'][0]['complemento'] = $cliente['complemento'];
                if (isset($cliente['logradouro']))
                    $cliente['enderecos'][0]['descricao'] = $cliente['logradouro'];
                if (isset($cliente['referencia']))
                    $cliente['enderecos'][0]['pontoReferencia'] = $cliente['referencia'];
                if (isset($cliente['bairro']))
                    $cliente['enderecos'][0]['bairro'] = $cliente['bairro'];
                if (isset($cliente['cidade']))
                    $cliente['enderecos'][0]['localidade'] = $cliente['cidade'];
                if (isset($cliente['numero']))
                    $cliente['enderecos'][0]['numero'] = $cliente['numero'];
                if (isset($cliente['cep']))
                    $cliente['enderecos'][0]['cep'] = $cliente['cep'];
                if (isset($entitySigla))
                    $cliente['enderecos'][0]['idUf'] = $entitySigla->getId();

                $entityCliente = new \Wms\Domain\Entity\Pessoa\Papel\Cliente();

                if ($entityPessoa == null) {
                    $entityPessoa = $ClienteRepo->persistirAtor($entityCliente, $cliente, false);
                } else {
                    $entityCliente->setPessoa($entityPessoa);
                }

                $entityCliente->setId($entityPessoa->getId());
                $entityCliente->setCodClienteExterno($cliente['codClienteExterno']);

                $em->persist($entityCliente);

            }
            return $entityCliente;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveReferenciaProduto($em, $referencia){

        try {
            /**  @var EntityManager $em*/
            $entity = new Referencia();
            $entity->setIdProduto($referencia['idProduto']);
            $entity->setFornecedor($referencia['fornecedor']);
            $entity->setDscReferencia($referencia['dscReferencia']);

            $em->persist($entity);
            $em->flush();
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveFornecedor($em, $fornecedor, $verificarCpfCnpj = true)
    {

        /** @var \Wms\Domain\Entity\Pessoa\Papel\FornecedorRepository $fornecedorRepo */
        $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');

        $entityFornecedor = null;

        if (isset($fornecedor['idExterno'])) {
            $entityFornecedor = $fornecedorRepo->findOneBy(array('idExterno' => $fornecedor['idExterno']));
        }

        $entityPessoa = null;

        if ($entityFornecedor == null) {

            switch ($fornecedor['tipoPessoa']) {
                case 'J':
                    $fornecedor['pessoa']['tipo'] = 'J';

                    if($verificarCpfCnpj) {
                        $PessoaJuridicaRepo = $em->getRepository('wms:Pessoa\Juridica');
                        $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => String::retirarMaskCpfCnpj($fornecedor['cpf_cnpj'])));
                        if ($entityPessoa) {
                            break;
                        }
                    }

                    $fornecedor['pessoa']['juridica']['dataAbertura'] = null;
                    $fornecedor['pessoa']['juridica']['cnpj'] = $fornecedor['cpf_cnpj'];
                    $fornecedor['pessoa']['juridica']['idTipoOrganizacao'] = null;
                    $fornecedor['pessoa']['juridica']['idRamoAtividade'] = null;
                    $fornecedor['pessoa']['juridica']['nome'] = $fornecedor['nome'];
                    if (isset($fornecedor['inscricaoEstadual']) && !empty($fornecedor['inscricaoEstadual']))
                        $fornecedor['pessoa']['juridica']['inscricaoEstadual'] = $fornecedor['inscricaoEstadual'];

                    break;
                case 'F':

                    if ($verificarCpfCnpj) {
                        $PessoaFisicaRepo = $em->getRepository('wms:Pessoa\Fisica');
                        $entityPessoa = $PessoaFisicaRepo->findOneBy(array('cpf' => String::retirarMaskCpfCnpj($fornecedor['cpf_cnpj'])));

                        if ($entityPessoa) {
                            break;
                        }
                    }

                    $fornecedor['pessoa']['tipo']              = 'F';
                    $fornecedor['pessoa']['fisica']['cpf']     = $fornecedor['cpf_cnpj'];
                    $fornecedor['pessoa']['fisica']['nome']    = $fornecedor['nome'];
                    break;
            }


            if (isset($fornecedor['uf'])) {
                /** @var SiglaRepository $SiglaRepo */
                $SiglaRepo = $em->getRepository('wms:Util\Sigla');
                $entitySigla = $SiglaRepo->findOneBy(array('referencia' => $fornecedor['uf']));
            }

            $fornecedor['cep'] = (isset($fornecedor['cep']) && !empty($fornecedor['cep']) ? $fornecedor['cep'] : '');
            $fornecedor['enderecos'][0]['acao'] = 'incluir';
            $fornecedor['enderecos'][0]['idTipo'] = \Wms\Domain\Entity\Pessoa\Endereco\Tipo::COMERCIAL;

            if (isset($fornecedor['complemento']))
                $fornecedor['enderecos'][0]['complemento'] = $fornecedor['complemento'];
            if (isset($fornecedor['logradouro']))
                $fornecedor['enderecos'][0]['descricao'] = $fornecedor['logradouro'];
            if (isset($fornecedor['referencia']))
                $fornecedor['enderecos'][0]['pontoReferencia'] = $fornecedor['referencia'];
            if (isset($fornecedor['bairro']))
                $fornecedor['enderecos'][0]['bairro'] = $fornecedor['bairro'];
            if (isset($fornecedor['cidade']))
                $fornecedor['enderecos'][0]['localidade'] = $fornecedor['cidade'];
            if (isset($fornecedor['numero']))
                $fornecedor['enderecos'][0]['numero'] =  $fornecedor['numeor'];
            if (isset($fornecedor['cep']))
                $fornecedor['enderecos'][0]['cep'] = $fornecedor['cep'];
            if (isset($entitySigla))
                $fornecedor['enderecos'][0]['idUf'] = $entitySigla->getId();

            $entityFornecedor  = new Fornecedor();


            if ($entityPessoa == null) {
                $entityPessoa = $fornecedorRepo->persistirAtor($entityFornecedor, $fornecedor, false);
            }

            try {
                $entityFornecedor->setId($entityPessoa->getId());
                $entityFornecedor->setIdExterno($fornecedor['idExterno']);

                $em->persist($entityFornecedor);
                return true;
            }catch (\Exception $e){
                return $e->getMessage();
            }
        }
        return "Já existe fornecedor com este código ". $fornecedor['idExterno'];

    }

    public function saveNotaFiscal($em, $idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao = null)
    {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
        $notaFiscalEn = $notaFiscalRepo->findOneBy(array('numero' => $numero, 'serie' => $serie, 'fornecedor' => $idFornecedor));

        if (!$notaFiscalEn) {
            $entityNotaFiscal = $notaFiscalRepo->salvarNota($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao);
        } else {
            $entityNotaFiscal = $notaFiscalRepo->salvarItens($itens, $notaFiscalEn);
        }
        return $entityNotaFiscal;

    }

    public function saveExpedicao($em, $placaExpedicao)
    {
        try {
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $em->getRepository('wms:Expedicao');
            $entityExpedicao = $expedicaoRepo->save($placaExpedicao);
            return $entityExpedicao;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveCarga($em, $carga)
    {
        try {
            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
            $cargaRepo = $em->getRepository('wms:Expedicao\Carga');

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expediacaoRepo */
            $expediacaoRepo = $em->getRepository('wms:Expedicao');

            $carga['idExpedicao'] = $expediacaoRepo->findOneBy(array('placaExpedicao' => $carga['placaExpedicao'], 'status' => array(Expedicao::STATUS_INTEGRADO, Expedicao::STATUS_EM_SEPARACAO, Expedicao::STATUS_EM_CONFERENCIA)));

            if ($carga['idExpedicao'] == null) {
                $carga['idExpedicao'] = $expediacaoRepo->save($carga['placaExpedicao']);
            }
            
            if ($carga['idExpedicao']->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_FINALIZADO) {
                return 'Expedicao ' . $carga['idExpedicao']->getId() . ' já está finalizada';
            }
            
            $entityCarga = $cargaRepo->findOneBy(array('codCargaExterno' => $carga['codCargaExterno']));
            if (!$entityCarga)
                $entityCarga = $cargaRepo->save($carga, false);

            return $entityCarga;
        } catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function savePedido($em, $pedido, $arrRepo)
    {
        /** @var EntityManager $em */
        try {

            if (!isset($pedido['pontoTransbordo']))
                $pedido['pontoTransbordo'] = null;

            $pedido['envioParaLoja'] = null;

            $pedido['itinerario'] = $em->getRepository('wms:expedicao\Itinerario')->findOneBy(array('id'=> $pedido['itinerario']));
            if (empty($pedido['itinerario']))
                throw new \Exception("Itinerário de código: $pedido[itinerario] não foi encontrado");

            $pedido['carga'] = $em->getRepository("wms:expedicao\Carga")->findOneBy(array('codCargaExterno' => (int)$pedido['codCargaExterno']));
            if (empty($pedido['carga']))
                throw new \Exception("Carga: $pedido[codCargaExterno] não foi encontrada");

            $pedido['pessoa'] = $em->getRepository('wms:Pessoa\Papel\Cliente')->findOneBy(array('codClienteExterno' => $pedido['codCliente']));

            if (empty($pedido['pessoa']) && !empty($pedido['cpf_cnpj'])) {
                $cpf_cnpjFormatado = \Core\Util\String::retirarMaskCpfCnpj($pedido['cpf_cnpj']);
                if (strlen($cpf_cnpjFormatado) == 11) {
                    $tipoCliente = "F";
                } else if (strlen($cpf_cnpjFormatado) == 14) {
                    $tipoCliente = "J";
                } else {
                    throw new \Exception("CNPJ ou CPF: $pedido[cpf_cnpj] fora do padrão, impossível cadastrar novo cliente para o pedido $pedido[codPedido]");
                }

                if ($tipoCliente == 'J') {
                    $pJuridicaRepo = $arrRepo['pJuridicaRepo'];
                    /** @var Juridica $entityPessoa */
                    $entityPessoa = $pJuridicaRepo->findOneBy(array('cnpj' => $cpf_cnpjFormatado));
                    if ($entityPessoa) {

                        /** @var Pessoa\Papel\ClienteRepository $clienteRepo */
                        $clienteRepo = $arrRepo['clienteRepo'];

                        /** @var Cliente $result */
                        $result = $clienteRepo->findOneBy(array('codPessoa' => $entityPessoa->getId()));

                        if (empty($result)){
                            $result = $this->savePessoaEmCliente($em, $entityPessoa, $pedido['codCliente']);
                        } else {
                            $result->setCodClienteExterno($pedido['codCliente']);
                            $em->persist($result);
                            $em->flush($result);
                        }

                        if (is_string($result)) {
                            throw new \Exception($result);
                        }

                        $pedido['pessoa'] = $result;
                    } else {
                        $nCliente = array(
                            'tipoPessoa' => $tipoCliente,
                            'codClienteExterno' => $pedido['codCliente'],
                            'cpf_cnpj' => $pedido['cpf_cnpj'],
                            'nome' => $pedido['nome'],
                            'uf' => $pedido['uf'],
                            'cidade' => $pedido['cidade']
                        );
                        $result = $this->saveCliente($em, $nCliente);
                        if (is_string($result)) {
                            throw new \Exception($result);
                        }
                        $pedido['pessoa'] = $result;
                    }
                } else if ($tipoCliente == 'F') {
                    $pFisicaRepo = $arrRepo['pFisicaRepo'];
                    $entityPessoa = $pFisicaRepo->findOneBy(array('cpf' => $cpf_cnpjFormatado));
                    if ($entityPessoa) {

                        /** @var Pessoa\Papel\ClienteRepository $clienteRepo */
                        $clienteRepo = $arrRepo['clienteRepo'];

                        /** @var Cliente $result */
                        $result = $clienteRepo->findOneBy(array('codPessoa' => $entityPessoa->getId()));

                        if (empty($result)) {
                            $result = $this->savePessoaEmCliente($em, $entityPessoa, $pedido['codCliente']);
                        } else {
                            $result->setCodClienteExterno($pedido['codCliente']);
                            $em->persist($result);
                            $em->flush($result);
                        }

                        if (is_string($result)) {
                            throw new \Exception($result);
                        }

                        $pedido['pessoa'] = $result;
                    } else {
                        $nCliente = array(
                            'tipoPessoa' => $tipoCliente,
                            'codClienteExterno' => $pedido['codCliente'],
                            'cpf_cnpj' => $pedido['cpf_cnpj'],
                            'nome' => $pedido['nome'],
                            'uf' => $pedido['uf'],
                            'cidade' => $pedido['cidade']
                        );
                        $result = $this->saveCliente($em, $nCliente);
                        if (is_string($result)) {
                            throw new \Exception($result);
                        }
                        $pedido['pessoa'] = $result;
                    }
                }
            }

            unset($pedido['cpf_cnpj']);
            unset($pedido['nome']);
            unset($pedido['cidade']);
            unset($pedido['uf']);

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
            $pedidoRepo = $em->getRepository('wms:Expedicao\Pedido');
            $entityPedido = $pedidoRepo->findOneBy(array('id' => $pedido['codPedido']));
            if (empty($entityPedido)) {
                $entityPedido = $pedidoRepo->save($pedido);
            }

            return $entityPedido;
        }catch (\Exception $e){
            return $e->getMessage();
        }

    }

    public function savePedidoProduto($em, $pedido, $flush = true)
    {
        try {
            /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
            $pedidoProdutoRepo = $em->getRepository('wms:Expedicao\PedidoProduto');
            $pedido['produto'] = $em->getRepository('wms:Produto')->findOneBy(array('id' => $pedido['codProduto'], 'grade' => $pedido['grade']));
            if (empty($pedido['produto'])){
                throw new \Exception("Produto: $pedido[codProduto] grade: $pedido[grade] não foi encontrado");
            }

            $pedido['pedido'] = $em->getRepository('wms:Expedicao\Pedido')->findOneBy(array('id' => $pedido['codPedido']));
            if (empty($pedido['pedido'])){
                throw new \Exception("Pedido: $pedido[codPedido] não foi encontrado");
            }

            $entityPedidoProduto = $pedidoProdutoRepo->findOneBy(array('codPedido' => $pedido['codPedido'], 'codProduto' => $pedido['produto']->getId(), 'grade' => $pedido['produto']->getGrade()));
            if (!$entityPedidoProduto)
                $entityPedidoProduto = $pedidoProdutoRepo->save($pedido);

            if ($flush)
                $em->flush();

            return $entityPedidoProduto;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveFabricante($em, $idFabricante, $nome, $repositorios)
    {   try {
            /** @var \Wms\Domain\Entity\FabricanteRepository $fabricanteRepo */
            $fabricanteRepo = $repositorios['fabricanteRepo'];
            $entityFabricante = $fabricanteRepo->save($idFabricante, $nome, false);
            return $entityFabricante;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveProduto($em, $produto, $repositorios)
    {
        /** @var EntityManager $em */
        try {
            $produtoRepo  = $repositorios['produtoRepo'];
            $enderecoRepo = $repositorios['enderecoRepo'];
            $produtoEntity = $produtoRepo->findOneBy(array('id' => $produto['id'], 'grade' => $produto['grade']));
            
            if ($produtoEntity == null) {
                $produtoEntity = new Produto();

                if (isset($produto['enderecoReferencia'])) {
                    $enderecoEn = $enderecoRepo->findOneBy(array('descricao'=>$produto['enderecoReferencia']));
                    if ($enderecoEn == null) {
                        throw new \Exception("Endereço de referencia para endereçamento automático inválido");
                    } else {
                        $produto['enderecoReferencia'] = $enderecoEn;
                    }
                }

                $temp = $produto['linhaSeparacao'];
                $produto['linhaSeparacao'] = $em->getReference('wms:Armazenagem\LinhaSeparacao', $produto['linhaSeparacao']);
                if (empty($produto['linhaSeparacao']))
                    throw new \Exception("Código de linha de separação $temp não encontrado.");

                $temp = $produto['tipoComercializacao'];
                $produto['tipoComercializacao'] = $em->getReference('wms:Produto\TipoComercializacao', $produto['tipoComercializacao']);
                if (empty($produto['tipoComercializacao']))
                    throw new \Exception("Código de tipo de comercialização $temp não encontrado.");

                $temp = $produto['classe'];
                $produto['classe'] = $em->getReference('wms:Produto\Classe', (int)$produto['classe']);
                if (empty($produto['classe']))
                    throw new \Exception("Código de classe $temp não encontrado.");

                $temp = $produto['fabricante'];
                $produto['fabricante'] = $em->getReference('wms:Fabricante', $produto['fabricante']);
                if (empty($produto['fabricante']))
                    throw new \Exception("Código de fabricante $temp não encontrado.");

                $sqcGenerator = new SequenceGenerator("SQ_PRODUTO_01",1);
                $produto['idProduto'] = $sqcGenerator->generate($em, $produtoEntity);

                Configurator::configure($produtoEntity, $produto);

                $em->persist($produtoEntity);
            }
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveFilial($em, $values)
    {
        try {
            /** @var \Wms\Domain\Entity\FilialRepository $filialRepo */
            $filialRepo = $em->getRepository('wms:Filial');
            $filianEn = $filialRepo->findOneBy(array('codExterno' => $values['pessoa']['juridica']['codExterno']));

            if (!$filianEn) {
                $filianEn = new Filial();
            }

            $filialRepo->save($filianEn, $values);
            return true;
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function saveEmbalagens($em, $registro, $repositorios)
    {
        try {
            /** @var EntityManager $em */

            $produtoRepo = $repositorios['produtoRepo'];
            $embalagemRepo = $repositorios['embalagemRepo'];

            $codigoBarras = "";
            if ($registro['codigoBarras'] != "") {
                $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($registro['codigoBarras']);
                $embalagemEntity = $embalagemRepo->findOneBy(array(
                    'codProduto' => $registro['codProduto'],
                    'grade' => $registro['grade'],
                    'codigoBarras' => $codigoBarras
                ));
            } else {
                $registro['CBInterno'] = 'S';
                $embalagemEntity = $embalagemRepo->findOneBy(array(
                    'codProduto' => $registro['codProduto'],
                    'grade' => $registro['grade'],
                    'quantidade' => $registro['quantidade']
                ));
            }

            $enderecoEn = null;
            if (!empty($registro['endereco'])) {

                $endereco = explode(".", $registro['endereco']);

                $arrDados['rua'] = $endereco[0];
                $arrDados['predio'] = $endereco[1];
                $arrDados['nivel'] = $endereco[2];
                $arrDados['apartamento'] = $endereco[3];

                $endereco = $em->getRepository('wms:Deposito\Endereco')
                    ->findOneBy(array(
                            'rua' => $endereco[0],
                            'predio' => $endereco[1],
                            'nivel' => $endereco[2],
                            'apartamento' => $endereco[3])
                    );

                if (empty($endereco))
                    throw new \Exception("O endereço $registro[endereco] não foi encontrado");

                $enderecoEn = $endereco;
            }

            /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEntity */
            if (empty($embalagemEntity)) {
                /** @var \Wms\Domain\Entity\Produto $produto */
                $produto = $produtoRepo->findOneBy(array(
                    'id' => $registro['codProduto'],
                    'grade' => $registro['grade'],
                ));

                if (empty($produto))
                    throw new \Exception("O produto $registro[codProduto] de grade $registro[grade] não foi encontrado");

                /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEntity */
                $embalagemEntity = new Produto\Embalagem();
                $embalagemEntity = \Wms\Domain\Configurator::configure($embalagemEntity, $registro);
                $embalagemEntity->setProduto($produto);
                if ($registro['codigoBarras'] == "") {
                    $codigoBarras = CodigoBarras::formatarCodigoEAN128Embalagem($embalagemEntity->getId());
                    $embalagemEntity->setCodigoBarras($codigoBarras);
                } else {
                    $embalagemEntity->setCodigoBarras($codigoBarras);
                }
                $embalagemEntity->setEndereco($enderecoEn);
                $em->persist($embalagemEntity);

            } else {
                $embalagemEntity->setEndereco($enderecoEn);
                $em->persist($embalagemEntity);
            }
            return true;
        }catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function saveNormaPaletizacao($em, $arrDados)
    {
        try {
            $entity = new Produto\NormaPaletizacao();

            $arrDados["unitizador"] = $em->getRepository('wms:Armazenagem\Unitizador')->findOneBy(array('id' => $arrDados['unitizador']));

            Configurator::configure($entity, $arrDados);
            $em->persist($entity);
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * @param $em EntityManager
     * @param $arrDados
     */
    public function saveUnitizador($em, $arrDados)
    {
        try {
            $entity = new Unitizador();
            Configurator::configure($entity, $arrDados);
            $em->persist($entity);
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * @param $em EntityManager
     * @param $arrDados
     */
    public function saveDadoLogistico($em, $arrDados)
    {
        try {
            //$produto = $em->getRepository("wms:Produto")->findOneBy( array("id" => $arrDados['id'], "grade" => $arrDados['grade']) );
            if (!is_object($arrDados["normaPaletizacao"]))
                $arrDados["normaPaletizacao"] = $em->getRepository('wms:Produto\NormaPaletizacao')->findOneBy(array("id" => $arrDados['normaPaletizacao']));

            if (!isset($arrDados['embalagem']) || !is_object($arrDados['embalagem'])) {
                if (isset($arrDados['codigoBarras']) && !empty($arrDados['codigoBarras'])) {
                    $criterio = array(
                        "codProduto" => $arrDados['codProduto'],
                        "grade" => $arrDados['grade'],
                        "codigoBarras" => $arrDados["codigoBarras"]
                    );
                    $arrDados["embalagem"] = $em->getRepository('wms:Produto\Embalagem')->findOneBy($criterio);
                } else if (isset($arrDados['quantidade']) && !empty($arrDados['quantidade'])) {
                    $criterio = array(
                        "codProduto" => $arrDados['codProduto'],
                        "grade" => $arrDados['grade'],
                        "quantidade" => $arrDados["quantidade"]
                    );
                    $arrDados["embalagem"] = $em->getRepository('wms:Produto\Embalagem')->findOneBy($criterio);
                }

                unset($arrDados['codProduto']);
                unset($arrDados['grade']);
                unset($arrDados['codigoBarras']);
                unset($arrDados['quantidade']);
            }

            if (!isset($arrDados['cubagem']) || empty($arrDados['cubagem'])){
                $arrDados['cubagem'] = $arrDados['largura'] * $arrDados['largura'] * $arrDados['profundidade'];
            }

            $arrDados['peso'] = str_replace(",", ".", strpos($arrDados['peso'], ",") ? $arrDados['peso'] : $arrDados['peso'] . ",0");
            //$arrDados['peso'] = Converter::brToEn($arrDados['peso'],-3);

            $entity = $em->getRepository('wms:Produto\DadoLogistico')->findOneBy($arrDados);

            if (!$entity) {
                $entity = new Produto\DadoLogistico();
                Configurator::configure($entity, $arrDados);
                $entity->setPeso($arrDados['peso'], true);
                $em->persist($entity);
            }
            return true;
        }catch (\Exception $e){
            $e->getMessage();
            return $e->getMessage();
        }
    }

    /**
     * @param $em EntityManager
     * @param $arrDados
     */
    public function saveEndereco($em, $arrDados)
    {
        try {
            $endereco = explode(".", $arrDados['endereco']);

            $arrDados['rua'] = $endereco[0];
            $arrDados['predio'] = $endereco[1];
            $arrDados['nivel'] = $endereco[2];
            $arrDados['apartamento'] = $endereco[3];

            $criterio = array(
                'rua' => $endereco[0],
                'predio' => $endereco[1],
                'nivel' => $endereco[2],
                'apartamento' => $endereco[3]
            );

            $entity = $em->getRepository('wms:Deposito\Endereco')->findOneBy($criterio);

            if (!$entity) {
            
                if (isset($arrDados['caracteristica']) && !empty($arrDados['caracteristica'])){
                    $temp = $arrDados['caracteristica'];
                    $arrDados['caracteristica'] = $em->getRepository('wms:Deposito\Endereco\Caracteristica')->findOneBy(array("id" => $arrDados['caracteristica']));
                    if (empty($arrDados['caracteristica']))
                        throw new \Exception("A característica de endereço com o código $temp não foi encontrada.");
                }
    
                if (isset($arrDados['estruturaArmazenagem']) && !empty($arrDados['estruturaArmazenagem'])) {
                    $temp = $arrDados['estruturaArmazenagem'];
                    $arrDados['estruturaArmazenagem'] = $em->getRepository('wms:Armazenagem\Estrutura\Tipo')->findOneBy(array("id" => $arrDados['estruturaArmazenagem']));
                    if (empty($arrDados['estruturaArmazenagem']))
                        throw new \Exception("A estrutura de armazenagem com o código $temp não foi encontrada.");
                }

                if (isset($arrDados['tipoEndereco']) && !empty($arrDados['tipoEndereco'])) {
                    $temp = $arrDados['tipoEndereco'];
                    $arrDados['tipoEndereco'] = $em->getRepository('wms:Deposito\Endereco\Tipo')->findOneBy(array("id" => $arrDados['tipoEndereco']));
                    if (empty($arrDados['tipoEndereco']))
                        throw new \Exception("O tipo de endereço com o código $temp não foi encontrado.");
                }

                if (isset($arrDados['areaArmazenagem']) && !empty($arrDados['areaArmazenagem'])) {
                    $temp = $arrDados['areaArmazenagem'];
                    $arrDados['areaArmazenagem'] = $em->getRepository('wms:Deposito\AreaArmazenagem')->findOneBy(array("id" => $arrDados['areaArmazenagem']));
                    if (empty($arrDados['areaArmazenagem']))
                        throw new \Exception("A área de armazenagem com o código $temp não foi encontrada");
                }

                if (isset($arrDados['deposito']) && !empty($arrDados['deposito'])) {
                    $temp = $arrDados['deposito'];
                    $arrDados['deposito'] = $em->getRepository('wms:Deposito')->findOneBy(array("id" => $arrDados['deposito']));
                    if (empty($arrDados['deposito']))
                        throw new \Exception("O depósito de código $temp não foi encontrado");
                }
                
                $entity = new Endereco();
                Configurator::configure($entity, $arrDados);
                
                $dscEndereco = array(
                    'RUA' => $endereco[0],
                    'PREDIO' => $endereco[1],
                    'NIVEL' => $endereco[2],
                    'APTO' => $endereco[3])
                ;
                $dscEndereco = EnderecoUtil::formatar($dscEndereco);
                
                $entity->setDescricao($dscEndereco);
                $em->persist($entity);
                $em->flush();
            }
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    private function persistirVolumes($em, $produtoEntity, $volume) {

        $volumeEntity = new Produto\Volume();

        $volumeEntity->setProduto($produtoEntity);
        $volumeEntity->setGrade($produtoEntity->getGrade());
        $volumeEntity->setLargura($volume['largura']);
        $volumeEntity->setProfundidade($volume['profundidade']);
        $volumeEntity->setCubagem($volume['cubagem']);
        $volumeEntity->setPeso($volume['peso']);
        $volumeEntity->setAltura($volume['altura']);
        $volumeEntity->setCodigoSequencial($volume['sequenciaVolume']);
        $volumeEntity->setDescricao($volume['descricaoVolume']);
        $volumeEntity->setCBInterno($volume['cbInterno']);
        $volumeEntity->setImprimirCB($volume['imprimirCb']);
        $volumeEntity->setCodigoBarras($volume['codigoBarras']);
        $volumeEntity->setCapacidadePicking($volume['capacidadePicking']);
        $volumeEntity->setPontoReposicao(0);
        $volumeEntity->setEndereco(null);

        if (!empty($volume['normaPaletizacao'])) {
            $normaPaletizacaoEntity = $em->getReference('wms:Produto\NormaPaletizacao', $volume['normaPaletizacao']);
            $volumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($volumeEntity);

        // gera o codigo de barras com base no id do volume. Ex: 12340102 / 12340202
        if ($volume['cbInterno'] == 'S') {
            $codigoBarras = $volumeEntity->getId();
            $codigoBarras .= Produto::preencheZerosEsquerda($volume['sequenciaVolume'], 2);
            $codigoBarras .= Produto::preencheZerosEsquerda($produtoEntity->getNumVolumes(), 2);
            $codigoBarras = CodigoBarras::formatarCodigoEAN128Volume($codigoBarras);
            $volumeEntity->setCodigoBarras($codigoBarras);
        }
    }

}