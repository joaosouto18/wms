angular.module("app").controller("InventarioCtrl", function($scope, $http){
    $scope.maxPerPage = 15;
    $scope.paginator = {
        pages: [],
        actPage: {},
        size: 0
    };

    $scope.inventarios = [];
    $scope.inventShowed = [];
    $scope.ordenarPor = function (campo) {
        $scope.direction = (campo !== null && $scope.tbOrderBy === campo) ? !$scope.direction : true;
        $scope.tbOrderBy = campo;
    };

    $scope.changePage = function (destination) {
        if ((destination > 0  && ($scope.paginator.actPage.idPage + 1 ) === $scope.paginator.size )
            || (destination < 0 && $scope.paginator.actPage.idPage === 0)) return;

        var page = $scope.paginator.actPage;
        $scope.paginator.actPage = $scope.paginator.pages[ page.idPage + destination ];
    };

    var getInventarios = function (params) {
        $http.get(URL_MODULO + "/index/get-inventarios-ajax").then(function (response){
            $scope.inventarios = response.data;
            preparePaginator();
        },function (error){

        });
    };

    var preparePaginator = function () {
        var nPages = Math.ceil($scope.inventarios.length / $scope.maxPerPage);
        for (var i = 0; i < nPages; i++) {

            var start = ( i * $scope.maxPerPage );
            var end = ( ( i + 1 ) * $scope.maxPerPage );

            if (i === nPages) {
                end = $scope.inventarios.length - 1;
            }

            var page = {
                idPage: i,
                label: "Página - " + (i + 1),
                indexStart: start,
                indexEnd: end
            };
            $scope.paginator.pages.push(page);
            if (i === 0 ) $scope.paginator.actPage = page;
        }
        $scope.paginator.size = nPages;
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
    
    $scope.selectAllPage = function() {

    };

    getInventarios([]);
    $scope.ordenarPor("id");
    
}).filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            var output = [];
            var start = Number(interval.start);
            var end = Number(interval.end);
            $.each( input, function (k, v) {
                var key = Number(v.id);
                if ((key > start && key < end) || (key === start || key === end)) {
                    output.push(v);
                }
            });
            return output;
        }
    }
});