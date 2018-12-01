angular.module("app").controller("inventarioCtrl", function($scope, $http, $filter){
    $scope.maxPerPage = 15;
    $scope.showLoading = false;
    $scope.showList = false;
    $scope.showNoResults = false;
    $scope.showPaginatorResult = false;
    $scope.showPaginatorElements = false;
    $scope.resultFormRequest = [];
    $scope.elementosSelecionados = [];
    var rotasRequest = {
        endereco:  "/index/get-enderecos-criar-ajax",
        produto: "/index/get-produtos-criar-ajax"
    };

    $scope.clearForm = function() {
        $scope.criterioForm = {
            codProduto: undefined,
            grade: undefined,
            descricao: undefined,
            fabricante: undefined,
            linhaSep: undefined,
            classe: undefined,
            ruaInicial: undefined,
            ruaFinal: undefined,
            predioInicial: undefined,
            predioFinal: undefined,
            nivelInicial: undefined,
            nivelFinal: undefined,
            aptoInicial: undefined,
            aptoFinal: undefined,
            lado: undefined,
            situacao: undefined,
            status: undefined,
            ativo: undefined,
            idCarac: undefined,
            estrutArmaz: undefined,
            tipoEnd: undefined,
            areaArmaz: undefined
        };
    };

    $scope.clearForm();

    var newPaginator = function() {
        return {
            pages: [],
            actPage: {}
        };
    };

    var newPage = function(idPage, indexStart, indexEnd, itensPerpage ){
        return {
            idPage: idPage,
            label: "Página - " + (idPage + 1),
            indexStart: indexStart,
            indexEnd: indexEnd,
            itensPerPage: itensPerpage
        }
    };

    $scope.elementsPaginator = newPaginator();
    $scope.resultPaginator = newPaginator();

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

    $scope.requestForm = function (criterio) {
        $scope.showLoading = true ;
        $scope.showNoResults = false ;
        $scope.showList = false;
        $scope.resultFormRequest = [];
        var params = {criterio: criterio};
        for (var x in $scope.criterioForm) {
            var val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        ajaxRequestByFormParams(params, rotasRequest[criterio]);
    };

    var ajaxRequestByFormParams = function (params, rota) {
        $http.post(URL_MODULO + rota, params).then(function (response){
            $scope.resultFormRequest = response.data;
            $scope.ordenarPor("id","result");
        }).then(function () {
            preparePaginator('results', $scope.resultFormRequest.length);
        });
    };

    var preparePaginator = function (grid, countItens) {
        var paginator = newPaginator();
        var nPages = Math.ceil(countItens / $scope.maxPerPage);
        for (var i = 0; i < nPages; i++) {

            var start = (i * $scope.maxPerPage);
            var end = ((i + 1) * $scope.maxPerPage);

            if (i === (nPages - 1)) {
                end = countItens;
            }

            var page = newPage(i, start, ( end - 1 ), (end - start));

            paginator.pages.push(page);
            if (i === 0) paginator.actPage = page;
        }
        if (grid === 'results') {
            $scope.showNoResults = (countItens === 0);
            $scope.showPaginatorResult = (nPages > 0);
            $scope.showLoading = false;
            $scope.showList = (countItens > 0);
            $scope.resultPaginator = paginator;
        } else if (grid === 'elements') {
            $scope.showPaginatorElements = (nPages > 0);
            console.log(paginator);
            $scope.elementsPaginator = paginator;
        }
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

    $scope.checkSelected = function (grid) {
        var actPag = {};
        if (grid === 'results') {
            actPag = $scope.resultPaginator.actPage;
            $scope.resultPaginator.actPage.selectedAll = (
                $filter("filter")(
                    $scope.resultFormRequest.slice(actPag.indexStart, actPag.indexEnd + 1),
                    {checked: true}).length === actPag.itensPerPage);
        } else if (grid === 'elements') {
            actPag = $scope.elementsPaginator.actPage;
            $scope.elementsPaginator.actPage.selectedAll = ($filter("filter")(
                $scope.elementosSelecionados.slice(actPag.indexStart, actPag.indexEnd + 1),
                {checked: true}).length === actPag.itensPerPage);
        }
    };
    
    $scope.selectAllPage = function(grid) {
        if (grid === 'results') {
            angular.forEach($scope.resultFormRequest, function (obj, k) {
                if (k >= $scope.resultPaginator.actPage.indexStart && k <= $scope.resultPaginator.actPage.indexEnd) {
                    $scope.resultFormRequest[k].checked = $scope.resultPaginator.actPage.selectedAll;
                }
            })
        } else if (grid === 'elements') {
            angular.forEach($scope.elementosSelecionados, function (obj, k) {
                if (k >= $scope.elementsPaginator.actPage.indexStart && k <= $scope.elementsPaginator.actPage.indexEnd) {
                    $scope.elementosSelecionados[k].checked = $scope.elementsPaginator.actPage.selectedAll;
                }
            })
        }
    };

    $scope.removeSelecionado = function(elemento) {
        // var key = $scope.elementosSelecionados.findIndex(function (el) { return (el === elemento) });
        // $scope.elementosSelecionados = $scope.elementosSelecionados.slice( key, 1 );
        // preparePaginator('elements', $scope.elementosSelecionados.length);
    };
    
    $scope.incluirSelecionados = function () {
        var count = 0;
        angular.forEach($filter("filter")( $scope.resultFormRequest, {checked: true} ), function (obj) {
            if (!$filter("filter")($scope.elementosSelecionados, {id: obj.id}).length) {
                obj.checked = false;
                $scope.elementosSelecionados.unshift(obj);
                ++count;
            }
        });
        if (count > 0) preparePaginator('elements', $scope.elementosSelecionados.length);
    }

}).filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            var start = Number(interval.start);
            var end = Number(interval.end);
            return input.slice(start, (end+1));
        }
    }
});