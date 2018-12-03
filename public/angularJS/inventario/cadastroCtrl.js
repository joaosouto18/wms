angular.module("app").controller("inventarioCtrl", function($scope, $http, $filter, $window){
    $scope.maxPerPage = 15;
    $scope.showLoading = false;
    $scope.showList = false;
    $scope.showNoResults = false;
    $scope.showPaginatorResult = false;
    $scope.showPaginatorElements = false;
    $scope.resultForm = [];
    $scope.elements = [];
    var rotasRequest = {
        endereco:  "/index/get-enderecos-criar-ajax",
        produto: "/index/get-produtos-criar-ajax"
    };

    $scope.gridState = {
        requesting: false,
        noResult: false,
        hasResult: false
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
            actPage: {},
            show: false
        };
    };

    var newPage = function(idPage, indexStart, indexEnd, itensPerpage ){
        return {
            idPage: idPage,
            label: "Página - " + (idPage + 1),
            indexStart: indexStart,
            indexEnd: indexEnd,
            itensPerPage: itensPerpage,
            selectedAll: false
        }
    };

    $scope.elementsPaginator = newPaginator();
    $scope.resultFormPaginator = newPaginator();

    $scope.ordenarPor = function (campo, grid) {
        $scope[grid + 'Direction'] = (campo !== null && $scope[grid + 'OrderBy'] === campo) ? !$scope[grid + 'Direction'] : true;
        $scope[grid + 'OrderBy'] = campo;
    };

    $scope.changePage = function (destination, grid) {
        if ((destination > 0  && ($scope[grid + 'Paginator'].actPage.idPage + 1 ) === $scope[grid + 'Paginator'].size )
            || (destination < 0 && $scope[grid + 'Paginator'].actPage.idPage === 0)) return;

        $scope[grid + 'Paginator'].actPage = $scope[grid + 'Paginator'].pages[ $scope[grid + 'Paginator'].actPage.idPage + destination ];
    };

    $scope.requestForm = function (criterio, grid) {
        var params = {criterio: criterio};
        for (var x in $scope.criterioForm) {
            var val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        $scope[grid] = [];
        $scope.gridState.requesting = true ;
        $scope.gridState.noResult = false;
        $scope.resultFormPaginator.show = false;
        ajaxRequestByFormParams(params, rotasRequest[criterio], grid);
    };

    var ajaxRequestByFormParams = function (params, rota, grid) {
        $http.get(URL_MODULO + rota, {params: params}).then(function (response){
            $scope[grid] = response.data;
            $scope.ordenarPor("id","result");
        }).then(function () {
            $scope.gridState.requesting = false;
            $scope.gridState.noResult = ( $scope.resultForm.length === 0 ) ;
            preparePaginator(grid, $scope.resultForm.length);
        });
    };

    var preparePaginator = function (grid, countItens, actPageId) {
        
        var paginator = newPaginator();

        if (countItens) {
            var nPages = Math.ceil(countItens / $scope.maxPerPage);
            for (var i = 0; i < nPages; i++) {

                var start = (i * $scope.maxPerPage);
                var end = ((i + 1) * $scope.maxPerPage);

                if (i === (nPages - 1)) {
                    end = countItens;
                }

                var page = newPage(i, start, (end - 1), (end - start));

                paginator.pages.push(page);
            }

            paginator.actPage = (paginator.pages[actPageId])? paginator.pages[actPageId] : paginator.pages[0];
        }

        paginator.show = (paginator.pages.length > 0);
        $scope[grid + 'Paginator'] = paginator;

    };

    $scope.checkSelected = function (grid) {
        $scope[grid + 'Paginator'].actPage.selectedAll = (
            $filter("filter")(
                $scope[grid].slice($scope[grid + 'Paginator'].actPage.indexStart, $scope[grid + 'Paginator'].actPage.indexEnd + 1),
                {checked: true}).length === $scope[grid + 'Paginator'].actPage.itensPerPage);
    };
    
    $scope.selectAllPage = function(grid) {
        angular.forEach($scope[grid], function (obj, k) {
            if (k >= $scope[grid + 'Paginator'].actPage.indexStart && k <= $scope[grid + 'Paginator'].actPage.indexEnd) {
                $scope[grid][k].checked = $scope[grid + 'Paginator'].actPage.selectedAll;
            }
        })
    };

    $scope.removeSelecionado = function(obj) {
        $scope.elements.splice($scope.elements.findIndex(function (el) { return (el === obj) }), 1 );
        preparePaginator('elements', $scope.elements.length, $scope.elementsPaginator.actPage.idPage);
    };
    
    $scope.incluirSelecionados = function () {
        var count = 0;
        angular.forEach($filter("filter")( $scope.resultForm, {checked: true} ), function (obj) {
            obj.checked = false;
            if (!$filter("filter")($scope.elements, {id: obj.id}, true).length) {
                $scope.elements.unshift(angular.copy(obj));
                ++count;
            }
        });
        if (count > 0) preparePaginator('elements', $scope.elements.length);
        $scope.selectAll('resultForm', true, true)
    };

    $scope.selectAll = function (grid, undo, onlyPaginator) {
        if (!onlyPaginator) angular.forEach($scope[grid], function (obj) { obj.checked = !undo; });
        angular.forEach($scope[grid + 'Paginator'], function (obj) { obj.selectedAll = !undo; })
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

    $scope.criarInventario = function (criterio) {
        $http.post(URL_MODULO + '/index/criar-inventario', {
            criterio: criterio,
            descricao: 'Teste',
            selecionados: $scope.elements,
            modelo: 1
        }).then(function () {
            $window.location.href = URL_MODULO
        })
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