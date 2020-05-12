angular.module("wms").controller("rastreioExpedicaoCtrl", function ($scope, $filter, $http, $window, shareDataService, uiDialogService) {

    $scope.inputMask = 'cpf';
    $scope.resultForm = {
        codExpedicao: null,
        codCarga: null,
        cpfCnpj: null,
        nomCliente: null,
        dthInicial1: null,
        dthInicial2: null,
        dthFinal1: null,
        dthFinal2: null,
        lote: null
    };

    $scope.results = [];
    $scope.noResults = false;
    $scope.showLoading = true;
    $scope.objectFilter = {};

    $scope.formatCpfCnpj = function () {
        let cpfCnpj = $scope.resultForm.cpfCnpj
        if (!isEmpty(cpfCnpj)) {
            let valClean = cpfCnpj.replace(/\D/g, '');
            if (valClean.length <= 11) {
                valClean = valClean.replace(/\D/g,"")
                valClean = valClean.replace(/(\d{3})(\d)/,"$1.$2")
                valClean = valClean.replace(/(\d{3})(\d)/,"$1.$2")
                valClean = valClean.replace(/(\d{3})(\d{1,2})$/,"$1-$2")
                $scope.resultForm.cpfCnpj = valClean;
            } else if (valClean.length > 11) {
                valClean = valClean.replace(/\D/g,"")
                valClean = valClean.replace(/^(\d{2})(\d)/,"$1.$2")
                valClean = valClean.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3")
                valClean = valClean.replace(/\.(\d{3})(\d)/,".$1/$2")
                valClean = valClean.replace(/(\d{4})(\d{2})/,"$1-$2").substring(0,18);
                $scope.resultForm.cpfCnpj = valClean;
            }
        }
    }

    $scope.initForm = function () {
        let today = (new Date()).toLocaleDateString();
        $scope.resultForm.dthInicial1 = today;
        $scope.resultForm.dthInicial2 = today;
        $scope.resultForm.dthFinal1 = today;
        $scope.resultForm.dthFinal2 = today;
        $scope.requestSent = false;
    }

    let configGridColumns = function () {
        $scope.gridColumns = [
            { name: 'codExpedicao'      , label: 'Expedição'   , width: '6%'  , filter: { type: 'text', size: 5 } },
            { name: 'codCarga'          , label: 'Carga'       , width: '6%'  , filter: { type: 'text', size: 5 } },
            { name: 'codPedido'         , label: 'Pedido'      , width: '6%'  , filter: { type: 'text', size: 5 } },
            { name: 'nomCliente'        , label: 'Cliente'     , width: '17%' , filter: { type: 'text', size: 27, uppercase: true } },
            { name: 'dthInicio'         , label: 'Inicio'      , width: '6%'  , filter: { type: 'text', size: 6, masked: true, maskFilter: '99/99/9999'} },
            { name: 'dthFim'            , label: 'Fim'         , width: '6%'  , filter: { type: 'text', size: 6, masked: true, maskFilter: '99/99/9999'} },
            { name: 'codProduto'        , label: 'Item'        , width: '6%'  , filter: { type: 'text', size: 5, } },
            { name: 'dscProd'           , label: 'Produto'     , width: '22%' , filter: { type: 'text', size: 37, uppercase: true } },
            { name: 'grade'             , label: 'Grade'       , width: '9%'  , filter: { type: 'text', size: 11, } },
            { name: 'lote'              , label: 'Lote'        , width: '10%' , filter: { type: 'text', size: 13, } },
            { name: 'qtdAtendida'       , label: 'Qtd.'        , width: '6%'  , filter: { type: 'text', size: 5, } },
        ];
    };

    $scope.sendRequest = function() {
        $scope.results = [];
        $scope.showLoading = true;
        $scope.objectFilter = {};

        if (!isEmpty($scope.resultForm.codExpedicao) || !isEmpty($scope.resultForm.codCarga)) {
            $scope.resultForm.dthInicial1 = null;
            $scope.resultForm.dthInicial2 = null;
            $scope.resultForm.dthFinal1 = null;
            $scope.resultForm.dthFinal2 = null;
        }

        let strParams = "";

        for(let inptName in $scope.resultForm) {
            if (!isEmpty($scope.resultForm[inptName])) {
                strParams += "/" + inptName + "/" + encodeURIComponent($scope.resultForm[inptName]);
            }
        }

        if (isEmpty(strParams)) {
            uiDialogService.dialogAlert("Nenhum parâmetro foi especificado!");
            return
        }

        $http.get(URL_MODULO + "/relatorio_saida/get-rastreio-results-ajax" + strParams).then(function (response){
            $scope.results = response.data.results;
        }).then(function () {
            $scope.noResults = isEmpty($scope.results);
            $scope.requestSent = true;
            $scope.showLoading = false ;
            configGridColumns();
        });
    };

    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.export = function (destino) {

        let result = $filter("filter")($scope.results, $scope.objectFilter);
        result = $filter("orderBy")(result, $scope.tbOrderBy, $scope.direction, typeSensitiveComparatorFn());

        let params = {
            destino: destino,
            results: result
        };

        $http.post(URL_MODULO + '/relatorio_saida/export-rastreio-ajax', params, { responseType: 'arraybuffer' })
            .then(function (response) {
                    try {
                        let blob = new Blob([response.data], { type: 'application/' + destino });
                        extractFile(blob, 'Rastreio de Expedição.' + destino);
                    } catch (ex) {
                        console.log(ex);
                    }
                }
            )
    };

    $scope.ordenarPor("codExpedicao");
});