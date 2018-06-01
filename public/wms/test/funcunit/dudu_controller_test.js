/*global module: true, ok: true, equals: true, S: true, test: true */
module("dudu", {
	setup: function () {
		// open the page
		S.open("//wms/wms.html");

		//make sure there's at least one dudu on the page before running a test
		S('.dudu').exists();
	},
	//a helper function that creates a dudu
	create: function () {
		S("[name=name]").type("Ice");
		S("[name=description]").type("Cold Water");
		S("[type=submit]").click();
		S('.dudu:nth-child(2)').exists();
	}
});

test("dudus present", function () {
	ok(S('.dudu').size() >= 1, "There is at least one dudu");
});

test("create dudus", function () {

	this.create();

	S(function () {
		ok(S('.dudu:nth-child(2) td:first').text().match(/Ice/), "Typed Ice");
	});
});

test("edit dudus", function () {

	this.create();

	S('.dudu:nth-child(2) a.edit').click();
	S(".dudu input[name=name]").type(" Water");
	S(".dudu input[name=description]").type("\b\b\b\b\bTap Water");
	S(".update").click();
	S('.dudu:nth-child(2) .edit').exists(function () {

		ok(S('.dudu:nth-child(2) td:first').text().match(/Ice Water/), "Typed Ice Water");

		ok(S('.dudu:nth-child(2) td:nth-child(2)').text().match(/Cold Tap Water/), "Typed Cold Tap Water");
	});
});

test("destroy", function () {

	this.create();

	S(".dudu:nth-child(2) .destroy").click();

	//makes the next confirmation return true
	S.confirm(true);

	S('.dudu:nth-child(2)').missing(function () {
		ok("destroyed");
	});

});