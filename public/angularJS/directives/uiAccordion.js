angular.module("wms").directive("uiAccordion", function () {
    return {
        templateUrl: "andamento-accordion.html",
        scope: {
            title: "@"
        }
    }
});