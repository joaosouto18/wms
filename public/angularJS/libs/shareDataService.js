angular.module("wms").service("shareDataService", function () {
    let data = {};

    return {
        getAllData: function () {
            return data;
        },

        addNewData: function (property, label) {
            data[property] = label;
        },

        getDataShared: function (property) {
            return data[property];
        }
    };
});