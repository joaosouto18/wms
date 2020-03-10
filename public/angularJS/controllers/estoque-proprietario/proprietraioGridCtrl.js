angular.module("wms").controller("proprietarioGridCtrl", function ($scope, $http, $filter, uiDialogService) {

    $scope.showGrid = false;
    $scope.results = [];
    $scope.originalResults = [];
    $scope.showLoading = true;
    $scope.objectFilter = {};
    $scope.filterNoZeros = {};

    $scope.params = {
        codProduto: null,
        proprietario: null,
        tipoBusca: "E",
        dataInicial: null,
        dataFinal: null
    };
    $scope.proprietarios = [];

    let preFilterNoZeros = function() {
        let args = {};
        if ($scope.filterNoZeros.qtdEstq) args['qtdEstq'] = '!0';
        if ($scope.filterNoZeros.qtdPend) args['qtdPend'] = '!0';

        $scope.results = $filter("filter")($scope.originalResults, args, true);
    };

    let gridColumns = {
        E: {
            nomProp: {
                name: 'nomProp',
                label: 'Proprietário',
                width: '20%',
                filter: {
                    type: 'text',
                    size: 31
                }
            },
            codProduto: {
                name: 'codProduto',
                label: 'Código',
                width: '12%'  ,
                filter: {
                    type: 'text',
                    size: 16
                }
            },
            dscProduto: {
                name: 'dscProduto',
                label: 'Produto',
                width: '44%',
                filter: {
                    type: 'text',
                    size: 76
                }
            },
            qtdEstq: {
                name: 'qtdEstq',
                label: 'Qtd Estq',
                width: '12%',
                filter: false
            },
            qtdPend: {
                name: 'qtdPend',
                label: 'Pendente p/ Entrar',
                width: '12%',
                filter: {
                    type: 'checkbox',
                    label: 'Apenas Pendentes',
                    function: preFilterNoZeros
                }
            }
        },
        H: {
            nomProp: {
                name: 'nomProp',
                label: 'Proprietário',
                width: '24%',
                filter: {
                    type: 'text',
                    size: 37
                }
            },
            codProduto: {
                name: 'codProduto',
                label: 'Código',
                width: '7%',
                filter: {
                    type: 'text',
                    size: 7
                }
            },
            dscProduto: {
                name: 'dscProduto',
                label: 'Produto',
                width: '33%',
                filter: {
                    type: 'text',
                    size: 54
                }
            },
            tipoMov: {
                name: 'tipoMov',
                label: 'Tipo Mov.',
                width: '12%',
                filter: {
                    type: 'select',
                    size: 19,
                    options: [],
                    dataSource: 'arrayList',
                    resource: 'gridResult',
                    filterByValue: true
                }
            },
            dthMov: {
                name: 'dthMov',
                label: 'Data Mov.',
                width: '7%',
                filter: {
                    type: 'text',
                    size: 6,
                    masked: true,
                    maskFilter: '99/99/9999'
                }
            },
            qtdMov: {
                name: 'qtdMov',
                label: 'Qtd Mov.',
                width: '5%',
                filter: false
            },
            qtdEstq: {
                name: 'qtdEstq',
                label: 'Saldo Final',
                width: '5%',
                filter: false
            },
        }
    };

    let resetFilters = function() {
        $scope.objectFilter = {};
        $scope.filterNoZeros = {
            qtdEstq: false,
            qtdPend: false
        };
    };

    let configGridColumns = function () {
        let grid = gridColumns[$scope.params.tipoBusca];
        for (let colName in grid) {
            if (grid[colName].filter.hasOwnProperty('type') && grid[colName].filter.type === 'select') {
                grid[colName] = prepareFilterOptions(grid[colName]);
            }
        }
        $scope.gridColumns = grid;
    };

    let hidrateFilterOptionByGridResult = function(column) {
        let newOptions = [];
        angular.forEach($scope.results, function (row) {
            if (newOptions.indexOf(row[column.name]) === -1){
                newOptions.push(row[column.name])
            }
        });
        return newOptions;
    };

    let prepareFilterOptions = function(column) {
        if (column.filter.resource === 'gridResult') {
            if (column.filter.dataSource === 'arrayList') {
                column.filter.options = hidrateFilterOptionByGridResult(column);
                return column;
            }
        }
    };

    $scope.prepareForm = function () {
        $http.get(URL_MODULO + "/relatorio_estoque-proprietario/get-list-proprietarios-ajax").then(function (response){
            $scope.proprietarios = response.data.proprietarios
        });
        resetFilters();
    };

    $scope.buscar = function() {
        let strParams = "";
        $scope.showGrid = true;
        $scope.showLoading = true ;
        $scope.results = [];
        $scope.originalResults = [];
        resetFilters();

        if (!isEmpty($scope.params.codProduto))
            strParams += "/codProduto/" + $scope.params.codProduto;

        if (!isEmpty($scope.params.proprietario))
            strParams += "/codProp/" + $scope.params.proprietario.id;

        if (!isEmpty($scope.params.tipoBusca))
            strParams += "/tipoBusca/" + $scope.params.tipoBusca;

        if (!isEmpty($scope.params.dataInicial))
            strParams += "/dataInicial/" + encodeURIComponent($scope.params.dataInicial);

        if (!isEmpty($scope.params.dataFinal))
            strParams += "/dataFinal/" + encodeURIComponent($scope.params.dataFinal);

        $http.get(URL_MODULO + "/relatorio_estoque-proprietario/get-gerencial-proprietario-ajax" + strParams)
            .then(
                function (response) {
                    if (typeof response.data === "string" && response.data.indexOf("Fatal error: Allowed memory size") !== 0) {
                        uiDialogService.dialogAlert("Os parâmetros do filtro estão excedendo a capacidade de resposta, tente por favor: <b><br> -> Especificar um Proprietário <br> -> Especificar um produto <br> -> Reduzir o intervalo de datas</b>");
                    } else {
                        $scope.originalResults = response.data.results;
                        $scope.results = response.data.results;
                    }
                },
            )
            .then(
                function () {
                    configGridColumns();
                    $scope.showLoading = false ;
                }
            );
    };

    $scope.setDateInterval = function () {
        if ($scope.params.tipoBusca === 'H') {
            let hoje = (new Date()).toLocaleDateString();
            let dia1 = hoje.split("/");
            dia1[0] = "01";
            $scope.params.dataInicial = dia1.join("/");
            $scope.params.dataFinal = hoje;
        } else {
            $scope.params.dataInicial = null;
            $scope.params.dataFinal = null;
        }
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
            divergencias: result
        };

        let config = { responseType: 'arraybuffer' };

        $http.post(URL_MODULO + '/index/export-divergencias-ajax', params, config)
            .then(function (response) {
                    try {
                        let blob = new Blob([response.data], { type: 'application/'+destino });
                        let url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    } catch (ex) {
                        console.log(ex);
                    }
                }
            )
    };

});