angular.module("app").controller("cadastroInventarioCtrl", function($scope, $http, $filter){
    $scope.maxPerPage = 15;
    $scope.showLoading = false ;
    $scope.showList = false;
    $scope.resultFormRequest = [];
    $scope.elementosSelecionados = [];

    $scope.clearForm = function() {
        console.log("chamou");
        $scope.criterioForm = {
            codProduto: undefined,
            grade: undefined,
            descricao: undefined,
            fabricante: undefined,
            inicialRua: undefined,
            finalRua: undefined,
            inicialPredio: undefined,
            finalPredio: undefined,
            inicialNivel: undefined,
            finalNivel: undefined,
            inicialApartamento: undefined,
            finalApartamento: undefined,
            lado: undefined,
            situacao: undefined,
            status: undefined,
            ativo: undefined,
            idCaracteristica: undefined,
            idEstruturaArmazenagem: undefined,
            idTipoEndereco: undefined,
            idAreaArmazenagem: undefined
        };
    };

    $scope.clearForm();

    $scope.removeSelecionado = function(elemento) {
        console.log(elemento);
    };

    $scope.resultPaginator = $scope.elementsPaginator= {
        pages: [],
        actPage: {},
        size: 0
    };

    $scope.ordenarPor = function (campo, grid) {
        if (grid === 'elements') {
            $scope.directionElements = (campo !== null && $scope.elementsOrderBy === campo) ? !$scope.directionElements : true;
            $scope.elementsOrderBy = campo;
        } else if (grid === 'results') {
            $scope.directionResults = (campo !== null && $scope.resultsOrderBy === campo) ? !$scope.directionResults : true;
            $scope.resultsOrderBy = campo;
        }
    };

    $scope.changePage = function (destination, grid) {
        if (grid === 'elements') {
            if ((destination > 0  && ($scope.elementsPaginator.actPage.idPage + 1 ) === $scope.elementsPaginator.size )
                || (destination < 0 && $scope.elementsPaginator.actPage.idPage === 0)) return;

            $scope.elementsPaginator.actPage = $scope.elementsPaginator.pages[ $scope.elementsPaginator.actPage.idPage + destination ];
        } else if (grid === 'results') {
            if ((destination > 0  && ($scope.resultPaginator.actPage.idPage + 1 ) === $scope.resultPaginator.size )
                || (destination < 0 && $scope.resultPaginator.actPage.idPage === 0)) return;

            $scope.resultPaginator.actPage = $scope.resultPaginator.pages[ $scope.resultPaginator.actPage.idPage + destination ];
        }
    };

    $scope.requestForm = function () {
        $scope.showLoading = true ;
        $scope.showList = !$scope.showLoading;
        var params = {};
        for (var x in $scope.criterioForm){
            var val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        ajaxRequestByFormParams(params);
    };

    var ajaxRequestByFormParams = function (params) {
        $http.post(URL_MODULO + "/index/get-elements-inventario-ajax", params).then(function (response){
            $scope.resultFormRequest = response.data;
            preparePaginator();
        }).then(function () {
            $scope.showLoading = ($scope.resultFormRequest.length === 0) ;
            $scope.showList = !$scope.resultFormRequest;
        });
    };

    var preparePaginator = function () {
        var nPages = Math.ceil($scope.resultFormRequest.length / $scope.maxPerPage);
        for (var i = 0; i < nPages; i++) {

            var start = ( i * $scope.maxPerPage );
            var end = ( ( i + 1 ) * $scope.maxPerPage ) - 1 ;

            if (i === nPages) {
                end = $scope.resultFormRequest.length - 1;
            }

            var page = {
                idPage: i,
                label: "Página - " + (i + 1),
                indexStart: start,
                indexEnd: end
            };
            $scope.resultPaginator.pages.push(page);
            if (i === 0 ) $scope.resultPaginator.actPage = page;
        }
        $scope.resultPaginator.size = nPages;
    };

    $scope.typeSensitiveComparator = function(v1, v2) {
        // If we don't get strings, just compare by index
        if (angular.isNumber(Number(v1.value)) && angular.isNumber(Number(v2.value))) {
            return (Number(v1.value) < Number(v2.value)) ? -1 : 1;
        } else if (v1.type !== 'string' || v2.type !== 'string') {
            return (v1.index < v2.index) ? -1 : 1;
        }

        // Compare strings alphabetically, taking locale into account
        return v1.value.localeCompare(v2.value);
    };

    $scope.checkSelected = function (inventario) {
        $scope.resultPaginator[$scope.resultPaginator.findIndex(function (el) {
            return (el === inventario)
        })].checked = !inventario.checked;
        var actPag = $scope.resultPaginator.actPage;
        $scope.resultPaginator.actPage.selectedAll = ($filter("filter")(
            $scope.resultFormRequest.slice(actPag.indexStart, actPag.indexEnd ),
            {checked: true }).length === (actPag.indexEnd - actPag.indexStart)) ;
    };
    
    $scope.selectAllPage = function() {
        var page = $scope.resultPaginator.actPage;
        angular.forEach($scope.resultFormRequest, function (inv, k) {
            if ( k >= page.indexStart && k <= page.indexEnd){
                $scope.resultFormRequest[k].checked = page.selectedAll;
            }
        })
    };

    $scope.ordenarPor("id","elements");
    
}).filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            var output = [];
            var start = Number(interval.start);
            var end = Number(interval.end);
            $.each( input, function (k, v) {
                if (k >= start && k <= end) {
                    output.push(v);
                }
            });

            return output;
        }
    }
});