/*global module: true, ok: true, equals: true, S: true, test: true */
module("foo", {
	setup: function () {
		// open the page
		S.open("//wms/wms.html");

		//make sure there's at least one foo on the page before running a test
		S('.foo').exists();
	},
	//a helper function that creates a foo
	create: function () {
		S("[name=name]").type("Ice");
		S("[name=description]").type("Cold Water");
		S("[type=submit]").click();
		S('.foo:nth-child(2)').exists();
	}
});

test("foos present", function () {
	ok(S('.foo').size() >= 1, "There is at least one foo");
});

test("create foos", function () {

	this.create();

	S(function () {
		ok(S('.foo:nth-child(2) td:first').text().match(/Ice/), "Typed Ice");
	});
});

test("edit foos", function () {

	this.create();

	S('.foo:nth-child(2) a.edit').click();
	S(".foo input[name=name]").type(" Water");
	S(".foo input[name=description]").type("\b\b\b\b\bTap Water");
	S(".update").click();
	S('.foo:nth-child(2) .edit').exists(function () {

		ok(S('.foo:nth-child(2) td:first').text().match(/Ice Water/), "Typed Ice Water");

		ok(S('.foo:nth-child(2) td:nth-child(2)').text().match(/Cold Tap Water/), "Typed Cold Tap Water");
	});
});

test("destroy", function () {

	this.create();

	S(".foo:nth-child(2) .destroy").click();

	//makes the next confirmation return true
	S.confirm(true);

	S('.foo:nth-child(2)').missing(function () {
		ok("destroyed");
	});

});