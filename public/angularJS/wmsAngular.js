angular.module("wms", [])
    .filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            let start = Number(interval.start);
            let end = Number(interval.end);
            return input.slice(start, (end + 1));
        }
    }
});