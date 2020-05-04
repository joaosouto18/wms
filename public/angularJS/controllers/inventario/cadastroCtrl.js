angular.module("wms").controller("cadastroInventarioCtrl", function($scope, $http, $filter, uiDialogService, shareDataService){
    $scope.maxPerPage = 15;
    $scope.showLoading = false;
    $scope.showList = false;
    $scope.showNoResults = false;
    $scope.showPaginatorResult = false;
    $scope.showPaginatorElements = false;
    $scope.resultForm = [];
    $scope.elements = [];
    $scope.typeSensitiveComparator = typeSensitiveComparatorFn();

    let rotasRequest = {
        E:  "/index/get-enderecos-criar-ajax",
        P: "/index/get-produtos-criar-ajax"
    };

    let arrConfigColumns = {
        E:  [
            { name: 'dscEndereco', label: 'Endereço', type: 'ordenator', width: '13%', orderBy: 'cleanEnd'},
            { name: 'caracEnd', label: 'Característica', type: 'ordenator', width: '19%'},
            { name: 'dscArea', label: 'Área', type: 'ordenator', width: '30%'},
            { name: 'dscEstrutura', label: 'Estrutura', type: 'ordenator', width: '28%'}
        ],
        P: [
            { name: 'codProduto', label: 'Código', type: 'ordenator', width: '19%'},
            { name: 'dscProduto', label: 'Descrição', type: 'ordenator', width: '34%'},
            { name: 'grade', label: 'Grade', type: 'ordenator', width: '24%'},
            { name: 'dscEndereco', label: 'Endereço', type: 'ordenator', width: '13%', orderBy: 'cleanEnd'}
        ]
    };

    $scope.initCreate = function (criterio) {
        this.configGridColumns(criterio);
        if (!isEmpty(itens)) {
            $scope.resultForm = itens;
            $scope.ordenarPor("id","result");
            preparePaginator('resultForm', $scope.resultForm.length);
        }
    };

    $scope.configGridColumns = function (criterio) {
        if (!isEmpty(criterio)) {
            $scope.criterio = criterio;
            $scope.gridColumnsResult = angular.copy(arrConfigColumns[$scope.criterio]);
            $scope.gridColumnsResult.unshift({type: 'checkBox'});
            $scope.gridColumnsElements = angular.copy(arrConfigColumns[$scope.criterio]);
            $scope.gridColumnsElements.push({label: 'Ação', type: 'dropAction'});
        }
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
            bloqueada: undefined,
            incluirPicking: undefined,
            status: undefined,
            ativo: undefined,
            idCarac: undefined,
            estrutArmaz: undefined,
            tipoEnd: undefined,
            areaArmaz: undefined
        };
    };

    $scope.clearForm();

    let newPaginator = function() {
        return {
            pages: [],
            actPage: {},
            show: false,
            selectedAll: false
        };
    };

    let newPage = function(idPage, indexStart, indexEnd, itensPerpage ){
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

    $scope.ordenarPor = function (column, grid) {
        let campo = (column.hasOwnProperty("orderBy")) ? column.orderBy : column.name;
        $scope[grid + 'Direction'] = (campo !== null && $scope[grid + 'OrderBy'] === campo) ? !$scope[grid + 'Direction'] : true;
        $scope[grid + 'OrderBy'] = campo;
    };

    $scope.changePage = function (destination, grid) {
        if ((destination > 0  && ($scope[grid + 'Paginator'].actPage.idPage + 1 ) === $scope[grid + 'Paginator'].pages.length )
            || (destination < 0 && $scope[grid + 'Paginator'].actPage.idPage === 0)) return;

        $scope[grid + 'Paginator'].actPage = $scope[grid + 'Paginator'].pages[ $scope[grid + 'Paginator'].actPage.idPage + destination ];
    };

    $scope.requestForm = function () {
        let params = {criterio: $scope.criterio};
        for (let x in $scope.criterioForm) {
            let val = $scope.criterioForm[x];
            if (val) params[x] = val;
        }
        $scope.resultForm = [];
        $scope.gridState.requesting = true ;
        $scope.gridState.noResult = false;
        $scope.resultFormPaginator.show = false;
        ajaxRequestByFormParams(params, rotasRequest[$scope.criterio]);
    };

    let ajaxRequestByFormParams = function (params, rota) {
        $http.get(URL_MODULO + rota, {params: params}).then(function (response){
            $scope.resultForm = response.data;
            $scope.ordenarPor("id","result");
        }).then(function () {
            $scope.gridState.requesting = false;
            $scope.gridState.noResult = ( $scope.resultForm.length === 0 ) ;
            preparePaginator('resultForm', $scope.resultForm.length);
        });
    };

    let preparePaginator = function (grid, countItens, actPageId) {

        let paginator = newPaginator();

        if (countItens) {
            let nPages = Math.ceil(countItens / $scope.maxPerPage);
            for (let i = 0; i < nPages; i++) {

                let start = (i * $scope.maxPerPage);
                let end = ((i + 1) * $scope.maxPerPage);

                if (i === (nPages - 1)) {
                    end = countItens;
                }

                let page = newPage(i, start, (end - 1), (end - start));

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
                {checked: true}).length === $scope[grid + 'Paginator'].actPage.itensPerPage
        );

        $scope[grid + 'Paginator'].selectedAll = (
            $filter("filter")(
                $scope[grid + 'Paginator'].pages,
                {selectedAll: true}).length === $scope[grid + 'Paginator'].pages.length
        );
    };

    let selectAll = function (grid, checked) {
        angular.forEach($scope[grid], function (obj) { obj.checked = checked; });
        $scope[grid + 'Paginator'].selectedAll = checked;
        angular.forEach($scope[grid + 'Paginator'].pages, function (obj) { obj.selectedAll = checked; });

    };

    $scope.selectAllPage = function(grid) {
        if ($scope[grid].length === 0) {
            $scope[grid + 'Paginator'].actPage.selectedAll = false;
            return;
        }
        angular.forEach($scope[grid], function (obj, k) {
            if (k >= $scope[grid + 'Paginator'].actPage.indexStart && k <= $scope[grid + 'Paginator'].actPage.indexEnd) {
                $scope[grid][k].checked = $scope[grid + 'Paginator'].actPage.selectedAll;
            }
        });
        $scope.checkSelected(grid);
    };

    $scope.selectAllGrid = function(grid) {
        if ($scope[grid + 'Paginator'].selectedAll) {
            if ($scope[grid].length === 0) {
                $scope[grid + 'Paginator'].selectedAll = false;
                return;
            }
            uiDialogService.dialogConfirm("Esta ação irá selecionar todos os itens de todas as páginas, deseja realmente continuar?", null, "Sim", "Não", function () {
                selectAll(grid, $scope[grid + 'Paginator'].selectedAll)
            }, null, function () {
                $scope[grid + 'Paginator'].selectedAll = false;
                selectAll(grid, $scope[grid + 'Paginator'].selectedAll)
            })
        } else {
            selectAll(grid, $scope[grid + 'Paginator'].selectedAll)
        }
    };

    $scope.removeSelecionado = function(obj) {
        $scope.elements.splice($scope.elements.findIndex(function (el) { return (el === obj) }), 1 );
        preparePaginator('elements', $scope.elements.length, $scope.elementsPaginator.actPage.idPage);
    };
    
    $scope.incluirSelecionados = function () {
        let count = 0;
        angular.forEach($filter("filter")( $scope.resultForm, {checked: true} ), function (obj) {
            obj.checked = false;
            let arg = {id: obj.id};
            if ($scope.criterio === 'P' ) {
                arg.codProduto = obj.codProduto;
                arg.grade = obj.grade;
            }
            if (!$filter("filter")($scope.elements, arg, true).length) {
                $scope.elements.unshift(angular.copy(obj));
                ++count;
            }
        });
        if (count > 0) preparePaginator('elements', $scope.elements.length);
        selectAll('resultForm', false)
    };

    $scope.showPreviewer = function() {
        if ($scope.elements.length) {
            shareDataService.addNewData("criterio", $scope.criterio);
            shareDataService.addNewData("itens", $scope.elements);
            shareDataService.addNewData("gridColumns", $scope.gridColumnsElements);
            uiDialogService.dialogModal("previewer-inventario.html", true, "Preview do Novo Inventário",780,null,null,['center', 80]);
        } else {
            uiDialogService.dialogAlert("Nenhum elemento foi adicionado na lista");
        }
    };
});