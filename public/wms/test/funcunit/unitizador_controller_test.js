/*global module: true, ok: true, equals: true, S: true, test: true */
module("unitizador", {
	setup: function () {
		// open the page
		S.open("//wms/wms.html");

		//make sure there's at least one unitizador on the page before running a test
		S('.unitizador').exists();
	},
	//a helper function that creates a unitizador
	create: function () {
		S("[name=name]").type("Ice");
		S("[name=description]").type("Cold Water");
		S("[type=submit]").click();
		S('.unitizador:nth-child(2)').exists();
	}
});

test("unitizadors present", function () {
	ok(S('.unitizador').size() >= 1, "There is at least one unitizador");
});

test("create unitizadors", function () {

	this.create();

	S(function () {
		ok(S('.unitizador:nth-child(2) td:first').text().match(/Ice/), "Typed Ice");
	});
});

test("edit unitizadors", function () {

	this.create();

	S('.unitizador:nth-child(2) a.edit').click();
	S(".unitizador input[name=name]").type(" Water");
	S(".unitizador input[name=description]").type("\b\b\b\b\bTap Water");
	S(".update").click();
	S('.unitizador:nth-child(2) .edit').exists(function () {

		ok(S('.unitizador:nth-child(2) td:first').text().match(/Ice Water/), "Typed Ice Water");

		ok(S('.unitizador:nth-child(2) td:nth-child(2)').text().match(/Cold Tap Water/), "Typed Cold Tap Water");
	});
});

test("destroy", function () {

	this.create();

	S(".unitizador:nth-child(2) .destroy").click();

	//makes the next confirmation return true
	S.confirm(true);

	S('.unitizador:nth-child(2)').missing(function () {
		ok("destroyed");
	});

});